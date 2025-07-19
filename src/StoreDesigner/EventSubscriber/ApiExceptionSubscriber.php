<?php

namespace App\StoreDesigner\EventSubscriber;

use App\StoreDesigner\Exception\InvalidSchemaDataException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
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
        }
    }
} 