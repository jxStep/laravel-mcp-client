<?php

namespace Scriptoshi\McpClient\Servers;

use Scriptoshi\McpClient\Contracts\LoggerInterface;
use Scriptoshi\McpClient\Contracts\McpServerInterface;
use Scriptoshi\McpClient\Contracts\McpToolInterface;

/**
 * Abstract base class that provides common MCP server functionality.
 * 
 * Implements basic tool management and standard server initialization,
 * reducing boilerplate code in concrete server implementations.
 */
abstract class BaseMcpServer implements McpServerInterface
{
    /** @var array<string, McpToolInterface> List of registered tools */
    protected array $tools = [];

    public function __construct() {}

    /**
     * @inheritDoc
     */
    public function initialize(): array
    {
        return [
            'serverInfo' => [
                'name' => static::class,
                'version' => '1.0.0'
            ],
            'capabilities' => [
                'tools' => true
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    public function listTools(): array
    {
        $tools = [];
        foreach ($this->tools as $tool) {
            $tools[] = [
                'name' => $tool->getName(),
                'description' => $tool->getDescription(),
                'inputSchema' => $tool->getInputSchema()
            ];
        }
        return ['tools' => $tools];
    }

    /**
     * @inheritDoc
     */
    public function toolShouldQueue(string $toolname): bool
    {
        if (!isset($this->tools[$toolname])) {
            throw new \Exception("Tool not found: {$toolname}");
        }

        return $this->tools[$toolname]->shouldQueue();
    }

    /**
     * @inheritDoc
     */
    public function executeTool(string $name, array $arguments, LoggerInterface $logger): array
    {
        if (!isset($this->tools[$name])) {
            throw new \Exception("Tool not found: {$name}");
        }

        return $this->tools[$name]->execute($arguments, $logger);
    }

    /**
     * Register a new tool with this server.
     *
     * @param McpToolInterface $tool The tool to register
     * @return void
     */
    protected function registerTool(McpToolInterface $tool): void
    {
        $this->tools[$tool->getName()] = $tool;
    }
}