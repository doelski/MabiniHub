<?php
/**
 * API: HR Update Leave Credits
 * Allows HR users to manually update employee leave credits
 */
require_once __DIR__ . '/_bootstrap.php';
header('Content-Type: application/json');
require_once __DIR__ . '/../auth_guard.php';
require_api_auth(['hr']);
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$employee_email = $data['employee_email'] ?? null;
$leave_type = $data['leave_type'] ?? null;
$new_credits = $data['new_credits'] ?? null;

if (!$employee_email || !$leave_type || $new_credits === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required parameters: employee_email, leave_type, new_credits']);
    exit;
}

// Validate new_credits is numeric and non-negative
if (!is_numeric($new_credits) || $new_credits < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Credits must be a non-negative number']);
    exit;
}

// Map leave type to database column (if using direct columns in users table)
// For now, we'll store custom leave credits in a new table: employee_leave_credits_override
try {
    // Create table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS employee_leave_credits_override (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_email VARCHAR(100) NOT NULL,
        leave_type VARCHAR(255) NOT NULL,
        override_credits DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        updated_by VARCHAR(100) NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_employee_leave (employee_email, leave_type),
        INDEX idx_employee (employee_email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Get HR user email
    $hr_email = $_SESSION['email'] ?? 'unknown';

    // Insert or update override
    $stmt = $pdo->prepare("
        INSERT INTO employee_leave_credits_override 
        (employee_email, leave_type, override_credits, updated_by) 
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        override_credits = VALUES(override_credits),
        updated_by = VALUES(updated_by),
        updated_at = CURRENT_TIMESTAMP
    ");
    
    $stmt->execute([$employee_email, $leave_type, $new_credits, $hr_email]);

    echo json_encode([
        'success' => true,
        'message' => 'Leave credits updated successfully',
        'data' => [
            'employee_email' => $employee_email,
            'leave_type' => $leave_type,
            'new_credits' => $new_credits
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
