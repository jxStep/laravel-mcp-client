<?php

namespace Scriptoshi\McpClient\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Scriptoshi\McpClient\Traits\HasUuid;
use Scriptoshi\McpClient\Enums\MessageRole;

class Message extends Model
{
    use SoftDeletes;
    use HasUuid;

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

        $this->table = config('mcp.table_names.messages', 'messages');
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'role' => MessageRole::class,
        'content' => 'array',
        'metadata' => 'array',
        'error' => 'boolean',
        'processed' => 'boolean'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'chat_id',
        'role',
        'uuid',
        'content',
        'metadata',
        'error',
        'processed'
    ];

    /**
     * Get the chat this message belongs to.
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    /**
     * Get the responses for this message.
     */
    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }

    /**
     * mark the message as processed
     */
    public function process(): void
    {
        $this->processed = true;
        $this->save();
    }

    /**
     * mark the message as errored
     */
    public function errored(string $error = null): void
    {
        $this->error = true;
        $this->metadata = [
            ...($this->metadata ?? []),
            'error' => $error ?? __('An error occurred while processing your request')
        ];
        $this->save();
    }
}