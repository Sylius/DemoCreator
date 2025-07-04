<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    path: '/theme-creator/{section}/{locale}',
    name: 'app_theme_creator',
    requirements: ['section' => 'admin|shop'],
    methods: ['GET']
)]
final class ThemeCreatorController extends AbstractController
{
    public function __invoke(Request $request, string $section = 'admin'): Response
    {
        $variables = [
            '--tblr-primary'       => $request->query->get('primary', '#206bc4'),
            '--tblr-btn-bg'        => $request->query->get('btnBg', '#206bc4'),
            '--tblr-btn-hover-bg'  => $request->query->get('btnHover', '#206bc4'),
        ];

        // Forward the base URL for preview (port 8000)
        $previewBaseUrl = 'http://localhost:8000';

        return $this->render('theme_generate/index.html.twig', [
            'section'        => $section,
            'variables'      => $variables,
            'previewBaseUrl' => $previewBaseUrl,
        ]);
    }
}
