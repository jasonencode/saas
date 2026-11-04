<?php

namespace App\Ai\Agents;

use App\Ai\Tools\GetTodayOrderSummary;
use App\Ai\Tools\SearchOrders;
use App\Models\System\Administrator;
use Jason\FilamentChatAssistant\Contracts\ChatAssistantAgent;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::DeepSeek)]
class OrderSearch implements ChatAssistantAgent, Conversational, HasTools
{
    use Promptable;

    /**
     * Create a new agent instance.
     *
     * @param  iterable<Message>  $history  之前的对话消息
     * @param  int|null  $tenantId  当前租户 ID；为空时查询全平台
     */
    public function __construct(
        public iterable $history = [],
        public ?int $tenantId = null,
        public ?Administrator $user = null,
    ) {
        //
    }

    public function name(): string
    {
        return '订单查询助手';
    }

    public function avatar(): ?string
    {
        return asset('images/avatar.png');
    }

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $scope = $this->tenantId === null
            ? '当前在平台运营端，可以查询全平台数据。'
            : "当前在租户后台（租户 ID：{$this->tenantId}），只能查询当前租户的数据。";

        return <<<TXT
            你是商城订单查询助手，可以调用 search_orders 工具查询系统内的订单。

            {$scope}

            规则：
            - 用户问订单相关问题时，优先调用工具查询，再根据结果回答，不要编造数据。
            - 回答使用中文，订单状态用中文表述（待付款/已取消/待发货/备货中/部分发货/已发货/已签收/已完成/待自提/已核销/已退款）。
            - 查询结果为空时如实告知，并建议用户调整查询条件。
            - 涉及多个订单时，用简洁的列表呈现关键信息（订单号、状态、金额、用户）。
            TXT;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return is_array($this->history) ? $this->history : iterator_to_array($this->history);
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            new SearchOrders($this->tenantId),
            new GetTodayOrderSummary($this->tenantId),
        ];
    }
}
