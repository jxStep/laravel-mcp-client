<?php

namespace Scriptoshi\McpClient\Traits;

use Illuminate\Support\Str;

trait HasUuid
{
    /**
     * Bootstrap the trait.
     */
    protected static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            if (! $model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get a new instance by UUID.
     */
    public static function findByUuid(string $uuid): ?static
    {
        return static::where('uuid', $uuid)->first();
    }

    /**
     * Get a new instance by UUID or fail.
     */
    public static function findByUuidOrFail(string $uuid): static
    {
        return static::where('uuid', $uuid)->firstOrFail();
    }
}