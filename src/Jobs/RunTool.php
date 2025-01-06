<?php

namespace Scriptoshi\McpClient\Jobs;

use Scriptoshi\McpClient\McpClient;
use Scriptoshi\McpClient\Models\Runner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunTool implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Runner $runner
    ) {}

    public function handle()
    {
        try {
            $this->runner->update(['status' => 'processing']);
            $client = app()->make(McpClient::class);
            $client->executeTool($this->runner);
            // Check if this was the last tool to complete
            $pendingTools = Runner::where('message_id', $this->runner->message_id)
                ->whereIn('status', ['queued', 'pending', 'processing'])
                ->count();
            if ($pendingTools === 0) {
                // All tools are complete, process results with Claude
                $client->processClaudeResponse($this->runner->message());
            }
        } catch (\Exception $e) {
            $this->runner->update([
                'status' => 'failed',
                'error' => $e->getMessage()
            ]);
        }
    }
}
