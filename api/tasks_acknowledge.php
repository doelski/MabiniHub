<?php
require_once __DIR__ . '/_bootstrap.php';
header('Content-Type: application/json');
require_once __DIR__ . '/../auth_guard.php';
require_api_auth(['employee', 'hr', 'department_head']);
require_once __DIR__ . '/../db.php';

$email = $_SESSION['email'] ?? null;
if (!$email) { http_response_code(401); echo json_encode(['success'=>false,'error'=>'Not authenticated']); exit; }

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$note = trim($_POST['note'] ?? '');
if ($id <= 0 || $note === '') { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Description is required']); exit; }

// Ensure columns exist (safety)
try {
  $pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
  due_date DATETIME DEFAULT NULL,
    status ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
    assigned_to_email VARCHAR(100) NOT NULL,
    assigned_by_email VARCHAR(100) NOT NULL,
    attachment_path VARCHAR(255) DEFAULT NULL,
    submission_file_path VARCHAR(255) DEFAULT NULL,
    submission_note TEXT DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    ack_note TEXT DEFAULT NULL,
    ack_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
  )");
} catch (PDOException $e) {}

try {
  // Only allow acknowledging tasks assigned to the current employee
  // Save note into submission_note and set status to in_progress
  $stmt = $pdo->prepare("UPDATE tasks SET submission_note = ?, status = 'in_progress', updated_at = NOW() WHERE id = ? AND assigned_to_email = ?");
  $stmt->execute([$note, $id, $email]);
  if ($stmt->rowCount() === 0) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Not allowed or task not found']); exit; }
  echo json_encode(['success'=>true]);
} catch (PDOException $e) {
  http_response_code(500); echo json_encode(['success'=>false,'error'=>'Database error']);
}
