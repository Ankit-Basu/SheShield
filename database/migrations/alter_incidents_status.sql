-- Alter incidents table to modify status column
ALTER TABLE incidents
MODIFY COLUMN status VARCHAR(20) DEFAULT 'pending';