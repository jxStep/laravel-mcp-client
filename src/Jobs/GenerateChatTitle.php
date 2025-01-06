<?php

namespace Scriptoshi\McpClient\Jobs;

use Anthropic;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Scriptoshi\McpClient\Models\Chat;

class GenerateChatTitle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Chat $chat,
        protected string $initialMessage
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $client = Anthropic::client(config('mcp.anthropic.api_key'));

            $result = $client->messages()->create([
                'model' => config('mcp.anthropic.model', 'claude-3-sonnet-20240229'),
                'max_tokens' => 100,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => "Generate a concise, descriptive title (maximum 5 words) for a chat that starts with this message. Respond with only the title, no quotes or explanations: {$this->initialMessage}"
                    ]
                ]
            ])->toArray();
            // Extract the title from the response
            $title = trim($result['content'][0]['text']);
            // Update the chat with the new title
            $this->chat->update([
                'title' => $title
            ]);
        } catch (\Exception $e) {
            // Log the error but don't rethrow - we don't want to retry title generation
            Log::error('Failed to generate chat title', [
                'chat_id' => $this->chat->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
