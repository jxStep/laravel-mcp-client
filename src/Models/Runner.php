<?php

namespace Scriptoshi\McpClient\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Scriptoshi\McpClient\Contracts\LoggerInterface;
use Scriptoshi\McpClient\Traits\HasUuid;
use Scriptoshi\McpClient\Enums\RunnerStatus;
use Scriptoshi\McpClient\Enums\LogLevel;

class Runner extends Model implements LoggerInterface
{
    use SoftDeletes;
    use HasUuid;
    use BroadcastsEvents;

    /**
     * The table associated with the model.
     */
    protected $table;

    /**
     * Create a new model instance.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('mcp.table_names.runners', 'runners');
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'arguments' => 'array',
        'result' => 'array',
        'status' => RunnerStatus::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'response_id',
        'uuid',
        'mcp_server',
        'tool_name',
        'tool_use_id',
        'arguments',
        'result',
        'error',
        'status',
        'started_at',
        'completed_at'
    ];

    /**
     * Get the response this runner belongs to.
     */
    public function response(): BelongsTo
    {
        return $this->belongsTo(Response::class);
    }

    /**
     * Get the logs for this runner.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(Log::class);
    }

    /**
     * Get the channels that model events should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel|\Illuminate\Database\Eloquent\Channels\Channel>
     */
    public function broadcastOn(string $event): array
    {
        return [
            new \Illuminate\Broadcasting\Channel("runner.{$this->uuid}")
        ];
    }

    /**
     * Get the data to broadcast for the model.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(string $event): array
    {
        return [
            'response_id' => $this->response_id,
            'uuid' => $this->uuid,
            'mcp_server' => $this->mcp_server,
            'tool_name' => $this->tool_name,
            'status' => $this->status->value,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'event' => $event
        ];
    }

    /**
     * Complete the execution.
     */
    public function complete(array $response = []): void
    {
        $this->update([
            'status' => RunnerStatus::COMPLETED,
            'result' => $response,
            'completed_at' => now()
        ]);
    }

    /**
     * Log an error message.
     */
    public function error(string $message, array $context = []): Log
    {
        return $this->logs()->create([
            'level' => LogLevel::ERROR,
            'message' => $message,
            'context' => $context
        ]);
    }

    /**
     * Log an info message.
     */
    public function info(string $message, array $context = []): Log
    {
        return $this->logs()->create([
            'level' => LogLevel::INFO,
            'message' => $message,
            'context' => $context
        ]);
    }

    /**
     * Log a warning message.
     */
    public function warning(string $message, array $context = []): Log
    {
        return $this->logs()->create([
            'level' => LogLevel::WARNING,
            'message' => $message,
            'context' => $context
        ]);
    }

    /**
     * Log a success message.
     */
    public function success(string $message, array $context = []): Log
    {
        return $this->logs()->create([
            'level' => LogLevel::SUCCESS,
            'message' => $message,
            'context' => $context
        ]);
    }

    /**
     * Log a progress message.
     */
    public function progress(string $message, array $context = []): Log
    {
        return $this->logs()->create([
            'level' => LogLevel::PROGRESS,
            'message' => $message,
            'context' => $context
        ]);
    }
}
