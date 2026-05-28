<?php
require_once __DIR__ . '/_bootstrap.php';
// api/super_admin_events.php
require_once __DIR__ . '/../auth_guard.php';
require_api_auth('super_admin');
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$data = json_decode(file_get_contents('php://input'), true);
	$action = $data['action'] ?? '';
	if ($action === 'add') {
		$stmt = $pdo->prepare('INSERT INTO events (title, date, time, location, description) VALUES (?, ?, ?, ?, ?)');
		$stmt->execute([
			$data['title'], $data['date'], $data['time'], $data['location'], $data['description']
		]);
		echo json_encode(['success' => true]);
		exit;
	} elseif ($action === 'edit') {
		$stmt = $pdo->prepare('UPDATE events SET title=?, date=?, time=?, location=?, description=? WHERE id=?');
		$stmt->execute([
			$data['title'], $data['date'], $data['time'], $data['location'], $data['description'], $data['id']
		]);
		echo json_encode(['success' => true]);
		exit;
	} elseif ($action === 'archive') {
		// Ensure columns exist
		try {
			$cols = $pdo->query("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'events'")->fetchAll(PDO::FETCH_COLUMN);
			$lower = array_map('strtolower', $cols ?: []);
			if (!in_array('is_archived', $lower)) { $pdo->exec("ALTER TABLE events ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0"); }
			if (!in_array('archived_at', $lower)) { $pdo->exec("ALTER TABLE events ADD COLUMN archived_at DATETIME NULL DEFAULT NULL"); }
		} catch (Throwable $__e) { /* ignore */ }
		$stmt = $pdo->prepare('UPDATE events SET is_archived = 1, archived_at = NOW() WHERE id = ?');
		$stmt->execute([$data['id']]);
		echo json_encode(['success' => true, 'archived' => true]);
		exit;
	} elseif ($action === 'restore') {
		$stmt = $pdo->prepare('UPDATE events SET is_archived = 0, archived_at = NULL WHERE id = ?');
		$stmt->execute([$data['id']]);
		echo json_encode(['success' => true, 'restored' => true]);
		exit;
	} elseif ($action === 'delete_permanent') {
		$stmt = $pdo->prepare('DELETE FROM events WHERE id = ?');
		$stmt->execute([$data['id']]);
		echo json_encode(['success' => true, 'deleted' => true]);
		exit;
	}
}
// Default: GET all events (including archived). Clients may filter as needed.
$events = $pdo->query('SELECT * FROM events ORDER BY date DESC, time DESC, id DESC')->fetchAll();
echo json_encode($events);
