<?php

namespace Scriptoshi\McpClient\Contracts;

use Scriptoshi\McpClient\Models\Log;

interface LoggerInterface
{
    /**
     * Complete the logger operation.
     *
     * @param array $response Optional response data
     */
    public function complete(array $response = []): void;

    /**
     * Log an error message.
     *
     * @param string $message The error message
     * @param array $context Additional context data
     */
    public function error(string $message, array $context = []): Log;

    /**
     * Log an info message.
     *
     * @param string $message The info message
     * @param array $context Additional context data
     */
    public function info(string $message, array $context = []): Log;

    /**
     * Log a warning message.
     *
     * @param string $message The warning message
     * @param array $context Additional context data
     */
    public function warning(string $message, array $context = []): Log;

    /**
     * Log a success message.
     *
     * @param string $message The success message
     * @param array $context Additional context data
     */
    public function success(string $message, array $context = []): Log;

    /**
     * Log a progress message.
     *
     * @param string $message The progress message
     * @param array $context Additional context data
     */
    public function progress(string $message, array $context = []): Log;
}