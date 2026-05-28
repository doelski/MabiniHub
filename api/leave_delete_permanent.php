<?php
require_once __DIR__ . '/_bootstrap.php';
header('Content-Type: application/json');
require_once __DIR__ . '/../auth_guard.php';
require_api_auth('hr');
require_once __DIR__ . '/../db.php';

$byEmail = $_SESSION['email'] ?? null;
$role = strtolower($_SESSION['role'] ?? $_SESSION['position'] ?? '');
if (!$byEmail) { http_response_code(401); echo json_encode(['success'=>false,'error'=>'Not authenticated']); exit; }
if ($role !== 'hr') { http_response_code(403); echo json_encode(['success'=>false,'error'=>'HR role required']); exit; }

$data = $_POST; if (empty($data)) { $data = json_decode(file_get_contents('php://input'), true) ?? []; }
$id = isset($data['id']) ? (int)$data['id'] : 0;
if ($id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Invalid id']); exit; }

try {
    // Optional: ensure archive columns exist for consistency (no-op here)
    $stmt = $pdo->prepare('DELETE FROM leave_requests WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->rowCount() === 0) { http_response_code(404); echo json_encode(['success'=>false,'error'=>'Leave request not found']); exit; }
    echo json_encode(['success'=>true, 'deleted'=>true]);
} catch (PDOException $e) {
    http_response_code(500); echo json_encode(['success'=>false,'error'=>'Database error']);
}
