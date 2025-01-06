<?php

namespace Scriptoshi\McpClient\Contracts;

interface McpServerInterface
{

    /**
     * Initialize the MCP server and return server information and capabilities.
     *
     * @return array{
     *     serverInfo: array{name: string, version: string},
     *     capabilities: array{tools: bool}
     * }
     */
    public function initialize(): array;

    /**
     * Get a list of all available tools provided by this server.
     *
     * @return array{tools: array<array{
     *     name: string,
     *     description: string,
     *     inputSchema: array
     * }>}
     */
    public function listTools(): array;

    /**
     * Check if a specific tool should be queued.
     *
     * @param string $toolname Name of the tool to check
     * @return boolean Whether the tool should be queued
     */
    public function toolShouldQueue(string $toolname): bool;

    /**
     * Execute a specific tool with the provided arguments.
     *
     * @param string $name The name of the tool to execute
     * @param array $arguments The arguments to pass to the tool
     * @param LoggerInterface $logger Logger instance for tracking execution
     * @return array{
     *     content?: array<array{type: string, text: string}>,
     *     error?: bool
     * }
     * @throws \Exception When tool is not found or execution fails
     */
    public function executeTool(string $name, array $arguments, LoggerInterface $logger): array;
}
