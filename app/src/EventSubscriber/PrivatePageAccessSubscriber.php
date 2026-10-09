<?php

namespace App\EventSubscriber;

use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PrivatePageAccessSubscriber implements EventSubscriberInterface
{
    private const PUBLIC_ROUTES = ['app_login'];

    public function __construct(
        private readonly Connection $connection,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['checkAccess', 0],
            KernelEvents::RESPONSE => ['preventPrivatePageCaching', 0],
        ];
    }

    public function checkAccess(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        if (!is_string($route) || in_array($route, self::PUBLIC_ROUTES, true)) {
            return;
        }

        $request->attributes->set('_private_page', true);
        $userId = $request->hasSession() ? $request->getSession()->get('user_id') : null;
        $user = is_int($userId) && $userId > 0
            ? $this->connection->fetchAssociative(
                "SELECT u.id, u.company_id, u.first_name, u.last_name, u.email,
                        EXISTS (SELECT 1 FROM user_roles ur JOIN roles r ON r.id = ur.role_id
                                WHERE ur.user_id = u.id AND r.name = 'administrateur') AS is_admin
                 FROM users u WHERE u.id = :id AND u.is_active = TRUE",
                ['id' => $userId]
            )
            : false;

        if (!$user) {
            if ($request->hasSession() && $userId !== null) {
                $request->getSession()->invalidate();
            }
            $event->setResponse($request->isXmlHttpRequest() || $request->getPreferredFormat() === 'json'
                ? new JsonResponse(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED)
                : new RedirectResponse($this->urlGenerator->generate('app_login')));
            return;
        }

        $isAdminPage = str_starts_with($route, 'app_admin')
            || $request->getPathInfo() === '/administration'
            || str_starts_with($request->getPathInfo(), '/administration/');
        if ($isAdminPage && !$user['is_admin']) {
            throw new NotFoundHttpException();
        }

        $request->attributes->set('_authenticated_user', $user);
    }

    public function preventPrivatePageCaching(ResponseEvent $event): void
    {
        if ($event->getRequest()->attributes->get('_private_page')) {
            $event->getResponse()->headers->set('Cache-Control', 'private, no-store');
        }
    }
}