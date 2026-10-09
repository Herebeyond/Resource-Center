-- Fixtures de démonstration
--
-- Usage:
-- 1) Exécuter d'abord Reservations.sql (schéma + données de référence).
-- 2) Exécuter ensuite ce fichier pour injecter un jeu de données de test.
--
-- Le script est idempotent autant que possible: il évite les doublons via ON CONFLICT
-- et via des INSERT ... WHERE NOT EXISTS sur les réservations.

BEGIN;

CREATE TABLE IF NOT EXISTS vehicle_usage (
  reservation_id INT PRIMARY KEY REFERENCES reservations(id) ON DELETE CASCADE,
  vehicle_id INT NOT NULL REFERENCES resources(id) ON DELETE RESTRICT,
  checked_out_at TIMESTAMP,
  returned_at TIMESTAMP,
  issue_reported_at TIMESTAMP,
  issue_description TEXT,
  issue_resolved_at TIMESTAMP,
  CONSTRAINT chk_vehicle_usage_return CHECK (returned_at IS NULL OR checked_out_at IS NOT NULL)
);
ALTER TABLE vehicle_usage ADD COLUMN IF NOT EXISTS vehicle_id INT REFERENCES resources(id) ON DELETE RESTRICT;
UPDATE vehicle_usage vu SET vehicle_id = r.resource_id FROM reservations r WHERE r.id = vu.reservation_id AND vu.vehicle_id IS NULL;
ALTER TABLE vehicle_usage ALTER COLUMN vehicle_id SET NOT NULL;
CREATE INDEX IF NOT EXISTS idx_vehicle_usage_vehicle ON vehicle_usage(vehicle_id);
ALTER TABLE vehicle_details ADD COLUMN IF NOT EXISTS vehicle_class VARCHAR(80);
ALTER TABLE equipment_details ADD COLUMN IF NOT EXISTS model VARCHAR(120);

-- Répare les libellés de référence si la base existante contient une ancienne valeur mal encodée.
UPDATE resource_types
SET description = CASE name
  WHEN 'portable' THEN 'Ordinateur portable partagé'
  WHEN 'audiovisuel' THEN 'Vidéoprojecteur et matériel audiovisuel'
END
WHERE name IN ('portable', 'audiovisuel');

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


DELETE FROM user_roles ur
USING users u, companies c
WHERE ur.user_id = u.id AND c.id = u.company_id
  AND c.name = 'Entreprise Démo CDA'
  AND u.email IN ('alice.martin@demo-cda.local', 'benoit.durand@demo-cda.local');

INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
JOIN companies c ON c.id = u.company_id
JOIN roles r ON r.name = 'employe'
WHERE c.name = 'Entreprise Démo CDA'
  AND u.email = 'alice.martin@demo-cda.local'
ON CONFLICT (user_id, role_id) DO NOTHING;

INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
JOIN companies c ON c.id = u.company_id
JOIN roles r ON r.name = 'administrateur'
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
SELECT c.id, rt.id, rs.id, vehicles.name, vehicles.code, vehicles.location, vehicles.capacity, TRUE
FROM companies c
JOIN resource_types rt ON rt.name = 'vehicule'
JOIN resource_states rs ON rs.label = 'disponible'
CROSS JOIN (VALUES
  ('Véhicule Zoe 02', 'VEH-ZOE-02', 'Parking site principal', 4),
  ('Véhicule Zoe 03', 'VEH-ZOE-03', 'Parking annexe', 4)
) AS vehicles(name, code, location, capacity)
WHERE c.name = 'Entreprise Démo CDA'
ON CONFLICT (company_id, code) DO NOTHING;

INSERT INTO resources (company_id, type_id, state_id, name, code, location, capacity, is_active)
SELECT c.id, rt.id, rs.id, 'Peugeot 308', 'VEH-308-01', 'Parking site principal', 5, TRUE
FROM companies c
JOIN resource_types rt ON rt.name = 'vehicule'
JOIN resource_states rs ON rs.label = 'disponible'
WHERE c.name = 'Entreprise Démo CDA'
ON CONFLICT (company_id, code) DO NOTHING;

INSERT INTO resources (company_id, type_id, state_id, name, code, location, capacity, is_active)
SELECT c.id, rt.id, rs.id, 'Renault Kangoo', 'VEH-KANGOO-01', 'Parking logistique', 2, TRUE
FROM companies c
JOIN resource_types rt ON rt.name = 'vehicule'
JOIN resource_states rs ON rs.label = 'disponible'
WHERE c.name = 'Entreprise Démo CDA'
ON CONFLICT (company_id, code) DO NOTHING;

INSERT INTO resources (company_id, type_id, state_id, name, code, location, capacity, is_active)
SELECT c.id, rt.id, rs.id, 'Tesla Model 3', 'VEH-TESLA-01', 'Parking visiteurs', 5, TRUE
FROM companies c
JOIN resource_types rt ON rt.name = 'vehicule'
JOIN resource_states rs ON rs.label = 'disponible'
WHERE c.name = 'Entreprise Démo CDA'
ON CONFLICT (company_id, code) DO NOTHING;

INSERT INTO resources (company_id, type_id, state_id, name, code, location, capacity, is_active)
SELECT c.id, rt.id, rs.id, vehicles.name, vehicles.code, vehicles.location, vehicles.capacity, TRUE
FROM companies c
JOIN resource_types rt ON rt.name = 'vehicule'
JOIN resource_states rs ON rs.label = 'disponible'
CROSS JOIN (VALUES
  ('Citadine Citroën C3', 'VEH-C3-01', 'Parking site principal', 5),
  ('Citadine Toyota Yaris', 'VEH-YARIS-01', 'Parking site principal', 5),
  ('Utilitaire Ford Transit', 'VEH-TRANSIT-01', 'Parking logistique', 3),
  ('Fourgon Mercedes Sprinter', 'VEH-SPRINTER-01', 'Parking logistique', 3),
  ('SUV Volkswagen ID.4', 'VEH-ID4-01', 'Parking visiteurs', 5),
  ('SUV Dacia Duster', 'VEH-DUSTER-01', 'Parking annexe', 5),
  ('Berline BMW i4', 'VEH-BMW-I4-01', 'Parking direction', 5),
  ('Citadine Nissan Leaf', 'VEH-LEAF-01', 'Parking site principal', 5),
  ('Citadine Renault Clio', 'VEH-CLIO-01', 'Parking site principal', 5),
  ('Citadine Peugeot e-208', 'VEH-E208-01', 'Parking visiteurs', 5),
  ('Minibus Toyota Proace Verso', 'VEH-PROACE-01', 'Parking logistique', 9),
  ('Ludospace Citroën Berlingo', 'VEH-BERLINGO-01', 'Parking annexe', 5),
  ('Fourgon Fiat Ducato', 'VEH-DUCATO-01', 'Parking logistique', 3),
  ('SUV Volvo XC40', 'VEH-XC40-01', 'Parking direction', 5),
  ('Crossover Kia Niro', 'VEH-NIRO-01', 'Parking visiteurs', 5),
  ('SUV Hyundai Kona', 'VEH-KONA-01', 'Parking visiteurs', 5),
  ('Break Skoda Octavia', 'VEH-OCTAVIA-01', 'Parking annexe', 5),
  ('Crossover Ford Puma', 'VEH-PUMA-01', 'Parking site principal', 5),
  ('Scooter Yamaha Tricity', 'VEH-TRICITY-01', 'Parking deux-roues', 2),
  ('Grand utilitaire Renault Master', 'VEH-MASTER-01', 'Parking logistique', 3)
) AS vehicles(name, code, location, capacity)
WHERE c.name = 'Entreprise Démo CDA'
ON CONFLICT (company_id, code) DO NOTHING;

-- 200 véhicules de flotte pour tester les catégories, les groupes et la localisation.
INSERT INTO resources (company_id, type_id, state_id, name, code, location, capacity, is_active)
SELECT c.id, rt.id, rs.id,
       models.vehicle_class || ' ' || models.brand || ' ' || models.model || ' ' || LPAD(units.unit_number::text, 2, '0'),
       'VEH-FLEET-' || LPAD(models.model_number::text, 2, '0') || '-' || LPAD(units.unit_number::text, 2, '0'),
       CASE (units.unit_number % 4)
         WHEN 0 THEN models.base_location
         WHEN 1 THEN 'Parking site principal'
         WHEN 2 THEN 'Parking annexe'
         ELSE 'Parking logistique'
       END,
       models.capacity,
       TRUE
FROM companies c
JOIN resource_types rt ON rt.name = 'vehicule'
JOIN resource_states rs ON rs.label = 'disponible'
CROSS JOIN (VALUES
  (1, 'citadine', 'Renault', 'Clio', 'essence', 5, 'Parking citadines'),
  (2, 'citadine', 'Peugeot', '208', 'electrique', 5, 'Parking citadines'),
  (3, 'citadine', 'Citroën', 'C3', 'essence', 5, 'Parking citadines'),
  (4, 'citadine', 'Toyota', 'Yaris', 'hybride', 5, 'Parking citadines'),
  (5, 'SUV', 'Dacia', 'Duster', 'GPL', 5, 'Parking SUV'),
  (6, 'SUV', 'Toyota', 'RAV4', 'hybride', 5, 'Parking SUV'),
  (7, 'pick-up', 'Ford', 'Ranger', 'diesel', 5, 'Parking utilitaires'),
  (8, 'pick-up', 'Toyota', 'Hilux', 'diesel', 5, 'Parking utilitaires'),
  (9, 'camionnette', 'Renault', 'Trafic', 'diesel', 3, 'Parking utilitaires'),
  (10, 'camionnette', 'Peugeot', 'Expert', 'electrique', 3, 'Parking utilitaires'),
  (11, 'autobus', 'Mercedes', 'Citaro', 'diesel', 55, 'Dépôt autobus'),
  (12, 'autobus', 'Iveco', 'Crossway', 'diesel', 55, 'Dépôt autobus'),
  (13, 'semi-remorque', 'Volvo', 'FH', 'diesel', 2, 'Dépôt poids lourds'),
  (14, 'semi-remorque', 'Scania', 'R-series', 'diesel', 2, 'Dépôt poids lourds'),
  (15, 'minibus', 'Toyota', 'Proace Verso', 'diesel', 9, 'Parking minibus'),
  (16, 'berline', 'BMW', 'Série 3', 'hybride', 5, 'Parking direction'),
  (17, 'break', 'Skoda', 'Octavia', 'diesel', 5, 'Parking direction'),
  (18, 'fourgon', 'Renault', 'Master', 'diesel', 3, 'Parking utilitaires'),
  (19, 'scooter', 'Yamaha', 'Tricity', 'essence', 2, 'Parking deux-roues'),
  (20, 'camion-benne', 'MAN', 'TGS', 'diesel', 2, 'Dépôt poids lourds')
) AS models(model_number, vehicle_class, brand, model, fuel_type, capacity, base_location)
CROSS JOIN generate_series(1, 10) AS units(unit_number)
WHERE c.name = 'Entreprise Démo CDA'
ON CONFLICT (company_id, code) DO NOTHING;

INSERT INTO resources (company_id, type_id, state_id, name, code, location, capacity, is_active)
SELECT c.id, rt.id, rs.id, 'Vidéoprojecteur Epson X1', 'EQP-VIDEO-01', 'Reserve materiel - Etage 1', NULL, TRUE
FROM companies c
JOIN resource_types rt ON rt.name = 'equipement'
JOIN resource_states rs ON rs.label = 'disponible'
WHERE c.name = 'Entreprise Démo CDA'
ON CONFLICT (company_id, code) DO NOTHING;

INSERT INTO resources (company_id, type_id, state_id, name, code, location, capacity, is_active)
SELECT c.id, rt.id, rs.id, items.name, items.code, items.location, NULL, TRUE
FROM companies c
JOIN resource_types rt ON rt.name = 'equipement'
JOIN resource_states rs ON rs.label = 'disponible'
CROSS JOIN (VALUES
  ('Appareil photo hybride Canon EOS R50', 'EQP-CAMERA-01', 'Studio audiovisuel'),
  ('Appareil photo reflex Nikon D7500', 'EQP-CAMERA-02', 'Studio audiovisuel'),
  ('Caméra vidéo Sony AX43', 'EQP-VIDEO-CAMERA-01', 'Studio audiovisuel'),
  ('Webcam Logitech Brio', 'EQP-WEBCAM-01', 'Armoire informatique'),
  ('Microphone sans fil Shure', 'EQP-MIC-WIRELESS-01', 'Studio audiovisuel'),
  ('Microphone USB Blue Yeti', 'EQP-MIC-USB-01', 'Armoire informatique'),
  ('Enregistreur audio Zoom H6', 'EQP-AUDIO-REC-01', 'Studio audiovisuel'),
  ('Enceinte portable JBL Charge', 'EQP-SPEAKER-01', 'Réserve événementielle'),
  ('Casque audio Sony WH-1000XM5', 'EQP-HEADSET-01', 'Armoire informatique'),
  ('Trépied vidéo Manfrotto', 'EQP-TRIPOD-01', 'Studio audiovisuel'),
  ('Kit éclairage LED Aputure', 'EQP-LIGHT-01', 'Studio audiovisuel'),
  ('Tablette Apple iPad Air', 'EQP-TABLET-01', 'Armoire informatique'),
  ('Écran portable ASUS ZenScreen', 'EQP-MONITOR-01', 'Armoire informatique'),
  ('Station d’accueil Dell WD19', 'EQP-DOCK-01', 'Armoire informatique'),
  ('Routeur Wi-Fi TP-Link AX55', 'EQP-ROUTER-01', 'Local réseau'),
  ('Point d’accès Wi-Fi Ubiquiti', 'EQP-ACCESS-POINT-01', 'Local réseau'),
  ('Switch réseau Netgear 8 ports', 'EQP-SWITCH-01', 'Local réseau'),
  ('Disque dur externe LaCie Rugged', 'EQP-STORAGE-01', 'Armoire informatique'),
  ('Trousse à outils Bosch', 'EQP-TOOLKIT-01', 'Atelier'),
  ('Perceuse sans fil Makita', 'EQP-DRILL-01', 'Atelier'),
  ('Multimètre numérique Fluke', 'EQP-MULTIMETER-01', 'Atelier'),
  ('Télémètre laser Bosch GLM', 'EQP-RANGEFINDER-01', 'Atelier'),
  ('Lampe de chantier rechargeable', 'EQP-WORKLIGHT-01', 'Atelier'),
  ('Kit de visioconférence Jabra PanaCast', 'EQP-CONFERENCE-01', 'Studio audiovisuel')
) AS items(name, code, location)
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

INSERT INTO vehicle_details (resource_id, license_plate, brand, model, fuel_type)
SELECT r.id, 'CD-456-EF', 'Peugeot', '308', 'essence'
FROM resources r
JOIN companies c ON c.id = r.company_id
WHERE c.name = 'Entreprise Démo CDA' AND r.code = 'VEH-308-01'
ON CONFLICT (resource_id) DO NOTHING;

INSERT INTO vehicle_details (resource_id, license_plate, brand, model, fuel_type)
SELECT r.id, 'GH-789-IJ', 'Renault', 'Kangoo', 'diesel'
FROM resources r
JOIN companies c ON c.id = r.company_id
WHERE c.name = 'Entreprise Démo CDA' AND r.code = 'VEH-KANGOO-01'
ON CONFLICT (resource_id) DO NOTHING;

INSERT INTO vehicle_details (resource_id, license_plate, brand, model, fuel_type)
SELECT r.id, 'KL-012-MN', 'Tesla', 'Model 3', 'electrique'
FROM resources r
JOIN companies c ON c.id = r.company_id
WHERE c.name = 'Entreprise Démo CDA' AND r.code = 'VEH-TESLA-01'
ON CONFLICT (resource_id) DO NOTHING;

INSERT INTO vehicle_details (resource_id, license_plate, brand, model, fuel_type)
SELECT r.id, vehicles.license_plate, vehicles.brand, vehicles.model, vehicles.fuel_type
FROM companies c
JOIN resources r ON r.company_id = c.id
JOIN (VALUES
  ('VEH-C3-01', 'OP-101-QR', 'Citroën', 'C3', 'essence'),
  ('VEH-YARIS-01', 'ST-202-UV', 'Toyota', 'Yaris', 'hybride'),
  ('VEH-TRANSIT-01', 'WX-303-YZ', 'Ford', 'Transit', 'diesel'),
  ('VEH-SPRINTER-01', 'AB-404-CD', 'Mercedes', 'Sprinter', 'diesel'),
  ('VEH-ID4-01', 'EF-505-GH', 'Volkswagen', 'ID.4', 'electrique'),
  ('VEH-DUSTER-01', 'IJ-606-KL', 'Dacia', 'Duster', 'GPL'),
  ('VEH-BMW-I4-01', 'MN-707-OP', 'BMW', 'i4', 'electrique'),
  ('VEH-LEAF-01', 'QR-808-ST', 'Nissan', 'Leaf', 'electrique'),
  ('VEH-CLIO-01', 'UV-909-WX', 'Renault', 'Clio', 'essence'),
  ('VEH-E208-01', 'YZ-110-AB', 'Peugeot', 'e-208', 'electrique'),
  ('VEH-PROACE-01', 'CD-211-EF', 'Toyota', 'Proace Verso', 'diesel'),
  ('VEH-BERLINGO-01', 'GH-312-IJ', 'Citroën', 'Berlingo', 'diesel'),
  ('VEH-DUCATO-01', 'KL-413-MN', 'Fiat', 'Ducato', 'diesel'),
  ('VEH-XC40-01', 'OP-514-QR', 'Volvo', 'XC40', 'hybride rechargeable'),
  ('VEH-NIRO-01', 'ST-615-UV', 'Kia', 'Niro', 'hybride'),
  ('VEH-KONA-01', 'WX-716-YZ', 'Hyundai', 'Kona', 'electrique'),
  ('VEH-OCTAVIA-01', 'AB-817-CD', 'Skoda', 'Octavia', 'diesel'),
  ('VEH-PUMA-01', 'EF-918-GH', 'Ford', 'Puma', 'hybride'),
  ('VEH-TRICITY-01', 'IJ-019-KL', 'Yamaha', 'Tricity', 'essence'),
  ('VEH-MASTER-01', 'MN-120-OP', 'Renault', 'Master', 'diesel'),
  ('VEH-ZOE-02', 'RS-221-TU', 'Renault', 'Zoe', 'electrique'),
  ('VEH-ZOE-03', 'VW-322-XA', 'Renault', 'Zoe', 'electrique')
) AS vehicles(code, license_plate, brand, model, fuel_type) ON vehicles.code = r.code
WHERE c.name = 'Entreprise Démo CDA'
ON CONFLICT (resource_id) DO NOTHING;

UPDATE vehicle_details vd
SET vehicle_class = classes.vehicle_class
FROM resources r
JOIN (VALUES
  ('VEH-ZOE-01', 'citadine'), ('VEH-ZOE-02', 'citadine'), ('VEH-ZOE-03', 'citadine'),
  ('VEH-308-01', 'berline'), ('VEH-KANGOO-01', 'camionnette'), ('VEH-TESLA-01', 'berline'),
  ('VEH-C3-01', 'citadine'), ('VEH-YARIS-01', 'citadine'), ('VEH-TRANSIT-01', 'camionnette'),
  ('VEH-SPRINTER-01', 'fourgon'), ('VEH-ID4-01', 'SUV'), ('VEH-DUSTER-01', 'SUV'),
  ('VEH-BMW-I4-01', 'berline'), ('VEH-LEAF-01', 'citadine'), ('VEH-CLIO-01', 'citadine'),
  ('VEH-E208-01', 'citadine'), ('VEH-PROACE-01', 'minibus'), ('VEH-BERLINGO-01', 'camionnette'),
  ('VEH-DUCATO-01', 'fourgon'), ('VEH-XC40-01', 'SUV'), ('VEH-NIRO-01', 'SUV'),
  ('VEH-KONA-01', 'SUV'), ('VEH-OCTAVIA-01', 'break'), ('VEH-PUMA-01', 'SUV'),
  ('VEH-TRICITY-01', 'scooter'), ('VEH-MASTER-01', 'fourgon')
) AS classes(code, vehicle_class) ON classes.code = r.code
WHERE vd.resource_id = r.id AND vd.vehicle_class IS NULL;

INSERT INTO vehicle_details (resource_id, license_plate, brand, model, fuel_type, vehicle_class)
SELECT r.id,
  'DM-' || LPAD(((models.model_number - 1) * 10 + units.unit_number)::text, 3, '0') || '-AA',
       models.brand, models.model, models.fuel_type, models.vehicle_class
FROM companies c
CROSS JOIN (VALUES
  (1, 'citadine', 'Renault', 'Clio', 'essence'),
  (2, 'citadine', 'Peugeot', '208', 'electrique'),
  (3, 'citadine', 'Citroën', 'C3', 'essence'),
  (4, 'citadine', 'Toyota', 'Yaris', 'hybride'),
  (5, 'SUV', 'Dacia', 'Duster', 'GPL'),
  (6, 'SUV', 'Toyota', 'RAV4', 'hybride'),
  (7, 'pick-up', 'Ford', 'Ranger', 'diesel'),
  (8, 'pick-up', 'Toyota', 'Hilux', 'diesel'),
  (9, 'camionnette', 'Renault', 'Trafic', 'diesel'),
  (10, 'camionnette', 'Peugeot', 'Expert', 'electrique'),
  (11, 'autobus', 'Mercedes', 'Citaro', 'diesel'),
  (12, 'autobus', 'Iveco', 'Crossway', 'diesel'),
  (13, 'semi-remorque', 'Volvo', 'FH', 'diesel'),
  (14, 'semi-remorque', 'Scania', 'R-series', 'diesel'),
  (15, 'minibus', 'Toyota', 'Proace Verso', 'diesel'),
  (16, 'berline', 'BMW', 'Série 3', 'hybride'),
  (17, 'break', 'Skoda', 'Octavia', 'diesel'),
  (18, 'fourgon', 'Renault', 'Master', 'diesel'),
  (19, 'scooter', 'Yamaha', 'Tricity', 'essence'),
  (20, 'camion-benne', 'MAN', 'TGS', 'diesel')
) AS models(model_number, vehicle_class, brand, model, fuel_type)
CROSS JOIN generate_series(1, 10) AS units(unit_number)
JOIN resources r ON r.company_id = c.id
  AND r.code = 'VEH-FLEET-' || LPAD(models.model_number::text, 2, '0') || '-' || LPAD(units.unit_number::text, 2, '0')
WHERE c.name = 'Entreprise Démo CDA'
ON CONFLICT (resource_id) DO UPDATE SET
    license_plate = EXCLUDED.license_plate,
    brand = EXCLUDED.brand,
    model = EXCLUDED.model,
    fuel_type = EXCLUDED.fuel_type,
    vehicle_class = EXCLUDED.vehicle_class;

INSERT INTO equipment_details (resource_id, serial_number, brand, category)
SELECT r.id, 'SN-EPS-0001', 'Epson', 'videoprojecteur'
FROM resources r
JOIN companies c ON c.id = r.company_id
WHERE c.name = 'Entreprise Démo CDA' AND r.code = 'EQP-VIDEO-01'
ON CONFLICT (resource_id) DO NOTHING;

INSERT INTO equipment_details (resource_id, serial_number, brand, category)
SELECT r.id, items.serial_number, items.brand, items.category
FROM companies c
JOIN resources r ON r.company_id = c.id
JOIN (VALUES
  ('EQP-CAMERA-01', 'SN-CANON-R50-01', 'Canon', 'photo'),
  ('EQP-CAMERA-02', 'SN-NIKON-D7500-01', 'Nikon', 'photo'),
  ('EQP-VIDEO-CAMERA-01', 'SN-SONY-AX43-01', 'Sony', 'video'),
  ('EQP-WEBCAM-01', 'SN-LOGI-BRIO-01', 'Logitech', 'visioconference'),
  ('EQP-MIC-WIRELESS-01', 'SN-SHURE-WL-01', 'Shure', 'audio'),
  ('EQP-MIC-USB-01', 'SN-BLUE-YETI-01', 'Blue', 'audio'),
  ('EQP-AUDIO-REC-01', 'SN-ZOOM-H6-01', 'Zoom', 'audio'),
  ('EQP-SPEAKER-01', 'SN-JBL-CHARGE-01', 'JBL', 'audio'),
  ('EQP-HEADSET-01', 'SN-SONY-WH1000-01', 'Sony', 'audio'),
  ('EQP-TRIPOD-01', 'SN-MANFROTTO-01', 'Manfrotto', 'photo'),
  ('EQP-LIGHT-01', 'SN-APUTURE-LED-01', 'Aputure', 'eclairage'),
  ('EQP-TABLET-01', 'SN-APPLE-IPAD-AIR-01', 'Apple', 'informatique'),
  ('EQP-MONITOR-01', 'SN-ASUS-ZEN-01', 'ASUS', 'informatique'),
  ('EQP-DOCK-01', 'SN-DELL-WD19-01', 'Dell', 'informatique'),
  ('EQP-ROUTER-01', 'SN-TPLINK-AX55-01', 'TP-Link', 'reseau'),
  ('EQP-ACCESS-POINT-01', 'SN-UBIQUITI-AP-01', 'Ubiquiti', 'reseau'),
  ('EQP-SWITCH-01', 'SN-NETGEAR-SW8-01', 'Netgear', 'reseau'),
  ('EQP-STORAGE-01', 'SN-LACIE-RUGGED-01', 'LaCie', 'stockage'),
  ('EQP-TOOLKIT-01', 'SN-BOSCH-TOOLS-01', 'Bosch', 'outillage'),
  ('EQP-DRILL-01', 'SN-MAKITA-DRILL-01', 'Makita', 'outillage'),
  ('EQP-MULTIMETER-01', 'SN-FLUKE-METER-01', 'Fluke', 'mesure'),
  ('EQP-RANGEFINDER-01', 'SN-BOSCH-GLM-01', 'Bosch', 'mesure'),
  ('EQP-WORKLIGHT-01', 'SN-WORKLIGHT-01', 'Bosch', 'eclairage'),
  ('EQP-CONFERENCE-01', 'SN-JABRA-PANA-01', 'Jabra', 'visioconference')
) AS items(code, serial_number, brand, category) ON items.code = r.code
WHERE c.name = 'Entreprise Démo CDA'
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

-- Série dense pour tester les colonnes d'évènements qui se chevauchent.
INSERT INTO reservations (company_id, resource_id, user_id, status_id, start_at, end_at, title, notes)
SELECT c.id, r.id, u.id, s.id,
       TIMESTAMP '2026-10-01 09:00:00',
       TIMESTAMP '2026-10-01 12:00:00',
       'Test flotte - ' || vehicles.name,
       'Réservation de démonstration pour tester les chevauchements du calendrier.'
FROM companies c
JOIN users u ON u.company_id = c.id AND u.email = 'alice.martin@demo-cda.local'
JOIN reservation_status s ON s.code = 'confirmed'
JOIN (VALUES
  ('VEH-C3-01', 'Citroën C3'),
  ('VEH-YARIS-01', 'Toyota Yaris'),
  ('VEH-TRANSIT-01', 'Ford Transit'),
  ('VEH-SPRINTER-01', 'Mercedes Sprinter'),
  ('VEH-ID4-01', 'Volkswagen ID.4'),
  ('VEH-DUSTER-01', 'Dacia Duster'),
  ('VEH-BMW-I4-01', 'BMW i4'),
  ('VEH-LEAF-01', 'Nissan Leaf'),
  ('VEH-CLIO-01', 'Renault Clio'),
  ('VEH-E208-01', 'Peugeot e-208'),
  ('VEH-PROACE-01', 'Toyota Proace Verso'),
  ('VEH-BERLINGO-01', 'Citroën Berlingo'),
  ('VEH-DUCATO-01', 'Fiat Ducato'),
  ('VEH-XC40-01', 'Volvo XC40'),
  ('VEH-NIRO-01', 'Kia Niro'),
  ('VEH-KONA-01', 'Hyundai Kona'),
  ('VEH-OCTAVIA-01', 'Skoda Octavia'),
  ('VEH-PUMA-01', 'Ford Puma'),
  ('VEH-TRICITY-01', 'Yamaha Tricity'),
  ('VEH-MASTER-01', 'Renault Master')
) AS vehicles(code, name) ON TRUE
JOIN resources r ON r.company_id = c.id AND r.code = vehicles.code
WHERE c.name = 'Entreprise Démo CDA'
  AND NOT EXISTS (
    SELECT 1 FROM reservations x
    WHERE x.resource_id = r.id
      AND x.start_at = TIMESTAMP '2026-10-01 09:00:00'
      AND x.end_at = TIMESTAMP '2026-10-01 12:00:00'
  );

INSERT INTO reservations (company_id, resource_id, user_id, status_id, start_at, end_at, title, notes)
SELECT c.id, r.id, u.id, s.id,
       TIMESTAMP '2026-10-01 09:00:00',
       TIMESTAMP '2026-10-01 12:00:00',
       'Test capacité salle',
       'Créneau de démonstration superposé aux réservations de véhicules.'
FROM companies c
JOIN resources r ON r.company_id = c.id AND r.code = 'SALLE-AUDITORIUM'
JOIN users u ON u.company_id = c.id AND u.email = 'alice.martin@demo-cda.local'
JOIN reservation_status s ON s.code = 'confirmed'
WHERE c.name = 'Entreprise Démo CDA'
  AND NOT EXISTS (
    SELECT 1 FROM reservations x
    WHERE x.resource_id = r.id
      AND x.start_at = TIMESTAMP '2026-10-01 09:00:00'
      AND x.end_at = TIMESTAMP '2026-10-01 12:00:00'
  );

INSERT INTO reservations (company_id, resource_id, user_id, status_id, start_at, end_at, title, notes)
SELECT c.id, r.id, u.id, s.id,
       TIMESTAMP '2026-10-01 09:30:00',
       TIMESTAMP '2026-10-01 11:30:00',
       'Test chevauchement salle',
       'Deuxième créneau de salle pour tester la largeur partagée des évènements.'
FROM companies c
JOIN resources r ON r.company_id = c.id AND r.code = 'SALLE-EXECUTIVE'
JOIN users u ON u.company_id = c.id AND u.email = 'alice.martin@demo-cda.local'
JOIN reservation_status s ON s.code = 'confirmed'
WHERE c.name = 'Entreprise Démo CDA'
  AND NOT EXISTS (
    SELECT 1 FROM reservations x
    WHERE x.resource_id = r.id
      AND x.start_at = TIMESTAMP '2026-10-01 09:30:00'
      AND x.end_at = TIMESTAMP '2026-10-01 11:30:00'
  );

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

-- Créneaux qui se chevauchent pour Alice: une salle et un véhicule.
INSERT INTO reservations (company_id, resource_id, user_id, status_id, start_at, end_at, title, notes)
SELECT c.id, r.id, u.id, s.id,
       TIMESTAMP '2026-10-01 09:30:00',
       TIMESTAMP '2026-10-01 10:30:00',
       'Préparation atelier',
       'Créneau de démonstration pour vérifier le chevauchement dans le calendrier.'
FROM companies c
JOIN resources r ON r.company_id = c.id AND r.code = 'SALLE-HUDDLE-01'
JOIN users u ON u.company_id = c.id AND u.email = 'alice.martin@demo-cda.local'
JOIN reservation_status s ON s.code = 'confirmed'
WHERE c.name = 'Entreprise Démo CDA'
  AND NOT EXISTS (
    SELECT 1 FROM reservations x
    WHERE x.resource_id = r.id
      AND x.start_at = TIMESTAMP '2026-10-01 09:30:00'
      AND x.end_at = TIMESTAMP '2026-10-01 10:30:00'
  );

INSERT INTO reservations (company_id, resource_id, user_id, status_id, start_at, end_at, title, notes)
SELECT c.id, r.id, u.id, s.id,
       TIMESTAMP '2026-10-01 09:45:00',
       TIMESTAMP '2026-10-01 11:15:00',
       'Déplacement de démonstration',
       'Créneau de véhicule qui chevauche une réservation de salle pour Alice.'
FROM companies c
JOIN resources r ON r.company_id = c.id AND r.code = 'VEH-308-01'
JOIN users u ON u.company_id = c.id AND u.email = 'alice.martin@demo-cda.local'
JOIN reservation_status s ON s.code = 'confirmed'
WHERE c.name = 'Entreprise Démo CDA'
  AND NOT EXISTS (
    SELECT 1 FROM reservations x
    WHERE x.resource_id = r.id
      AND x.start_at = TIMESTAMP '2026-10-01 09:45:00'
      AND x.end_at = TIMESTAMP '2026-10-01 11:15:00'
  );

-- Dix réservations de véhicules pour la semaine ouvrée à venir.
INSERT INTO reservations (company_id, resource_id, user_id, status_id, start_at, end_at, title, notes)
SELECT c.id, r.id, u.id, s.id,
       (date_trunc('week', CURRENT_DATE)::date + INTERVAL '1 week' + bookings.day_offset * INTERVAL '1 day')::date + bookings.start_time,
       (date_trunc('week', CURRENT_DATE)::date + INTERVAL '1 week' + bookings.day_offset * INTERVAL '1 day')::date + bookings.end_time,
       bookings.title,
       'Réservation de démonstration de la flotte.'
FROM companies c
JOIN reservation_status s ON s.code = 'confirmed'
CROSS JOIN (VALUES
  (0, 'VEH-ZOE-02', 'alice.martin@demo-cda.local', TIME '09:00', TIME '12:00', 'Déplacement commercial - Lyon'),
  (0, 'VEH-C3-01', 'claire.moreau@demo-cda.local', TIME '14:00', TIME '17:00', 'Visite de site - Villeurbanne'),
  (1, 'VEH-ZOE-03', 'benoit.durand@demo-cda.local', TIME '09:00', TIME '12:00', 'Rendez-vous partenaire - Grenoble'),
  (1, 'VEH-YARIS-01', 'alice.martin@demo-cda.local', TIME '14:00', TIME '17:00', 'Déplacement équipe - Annecy'),
  (2, 'VEH-308-01', 'claire.moreau@demo-cda.local', TIME '09:00', TIME '12:00', 'Visite client - Chambéry'),
  (2, 'VEH-LEAF-01', 'benoit.durand@demo-cda.local', TIME '14:00', TIME '17:00', 'Déplacement commercial - Valence'),
  (3, 'VEH-KANGOO-01', 'alice.martin@demo-cda.local', TIME '09:00', TIME '12:00', 'Transport de matériel - Saint-Étienne'),
  (3, 'VEH-TRANSIT-01', 'claire.moreau@demo-cda.local', TIME '14:00', TIME '17:00', 'Intervention technique - Bourg-en-Bresse'),
  (4, 'VEH-TESLA-01', 'benoit.durand@demo-cda.local', TIME '09:00', TIME '12:00', 'Rendez-vous direction - Dijon'),
  (4, 'VEH-DUSTER-01', 'alice.martin@demo-cda.local', TIME '14:00', TIME '17:00', 'Déplacement équipe - Mâcon')
) AS bookings(day_offset, resource_code, user_email, start_time, end_time, title)
JOIN resources r ON r.company_id = c.id AND r.code = bookings.resource_code
JOIN users u ON u.company_id = c.id AND u.email = bookings.user_email
WHERE c.name = 'Entreprise Démo CDA'
  AND NOT EXISTS (
    SELECT 1 FROM reservations existing
    WHERE existing.resource_id = r.id
      AND existing.start_at = (date_trunc('week', CURRENT_DATE)::date + INTERVAL '1 week' + bookings.day_offset * INTERVAL '1 day')::date + bookings.start_time
      AND existing.end_at = (date_trunc('week', CURRENT_DATE)::date + INTERVAL '1 week' + bookings.day_offset * INTERVAL '1 day')::date + bookings.end_time
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
