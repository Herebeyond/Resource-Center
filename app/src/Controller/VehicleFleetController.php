<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class VehicleFleetController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    #[Route('/vehicules/suivi', name: 'app_vehicle_operations', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $user = $this->getCurrentUser($request);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $canManageFleet = (bool) $user['can_manage_fleet'];
        $reservations = $this->connection->fetchAllAssociative(
            "SELECT r.id, r.title, r.start_at, r.end_at, r.user_id, rs.code AS status,
                    resource.id AS vehicle_id, resource.name AS vehicle_name, resource.location AS parking_location,
                    resource_state.label AS vehicle_state, vd.brand, vd.model, vd.license_plate,
                    vu.checked_out_at, vu.returned_at, vu.issue_description, vu.issue_reported_at, vu.issue_resolved_at,
                    (r.start_at <= NOW() + INTERVAL '30 minutes' AND r.end_at > NOW()) AS can_pickup,
                    (vu.checked_out_at IS NOT NULL AND vu.returned_at IS NULL AND NOW() > r.end_at) AS is_late,
                    (vu.returned_at IS NOT NULL AND vu.returned_at > r.end_at) AS was_late,
                    (resource_state.label <> 'disponible' OR EXISTS (
                        SELECT 1
                        FROM vehicle_usage previous_usage
                        JOIN reservations previous_reservation ON previous_reservation.id = previous_usage.reservation_id
                        WHERE previous_usage.vehicle_id = resource.id
                          AND previous_reservation.id <> r.id
                          AND previous_reservation.end_at <= r.start_at
                          AND previous_usage.checked_out_at IS NOT NULL
                          AND (previous_usage.returned_at IS NULL OR previous_usage.returned_at > r.start_at)
                    )) AS needs_replacement
             FROM reservations r
             JOIN resources resource ON resource.id = r.resource_id
             JOIN resource_types rt ON rt.id = resource.type_id AND rt.name = 'vehicule'
             JOIN resource_states resource_state ON resource_state.id = resource.state_id
             JOIN reservation_status rs ON rs.id = r.status_id
             LEFT JOIN vehicle_details vd ON vd.resource_id = resource.id
             LEFT JOIN vehicle_usage vu ON vu.reservation_id = r.id AND vu.vehicle_id = resource.id
             WHERE r.company_id = :company
               AND r.end_at >= NOW() - INTERVAL '3 days'
               AND rs.code = 'confirmed'
               AND (:can_manage = TRUE OR r.user_id = :user)
             ORDER BY r.start_at
             LIMIT 150",
            ['company' => $user['company_id'], 'user' => $user['id'], 'can_manage' => $canManageFleet]
        );

        foreach ($reservations as &$reservation) {
            $reservation['alternatives'] = $reservation['needs_replacement']
                ? $this->getReplacementVehicles($user['company_id'], $reservation)
                : [];
        }
        unset($reservation);

                $openIssues = $canManageFleet ? $this->connection->fetchAllAssociative(
                        "SELECT vehicle.id, vehicle.name, vehicle.location, vd.brand, vd.model, vd.license_plate,
                                        vu.issue_description, vu.issue_reported_at, r.title AS related_reservation
                         FROM vehicle_usage vu
                         JOIN resources vehicle ON vehicle.id = vu.vehicle_id
                         JOIN resource_types rt ON rt.id = vehicle.type_id AND rt.name = 'vehicule'
                         LEFT JOIN vehicle_details vd ON vd.resource_id = vehicle.id
                         LEFT JOIN reservations r ON r.id = vu.reservation_id
                         WHERE vehicle.company_id = :company
                             AND vu.issue_description IS NOT NULL AND vu.issue_resolved_at IS NULL
                         ORDER BY vu.issue_reported_at DESC",
                        ['company' => $user['company_id']]
                ) : [];

        return $this->render('vehicle/operations.html.twig', [
            'current_user' => $user,
            'can_manage_fleet' => $canManageFleet,
            'reservations' => $reservations,
            'open_issues' => $openIssues,
        ]);
    }

    #[Route('/vehicules/reservations/{id}/prise', name: 'app_vehicle_pickup', methods: ['POST'])]
    public function pickup(Request $request, int $id): Response
    {
        $user = $this->getCurrentUser($request);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $reservation = $this->getAuthorizedReservation($id, $user);
        $this->validateToken($request, 'vehicle_use_' . $id);
        $existingUsage = $this->connection->fetchAssociative('SELECT checked_out_at, returned_at FROM vehicle_usage WHERE reservation_id = :id', ['id' => $id]);
        if ($existingUsage && $existingUsage['checked_out_at']) {
            return $this->redirectToRoute('app_vehicle_operations');
        }

        $this->connection->executeStatement(
            "INSERT INTO vehicle_usage (reservation_id, vehicle_id, checked_out_at, returned_at)
             VALUES (:reservation, :vehicle, NOW(), NULL)
             ON CONFLICT (reservation_id) DO UPDATE SET vehicle_id = EXCLUDED.vehicle_id, checked_out_at = EXCLUDED.checked_out_at, returned_at = NULL",
            ['reservation' => $reservation['id'], 'vehicle' => $reservation['vehicle_id']]
        );

        return $this->redirectToRoute('app_vehicle_operations');
    }

    #[Route('/vehicules/reservations/{id}/retour', name: 'app_vehicle_return', methods: ['POST'])]
    public function returnVehicle(Request $request, int $id): Response
    {
        $user = $this->getCurrentUser($request);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $reservation = $this->getAuthorizedReservation($id, $user);
        $this->validateToken($request, 'vehicle_use_' . $id);
        $this->connection->executeStatement(
            'UPDATE vehicle_usage SET returned_at = NOW() WHERE reservation_id = :reservation AND vehicle_id = :vehicle AND checked_out_at IS NOT NULL AND returned_at IS NULL',
            ['reservation' => $reservation['id'], 'vehicle' => $reservation['vehicle_id']]
        );

        return $this->redirectToRoute('app_vehicle_operations');
    }

    #[Route('/vehicules/reservations/{id}/incident', name: 'app_vehicle_issue', methods: ['POST'])]
    public function reportIssue(Request $request, int $id): Response
    {
        $user = $this->getCurrentUser($request);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $reservation = $this->getAuthorizedReservation($id, $user);
        $this->validateToken($request, 'vehicle_issue_' . $id);
        $description = trim((string) $request->request->get('issue_description'));
        if ($description === '') {
            return $this->redirectToRoute('app_vehicle_operations');
        }

        $maintenanceStateId = $this->connection->fetchOne("SELECT id FROM resource_states WHERE label = 'maintenance'");
        if (!$maintenanceStateId) {
            throw $this->createNotFoundException();
        }

        $this->connection->beginTransaction();
        try {
            $this->connection->executeStatement(
                "INSERT INTO vehicle_usage (reservation_id, vehicle_id, issue_reported_at, issue_description)
                 VALUES (:reservation, :vehicle, NOW(), :description)
                 ON CONFLICT (reservation_id) DO UPDATE SET vehicle_id = EXCLUDED.vehicle_id, issue_reported_at = NOW(), issue_description = EXCLUDED.issue_description, issue_resolved_at = NULL",
                ['reservation' => $reservation['id'], 'vehicle' => $reservation['vehicle_id'], 'description' => mb_substr($description, 0, 2000)]
            );
            $this->connection->update('resources', ['state_id' => $maintenanceStateId], ['id' => $reservation['vehicle_id'], 'company_id' => $user['company_id']]);
            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }

        return $this->redirectToRoute('app_vehicle_operations');
    }

    #[Route('/vehicules/{id}/incident/resolution', name: 'app_vehicle_resolve_issue', methods: ['POST'])]
    public function resolveIssue(Request $request, int $id): Response
    {
        $user = $this->getCurrentUser($request);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        if (!$user['can_manage_fleet']) {
            throw $this->createNotFoundException();
        }

        $this->validateToken($request, 'vehicle_resolve_' . $id);
        $availableStateId = $this->connection->fetchOne("SELECT id FROM resource_states WHERE label = 'disponible'");
        $vehicleExists = $this->connection->fetchOne(
            "SELECT 1 FROM resources r JOIN resource_types rt ON rt.id = r.type_id WHERE r.id = :id AND r.company_id = :company AND rt.name = 'vehicule'",
            ['id' => $id, 'company' => $user['company_id']]
        );
        if (!$availableStateId || !$vehicleExists) {
            throw $this->createNotFoundException();
        }

        $this->connection->beginTransaction();
        try {
            $this->connection->update('resources', ['state_id' => $availableStateId], ['id' => $id, 'company_id' => $user['company_id']]);
            $this->connection->executeStatement(
                'UPDATE vehicle_usage SET issue_resolved_at = NOW() WHERE vehicle_id = :vehicle AND issue_description IS NOT NULL AND issue_resolved_at IS NULL',
                ['vehicle' => $id]
            );
            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }

        return $this->redirectToRoute('app_vehicle_operations');
    }

    #[Route('/vehicules/reservations/{id}/remplacer', name: 'app_vehicle_replace', methods: ['POST'])]
    public function replaceVehicle(Request $request, int $id): Response
    {
        $user = $this->getCurrentUser($request);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $reservation = $this->getAuthorizedReservation($id, $user);
        $this->validateToken($request, 'vehicle_replace_' . $id);
        $replacementId = (int) $request->request->get('replacement_id');
        $replacement = $this->connection->fetchOne(
            "SELECT 1 FROM resources candidate
             JOIN resources original ON original.id = :original
             JOIN vehicle_details candidate_details ON candidate_details.resource_id = candidate.id
             JOIN vehicle_details original_details ON original_details.resource_id = original.id
             JOIN resource_types candidate_type ON candidate_type.id = candidate.type_id AND candidate_type.name = 'vehicule'
             JOIN resource_states state ON state.id = candidate.state_id AND state.label = 'disponible'
                         WHERE candidate.id = :replacement AND candidate.company_id = :company
                             AND candidate.is_active = TRUE
               AND candidate.id <> original.id
               AND candidate_details.brand = original_details.brand AND candidate_details.model = original_details.model
               AND NOT EXISTS (
                    SELECT 1 FROM reservations other_booking
                    JOIN reservation_status blocking_status ON blocking_status.id = other_booking.status_id
                    WHERE other_booking.resource_id = candidate.id AND blocking_status.is_blocking = TRUE
                      AND other_booking.id <> :reservation
                      AND other_booking.start_at < :end_at AND other_booking.end_at > :start_at
               )",
            [
                'original' => $reservation['vehicle_id'], 'replacement' => $replacementId,
                'company' => $user['company_id'], 'reservation' => $id,
                'start_at' => $reservation['start_at'], 'end_at' => $reservation['end_at'],
            ]
        );
        if (!$replacement) {
            throw $this->createNotFoundException();
        }

        $this->connection->update('reservations', ['resource_id' => $replacementId], ['id' => $id, 'company_id' => $user['company_id']]);
        return $this->redirectToRoute('app_vehicle_operations');
    }

    private function getAuthorizedReservation(int $id, array $user): array
    {
        $reservation = $this->connection->fetchAssociative(
            "SELECT r.id, r.user_id, r.start_at, r.end_at, r.resource_id AS vehicle_id
             FROM reservations r
             JOIN resources resource ON resource.id = r.resource_id
             JOIN resource_types rt ON rt.id = resource.type_id AND rt.name = 'vehicule'
             JOIN reservation_status rs ON rs.id = r.status_id AND rs.code = 'confirmed'
             WHERE r.id = :id AND r.company_id = :company",
            ['id' => $id, 'company' => $user['company_id']]
        );
        if (!$reservation || ((int) $reservation['user_id'] !== (int) $user['id'] && !$user['can_manage_fleet'])) {
            throw $this->createNotFoundException();
        }

        return $reservation;
    }

    private function validateToken(Request $request, string $tokenId): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken($tokenId, (string) $request->request->get('_csrf_token')))) {
            throw $this->createAccessDeniedException();
        }
    }

    private function getReplacementVehicles(int $companyId, array $reservation): array
    {
        return $this->connection->fetchAllAssociative(
            "SELECT candidate.id, candidate.name, candidate.location, candidate_details.license_plate
             FROM resources candidate
             JOIN resource_states state ON state.id = candidate.state_id AND state.label = 'disponible'
             JOIN vehicle_details candidate_details ON candidate_details.resource_id = candidate.id
             JOIN vehicle_details original_details ON original_details.resource_id = :original
             JOIN resource_types candidate_type ON candidate_type.id = candidate.type_id AND candidate_type.name = 'vehicule'
             WHERE candidate.company_id = :company
               AND candidate.is_active = TRUE
               AND candidate.id <> :original
               AND candidate_details.brand = original_details.brand AND candidate_details.model = original_details.model
               AND NOT EXISTS (
                    SELECT 1 FROM reservations other_booking
                    JOIN reservation_status blocking_status ON blocking_status.id = other_booking.status_id
                    WHERE other_booking.resource_id = candidate.id AND blocking_status.is_blocking = TRUE
                      AND other_booking.start_at < :end_at AND other_booking.end_at > :start_at
               )
             ORDER BY candidate.name LIMIT 5",
            [
                'company' => $companyId, 'original' => $reservation['vehicle_id'],
                'start_at' => $reservation['start_at'], 'end_at' => $reservation['end_at'],
            ]
        );
    }

    private function getCurrentUser(Request $request): ?array
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return null;
        }

        return $this->connection->fetchAssociative(
            "SELECT u.id, u.company_id, u.first_name, u.last_name, u.email,
                    EXISTS (SELECT 1 FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = u.id AND r.name = 'administrateur') AS is_admin,
                    EXISTS (SELECT 1 FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = u.id AND r.name IN ('administrateur', 'gestionnaire')) AS can_manage_fleet
             FROM users u WHERE u.id = :id AND u.is_active = TRUE",
            ['id' => $userId]
        ) ?: null;
    }
}