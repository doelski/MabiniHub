<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../auth_guard.php';
require_api_auth('hr');
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

// Check if user is logged in and is HR
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$today = date('Y-m-d');

try {
    // Get counts by employee position for ALL departments (no filtering)
    $categories = ['Permanent', 'Casual', 'JO', 'OJT'];
    $stats = [];
    
    foreach ($categories as $category) {
        // Total count for this category (all departments)
        $totalSql = 'SELECT COUNT(*) FROM users WHERE status = "approved" AND position = ?';
        $totalStmt = $pdo->prepare($totalSql);
        $totalStmt->execute([$category]);
        $total = $totalStmt->fetchColumn();
        
        // Active count (has am_in OR pm_in) for this category today (all departments)
        $activeSql = 'SELECT COUNT(DISTINCT a.employee_id) 
                      FROM attendance a 
                      JOIN users u ON a.employee_id = u.employee_id 
                      WHERE a.date = ? 
                      AND (a.am_in IS NOT NULL OR a.pm_in IS NOT NULL)
                      AND u.position = ?
                      AND u.status = "approved"';
        $activeStmt = $pdo->prepare($activeSql);
        $activeStmt->execute([$today, $category]);
        $active = $activeStmt->fetchColumn();
        
        $stats[$category] = [
            'total' => (int)$total,
            'active' => (int)$active
        ];
    }
    
    // Get overall totals (all departments)
    $totalEmployeesSql = 'SELECT COUNT(*) FROM users WHERE status = "approved"';
    $totalStmt = $pdo->prepare($totalEmployeesSql);
    $totalStmt->execute();
    $totalEmployees = $totalStmt->fetchColumn();
    
    // Get overall active count (has am_in OR pm_in) for all departments
    $overallActiveSql = 'SELECT COUNT(DISTINCT a.employee_id) 
                         FROM attendance a 
                         JOIN users u ON a.employee_id = u.employee_id 
                         WHERE a.date = ? 
                         AND (a.am_in IS NOT NULL OR a.pm_in IS NOT NULL)
                         AND u.status = "approved"';
    $overallActiveStmt = $pdo->prepare($overallActiveSql);
    $overallActiveStmt->execute([$today]);
    $overallActive = $overallActiveStmt->fetchColumn();
    
    // Get present count (has am_in OR pm_in, not on leave)
    $presentSql = 'SELECT COUNT(DISTINCT a.employee_id) 
                   FROM attendance a 
                   JOIN users u ON a.employee_id = u.employee_id 
                   WHERE a.date = ? 
                   AND (a.am_in IS NOT NULL OR a.pm_in IS NOT NULL)
                   AND (a.status IS NULL OR a.status != "on-leave")
                   AND u.status = "approved"';
    $presentStmt = $pdo->prepare($presentSql);
    $presentStmt->execute([$today]);
    $present = $presentStmt->fetchColumn();
    
    // Late count no longer tracked - always 0
    $late = 0;
    
    // Absent count
    $absent = (int)$totalEmployees - (int)$overallActive;
    
    echo json_encode([
        'success' => true,
        'date' => $today,
        'categories' => $stats,
        'overall' => [
            'total' => (int)$totalEmployees,
            'active' => (int)$overallActive,
            'present' => (int)$present,
            'late' => (int)$late,
            'absent' => max(0, $absent)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
