# Laravel MCP Client

Disclosure: This package was designed and written Entirely by claude ai. I may have guided and nuggged it in a few places, but the code was written by claude ai.

Laravel MCP (Message Context Protocol) Client is a package that integrates Anthropic's Claude AI model with custom tool servers, allowing you to extend Claude's capabilities with your own tools and services.

## Features

-   Seamless integration with Anthropic's Claude API
-   Tool server management and execution
-   Built-in queuing support for long-running tools
-   Event-driven architecture
-   Automatic chat title generation
-   Complete chat history management
-   Database persistence for conversations and tool executions
-   Soft deletes support
-   Comprehensive logging system

## Requirements

-   PHP 8.3 or higher
-   Laravel 11.0 or higher
-   Anthropic API key

## Installation

You can install the package via composer:

```bash
composer require scriptoshi/laravel-mcp-client
```

## Configuration

1. Publish the configuration file:

```bash
php artisan vendor:publish --provider="Scriptoshi\McpClient\McpClientServiceProvider"
```

2. Add your Anthropic API key to your .env file:

```env
ANTHROPIC_API_KEY=your-api-key-here
ANTHROPIC_MODEL=claude-3-sonnet-20240229
ANTHROPIC_MAX_TOKENS=1024
```

3. Run the migrations:

```bash
php artisan migrate
```

## Usage

### Basic Usage

```php
use Scriptoshi\McpClient\Facades\McpClient;

// Start a new chat
McpClient::processRequest("What's the weather like?", $chatUuid);
```

### Implementing a Custom Tool Server

Create a new server class that implements `McpServerInterface`:

```php
use Scriptoshi\McpClient\Contracts\McpServerInterface;

class WeatherServer implements McpServerInterface
{
    public function initialize(): array
    {
        return [
            'serverInfo' => [
                'name' => 'WeatherServer',
                'version' => '1.0.0'
            ],
            'capabilities' => [
                'tools' => true
            ]
        ];
    }

    public function listTools(): array
    {
        return [
            'tools' => [
                [
                    'name' => 'get_weather',
                    'description' => 'Get current weather for a location',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'location' => [
                                'type' => 'string',
                                'description' => 'City name or coordinates'
                            ]
                        ],
                        'required' => ['location']
                    ]
                ]
            ]
        ];
    }

    public function toolShouldQueue(string $toolname): bool
    {
        return false;
    }

    public function executeTool(string $name, array $arguments, LoggerInterface $logger): array
    {
        // Implement your tool logic here
    }
}
```

### Registering a Tool Server

You can register tool servers in your `AppServiceProvider` or create a dedicated service provider:

```php
use Scriptoshi\McpClient\Facades\McpClient;

public function boot()
{
    McpClient::registerServer('weather', new WeatherServer());
}
```

### Using the Queue

For long-running tools, implement queueing:

```php
public function toolShouldQueue(string $toolname): bool
{
    return match($toolname) {
        'long_running_process' => true,
        default => false
    };
}
```

### Working with Chat History

```php
use Scriptoshi\McpClient\Models\Chat;

// Find a chat by UUID
$chat = Chat::findByUuid($uuid);

// Get chat messages
$messages = $chat->messages;

// Get message responses
$responses = $message->responses;

// Get tool executions
$runners = $response->runners;
```

### Error Handling

The package includes comprehensive error handling and logging:

```php
$runner->error('Something went wrong', [
    'context' => 'Additional error details'
]);

// Different log levels
$runner->info('Processing started');
$runner->warning('Resource usage high');
$runner->success('Operation completed');
```

## Events

The package dispatches several events you can listen for:

-   `MessageCreatedEvent`
-   `MessageProcessedEvent`
-   `MessageErrorEvent`

## Testing

```bash
composer test
```

## Security

If you discover any security-related issues, please email security@scriptoshi.com instead of using the issue tracker.

## Credits

-   [Scriptoshi](https://github.com/scriptoshi)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
