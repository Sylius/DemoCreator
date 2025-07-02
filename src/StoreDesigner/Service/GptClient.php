<?php

declare(strict_types=1);

namespace App\StoreDesigner\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Psr\Log\LoggerInterface;

final readonly class GptClient
{
    public function __construct(
        private HttpClientInterface $client,
        #[Autowire(env: 'OPENAI_API_KEY')] private string $openaiApiKey,
        private LoggerInterface $logger,
    ) {
    }

    public function chatCompletions(array $messages, string $model, int $maxCompletionTokens = 1024, array $functions = []): GptResponse
    {
        $requestId = uniqid('gpt_', true);
        $this->logger->info('Starting GPT request', [
            'request_id' => $requestId,
            'model' => $model,
            'max_tokens' => $maxCompletionTokens,
            'messages_count' => count($messages),
            'has_functions' => !empty($functions)
        ]);

        try {
            $payload = [
                'model' => $model,
                'messages' => $messages,
                'functions' => $functions,
                'function_call' => 'auto',
                'max_completion_tokens' => $maxCompletionTokens,
            ];

            $this->logger->debug('GPT request payload', [
                'request_id' => $requestId,
                'payload' => $payload
            ]);

            $response = $this->client->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->openaiApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
                'timeout' => 600, // 10 minutes timeout
            ]);

            $statusCode = $response->getStatusCode();
            $this->logger->info('GPT response received', [
                'request_id' => $requestId,
                'status_code' => $statusCode
            ]);

            $body = $response->toArray(false);
            
            // Log the full response for debugging
            $this->logger->debug('GPT response body', [
                'request_id' => $requestId,
                'body' => $body
            ]);

            // Handle different error scenarios
            if ($statusCode !== 200) {
                $this->handleHttpError($statusCode, $body, $requestId);
            }

            if (isset($body['error'])) {
                $this->handleOpenAIError($body['error'], $requestId);
            }

            if (!isset($body['choices']) || empty($body['choices'])) {
                throw new \RuntimeException('Invalid response format: no choices found');
            }

            $message = $body['choices'][0]['message'] ?? [];
            $message['usage'] = $body['usage'] ?? [];
            $message['finish_reason'] = $body['choices'][0]['finish_reason'] ?? null;

            $this->logger->info('GPT request completed successfully', [
                'request_id' => $requestId,
                'finish_reason' => $message['finish_reason'],
                'usage' => $body['usage'] ?? null
            ]);

            return new GptResponse($message);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('GPT transport error', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            throw new \RuntimeException('OpenAI API transport error: ' . $e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            $this->logger->error('GPT request failed', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            throw new \RuntimeException('OpenAI API error: ' . $e->getMessage(), 0, $e);
        }
    }

    private function handleHttpError(int $statusCode, array $body, string $requestId): void
    {
        $errorMessage = match ($statusCode) {
            400 => 'Bad request - invalid parameters',
            401 => 'Unauthorized - invalid API key',
            402 => 'Payment required - quota exceeded',
            403 => 'Forbidden - access denied',
            404 => 'Not found - endpoint does not exist',
            409 => 'Conflict - request conflicts with current state',
            429 => 'Rate limit exceeded - too many requests',
            500 => 'Internal server error - OpenAI service issue',
            502 => 'Bad gateway - OpenAI service temporarily unavailable',
            503 => 'Service unavailable - OpenAI service overloaded',
            504 => 'Gateway timeout - request timed out',
            default => "HTTP error {$statusCode}"
        };

        $this->logger->error('GPT HTTP error', [
            'request_id' => $requestId,
            'status_code' => $statusCode,
            'error_message' => $errorMessage,
            'body' => $body
        ]);

        throw new \RuntimeException("OpenAI API HTTP error {$statusCode}: {$errorMessage}");
    }

    private function handleOpenAIError(array $error, string $requestId): void
    {
        $errorType = $error['type'] ?? 'unknown';
        $errorMessage = $error['message'] ?? 'Unknown error';
        $errorCode = $error['code'] ?? null;

        $this->logger->error('OpenAI API error', [
            'request_id' => $requestId,
            'error_type' => $errorType,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'full_error' => $error
        ]);

        // Handle specific error types
        $specificMessage = match ($errorType) {
            'invalid_request_error' => "Invalid request: {$errorMessage}",
            'authentication_error' => "Authentication failed: {$errorMessage}",
            'rate_limit_error' => "Rate limit exceeded: {$errorMessage}",
            'quota_exceeded_error' => "Quota exceeded: {$errorMessage}",
            'billing_error' => "Billing issue: {$errorMessage}",
            'server_error' => "OpenAI server error: {$errorMessage}",
            default => "OpenAI API error: {$errorMessage}"
        };

        throw new \RuntimeException($specificMessage);
    }
}
