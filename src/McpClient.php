<?php

namespace Scriptoshi\McpClient;

use Anthropic;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Scriptoshi\McpClient\Contracts\McpServerInterface;
use Scriptoshi\McpClient\Models\Message;
use Scriptoshi\McpClient\Models\Response;
use Scriptoshi\McpClient\Models\Runner;
use Scriptoshi\McpClient\Enums\RunnerStatus;
use Scriptoshi\McpClient\Jobs\RunTool;
use Scriptoshi\McpClient\Jobs\GenerateChatTitle;
use Scriptoshi\McpClient\Models\Chat;

class McpClient
{
    /** @var array<string, McpServerInterface> Map of registered servers */
    protected array $servers = [];

    public function __construct()
    {
        $this->loadServersFromConfig();
    }

    /**
     * Load and initialize servers from configuration.
     */
    protected function loadServersFromConfig(): void
    {
        $servers = config('mcp.servers', []);

        foreach ($servers as $name => $serverConfig) {
            if (!isset($serverConfig['class'])) {
                throw new \InvalidArgumentException("Server '{$name}' is missing 'class' configuration.");
            }
            $serverClass = $serverConfig['class'];
            if (!class_exists($serverClass)) {
                throw new \InvalidArgumentException("Server class '{$serverClass}' does not exist.");
            }
            // Create server instance, passing config if needed
            $server =  App::make($serverClass, $serverConfig['config'] ?? []);

            if (!($server instanceof McpServerInterface)) {
                throw new \InvalidArgumentException(
                    "Server class '{$serverClass}' must implement McpServerInterface."
                );
            }

            $this->servers[$name] = $server;
        }
    }

    /**
     * Process a user request through Claude and handle tool executions.
     */
    public function processRequest(string $userMessage, string $chatUuid =  null): void
    {
        $chat = Chat::query()->firstOrCreate(
            ['uuid' => $chatUuid ?? Str::uuid()],
            [
                'title' => null,
                'metadata' => []
            ]
        );

        // Generate chat title asynchronously
        dispatch(new GenerateChatTitle($chat, $userMessage));

        // Store initial user message
        $message = Message::create([
            'uuid'=> Str::uuid(),
            'chat_id' => $chat->id,
            'role' => 'user',
            'content' => $userMessage
        ]);
        // Start processing chain
        $this->processClaudeResponse($message);
    }


    /**
     * Process response from Claude and handle any tool executions.
     */
    public function processClaudeResponse(Message $message): void
    {
        $client = Anthropic::client(config('mcp.anthropic.api_key'));
        $result = $client->messages()->create([
            'model' => config('mcp.anthropic.model', 'claude-3-sonnet-20240229'),
            'max_tokens' => config('mcp.anthropic.max_tokens', 1024),
            'system' =>  config('mcp.system', null),
            'messages' => $this->getHistory($message),
            'tools' => $this->listTools()
        ])->toArray();

        $this->processContent($result, $message);
    }

    /**
     * Process the content from Claude's response.
     */
    protected function processContent(array $result, Message $message): void
    {
        $response = $this->saveResponse($message->id, $result);
        // Check and process any tool calls
        $toolsUsed = false;
        foreach ($result['content'] as $item) {
            if ($item['type'] === 'tool_use') {
                $this->processTool($item, $response);
                $toolsUsed = true;
            }
        }
        // If no tools were used or all tools completed synchronously,
        // we can continue the conversation
        if (!$toolsUsed) {
            $message->process();
            return;
        }
        $pendingTools = Runner::where('response_id', $response->id)
            ->whereIn('status', ['queued', 'pending', 'processing'])
            ->count();
        if ($pendingTools === 0) {
            $this->processClaudeResponse($message);
        }
    }

    /**
     * Process a tool call from Claude.
     */
    protected function processTool(array $item, Response $response): void
    {
        [$serverName, $toolName] = explode('.', $item['name'], 2);
        $runner = $response->runners()->create([
            'tool_use_id' => $item['id'],
            'tool_name' => $toolName,
            'arguments' => $item['input'],
            'status' => RunnerStatus::PENDING,
            'mcp_server' => $serverName,
            'started_at' => now(),
        ]);

        $server = $this->getServer($runner->mcp_server);

        if ($server->toolShouldQueue($toolName)) {
            $runner->status = RunnerStatus::QUEUED;
            $runner->save();
            dispatch(new RunTool($runner));
            return;
        }
        $this->executeTool($runner);
    }

    /**
     * Execute a tool and handle the response.
     */
    public function executeTool(Runner $runner): array
    {
        $server = $this->getServer($runner->mcp_server);
        $toolInfo = str_replace('_', ' ', $runner->tool_name);

        $runner->info("Initializing {$runner->mcp_server}: {$toolInfo} ...");

        return $server->executeTool($runner->tool_name, $runner->arguments, $runner);
    }

    /**
     * Get a server by name.
     *
     * @throws \Exception if server not found
     */
    protected function getServer(string $name): McpServerInterface
    {
        if (!isset($this->servers[$name])) {
            throw new \Exception("Server not found: {$name}");
        }
        return $this->servers[$name];
    }

    /**
     * Save Claude's response to database.
     */
    protected function saveResponse(int $messageId, array $response): Response
    {
        return Response::create([
            'message_id' => $messageId,
            'anthropic_id' => $response['id'],
            'content' => $response['content'],
            'model' => $response['model'],
            'role' => $response['role'],
            'stop_reason' => $response['stop_reason'],
            'stop_sequence' => $response['stop_sequence'],
            'type' => $response['type'],
            'input_tokens' => $response['usage']['input_tokens'],
            'output_tokens' => $response['usage']['output_tokens']
        ]);
    }

    /**
     * Get all available tools from registered servers.
     */
    protected function listTools(): array
    {
        $tools = [];
        foreach ($this->servers as $serverName => $server) {
            $serverTools = $server->listTools()['tools'];
            foreach ($serverTools as $tool) {
                $tools[] = [
                    'name' => "{$serverName}.{$tool['name']}",
                    'description' => $tool['description'],
                    'parameters' => $tool['inputSchema']
                ];
            }
        }
        return $tools;
    }

    /**
     * Get the chat history formatted for Claude.
     */
    protected function getHistory(Message $message): array
    {
        // Get all messages up to current message
        $history = Message::where('chat_id', $message->chat_id)
            ->where('created_at', '<=', $message->created_at)
            ->with('responses.runners')
            ->orderBy('created_at')
            ->get();

        $messages = [];

        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg->role,
                'content' => $msg->content
            ];

            foreach ($msg->responses as $response) {
                $messages[] = [
                    'role' => $response->role,
                    'content' => $response->content
                ];

                if (!$response->runners || $response->runners->count() < 1) continue;

                $toolResults = $response->runners->map(function (Runner $runner) {
                    return [
                        "type" => "tool_result",
                        "tool_use_id" => $runner->tool_use_id,
                        "content" => [
                            'message' => $runner->message,
                            ...$runner->result,
                        ]
                    ];
                });

                $messages[] = [
                    'role' => 'user',
                    'content' => $toolResults->toArray()
                ];
            }
        }
        return $messages;
    }
}
