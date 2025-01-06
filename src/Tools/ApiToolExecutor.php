<?php

namespace Scriptoshi\McpClient\Tools;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Validator;
use Scriptoshi\McpClient\Contracts\LoggerInterface;
use Scriptoshi\McpClient\Contracts\McpToolInterface;

class ApiToolExecutor implements McpToolInterface
{
    /**
     * @param PendingRequest $client HTTP client for making API requests
     * @param array $toolConfig Configuration for this tool
     */
    public function __construct(
        protected PendingRequest $client,
        protected array $toolConfig
    ) {}

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->toolConfig['name'];
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return $this->toolConfig['description'];
    }

    /**
     * @inheritDoc
     */
    public function shouldQueue(): bool
    {
        return $this->toolConfig['shouldQueue'] ?? false;
    }

    /**
     * @inheritDoc
     */
    public function getInputSchema(): array
    {
        $schema = $this->toolConfig['inputSchema'];

        // Keep only essential fields for each property
        if (isset($schema['properties'])) {
            foreach ($schema['properties'] as $key => $prop) {
                $schema['properties'][$key] = [
                    'type' => $prop['type'],
                    'description' => $prop['description']
                ];
            }
        }

        return $schema;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $arguments, LoggerInterface $logger): array
    {
        try {
            // Validate input
            $this->validateArguments($arguments);

            // Prepare and execute request
            $request = $this->buildRequest($arguments);
            $method = strtolower($this->toolConfig['request']['method']);
            $options = $method == 'get' ?
                ($request['options']['query'] ?? []) : ($request['options']['json'] ?? []);
            $url = $request['url'];
            $logger->info($method . ' API Request:', $options);

            $response = match ($method) {
                'get' => $this->client->get($url, $options),
                'post' => $this->client->post($url, $options),
                'put' => $this->client->put($url, $options),
                'patch' => $this->client->patch($url, $options),
                'delete' => $this->client->delete($url, $options),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}")
            };

            // Check if response failed
            $this->checkApiError($response);

            $logger->success('API response successful');
            $logger->complete($response->json());

            return $response->json();
        } catch (\Exception $e) {
            $error = [
                'isError' => true,
                'message' => 'API call failed.',
                'error' => $e->getMessage()
            ];

            $logger->error('Tool execution failed', [
                'error_type' => get_class($e),
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            $logger->complete($error);

            return $error;
        }
    }

    /**
     * Validate the input arguments against the tool's schema.
     *
     * @param array $arguments The arguments to validate
     * @throws \InvalidArgumentException If validation fails
     */
    protected function validateArguments(array $arguments): void
    {
        // Get validation rules from config
        $rules = $this->toolConfig['inputValidation'] ?? [];

        if (empty($rules)) {
            // If no validation rules defined, just check required fields from inputSchema
            $required = $this->toolConfig['inputSchema']['required'] ?? [];
            $rules = array_fill_keys($required, 'required');
        }

        $validator = Validator::make($arguments, $rules);

        if ($validator->fails()) {
            throw new \InvalidArgumentException(
                "Validation failed: " . implode(", ", $validator->errors()->all())
            );
        }
    }

    /**
     * Build the HTTP request configuration from the arguments.
     *
     * @param array $arguments The input arguments
     * @return array The request configuration
     */
    protected function buildRequest(array $arguments): array
    {
        $mapping = $this->toolConfig['mapping'];
        $url = $this->toolConfig['request']['endpoint'];

        // Build path parameters
        foreach ($mapping['path'] ?? [] as $target => $source) {
            $url = str_replace("{{$target}}", $arguments[$source], $url);
        }

        // Build options
        $options = ['headers' => $this->toolConfig['request']['headers'] ?? []];

        // Add query parameters
        if (isset($mapping['query'])) {
            $options['query'] = $this->mapParameters($arguments, $mapping['query']);
        }

        // Add body parameters
        if (isset($mapping['body'])) {
            $options['json'] = $this->mapParameters($arguments, $mapping['body']);
        }

        return [
            'url' => $url,
            'options' => $options
        ];
    }

    /**
     * Map input parameters to API parameters according to configuration.
     *
     * @param array $arguments The input arguments
     * @param array $mapping The parameter mapping configuration
     * @return array The mapped parameters
     */
    protected function mapParameters(array $arguments, array $mapping): array
    {
        $result = [];
        foreach ($mapping as $target => $source) {
            if (isset($arguments[$source])) {
                $result[$target] = $arguments[$source];
            }
        }
        return $result;
    }

    /**
     * Check if the API response indicates an error.
     *
     * @param Response $response The API response
     * @throws \Exception If an error is detected
     */
    protected function checkApiError(Response $response): void
    {
        $data = $response->json();

        // Get error checking configuration
        $errorConfig = $this->toolConfig['error'] ?? null;
        if ($errorConfig) {
            $field = $errorConfig['field'] ?? null;
            $value = $errorConfig['value'] ?? null;
            $message = $errorConfig['message'] ?? null;

            if (
                $field &&
                isset($data[$field]) &&
                $data[$field] === $value &&
                $message &&
                isset($data[$message])
            ) {
                throw new \Exception($data[$message]);
            }
        }

        if ($response->failed()) {
            throw new \Exception($response->body(), $response->status());
        }
    }
}
