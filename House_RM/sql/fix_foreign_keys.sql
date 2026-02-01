USE house_rm;

-- Drop existing foreign key constraints
ALTER TABLE bookings 
DROP FOREIGN KEY bookings_ibfk_1,
DROP FOREIGN KEY bookings_ibfk_2;

ALTER TABLE maintenance_requests 
DROP FOREIGN KEY maintenance_requests_ibfk_1,
DROP FOREIGN KEY maintenance_requests_ibfk_2;

ALTER TABLE property_images 
DROP FOREIGN KEY property_images_ibfk_1;

-- Recreate foreign key constraints with ON DELETE CASCADE
ALTER TABLE bookings
ADD CONSTRAINT bookings_property_fk
FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
ADD CONSTRAINT bookings_tenant_fk
FOREIGN KEY (tenant_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE maintenance_requests
ADD CONSTRAINT maintenance_property_fk
FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
ADD CONSTRAINT maintenance_tenant_fk
FOREIGN KEY (tenant_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE property_images
ADD CONSTRAINT property_images_property_fk
FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE;
