<?php

namespace Scriptoshi\McpClient\Contracts;

interface McpToolInterface
{
    /**
     * Get the unique identifier for this tool.
     *
     * @return string The tool's name
     */
    public function getName(): string;

    /**
     * Get a human-readable description of what this tool does.
     *
     * @return string The tool's description
     */
    public function getDescription(): string;

    /**
     * Check if the tool should be queued.
     *
     * @return boolean
     */
    public function shouldQueue(): bool;

    /**
     * Get the JSON Schema that describes the required input format.
     *
     * @return array The JSON Schema definition for tool inputs
     */
    public function getInputSchema(): array;

    /**
     * Execute the tool with the provided arguments.
     *
     * @param array $arguments The validated input arguments
     * @param LoggerInterface $logger Logger instance for tracking execution
     * @return array{
     *     content?: array<array{type: string, text: string}>,
     *     error?: bool
     * }
     * @throws \Exception When execution fails
     */
    public function execute(array $arguments, LoggerInterface $logger): array;
}