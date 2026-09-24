<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class ReservationController extends AbstractController
{
    private const TYPE_MAP = [
        'room' => 'salle',
        'vehicle' => 'vehicule',
        'laptop' => 'portable',
        'projector' => 'audiovisuel',
        'equipment' => 'equipement',
    ];

    private const LABELS = [
        'room' => ['title' => 'Select Room', 'search' => 'Search a room here'],
        'vehicle' => ['title' => 'Select Vehicle', 'search' => 'Search a vehicle here'],
        'laptop' => ['title' => 'Select Laptop', 'search' => 'Search a laptop here'],
        'projector' => ['title' => 'Select Projector', 'search' => 'Search a projector here'],
        'equipment' => ['title' => 'Select Equipment', 'search' => 'Search equipment here'],
    ];

    public function __construct(
        private readonly Connection $connection,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    #[Route('/reservations/{type}', name: 'app_reservation', methods: ['GET', 'POST'])]
    public function index(Request $request, string $type): Response
    {
        $user = $this->getCurrentUser($request);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!isset(self::TYPE_MAP[$type])) {
            throw $this->createNotFoundException();
        }

        $selectedDate = $this->normalizeDate($request->query->get('date'));
        $resources = $this->getResources((int) $user['company_id'], self::TYPE_MAP[$type]);
        $selectedResourceId = (int) ($request->query->get('resource') ?: ($resources[0]['id'] ?? 0));
        $selectedResource = $this->findResource($resources, $selectedResourceId);
        $error = null;
        $warning = null;

        if ($request->isMethod('POST')) {
            $selectedDate = $this->normalizeDate($request->request->get('date'));
            $selectedResourceId = (int) $request->request->get('resource_id');
            $selectedResource = $this->findResource($resources, $selectedResourceId);
            $startTime = (string) $request->request->get('start_time');
            $endTime = (string) $request->request->get('end_time');
            $title = trim((string) $request->request->get('title'));
            $notes = trim((string) $request->request->get('notes'));
            $csrfToken = (string) $request->request->get('_csrf_token');

            if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('reservation', $csrfToken))) {
                $error = 'The form security token is invalid. Please reload the page.';
            } elseif (!$selectedResource || !$this->validTimeRange($startTime, $endTime) || $title === '') {
                $error = 'Please select a resource, a valid time range, and enter a name.';
            } else {
                $startAt = $selectedDate . ' ' . $startTime . ':00';
                $endAt = $selectedDate . ' ' . $endTime . ':00';
                $conflict = $this->connection->fetchOne(
                    "SELECT 1
                     FROM reservations r
                     JOIN reservation_status rs ON rs.id = r.status_id
                     WHERE r.resource_id = :resource_id
                       AND rs.is_blocking = TRUE
                       AND r.start_at < :end_at
                       AND r.end_at > :start_at
                     LIMIT 1",
                    ['resource_id' => $selectedResourceId, 'start_at' => $startAt, 'end_at' => $endAt]
                );

                if ($conflict) {
                    $error = 'A reservation already exists in the time-frame you chose.';
                } else {
                    $nearby = $this->connection->fetchOne(
                        "SELECT 1
                         FROM reservations r
                         JOIN reservation_status rs ON rs.id = r.status_id
                         WHERE r.resource_id = :resource_id
                           AND rs.is_blocking = TRUE
                           AND ABS(EXTRACT(EPOCH FROM (r.end_at - CAST(:start_at AS timestamp)))) < 300
                         LIMIT 1",
                        ['resource_id' => $selectedResourceId, 'start_at' => $startAt]
                    );
                    $warning = $nearby ? 'This reservation is very close to another one. Please check the transition time.' : null;

                    $statusId = (int) $this->connection->fetchOne("SELECT id FROM reservation_status WHERE code = 'confirmed'");
                    $this->connection->insert('reservations', [
                        'company_id' => $user['company_id'],
                        'resource_id' => $selectedResourceId,
                        'user_id' => $user['id'],
                        'status_id' => $statusId,
                        'start_at' => $startAt,
                        'end_at' => $endAt,
                        'title' => $title,
                        'notes' => $notes ?: null,
                    ]);

                    return $this->redirectToRoute('app_reservation', [
                        'type' => $type,
                        'date' => $selectedDate,
                        'resource' => $selectedResourceId,
                        'created' => 1,
                    ]);
                }
            }
        }

        return $this->render('reservation/index.html.twig', [
            'current_user' => $user,
            'type' => $type,
            'type_label' => self::LABELS[$type]['title'],
            'search_placeholder' => self::LABELS[$type]['search'],
            'resources' => $resources,
            'selected_resource' => $selectedResource,
            'selected_date' => $selectedDate,
            'reservations' => $this->getReservations((int) $user['company_id'], self::TYPE_MAP[$type], $selectedDate),
            'error' => $error,
            'warning' => $warning,
            'created' => (bool) $request->query->get('created'),
        ]);
    }

    private function getCurrentUser(Request $request): ?array
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return null;
        }

        return $this->connection->fetchAssociative(
            'SELECT id, company_id, first_name, last_name, email FROM users WHERE id = :id AND is_active = TRUE',
            ['id' => $userId]
        ) ?: null;
    }

    private function getResources(int $companyId, string $type): array
    {
        return $this->connection->fetchAllAssociative(
            "SELECT r.id, r.name, r.code, r.location, r.capacity, rs.label AS state,
                    COALESCE(rd.available_places, r.capacity, 0) AS places,
                    rd.has_screen, rd.has_whiteboard
             FROM resources r
             JOIN resource_types rt ON rt.id = r.type_id
             JOIN resource_states rs ON rs.id = r.state_id
             LEFT JOIN room_details rd ON rd.resource_id = r.id
             WHERE r.company_id = :company_id AND r.is_active = TRUE AND rt.name = :type
             ORDER BY r.name",
            ['company_id' => $companyId, 'type' => $type]
        );
    }

    private function getReservations(int $companyId, string $type, string $date): array
    {
        return $this->connection->fetchAllAssociative(
            "SELECT r.id, r.title, r.start_at, r.end_at, r.resource_id, res.name AS resource_name
             FROM reservations r
             JOIN resources res ON res.id = r.resource_id
             JOIN resource_types rt ON rt.id = res.type_id
             JOIN reservation_status rs ON rs.id = r.status_id
             WHERE r.company_id = :company_id AND rt.name = :type
               AND rs.is_blocking = TRUE
               AND r.start_at < CAST(:date AS date) + INTERVAL '1 day'
               AND r.end_at > CAST(:date AS date)
             ORDER BY r.start_at",
            ['company_id' => $companyId, 'type' => $type, 'date' => $date]
        );
    }

    private function findResource(array $resources, int $id): ?array
    {
        foreach ($resources as $resource) {
            if ((int) $resource['id'] === $id) {
                return $resource;
            }
        }

        return null;
    }

    private function normalizeDate(?string $date): string
    {
        $parsed = $date ? \DateTimeImmutable::createFromFormat('Y-m-d', $date) : false;
        return $parsed ? $parsed->format('Y-m-d') : (new \DateTimeImmutable())->format('Y-m-d');
    }

    private function validTimeRange(string $start, string $end): bool
    {
        return preg_match('/^\d{2}:\d{2}$/', $start) === 1
            && preg_match('/^\d{2}:\d{2}$/', $end) === 1
            && $end > $start;
    }
}
