<?php
require_once __DIR__ . '/_bootstrap.php';
// api/super_admin_dept_heads.php
require_once __DIR__ . '/../auth_guard.php';
require_api_auth('super_admin');
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query(
        "SELECT id, firstname, lastname, email, department, position, status
         FROM users
         WHERE role = 'department_head'
         ORDER BY department, lastname, firstname"
    );
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load department heads.']);
}
