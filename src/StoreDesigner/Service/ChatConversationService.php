<?php

declare(strict_types=1);

namespace App\StoreDesigner\Service;

use App\StoreDesigner\Dto\ChatRequestDto;
use App\StoreDesigner\Dto\ChatResponseDto;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\HttpKernel\KernelInterface;

final class ChatConversationService
{
    private Client $client;
    private KernelInterface $kernel;

    public function __construct(
        KernelInterface $kernel
    ) {
        $this->kernel = $kernel;
        $this->setupClient();
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

        do {
            $message = $this->askGPT(
                $messages,
                $model,
                $maxCompletionTokens ?? 1024
            );
            if (isset($message['function_call'])) {
                $func = $message['function_call'];
                $name = $func['name'];
                $args = json_decode($func['arguments'], true);
                switch ($name) {
                    case 'collectStoreInfo':
                        $resultData = $this->collectStoreInfo($args);
                        $storeInfo = $resultData;
                        $messages[] = $message;
                        $messages[] = [
                            'role'    => 'function',
                            'name'    => $name,
                            'content' => json_encode($resultData),
                        ];
                        $dataCompleted = isset($args['industry'], $args['locales'], $args['currencies'], $args['countries'], $args['productsPerCat'], $args['descriptionStyle'], $args['imageStyle']);
                        break;
                    case 'generateFixtures':
                        $resultData = $args;
                        $messages[] = $message;
                        $messages[] = [
                            'role' => 'function',
                            'name' => $name,
                            'content' => json_encode($resultData),
                        ];
                        return new ChatResponseDto(
                            conversationId: $conversationId,
                            dataCompleted: true,
                            storeInfo: $storeInfo,
                            fixtures: $resultData,
                            messages: $messages,
                        );
                    default:
                        $resultData = [];
                }
            } else {
                $messages[] = $message;
                break;
            }
        } while (true);

        return new ChatResponseDto(
            conversationId: $conversationId,
            dataCompleted: $dataCompleted ?? false,
            storeInfo: $storeInfo,
            fixtures: null,
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
        $apiKey = $_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY');
        if (!$apiKey) {
            throw new \Exception('OpenAI API key is not set. Please set the OPENAI_API_KEY environment variable.');
        }

        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    private function askGPT(array $messages, string $model, int $maxCompletionTokens = 1024): array
    {
        try {
            $response = $this->client->post('chat/completions', [
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
            $body = json_decode($response->getBody()->getContents(), true);
            $usage = $body['usage'] ?? [];
            $message = $body['choices'][0]['message'] ?? [];
            $message['usage'] = $usage;
            return $message;
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $body = $response ? $response->getBody()->getContents() : $e->getMessage();
            throw new \RuntimeException('OpenAI API returned an error: ' . $body, $response ? $response->getStatusCode() : 0, $e);
        }
    }

    private function getSystemInstructions(): string
    {
        $path = $this->kernel->getProjectDir() . '/config/gpt/system_instructions.txt';
        if (!file_exists($path)) {
            throw new \RuntimeException('System instructions JSON not found: ' . $path);
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
        return ['role' => 'system', 'content' => $instructions['system'] ?? ''];
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
