<?php

declare(strict_types=1);

namespace App\Controller;

use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use GuzzleHttp\Exception\ClientException;

class GptChatController extends AbstractController
{
    private Client $client;

    public function __construct()
    {
        $this->setupClient();
    }

    #[Route('/api/gpt-chat', name: 'api_gpt_chat', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $storeInfo = $data['storeInfo'] ?? [];
        $conversationId = $data['conversation_id'] ?? null;
        if (!$conversationId) {
            $conversationId = bin2hex(random_bytes(16));
        }

        $inputMessages = $data['messages'] ?? [];
        // Prepend system message once for new conversations
        if (empty($inputMessages) || ($inputMessages[0]['role'] ?? '') !== 'system') {
            array_unshift($inputMessages, $this->getSystemMessage());
        }
        $messages = $inputMessages;


        // Confirmation is handled by the model itself via function_call: 'auto'

        // Choose model: mini for interview, full for final generation
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
                        // Append the function call and its result to the conversation
                        $messages[] = $message;
                        $messages[] = [
                            'role'    => 'function',
                            'name'    => $name,
                            'content' => json_encode($resultData),
                        ];
                        if (
                            isset($args['industry'], $args['locales'], $args['currencies'], $args['countries'],
                                $args['productsPerCat'], $args['descriptionStyle'], $args['imageStyle'])
                        ) {
                            $dataCompleted = true;
                        }
                        break;
                    case 'generateFixtures':
                        // Arguments are already decoded to array
                        $resultData = $args;
                        $filesystem = new Filesystem();
                        $industry = $storeInfo['industry'] ?? 'default';
                        $fixturesPath = $this->getParameter('kernel.project_dir') . '/fixtures/' . $industry . '_' . date('Ymd_His') . '.json';
                        if (!$filesystem->exists(dirname($fixturesPath))) {
                            $filesystem->mkdir(dirname($fixturesPath));
                        }
                        $filesystem->dumpFile($fixturesPath, json_encode($resultData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                        $messages[] = $message;
                        $messages[] = [
                            'role' => 'function',
                            'name' => $name,
                            'content' => json_encode($resultData),
                        ];
                        return new JsonResponse([
                            'conversation_id' => $conversationId,
                            'dataCompleted' => true,
                            'storeInfo' => $storeInfo,
                            'fixtures' => $resultData,
                            'messages' => $messages,
                        ]);
                    // add additional cases as needed
                    default:
                        $resultData = [];
                }
                // Continue loop to process next function call
            } else {
                // Append the final assistant message before exiting the loop
                $messages[] = $message;
                break;
            }
        } while (true);
        return new JsonResponse([
            'conversation_id' => $conversationId,
            'dataCompleted' => $dataCompleted ?? false,
            'storeInfo' => $storeInfo,
            'messages' => $messages,
        ]);
    }


    private function collectStoreInfo(array $data): array
    {
        // Fill defaults where missing
        $data['locales'] = $data['locales'] ?? ['pl_PL'];
        $data['currencies'] = $data['currencies'] ?? ['PLN'];
        $data['countries'] = $data['countries'] ?? [$data['locales'][0] === 'en_US' ? 'US' : 'PL'];
        $data['categories'] = $data['categories'] ?? [];
        $data['productsPerCat'] = $data['productsPerCat'] ?? 10;
        $data['descriptionStyle'] = $data['descriptionStyle'] ?? '';
        $data['imageStyle'] = $data['imageStyle'] ?? '';
        // Default to a single WORLD zone if none provided
        $data['zones'] = $data['zones'] ?? ['WORLD' => ['name' => 'WORLD', 'countries' => $data['countries']]];
        // Here you could save $data to session or database if needed
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

    private function getCollectStoreDataFunction(): array
    {
        return
            [
                'name' => 'collectStoreInfo',
                'description' => 'Zbiera podstawowe dane sklepu',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'industry' => ['type' => 'string'],
                        'locales' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'currencies' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'countries' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'zones' => [
                            'type' => 'object',
                            'additionalProperties' => [
                                'type' => 'object',
                                'properties' => [
                                    'name' => ['type' => 'string'],
                                    'countries' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string'],
                                    ],
                                ],
                                'required' => ['name', 'countries'],
                                'additionalProperties' => false,
                            ],
                        ],
                        'categories' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'code' => ['type' => 'string'],
                                    'name' => ['type' => 'string'],
                                    'slug' => ['type' => 'string'],
                                    'translations' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'string',
                                            ]
                                        ],
                                    ],
                                ],
                                'required' => ['code', 'name'],
                            ],
                        ],
                        'productsPerCat' => ['type' => 'integer', 'minimum' => 1],
                        'descriptionStyle' => ['type' => 'string'],
                        'imageStyle' => ['type' => 'string'],
                    ],
                    'required' => [],
                ],
            ];
    }

    private function getGenerateFixturesFunction(): array
    {
        $schemaPath = __DIR__ . '/../../config/core.json';

        if (!file_exists($schemaPath)) {
            throw new \RuntimeException('Fixtures schema file not found: ' . $schemaPath);
        }
        $fixturesSchema = json_decode(file_get_contents($schemaPath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON in fixtures schema file: ' . json_last_error_msg());
        }

        return [
            'name' => 'generateFixtures',
            'description' => 'It generates the final json fixtures based on the collectStoreInfo retrieved data',
            'parameters' => $fixturesSchema,
        ];
    }

    private function getSystemMessage(): array
    {
        $content = <<<'SYS'
You are an AI assistant that helps create complete Sylius store fixtures in JSON format.
• If any of the following details have not yet been provided by the user, ask EXACTLY ONE polite, consolidated question to collect them:
  – Industry or product type (e.g., furniture, books, clothing, electronics)
  – Store locales (convert natural language to locale codes, e.g. “Polish” → pl_PL), but don't use technical terms like "locale"
  – Currencies (convert to ISO codes, e.g. “złotówki” → PLN)
  – Countries (convert to ISO 3166-1 alpha-2 codes and full names), don't use technical terms like pl_PL
  - Categories (provide a list of categories with codes, names, and slugs; if user doesn't specify, use 5 categories based on industry)
  – Number of products (total or per category; default 5 per category if omitted)
  – Description style and image style preferences (if relevant)
• If user let you decide, choose locale and currency based on the language he speaks, for example:
  – For Polish, use pl_PL and PLN.
  – For English, use en_US and USD.
• Ask only one combined question; once answered, proceed directly to gathering the next missing detail.
• Do NOT suggest exporting or generating the final fixtures file until all required details have been collected.
• Once all information is gathered, present a concise summary of the store configuration and ask the user if they would like to make any final changes before proceeding to generation.
• Don't use technical terms like JSON schema, fixtures, or export.

Whenever you receive new relevant information from the user, call the function `collectStoreInfo` with that data to update the current `storeInfo`. Continue this step-by-step process until all fields in the JSON schema are fully populated: industry, locales, currencies, countries, categories, productsPerCat, descriptionStyle, imageStyle.
Other technical details:
- when generating fixtures, ensure each product includes a `product_attributes` object containing key/value string pairs (e.g., `"material": "Steel"`, `"weight_kg": "18"`), even if empty.
- suiteName - name of the store without spaces (snake/lower-case); if not provided by the user, create it based on the industry.
- zones - if the user does not define zones, create a single "WORLD" zone that includes all countries.
- products - ensure each product has a realistic name according to the category, e.g. "Brass earrings" for earrings category, "Wooden chair" for chairs, etc. Use the `descriptionStyle` to generate a product description and `imageStyle` to generate an image URL.
- When generating the products array, use the productsPerCat integer to create exactly that many products per category.
Once `storeInfo` is complete, present a concise summary of the store configuration and ask the user if they would like any final changes. Ask the user to write a certain confirmation word. Only after the user confirms, call the function `generateFixtures` to generate the final JSON fixtures.
SYS;

        return [
            'role' => 'system',
            'content' => $content,
        ];
    }
}
