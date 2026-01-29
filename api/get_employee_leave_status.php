<?php
/**
 * API: Get Employee Leave Status
 * Returns whether an employee can apply for leave and their custom leave credits
 */
require_once __DIR__ . '/_bootstrap.php';
header('Content-Type: application/json');
require_once __DIR__ . '/../auth_guard.php';
require_api_auth(['hr', 'department_head', 'employee']);
require_once '../db.php';

$employee_email = $_GET['email'] ?? null;

if (!$employee_email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing email parameter']);
    exit;
}

try {
    // Get user's can_apply_leave status
    $stmt = $pdo->prepare("SELECT can_apply_leave FROM users WHERE email = ?");
    $stmt->execute([$employee_email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Employee not found']);
        exit;
    }

    // Get custom leave credits overrides if they exist
    $stmt = $pdo->prepare("SELECT leave_type, override_credits FROM employee_leave_credits_override WHERE employee_email = ?");
    $stmt->execute([$employee_email]);
    $overrides = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $overrides_map = [];
    foreach ($overrides as $override) {
        $overrides_map[$override['leave_type']] = $override['override_credits'];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'can_apply_leave' => (int)$user['can_apply_leave'],
            'leave_credits_overrides' => $overrides_map
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
