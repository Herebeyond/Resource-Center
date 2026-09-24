-- Fixtures de démonstration
--
-- Usage:
-- 1) Exécuter d'abord Reservations.sql (schéma + données de référence).
-- 2) Exécuter ensuite ce fichier pour injecter un jeu de données de test.
--
-- Le script est idempotent autant que possible: il évite les doublons via ON CONFLICT
-- et via des INSERT ... WHERE NOT EXISTS sur les réservations.

BEGIN;

-- =========================
-- Entreprise de démo
-- =========================

INSERT INTO companies (name)
VALUES ('Entreprise Démo CDA')
ON CONFLICT (name) DO NOTHING;

-- =========================
-- Utilisateurs de démo
-- =========================

INSERT INTO users (company_id, first_name, last_name, email, phone_number, password_hash, is_active)
SELECT c.id, 'Alice', 'Martin', 'alice.martin@demo-cda.local', '0102030405', '$2y$10$vMcqU/TZ74LJU0O7xeOA/OZo6Brn4xNeipgje3Q6f2G76zsenImm6', TRUE
FROM companies c
WHERE c.name = 'Entreprise Démo CDA'
ON CONFLICT (company_id, email) DO NOTHING;

INSERT INTO users (company_id, first_name, last_name, email, phone_number, password_hash, is_active)
SELECT c.id, 'Benoit', 'Durand', 'benoit.durand@demo-cda.local', '0102030406', '$2y$10$vMcqU/TZ74LJU0O7xeOA/OZo6Brn4xNeipgje3Q6f2G76zsenImm6', TRUE
FROM companies c
WHERE c.name = 'Entreprise Démo CDA'
ON CONFLICT (company_id, email) DO NOTHING;

INSERT INTO users (company_id, first_name, last_name, email, phone_number, password_hash, is_active)
SELECT c.id, 'Claire', 'Moreau', 'claire.moreau@demo-cda.local', '0102030407', '$2y$10$vMcqU/TZ74LJU0O7xeOA/OZo6Brn4xNeipgje3Q6f2G76zsenImm6', TRUE
FROM companies c
WHERE c.name = 'Entreprise Démo CDA'
ON CONFLICT (company_id, email) DO NOTHING;

-- =========================
-- Affectation des rôles
-- =========================

INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
JOIN companies c ON c.id = u.company_id
JOIN roles r ON r.name = 'administrateur'
WHERE c.name = 'Entreprise Démo CDA'
  AND u.email = 'alice.martin@demo-cda.local'
ON CONFLICT (user_id, role_id) DO NOTHING;

INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
JOIN companies c ON c.id = u.company_id
JOIN roles r ON r.name = 'gestionnaire'
WHERE c.name = 'Entreprise Démo CDA'
  AND u.email = 'benoit.durand@demo-cda.local'
ON CONFLICT (user_id, role_id) DO NOTHING;

INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
JOIN companies c ON c.id = u.company_id
JOIN roles r ON r.name = 'employe'
WHERE c.name = 'Entreprise Démo CDA'
  AND u.email = 'claire.moreau@demo-cda.local'
ON CONFLICT (user_id, role_id) DO NOTHING;

-- =========================
-- Ressources de démo
-- =========================

INSERT INTO resources (company_id, type_id, state_id, name, code, location, capacity, is_active)
SELECT c.id, rt.id, rs.id, 'Salle Orion', 'SALLE-ORION', 'Batiment A - 2e étage', 10, TRUE
FROM companies c
JOIN resource_types rt ON rt.name = 'salle'
JOIN resource_states rs ON rs.label = 'disponible'
WHERE c.name = 'Entreprise Démo CDA'
ON CONFLICT (company_id, code) DO NOTHING;

INSERT INTO resources (company_id, type_id, state_id, name, code, location, capacity, is_active)
SELECT c.id, rt.id, rs.id, 'Huddle Room 1', 'SALLE-HUDDLE-01', 'Batiment B - 1er étage', 4, TRUE
FROM companies c JOIN resource_types rt ON rt.name = 'salle' JOIN resource_states rs ON rs.label = 'disponible'
WHERE c.name = 'Entreprise Démo CDA'
ON CONFLICT (company_id, code) DO NOTHING;

INSERT INTO resources (company_id, type_id, state_id, name, code, location, capacity, is_active)
SELECT c.id, rt.id, rs.id, 'Auditorium', 'SALLE-AUDITORIUM', 'Campus principal', 200, TRUE
FROM companies c JOIN resource_types rt ON rt.name = 'salle' JOIN resource_states rs ON rs.label = 'disponible'
WHERE c.name = 'Entreprise Démo CDA'
ON CONFLICT (company_id, code) DO NOTHING;

INSERT INTO resources (company_id, type_id, state_id, name, code, location, capacity, is_active)
SELECT c.id, rt.id, rs.id, 'Executive Boardroom', 'SALLE-EXECUTIVE', 'Batiment A - Rez-de-chaussée', 12, TRUE
FROM companies c JOIN resource_types rt ON rt.name = 'salle' JOIN resource_states rs ON rs.label = 'disponible'
WHERE c.name = 'Entreprise Démo CDA'
ON CONFLICT (company_id, code) DO NOTHING;

INSERT INTO resources (company_id, type_id, state_id, name, code, location, capacity, is_active)
SELECT c.id, rt.id, rs.id, 'Ordinateur portable Dell 01', 'PORT-DELL-01', 'Armoire informatique - Etage 1', NULL, TRUE
FROM companies c
JOIN resource_types rt ON rt.name = 'portable'
JOIN resource_states rs ON rs.label = 'disponible'
WHERE c.name = 'Entreprise Démo CDA'
ON CONFLICT (company_id, code) DO NOTHING;

INSERT INTO resources (company_id, type_id, state_id, name, code, location, capacity, is_active)
SELECT c.id, rt.id, rs.id, 'Vidéoprojecteur Epson X2', 'AV-EPS-02', 'Réserve audiovisuelle - Etage 1', NULL, TRUE
FROM companies c
JOIN resource_types rt ON rt.name = 'audiovisuel'
JOIN resource_states rs ON rs.label = 'disponible'
WHERE c.name = 'Entreprise Démo CDA'
ON CONFLICT (company_id, code) DO NOTHING;

INSERT INTO resources (company_id, type_id, state_id, name, code, location, capacity, is_active)
SELECT c.id, rt.id, rs.id, 'Véhicule Zoe 01', 'VEH-ZOE-01', 'Parking site principal', 4, TRUE
FROM companies c
JOIN resource_types rt ON rt.name = 'vehicule'
JOIN resource_states rs ON rs.label = 'disponible'
WHERE c.name = 'Entreprise Démo CDA'
ON CONFLICT (company_id, code) DO NOTHING;

INSERT INTO resources (company_id, type_id, state_id, name, code, location, capacity, is_active)
SELECT c.id, rt.id, rs.id, 'Vidéoprojecteur Epson X1', 'EQP-VIDEO-01', 'Reserve materiel - Etage 1', NULL, TRUE
FROM companies c
JOIN resource_types rt ON rt.name = 'equipement'
JOIN resource_states rs ON rs.label = 'disponible'
WHERE c.name = 'Entreprise Démo CDA'
ON CONFLICT (company_id, code) DO NOTHING;

-- Détails spécifiques des ressources
INSERT INTO room_details (resource_id, building, floor, has_screen, has_whiteboard, has_projector, available_places)
SELECT r.id, 'Batiment A', '2', TRUE, TRUE, TRUE, 10
FROM resources r
JOIN companies c ON c.id = r.company_id
WHERE c.name = 'Entreprise Démo CDA' AND r.code = 'SALLE-ORION'
ON CONFLICT (resource_id) DO NOTHING;

INSERT INTO room_details (resource_id, building, floor, has_screen, has_whiteboard, has_projector, available_places)
SELECT r.id, 'Batiment B', '1', TRUE, FALSE, FALSE, 4
FROM resources r
WHERE r.code = 'SALLE-HUDDLE-01'
ON CONFLICT (resource_id) DO NOTHING;

INSERT INTO room_details (resource_id, building, floor, has_screen, has_whiteboard, has_projector, available_places)
SELECT r.id, 'Campus principal', '1', TRUE, TRUE, TRUE, 200
FROM resources r
WHERE r.code = 'SALLE-AUDITORIUM'
ON CONFLICT (resource_id) DO NOTHING;

INSERT INTO room_details (resource_id, building, floor, has_screen, has_whiteboard, has_projector, available_places)
SELECT r.id, 'Batiment A', '0', TRUE, TRUE, TRUE, 12
FROM resources r
WHERE r.code = 'SALLE-EXECUTIVE'
ON CONFLICT (resource_id) DO NOTHING;

INSERT INTO vehicle_details (resource_id, license_plate, brand, model, fuel_type)
SELECT r.id, 'AA-123-BB', 'Renault', 'Zoe', 'electrique'
FROM resources r
JOIN companies c ON c.id = r.company_id
WHERE c.name = 'Entreprise Démo CDA' AND r.code = 'VEH-ZOE-01'
ON CONFLICT (resource_id) DO NOTHING;

INSERT INTO equipment_details (resource_id, serial_number, brand, category)
SELECT r.id, 'SN-EPS-0001', 'Epson', 'videoprojecteur'
FROM resources r
JOIN companies c ON c.id = r.company_id
WHERE c.name = 'Entreprise Démo CDA' AND r.code = 'EQP-VIDEO-01'
ON CONFLICT (resource_id) DO NOTHING;

-- =========================
-- Réservations de démo
-- =========================

-- Réservation 1: confirmée pour la salle
INSERT INTO reservations (company_id, resource_id, user_id, status_id, start_at, end_at, title, notes)
SELECT c.id, r.id, u.id, s.id,
       TIMESTAMP '2026-10-01 09:00:00',
       TIMESTAMP '2026-10-01 10:00:00',
       'Point équipe hebdomadaire',
       'Réunion projet CDA'
FROM companies c
JOIN resources r ON r.company_id = c.id AND r.code = 'SALLE-ORION'
JOIN users u ON u.company_id = c.id AND u.email = 'claire.moreau@demo-cda.local'
JOIN reservation_status s ON s.code = 'confirmed'
WHERE c.name = 'Entreprise Démo CDA'
  AND NOT EXISTS (
    SELECT 1 FROM reservations x
    WHERE x.resource_id = r.id
      AND x.start_at = TIMESTAMP '2026-10-01 09:00:00'
      AND x.end_at = TIMESTAMP '2026-10-01 10:00:00'
  );

-- Réservation 2: en attente pour le véhicule
INSERT INTO reservations (company_id, resource_id, user_id, status_id, start_at, end_at, title, notes)
SELECT c.id, r.id, u.id, s.id,
       TIMESTAMP '2026-10-02 14:00:00',
       TIMESTAMP '2026-10-02 16:00:00',
       'Déplacement client',
       'Visite commerciale'
FROM companies c
JOIN resources r ON r.company_id = c.id AND r.code = 'VEH-ZOE-01'
JOIN users u ON u.company_id = c.id AND u.email = 'benoit.durand@demo-cda.local'
JOIN reservation_status s ON s.code = 'pending'
WHERE c.name = 'Entreprise Démo CDA'
  AND NOT EXISTS (
    SELECT 1 FROM reservations x
    WHERE x.resource_id = r.id
      AND x.start_at = TIMESTAMP '2026-10-02 14:00:00'
      AND x.end_at = TIMESTAMP '2026-10-02 16:00:00'
  );

-- Participants sur la réservation de salle
INSERT INTO reservation_participants (reservation_id, user_id, email, status)
SELECT rv.id, u.id, u.email, 'accepted'
FROM reservations rv
JOIN resources r ON r.id = rv.resource_id
JOIN users u ON u.company_id = rv.company_id
WHERE r.code = 'SALLE-ORION'
  AND rv.title = 'Point équipe hebdomadaire'
  AND u.email IN ('alice.martin@demo-cda.local', 'benoit.durand@demo-cda.local')
ON CONFLICT (reservation_id, user_id) DO NOTHING;

-- Notification de démonstration
INSERT INTO notifications (user_id, reservation_id, type, message, is_read)
SELECT u.id, rv.id, 'confirmation', 'Votre réservation est confirmée.', FALSE
FROM users u
JOIN reservations rv ON rv.user_id = u.id
WHERE u.email = 'claire.moreau@demo-cda.local'
  AND rv.title = 'Point équipe hebdomadaire'
  AND NOT EXISTS (
    SELECT 1 FROM notifications n
    WHERE n.user_id = u.id
      AND n.reservation_id = rv.id
      AND n.type = 'confirmation'
  );

-- Évènement de maintenance hors conflit
INSERT INTO maintenance_events (resource_id, created_by_user_id, start_at, end_at, reason)
SELECT r.id, admin_user.id,
       TIMESTAMP '2026-10-03 08:00:00',
       TIMESTAMP '2026-10-03 09:00:00',
       'Vérification préventive'
FROM resources r
JOIN companies c ON c.id = r.company_id
JOIN users admin_user ON admin_user.company_id = c.id AND admin_user.email = 'alice.martin@demo-cda.local'
WHERE c.name = 'Entreprise Démo CDA'
  AND r.code = 'VEH-ZOE-01'
  AND NOT EXISTS (
    SELECT 1 FROM maintenance_events m
    WHERE m.resource_id = r.id
      AND m.start_at = TIMESTAMP '2026-10-03 08:00:00'
      AND m.end_at = TIMESTAMP '2026-10-03 09:00:00'
  );

COMMIT;
