<?php

namespace Scriptoshi\McpClient\Tests\Feature;

use Scriptoshi\McpClient\Tests\TestCase;
use Scriptoshi\McpClient\McpClient;
use Scriptoshi\McpClient\Contracts\McpServerInterface;
use Scriptoshi\McpClient\Contracts\LoggerInterface;
use Scriptoshi\McpClient\Models\Chat;
use Scriptoshi\McpClient\Models\Message;
use Scriptoshi\McpClient\Models\Response;
use Scriptoshi\McpClient\Models\Runner;
use Scriptoshi\McpClient\Enums\RunnerStatus;
use Scriptoshi\McpClient\Enums\ResponseType;
use Scriptoshi\McpClient\Jobs\RunTool;
use Scriptoshi\McpClient\Jobs\GenerateChatTitle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Mockery;

class McpClientTest extends TestCase
{
    use RefreshDatabase;

    protected McpClient $client;
    protected McpServerInterface $mockServer;
    protected string $chatUuid;

    protected function setUp(): void
    {
        parent::setUp();
        
        Queue::fake();
        
        // Mock Anthropic API responses
        Http::fake([
            'api.anthropic.com/v1/messages' => Http::response([
                'id' => 'msg_123',
                'type' => 'message',
                'role' => 'assistant',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Test response'
                    ]
                ],
                'model' => 'claude-3-sonnet-20240229',
                'stop_reason' => null,
                'stop_sequence' => null,
                'usage' => [
                    'input_tokens' => 10,
                    'output_tokens' => 20
                ]
            ], 200)
        ]);

        // Create mock server
        $this->mockServer = Mockery::mock(McpServerInterface::class);
        
        // Mock initialize method
        $this->mockServer->shouldReceive('initialize')->andReturn([
            'serverInfo' => [
                'name' => 'TestServer',
                'version' => '1.0'
            ],
            'capabilities' => [
                'tools' => true
            ]
        ])->byDefault();
        
        // Mock listTools method - this should be called multiple times
        $this->mockServer->shouldReceive('listTools')->andReturn([
            'tools' => [
                [
                    'name' => 'test-tool',
                    'description' => 'A test tool',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'param1' => [
                                'type' => 'string',
                                'description' => 'Test parameter'
                            ]
                        ]
                    ]
                ]
            ]
        ])->byDefault();

        config(['mcp.servers.test-server' => [
            'class' => get_class($this->mockServer),
            'config' => []
        ]]);

        $this->client = new McpClient();
        $this->chatUuid = (string) Str::uuid();
    }


    public function test_it_processes_simple_request(): void
    {
        $this->client->processRequest('Test message');

        $chat = Chat::first();
        $this->assertNotNull($chat);

        $message = Message::where('chat_id', $chat->id)->first();
        $this->assertNotNull($message);
        $this->assertEquals('Test message', $message->content);

        $response = Response::where('message_id', $message->id)->first();
        $this->assertNotNull($response);
        $this->assertEquals('Test response', $response->content[0]['text']);
        $this->assertEquals('msg_123', $response->anthropic_id);
    }

    public function test_it_processes_request_with_existing_chat(): void
    {
        $chat = Chat::create([
            'uuid' => $this->chatUuid,
            'title' => 'Existing Chat'
        ]);

        $this->client->processRequest('Test message', $chat->uuid);

        $this->assertCount(1, $chat->messages);
        $message = $chat->messages->first();
        $this->assertEquals('Test message', $message->content);
    }

    public function test_it_handles_tool_execution(): void
    {
        // Setup tool execution mock
        $this->mockServer->shouldReceive('toolShouldQueue')
            ->with('test-tool')
            ->andReturn(false);

        $this->mockServer->shouldReceive('executeTool')
            ->with('test-tool', Mockery::any(), Mockery::type(LoggerInterface::class))
            ->andReturn(['result' => 'success']);

        // Mock Claude response with tool use
        Http::fake([
            'api.anthropic.com/v1/messages' => Http::sequence()
                ->push([
                    'id' => 'msg_123',
                    'type' => 'message',
                    'role' => 'assistant',
                    'content' => [
                        [
                            'type' => 'tool_use',
                            'id' => 'tool_123',
                            'name' => 'test-server.test-tool',
                            'input' => ['param1' => 'test']
                        ]
                    ],
                    'model' => 'claude-3-sonnet-20240229',
                    'stop_reason' => null,
                    'stop_sequence' => null,
                    'usage' => [
                        'input_tokens' => 10,
                        'output_tokens' => 20
                    ]
                ])
                ->push([
                    'id' => 'msg_124',
                    'type' => 'message',
                    'role' => 'assistant',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Tool execution complete'
                        ]
                    ],
                    'model' => 'claude-3-sonnet-20240229',
                    'stop_reason' => null,
                    'stop_sequence' => null,
                    'usage' => [
                        'input_tokens' => 30,
                        'output_tokens' => 40
                    ]
                ])
        ]);

        $this->client->processRequest('Test message with tool', $this->chatUuid);

        $message = Message::first();
        $response = $message->responses()->first();
        $runner = $response->runners()->first();

        $this->assertNotNull($runner);
        $this->assertEquals('test-tool', $runner->tool_name);
        $this->assertEquals('test-server', $runner->mcp_server);
        $this->assertEquals(['param1' => 'test'], $runner->arguments);
        $this->assertEquals(['result' => 'success'], $runner->result);
        $this->assertEquals(RunnerStatus::COMPLETED, $runner->status);
    }

    public function test_it_queues_tools_when_required(): void
    {
        // Setup tool execution mock
        $this->mockServer->shouldReceive('toolShouldQueue')
            ->with('test-tool')
            ->andReturn(true);

        // Mock Claude response with tool use
        Http::fake([
            'api.anthropic.com/v1/messages' => Http::response([
                'id' => 'msg_123',
                'type' => 'message',
                'role' => 'assistant',
                'content' => [
                    [
                        'type' => 'tool_use',
                        'id' => 'tool_123',
                        'name' => 'test-server.test-tool',
                        'input' => ['param1' => 'test']
                    ]
                ],
                'model' => 'claude-3-sonnet-20240229',
                'stop_reason' => null,
                'stop_sequence' => null,
                'usage' => [
                    'input_tokens' => 10,
                    'output_tokens' => 20
                ]
            ])
        ]);

        $this->client->processRequest('Test message with queued tool', $this->chatUuid);

        // Verify tool was queued
        $runner = Runner::first();
        $this->assertNotNull($runner);
        $this->assertEquals(RunnerStatus::QUEUED, $runner->status);

        Queue::assertPushed(RunTool::class, function ($job) use ($runner) {
            return $job->runner->id === $runner->id;
        });
    }

    public function test_it_handles_tool_execution_errors(): void
    {
        // Setup tool execution mock to throw exception
        $this->mockServer->shouldReceive('toolShouldQueue')
            ->with('test-tool')
            ->andReturn(false);

        $this->mockServer->shouldReceive('executeTool')
            ->with('test-tool', Mockery::any(), Mockery::type(LoggerInterface::class))
            ->andThrow(new \Exception('Tool execution failed'));

        // Mock Claude response with tool use
        Http::fake([
            'api.anthropic.com/v1/messages' => Http::response([
                'id' => 'msg_123',
                'type' => 'message',
                'role' => 'assistant',
                'content' => [
                    [
                        'type' => 'tool_use',
                        'id' => 'tool_123',
                        'name' => 'test-server.test-tool',
                        'input' => ['param1' => 'test']
                    ]
                ],
                'model' => 'claude-3-sonnet-20240229',
                'stop_reason' => null,
                'stop_sequence' => null,
                'usage' => [
                    'input_tokens' => 10,
                    'output_tokens' => 20
                ]
            ])
        ]);

        $this->client->processRequest('Test message with failing tool', $this->chatUuid);

        // Verify error was recorded
        $runner = Runner::first();
        $this->assertNotNull($runner);
        $this->assertEquals(RunnerStatus::FAILED, $runner->status);
        $this->assertEquals('Tool execution failed', $runner->error);

        // Verify error was logged
        $this->assertDatabaseHas('logs', [
            'runner_id' => $runner->id,
            'level' => 'error',
            'message' => 'Tool execution failed'
        ]);
    }

    public function test_it_generates_chat_title(): void
    {
        Queue::fake();

        $this->client->processRequest('Test message', $this->chatUuid);

        Queue::assertPushed(GenerateChatTitle::class, function ($job) {
            return $job->chat->id === Chat::first()->id;
        });
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
