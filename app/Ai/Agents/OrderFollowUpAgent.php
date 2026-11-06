<?php

namespace App\Ai\Agents;

use App\Ai\Tools\GetOrderAnomalies;
use App\Ai\Tools\GetOrderDetail;
use App\Ai\Tools\GetOrderTimeline;
use App\Ai\Tools\SearchOrders;
use App\Models\System\Administrator;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\CanActAsTool;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * 订单跟单子智能体
 *
 * 由 GeneralAssistant 通过 tools() 委派，只处理具体订单的查询与跟进。
 */
#[Provider(Lab::DeepSeek)]
class OrderFollowUpAgent implements Agent, CanActAsTool, HasTools
{
    use Promptable;

    /**
     * @param  int|null  $tenantId  当前租户 ID；为空时查询全平台
     */
    public function __construct(
        protected ?int $tenantId = null,
        protected ?Administrator $user = null,
    ) {
        //
    }

    /**
     * 子智能体暴露给父智能体的工具名
     */
    public function name(): string
    {
        return 'order_follow_up';
    }

    /**
     * 子智能体暴露给父智能体的工具描述
     */
    public function description(): Stringable|string
    {
        return '订单跟单专员：查询订单列表/详情、订单事件时间线、订单异常巡检。task 必须自带订单号（例如「查订单 2611... 为什么没发货」），否则无法定位。';
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
            你是商城后台的订单跟单专员，只处理与具体订单相关的查询和跟进。

            {$scope}

            规则：
            - 任务里没有订单号、又需要定位具体订单时，先说明需要订单号，不要猜测或编造。
            - 查订单详情用 get_order_detail；查状态流转和操作记录用 get_order_timeline；查异常用 get_order_anomalies；按条件找单用 search_orders。
            - 只回答跟单相关问题；统计、报表、趋势类问题不属于你的范围，如实说明即可。
            - 回答使用中文，订单状态用中文表述（待付款/已取消/待发货/备货中/部分发货/已发货/已签收/已完成/待自提/已核销/已退款）。
            - 涉及多个订单时，用简洁的列表呈现关键信息（订单号、状态、金额、用户）。
            - 工具没有返回数据时如实告知，并建议调整查询条件。
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
            new SearchOrders($this->tenantId),
            new GetOrderDetail($this->tenantId),
            new GetOrderTimeline($this->tenantId),
            new GetOrderAnomalies($this->tenantId),
        ];
    }
}
