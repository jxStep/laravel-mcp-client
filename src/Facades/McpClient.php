<?php

namespace Scriptoshi\McpClient\Facades;

use Illuminate\Support\Facades\Facade;
use Scriptoshi\McpClient\Contracts\McpServerInterface;
use Scriptoshi\McpClient\Models\Runner;

/**
 * @method static void registerServer(string $name, McpServerInterface $server)
 * @method static void processRequest(string $userMessage, string $chatId)
 * @method static array executeTool(Runner $runner)
 * 
 * @see \Scriptoshi\McpClient\McpClient
 */
class McpClient extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'mcp-client';
    }
}