<?php
require_once __DIR__ . '/_bootstrap.php';
header('Content-Type: application/json');
require_once __DIR__ . '/../auth_guard.php';
require_api_auth();
require_once __DIR__ . '/../db.php';

$email = $_SESSION['email'] ?? null;
if (!$email) {
    echo json_encode(['logged_in' => false]);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT firstname, lastname, mi, department, position, email, gender, can_apply_leave FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($u) {
        // Ensure can_apply_leave is set (default to 1 if column doesn't exist yet)
        if (!isset($u['can_apply_leave'])) {
            $u['can_apply_leave'] = 1;
        }
        echo json_encode(array_merge(['logged_in' => true], $u));
        exit;
    }
    echo json_encode(['logged_in' => false]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error']);
}