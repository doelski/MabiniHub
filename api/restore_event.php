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
    // Ensure columns exist
    try {
        $cols = $pdo->query("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'events'")->fetchAll(PDO::FETCH_COLUMN);
        $lower = array_map('strtolower', $cols ?: []);
        if (!in_array('is_archived', $lower)) { $pdo->exec("ALTER TABLE events ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0"); }
        if (!in_array('archived_at', $lower)) { $pdo->exec("ALTER TABLE events ADD COLUMN archived_at DATETIME NULL DEFAULT NULL"); }
    } catch (Throwable $__e) { /* ignore */ }
    $stmt = $pdo->prepare("UPDATE events SET is_archived = 0, archived_at = NULL WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success'=>true, 'restored'=>true]);
} catch (PDOException $e) {
    http_response_code(500); echo json_encode(['success'=>false,'error'=>'Restore failed']);
}
