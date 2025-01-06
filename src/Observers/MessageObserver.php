<?php

namespace Scriptoshi\McpClient\Observers;

use Scriptoshi\McpClient\Models\Message;
use Scriptoshi\McpClient\Events\MessageCreatedEvent;
use Scriptoshi\McpClient\Events\MessageProcessedEvent;
use Scriptoshi\McpClient\Events\MessageErrorEvent;

class MessageObserver
{
    /**
     * Handle the Message "created" event.
     */
    public function created(Message $message): void
    {
        event(new MessageCreatedEvent($message));
    }

    /**
     * Handle the Message "updated" event.
     */
    public function updated(Message $message): void
    {
        // If the message was just processed
        if ($message->wasChanged('processed') && $message->processed) {
            event(new MessageProcessedEvent($message));
        }

        // If an error occurred
        if ($message->wasChanged('error') && $message->error) {
            $errorMessage = $message->metadata['error_message'] ?? 'An unknown error occurred';
            event(new MessageErrorEvent($message, $errorMessage));
        }
    }
}