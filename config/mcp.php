<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Anthropic API Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can configure your Anthropic API settings.
    |
    */
    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-3-sonnet-20240229'),
        'max_tokens' => env('ANTHROPIC_MAX_TOKENS', 1024),
    ],

    /*
    |--------------------------------------------------------------------------
    | MCP Servers Configuration 
    |--------------------------------------------------------------------------
    |
    | Register your MCP servers here. Each server should have a unique key and
    | specify its class name. You can also provide server-specific configuration.
    |
    | Example:
    | 'servers' => [
    |     'python' => [
    |         'class' => \App\McpServers\PythonMcpServer::class,
    |         'config' => [
    |             'base_url' => env('PYTHON_MCP_URL'),
    |             'api_key' => env('PYTHON_MCP_KEY'),
    |         ],
    |     ],
    | ],
    |
    */
    'servers' => [
        // Register your MCP servers here
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Tables
    |--------------------------------------------------------------------------
    |
    | You can customize the table names used by the package.
    |
    */
    'table_names' => [
        'chats' => 'chats',
        'messages' => 'messages',
        'responses' => 'responses',
        'runners' => 'runners',
        'logs' => 'logs',
    ],
    'system' => "Answer the user's request using relevant tools (if they are available). Before calling a tool, do some analysis within \<thinking>\</thinking> tags. First, think about which of the provided tools is the relevant tool to answer the user's request. Second, go through each of the required parameters of the relevant tool and determine if the user has directly provided or given enough information to infer a value. When deciding if the parameter can be inferred, carefully consider all the context to see if it supports a specific value. If all of the required parameters are present or can be reasonably inferred, close the thinking tag and proceed with the tool call. BUT, if one of the values for a required parameter is missing, DO NOT invoke the function (not even with fillers for the missing params) and instead, ask the user to provide the missing parameters. DO NOT ask for more information on optional parameters if it is not provided."
];
