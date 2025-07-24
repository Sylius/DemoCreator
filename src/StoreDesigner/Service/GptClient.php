<?php

declare(strict_types=1);

namespace App\StoreDesigner\Service;

use App\StoreDesigner\Dto\ImageRequestDto;
use SensitiveParameter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Psr\Log\LoggerInterface;

final readonly class GptClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[SensitiveParameter] #[Autowire(env: 'OPENAI_API_KEY')] private string $openaiApiKey,
        private LoggerInterface $logger,
    ) {
    }

    public function chatCompletions(array $messages, string $model, int $maxCompletionTokens = 1024, array $functions = []): GptResponse
    {
        $requestId = uniqid('gpt_', true);
        try {
            $payload = [
                'model' => $model,
                'messages' => $messages,
                'functions' => $functions,
                'function_call' => 'auto',
                'max_completion_tokens' => $maxCompletionTokens,
            ];

            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->openaiApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
                'timeout' => 600,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->toArray(false);

            if ($statusCode !== 200) {
                $this->handleHttpError($statusCode);
            }

            if (empty($body['choices'])) {
                throw new \RuntimeException('Invalid response format: no choices found');
            }

            $message = $body['choices'][0]['message'] ?? [];
            $message['usage'] = $body['usage'] ?? [];
            $message['finish_reason'] = $body['choices'][0]['finish_reason'] ?? null;

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

    private function handleHttpError(int $statusCode): void
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
            default => "HTTP error $statusCode"
        };

        throw new \RuntimeException("OpenAI API HTTP error $statusCode: $errorMessage");
    }

    public function generateImage(ImageRequestDto $imageRequestDto): string
    {
        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/images/generations', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $imageRequestDto->model,
                'prompt' => $imageRequestDto->prompt,
                'n' => $imageRequestDto->n,
                'size' => $imageRequestDto->imageResolution,
                'quality' => $imageRequestDto->imageQuality,
            ],
        ]);
        $statusCode = $response->getStatusCode();
        $raw = $response->getContent(false);
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new \RuntimeException('OpenAI API error: Invalid JSON response: ' . $raw);
        }
        if ($statusCode !== 200) {
            throw new \RuntimeException('OpenAI API error: HTTP ' . $statusCode . ' - ' . ($data['error']['message'] ?? $raw));
        }
        $b64 = $data['data'][0]['b64_json'] ?? null;
        if (!$b64) {
            throw new \RuntimeException('OpenAI API error: No image data returned');
        }
        $binary = base64_decode($b64);
        if ($binary === false) {
            throw new \RuntimeException('OpenAI API error: Invalid base64 image data');
        }

        return $binary;
    }
}
