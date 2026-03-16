<?php

namespace App\Filament\Actions\Foundation;

use App\Models\Wechat;
use App\Services\WechatService;
use Exception;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

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
        $this->visible(fn(Wechat $wechat) => userCan(self::getDefaultName(), $wechat));
        $this->hidden(fn(Wechat $wechat) => $wechat->connection);

        $this->action(function (Wechat $wechat) {
            try {
                resolve(WechatService::class)
                    ->testConnection($wechat);
                $this->successNotificationTitle('配置测试通过，连接成功');
                $this->success();
                $status = true;
            } catch (Exception $exception) {
                $this->failureNotificationTitle($exception->getMessage());
                $this->failure();
                $status = false;
            }

            $wechat->connection = $status;
            $wechat->save();
        });
    }
}
