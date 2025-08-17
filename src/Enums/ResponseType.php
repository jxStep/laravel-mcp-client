<?php

namespace Scriptoshi\McpClient\Enums;

enum ResponseType: string
{
    case USER = 'user';
    case ASSISTANT = 'assistant';
    case MESSAGE = 'message';
}
