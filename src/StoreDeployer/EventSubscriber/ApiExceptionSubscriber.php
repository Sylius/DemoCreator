<?php

namespace App\StoreDeployer\EventSubscriber;

use App\StoreDeployer\Exception\DemoDeploymentException;
use App\StoreDeployer\Exception\InvalidDeployTargetException;
use App\StoreDesigner\Exception\InvalidSchemaDataException;
use Symfony\Component\ErrorHandler\Error\FatalError;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Process\Exception\ProcessFailedException;

final readonly class ApiExceptionSubscriber implements EventSubscriberInterface
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

        if ($exception instanceof DemoDeploymentException) {
            $event->setResponse(new JsonResponse([
                'error' => 'Demo deployment error',
                'details' => $exception->getMessage(),
            ], 500));

            return;
        }

        if ($exception instanceof ProcessFailedException) {
            $event->setResponse(new JsonResponse([
                'error' => 'Deployment process failed',
                'details' => $exception->getMessage(),
            ], 500));

            return;
        }

        if ($exception instanceof InvalidDeployTargetException) {

            $event->setResponse(new JsonResponse([
                'error'         => 'Invalid deploy target',
                'details'       => $exception->getMessage(),
            ], 500));

            return;
        }

        if ($exception instanceof FatalError && str_contains($exception->getMessage(), 'Maximum execution time')) {
            $event->setResponse(new JsonResponse([
                'error'   => 'Timeout',
                'details' => 'The operation took too long to complete. Please try again later.',
            ], 504));
        }
    }
}
