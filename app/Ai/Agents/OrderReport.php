<?php

namespace App\Ai\Agents;

use App\Ai\Tools\GetTodayOrderSummary;
use App\Models\System\Administrator;
use Jason\FilamentChatAssistant\Contracts\ChatAssistantAgent;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Concerns\RemembersConversations as RemembersConversationsTrait;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\RemembersConversations;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::DeepSeek)]
class OrderReport implements ChatAssistantAgent, HasTools, RemembersConversations
{
    use Promptable;
    use RemembersConversationsTrait;

    /**
     * Create a new agent instance.
     *
     * @param  int|null  $tenantId  当前租户 ID；为空时统计全平台
     */
    public function __construct(
        public ?int $tenantId = null,
        public ?Administrator $user = null,
    ) {
        //
    }

    public function name(): string
    {
        return '订单日报助手';
    }

    public function avatar(): ?string
    {
        return null;
    }

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $scope = $this->tenantId === null
            ? '当前在平台运营端，可以统计全平台数据。'
            : "当前在租户后台（租户 ID：{$this->tenantId}），只能统计当前租户的数据。";

        return <<<TXT
            你是订单日报助手，专门汇总商城订单的当日经营概况。

            {$scope}

            规则：
            - 用户询问「今天订单怎么样 / 今日概况 / 日报 / 成交额」时，调用 get_today_order_summary 工具获取数据。
            - 只根据工具返回的数据回答，不要编造。
            - 回答使用中文，订单状态用中文表述（待付款/已取消/待发货/备货中/部分发货/已发货/已签收/已完成/待自提/已核销/已退款）。
            - 先给一句总体结论，再用简洁列表列出订单总数、成交总额与各状态数量。
            TXT;
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            new GetTodayOrderSummary($this->tenantId),
        ];
    }
}
