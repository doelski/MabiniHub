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
    $stmt = $pdo->prepare('SELECT id, employee_id, date, time_in, time_out, time_in_status, time_out_status FROM attendance WHERE employee_id = ? ORDER BY date ASC');
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
        $timeInFmt = $fmtTime($r['time_in']);
        $timeOutFmt = $fmtTime($r['time_out']);
        $timeInStatus = $r['time_in_status'] ?? null;
        $timeOutStatus = $r['time_out_status'] ?? null;

        // Flags
        $tardy = ($timeInStatus === 'Late');
        $undertime = ($timeOutStatus === 'Undertime');
        $overtime = ($timeOutStatus === 'Overtime');

        $records[] = [
            'id' => (int)$r['id'],
            'date' => $r['date'],
            'timeIn' => $timeInFmt,
            'timeOut' => $timeOutFmt,
            'timeInStatus' => $timeInStatus,
            'timeOutStatus' => $timeOutStatus,
            'tardy' => $tardy,
            'undertime' => $undertime,
            'overtime' => $overtime,
        ];
    }

    // Summary
    $daysPresent = 0; $daysLate = 0; $daysAbsent = 0; $totalTardy = 0; $totalUndertime = 0; $totalOvertime = 0;
    foreach ($records as $rec) {
        if ($rec['timeInStatus'] === 'Present') $daysPresent++;
        elseif ($rec['timeInStatus'] === 'Late') $daysLate++;
        if ($rec['timeInStatus'] === 'Absent' || !$rec['timeIn']) $daysAbsent++;
        if ($rec['tardy']) $totalTardy++;
        if ($rec['undertime']) $totalUndertime++;
        if ($rec['overtime']) $totalOvertime++;
    }
    $total = count($records);
    $daysActive = $daysPresent + $daysLate;
    $attendanceRate = $total ? round(($daysActive / $total) * 100) : 0;

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
            'daysLate' => $daysLate,
            'daysActive' => $daysActive,
            'daysAbsent' => $daysAbsent,
            'totalTardy' => $totalTardy,
            'totalUndertime' => $totalUndertime,
            'totalOvertime' => $totalOvertime,
            'attendanceRate' => $attendanceRate
        ],
        'server_time' => (new DateTime())->format('Y-m-d H:i:s')
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
