<?php

declare(strict_types=1);

namespace App\StoreDesigner\Service;

use App\StoreDesigner\Dto\ChatRequestDto;
use App\StoreDesigner\Dto\ChatResponseDto;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class ChatConversationService
{
    private HttpClientInterface $client;
    private KernelInterface $kernel;

    public function __construct(
        KernelInterface $kernel,
        HttpClientInterface $client
    ) {
        $this->kernel = $kernel;
        $this->client = $client;
    }

    public function processConversation(ChatRequestDto $data): ChatResponseDto
    {
        $storeInfo = $data['storeInfo'] ?? [];
        $conversationId = $data['conversationId'] ?? bin2hex(random_bytes(16));
        $inputMessages = $data['messages'] ?? [];

        if (empty($inputMessages) || ($inputMessages[0]['role'] ?? '') !== 'system') {
            array_unshift($inputMessages, $this->getSystemMessage());
        }
        $messages = $inputMessages;

        $last = end($messages);
        $model = 'gpt-4.1-mini';
        if (isset($last['function_call']['name']) && $last['function_call']['name'] === 'generateFixtures') {
            $model = 'gpt-4o-2024-08-06';
            $maxCompletionTokens = 8192;
        }

        $message = $this->askGPT(
            $messages,
            $model,
            $maxCompletionTokens ?? 1024
        );
        $messages[] = $message;

        $dataCompleted = false;
        $fixtures = null;
        if (isset($message['function_call'])) {
            $func = $message['function_call'];
            $name = $func['name'];
            $args = json_decode($func['arguments'], true);
            switch ($name) {
                case 'collectStoreInfo':
                    $storeInfo = $this->collectStoreInfo($args);
                    $dataCompleted = isset($args['industry'], $args['locales'], $args['currencies'], $args['countries'], $args['productsPerCat'], $args['descriptionStyle'], $args['imageStyle']);
                    break;
                case 'generateFixtures':
                    $fixtures = $args;
                    $dataCompleted = true;
                    break;
            }
        }

        return new ChatResponseDto(
            conversationId: $conversationId,
            dataCompleted: $dataCompleted,
            storeInfo: $storeInfo,
            fixtures: $fixtures,
            messages: $messages,
        );
    }

    private function collectStoreInfo(array $data): array
    {
        $data['locales'] = $data['locales'] ?? ['pl_PL'];
        $data['currencies'] = $data['currencies'] ?? ['PLN'];
        $data['countries'] = $data['countries'] ?? [$data['locales'][0] === 'en_US' ? 'US' : 'PL'];
        $data['categories'] = $data['categories'] ?? [];
        $data['productsPerCat'] = $data['productsPerCat'] ?? 10;
        $data['descriptionStyle'] = $data['descriptionStyle'] ?? '';
        $data['imageStyle'] = $data['imageStyle'] ?? '';
        $data['zones'] = $data['zones'] ?? ['WORLD' => ['name' => 'WORLD', 'countries' => $data['countries']]];
        return $data;
    }

    private function setupClient(): void
    {
        // NIEUŻYWANE - klient przekazywany przez DI
    }

    private function askGPT(array $messages, string $model, int $maxCompletionTokens = 1024): array
    {
        $apiKey = $_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY');
        if (!$apiKey) {
            throw new \Exception('OpenAI API key is not set. Please set the OPENAI_API_KEY environment variable.');
        }
        try {
            $response = $this->client->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'messages' => $messages,
                    'functions' => array_merge([
                        $this->getCollectStoreDataFunction(),
                        $this->getGenerateFixturesFunction(),
                    ]),
                    'function_call' => 'auto',
                    'max_completion_tokens' => $maxCompletionTokens,
                ],
            ]);
            $body = $response->toArray(false);
            $usage = $body['usage'] ?? [];
            $message = $body['choices'][0]['message'] ?? [];
            $message['usage'] = $usage;
            return $message;
        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException('OpenAI API transport error: ' . $e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            throw new \RuntimeException('OpenAI API error: ' . $e->getMessage(), 0, $e);
        }
    }

    private function getSystemInstructions(): string
    {
        $path = $this->kernel->getProjectDir() . '/config/gpt/system_instructions.md';
        if (!file_exists($path)) {
            throw new \RuntimeException('System instructions file not found: ' . $path);
        }
        $data = file_get_contents($path);
        if ($data === false) {
            throw new \RuntimeException('Failed to read system instructions file: ' . $path);
        }
        return $data;
    }

    private function getSystemMessage(): array
    {
        $instructions = $this->getSystemInstructions();
        return [
            'role' => 'system',
            'content' => $instructions,
        ];
    }

    private function getCollectStoreDataFunction(): array
    {
        $functions = $this->getSystemInstructions()['functions'] ?? [];
        return $functions['collectStoreInfo'] ?? [
            'name' => 'collectStoreInfo',
            'description' => 'Collects basic store data.',
            'parameters' => [],
        ];
    }

    private function getGenerateFixturesFunction(): array
    {
        $functions = $this->getSystemInstructions()['functions'] ?? [];
        return $functions['generateFixtures'] ?? [
            'name' => 'generateFixtures',
            'description' => 'Generates fixture JSON based on store data.',
            'parameters' => [],
        ];
    }
}
