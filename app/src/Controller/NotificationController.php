<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class NotificationController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    #[Route('/notifications', name: 'app_notifications', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $user = $this->getCurrentUser($request);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('notifications/index.html.twig', [
            'current_user' => $user,
            'notifications' => $this->connection->fetchAllAssociative(
                'SELECT n.id, n.type, n.message, n.is_read, n.sent_at, n.reservation_id, r.title AS reservation_title
                 FROM notifications n
                 LEFT JOIN reservations r ON r.id = n.reservation_id
                 WHERE n.user_id = :user ORDER BY n.sent_at DESC, n.id DESC',
                ['user' => $user['id']]
            ),
        ]);
    }

    #[Route('/notifications/{id}/lue', name: 'app_notification_read', methods: ['POST'])]
    public function markRead(Request $request, int $id): Response
    {
        $user = $this->getCurrentUser($request);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $token = (string) $request->request->get('_csrf_token');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('notification_read_' . $id, $token))) {
            throw $this->createAccessDeniedException();
        }

        $this->connection->executeStatement(
            'UPDATE notifications SET is_read = TRUE WHERE id = :id AND user_id = :user',
            ['id' => $id, 'user' => $user['id']]
        );

        return $this->redirectToRoute('app_notifications');
    }

    private function getCurrentUser(Request $request): ?array
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return null;
        }

        return $this->connection->fetchAssociative(
            "SELECT u.id, u.company_id, u.first_name, u.last_name, u.email,
                    EXISTS (SELECT 1 FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = u.id AND r.name = 'administrateur') AS is_admin
             FROM users u WHERE u.id = :id AND u.is_active = TRUE",
            ['id' => $userId]
        ) ?: null;
    }
}