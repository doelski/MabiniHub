<?php
require_once __DIR__ . '/_bootstrap.php';
header('Content-Type: application/json');
require_once __DIR__ . '/../auth_guard.php';
require_api_auth(['hr', 'department_head']);
require_once __DIR__ . '/../db.php';

$byEmail = $_SESSION['email'] ?? null;
$role = strtolower($_SESSION['role'] ?? $_SESSION['position'] ?? '');
if (!$byEmail) { http_response_code(401); echo json_encode(['success'=>false,'error'=>'Not authenticated']); exit; }

$data = $_POST; if (empty($data)) { $data = json_decode(file_get_contents('php://input'), true) ?? []; }
$id = isset($data['id']) ? (int)$data['id'] : 0;
if ($id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Invalid id']); exit; }

try {
    if ($role === 'hr') {
        $stmt = $pdo->prepare('UPDATE leave_requests SET is_archived = 0, archived_at = NULL WHERE id = ?');
        $stmt->execute([$id]);
    } else {
        $stmt = $pdo->prepare('UPDATE leave_requests SET is_archived = 0, archived_at = NULL WHERE id = ? AND dept_head_email = ?');
        $stmt->execute([$id, $byEmail]);
    }
    if ($stmt->rowCount() === 0) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Not allowed or leave request not found']); exit; }
    echo json_encode(['success'=>true, 'restored'=>true]);
} catch (PDOException $e) {
    http_response_code(500); echo json_encode(['success'=>false,'error'=>'Database error']);
}
