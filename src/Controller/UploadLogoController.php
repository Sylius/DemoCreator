<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Filesystem\Filesystem;

#[Route('/upload-logo', name: 'demo_upload_logo', methods: ['POST'])]
final class UploadLogoController extends AbstractController
{
    public function __invoke(Request $request): JsonResponse
    {
        $file = $request->files->get('logo');
        if (!$file) {
            return $this->json(['error' => 'Brak pliku logo'], 400);
        }

        $uploadsDir = $this->getParameter('kernel.project_dir').'/public/uploads';
        (new Filesystem())->mkdir($uploadsDir);

        $filename = uniqid('logo_').'.'.$file->guessExtension();
        $file->move($uploadsDir, $filename);

        $url = '/uploads/'.$filename;
        return $this->json(['logoUrl' => $url]);
    }
}
