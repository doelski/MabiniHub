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
                SELECT id, time_in, time_in_status 
                FROM attendance 
                WHERE employee_id = ? AND date = ?
            ');
            $check->execute([$empId, $today]);
            $existing = $check->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Record exists, check if needs update (mark absent if no time-in)
                $currentHour = (int)date('H');
                $currentMinute = (int)date('i');
                $isAfter5PM = ($currentHour > 17) || ($currentHour === 17 && $currentMinute >= 0);
                
                if ($isAfter5PM && empty($existing['time_in']) && $existing['time_in_status'] !== 'Absent') {
                    // Mark as absent if past 5 PM and no time-in
                    $update = $pdo->prepare('
                        UPDATE attendance 
                        SET time_in_status = "Absent", 
                            status = "Absent",
                            updated_at = NOW()
                        WHERE id = ?
                    ');
                    $update->execute([$existing['id']]);
                }
                $recordsExist++;
            } else {
                // No record exists, create placeholder for today
                // Will be marked as absent at end of day if no time-in
                $insert = $pdo->prepare('
                    INSERT INTO attendance 
                    (employee_id, date, time_in, time_out, time_in_status, time_out_status, status, created_at) 
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
    
    // Now mark all without time-in as Absent if after 5 PM
    $currentHour = (int)date('H');
    $currentMinute = (int)date('i');
    $isAfter5PM = ($currentHour > 17) || ($currentHour === 17 && $currentMinute >= 0);
    
    $markedAbsent = 0;
    if ($isAfter5PM) {
        $markAbsent = $pdo->prepare('
            UPDATE attendance 
            SET time_in_status = "Absent",
                status = "Absent",
                updated_at = NOW()
            WHERE date = ? 
            AND (time_in IS NULL OR time_in = "")
            AND (time_in_status IS NULL OR time_in_status != "Absent")
        ');
        $markAbsent->execute([$today]);
        $markedAbsent = $markAbsent->rowCount();
    }
    
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
