-- Fixtures autonomes de salles et de véhicules pour Entreprise Démo CDA.
-- Peut être exécuté après Reservations.sql, sans Reservations.demo.fixtures.sql.
-- N'ajoute ni utilisateur ni rôle et peut être rejoué sans créer de doublons.

BEGIN;

INSERT INTO companies (name)
VALUES ('Entreprise Démo CDA')
ON CONFLICT (name) DO NOTHING;

-- Quarante salles réparties sur plusieurs bâtiments, capacités et équipements.
INSERT INTO resources (company_id, type_id, state_id, name, code, location, capacity, is_active)
SELECT company.id,
       resource_type.id,
       resource_state.id,
       'Salle de réunion ' || LPAD(rooms.room_number::text, 3, '0'),
       'SALLE-DEMO-' || LPAD(rooms.room_number::text, 3, '0'),
    rooms_location.building || ' - ' || rooms_location.floor,
       4 + (rooms.room_number % 5) * 2,
       TRUE
FROM companies company
CROSS JOIN generate_series(1, 40) AS rooms(room_number)
JOIN resource_types resource_type ON resource_type.name = 'salle'
JOIN resource_states resource_state ON resource_state.label = 'disponible'
CROSS JOIN LATERAL (
    SELECT CASE rooms.room_number % 4
        WHEN 0 THEN 'Bâtiment A'
        WHEN 1 THEN 'Bâtiment B'
        WHEN 2 THEN 'Campus principal'
        ELSE 'Bâtiment C'
    END AS building,
    CASE rooms.room_number % 3
        WHEN 0 THEN 'Rez-de-chaussée'
        WHEN 1 THEN '1er étage'
        ELSE '2e étage'
    END AS floor
) rooms_location
WHERE company.name = 'Entreprise Démo CDA'
ON CONFLICT (company_id, code) DO NOTHING;

INSERT INTO room_details (resource_id, building, floor, has_screen, has_whiteboard, has_projector, available_places)
SELECT resource.id,
       split_part(resource.location, ' - ', 1),
       split_part(resource.location, ' - ', 2),
       room_number % 2 = 0,
       room_number % 3 = 0,
       room_number % 5 = 0,
       resource.capacity
FROM companies company
JOIN resources resource ON resource.company_id = company.id
CROSS JOIN LATERAL (SELECT split_part(resource.code, '-', 3)::int AS room_number) room
WHERE company.name = 'Entreprise Démo CDA'
  AND resource.code LIKE 'SALLE-DEMO-%'
ON CONFLICT (resource_id) DO UPDATE SET
    building = EXCLUDED.building,
    floor = EXCLUDED.floor,
    has_screen = EXCLUDED.has_screen,
    has_whiteboard = EXCLUDED.has_whiteboard,
    has_projector = EXCLUDED.has_projector,
    available_places = EXCLUDED.available_places;

CREATE TEMP TABLE demo_vehicle_models ON COMMIT DROP AS
SELECT * FROM (VALUES
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
) AS models(model_number, vehicle_class, brand, model, fuel_type, capacity, base_location);

INSERT INTO resources (company_id, type_id, state_id, name, code, location, capacity, is_active)
SELECT company.id,
       resource_type.id,
       resource_state.id,
       models.vehicle_class || ' ' || models.brand || ' ' || models.model || ' ' || LPAD(units.unit_number::text, 2, '0'),
       'VEH-FLEET-' || LPAD(models.model_number::text, 2, '0') || '-' || LPAD(units.unit_number::text, 2, '0'),
       CASE units.unit_number % 4
           WHEN 0 THEN models.base_location
           WHEN 1 THEN 'Parking site principal'
           WHEN 2 THEN 'Parking annexe'
           ELSE 'Parking logistique'
       END,
       models.capacity,
       TRUE
FROM companies company
CROSS JOIN demo_vehicle_models models
CROSS JOIN generate_series(1, 10) AS units(unit_number)
JOIN resource_types resource_type ON resource_type.name = 'vehicule'
JOIN resource_states resource_state ON resource_state.label = 'disponible'
WHERE company.name = 'Entreprise Démo CDA'
ON CONFLICT (company_id, code) DO NOTHING;

INSERT INTO vehicle_details (resource_id, license_plate, brand, model, fuel_type, vehicle_class)
SELECT resource.id,
       'DM-' || LPAD(((models.model_number - 1) * 10 + units.unit_number)::text, 3, '0') || '-AA',
       models.brand,
       models.model,
       models.fuel_type,
       models.vehicle_class
FROM companies company
CROSS JOIN demo_vehicle_models models
CROSS JOIN generate_series(1, 10) AS units(unit_number)
JOIN resources resource ON resource.company_id = company.id
    AND resource.code = 'VEH-FLEET-' || LPAD(models.model_number::text, 2, '0') || '-' || LPAD(units.unit_number::text, 2, '0')
WHERE company.name = 'Entreprise Démo CDA'
ON CONFLICT (resource_id) DO UPDATE SET
    license_plate = EXCLUDED.license_plate,
    brand = EXCLUDED.brand,
    model = EXCLUDED.model,
    fuel_type = EXCLUDED.fuel_type,
    vehicle_class = EXCLUDED.vehicle_class;

COMMIT;
