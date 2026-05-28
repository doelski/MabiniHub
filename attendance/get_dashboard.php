<?php
require_once __DIR__ . '/../auth_guard.php';
require_api_auth(['hr', 'department_head', 'employee']);
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

$dept = $_GET['department'] ?? '';
$today = date('Y-m-d');

// Total employees (approved only)
$totSql = 'SELECT COUNT(*) FROM users WHERE status = "approved"';
$totParams = [];
if ($dept) {
    $totSql .= ' AND department = ?';
    $totParams[] = $dept;
}
$totStmt = $pdo->prepare($totSql);
$totStmt->execute($totParams);
$total = $totStmt->fetchColumn();

// Present count (has am_in OR pm_in, excluding on-leave)
$presentSql = 'SELECT COUNT(DISTINCT a.employee_id) FROM attendance a 
               JOIN users u ON a.employee_id = u.employee_id 
               WHERE a.date = ? 
               AND (a.am_in IS NOT NULL OR a.pm_in IS NOT NULL)
               AND (a.status IS NULL OR a.status != "on-leave")';
$presentParams = [$today];
if ($dept) {
    $presentSql .= ' AND u.department = ?';
    $presentParams[] = $dept;
}
$presentStmt = $pdo->prepare($presentSql);
$presentStmt->execute($presentParams);
$present = $presentStmt->fetchColumn();

// On Leave count
$leaveSql = 'SELECT COUNT(DISTINCT a.employee_id) FROM attendance a 
             JOIN users u ON a.employee_id = u.employee_id 
             WHERE a.date = ? AND a.status = "on-leave"';
$leaveParams = [$today];
if ($dept) {
    $leaveSql .= ' AND u.department = ?';
    $leaveParams[] = $dept;
}
$leaveStmt = $pdo->prepare($leaveSql);
$leaveStmt->execute($leaveParams);
$onLeave = $leaveStmt->fetchColumn();

// Late count (clocked in after 7:00 AM)
$lateSql = 'SELECT COUNT(DISTINCT a.employee_id) FROM attendance a 
            JOIN users u ON a.employee_id = u.employee_id 
            WHERE a.date = ? 
            AND a.am_in IS NOT NULL 
            AND TIME(a.am_in) > "07:00:00"
            AND (a.status IS NULL OR a.status != "on-leave")';
$lateParams = [$today];
if ($dept) {
    $lateSql .= ' AND u.department = ?';
    $lateParams[] = $dept;
}
$lateStmt = $pdo->prepare($lateSql);
$lateStmt->execute($lateParams);
$late = $lateStmt->fetchColumn();

// Active = Present + On Leave (anyone with attendance record)
$active = intval($present) + intval($onLeave);

// Absent = total - active
$absent = max(0, intval($total) - intval($active));

echo json_encode([
    'success' => true,
    'total_employees' => intval($total),
    'present' => intval($present),
    'late' => intval($late),
    'on_leave' => intval($onLeave),
    'active' => intval($active),
    'absent' => intval($absent),
    'date' => $today,
]);
