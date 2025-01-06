<?php

namespace Scriptoshi\McpClient\Enums;

enum LogLevel: string
{
    case ERROR = 'error';
    case WARNING = 'warning';
    case INFO = 'info';
    case SUCCESS = 'success';
    case PROGRESS = 'progress';

    /**
     * Get the severity level of the log.
     */
    public function severity(): int
    {
        return match($this) {
            self::ERROR => 500,
            self::WARNING => 400,
            self::INFO => 200,
            self::SUCCESS => 100,
            self::PROGRESS => 50
        };
    }

    /**
     * Check if this is an error level.
     */
    public function isError(): bool
    {
        return $this === self::ERROR;
    }

    /**
     * Check if this is a warning level.
     */
    public function isWarning(): bool
    {
        return $this === self::WARNING;
    }

    /**
     * Check if this is a success level.
     */
    public function isSuccess(): bool
    {
        return $this === self::SUCCESS;
    }
}