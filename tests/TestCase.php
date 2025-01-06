<?php

namespace Scriptoshi\McpClient\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Scriptoshi\McpClient\McpClientServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Fake events and queues by default in tests
        Event::fake();
        Queue::fake();
    }

    protected function getPackageProviders($app): array
    {
        return [
            McpClientServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        // Include Laravel's core migrations for queue/jobs
        $this->loadMigrationsFrom(dirname(__DIR__) . '/vendor/laravel/framework/src/Illuminate/Queue/migrations');
        
        // Load package migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Set up the testing database
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Configure MCP settings
        config()->set('mcp.anthropic.api_key', 'test-api-key');
        config()->set('mcp.anthropic.model', 'claude-3-sonnet-20240229');
        
        // Set queue driver to sync for testing
        config()->set('queue.default', 'sync');
        
        // Disable broadcasting in tests
        config()->set('broadcasting.default', 'null');
    }
}