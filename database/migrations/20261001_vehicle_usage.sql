BEGIN;

CREATE TABLE IF NOT EXISTS vehicle_usage (
  reservation_id INT PRIMARY KEY REFERENCES reservations(id) ON DELETE CASCADE,
  vehicle_id INT REFERENCES resources(id) ON DELETE RESTRICT,
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

COMMIT;