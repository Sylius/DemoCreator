<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GptChatPageController extends AbstractController
{
    #[Route('/gpt-chat', name: 'gpt_chat_page', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('gpt_chat/index.html.twig');
    }
} 