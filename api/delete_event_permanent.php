<?php
require_once __DIR__ . '/_bootstrap.php';
header('Content-Type: application/json');
require_once __DIR__ . '/../auth_guard.php';
require_api_auth(['hr', 'super_admin']);
require_once __DIR__ . '/../db.php';
$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;
if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Missing event id']); exit; }
try {
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success'=>true, 'deleted'=>true]);
} catch (PDOException $e) {
    http_response_code(500); echo json_encode(['success'=>false,'error'=>'Permanent delete failed']);
}
