<?php
/**
 * API: HR Toggle Leave Application
 * Allows HR users to enable/disable leave applications for specific employees
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
$can_apply_leave = $data['can_apply_leave'] ?? null;

if (!$employee_email || $can_apply_leave === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required parameters: employee_email, can_apply_leave']);
    exit;
}

// Validate can_apply_leave is boolean (0 or 1)
$can_apply_leave = $can_apply_leave ? 1 : 0;

try {
    // Update user's can_apply_leave status
    $stmt = $pdo->prepare("UPDATE users SET can_apply_leave = ? WHERE email = ?");
    $stmt->execute([$can_apply_leave, $employee_email]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Employee not found']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Leave application status updated successfully',
        'data' => [
            'employee_email' => $employee_email,
            'can_apply_leave' => $can_apply_leave
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
