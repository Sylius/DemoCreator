<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Process\Process;

#[Route('/create-demo', name: 'demo_create', methods: ['POST'])]
final class CreateDemoController extends AbstractController
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['slug']) || !is_string($data['slug'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Missing or invalid "slug"'
            ], 400);
        }
        $slug = $data['slug'];

        if (empty($data['plugins']) || !is_array($data['plugins'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Missing or invalid "plugins" array'
            ], 400);
        }
        $plugins = $data['plugins'];

        $tmpFile = sys_get_temp_dir() . '/booster_' . uniqid('', true) . '.json';
        $boosterContent = ['plugins' => $plugins];
        file_put_contents(
            $tmpFile,
            json_encode($boosterContent, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
        );

        $console = $this->getParameter('kernel.project_dir') . '/bin/console';
        $process = Process::fromShellCommandline(sprintf(
            'php %s demo:create %s %s',
            escapeshellarg($console),
            escapeshellarg($slug),
            escapeshellarg($tmpFile)
        ));
        $process->run();

        if (!$process->isSuccessful()) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $process->getErrorOutput(),
            ], 500);
        }

        $result = json_decode($process->getOutput(), true);
        if (null === $result) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid JSON from demo:create command',
            ], 500);
        }

        return new JsonResponse($result);
    }
}
