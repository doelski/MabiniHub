<?php
require_once __DIR__ . '/_bootstrap.php';
header('Content-Type: application/json');
require_once __DIR__ . '/../auth_guard.php';
require_api_auth(['hr', 'department_head']);
require_once __DIR__ . '/../db.php';

$byEmail = $_SESSION['email'] ?? null;
$role = strtolower($_SESSION['role'] ?? $_SESSION['position'] ?? '');
if (!$byEmail) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$data = $_POST;
if (empty($data)) {
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
}

$id = isset($data['id']) ? (int)$data['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid id']);
    exit;
}

try {
    // Ensure archive columns exist (idempotent)
    try {
        $cols = $pdo->query("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'leave_requests'")->fetchAll(PDO::FETCH_COLUMN);
        $lower = array_map('strtolower', $cols ?: []);
        if (!in_array('is_archived', $lower)) { $pdo->exec("ALTER TABLE leave_requests ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0"); }
        if (!in_array('archived_at', $lower)) { $pdo->exec("ALTER TABLE leave_requests ADD COLUMN archived_at DATETIME NULL DEFAULT NULL"); }
    } catch (Throwable $__e) { /* ignore */ }

    // HR can archive any leave; Dept Head can archive only those assigned to them
    if ($role === 'hr') {
        $stmt = $pdo->prepare('UPDATE leave_requests SET is_archived = 1, archived_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);
    } else {
        $stmt = $pdo->prepare('UPDATE leave_requests SET is_archived = 1, archived_at = NOW() WHERE id = ? AND dept_head_email = ?');
        $stmt->execute([$id, $byEmail]);
    }
    if ($stmt->rowCount() === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Not allowed or leave request not found']);
        exit;
    }
    echo json_encode(['success' => true, 'archived' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
