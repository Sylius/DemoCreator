<?php

declare(strict_types=1);

namespace App\StoreDesigner\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use App\StoreDesigner\Service\GptResponse;

final readonly class GptClient
{
    public function __construct(
        private HttpClientInterface $client,
        #[Autowire(env: 'OPENAI_API_KEY')] private string $openaiApiKey
    ) {
    }

    public function chatCompletions(array $messages, string $model, int $maxCompletionTokens = 1024, array $functions = []): GptResponse
    {
        try {
            $response = $this->client->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->openaiApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'messages' => $messages,
                    'functions' => $functions,
                    'function_call' => 'auto',
                    'max_completion_tokens' => $maxCompletionTokens,
                ],
            ]);
            $body = $response->toArray(false);
            if ($body['error'] ?? null) {
                throw new \RuntimeException('OpenAI API error: ' . $body['error']['message']);
            }

            $message = $body['choices'][0]['message'] ?? [];
            $message['usage'] = $body['usage'] ?? [];

            return new GptResponse($message);
        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException('OpenAI API transport error: ' . $e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            throw new \RuntimeException('OpenAI API error: ' . $e->getMessage(), 0, $e);
        }
    }
} 