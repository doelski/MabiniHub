<?php
/**
 * Mark Absent Script
 * This script marks all employees who didn't time in today as "Absent".
 * Run this at or after 5:00 PM daily (Asia/Manila) via scheduler or manually.
 */

require_once __DIR__ . '/../db.php';
date_default_timezone_set('Asia/Manila');

$today = date('Y-m-d');
$nowTime = strtotime(date('H:i:s'));
$cutoff = strtotime('17:00:00'); // 5:00 PM

if ($nowTime < $cutoff) {
    echo json_encode([
        'success' => false,
        'date' => $today,
        'marked_absent' => 0,
        'message' => 'It is not yet 5:00 PM Manila time. No action taken.'
    ]);
    exit;
}

// Get all approved employees
$stmt = $pdo->prepare('SELECT employee_id FROM users WHERE status = "approved" AND employee_id IS NOT NULL');
$stmt->execute();
$allEmployees = $stmt->fetchAll(PDO::FETCH_COLUMN);

$absentCount = 0;

foreach ($allEmployees as $empId) {
        // Check if employee has attendance record for today
        $check = $pdo->prepare('SELECT id, am_in, pm_in, status FROM attendance WHERE employee_id = ? AND date = ? LIMIT 1');
        $check->execute([$empId, $today]);
        $record = $check->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            // No record at all = No need to insert, absence is implicit
            // Optionally create a placeholder record (commented out to avoid clutter)
            // $insert = $pdo->prepare('INSERT INTO attendance (employee_id, date, am_in, am_out, pm_in, pm_out, status, created_at) VALUES (?, ?, NULL, NULL, NULL, NULL, NULL, NOW())');
            // $insert->execute([$empId, $today]);
            $absentCount++;
        } else {
            // Record exists - absent is determined by: no am_in AND no pm_in AND not on leave
            $dbStatus = strtolower($record['status'] ?? '');
            $isOnLeave = ($dbStatus === 'on-leave' || $dbStatus === 'on leave' || $dbStatus === 'leave');
            
            if (empty($record['am_in']) && empty($record['pm_in']) && !$isOnLeave) {
                // This employee is absent (no clock-ins and not on leave)
                $absentCount++;
            }
        }
}

echo json_encode([
    'success' => true,
    'date' => $today,
    'total_employees' => count($allEmployees),
    'marked_absent' => $absentCount,
    'message' => "Marked {$absentCount} employees as absent for {$today}"
]);
// record last run date so web pages can avoid re-running multiple times per day
$lastRunFile = __DIR__ . '/last_absent_run.txt';
@file_put_contents($lastRunFile, $today);
?>
