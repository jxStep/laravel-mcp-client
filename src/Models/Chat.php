<?php

namespace Scriptoshi\McpClient\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Scriptoshi\McpClient\Traits\HasUuid;

class Chat extends Model
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
        
        $this->table = config('mcp.table_names.chats', 'chats');
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'uuid',
        'title',
        'metadata'
    ];

    /**
     * Get the messages for this chat.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}