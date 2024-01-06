<?php

namespace App\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Verify if in API context
        if (str_starts_with($path, "/api")) {
            $status = 500; // return 500 by default when it's not an HTTP exception
            if ($exception instanceof HttpException) {
                $status = $exception->getStatusCode();
            }
            $event->setResponse(new JsonResponse([
                'status' => $status,
                'message' => $exception->getMessage()
            ]));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }
}
