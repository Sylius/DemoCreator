<?php

namespace App\Controller;

use OpenAI\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ThemeGenerateController extends AbstractController
{
    #[Route('/api/theme/generate', methods:['POST'])]
    public function generate(Request $request, Client $client): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $prompt = $data['prompt'] ?? '';
        $base  = $data['baseVars'] ?? [];

        $response = $client->chat()->create([
            'model'=>'gpt-4',
            'messages'=>[
                ['role'=>'system','content'=>'You are a CSS variable assistant.'],
                ['role'=>'user','content'=>"Suggest JSON of CSS variables based on this theme: {$prompt}. Base: ".json_encode($base)]
            ]
        ]);

        $text = $response->choices[0]->message->content;
        $vars = json_decode($text, true);

        return new JsonResponse(['suggestedVars'=>$vars]);
    }

}
