<?php

namespace App\Ai\Agents;

use App\Models\System\Administrator;
use Jason\FilamentChatAssistant\Contracts\ChatAssistantAgent;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Concerns\RemembersConversations as RemembersConversationsTrait;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * 综合服务主智能体
 *
 * 自身不挂业务工具，只负责把问题委派给订单跟单、经营分析两个子智能体。
 */
#[Provider(Lab::DeepSeek)]
class GeneralAssistant implements ChatAssistantAgent, HasTools
{
    use Promptable;
    use RemembersConversationsTrait;

    /**
     * Create a new agent instance.
     *
     * @param  int|null  $tenantId  当前租户 ID；为空时查询全平台
     */
    public function __construct(
        public ?int $tenantId = null,
        public ?Administrator $user = null,
    ) {
        //
    }

    public function name(): string
    {
        return '综合服务';
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
            你是商城后台的综合服务助手，负责把用户问题转交给合适的子专员：
            - order_follow_up：具体订单跟单（订单详情、为什么没发货、退款到哪一步、状态时间线、异常订单）。
            - business_analytics：经营分析（今日统计、商品/分类排行、销售趋势与环比、退款分析、发货积压）。

            {$scope}

            规则：
            - 需要委派时，交给子专员的 task 必须是自包含的：写明订单号或统计周期、以及要看的指标，不要只写「帮我看看」。
            - 用户没有提供订单号、又需要定位订单时，先反问订单号，不要猜测。
            - 用户没有说统计周期时，task 里写明「默认本月」。
            - 简单寒暄、范围外的问题直接回答，不要强行委派。
            - 汇总子专员的结论后，用中文给出简洁、结构化的最终回答，不要编造数据。
            - 订单状态用中文表述（待付款/已取消/待发货/备货中/部分发货/已发货/已签收/已完成/待自提/已核销/已退款）。
            TXT;
    }

    /**
     * Get the sub-agents available to the parent agent.
     *
     * @return array<int, Agent>
     */
    public function tools(): iterable
    {
        return [
            new OrderFollowUpAgent($this->tenantId, $this->user),
            new BusinessAnalyticsAgent($this->tenantId, $this->user),
        ];
    }
}
