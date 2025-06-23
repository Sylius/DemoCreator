<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class GptChatController extends AbstractController
{
    #[Route('/api/gpt-chat', name: 'api_gpt_chat', methods: ['POST'])]
    public function chat(Request $request, SessionInterface $session): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        // Conversation token for client-side tracking
        $conversationId = $data['conversation_id'] ?? null;
        if (!$conversationId) {
            $conversationId = bin2hex(random_bytes(16));
        }
        // Persist conversation ID in session for later retrieval
        $session->set('conversation_id', $conversationId);

        // Merge incoming messages with any stored conversation, or prepend system prompt if new
        $inputMessages = $data['messages'] ?? [];
        $storedMessages = $session->get('messages_' . $conversationId, []);
        if (is_array($storedMessages) && count($storedMessages) > 0) {
            // Continue existing conversation
            $messages = array_merge($storedMessages, $inputMessages);
        } else {
            // New conversation: initialize with system instruction
            $systemMessage = [
                'role' => 'system',
                'content' => <<<'SYS'
You are an AI assistant that helps create complete Sylius store fixtures in JSON format. INFORMATION GATHERING
• If any of the following details have not yet been provided by the user, ask EXACTLY ONE polite, consolidated question to collect them:
  – Industry or product type (e.g., furniture, books, clothing, electronics)
  – Store locales (convert natural language to locale codes, e.g. “Polish” → pl_PL)
  – Currencies (convert to ISO codes, e.g. “złotówki” → PLN)
  – Countries (convert to ISO 3166-1 alpha-2 codes and full names)
  – Number of products (total or per category; default ≈10 per category if omitted)
  – Description style and image style preferences (if relevant)
• Default to pl_PL if locales are omitted.
• Default currency by primary locale (pl_PL → PLN, en_US → USD, etc.) if omitted.
• Default to the country matching the primary locale if omitted.
• Ask only one combined question; once answered, proceed directly to gathering the next missing detail.
• Do NOT suggest exporting or generating the final fixtures file until all required details have been collected.
• Once all information is gathered, present a concise summary of the store configuration (locales, currency, countries, categories, number of products, description and image styles) and ask the user if they would like to make any final changes before proceeding to JSON generation.
SYS
            ];
            $messages = array_merge([$systemMessage], $inputMessages);
        }

        // Load JSON schema for final fixtures
        $schemaPath = __DIR__ . '/../../config/core.json';
        $fixturesSchema = [];
        if (file_exists($schemaPath)) {
            $fixturesSchema = json_decode(file_get_contents($schemaPath), true);
        }

        $apiKey = $_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY');
        if (!$apiKey) {
            return new JsonResponse(['error' => 'OpenAI API key not set'], 500);
        }

        // Define function specs
        $functions = [
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
                        'productsPerCat' => ['type' => 'integer', 'minimum' => 1],
                        'descriptionStyle' => ['type' => 'string'],
                        'imageStyle' => ['type' => 'string'],
                    ],
                    'required' => ['industry'],
                ],
            ],
        ];

        // Append final generation function with full fixtures schema
        $functions[] = [
            'name' => 'generateFixtures',
            'description' => 'Generuje finalny JSON fixtures zgodnie z core.json',
            'parameters' => $fixturesSchema ?: [
                'type' => 'object',
                'properties' => [],
            ],
        ];

        // Choose model: mini for interview, full for final generation
        $last = end($messages);
        $model = 'gpt-4.1-mini';
        if (isset($last['function_call']['name']) && $last['function_call']['name'] === 'generate_fixtures') {
            $model = 'gpt-4o-2024-08-06';
        }

        // Call OpenAI
        $client = new \GuzzleHttp\Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);

        try {
            do {
                $response = $client->post('chat/completions', [
                    'json' => [
                        'model' => $model,
                        'messages' => $messages,
                        'functions' => $functions,
                        'function_call' => 'auto',
                    ],
                ]);
                $body = json_decode($response->getBody()->getContents(), true);
                if (empty($body['choices'][0]['message'])) {
                    throw new \RuntimeException('No assistant message returned');
                }
                $message = $body['choices'][0]['message'];
                if (isset($message['function_call'])) {
                    $func = $message['function_call'];
                    $name = $func['name'];
                    $args = json_decode($func['arguments'], true);
                    switch ($name) {
                        case 'collectStoreInfo':
                            $resultData = $this->collectStoreInfo($args);
                            $session->set('store_info_' . $conversationId, $resultData);
                            if (
                                isset($args['industry'], $args['locales'], $args['currencies'], $args['countries'],
                                      $args['productsPerCat'], $args['descriptionStyle'], $args['imageStyle'])
                            ) {
                                $dataCompleted = true;
                            }
                            break;
                        case 'generateFixtures':
                            $resultData = $this->generateFixtures($args);
                            $session->set('messages_' . $conversationId, $messages);
                            // Ensure conversation ID is stored
                            $session->set('conversation_id', $conversationId);
                            // Immediately return validated fixtures JSON
                            return new JsonResponse([
                                'conversation_id' => $conversationId,
                                'dataCompleted' => true,
                                'storeInfo' => $session->get('store_info_' . $conversationId),
                                'fixtures' => $resultData,
                            ]);
                        // add additional cases as needed
                        default:
                            $resultData = [];
                    }
                    $messages[] = $message;
                    $messages[] = [
                        'role' => 'function',
                        'name' => $name,
                        'content' => json_encode($resultData),
                    ];
                    // Continue loop to process next function call
                } else {
                    // Append the final assistant message before exiting the loop
                    $messages[] = $message;
                    break;
                }
            } while (true);
            // Ensure conversation ID is stored for subsequent requests
            $session->set('conversation_id', $conversationId);
            $session->set('messages_' . $conversationId, $messages);
            return new JsonResponse([
                'conversation_id' => $conversationId,
                'dataCompleted' => $dataCompleted ?? false,
                'storeInfo' => $session->get('store_info_' . $conversationId),
                'messages' => $messages,
            ]);
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $rawBody = $response ? $response->getBody()->getContents() : $e->getMessage();
            if ($response) {
                $decoded = json_decode($rawBody, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $decoded = $rawBody;
                }
            } else {
                $decoded = $rawBody;
            }
            return new JsonResponse([
                'error' => 'OpenAI API returned an error',
                'details' => $decoded,
                'conversation_id' => $conversationId,
                'messages' => $messages,
            ], $response ? $response->getStatusCode() : 500);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
                'conversation_id' => $conversationId,
                'messages' => $messages,
            ], 500);
        }
    }

    #[Route('/api/gpt-chat/state', name: 'api_gpt_chat_state', methods: ['GET'])]
    public function state(Request $request, SessionInterface $session): JsonResponse
    {
        // Retrieve or generate conversation ID for new session
        $conversationId = $request->query->get('conversation_id') ?? $session->get('conversation_id');
        if (!$conversationId) {
            $conversationId = bin2hex(random_bytes(16));
        }
        // Persist conversation ID in session
        $session->set('conversation_id', $conversationId);

        $info = $session->get('store_info_' . $conversationId, null);
        $dataCompleted = $info !== null
            && isset(
                $info['industry'],
                $info['locales'],
                $info['currencies'],
                $info['countries'],
                $info['productsPerCat'],
                $info['descriptionStyle'],
                $info['imageStyle']
            );
        // Load existing messages or initialize with system prompt
        $messages = $session->get('messages_' . $conversationId, []);
        $systemMessage = [
            'role' => 'system',
            'content' => <<<'SYS'
You are an AI assistant that helps create complete Sylius store fixtures in JSON format. INFORMATION GATHERING
• If any of the following details have not yet been provided by the user, ask EXACTLY ONE polite, consolidated question to collect them:
  – Industry or product type (e.g., furniture, books, clothing, electronics)
  – Store locales (convert natural language to locale codes, e.g. “Polish” → pl_PL)
  – Currencies (convert to ISO codes, e.g. “złotówki” → PLN)
  – Countries (convert to ISO 3166-1 alpha-2 codes and full names)
  – Number of products (total or per category; default ≈10 per category if omitted)
  – Description style and image style preferences (if relevant)
• Default to pl_PL if locales are omitted.
• Default currency by primary locale (pl_PL → PLN, en_US → USD, etc.) if omitted.
• Default to the country matching the primary locale if omitted.
• Ask only one combined question; once answered, proceed directly to gathering the next missing detail.
• Do NOT suggest exporting or generating the final fixtures file until all required details have been collected.
• Once all information is gathered, present a concise summary of the store configuration (locales, currency, countries, categories, number of products, description and image styles) and ask the user if they would like to make any final changes before proceeding to JSON generation.
SYS
        ];
        if (empty($messages)) {
            $messages = [$systemMessage];
        }
        return new JsonResponse([
            'conversation_id' => $conversationId,
            'dataCompleted' => $dataCompleted,
            'storeInfo' => $info,
            'messages' => $messages,
        ]);
    }

    private function collectStoreInfo(array $data): array
    {
        // Fill defaults where missing
        $data['locales'] = $data['locales'] ?? ['pl_PL'];
        $data['currencies'] = $data['currencies'] ?? ['PLN'];
        $data['countries'] = $data['countries'] ?? [$data['locales'][0] === 'en_US' ? 'US' : 'PL'];
        $data['productsPerCat'] = $data['productsPerCat'] ?? 10;
        $data['descriptionStyle'] = $data['descriptionStyle'] ?? '';
        $data['imageStyle'] = $data['imageStyle'] ?? '';
        // Here you could save $data to session or database if needed
        return $data;
    }

    /**
     * Generate final fixtures array based on provided schema args.
     */
    private function generateFixtures(array $data): array
    {
        // Load and parse schema
        $schemaPath = __DIR__ . '/../../config/core.json';
        if (!file_exists($schemaPath)) {
            throw new \RuntimeException("Schema file not found at {$schemaPath}");
        }
        $schema = json_decode(file_get_contents($schemaPath));
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON schema: " . json_last_error_msg());
        }

        // Build fixtures payload using provided data or sensible defaults
        $result = [
            'suiteName' => $data['suiteName'] ?? 'Sylius Store Fixtures',
            'locales' => $data['locales'] ?? ['pl_PL'],
            'currencies' => $data['currencies'] ?? ['PLN'],
            'countries' => $data['countries'] ?? ['PL'],
            'zones' => $data['zones'] ?? [
                'WORLD' => [
                    'name' => 'World Zone',
                    'countries' => $data['countries'] ?? ['PL'],
                ],
            ],
            'menuTaxon' => $data['menuTaxon'] ?? [
                'code' => 'menu',
                'name' => $data['ind'] ?? [],
            ],
            'channel' => $data['channel'] ?? [
                'code' => 'default',
                'name' => 'Default Channel',
                'locales' => $data['locales'] ?? ['pl_PL'],
                'currencies' => $data['currencies'] ?? ['PLN'],
                'hostname' => 'localhost',
                'theme_name' => 'default',
            ],
            'categories' => $data['categories'] ?? [],
            'paymentMethods' => $data['paymentMethods'] ?? [],
            'shippingMethods' => $data['shippingMethods'] ?? [],
            'taxCategories' => $data['taxCategories'] ?? [],
            'taxons' => $data['taxons'] ?? [],
            'taxRates' => $data['taxRates'] ?? [],
            'products' => $data['products'] ?? [],
        ];
        return $result;
    }
}
