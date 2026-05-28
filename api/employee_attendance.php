<?php
require_once __DIR__ . '/_bootstrap.php';
header('Content-Type: application/json');
require_once __DIR__ . '/../auth_guard.php';
require_api_auth();
require_once __DIR__ . '/../db.php';

try {
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit();
    }

    // Fetch user to get employee_id
    $stmt = $pdo->prepare('SELECT id, firstname, lastname, position, employee_id, email FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit();
    }

    $employeeId = $user['employee_id'] ?? null;
    if (!$employeeId) {
        echo json_encode(['success' => false, 'error' => 'No employee id for user']);
        exit();
    }

    // Fetch attendance for the employee
    $stmt = $pdo->prepare('SELECT id, employee_id, date, am_in, am_out, pm_in, pm_out, status FROM attendance WHERE employee_id = ? ORDER BY date ASC');
    $stmt->execute([$employeeId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Helper format
    $fmtTime = function($dt) {
        if (!$dt) return null;
        try {
            $d = new DateTime($dt);
            return $d->format('h:i A');
        } catch (Exception $e) {
            return null;
        }
    };

    $records = [];
    foreach ($rows as $r) {
        $amInRaw = $r['am_in'];
        $amOutRaw = $r['am_out'];
        $pmInRaw = $r['pm_in'];
        $pmOutRaw = $r['pm_out'];
        
        $amInFmt = $fmtTime($amInRaw);
        $amOutFmt = $fmtTime($amOutRaw);
        $pmInFmt = $fmtTime($pmInRaw);
        $pmOutFmt = $fmtTime($pmOutRaw);
        
        $dbStatus = trim(strtolower($r['status'] ?? '')); // Get database status field
        
        // PRIORITY: Check database status field FIRST for special statuses like on-leave
        if ($dbStatus === 'on-leave' || $dbStatus === 'on leave' || $dbStatus === 'leave') {
            $status = 'On Leave';
        } 
        // Otherwise determine status based on presence of attendance times
        elseif (!$amInRaw && !$pmInRaw) {
            $status = 'Absent';
        } else {
            $status = 'Present';
        }

        $records[] = [
            'id' => (int)$r['id'],
            'date' => $r['date'],
            'amIn' => $amInFmt,
            'amOut' => $amOutFmt,
            'pmIn' => $pmInFmt,
            'pmOut' => $pmOutFmt,
            'status' => $status,
        ];
    }

    // Summary
    $daysPresent = 0; $daysAbsent = 0;
    foreach ($records as $rec) {
        if ($rec['status'] === 'Present' || $rec['status'] === 'On Leave') {
            $daysPresent++;
        } elseif ($rec['status'] === 'Absent') {
            $daysAbsent++;
        }
    }
    $total = count($records);
    $attendanceRate = $total ? round(($daysPresent / $total) * 100) : 0;

    echo json_encode([
        'success' => true,
        'user' => [
            'name' => trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')),
            'position' => $user['position'] ?? '',
            'employee_id' => $employeeId,
            'email' => $user['email'] ?? ''
        ],
        'attendance' => $records,
        'summary' => [
            'daysPresent' => $daysPresent,
            'daysAbsent' => $daysAbsent,
            'attendanceRate' => $attendanceRate
        ],
        'server_time' => (new DateTime())->format('Y-m-d H:i:s')
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
