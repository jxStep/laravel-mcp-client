<?php

namespace Scriptoshi\McpClient\Tests\Feature\Models;

use Scriptoshi\McpClient\Tests\TestCase;
use Scriptoshi\McpClient\Models\Chat;
use Scriptoshi\McpClient\Models\Message;
use Scriptoshi\McpClient\Models\Response;
use Scriptoshi\McpClient\Enums\MessageRole;
use Scriptoshi\McpClient\Enums\ResponseType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    protected Chat $chat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chat = Chat::create([
            'uuid' => Str::uuid(),
            'title' => 'Test Chat'
        ]);
    }

    public function test_it_can_create_message(): void
    {
        $message = Message::create([
            'chat_id' => $this->chat->id,
            'uuid' => Str::uuid(),
            'role' => MessageRole::USER,
            'content' => 'Test message',
            'metadata' => ['key' => 'value'],
            'error' => false,
            'processed' => false
        ]);

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'chat_id' => $this->chat->id,
            'role' => MessageRole::USER->value,
            'error' => false,
            'processed' => false
        ]);

        $this->assertNotNull($message->uuid);
        $this->assertEquals(['key' => 'value'], $message->metadata);
    }

    public function test_it_belongs_to_chat(): void
    {
        $message = Message::create([
            'chat_id' => $this->chat->id,
            'uuid' => Str::uuid(),
            'role' => MessageRole::USER,
            'content' => 'Test message'
        ]);

        $this->assertInstanceOf(Chat::class, $message->chat);
        $this->assertEquals($this->chat->id, $message->chat->id);
    }

    public function test_it_has_responses_relationship(): void
    {
        $message = Message::create([
            'chat_id' => $this->chat->id,
            'uuid' => Str::uuid(),
            'role' => MessageRole::USER,
            'content' => 'Test message'
        ]);

        $response = Response::create([
            'message_id' => $message->id,
            'content' => ['text' => 'Test response'],
            'anthropic_id' => 'msg_123',
            'model' => 'claude-3-sonnet',
            'role' => 'assistant',
            'type' => ResponseType::ASSISTANT,
            'input_tokens' => 0,
            'output_tokens' => 0
        ]);

        $this->assertCount(1, $message->responses);
        $this->assertEquals('Test response', $message->responses->first()->content['text']);
    }

    public function test_it_uses_soft_deletes(): void
    {
        $message = Message::create([
            'chat_id' => $this->chat->id,
            'uuid' => Str::uuid(),
            'role' => MessageRole::USER,
            'content' => 'Test message'
        ]);

        $message->delete();

        $this->assertDatabaseMissing('messages', [
            'id' => $message->id,
            'deleted_at' => null
        ]);

        $this->assertNull(Message::find($message->id));
        $this->assertNotNull(Message::withTrashed()->find($message->id));
    }

    public function test_it_can_find_message_by_uuid(): void
    {
        $uuid = (string) Str::uuid();
        $message = Message::create([
            'chat_id' => $this->chat->id,
            'uuid' => $uuid,
            'role' => MessageRole::USER,
            'content' => 'Test message'
        ]);

        $found = Message::findByUuid($uuid);
        $this->assertNotNull($found);
        $this->assertEquals($message->id, $found->id);
    }
}