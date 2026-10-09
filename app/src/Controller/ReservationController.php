<?php

namespace App\Controller;

use App\Service\ResourceTypeCatalog;
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
    public function __construct(
        private readonly Connection $connection,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly TranslatorInterface $translator,
        private readonly ResourceTypeCatalog $resourceTypeCatalog,
    ) {
    }

    #[Route('/reservations/{type}', name: 'app_reservation', methods: ['GET', 'POST'])]
    public function index(Request $request, string $type): Response
    {
        $user = $this->getCurrentUser($request);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $resourceTypeName = $this->resourceTypeCatalog->resolveName($type);
        if ($resourceTypeName === null) {
            throw $this->createNotFoundException();
        }
        $resourceTypeNames = $type === 'equipment'
            ? ['equipement', 'portable', 'audiovisuel']
            : [$resourceTypeName];

        $selectedDate = $this->normalizeDate($request->query->get('date'));
        $timeframe = $request->query->get('timeframe') === 'full' ? 'full' : 'work';
        $hours = range($timeframe === 'full' ? 0 : 6, $timeframe === 'full' ? 23 : 18);
        $resources = $this->getResources((int) $user['company_id'], $resourceTypeNames);
        $groupedSelection = in_array($type, ['vehicle', 'equipment', 'laptop', 'projector'], true);
        $resourceGroups = $groupedSelection ? $this->groupResources($resources, $type === 'vehicle') : [];
        $roomFeatures = [];
        if ($type === 'room') {
            foreach (['screen' => 'has_screen', 'whiteboard' => 'has_whiteboard', 'projector' => 'has_projector'] as $feature => $column) {
                if (array_filter($resources, static fn (array $resource): bool => (bool) $resource[$column])) {
                    $roomFeatures[] = $feature;
                }
            }
        }
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
            'grouped_selection' => $groupedSelection,
            'resource_groups' => $resourceGroups,
            'room_features' => $roomFeatures,
            'vehicle_classes' => $type === 'vehicle'
                ? $this->connection->fetchFirstColumn(
                    "SELECT DISTINCT vd.vehicle_class
                     FROM vehicle_details vd
                     JOIN resources r ON r.id = vd.resource_id
                     JOIN resource_types rt ON rt.id = r.type_id AND rt.name = 'vehicule'
                     WHERE r.company_id = :company AND vd.vehicle_class IS NOT NULL
                     ORDER BY vd.vehicle_class",
                    ['company' => $user['company_id']]
                )
                : [],
            'selected_resource' => $selectedResource,
            'selected_date' => $selectedDate,
            'timeframe' => $timeframe,
            'hours' => $hours,
            'reservations' => $this->getReservations((int) $user['company_id'], $resourceTypeNames, $selectedDate),
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
            "SELECT u.id, u.company_id, u.first_name, u.last_name, u.email,
                    EXISTS (SELECT 1 FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = u.id AND r.name = 'administrateur') AS is_admin
             FROM users u WHERE u.id = :id AND u.is_active = TRUE",
            ['id' => $userId]
        ) ?: null;
    }

    private function getResources(int $companyId, array $types): array
    {
        [$typePlaceholders, $typeParameters] = $this->typeQueryParameters($types, 'resource_type');
        return $this->connection->fetchAllAssociative(
            "SELECT r.id, r.name, r.code, r.location, r.capacity, rs.label AS state, rt.name AS resource_type,
                    COALESCE(rd.available_places, r.capacity, 0) AS places,
                    rd.has_screen, rd.has_whiteboard, rd.has_projector,
                    vd.license_plate, vd.brand AS vehicle_brand, vd.model AS vehicle_model, vd.fuel_type, vd.vehicle_class,
                    ed.brand AS equipment_brand, ed.model AS equipment_model,
                    ed.category AS equipment_category, ed.serial_number
             FROM resources r
             JOIN resource_types rt ON rt.id = r.type_id
             JOIN resource_states rs ON rs.id = r.state_id
             LEFT JOIN room_details rd ON rd.resource_id = r.id
             LEFT JOIN vehicle_details vd ON vd.resource_id = r.id
             LEFT JOIN equipment_details ed ON ed.resource_id = r.id
                         WHERE r.company_id = :company_id AND r.is_active = TRUE AND rs.label = 'disponible'
                             AND rt.name IN ($typePlaceholders)
             ORDER BY r.name",
            ['company_id' => $companyId] + $typeParameters
        );
    }

    private function getReservations(int $companyId, array $types, string $date): array
    {
        [$typePlaceholders, $typeParameters] = $this->typeQueryParameters($types, 'reservation_type');
        return $this->connection->fetchAllAssociative(
                "SELECT r.id, r.title, r.start_at, r.end_at, r.notes, r.resource_id,
                    CASE WHEN rt.name = 'vehicule' THEN CONCAT_WS(' · ',
                        COALESCE(NULLIF(TRIM(CONCAT_WS(' ', vd.brand, vd.model)), ''), res.name),
                        NULLIF(vd.license_plate, ''))
                    WHEN rt.name IN ('equipement', 'portable', 'audiovisuel') THEN CONCAT_WS(' · ',
                        CASE WHEN NULLIF(TRIM(ed.model), '') IS NOT NULL
                            THEN TRIM(CONCAT_WS(' ', ed.brand, ed.model)) ELSE res.name END,
                        COALESCE(NULLIF(ed.serial_number, ''), res.code))
                    ELSE res.name END AS resource_name,
                    GREATEST(r.start_at, CAST(:date AS date)) AS visual_start_at,
                    LEAST(r.end_at, CAST(:date AS date) + INTERVAL '1 day' - INTERVAL '1 second') AS visual_end_at,
                    (r.start_at < CAST(:date AS date)) AS continues_before,
                    (r.end_at >= CAST(:date AS date) + INTERVAL '1 day') AS continues_after
             FROM reservations r
             JOIN resources res ON res.id = r.resource_id
             JOIN resource_types rt ON rt.id = res.type_id
             LEFT JOIN vehicle_details vd ON vd.resource_id = res.id
             LEFT JOIN equipment_details ed ON ed.resource_id = res.id
             JOIN reservation_status rs ON rs.id = r.status_id
             WHERE r.company_id = :company_id AND rt.name IN ($typePlaceholders)
               AND rs.is_blocking = TRUE
               AND r.start_at < CAST(:date AS date) + INTERVAL '1 day'
               AND r.end_at > CAST(:date AS date)
             ORDER BY r.start_at",
            ['company_id' => $companyId, 'date' => $date] + $typeParameters
        );
    }

    private function groupResources(array &$resources, bool $isVehicle): array
    {
        $groups = [];
        foreach ($resources as &$resource) {
            $brand = trim((string) ($resource[$isVehicle ? 'vehicle_brand' : 'equipment_brand'] ?? ''));
            $model = trim((string) ($resource[$isVehicle ? 'vehicle_model' : 'equipment_model'] ?? ''));
            $category = trim((string) ($resource['equipment_category'] ?? ''));
            $vehicleClass = trim((string) ($resource['vehicle_class'] ?? ''));
            $fuelType = trim((string) ($resource['fuel_type'] ?? ''));
            $capacity = (int) $resource['places'];
            $groupIdentity = $isVehicle
                ? [$vehicleClass, $brand, $model, $fuelType, $capacity]
                : [$resource['resource_type'], $brand, $model, $category];
            $groupKey = $model !== '' && (!$isVehicle || $brand !== '')
                ? hash('sha256', mb_strtolower(json_encode($groupIdentity, JSON_THROW_ON_ERROR)))
                : 'resource-' . $resource['id'];
            $resource['vehicle_group_key'] = $groupKey;

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'key' => $groupKey,
                    'brand' => $brand,
                    'model' => $model,
                    'vehicle_class' => $vehicleClass,
                    'category' => $category,
                    'name' => $model !== '' ? trim($brand . ' ' . $model) : $resource['name'],
                    'capacity' => $capacity,
                    'fuel_type' => $fuelType,
                    'locations' => [],
                    'resource_ids' => [],
                    'resources' => [],
                ];
            }

            $groups[$groupKey]['resources'][] = $resource;
            $groups[$groupKey]['resource_ids'][] = (string) $resource['id'];
            if ($resource['location']) {
                $groups[$groupKey]['locations'][$resource['location']] = $resource['location'];
            }
        }
        unset($resource);

        foreach ($groups as &$group) {
            $group['count'] = count($group['resources']);
            $group['locations'] = array_values($group['locations']);
            $group['search_location'] = mb_strtolower(implode(' ', $group['locations']));
        }
        unset($group);

        return array_values($groups);
    }

    private function typeQueryParameters(array $types, string $prefix): array
    {
        $placeholders = [];
        $parameters = [];
        foreach (array_values($types) as $index => $type) {
            $name = $prefix . $index;
            $placeholders[] = ':' . $name;
            $parameters[$name] = $type;
        }

        return [implode(', ', $placeholders), $parameters];
    }

    private function typeLabelKey(string $type): string
    {
        return match ($type) {
            'room' => 'reservation.select_room',
            'vehicle' => 'reservation.select_vehicle',
            'laptop' => 'reservation.select_laptop',
            'projector' => 'reservation.select_projector',
            'equipment' => 'reservation.select_equipment',
            default => 'reservation.select_resource',
        };
    }

    private function searchKey(string $type): string
    {
        return match ($type) {
            'room' => 'reservation.search_room',
            'vehicle' => 'reservation.search_vehicle',
            'laptop' => 'reservation.search_laptop',
            'projector' => 'reservation.search_projector',
            'equipment' => 'reservation.search_equipment',
            default => 'reservation.search_resource',
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
            'allBuildings' => 'reservation.all_buildings', 'brand' => 'reservation.brand',
            'allBrands' => 'reservation.all_brands', 'fuelType' => 'reservation.fuel_type',
            'allFuelTypes' => 'reservation.all_fuel_types', 'vehicleClass' => 'reservation.vehicle_class',
            'allVehicleClasses' => 'reservation.all_vehicle_classes', 'location' => 'reservation.location',
            'allLocations' => 'reservation.all_locations', 'category' => 'reservation.category',
            'allCategories' => 'reservation.all_categories', 'physicalVehicles' => 'reservation.physical_vehicles',
            'backToVehicles' => 'reservation.back_to_vehicles', 'sortVehicles' => 'reservation.sort_vehicles',
            'sortAvailability' => 'reservation.sort_availability', 'sortName' => 'reservation.sort_name',
            'parkingLocation' => 'reservation.parking_location', 'slotAvailable' => 'reservation.slot_available',
            'slotUnavailable' => 'reservation.slot_unavailable', 'criteriaNotMet' => 'reservation.criteria_not_met',
            'selectDate' => 'reservation.select_date',
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
            'projectorTag' => 'reservation.projector_tag',
            'seats' => 'reservation.seats', 'changeLanguage' => 'account.change_language',
            'conflict' => 'reservation.conflict', 'nearby' => 'reservation.nearby',
            'nearbyReverse' => 'reservation.nearby_reverse',
            'nameRequired' => 'reservation.name_required', 'resourceRequired' => 'reservation.resource_required',
            'selectVehicle' => 'reservation.select_vehicle', 'reloadAvailability' => 'reservation.reload_availability',
            'physicalUnits' => 'reservation.physical_units', 'selectUnit' => 'reservation.select_unit',
            'backToModels' => 'reservation.back_to_models', 'equipmentCriteriaNotMet' => 'reservation.equipment_criteria_not_met',
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
