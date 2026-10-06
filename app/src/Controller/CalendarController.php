<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CalendarController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly TranslatorInterface $translator,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    #[Route('/calendrier', name: 'app_calendar', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $user = $this->getCurrentUser($request);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $selectedDate = $this->parseDate($request->query->get('date')) ?? new \DateTimeImmutable('today');
        $month = $selectedDate->modify('first day of this month');
        $monthGridStart = $month->modify('monday this week')->setTime(0, 0);
        $monthGridEnd = $month->modify('last day of this month')->modify('sunday this week')->modify('+1 day')->setTime(0, 0);
        $weekStart = $selectedDate->modify('monday this week')->setTime(0, 0);
        $weekEnd = $weekStart->modify('+7 days');
        $reservations = $this->connection->fetchAllAssociative(
            "SELECT r.id, r.title, r.start_at, r.end_at, r.notes, r.user_id, rs.code AS status,
                    res.name AS resource_name, rt.name AS resource_type
             FROM reservations r
             JOIN resources res ON res.id = r.resource_id
             JOIN resource_types rt ON rt.id = res.type_id
             JOIN reservation_status rs ON rs.id = r.status_id
             WHERE r.company_id = :company_id
               AND r.start_at < :grid_end AND r.end_at > :grid_start
               AND (r.user_id = :user_id OR EXISTS (
                    SELECT 1 FROM reservation_participants rp
                    WHERE rp.reservation_id = r.id AND rp.user_id = :user_id
               ))
             ORDER BY r.start_at, r.id",
            [
                'company_id' => $user['company_id'],
                'user_id' => $user['id'],
                'grid_start' => $monthGridStart->format('Y-m-d H:i:s'),
                'grid_end' => $monthGridEnd->format('Y-m-d H:i:s'),
            ]
        );

        $eventsByDate = [];
        $weekSegmentsByDate = [];
        foreach ($reservations as $reservation) {
            $startAt = new \DateTimeImmutable($reservation['start_at']);
            $endAt = new \DateTimeImmutable($reservation['end_at']);
            $lastEventDay = $endAt->modify('-1 second')->setTime(0, 0);
            $eventDay = $startAt->setTime(0, 0);
            while ($eventDay <= $lastEventDay) {
                $dateKey = $eventDay->format('Y-m-d');
                $event = [
                    'id' => (int) $reservation['id'],
                    'can_cancel' => (int) $reservation['user_id'] === (int) $user['id'] && $reservation['status'] !== 'cancelled',
                    'title' => $reservation['title'],
                    'resource' => $reservation['resource_name'],
                    'resource_type' => $reservation['resource_type'],
                    'start' => $startAt->format('H:i'),
                    'end' => $endAt->format('H:i'),
                    'status' => $reservation['status'],
                    'notes' => $reservation['notes'],
                    'starts_today' => $dateKey === $startAt->format('Y-m-d'),
                    'ends_today' => $dateKey === $lastEventDay->format('Y-m-d'),
                ];
                $eventsByDate[$dateKey][] = $event;

                if ($eventDay >= $weekStart && $eventDay < $weekEnd) {
                    $segmentStart = $startAt > $eventDay ? $startAt : $eventDay;
                    $nextDay = $eventDay->modify('+1 day');
                    $segmentEnd = $endAt < $nextDay ? $endAt : $nextDay;
                    $weekSegmentsByDate[$dateKey][] = [
                        ...$event,
                        'start_minutes' => ((int) $segmentStart->format('H')) * 60 + (int) $segmentStart->format('i'),
                        'duration_minutes' => max(1, (int) ceil(($segmentEnd->getTimestamp() - $segmentStart->getTimestamp()) / 60)),
                        'continues_before' => $startAt < $eventDay,
                        'continues_after' => $endAt > $nextDay,
                        'date' => $dateKey,
                    ];
                }
                $eventDay = $eventDay->modify('+1 day');
            }
        }

        $days = [];
        for ($day = $monthGridStart; $day < $monthGridEnd; $day = $day->modify('+1 day')) {
            $dateKey = $day->format('Y-m-d');
            $days[] = [
                'date' => $dateKey,
                'number' => $day->format('j'),
                'in_month' => $day->format('Y-m') === $month->format('Y-m'),
                'is_today' => $dateKey === (new \DateTimeImmutable('today'))->format('Y-m-d'),
                'has_reservations' => !empty($eventsByDate[$dateKey]),
                'reservations' => $eventsByDate[$dateKey] ?? [],
            ];
        }

        $weekDays = [];
        for ($day = $weekStart; $day < $weekEnd; $day = $day->modify('+1 day')) {
            $dateKey = $day->format('Y-m-d');
            $segments = $this->assignLanes($weekSegmentsByDate[$dateKey] ?? []);
            $weekDays[] = [
                'date' => $dateKey,
                'number' => $day->format('j'),
                'weekday_index' => (int) $day->format('N') - 1,
                'is_weekend' => (int) $day->format('N') >= 6,
                'is_today' => $dateKey === (new \DateTimeImmutable('today'))->format('Y-m-d'),
                'is_selected' => $dateKey === $selectedDate->format('Y-m-d'),
                'events' => $segments,
            ];
        }

        $translationKeys = [
            'title' => 'calendar.title', 'subtitle' => 'calendar.subtitle',
            'previousMonth' => 'calendar.previous_month', 'nextMonth' => 'calendar.next_month',
            'today' => 'calendar.today', 'selectedDay' => 'calendar.selected_day',
            'noReservations' => 'calendar.no_reservations', 'resource' => 'calendar.resource',
            'time' => 'calendar.time', 'status' => 'calendar.status',
            'notes' => 'calendar.notes', 'weekdays' => 'calendar.weekdays',
            'pending' => 'calendar.pending', 'confirmed' => 'calendar.confirmed',
            'rejected' => 'calendar.rejected', 'cancelled' => 'calendar.cancelled',
            'completed' => 'calendar.completed',
            'cancel' => 'calendar.cancel', 'cancelWait' => 'calendar.cancel_wait',
            'details' => 'calendar.details', 'cancelSuccess' => 'calendar.cancel_success',
            'cancelError' => 'calendar.cancel_error', 'close' => 'calendar.close',
        ];
        $translations = [];
        foreach (['fr', 'en'] as $language) {
            foreach ($translationKeys as $key => $translationKey) {
                $translations[$language][$key] = $this->translator->trans($translationKey, [], 'messages', $language);
            }
            $translations[$language]['weekdays'] = array_map(
                fn (string $weekday): string => $this->translator->trans('calendar.weekday.' . $weekday, [], 'messages', $language),
                ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']
            );
            $translations[$language]['month'] = $month->format('F Y');
            $translations[$language]['selectedDate'] = $selectedDate->format('Y-m-d');
        }

        return $this->render('calendar/index.html.twig', [
            'current_user' => $user,
            'month' => $month,
            'selected_date' => $selectedDate,
            'days' => $days,
            'week_days' => $weekDays,
            'selected_reservations' => $eventsByDate[$selectedDate->format('Y-m-d')] ?? [],
            'translations' => $translations,
        ]);
    }

    #[Route('/calendrier/reservations/{id}/annuler', name: 'app_calendar_cancel', methods: ['POST'])]
    public function cancel(Request $request, int $id): JsonResponse
    {
        $user = $this->getCurrentUser($request);
        if (!$user) {
            return $this->json(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $token = (string) $request->request->get('_csrf_token');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('calendar_cancel_' . $id, $token))) {
            return $this->json(['error' => 'invalid_token'], Response::HTTP_FORBIDDEN);
        }

        $startedAt = $request->getSession()->get('calendar_cancel_started', [])[$id] ?? null;
        if (!is_float($startedAt) && !is_int($startedAt) || microtime(true) - (float) $startedAt < 3) {
            return $this->json(['error' => 'confirmation_too_soon'], Response::HTTP_TOO_EARLY);
        }

        $statusId = $this->connection->fetchOne("SELECT id FROM reservation_status WHERE code = 'cancelled'");
        if (!$statusId) {
            return $this->json(['error' => 'cancel_unavailable'], Response::HTTP_CONFLICT);
        }

        $affected = $this->connection->executeStatement(
            "UPDATE reservations
             SET status_id = :cancelled_status
             WHERE id = :id AND company_id = :company_id AND user_id = :user_id
               AND EXISTS (
                    SELECT 1 FROM reservation_status current_status
                    WHERE current_status.id = reservations.status_id AND current_status.is_blocking = TRUE
               )",
            [
                'cancelled_status' => $statusId,
                'id' => $id,
                'company_id' => $user['company_id'],
                'user_id' => $user['id'],
            ]
        );

        if ($affected !== 1) {
            return $this->json(['error' => 'not_cancellable'], Response::HTTP_CONFLICT);
        }

        $startedAtByReservation = $request->getSession()->get('calendar_cancel_started', []);
        unset($startedAtByReservation[$id]);
        $request->getSession()->set('calendar_cancel_started', $startedAtByReservation);

        return $this->json(['success' => true]);
    }

    #[Route('/calendrier/reservations/{id}/annulation/demarrer', name: 'app_calendar_cancel_start', methods: ['POST'])]
    public function startCancellation(Request $request, int $id): JsonResponse
    {
        $user = $this->getCurrentUser($request);
        if (!$user) {
            return $this->json(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $token = (string) $request->request->get('_csrf_token');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('calendar_cancel_' . $id, $token))) {
            return $this->json(['error' => 'invalid_token'], Response::HTTP_FORBIDDEN);
        }

        $reservationExists = $this->connection->fetchOne(
            "SELECT 1
             FROM reservations r
             JOIN reservation_status rs ON rs.id = r.status_id
             WHERE r.id = :id AND r.company_id = :company_id AND r.user_id = :user_id
               AND rs.is_blocking = TRUE",
            ['id' => $id, 'company_id' => $user['company_id'], 'user_id' => $user['id']]
        );
        if (!$reservationExists) {
            return $this->json(['error' => 'not_cancellable'], Response::HTTP_CONFLICT);
        }

        $startedAtByReservation = $request->getSession()->get('calendar_cancel_started', []);
        $startedAtByReservation[$id] = microtime(true);
        $request->getSession()->set('calendar_cancel_started', $startedAtByReservation);

        return $this->json(['success' => true]);
    }

    private function assignLanes(array $segments): array
    {
        usort($segments, static fn (array $left, array $right): int => $left['start_minutes'] <=> $right['start_minutes']);
        $groups = [];
        $group = [];
        $groupEnd = -1;

        foreach ($segments as $segment) {
            $segmentEnd = $segment['start_minutes'] + $segment['duration_minutes'];
            if ($group && $segment['start_minutes'] >= $groupEnd) {
                $groups[] = $group;
                $group = [];
                $groupEnd = -1;
            }
            $group[] = $segment;
            $groupEnd = max($groupEnd, $segmentEnd);
        }
        if ($group) {
            $groups[] = $group;
        }

        $laidOut = [];
        foreach ($groups as $group) {
            $laneEnds = [];
            $laneSegments = [];
            foreach ($group as $segment) {
                $lane = null;
                foreach ($laneEnds as $index => $laneEnd) {
                    if ($laneEnd <= $segment['start_minutes']) {
                        $lane = $index;
                        break;
                    }
                }
                if ($lane === null) {
                    $lane = count($laneEnds);
                    $laneEnds[] = 0;
                }
                $laneEnds[$lane] = $segment['start_minutes'] + $segment['duration_minutes'];
                $segment['lane'] = $lane;
                $laneSegments[] = $segment;
            }

            $laneCount = max(1, count($laneEnds));
            foreach ($laneSegments as $segment) {
                $segment['lane_count'] = $laneCount;
                $laidOut[] = $segment;
            }
        }

        return $laidOut;
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

    private function parseDate(?string $value): ?\DateTimeImmutable
    {
        if (!$value || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $date : null;
    }

    private function parseMonth(?string $value): ?\DateTimeImmutable
    {
        if (!$value || !preg_match('/^\d{4}-\d{2}$/', $value)) {
            return null;
        }

        $month = \DateTimeImmutable::createFromFormat('!Y-m', $value);
        return $month && $month->format('Y-m') === $value ? $month : null;
    }
}