<?php

namespace App\Filament\Actions\Foundation;

use App\Models\Wechat;
use App\Services\WechatService;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Throwable;

class TestWechatConnection extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'testWechat';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('测试配置');
        $this->icon(Heroicon::OutlinedFingerPrint);
        $this->visible(fn (Wechat $wechat) => userCan(self::getDefaultName(), $wechat));
        $this->hidden(fn (Wechat $wechat) => $wechat->is_connected);

        $this->action(function (Wechat $wechat) {
            try {
                service(WechatService::class)
                    ->testConnection($wechat);
                $this->successNotificationTitle('配置测试通过，连接成功');
                $this->success();
                $status = true;
            } catch (Throwable $e) {
                $this->failureNotificationTitle($e->getMessage());
                $this->failure();
                $status = false;
            }

            $wechat->is_connected = $status;
            $wechat->save();
        });
    }
}
