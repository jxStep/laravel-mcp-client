<?php

namespace Scriptoshi\McpClient\Enums;

enum RunnerStatus: string
{
    case PENDING = 'pending';
    case QUEUED = 'queued';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    /**
     * Check if the status is considered active.
     */
    public function isActive(): bool
    {
        return match($this) {
            self::PENDING, self::QUEUED, self::PROCESSING => true,
            default => false
        };
    }

    /**
     * Check if the status is considered final.
     */
    public function isFinal(): bool
    {
        return match($this) {
            self::COMPLETED, self::FAILED => true,
            default => false
        };
    }
}