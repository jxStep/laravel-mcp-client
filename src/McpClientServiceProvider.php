<?php

namespace Scriptoshi\McpClient;

use Illuminate\Support\ServiceProvider;
use Scriptoshi\McpClient\Models\Message;
use Scriptoshi\McpClient\Observers\MessageObserver;

class McpClientServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     */
    public function register(): void
    {
        // Register the main class to use with the facade
        $this->app->singleton('mcp-client', function () {
            return new McpClient();
        });

        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/mcp.php', 'mcp'
        );
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        // Register message observer (keeping this one for error handling)
        Message::observe(MessageObserver::class);

        if ($this->app->runningInConsole()) {
            // Publish configuration
            $this->publishes([
                __DIR__.'/../config/mcp.php' => config_path('mcp.php'),
            ], 'mcp-config');

            // Publish migrations
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'mcp-migrations');
        }
    }
}