<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

date_default_timezone_set('Asia/Manila');
$today = date('Y-m-d');
$department = $_GET['department'] ?? '';

try {
    // Get employees who have am_in OR pm_in today (they are active/present)
    $sql = 'SELECT 
                u.position,
                COUNT(DISTINCT a.employee_id) as active_count
            FROM attendance a
            JOIN users u ON u.employee_id = a.employee_id
            WHERE a.date = ? AND (a.am_in IS NOT NULL OR a.pm_in IS NOT NULL)';
    
    $params = [$today];
    
    // Filter by department if provided
    if ($department) {
        $sql .= ' AND u.department = ?';
        $params[] = $department;
    }
    
    $sql .= ' GROUP BY u.position';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the response
    $activeCounts = [
        'Permanent' => 0,
        'Casual' => 0,
        'JO' => 0,
        'OJT' => 0
    ];
    
    foreach ($results as $row) {
        $activeCounts[$row['position']] = (int)$row['active_count'];
    }
    
    // Get total counts
    $totalSql = 'SELECT position, COUNT(*) as total_count 
                 FROM users 
                 WHERE status = "approved" AND employee_id IS NOT NULL';
    
    $totalParams = [];
    
    if ($department) {
        $totalSql .= ' AND department = ?';
        $totalParams[] = $department;
    }
    
    $totalSql .= ' GROUP BY position';
    
    $totalStmt = $pdo->prepare($totalSql);
    $totalStmt->execute($totalParams);
    $totalResults = $totalStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalCounts = [
        'Permanent' => 0,
        'Casual' => 0,
        'JO' => 0,
        'OJT' => 0
    ];
    
    foreach ($totalResults as $row) {
        $totalCounts[$row['position']] = (int)$row['total_count'];
    }
    
    echo json_encode([
        'success' => true,
        'date' => $today,
        'department' => $department,
        'active' => $activeCounts,
        'total' => $totalCounts
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
