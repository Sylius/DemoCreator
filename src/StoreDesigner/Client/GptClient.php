<?php

declare(strict_types=1);

namespace App\StoreDesigner\Client;

use App\StoreDesigner\Exception\OpenAiApiException;
use SensitiveParameter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class GptClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[SensitiveParameter] #[Autowire(env: 'OPENAI_API_KEY')] private string $openaiApiKey,
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
                throw new OpenAiApiException(
                    sprintf(
                        'OpenAI API error: HTTP %d - %s',
                        $statusCode,
                        $body['error']['message'] ?? 'Unknown error'
                    )
                );
            }

            if (empty($body['choices'])) {
                throw new \RuntimeException('Invalid response format: no choices found');
            }

            $message = $body['choices'][0]['message'] ?? [];
            $message['usage'] = $body['usage'] ?? [];
            $message['finish_reason'] = $body['choices'][0]['finish_reason'] ?? null;

            return new GptResponse($message);
        } catch (TransportExceptionInterface $e) {
            throw new OpenAiApiException(sprintf(
                'OpenAI API request failed: %s (code %d)',
                $e->getMessage(),
                $e->getCode()
            ), 0, $e);
        } catch (\Exception $e) {
            throw new OpenAiApiException(sprintf(
                'OpenAI API request failed: %s (code %d)',
                $e->getMessage(),
                $e->getCode()
            ), 0, $e);
        }
    }
}
