<?php

namespace App\Services\User;

use App\Contracts\ServiceInterface;
use App\Models\User\User;
use App\Models\User\UserRelation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class UserRelationService implements ServiceInterface
{
    /**
     * 初始化所有用户的推荐关系基础数据
     *
     * - 为没有 UserRelation 记录的用户创建基础记录
     * - 重新计算所有用户的直推人数和团队人数
     *
     * @throws Throwable
     *
     * @return array{created: int, updated: int}
     */
    public function initRelationData(): array
    {
        return DB::transaction(static function () {
            $updated = 0;
            $created = 0;
            // 为没有关系记录的用户创建基础记录
            User::whereNotIn('id', static fn ($query) => $query->select('user_id')->from('user_relations'))
                ->eachById(static function (User $user) use (&$created): void {
                    UserRelation::create([
                        'user_id' => $user->id,
                        'parent_id' => null,
                        'layer' => 0,
                        'path' => '/'.$user->id.'/',
                        'direct_count' => 0,
                        'team_count' => 0,
                    ]);
                    $created++;
                }, 500);
            // 重新计算所有直推人数
            UserRelation::eachById(static function (UserRelation $relation): void {
                $relation->direct_count = UserRelation::where('parent_id', $relation->user_id)->count();
                $relation->saveQuietly();
            }, 500);
            // 重新计算所有团队人数
            UserRelation::eachById(static function (UserRelation $relation) use (&$updated): void {
                $relation->team_count = UserRelation::where('path', 'like', $relation->path.'%')
                    ->where('user_id', '!=', $relation->user_id)
                    ->count();
                $relation->saveQuietly();
                $updated++;
            }, 500);

            return ['created' => $created, 'updated' => $updated];
        });
    }

    /**
     * 创建用户关系
     *
     * @param  User  $user  用户
     * @param  int|null  $parentId  推荐人 ID
     *
     * @throws Throwable 用户关系已存在或推荐人无效
     *
     * @return bool 是否创建成功
     */
    public function createRelation(User $user, ?int $parentId = null): bool
    {
        if (UserRelation::where('user_id', $user->getKey())->exists()) {
            throw new InvalidArgumentException('用户关系已存在');
        }

        return DB::transaction(static function () use ($user, $parentId) {
            if ($parentId === $user->getKey()) {
                throw new InvalidArgumentException('不能将自己设为推荐人');
            }
            [$layer, $path] = self::resolveLayerAndPath($parentId);

            UserRelation::create([
                'user_id' => $user->getKey(),
                'parent_id' => $parentId,
                'layer' => $layer,
                'path' => $path.$user->getKey().'/',
            ]);

            self::updateAncestorCounts($user);

            return true;
        });
    }

    /**
     * 解析层级和路径
     *
     * @param  int|null  $parentId  推荐人 ID
     *
     * @throws InvalidArgumentException 推荐人不存在
     *
     * @return array{0: int, 1: string} 层级和路径
     */
    protected static function resolveLayerAndPath(?int $parentId): array
    {
        if ($parentId !== null && $parentId > 0) {
            $parent = UserRelation::find($parentId);
            if (!$parent) {
                throw new InvalidArgumentException('推荐人不存在');
            }

            return [$parent->layer + 1, $parent->path];
        }

        return [0, '/'];
    }

    /**
     * 更新上级统计
     *
     * @param  User  $user  用户
     */
    protected static function updateAncestorCounts(User $user): void
    {
        $relation = UserRelation::find($user->getKey());
        if (!$relation) {
            return;
        }

        // 获取所有上级ID
        $ancestorIds = trim($relation->path, '/')
                |> (static fn ($x) => explode('/', $x))
                |> (static fn ($x) => array_filter($x, static fn ($id) => $id > 0));

        foreach ($ancestorIds as $ancestorId) {
            $ancestor = UserRelation::find($ancestorId);
            if ($ancestor) {
                // 更新直推人数
                $ancestor->direct_count = UserRelation::where('parent_id', $ancestorId)->count();

                // 更新团队人数
                $ancestor->team_count = UserRelation::where('path', 'like', $ancestor->path.'%')
                    ->where('user_id', '!=', $ancestorId)
                    ->count();

                $ancestor->save();
            }
        }
    }

    /**
     * 更新用户的推荐人
     *
     * @param  User  $user  用户
     * @param  int|null  $newParentId  新推荐人 ID
     *
     * @throws Throwable 用户关系不存在或推荐人无效
     *
     * @return bool 是否更新成功
     */
    public function updateParent(User $user, ?int $newParentId): bool
    {
        $relation = UserRelation::where('user_id', $user->id)->first();
        if (!$relation) {
            throw new InvalidArgumentException('用户关系不存在');
        }
        // 如果新旧推荐人相同，直接返回
        if ($relation->parent_id === $newParentId) {
            return true;
        }

        // 检查合法性
        if ($newParentId !== null && !$relation->canUpdateParent($newParentId)) {
            throw new InvalidArgumentException('不能将自己或下级设为推荐人');
        }

        return DB::transaction(static function () use ($relation, $newParentId) {
            $oldParentId = $relation->parent_id;
            $oldPath = $relation->path;

            // 获取新路径
            [$newLayer, $newPath] = self::resolveLayerAndPath($newParentId);
            $newPath .= $relation->user_id.'/';

            // 计算层级差
            $layerDiff = $newLayer - $relation->layer;

            // 更新当前节点
            $relation->parent_id = $newParentId;
            $relation->layer = $newLayer;
            $relation->path = $newPath;
            $relation->save();

            // 更新所有下级节点
            UserRelation::where('path', 'like', $oldPath.'%')
                ->where('user_id', '!=', $relation->user_id)
                ->update([
                    'layer' => DB::raw("layer + $layerDiff"),
                    'path' => DB::raw("CONCAT('$newPath', SUBSTRING(path, ".(strlen($oldPath) + 1).'))'),
                ]);

            // 更新新旧上级的统计
            if ($oldParentId !== null && $oldParentId > 0) {
                $oldParent = User::find($oldParentId);
                if ($oldParent) {
                    static::updateAncestorCounts($oldParent);
                }
            }
            if ($newParentId !== null && $newParentId > 0) {
                $newParent = User::find($newParentId);
                if ($newParent) {
                    static::updateAncestorCounts($newParent);
                }
            }

            return true;
        });
    }
}
