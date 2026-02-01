USE house_rm;

CREATE TABLE IF NOT EXISTS maintenance_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT,
    tenant_id INT,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert some test data
INSERT INTO maintenance_requests (property_id, tenant_id, title, description, status, created_at) 
SELECT 
    p.id,
    u.id,
    'Test Maintenance Request',
    'This is a test maintenance request to verify the system functionality.',
    'pending',
    NOW()
FROM 
    properties p
    CROSS JOIN users u
WHERE 
    p.landlord_id = (SELECT id FROM users WHERE username = 'landlord')
    AND u.user_type = 'tenant'
LIMIT 1;
