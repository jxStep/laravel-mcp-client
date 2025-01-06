<?php

namespace Scriptoshi\McpClient\Tests\Unit\Tools;

use Scriptoshi\McpClient\Tests\TestCase;
use Scriptoshi\McpClient\Tools\ApiToolExecutor;
use Scriptoshi\McpClient\Models\Runner;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ApiToolExecutorTest extends TestCase
{
    protected array $toolConfig;
    protected PendingRequest $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->toolConfig = [
            'name' => 'test-tool',
            'description' => 'A test tool',
            'shouldQueue' => false,
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'param1' => [
                        'type' => 'string',
                        'description' => 'Test parameter'
                    ]
                ],
                'required' => ['param1']
            ],
            'inputValidation' => [
                'param1' => 'required|string'
            ],
            'request' => [
                'method' => 'POST',
                'endpoint' => '/test',  // Remove domain for testing
                'headers' => [
                    'X-API-Key' => 'test-key'
                ]
            ],
            'mapping' => [
                'body' => [
                    'input' => 'param1'
                ]
            ],
            'error' => [
                'field' => 'error',
                'value' => true,
                'message' => 'error_message'
            ]
        ];

        // Configure the client for testing with a base URL
        $this->client = Http::baseUrl('http://localhost')
            ->withHeaders([
                'Accept' => 'application/json',
            ]);
    }

    public function test_it_returns_tool_info(): void
    {
        $tool = new ApiToolExecutor($this->client, $this->toolConfig);

        $this->assertEquals('test-tool', $tool->getName());
        $this->assertEquals('A test tool', $tool->getDescription());
        $this->assertFalse($tool->shouldQueue());
        
        $schema = $tool->getInputSchema();
        $this->assertEquals('string', $schema['properties']['param1']['type']);
        $this->assertEquals('Test parameter', $schema['properties']['param1']['description']);
        $this->assertContains('param1', $schema['required']);
    }

    public function test_it_executes_successful_api_call(): void
    {
        Http::fake([
            'http://localhost/test' => Http::response([
                'data' => 'success',
                'error' => false
            ], 200)
        ]);

        $tool = new ApiToolExecutor($this->client, $this->toolConfig);
        
        $logger = new Runner();
        $logger->forceFill([
            'id' => 1,
            'response_id' => 1,
            'uuid' => Str::uuid(),
            'mcp_server' => 'test-server',
            'tool_name' => 'test-tool',
            'tool_use_id' => 'test-use-id',
            'status' => 'processing',
            'arguments' => ['param1' => 'test']
        ]);
        $logger->save();

        $result = $tool->execute(['param1' => 'test'], $logger);

        $this->assertEquals([
            'data' => 'success',
            'error' => false
        ], $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://localhost/test' &&
                   $request->method() === 'POST' &&
                   $request->header('X-API-Key')[0] === 'test-key' &&
                   $request['input'] === 'test';
        });

        $this->assertDatabaseHas('logs', [
            'runner_id' => $logger->id,
            'level' => 'info',
            'message' => 'post API Request:'
        ]);

        $this->assertDatabaseHas('logs', [
            'runner_id' => $logger->id,
            'level' => 'success',
            'message' => 'API response successful'
        ]);
    }

    public function test_it_handles_validation_failure(): void
    {
        $tool = new ApiToolExecutor($this->client, $this->toolConfig);
        
        $logger = new Runner();
        $logger->forceFill([
            'id' => 1,
            'response_id' => 1,
            'uuid' => Str::uuid(),
            'mcp_server' => 'test-server',
            'tool_name' => 'test-tool',
            'tool_use_id' => 'test-use-id',
            'status' => 'processing',
            'arguments' => []  // Empty arguments for validation failure
        ]);
        $logger->save();

        $result = $tool->execute([], $logger);

        $this->assertTrue($result['isError']);
        $this->assertEquals('API call failed.', $result['message']);
        $this->assertStringContainsString('Validation failed:', $result['error']);

        $this->assertDatabaseHas('logs', [
            'runner_id' => $logger->id,
            'level' => 'error',
            'message' => 'Tool execution failed'
        ]);
    }

    public function test_it_handles_api_error_response(): void
    {
        Http::fake([
            'http://localhost/test' => Http::response([
                'error' => true,
                'error_message' => 'API Error occurred'
            ], 200)
        ]);

        $tool = new ApiToolExecutor($this->client, $this->toolConfig);
        
        $logger = new Runner();
        $logger->forceFill([
            'id' => 1,
            'response_id' => 1,
            'uuid' => Str::uuid(),
            'mcp_server' => 'test-server',
            'tool_name' => 'test-tool',
            'tool_use_id' => 'test-use-id',
            'status' => 'processing',
            'arguments' => ['param1' => 'test']
        ]);
        $logger->save();

        $result = $tool->execute(['param1' => 'test'], $logger);

        $this->assertTrue($result['isError']);
        $this->assertEquals('API call failed.', $result['message']);
        $this->assertEquals('API Error occurred', $result['error']);

        $this->assertDatabaseHas('logs', [
            'runner_id' => $logger->id,
            'level' => 'error',
            'message' => 'Tool execution failed'
        ]);
    }

    public function test_it_handles_http_failure(): void
    {
        Http::fake([
            'http://localhost/test' => Http::response(null, 500)
        ]);

        $tool = new ApiToolExecutor($this->client, $this->toolConfig);
        
        $logger = new Runner();
        $logger->forceFill([
            'id' => 1,
            'response_id' => 1,
            'uuid' => Str::uuid(),
            'mcp_server' => 'test-server',
            'tool_name' => 'test-tool',
            'tool_use_id' => 'test-use-id',
            'status' => 'processing',
            'arguments' => ['param1' => 'test']
        ]);
        $logger->save();

        $result = $tool->execute(['param1' => 'test'], $logger);

        $this->assertTrue($result['isError']);
        $this->assertEquals('API call failed.', $result['message']);
        $this->assertStringContainsString('500', $result['error']);

        $this->assertDatabaseHas('logs', [
            'runner_id' => $logger->id,
            'level' => 'error',
            'message' => 'Tool execution failed'
        ]);
    }
}