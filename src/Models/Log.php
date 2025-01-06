<?php

namespace Scriptoshi\McpClient\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Scriptoshi\McpClient\Enums\LogLevel;

class Log extends Model
{
    use SoftDeletes;
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
        
        $this->table = config('mcp.table_names.logs', 'logs');
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'level' => LogLevel::class,
        'context' => 'array'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'runner_id',
        'level',
        'message',
        'context'
    ];

    /**
     * Get the runner this log belongs to.
     */
    public function runner(): BelongsTo
    {
        return $this->belongsTo(Runner::class);
    }

    /**
     * Get the channels that model events should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel|\Illuminate\Database\Eloquent\Channels\Channel>
     */
    public function broadcastOn(string $event): array
    {
        return [
            new \Illuminate\Broadcasting\Channel("runner.{$this->runner_id}"),
            new \Illuminate\Broadcasting\Channel("chat.{$this->runner->response->message->chat_id}")
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
            'id' => $this->id,
            'runner_id' => $this->runner_id,
            'level' => $this->level->value,
            'message' => $this->message,
            'context' => $this->context,
            'created_at' => $this->created_at,
            'event' => $event
        ];
    }
}