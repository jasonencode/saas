<?php

namespace App\Notifications\Mall;

use App\Contracts\Authenticatable;
use App\Contracts\Notification\BaseNotification;
use App\Enums\Mall\ApplyStatus;
use App\Models\Mall\StoreApply;

/**
 * 店铺入驻审核结果通知
 */
class StoreApplyReviewedNotification extends BaseNotification
{
    public function __construct(public StoreApply $storeApply)
    {
        //
    }

    public static function getGroupTitle(): string
    {
        return '店铺通知';
    }

    public static function getType(): string
    {
        return 'store_apply_reviewed';
    }

    public function getIcon(): string
    {
        return 'store';
    }

    public function getColor(): string
    {
        return $this->storeApply->status === ApplyStatus::Approved ? 'success' : 'danger';
    }

    public function via(Authenticatable $user): array
    {
        return ['database'];
    }

    public function getUrl(Authenticatable $notifiable): string
    {
        return url('/user/store-applies/'.$this->storeApply->id);
    }

    public function getMessage(): string
    {
        $isApproved = $this->storeApply->status === ApplyStatus::Approved;

        return $isApproved
            ? sprintf('店铺 %s 入驻申请已通过', $this->storeApply->name)
            : sprintf('店铺 %s 入驻申请未通过', $this->storeApply->name);
    }

    protected function getData(): array
    {
        return [
            'store_apply_id' => $this->storeApply->id,
            'name' => $this->storeApply->name,
            'status' => $this->storeApply->status->value,
        ];
    }
}
