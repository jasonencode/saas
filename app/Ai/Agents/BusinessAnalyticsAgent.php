<?php

namespace App\Ai\Agents;

use App\Ai\Tools\GetPendingFulfillment;
use App\Ai\Tools\GetProductSalesReport;
use App\Ai\Tools\GetRefundAnalysis;
use App\Ai\Tools\GetSalesTrend;
use App\Ai\Tools\GetTodayOrderStats;
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
 * 经营分析子智能体
 *
 * 由 GeneralAssistant 通过 tools() 委派，只处理统计、报表与趋势分析。
 */
#[Provider(Lab::DeepSeek)]
class BusinessAnalyticsAgent implements Agent, CanActAsTool, HasTools
{
    use Promptable;

    /**
     * @param  int|null  $tenantId  当前租户 ID；为空时统计全平台
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
        return 'business_analytics';
    }

    /**
     * 子智能体暴露给父智能体的工具描述
     */
    public function description(): Stringable|string
    {
        return '经营分析专员：今日统计、商品/分类销售排行、销售趋势与环比、退款分析、发货积压。task 必须说明统计周期（今天/本周/本月/今年/自定义区间），未说明时按本月处理。';
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
            你是商城后台的经营分析专员，负责统计报表、销售趋势与结构分析。

            {$scope}

            规则：
            - 任务未指定统计周期时按本月处理，并在回答中说明实际使用的周期。
            - 今日概况用 get_today_order_stats；商品/分类排行用 get_product_sales_report；趋势与环比用 get_sales_trend；退款结构用 get_refund_analysis；发货积压用 get_pending_fulfillment。
            - 只做聚合分析，不逐单排查具体订单；遇到具体订单问题如实说明不属于你的范围。
            - 回答使用中文，先给一句总体结论，再用列表或表格呈现关键指标与 TOP 商品。
            - 数据为空时如实告知，不要编造。
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
            new GetTodayOrderStats($this->tenantId),
            new GetProductSalesReport($this->tenantId),
            new GetSalesTrend($this->tenantId),
            new GetRefundAnalysis($this->tenantId),
            new GetPendingFulfillment($this->tenantId),
        ];
    }
}
