<?php
/**
 * SYNC APPROVED LEAVES TO ATTENDANCE
 * 
 * This script retroactively creates/updates attendance records for all
 * leaves that have been approved by municipal admin (approved_by_municipal = 1)
 * 
 * Run this ONCE to sync existing approved leaves to attendance table
 */

require_once '../db.php';
header('Content-Type: application/json');

try {
    $pdo->beginTransaction();
    
    // Get all approved leaves (approved by municipal)
    $stmt = $pdo->query("
        SELECT lr.id, lr.employee_email, lr.dates, lr.leave_type,
               u.employee_id, u.firstname, u.lastname
        FROM leave_requests lr
        JOIN users u ON lr.employee_email = u.email
        WHERE lr.approved_by_municipal = 1
        AND lr.status = 'approved'
        AND u.employee_id IS NOT NULL
    ");
    
    $approved_leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_leaves = count($approved_leaves);
    $total_dates_processed = 0;
    $errors = [];
    
    foreach ($approved_leaves as $leave) {
        $employee_id = $leave['employee_id'];
        $dates_str = $leave['dates'];
        
        // Parse dates from leave request
        if (preg_match_all('/\d{4}-\d{2}-\d{2}/', $dates_str, $matches)) {
            $leave_dates = [];
            
            if (count($matches[0]) >= 2) {
                // Date range
                $start_date = new DateTime($matches[0][0]);
                $end_date = new DateTime($matches[0][1]);
                $end_date->modify('+1 day');
                
                $interval = new DateInterval('P1D');
                $period = new DatePeriod($start_date, $interval, $end_date);
                
                foreach ($period as $date) {
                    $leave_dates[] = $date->format('Y-m-d');
                }
            } else if (count($matches[0]) == 1) {
                // Single date
                $leave_dates[] = $matches[0][0];
            }
            
            // Create/update attendance records
            foreach ($leave_dates as $leave_date) {
                try {
                    $attStmt = $pdo->prepare("
                        INSERT INTO attendance 
                        (employee_id, date, time_in, time_out, time_in_status, time_out_status, status, created_at, updated_at)
                        VALUES (?, ?, NULL, NULL, NULL, NULL, 'on-leave', NOW(), NOW())
                        ON DUPLICATE KEY UPDATE
                            status = 'on-leave',
                            updated_at = NOW()
                    ");
                    $attStmt->execute([$employee_id, $leave_date]);
                    $total_dates_processed++;
                } catch (PDOException $e) {
                    $errors[] = [
                        'employee' => $leave['firstname'] . ' ' . $leave['lastname'],
                        'employee_id' => $employee_id,
                        'date' => $leave_date,
                        'error' => $e->getMessage()
                    ];
                }
            }
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Sync completed successfully',
        'stats' => [
            'total_approved_leaves' => $total_leaves,
            'total_dates_processed' => $total_dates_processed,
            'errors_count' => count($errors)
        ],
        'errors' => $errors
    ]);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'details' => $e->getMessage()
    ]);
}
?>
