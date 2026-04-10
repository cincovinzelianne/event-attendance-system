-- Add qr_code_locked field to students table
-- This field allows admins to lock/unlock student QR codes
-- When locked, students cannot view their QR code
-- When unlocked, the QR code is regenerated

ALTER TABLE students 
ADD COLUMN qr_code_locked TINYINT(1) DEFAULT 0 AFTER qr_code_path;

-- Add index for better query performance
CREATE INDEX idx_qr_code_locked ON students(qr_code_locked);


