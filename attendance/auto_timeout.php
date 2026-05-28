<?php
/**
 * Auto Timeout Script - DEPRECATED
 * 
 * This script is no longer used after the attendance system was restructured
 * to use AM/PM sessions (am_in, am_out, pm_in, pm_out) instead of 
 * single time_in/time_out with status tracking.
 * 
 * The new system does not require auto-timeout functionality as employees
 * can clock in/out for morning and afternoon sessions independently.
 */

header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'Auto timeout script is deprecated. Attendance system now uses AM/PM sessions.',
    'note' => 'This file can be safely removed or archived.'
]);

?>
