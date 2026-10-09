<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class AdminController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    #[Route('/administration', name: 'app_admin', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $user = $this->requireAdmin($request);

        $companyId = (int) $user['company_id'];
        return $this->render('admin/index.html.twig', [
            'current_user' => $user,
            'resource_count' => $this->connection->fetchOne('SELECT COUNT(*) FROM resources WHERE company_id = :company', ['company' => $companyId]),
            'active_resource_count' => $this->connection->fetchOne('SELECT COUNT(*) FROM resources WHERE company_id = :company AND is_active = TRUE', ['company' => $companyId]),
            'user_count' => $this->connection->fetchOne('SELECT COUNT(*) FROM users WHERE company_id = :company', ['company' => $companyId]),
            'unread_notifications' => $this->connection->fetchOne('SELECT COUNT(*) FROM notifications WHERE user_id = :user AND is_read = FALSE', ['user' => $user['id']]),
        ]);
    }

    #[Route('/administration/ressources', name: 'app_admin_resources', methods: ['GET', 'POST'])]
    public function resources(Request $request): Response
    {
        $user = $this->requireAdmin($request);

        $error = null;
        if ($request->isMethod('POST')) {
            $token = (string) $request->request->get('_csrf_token');
            $name = trim((string) $request->request->get('name'));
            $code = trim((string) $request->request->get('code'));
            $typeId = (int) $request->request->get('type_id');
            $location = trim((string) $request->request->get('location'));
            $capacityValue = trim((string) $request->request->get('capacity'));
            $capacity = $capacityValue === '' ? null : filter_var($capacityValue, FILTER_VALIDATE_INT);

            if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('admin_resource_create', $token))) {
                $error = 'admin.invalid_token';
            } elseif ($name === '' || $code === '' || $typeId < 1 || ($capacityValue !== '' && ($capacity === false || $capacity < 0))) {
                $error = 'admin.invalid_resource';
            } else {
                $stateId = $this->connection->fetchOne("SELECT id FROM resource_states WHERE label = 'disponible'");
                $typeExists = $this->connection->fetchOne('SELECT 1 FROM resource_types WHERE id = :id', ['id' => $typeId]);
                if (!$stateId || !$typeExists) {
                    $error = 'admin.invalid_resource';
                } else {
                    try {
                        $this->connection->insert('resources', [
                            'company_id' => $user['company_id'],
                            'type_id' => $typeId,
                            'state_id' => $stateId,
                            'name' => $name,
                            'code' => $code,
                            'location' => $location ?: null,
                            'capacity' => $capacity,
                            'is_active' => true,
                        ]);
                        $request->getSession()->set('admin_success', 'admin.resource_created');
                        return $this->redirectToRoute('app_admin_resources');
                    } catch (\Throwable) {
                        $error = 'admin.resource_code_exists';
                    }
                }
            }
        }

        return $this->render('admin/resources.html.twig', [
            'current_user' => $user,
            'error' => $error,
            'resource_types' => $this->connection->fetchAllAssociative('SELECT id, name, description FROM resource_types ORDER BY description'),
            'resources' => $this->connection->fetchAllAssociative(
                'SELECT r.id, r.name, r.code, r.location, r.capacity, r.is_active,
                        rt.name AS type_name, rt.description AS type_label, rs.label AS state
                 FROM resources r
                 JOIN resource_types rt ON rt.id = r.type_id
                 JOIN resource_states rs ON rs.id = r.state_id
                 WHERE r.company_id = :company ORDER BY rt.description, r.name',
                ['company' => $user['company_id']]
            ),
            'success' => $this->consumeMessages($request, 'admin_success'),
        ]);
    }

    #[Route('/administration/ressources/{id}/activation', name: 'app_admin_resource_toggle', methods: ['POST'])]
    public function toggleResource(Request $request, int $id): Response
    {
        $user = $this->requireAdmin($request);

        $token = (string) $request->request->get('_csrf_token');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('admin_resource_' . $id, $token))) {
            throw $this->createAccessDeniedException();
        }

        $this->connection->executeStatement(
            'UPDATE resources SET is_active = NOT is_active WHERE id = :id AND company_id = :company',
            ['id' => $id, 'company' => $user['company_id']]
        );

        return $this->redirectToRoute('app_admin_resources');
    }

    #[Route('/administration/utilisateurs', name: 'app_admin_users', methods: ['GET'])]
    public function users(Request $request): Response
    {
        $user = $this->requireAdmin($request);

        return $this->render('admin/users.html.twig', [
            'current_user' => $user,
            'users' => $this->connection->fetchAllAssociative(
                "SELECT u.id, u.first_name, u.last_name, u.email, u.is_active,
                        COALESCE(string_agg(r.name, ',' ORDER BY r.name), '') AS role_names
                 FROM users u
                 LEFT JOIN user_roles ur ON ur.user_id = u.id
                 LEFT JOIN roles r ON r.id = ur.role_id
                 WHERE u.company_id = :company
                 GROUP BY u.id ORDER BY u.last_name, u.first_name",
                ['company' => $user['company_id']]
            ),
            'roles' => $this->connection->fetchAllAssociative('SELECT name, description FROM roles ORDER BY name'),
            'success' => $this->consumeMessages($request, 'admin_success'),
            'error' => $this->consumeMessages($request, 'admin_error'),
        ]);
    }

    #[Route('/administration/utilisateurs/{id}/role', name: 'app_admin_user_role', methods: ['POST'])]
    public function updateUserRole(Request $request, int $id): Response
    {
        $user = $this->requireAdmin($request);

        $token = (string) $request->request->get('_csrf_token');
        $roleName = (string) $request->request->get('role');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('admin_user_role_' . $id, $token))) {
            throw $this->createAccessDeniedException();
        }
        if (!in_array($roleName, ['employe', 'gestionnaire', 'administrateur'], true)) {
            throw $this->createNotFoundException();
        }
        if ($id === (int) $user['id'] && $roleName !== 'administrateur') {
            $request->getSession()->set('admin_error', 'admin.cannot_demote_self');
            return $this->redirectToRoute('app_admin_users');
        }

        $roleId = $this->connection->fetchOne('SELECT id FROM roles WHERE name = :name', ['name' => $roleName]);
        if (!$roleId || !$this->connection->fetchOne('SELECT 1 FROM users WHERE id = :id AND company_id = :company', ['id' => $id, 'company' => $user['company_id']])) {
            throw $this->createNotFoundException();
        }

        if ($roleName !== 'administrateur') {
            $otherAdmins = (int) $this->connection->fetchOne(
                "SELECT COUNT(DISTINCT u.id)
                 FROM users u JOIN user_roles ur ON ur.user_id = u.id JOIN roles r ON r.id = ur.role_id
                 WHERE u.company_id = :company AND u.id <> :id AND u.is_active = TRUE AND r.name = 'administrateur'",
                ['company' => $user['company_id'], 'id' => $id]
            );
            $targetIsAdmin = $this->connection->fetchOne(
                "SELECT 1 FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = :id AND r.name = 'administrateur'",
                ['id' => $id]
            );
            if ($targetIsAdmin && $otherAdmins === 0) {
                $request->getSession()->set('admin_error', 'admin.last_admin');
                return $this->redirectToRoute('app_admin_users');
            }
        }

        $this->connection->beginTransaction();
        try {
            $this->connection->delete('user_roles', ['user_id' => $id]);
            $this->connection->insert('user_roles', ['user_id' => $id, 'role_id' => $roleId]);
            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }

        $request->getSession()->set('admin_success', 'admin.role_updated');
        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/administration/utilisateurs/{id}/activation', name: 'app_admin_user_toggle', methods: ['POST'])]
    public function toggleUser(Request $request, int $id): Response
    {
        $user = $this->requireAdmin($request);

        $token = (string) $request->request->get('_csrf_token');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('admin_user_' . $id, $token))) {
            throw $this->createAccessDeniedException();
        }
        if ($id === (int) $user['id']) {
            $request->getSession()->set('admin_error', 'admin.cannot_disable_self');
            return $this->redirectToRoute('app_admin_users');
        }

        $targetIsAdmin = $this->connection->fetchOne(
            "SELECT 1 FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = :id AND r.name = 'administrateur'",
            ['id' => $id]
        );
        if ($targetIsAdmin) {
            $otherAdmins = (int) $this->connection->fetchOne(
                "SELECT COUNT(DISTINCT u.id)
                 FROM users u JOIN user_roles ur ON ur.user_id = u.id JOIN roles r ON r.id = ur.role_id
                 WHERE u.company_id = :company AND u.id <> :id AND u.is_active = TRUE AND r.name = 'administrateur'",
                ['company' => $user['company_id'], 'id' => $id]
            );
            $targetActive = $this->connection->fetchOne(
                'SELECT is_active FROM users WHERE id = :id AND company_id = :company',
                ['id' => $id, 'company' => $user['company_id']]
            );
            if ($targetActive && $otherAdmins === 0) {
                $request->getSession()->set('admin_error', 'admin.last_admin');
                return $this->redirectToRoute('app_admin_users');
            }
        }

        $this->connection->executeStatement(
            'UPDATE users SET is_active = NOT is_active WHERE id = :id AND company_id = :company',
            ['id' => $id, 'company' => $user['company_id']]
        );
        $request->getSession()->set('admin_success', 'admin.user_updated');
        return $this->redirectToRoute('app_admin_users');
    }

    private function requireAdmin(Request $request): array
    {
        $user = $this->connection->fetchAssociative(
            "SELECT id, company_id, first_name, last_name, email, TRUE AS is_admin
             FROM users WHERE id = :id AND is_active = TRUE",
            ['id' => $request->getSession()->get('user_id')]
        );
        if (!$user) {
            throw $this->createNotFoundException();
        }

        $isAdmin = $this->connection->fetchOne(
            "SELECT 1 FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = :id AND r.name = 'administrateur'",
            ['id' => $user['id']]
        );
        if (!$isAdmin) {
            throw $this->createNotFoundException();
        }

        return $user;
    }

    private function consumeMessages(Request $request, string $key): array
    {
        $message = $request->getSession()->get($key);
        $request->getSession()->remove($key);

        return $message ? [$message] : [];
    }
}