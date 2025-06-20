<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

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
                'name' => 'collect_store_info',
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
            return new JsonResponse(['choices' => [['message' => $message]]]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    // ... mapDoctrineTypeToJson if still needed elsewhere ...
}
