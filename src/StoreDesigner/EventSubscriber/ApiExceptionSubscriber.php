<?php

namespace App\StoreDesigner\EventSubscriber;

use App\StoreDesigner\Exception\InvalidSchemaDataException;
use App\StoreDesigner\Exception\OpenAiApiException;
use App\StoreDesigner\Exception\StoreDefinitionNotFoundException;
use App\StoreDesigner\Exception\StorePresetNotFoundException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof InvalidSchemaDataException) {
            $event->setResponse(new JsonResponse([
                'error' => 'Invalid data',
                'details' => $exception->getMessage(),
            ], 400));

            return;
        }

        if ($exception instanceof \JsonException) {
            $event->setResponse(new JsonResponse([
                'error' => 'JSON error',
                'details' => $exception->getMessage(),
            ], 400));
        }

        if ($exception instanceof StorePresetNotFoundException) {
            $event->setResponse(new JsonResponse([
                'error' => 'Store preset not found',
                'details' => $exception->getMessage(),
            ], 404));

            return;
        }

        if ($exception instanceof StoreDefinitionNotFoundException) {
            $event->setResponse(new JsonResponse([
                'error' => 'Store definition not found',
                'details' => $exception->getMessage(),
            ], 404));

            return;
        }

        if ($exception instanceof OpenAiApiException) {
            $event->setResponse(new JsonResponse([
                'error' => 'OpenAI API error',
                'details' => $exception->getMessage(),
            ], 500));

            return;
        }

        if ($exception instanceof IOException) {
            $event->setResponse(new JsonResponse([
                'error' => 'File system error',
                'details' => $exception->getMessage(),
            ], 500));

            return;
        }
    }
}
