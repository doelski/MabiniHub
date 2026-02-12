<?php
require_once __DIR__ . '/_bootstrap.php';
header('Content-Type: application/json');
session_start();
require_once '../db.php';

// Check if municipal admin is logged in
if (!isset($_SESSION['municipal_logged_in']) || $_SESSION['municipal_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Ensure approved_by_municipal column exists
try {
    $checkCol = $pdo->query("SHOW COLUMNS FROM leave_requests LIKE 'approved_by_municipal'");
    if ($checkCol->rowCount() === 0) {
        $pdo->exec("ALTER TABLE leave_requests ADD COLUMN approved_by_municipal TINYINT(1) NOT NULL DEFAULT 0");
    }
    
    // Add municipal_approval_date column for history tracking
    $checkDateCol = $pdo->query("SHOW COLUMNS FROM leave_requests LIKE 'municipal_approval_date'");
    if ($checkDateCol->rowCount() === 0) {
        $pdo->exec("ALTER TABLE leave_requests ADD COLUMN municipal_approval_date DATETIME NULL DEFAULT NULL");
    }
} catch (PDOException $e) {
    // If column creation fails, return a clear error
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Database schema error. Please run fix_database.php first.',
        'details' => $e->getMessage()
    ]);
    exit;
}

// Ensure municipal_signatures table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS municipal_signatures (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) NOT NULL UNIQUE,
        file_path VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) {}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;
$action = $data['action'] ?? null; // 'approve' or 'decline'
$signature_data_uri = $data['signature_data_uri'] ?? null;
$decline_reason = $data['decline_reason'] ?? '';
$municipal_email = 'municipaladmin@gmail.com'; // Hardcoded municipal admin email

if (!$id || !$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    // Get leave request details
    $stmt = $pdo->prepare("SELECT lr.*, u.firstname, u.lastname, u.email as emp_email
                           FROM leave_requests lr
                           JOIN users u ON lr.employee_email = u.email
                           WHERE lr.id = ?");
    $stmt->execute([$id]);
    $leave = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$leave) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Leave request not found']);
        exit;
    }
    
    // Get current leave credits from users table (if columns exist)
    $currentVL = 0;
    $currentSL = 0;
    try {
        $creditsStmt = $pdo->prepare("SELECT vacation_leave, sick_leave FROM users WHERE email = ?");
        $creditsStmt->execute([$leave['emp_email']]);
        $credits = $creditsStmt->fetch(PDO::FETCH_ASSOC);
        if ($credits) {
            $currentVL = $credits['vacation_leave'] ?? 0;
            $currentSL = $credits['sick_leave'] ?? 0;
        }
    } catch (PDOException $e) {
        // Columns don't exist, use defaults
        $currentVL = 15; // Default vacation leave
        $currentSL = 15; // Default sick leave
    }

    // Check if already approved by HR
    if ($leave['status'] !== 'approved' || $leave['approved_by_hr'] != 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Leave must be approved by HR first']);
        exit;
    }

    // Check if already processed by municipal (handle if column doesn't exist)
    $already_municipal_approved = isset($leave['approved_by_municipal']) && $leave['approved_by_municipal'] == 1;
    if ($already_municipal_approved) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Leave already processed by municipal admin']);
        exit;
    }

    if ($action === 'approve') {
        // Save municipal signature if provided
        $signature_path = null;
        if (!empty($signature_data_uri) && strpos($signature_data_uri, 'data:image/') === 0) {
            $parts = explode(',', $signature_data_uri, 2);
            if (count($parts) === 2) {
                $meta = $parts[0]; 
                $b64 = $parts[1];
                $ext = 'png';
                if (preg_match('#data:image/(\w+);base64#i', $meta, $m)) { 
                    $ext = strtolower($m[1]); 
                }
                $bin = base64_decode($b64);
                if ($bin !== false) {
                    $ext = $ext === 'jpeg' ? 'jpg' : $ext;
                    $allowed = ['png','jpg','jpeg','gif','webp'];
                    if (in_array($ext, $allowed)) {
                        // Deterministic signature path
                        $hash = sha1($municipal_email);
                        $fileName = 'municipal_sig_' . substr($hash, 0, 24) . '.' . $ext;
                        $relPath = 'uploads/signatures/' . $fileName;
                        $absPath = __DIR__ . '/../' . $relPath;
                        
                        if (!is_dir(dirname($absPath))) {
                            @mkdir(dirname($absPath), 0777, true);
                        }
                        
                        if (@file_put_contents($absPath, $bin) !== false) {
                            $signature_path = $relPath;
                            
                            // Save to municipal_signatures table for reuse
                            try {
                                $ins = $pdo->prepare('INSERT INTO municipal_signatures (email, file_path) VALUES (?, ?) ON DUPLICATE KEY UPDATE file_path = VALUES(file_path)');
                                $ins->execute([$municipal_email, $signature_path]);
                            } catch (PDOException $e) {}
                        }
                    }
                }
            }
        }

        // Calculate leave credits deduction
        $leave_type = strtolower($leave['leave_type']);
        $dates = $leave['dates'];
        
        // Parse dates to calculate days
        $days = 0;
        if (preg_match_all('/\d{4}-\d{2}-\d{2}/', $dates, $matches)) {
            if (count($matches[0]) >= 2) {
                $start = new DateTime($matches[0][0]);
                $end = new DateTime($matches[0][1]);
                $interval = $start->diff($end);
                $days = $interval->days + 1; // Include both start and end date
            } else if (count($matches[0]) == 1) {
                $days = 1;
            }
        }

        // Deduct leave credits from users table
        $vl_deduct = 0;
        $sl_deduct = 0;
        
        if (strpos($leave_type, 'vacation') !== false) {
            $vl_deduct = $days;
        } else if (strpos($leave_type, 'sick') !== false) {
            $sl_deduct = $days;
        }

        // Update user's leave credits (only if columns exist)
        if ($vl_deduct > 0 || $sl_deduct > 0) {
            try {
                $updateUserStmt = $pdo->prepare("UPDATE users SET 
                    vacation_leave = GREATEST(0, COALESCE(vacation_leave, 15) - ?),
                    sick_leave = GREATEST(0, COALESCE(sick_leave, 15) - ?)
                    WHERE email = ?");
                $updateUserStmt->execute([$vl_deduct, $sl_deduct, $leave['emp_email']]);
            } catch (PDOException $e) {
                // If columns don't exist, just log it but don't fail the approval
                error_log("Could not deduct leave credits: " . $e->getMessage());
            }
        }

        // Update leave request - mark as approved by municipal
        $updateStmt = $pdo->prepare("UPDATE leave_requests SET 
            approved_by_municipal = 1,
            municipal_approval_date = NOW(),
            updated_at = NOW()
            WHERE id = ?");
        $updateStmt->execute([$id]);

        // AUTO-CREATE ATTENDANCE RECORDS WITH "ON-LEAVE" STATUS
        // Get employee_id from users table
        $empIdStmt = $pdo->prepare("SELECT employee_id FROM users WHERE email = ?");
        $empIdStmt->execute([$leave['emp_email']]);
        $employee_id = $empIdStmt->fetchColumn();
        
        if ($employee_id) {
            // Parse all dates from the leave request dates field
            if (preg_match_all('/\d{4}-\d{2}-\d{2}/', $dates, $matches)) {
                $leave_dates = [];
                
                if (count($matches[0]) >= 2) {
                    // Date range: generate all dates between start and end
                    $start_date = new DateTime($matches[0][0]);
                    $end_date = new DateTime($matches[0][1]);
                    $end_date->modify('+1 day'); // Include end date
                    
                    $interval = new DateInterval('P1D');
                    $period = new DatePeriod($start_date, $interval, $end_date);
                    
                    foreach ($period as $date) {
                        $leave_dates[] = $date->format('Y-m-d');
                    }
                } else if (count($matches[0]) == 1) {
                    // Single date
                    $leave_dates[] = $matches[0][0];
                }
                
                // Insert/Update attendance records for each leave date
                foreach ($leave_dates as $leave_date) {
                    try {
                        // Use INSERT ... ON DUPLICATE KEY UPDATE to handle existing records
                        $attStmt = $pdo->prepare("
                            INSERT INTO attendance 
                            (employee_id, date, time_in, time_out, time_in_status, time_out_status, status, created_at, updated_at)
                            VALUES (?, ?, NULL, NULL, NULL, NULL, 'on-leave', NOW(), NOW())
                            ON DUPLICATE KEY UPDATE
                                status = 'on-leave',
                                updated_at = NOW()
                        ");
                        $attStmt->execute([$employee_id, $leave_date]);
                    } catch (PDOException $e) {
                        // Log error but don't fail leave approval
                        error_log("Failed to create attendance record for $leave_date: " . $e->getMessage());
                    }
                }
            }
        }

        // Store municipal signature in details JSON
        if ($signature_path) {
            $details = json_decode($leave['details'], true) ?: [];
            if (!isset($details['municipal'])) $details['municipal'] = [];
            $details['municipal']['signature'] = $signature_path;
            $details['municipal']['approved_at'] = date('Y-m-d H:i:s');
            
            $updateDetailsStmt = $pdo->prepare("UPDATE leave_requests SET details = ? WHERE id = ?");
            $updateDetailsStmt->execute([json_encode($details), $id]);
        }

        // Notify employee of final approval
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                recipient_email VARCHAR(150),
                recipient_role VARCHAR(100),
                message TEXT NOT NULL,
                type VARCHAR(50) DEFAULT 'leave',
                is_read TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            
            $msg = sprintf('Your %s leave request has been APPROVED by Municipal Admin. Leave credits have been deducted.', $leave['leave_type']);
            $notifyStmt = $pdo->prepare('INSERT INTO notifications (recipient_email, recipient_role, message, type) VALUES (?, ?, ?, ?)');
            $notifyStmt->execute([$leave['emp_email'], null, $msg, 'leave_approved_by_municipal']);
        } catch (PDOException $e) {}

        echo json_encode([
            'success' => true, 
            'message' => 'Leave approved successfully', 
            'signature_saved' => !empty($signature_path),
            'credits_deducted' => ['vacation' => $vl_deduct, 'sick' => $sl_deduct]
        ]);

    } else if ($action === 'decline') {
        // Decline the leave request
        $updateStmt = $pdo->prepare("UPDATE leave_requests SET 
            status = 'declined',
            approved_by_municipal = 2,
            municipal_approval_date = NOW(),
            decline_reason = ?,
            updated_at = NOW()
            WHERE id = ?");
        $updateStmt->execute([$decline_reason, $id]);

        // Notify employee of decline
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                recipient_email VARCHAR(150),
                recipient_role VARCHAR(100),
                message TEXT NOT NULL,
                type VARCHAR(50) DEFAULT 'leave',
                is_read TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            
            $msg = sprintf('Your %s leave request has been DECLINED by Municipal Admin. Reason: %s', 
                $leave['leave_type'], 
                $decline_reason ?: 'Not specified'
            );
            $notifyStmt = $pdo->prepare('INSERT INTO notifications (recipient_email, recipient_role, message, type) VALUES (?, ?, ?, ?)');
            $notifyStmt->execute([$leave['emp_email'], null, $msg, 'leave_declined_by_municipal']);
        } catch (PDOException $e) {}

        echo json_encode(['success' => true, 'message' => 'Leave declined successfully']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error', 'details' => $e->getMessage()]);
}
?>
