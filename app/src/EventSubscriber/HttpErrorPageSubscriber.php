<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Twig\Environment;

final class HttpErrorPageSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly Environment $twig)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['renderSafeErrorPage', 100]];
    }

    public function renderSafeErrorPage(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $exception = $event->getThrowable();
        $statusCode = null;
        if ($exception instanceof AccessDeniedException) {
            $statusCode = Response::HTTP_FORBIDDEN;
        } elseif ($exception instanceof HttpExceptionInterface && in_array($exception->getStatusCode(), [Response::HTTP_NOT_FOUND, Response::HTTP_FORBIDDEN], true)) {
            $statusCode = $exception->getStatusCode();
        }

        if ($statusCode === null) {
            return;
        }

        $template = $statusCode === Response::HTTP_NOT_FOUND
            ? 'bundles/TwigBundle/Exception/error404.html.twig'
            : 'bundles/TwigBundle/Exception/error403.html.twig';

        $event->setResponse(new Response($this->twig->render($template), $statusCode));
    }
}