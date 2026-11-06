<?php

namespace Tests\Feature\Ai;

use App\Ai\Agents\BusinessAnalyticsAgent;
use App\Ai\Agents\GeneralAssistant;
use App\Ai\Agents\OrderFollowUpAgent;
use Jason\FilamentChatAssistant\Contracts\ChatAssistantAgent;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\CanActAsTool;
use Laravel\Ai\Tools\AgentTool;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class AgentDelegationTest extends TestCase
{
    public function test_general_assistant_delegates_to_two_sub_agents(): void
    {
        $agent = new GeneralAssistant(tenantId: 1);

        $this->assertInstanceOf(ChatAssistantAgent::class, $agent);
        $this->assertSame('综合服务', $agent->name());

        $tools = $agent->tools();

        $this->assertCount(2, $tools);
        $this->assertInstanceOf(OrderFollowUpAgent::class, $tools[0]);
        $this->assertInstanceOf(BusinessAnalyticsAgent::class, $tools[1]);
    }

    public function test_order_follow_up_agent_exposes_tool_contract(): void
    {
        $agent = new OrderFollowUpAgent(tenantId: 7);

        $this->assertInstanceOf(Agent::class, $agent);
        $this->assertInstanceOf(CanActAsTool::class, $agent);
        $this->assertSame('order_follow_up', $agent->name());
        $this->assertStringContainsString('订单号', (string) $agent->description());
        $this->assertCount(4, $agent->tools());
        $this->assertStringContainsString('租户 ID：7', (string) $agent->instructions());
    }

    public function test_business_analytics_agent_exposes_tool_contract(): void
    {
        $agent = new BusinessAnalyticsAgent(tenantId: 8);

        $this->assertInstanceOf(Agent::class, $agent);
        $this->assertInstanceOf(CanActAsTool::class, $agent);
        $this->assertSame('business_analytics', $agent->name());
        $this->assertStringContainsString('统计周期', (string) $agent->description());
        $this->assertCount(5, $agent->tools());
        $this->assertStringContainsString('租户 ID：8', (string) $agent->instructions());
    }

    public function test_parent_instructions_require_self_contained_tasks(): void
    {
        $instructions = (string) (new GeneralAssistant(tenantId: 1))->instructions();

        $this->assertStringContainsString('order_follow_up', $instructions);
        $this->assertStringContainsString('business_analytics', $instructions);
        $this->assertStringContainsString('自包含', $instructions);
        $this->assertStringContainsString('默认本月', $instructions);
    }

    public function test_sub_agent_can_be_invoked_through_agent_tool(): void
    {
        OrderFollowUpAgent::fake(['订单已发货']);

        $result = (new AgentTool(new OrderFollowUpAgent(tenantId: 1)))->handle(
            new Request(['task' => '查订单 261101172319076528 为什么没发货'])
        );

        $this->assertSame('订单已发货', $result);
    }
}
