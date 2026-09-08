<?php

namespace App\Services\Content;

use App\Contracts\Authenticatable;
use App\Contracts\ServiceInterface;
use App\Enums\Content\SuggestStatus;
use App\Models\Content\Suggest;
use App\Models\Content\SuggestMessage;

class SuggestService implements ServiceInterface
{
    /**
     * 提交反馈
     *
     * 创建反馈并写入首条消息，状态为待处理。
     *
     * @param  Authenticatable  $user  用户
     * @param  string  $type  反馈类型
     * @param  string|null  $contact  联系方式
     * @param  string  $content  反馈内容
     *
     * @return Suggest 反馈
     */
    public function store(Authenticatable $user, string $type, ?string $contact, string $content): Suggest
    {
        $suggest = Suggest::create([
            'user_id' => $user->id,
            'type' => $type,
            'contact' => $contact,
            'status' => SuggestStatus::Pending,
        ]);

        $this->addMessage($suggest, $user, $content);

        return $suggest;
    }

    /**
     * 用户追加消息
     *
     * 状态自动重置为待处理，提醒管理员有新消息。
     *
     * @param  Suggest  $suggest  反馈
     * @param  Authenticatable  $user  用户
     * @param  string  $content  消息内容
     *
     * @return SuggestMessage 消息
     */
    public function appendUserMessage(Suggest $suggest, Authenticatable $user, string $content): SuggestMessage
    {
        $message = $this->addMessage($suggest, $user, $content);

        if ($suggest->status !== SuggestStatus::Pending) {
            $suggest->update(['status' => SuggestStatus::Pending]);
        }

        return $message;
    }

    /**
     * 管理员回复
     *
     * 状态自动改为已回复。
     *
     * @param  Suggest  $suggest  反馈
     * @param  Authenticatable  $admin  管理员
     * @param  string  $content  回复内容
     *
     * @return SuggestMessage 消息
     */
    public function reply(Suggest $suggest, Authenticatable $admin, string $content): SuggestMessage
    {
        $message = $this->addMessage($suggest, $admin, $content);

        if ($suggest->status !== SuggestStatus::Resolved) {
            $suggest->update(['status' => SuggestStatus::Resolved]);
        }

        return $message;
    }

    /**
     * 关闭反馈
     *
     * @param  Suggest  $suggest  反馈
     */
    public function close(Suggest $suggest): void
    {
        if ($suggest->status !== SuggestStatus::Closed) {
            $suggest->update(['status' => SuggestStatus::Closed]);
        }
    }

    /**
     * 重新开启反馈
     *
     * @param  Suggest  $suggest  反馈
     */
    public function reopen(Suggest $suggest): void
    {
        if ($suggest->status !== SuggestStatus::Pending) {
            $suggest->update(['status' => SuggestStatus::Pending]);
        }
    }

    /**
     * 添加对话消息
     *
     * @param  Suggest  $suggest  反馈
     * @param  Authenticatable  $sender  发送者
     * @param  string  $content  消息内容
     *
     * @return SuggestMessage 消息
     */
    protected function addMessage(Suggest $suggest, Authenticatable $sender, string $content): SuggestMessage
    {
        return $suggest->messages()->create([
            'sender_type' => $sender::class,
            'sender_id' => $sender->id,
            'content' => $content,
        ]);
    }
}
