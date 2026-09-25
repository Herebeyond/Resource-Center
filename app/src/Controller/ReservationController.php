<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
        private readonly TranslatorInterface $translator,
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
        $timeframe = $request->query->get('timeframe') === 'full' ? 'full' : 'work';
        $hours = range($timeframe === 'full' ? 0 : 6, $timeframe === 'full' ? 23 : 18);
        $resources = $this->getResources((int) $user['company_id'], self::TYPE_MAP[$type]);
        $selectedResourceId = (int) $request->query->get('resource', 0);
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
                $error = 'reservation.invalid_token';
            } elseif (!$selectedResource || !$this->validTimeRange($startTime, $endTime) || $title === '') {
                $error = 'reservation.invalid_form';
            } else {
                $startAt = $selectedDate . ' ' . $startTime . ':00';
                $endDate = $endTime < $startTime
                    ? (new \DateTimeImmutable($selectedDate))->modify('+1 day')->format('Y-m-d')
                    : $selectedDate;
                $endAt = $endDate . ' ' . $endTime . ':00';
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
                    $error = 'reservation.conflict';
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
                    $warning = $nearby ? 'reservation.nearby' : null;

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

                    $this->addFlash('reservation_success', '1');

                    return $this->redirectToRoute('app_reservation', [
                        'type' => $type,
                        'date' => $selectedDate,
                        'resource' => $selectedResourceId,
                        'timeframe' => $timeframe,
                    ]);
                }
            }
        }

        return $this->render('reservation/index.html.twig', [
            'current_user' => $user,
            'type' => $type,
            'type_label' => $this->translator->trans($this->typeLabelKey($type), [], 'reservation', $request->getLocale()),
            'search_placeholder' => $this->translator->trans($this->searchKey($type), [], 'reservation', $request->getLocale()),
            'resources' => $resources,
            'selected_resource' => $selectedResource,
            'selected_date' => $selectedDate,
            'timeframe' => $timeframe,
            'hours' => $hours,
            'reservations' => $this->getReservations((int) $user['company_id'], self::TYPE_MAP[$type], $selectedDate),
            'error' => $error ? $this->translator->trans($error, [], 'reservation', $request->getLocale()) : null,
            'warning' => $warning ? $this->translator->trans($warning, [], 'reservation', $request->getLocale()) : null,
            'flash_success' => (bool) $request->getSession()->getFlashBag()->get('reservation_success'),
            'reservation_translations' => $this->getTranslations($type),
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
                "SELECT r.id, r.title, r.start_at, r.end_at, r.notes, r.resource_id, res.name AS resource_name,
                    GREATEST(r.start_at, CAST(:date AS date)) AS visual_start_at,
                    LEAST(r.end_at, CAST(:date AS date) + INTERVAL '1 day' - INTERVAL '1 second') AS visual_end_at,
                    (r.start_at < CAST(:date AS date)) AS continues_before,
                    (r.end_at >= CAST(:date AS date) + INTERVAL '1 day') AS continues_after
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

    private function typeLabelKey(string $type): string
    {
        return match ($type) {
            'room' => 'reservation.select_room',
            'vehicle' => 'reservation.select_vehicle',
            'laptop' => 'reservation.select_laptop',
            'projector' => 'reservation.select_projector',
            default => 'reservation.select_equipment',
        };
    }

    private function searchKey(string $type): string
    {
        return match ($type) {
            'room' => 'reservation.search_room',
            'vehicle' => 'reservation.search_vehicle',
            'laptop' => 'reservation.search_laptop',
            'projector' => 'reservation.search_projector',
            default => 'reservation.search_equipment',
        };
    }

    private function getTranslations(string $type): array
    {
        $keys = [
            'brandSubtitle' => 'reservation.brand_subtitle', 'navReservations' => 'reservation.nav_reservations',
            'navResources' => 'reservation.nav_resources', 'navCalendar' => 'reservation.nav_calendar',
            'navSettings' => 'reservation.nav_settings',
            'chooseResource' => 'reservation.choose_resource', 'searchOptions' => 'reservation.search_options',
            'videoConferencing' => 'reservation.video_conferencing', 'other' => 'reservation.other',
            'whiteboard' => 'reservation.whiteboard', 'minimumSeats' => 'reservation.minimum_seats',
            'maximumSeats' => 'reservation.maximum_seats', 'building' => 'reservation.building',
            'allBuildings' => 'reservation.all_buildings', 'selectDate' => 'reservation.select_date',
            'today' => 'reservation.today', 'next7Days' => 'reservation.next_7_days',
            'selectDateLabel' => 'reservation.select_date_label', 'selectTime' => 'reservation.select_time',
            'timeframe' => 'reservation.timeframe', 'workHours' => 'reservation.work_hours',
            'fullDay' => 'reservation.full_day', 'start' => 'reservation.start', 'end' => 'reservation.end',
            'name' => 'reservation.name', 'reservationName' => 'reservation.reservation_name',
            'description' => 'reservation.description', 'addDetails' => 'reservation.add_details',
            'timeSlot' => 'reservation.time_slot', 'availability' => 'reservation.availability',
            'summary' => 'reservation.summary', 'review' => 'reservation.review', 'resource' => 'reservation.resource',
            'noResource' => 'reservation.no_resource', 'date' => 'reservation.date', 'time' => 'reservation.time',
            'cancel' => 'reservation.cancel', 'confirm' => 'reservation.confirm', 'created' => 'reservation.created',
            'noResources' => 'reservation.no_resources', 'error' => 'reservation.invalid_form',
            'warning' => 'reservation.nearby', 'companyResource' => 'reservation.company_resource',
            'videoTag' => 'reservation.video_tag', 'whiteboardTag' => 'reservation.whiteboard_tag',
            'seats' => 'reservation.seats', 'changeLanguage' => 'account.change_language',
            'conflict' => 'reservation.conflict', 'nearby' => 'reservation.nearby',
            'nearbyReverse' => 'reservation.nearby_reverse',
            'nameRequired' => 'reservation.name_required', 'resourceRequired' => 'reservation.resource_required',
        ];

        $translations = [
            'fr' => $this->translateKeys($keys, 'fr'),
            'en' => $this->translateKeys($keys, 'en'),
        ];

        $translations['fr']['changeLanguage'] = $this->translator->trans('account.change_language', [], 'messages', 'fr');
        $translations['en']['changeLanguage'] = $this->translator->trans('account.change_language', [], 'messages', 'en');
        foreach (['fr', 'en'] as $locale) {
            $translations[$locale]['typeLabel'] = $this->translator->trans($this->typeLabelKey($type), [], 'reservation', $locale);
            $translations[$locale]['searchPlaceholder'] = $this->translator->trans($this->searchKey($type), [], 'reservation', $locale);
        }

        return $translations;
    }

    private function translateKeys(array $keys, string $locale): array
    {
        $result = [];
        foreach ($keys as $name => $key) {
            $result[$name] = $this->translator->trans($key, [], 'reservation', $locale);
        }

        return $result;
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
            && $end !== $start;
    }
}
