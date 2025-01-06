<?php

namespace Scriptoshi\McpClient\Tests\Feature\Models;

use Scriptoshi\McpClient\Tests\TestCase;
use Scriptoshi\McpClient\Models\Chat;
use Scriptoshi\McpClient\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_create_chat(): void
    {
        $chat = Chat::create([
            'uuid' => Str::uuid(), // Explicitly set UUID for testing
            'title' => 'Test Chat',
            'metadata' => ['key' => 'value']
        ]);

        $this->assertDatabaseHas('chats', [
            'id' => $chat->id,
            'title' => 'Test Chat'
        ]);

        $this->assertNotNull($chat->uuid);
        $this->assertEquals(['key' => 'value'], $chat->metadata);
    }

    public function test_it_can_find_chat_by_uuid(): void
    {
        $uuid = Str::uuid();
        $chat = Chat::create([
            'uuid' => $uuid,
            'title' => 'Test Chat'
        ]);

        $found = Chat::findByUuid($uuid);
        $this->assertNotNull($found);
        $this->assertEquals($chat->id, $found->id);
    }

    public function test_it_has_messages_relationship(): void
    {
        $chat = Chat::create([
            'uuid' => Str::uuid(),
            'title' => 'Test Chat'
        ]);
        
        Message::create([
            'chat_id' => $chat->id,
            'role' => 'user',
            'uuid' => Str::uuid(),
            'content' => 'Test message'
        ]);

        $this->assertCount(1, $chat->messages);
        $this->assertEquals('Test message', $chat->messages->first()->content);
    }

    public function test_it_uses_soft_deletes(): void
    {
        $chat = Chat::create([
            'uuid' => Str::uuid(),
            'title' => 'Test Chat'
        ]);
        $chat->delete();

        $this->assertDatabaseMissing('chats', [
            'id' => $chat->id,
            'deleted_at' => null
        ]);

        $this->assertDatabaseHas('chats', [
            'id' => $chat->id
        ]);

        $this->assertNull(Chat::find($chat->id));
        $this->assertNotNull(Chat::withTrashed()->find($chat->id));
    }
}