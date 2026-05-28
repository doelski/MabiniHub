<?php
require_once __DIR__ . '/_bootstrap.php';
/**
 * Recalculate Attendance Status - DEPRECATED
 * 
 * This script is no longer used after the attendance system was restructured
 * to use AM/PM sessions (am_in, am_out, pm_in, pm_out) instead of 
 * single time_in/time_out with status tracking (time_in_status, time_out_status).
 * 
 * The new system does not calculate statuses for individual clock-ins/outs.
 * Instead, status is determined by:
 * - PRESENT: Has am_in OR pm_in
 * - ABSENT: No am_in AND no pm_in
 * - ON LEAVE: status field set to 'on-leave'
 */

header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'Recalculate status script is deprecated. Attendance system now uses AM/PM sessions.',
    'note' => 'Status is now calculated dynamically based on am_in/pm_in presence. This file can be safely removed or archived.'
]);

?>
