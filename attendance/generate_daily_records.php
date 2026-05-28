<?php
/**
 * AUTOMATIC DAILY ATTENDANCE GENERATOR
 * ===========================================
 * This script automatically generates daily attendance records for all employees
 * Runs daily (except weekends) to ensure consistent attendance tracking
 * 
 * Features:
 * - Creates daily records for all approved employees
 * - Skips weekends (Saturday & Sunday)
 * - Marks absent if no time-in by end of day
 * - Can be triggered by cron job, scheduler, or manual access
 * - Works on hosted servers (no localhost dependency)
 * 
 * Usage:
 * 1. Set up cron job: 0 6 * * 1-5 php /path/to/generate_daily_records.php
 * 2. Or use Windows Task Scheduler
 * 3. Or access via URL (with security token)
 */

require_once __DIR__ . '/../db.php';
date_default_timezone_set('Asia/Manila');

// Security token (change this to a random string for production)
define('CRON_SECRET_TOKEN', 'capstone_auto_attendance_2026');

// Check if accessed via URL with security token
$isWebAccess = php_sapi_name() !== 'cli';
if ($isWebAccess) {
    $providedToken = $_GET['token'] ?? '';
    if ($providedToken !== CRON_SECRET_TOKEN) {
        http_response_code(403);
        die('Forbidden: Invalid security token');
    }
    header('Content-Type: application/json');
}

$today = date('Y-m-d');
$dayOfWeek = date('N'); // 1=Monday, 7=Sunday

// Skip weekends
if ($dayOfWeek >= 6) { // 6=Saturday, 7=Sunday
    $result = [
        'success' => true,
        'date' => $today,
        'day' => date('l'),
        'message' => 'Weekend - no attendance records generated',
        'records_created' => 0
    ];
    echo json_encode($result);
    exit;
}

try {
    // Get all approved employees with employee_id
    $stmt = $pdo->prepare('
        SELECT employee_id, firstname, lastname, email 
        FROM users 
        WHERE status = "approved" 
        AND employee_id IS NOT NULL
        AND employee_id != ""
        ORDER BY employee_id
    ');
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($employees)) {
        $result = [
            'success' => false,
            'date' => $today,
            'message' => 'No approved employees found',
            'records_created' => 0
        ];
        echo json_encode($result);
        exit;
    }
    
    $recordsCreated = 0;
    $recordsExist = 0;
    $errors = [];
    
    foreach ($employees as $emp) {
        $empId = $emp['employee_id'];
        
        try {
            // Check if record already exists for today
            $check = $pdo->prepare('
                SELECT id 
                FROM attendance 
                WHERE employee_id = ? AND date = ?
            ');
            $check->execute([$empId, $today]);
            $existing = $check->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Record exists, no update needed
                $recordsExist++;
            } else {
                // No record exists, create placeholder for today
                // Status will be determined based on whether they clock in
                $insert = $pdo->prepare('
                    INSERT INTO attendance 
                    (employee_id, date, am_in, am_out, pm_in, pm_out, status, created_at) 
                    VALUES 
                    (?, ?, NULL, NULL, NULL, NULL, NULL, NOW())
                ');
                $insert->execute([$empId, $today]);
                $recordsCreated++;
            }
        } catch (PDOException $e) {
            $errors[] = [
                'employee_id' => $empId,
                'name' => $emp['firstname'] . ' ' . $emp['lastname'],
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Status is now determined dynamically based on am_in/pm_in presence
    // No need to mark records as absent - it's calculated on the fly
    $markedAbsent = 0;
    $isAfter5PM = ((int)date('H')) >= 17;
    
    $result = [
        'success' => true,
        'date' => $today,
        'day' => date('l'),
        'time' => date('H:i:s'),
        'total_employees' => count($employees),
        'records_created' => $recordsCreated,
        'records_exist' => $recordsExist,
        'marked_absent' => $markedAbsent,
        'is_after_5pm' => $isAfter5PM,
        'errors' => $errors,
        'message' => 'Daily attendance records generated successfully'
    ];
    
    // Log to file for debugging
    $logFile = __DIR__ . '/daily_generator_log.txt';
    $logEntry = date('Y-m-d H:i:s') . ' - ' . json_encode($result) . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    $result = [
        'success' => false,
        'date' => $today,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ];
    echo json_encode($result);
}
