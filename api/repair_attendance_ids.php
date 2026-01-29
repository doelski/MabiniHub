<?php
require_once __DIR__ . '/_bootstrap.php';
header('Content-Type: application/json');
require_once __DIR__ . '/../auth_guard.php';
require_api_auth();
require_role(['hr','superadmin']);
require_once __DIR__ . '/../db.php';

try {
    // Update attendance rows where employee_id is numeric and equals users.id
    // Cast users.id to CHAR to compare with attendance.employee_id (likely VARCHAR)
    $sql = 'UPDATE attendance a
            JOIN users u ON a.employee_id = CAST(u.id AS CHAR)
            SET a.employee_id = u.employee_id
            WHERE a.employee_id REGEXP "^[0-9]+$"';
    $affected = $pdo->exec($sql);

    echo json_encode([
        'success' => true,
        'updated_rows' => (int)$affected,
        'message' => 'Mapped numeric employee_id to canonical users.employee_id'
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
