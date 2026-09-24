<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    public function __construct(private readonly Connection $connection)
    {
    }

    #[Route('/', name: 'app_home')]
    public function index(Request $request): Response
    {
        return $this->render('home/index.html.twig', [
            'current_user' => $this->getCurrentUser($request),
            'resource_cards' => $this->getResourceCards(),
        ]);
    }

    #[Route('/connexion', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(Request $request): Response
    {
        $error = null;

        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email'));
            $password = (string) $request->request->get('password');
            $user = $this->connection->fetchAssociative(
                'SELECT id, first_name, last_name, email, password_hash
                 FROM users
                 WHERE LOWER(email) = LOWER(:email) AND is_active = TRUE',
                ['email' => $email]
            );

            if ($user && password_verify($password, (string) $user['password_hash'])) {
                $request->getSession()->set('user_id', (int) $user['id']);
                return $this->redirectToRoute('app_home');
            }

            $error = 'Adresse email ou mot de passe incorrect.';
        }

        return $this->render('account/login.html.twig', ['error' => $error]);
    }

    #[Route('/profil', name: 'app_profile')]
    public function profile(Request $request): Response
    {
        $user = $this->getCurrentUser($request);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('account/profile.html.twig', ['current_user' => $user]);
    }

    #[Route('/deconnexion', name: 'app_logout', methods: ['GET'])]
    public function logout(Request $request): Response
    {
        $request->getSession()->clear();
        return $this->redirectToRoute('app_home');
    }

    private function getCurrentUser(Request $request): ?array
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return null;
        }

        $user = $this->connection->fetchAssociative(
            'SELECT id, first_name, last_name, email FROM users WHERE id = :id AND is_active = TRUE',
            ['id' => $userId]
        );

        if (!$user) {
            $request->getSession()->clear();
            return null;
        }

        return $user;
    }

    private function getResourceCards(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT rt.name AS type_name,
                    COUNT(r.id)::int AS total,
                    COUNT(*) FILTER (WHERE rs.label = 'disponible')::int AS available,
                    COALESCE(SUM(r.capacity), 0)::int AS capacity
             FROM resources r
             JOIN resource_types rt ON rt.id = r.type_id
             JOIN resource_states rs ON rs.id = r.state_id
             WHERE r.is_active = TRUE
             GROUP BY rt.name"
        );

        $cards = [
            'rooms' => ['total' => 0, 'available' => 0, 'capacity' => 0],
            'laptops' => ['total' => 0, 'available' => 0, 'capacity' => 0],
            'vehicles' => ['total' => 0, 'available' => 0, 'capacity' => 0],
            'projectors' => ['total' => 0, 'available' => 0, 'capacity' => 0],
            'equipment' => ['total' => 0, 'available' => 0, 'capacity' => 0],
        ];
        $typeMap = [
            'salle' => 'rooms',
            'portable' => 'laptops',
            'vehicule' => 'vehicles',
            'audiovisuel' => 'projectors',
            'equipement' => 'equipment',
        ];

        foreach ($rows as $row) {
            $card = $typeMap[$row['type_name']] ?? null;
            if ($card) {
                $cards[$card] = [
                    'total' => (int) $row['total'],
                    'available' => (int) $row['available'],
                    'capacity' => (int) $row['capacity'],
                ];
            }
        }

        return $cards;
    }
}
