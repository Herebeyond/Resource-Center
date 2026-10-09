BEGIN;

ALTER TABLE equipment_details ADD COLUMN IF NOT EXISTS model VARCHAR(120);

CREATE TEMP TABLE demo_equipment_models ON COMMIT DROP AS
SELECT * FROM (VALUES
  (1, 'portable', 'Dell', 'Latitude 5440', 'informatique', 'Armoire informatique'),
  (2, 'portable', 'Lenovo', 'ThinkPad T14', 'informatique', 'Armoire informatique'),
  (3, 'portable', 'Apple', 'MacBook Air 13', 'informatique', 'Armoire informatique'),
  (4, 'audiovisuel', 'Epson', 'EB-FH52', 'vidéoprojection', 'Réserve audiovisuelle'),
  (5, 'audiovisuel', 'BenQ', 'TH685', 'vidéoprojection', 'Réserve audiovisuelle'),
  (6, 'equipement', 'Canon', 'EOS R50', 'photo', 'Studio audiovisuel'),
  (7, 'equipement', 'Nikon', 'Z50', 'photo', 'Studio audiovisuel'),
  (8, 'equipement', 'Sony', 'AX43', 'vidéo', 'Studio audiovisuel'),
  (9, 'equipement', 'Logitech', 'Brio', 'visioconférence', 'Armoire informatique'),
  (10, 'equipement', 'Jabra', 'PanaCast', 'visioconférence', 'Réserve audiovisuelle'),
  (11, 'equipement', 'Shure', 'BLX24', 'audio', 'Studio audiovisuel'),
  (12, 'equipement', 'Zoom', 'H6', 'audio', 'Studio audiovisuel'),
  (13, 'equipement', 'JBL', 'Charge 5', 'audio', 'Réserve événementielle'),
  (14, 'equipement', 'Apple', 'iPad Air', 'informatique', 'Armoire informatique'),
  (15, 'equipement', 'ASUS', 'ZenScreen', 'informatique', 'Armoire informatique'),
  (16, 'equipement', 'TP-Link', 'Archer AX55', 'réseau', 'Local réseau'),
  (17, 'equipement', 'LaCie', 'Rugged 2 To', 'stockage', 'Armoire informatique'),
  (18, 'equipement', 'Bosch', 'GSR 18V', 'outillage', 'Atelier'),
  (19, 'equipement', 'Fluke', '117', 'mesure', 'Atelier'),
  (20, 'equipement', 'Aputure', 'Amaran 200d', 'éclairage', 'Studio audiovisuel')
) AS models(model_number, resource_type, brand, model, category, base_location);

INSERT INTO resources (company_id, type_id, state_id, name, code, location, is_active)
SELECT company.id, resource_type.id, state.id,
       models.brand || ' ' || models.model || ' ' || LPAD(units.unit_number::text, 2, '0'),
       'EQP-DEMO-' || LPAD(models.model_number::text, 2, '0') || '-' || LPAD(units.unit_number::text, 2, '0'),
       CASE units.unit_number % 3
         WHEN 1 THEN models.base_location
         WHEN 2 THEN 'Réserve annexe'
         ELSE 'Site secondaire'
       END,
       TRUE
FROM companies company
CROSS JOIN demo_equipment_models models
CROSS JOIN generate_series(1, 10) AS units(unit_number)
JOIN resource_types resource_type ON resource_type.name = models.resource_type
JOIN resource_states state ON state.label = 'disponible'
WHERE company.name = 'Entreprise Démo CDA'
ON CONFLICT (company_id, code) DO NOTHING;

INSERT INTO equipment_details (resource_id, serial_number, brand, model, category)
SELECT resource.id,
       'SN-DEMO-' || LPAD(models.model_number::text, 2, '0') || '-' || LPAD(units.unit_number::text, 2, '0'),
       models.brand, models.model, models.category
FROM companies company
CROSS JOIN demo_equipment_models models
CROSS JOIN generate_series(1, 10) AS units(unit_number)
JOIN resources resource ON resource.company_id = company.id
  AND resource.code = 'EQP-DEMO-' || LPAD(models.model_number::text, 2, '0') || '-' || LPAD(units.unit_number::text, 2, '0')
WHERE company.name = 'Entreprise Démo CDA'
ON CONFLICT (resource_id) DO UPDATE SET
    serial_number = EXCLUDED.serial_number,
    brand = EXCLUDED.brand,
    model = EXCLUDED.model,
    category = EXCLUDED.category;

INSERT INTO reservations (company_id, resource_id, user_id, status_id, start_at, end_at, title, notes)
SELECT company.id, resource.id, demo_user.id, status.id,
       CURRENT_DATE + TIME '10:00', CURRENT_DATE + TIME '13:00',
       'Test exemplaires - ' || models.brand || ' ' || models.model,
       'Réservation de démonstration pour tester la disponibilité par exemplaire.'
FROM companies company
CROSS JOIN demo_equipment_models models
JOIN resources resource ON resource.company_id = company.id
  AND resource.code = 'EQP-DEMO-' || LPAD(models.model_number::text, 2, '0') || '-01'
JOIN users demo_user ON demo_user.company_id = company.id AND demo_user.email = 'alice.martin@demo-cda.local'
JOIN reservation_status status ON status.code = 'confirmed'
WHERE company.name = 'Entreprise Démo CDA'
  AND NOT EXISTS (
    SELECT 1 FROM reservations existing
    JOIN reservation_status existing_status ON existing_status.id = existing.status_id
    WHERE existing.resource_id = resource.id AND existing_status.is_blocking = TRUE
      AND existing.start_at < CURRENT_DATE + TIME '13:00'
      AND existing.end_at > CURRENT_DATE + TIME '10:00'
  )
  AND NOT EXISTS (
    SELECT 1 FROM maintenance_events maintenance
    WHERE maintenance.resource_id = resource.id
      AND maintenance.start_at < CURRENT_DATE + TIME '13:00'
      AND maintenance.end_at > CURRENT_DATE + TIME '10:00'
  );

COMMIT;