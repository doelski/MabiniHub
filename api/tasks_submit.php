<?php
require_once __DIR__ . '/_bootstrap.php';
header('Content-Type: application/json');
require_once __DIR__ . '/../auth_guard.php';
require_api_auth(['employee', 'hr', 'department_head']);
require_once __DIR__ . '/../db.php';

$email = $_SESSION['email'] ?? null; // employee email
if (!$email) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Ensure tasks table and new columns exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
    due_date DATETIME DEFAULT NULL,
        status ENUM('pending','in_progress','completed','missed') NOT NULL DEFAULT 'pending',
        assigned_to_email VARCHAR(100) NOT NULL,
        assigned_by_email VARCHAR(100) NOT NULL,
        attachment_path VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
    )");
    // Add submission columns if missing
    $checkCols = $pdo->prepare("SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tasks'");
    $checkCols->execute();
    $cols = array_map(fn($r) => $r['column_name'], $checkCols->fetchAll(PDO::FETCH_ASSOC));
    if (!in_array('submission_file_path', $cols)) {
        $pdo->exec("ALTER TABLE tasks ADD COLUMN submission_file_path VARCHAR(255) DEFAULT NULL");
    }
    if (!in_array('submission_note', $cols)) {
        $pdo->exec("ALTER TABLE tasks ADD COLUMN submission_note TEXT DEFAULT NULL");
    }
    if (!in_array('completed_at', $cols)) {
        $pdo->exec("ALTER TABLE tasks ADD COLUMN completed_at DATETIME DEFAULT NULL");
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to prepare tasks table']);
    exit;
}

$taskId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$note = trim($_POST['note'] ?? '');
$mode = trim($_POST['mode'] ?? '');
if ($taskId <= 0 || $note === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Task and description are required']);
    exit;
}

// Validate ownership
try {
    $s = $pdo->prepare('SELECT id FROM tasks WHERE id = ? AND assigned_to_email = ?');
    $s->execute([$taskId, $email]);
    if (!$s->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Task not found or not assigned to you']);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit;
}

// Require a file upload
// Note: For missed tasks we allow note-only iaDJUST requests (file optional). For normal submissions, file is required.
// Handle file upload (for normal submission we require success)
$submission_path = null;
$fileProvided = !(empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK);
if ($fileProvided) {
    $tmpPath = $_FILES['file']['tmp_name'];
    $origName = basename($_FILES['file']['name']);
    $ext = pathinfo($origName, PATHINFO_EXTENSION);
    $safeExt = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
    $targetDir = __DIR__ . '/../uploads/task_submissions/';
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0775, true);
    }
    if (!is_dir($targetDir) || !is_writable($targetDir)) {
        // Directory issue; keep path null
        $fileProvided = false; // treat as failure
    } else {
        $fileName = uniqid('submission_') . ($safeExt ? ('.' . $safeExt) : '');
        $dest = $targetDir . $fileName;
        if (@move_uploaded_file($tmpPath, $dest)) {
            $submission_path = 'uploads/task_submissions/' . $fileName;
        } else {
            $fileProvided = false; // move failed
        }
    }
}

try {
    // Check current task status to decide behavior
    $check = $pdo->prepare('SELECT status, due_date, submission_note, assigned_by_email FROM tasks WHERE id = ? AND assigned_to_email = ?');
    $check->execute([$taskId, $email]);
    $row = $check->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Task not found']);
        exit;
    }

    $currentStatus = $row['status'] ?? '';
    $currentDue = $row['due_date'] ?? null;
    $existingNote = $row['submission_note'] ?? '';

    // If client explicitly requested append mode, treat as an iaDJUST (note-only) request.
    if ($mode === 'append' || $currentStatus === 'missed') {
        // Get employee name and position for the appended note
        $employeeName = $email;
        $employeePosition = '';
        try {
            $u = $pdo->prepare('SELECT firstname, lastname FROM users WHERE email = ? LIMIT 1');
            $u->execute([$email]);
            $ur = $u->fetch(PDO::FETCH_ASSOC);
            if ($ur) { $employeeName = trim(($ur['firstname'] ?? '') . ' ' . ($ur['lastname'] ?? '')); }
        } catch (PDOException $e) { /* ignore */ }
        try {
            $p = $pdo->prepare('SELECT position FROM employees WHERE email = ? LIMIT 1');
            $p->execute([$email]);
            $pr = $p->fetch(PDO::FETCH_ASSOC);
            if ($pr && !empty($pr['position'])) { $employeePosition = $pr['position']; }
        } catch (PDOException $e) { /* ignore */ }

        $timestamp = (new DateTime())->format('Y-m-d H:i:s');
        // Employee submission on a missed task -> append adjustment note (with name, email, position, timestamp) and extend due date by 1 day
        // Store the adjustment note separately so it's only visible to department heads.
        try {
            // ensure adjustment_note column exists
            $checkCols = $pdo->prepare("SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tasks'");
            $checkCols->execute();
            $cols = array_map(fn($r) => $r['column_name'], $checkCols->fetchAll(PDO::FETCH_ASSOC));
            if (!in_array('adjustment_note', $cols)) {
                $pdo->exec("ALTER TABLE tasks ADD COLUMN adjustment_note TEXT DEFAULT NULL");
            }
        } catch (PDOException $e) {
            // ignore
        }

        // Save the plain note (no name meta) into adjustment_note. Do NOT change status or due_date here.
        $stmt = $pdo->prepare('UPDATE tasks SET adjustment_note = ? WHERE id = ? AND assigned_to_email = ?');
        $stmt->execute([$note, $taskId, $email]);

        // Create a notification for the assigning department head (note only, no name meta)
        // Do NOT create a notification if the recipient would be the submitting employee.
        $deptHeadEmail = $row['assigned_by_email'] ?? null;
        if ($deptHeadEmail && $deptHeadEmail !== $email) {
            try {
                    // ensure notifications table exists (with role support and is_read flag)
                    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        recipient_email VARCHAR(150),
                        recipient_role VARCHAR(100),
                        message TEXT NOT NULL,
                        type VARCHAR(50) DEFAULT 'task',
                        is_read TINYINT(1) DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )");
                    $msg = "Adjustment request for task #{$taskId}: {$note}";
                    $ins = $pdo->prepare('INSERT INTO notifications (recipient_email, recipient_role, message, type) VALUES (?, ?, ?, ?)');
                    $ins->execute([$deptHeadEmail, null, $msg, 'task_adjust']);
            } catch (PDOException $e) { /* ignore notification errors */ }
        }

        echo json_encode(['success' => true, 'file' => $submission_path, 'adjustment_note' => $note]);
        exit;
    } else {
        // Normal submission: mark completed
        // For normal submissions, file is required
        if (!$submission_path) {
            http_response_code(400);
            $errMsg = $fileProvided ? 'Failed to store uploaded file' : 'File is required for normal submission';
            echo json_encode(['success' => false, 'error' => $errMsg]);
            exit;
        }
        $stmt = $pdo->prepare('UPDATE tasks SET status = "completed", submission_file_path = ?, submission_note = ?, completed_at = NOW() WHERE id = ? AND assigned_to_email = ?');
        $stmt->execute([$submission_path, $note, $taskId, $email]);

        // Notify the assigning department head that the task was completed
        try {
            $deptHeadEmail = $row['assigned_by_email'] ?? null;
            if ($deptHeadEmail && $deptHeadEmail !== $email) {
                $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    recipient_email VARCHAR(150),
                    recipient_role VARCHAR(100),
                    message TEXT NOT NULL,
                    type VARCHAR(50) DEFAULT 'task',
                    is_read TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                $msg = "Task #{$taskId} submitted/completed by {$email}";
                $ins = $pdo->prepare('INSERT INTO notifications (recipient_email, recipient_role, message, type) VALUES (?, ?, ?, ?)');
                $ins->execute([$deptHeadEmail, null, $msg, 'task_completed']);
            }
        } catch (PDOException $e) { /* ignore */ }

        echo json_encode(['success' => true, 'file' => $submission_path]);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update task']);
}
