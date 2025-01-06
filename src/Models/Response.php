<?php

namespace Scriptoshi\McpClient\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Scriptoshi\McpClient\Enums\ResponseType;

class Response extends Model
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
        
        $this->table = config('mcp.table_names.responses', 'responses');
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'content' => 'array',
        'type' => ResponseType::class,
        'input_tokens' => 'integer',
        'output_tokens' => 'integer'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'message_id',
        'content',
        'anthropic_id',
        'model',
        'role',
        'stop_reason',
        'stop_sequence',
        'type',
        'input_tokens',
        'output_tokens'
    ];

    /**
     * Get the message this response belongs to.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Get the runners for this response.
     */
    public function runners(): HasMany
    {
        return $this->hasMany(Runner::class);
    }

    /**
     * Get the channels that model events should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel|\Illuminate\Database\Eloquent\Channels\Channel>
     */
    public function broadcastOn(string $event): array
    {
        return [
            new \Illuminate\Broadcasting\Channel("chat.{$this->message->chat_id}")
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
            'message_id' => $this->message_id,
            'content' => $this->content,
            'model' => $this->model,
            'role' => $this->role,
            'type' => $this->type->value,
            'created_at' => $this->created_at,
            'event' => $event
        ];
    }
}