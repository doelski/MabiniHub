<?php
/**
 * Auto Timeout Script - DEPRECATED
 *
 * Safe to include from dashboards. It only sends JSON headers/output when
 * opened directly.
 */

$isDirectRun = realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__;

$response = [
    'success' => true,
    'message' => 'Auto timeout script is deprecated. Attendance system now uses AM/PM sessions.',
    'note' => 'This file can be safely removed or archived.'
];

if ($isDirectRun) {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

return $response;
