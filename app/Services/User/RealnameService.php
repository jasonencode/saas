<?php

namespace App\Services\User;

use App\Contracts\ServiceInterface;
use App\Enums\User\RealnameStatus;
use App\Enums\User\RealnameType;
use App\Events\User\UserRealnameApproved;
use App\Events\User\UserRealnameRejected;
use App\Models\User\UserRealname;
use InvalidArgumentException;

class RealnameService implements ServiceInterface
{
    /**
     * 提交实名认证（新增或更新同一认证类型的记录）
     *
     * 同一用户同一认证类型仅保留一条记录（数据库 `unique(user_id, type)` 兜底）。
     *
     * 状态约束：
     * - 已通过：不可重复申请
     * - 审核中：不可重复提交
     * - 已拒绝 / 无记录：提交（已拒绝时更新原记录重置为待审核，不新增历史）
     *
     * @param  int  $userId  用户 ID
     * @param  RealnameType  $type  认证类型
     * @param  array  $data  认证资料（不含 type/user_id）
     *
     * @return UserRealname 认证记录
     */
    public function submit(int $userId, RealnameType $type, array $data): UserRealname
    {
        $realname = UserRealname::withTrashed()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->first();

        if ($realname && $realname->status === RealnameStatus::Approved) {
            throw new InvalidArgumentException(sprintf('「%s」已通过认证，不可重复申请', $type->getLabel()));
        }

        if ($realname && $realname->status === RealnameStatus::Pending) {
            throw new InvalidArgumentException('实名认证审核中，请勿重复提交');
        }

        $payload = array_merge($data, [
            'status' => RealnameStatus::Pending,
            'verified_at' => null,
            'reject_reason' => null,
        ]);

        if ($realname) {
            if ($realname->trashed()) {
                $realname->restore();
            }
            $realname->update($payload);

            return $realname->fresh();
        }

        return UserRealname::create([
            ...$payload,
            'user_id' => $userId,
            'type' => $type,
        ]);
    }

    /**
     * 审批通过实名认证
     *
     * @param  UserRealname  $realname  实名认证记录
     */
    public function approve(UserRealname $realname): void
    {
        $realname->update([
            'status' => RealnameStatus::Approved,
            'verified_at' => now(),
        ]);

        UserRealnameApproved::dispatch($realname);
    }

    /**
     * 拒绝实名认证
     *
     * @param  UserRealname  $realname  实名认证记录
     * @param  string  $reason  拒绝原因
     */
    public function reject(UserRealname $realname, string $reason): void
    {
        $realname->update([
            'status' => RealnameStatus::Rejected,
            'reject_reason' => $reason,
        ]);

        UserRealnameRejected::dispatch($realname, $reason);
    }
}
