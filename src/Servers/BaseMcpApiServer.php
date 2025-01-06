<?php

namespace Scriptoshi\McpClient\Servers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Log;
use Scriptoshi\McpClient\Tools\ApiToolExecutor;

/**
 * Abstract base class that provides common API-based MCP server functionality.
 * 
 * Implements automated tool loading from JSON configurations and API client management.
 */
abstract class BaseMcpApiServer extends BaseMcpServer
{
    /** @var string Path to json tools configurations */
    protected string $toolsPath;

    /**
     * Create a new HTTP client instance for API requests.
     */
    abstract protected function client(): PendingRequest;

    /**
     * Load tools from JSON configuration files.
     */
    protected function loadTools(): void
    {
        try {
            $toolFiles = glob("{$this->toolsPath}/*.json");
            foreach ($toolFiles as $file) {
                $toolConfig = $this->loadToolConfig($file);
                if ($toolConfig) {
                    $this->registerTool(new ApiToolExecutor($this->client(), $toolConfig));
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to load api tools', [
                'error' => $e->getMessage(),
                'tools_path' => $this->toolsPath
            ]);
        }
    }

    /**
     * Load and validate a tool configuration from JSON file.
     *
     * @param string $file Path to the JSON configuration file
     * @return array|null The validated configuration or null if invalid
     */
    protected function loadToolConfig(string $file): ?array
    {
        try {
            $content = file_get_contents($file);
            $config = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException("Invalid JSON in tool config: " . json_last_error_msg());
            }

            // Validate required fields
            if (!isset($config['name'], $config['description'], $config['inputSchema'])) {
                Log::warning('Invalid tool configuration', [
                    'file' => $file,
                    'missing_fields' => array_diff(
                        ['name', 'description', 'inputSchema'],
                        array_keys($config)
                    )
                ]);
                return null;
            }

            return $config;
        } catch (\Exception $e) {
            Log::error('Failed to load tool configuration', [
                'file' => $file,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}