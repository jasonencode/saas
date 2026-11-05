<?php

namespace Tests\Feature;

use App\Ai\Agents\OrderReport;
use App\Ai\Agents\OrderSearch as ChatAssistantAgent;
use App\Models\System\Administrator;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jason\FilamentChatAssistant\Livewire\ChatAssistant;
use Livewire\Livewire;
use Tests\TestCase;

class ChatAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected Administrator $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Administrator::factory()->create(['name' => '测试管理员']);
        $this->actingAs($this->admin, 'backend');
        Filament::setCurrentPanel('backend');

        config(['chat-assistant.agents' => [ChatAssistantAgent::class]]);
    }

    public function test_backend_page_renders_chat_assistant(): void
    {
        $this->get('/backend')
            ->assertOk()
            ->assertSee('tk-assistant', false);
    }

    public function test_window_size_comes_from_config(): void
    {
        config([
            'chat-assistant.ui.width' => 420,
            'chat-assistant.ui.height' => 640,
        ]);

        $this->get('/backend')
            ->assertOk()
            ->assertSee('|| 420', false)
            ->assertSee('|| 640', false)
            ->assertSee('this.width = 420;', false)
            ->assertSee('this.height = 640;', false);
    }

    public function test_fullscreen_and_new_conversation_can_be_disabled(): void
    {
        config([
            'chat-assistant.ui.fullscreen' => false,
            'chat-assistant.ui.new_conversation' => false,
        ]);

        Livewire::test(ChatAssistant::class)
            ->call('toggle')
            ->assertDontSee('title="最大化"', false)
            ->assertDontSee('title="开始新会话"', false)
            ->assertSee('maximized: false', false);
    }

    public function test_fullscreen_and_new_conversation_show_by_default(): void
    {
        Livewire::test(ChatAssistant::class)
            ->call('toggle')
            ->assertSee('title="最大化"', false)
            ->assertSee('title="开始新会话"', false)
            ->assertSee('tk-assistant__typing', false);
    }

    public function test_agent_name_and_avatar_are_rendered(): void
    {
        config(['chat-assistant.ui.title' => null]);

        Livewire::test(ChatAssistant::class)
            ->call('toggle')
            ->assertSee('订单查询助手')
            ->assertSee('images/avatar.png', false)
            ->assertDontSee('aria-haspopup="listbox"', false);
    }

    public function test_agent_name_takes_priority_over_ui_title(): void
    {
        config(['chat-assistant.ui.title' => '订单助手']);

        Livewire::test(ChatAssistant::class)
            ->call('toggle')
            ->assertSee('订单查询助手')
            ->assertSee('你好，我是订单查询助手');
    }

    public function test_missing_agent_shows_notice(): void
    {
        config(['chat-assistant.agents' => []]);

        Livewire::test(ChatAssistant::class)
            ->call('toggle')
            ->assertSee('未配置有效的 AI Agent')
            ->assertSee('disabled', false);
    }

    public function test_invalid_agents_are_ignored(): void
    {
        config(['chat-assistant.agents' => [
            ChatAssistantAgent::class,
            \stdClass::class,
            'App\\Ai\\Agents\\MissingAgent',
        ]]);

        Livewire::test(ChatAssistant::class)
            ->call('toggle')
            ->assertSet('agentConfigured', true)
            ->assertCount('agents', 1);
    }

    public function test_multiple_agents_can_be_switched(): void
    {
        config(['chat-assistant.agents' => [
            'order' => ChatAssistantAgent::class,
            'audit' => SecondChatAgent::class,
        ]]);

        Livewire::test(ChatAssistant::class)
            ->call('toggle')
            ->assertSee('订单查询助手')
            ->assertSee('审核助手')
            ->assertSee('tk-assistant__agent-menu', false)
            ->assertSee('selectAgent(1)', false)
            ->assertSet('agentName', '订单查询助手')
            ->call('selectAgent', 1)
            ->assertSet('agentName', '审核助手')
            ->assertSet('agentAvatar', asset('images/audit.png'));
    }

    public function test_host_agents_are_switchable(): void
    {
        config(['chat-assistant.agents' => [
            'order' => ChatAssistantAgent::class,
            'report' => OrderReport::class,
        ]]);

        Livewire::test(ChatAssistant::class)
            ->call('toggle')
            ->assertSee('订单查询助手')
            ->assertSee('订单日报助手')
            ->call('selectAgent', 1)
            ->assertSet('agentName', '订单日报助手')
            ->assertSet('agentAvatar', null);
    }

    public function test_conversations_are_isolated_per_agent(): void
    {
        config(['chat-assistant.agents' => [
            'order' => ChatAssistantAgent::class,
            'audit' => SecondChatAgent::class,
        ]]);

        $component = Livewire::test(ChatAssistant::class)
            ->call('toggle')
            ->set('question', '你好')
            ->call('sendMessage')
            ->assertCount('messages', 1);

        $component->call('selectAgent', 1)
            ->assertCount('messages', 0);

        $component->call('selectAgent', 0)
            ->assertCount('messages', 1)
            ->assertSet('messages.0.content', '你好');
    }

    public function test_user_message_shows_name_and_avatar(): void
    {
        Livewire::test(ChatAssistant::class)
            ->call('toggle')
            ->set('question', '你好')
            ->call('sendMessage')
            ->assertSee('测试管理员')
            ->assertSee('images/avatar.jpg', false);
    }

    public function test_it_streams_reply_from_agent(): void
    {
        ChatAssistantAgent::fake(['这是测试回复']);

        Livewire::test(ChatAssistant::class)
            ->call('toggle')
            ->assertSet('open', true)
            ->set('question', '你好')
            ->call('sendMessage')
            ->assertSet('question', '')
            ->assertCount('messages', 1)
            ->call('requestReply')
            ->assertCount('messages', 2)
            ->assertSet('messages.1.role', 'assistant')
            ->assertSet('messages.1.content', '这是测试回复');
    }

    public function test_messages_are_persisted_to_the_conversation_tables(): void
    {
        ChatAssistantAgent::fake(['这是测试回复']);

        Livewire::test(ChatAssistant::class)
            ->call('toggle')
            ->set('question', '你好')
            ->call('sendMessage')
            ->call('requestReply');

        $this->assertDatabaseHas('agent_conversation_messages', [
            'role' => 'user',
            'content' => '你好',
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('agent_conversation_messages', [
            'role' => 'assistant',
            'content' => '这是测试回复',
            'status' => 'completed',
        ]);
    }

    public function test_a_stored_message_can_be_retracted(): void
    {
        ChatAssistantAgent::fake(['回答']);

        $component = Livewire::test(ChatAssistant::class)
            ->call('toggle')
            ->set('question', '问题')
            ->call('sendMessage')
            ->call('requestReply')
            ->assertCount('messages', 2);

        $component->call('retractMessage', 1)->assertCount('messages', 1);

        $this->assertDatabaseMissing('agent_conversation_messages', ['role' => 'assistant']);
        $this->assertDatabaseHas('agent_conversation_messages', ['role' => 'user', 'content' => '问题']);
    }

    public function test_a_stored_reply_can_be_regenerated(): void
    {
        ChatAssistantAgent::fake(['回答']);

        $component = Livewire::test(ChatAssistant::class)
            ->call('toggle')
            ->set('question', '问题')
            ->call('sendMessage')
            ->call('requestReply')
            ->assertCount('messages', 2);

        $component->call('regenerateReply')
            ->assertCount('messages', 1)
            ->assertSet('messages.0.role', 'user')
            ->assertDispatched('chat-reply-requested');

        $this->assertDatabaseMissing('agent_conversation_messages', ['role' => 'assistant']);
    }

    public function test_it_resets_conversation(): void
    {
        Livewire::test(ChatAssistant::class)
            ->set('question', '测试')
            ->call('sendMessage')
            ->call('resetConversation')
            ->assertSet('messages', [])
            ->assertSet('question', '');
    }
}

/**
 * 仅用于多 Agent 切换测试
 */
class SecondChatAgent extends ChatAssistantAgent
{
    public function name(): string
    {
        return '审核助手';
    }

    public function avatar(): ?string
    {
        return asset('images/audit.png');
    }
}
