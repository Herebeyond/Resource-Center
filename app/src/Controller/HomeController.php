<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly TranslatorInterface $translator,
    )
    {
    }

    #[Route('/', name: 'app_home')]
    public function index(Request $request): Response
    {
        $currentUser = $this->getCurrentUser($request);
        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('home/index.html.twig', [
            'current_user' => $currentUser,
            'resource_cards' => $this->getResourceCards(),
            'translations' => $this->getHomeTranslations(),
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

            $error = 'account.invalid_credentials';
        }

        return $this->render('account/login.html.twig', [
            'error' => $error ? $this->translator->trans($error, [], 'messages', $request->getLocale()) : null,
            'login_translations' => $this->getLoginTranslations(),
        ]);
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

    private function getHomeTranslations(): array
    {
        return [
            'fr' => $this->translateKeys([
                'brandSubtitle' => 'brand.subtitle', 'navReservations' => 'nav.reservations',
                'navResources' => 'nav.resources', 'navCalendar' => 'nav.calendar',
                'navSettings' => 'nav.settings', 'signIn' => 'nav.sign_in',
                'signOut' => 'nav.sign_out', 'profile' => 'nav.profile',
                'heroTitle' => 'hero.title', 'heroCopy' => 'hero.copy',
                'roomTitle' => 'resource.meeting_rooms', 'laptopTitle' => 'resource.laptops',
                'vehicleTitle' => 'resource.vehicles', 'projectorTitle' => 'resource.projectors',
                'equipmentTitle' => 'resource.equipment', 'spaces' => 'unit.spaces',
                'seats' => 'unit.seats', 'devices' => 'unit.devices', 'available' => 'unit.available',
                'vehicles' => 'unit.vehicles', 'units' => 'unit.units', 'items' => 'unit.items',
                'roomDesc' => 'resource.room_description', 'laptopDesc' => 'resource.laptop_description',
                'vehicleDesc' => 'resource.vehicle_description', 'projectorDesc' => 'resource.projector_description',
                'equipmentDesc' => 'resource.equipment_description', 'footerTitle' => 'footer.title',
                'footerSubtitle' => 'footer.subtitle', 'scrollUp' => 'account.scroll_top',
                'changeLanguage' => 'account.change_language',
            ], 'fr'),
            'en' => $this->translateKeys([
                'brandSubtitle' => 'brand.subtitle', 'navReservations' => 'nav.reservations',
                'navResources' => 'nav.resources', 'navCalendar' => 'nav.calendar',
                'navSettings' => 'nav.settings', 'signIn' => 'nav.sign_in',
                'signOut' => 'nav.sign_out', 'profile' => 'nav.profile',
                'heroTitle' => 'hero.title', 'heroCopy' => 'hero.copy',
                'roomTitle' => 'resource.meeting_rooms', 'laptopTitle' => 'resource.laptops',
                'vehicleTitle' => 'resource.vehicles', 'projectorTitle' => 'resource.projectors',
                'equipmentTitle' => 'resource.equipment', 'spaces' => 'unit.spaces',
                'seats' => 'unit.seats', 'devices' => 'unit.devices', 'available' => 'unit.available',
                'vehicles' => 'unit.vehicles', 'units' => 'unit.units', 'items' => 'unit.items',
                'roomDesc' => 'resource.room_description', 'laptopDesc' => 'resource.laptop_description',
                'vehicleDesc' => 'resource.vehicle_description', 'projectorDesc' => 'resource.projector_description',
                'equipmentDesc' => 'resource.equipment_description', 'footerTitle' => 'footer.title',
                'footerSubtitle' => 'footer.subtitle', 'scrollUp' => 'account.scroll_top',
                'changeLanguage' => 'account.change_language',
            ], 'en'),
        ];
    }

    private function getLoginTranslations(): array
    {
        $keys = [
            'title' => 'account.sign_in', 'intro' => 'account.intro', 'email' => 'account.email',
            'password' => 'account.password', 'submit' => 'account.sign_in', 'note' => 'account.note',
            'error' => 'account.invalid_credentials', 'toggle' => 'account.change_language',
        ];

        return ['fr' => $this->translateKeys($keys, 'fr'), 'en' => $this->translateKeys($keys, 'en')];
    }

    private function translateKeys(array $keys, string $locale): array
    {
        $translations = [];
        foreach ($keys as $name => $key) {
            $translations[$name] = $this->translator->trans($key, [], 'messages', $locale);
        }

        return $translations;
    }
}
