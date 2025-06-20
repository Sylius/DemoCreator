<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use GuzzleHttp\Exception\ClientException;

class GptChatController extends AbstractController
{
    #[Route('/api/gpt-chat', name: 'api_gpt_chat', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $messages = $data['messages'] ?? [];

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
            [
                'name' => 'collect_category',
                'description' => 'Zbiera dane jednej kategorii',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'code' => ['type' => 'string'],
                        'name' => ['type' => 'string'],
                        'slug' => ['type' => 'string'],
                        'parentCode' => ['type' => 'string'],
                    ],
                    'required' => ['code', 'name'],
                ],
            ],
            [
                'name' => 'collect_product',
                'description' => 'Zbiera dane pojedynczego produktu',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                        'description' => ['type' => 'string'],
                        'shortDescription' => ['type' => 'string'],
                        'imgPrompt' => ['type' => 'string'],
                        'taxCategory' => ['type' => 'string'],
                        'mainTaxon' => ['type' => 'string'],
                        'taxons' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'productAttributes' => ['type' => 'object', 'additionalProperties' => ['type' => 'string']],
                    ],
                    'required' => ['name', 'description', 'taxCategory', 'mainTaxon'],
                ],
            ],
        ];

        // Append final generation function with full fixtures schema
        $functions[] = [
            'name' => 'generate_fixtures',
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
                return new JsonResponse(['error' => 'No assistant message returned'], 500);
            }
            $message = $body['choices'][0]['message'];

            // Handle function call from assistant
            if (isset($message['function_call'])) {
                $func = $message['function_call'];
                $name = $func['name'];
                $args = json_decode($func['arguments'], true);

                // Execute the corresponding PHP function
                switch ($name) {
                    case 'collectStoreInfo':
                        $resultData = $this->collectStoreInfo($args);
                        break;
                    // add more cases as you define other functions
                    default:
                        $resultData = [];
                }

                // Append function call and its result to the conversation
                $messages[] = $message;
                $messages[] = [
                    'role' => 'function',
                    'name' => $name,
                    'content' => json_encode($resultData),
                ];

                // Call OpenAI again with the function result
                $response2 = $client->post('chat/completions', [
                    'json' => [
                        'model' => $model,
                        'messages' => $messages,
                        'functions' => $functions,
                        'function_call' => 'auto',
                    ],
                ]);
                $body2 = json_decode($response2->getBody()->getContents(), true);
                $message2 = $body2['choices'][0]['message'];
                return new JsonResponse(['choices' => [['message' => $message2]]]);
            }

            // No function call: return assistant message directly
            return new JsonResponse(['choices' => [['message' => $message]]]);
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
                'details' => $decoded
            ], $response ? $response->getStatusCode() : 500);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
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
}
