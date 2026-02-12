<?php
// Start output buffering to prevent any accidental output before JSON response
ob_start();

require_once __DIR__ . '/../auth_guard.php';
require_once __DIR__ . '/../db.php';

// Clean any previous output and set JSON header
ob_clean();
header('Content-Type: application/json');

try {
    // Only HR can import; adjust roles if needed
    require_role(['hr']);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
        exit;
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $err = isset($_FILES['file']) ? $_FILES['file']['error'] : 'No file uploaded';
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Upload error: ' . $err]);
        exit;
    }

    $file = $_FILES['file'];
    $name = $file['name'];
    $tmp = $file['tmp_name'];

    // Validate extension
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowed = ['xls', 'xlsx', 'csv'];
    if (!in_array($ext, $allowed, true)) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Allowed: xls, xlsx, csv']);
        exit;
    }

    // Prepare uploads directory
    $uploadDir = realpath(__DIR__ . '/../uploads');
    if ($uploadDir === false) {
        $uploadDir = __DIR__ . '/../uploads';
    }
    $importsDir = $uploadDir . '/imports';
    if (!is_dir($importsDir)) {
        if (!mkdir($importsDir, 0775, true)) {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Failed to create import directory']);
            exit;
        }
    }

    // Sanitize filename
    $base = preg_replace('/[^A-Za-z0-9_.-]/', '_', pathinfo($name, PATHINFO_FILENAME));
    $destName = $base . '.' . $ext;
    $destPath = $importsDir . '/' . $destName;

    // DELETE all previous CSV/Excel files before saving the new one
    // This keeps only the latest imported file in the folder
    // Database records are always preserved (upsert logic below)
    $previousFiles = glob($importsDir . '/*.{csv,xls,xlsx}', GLOB_BRACE);
    if ($previousFiles !== false) {
        foreach ($previousFiles as $oldFile) {
            if (is_file($oldFile)) {
                @unlink($oldFile); // Delete previous import file
            }
        }
    }

    // Save the new import file
    if (!move_uploaded_file($tmp, $destPath)) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file']);
        exit;
    }

    // Parse file into rows
    $rows = [];
    $headers = [];
    $source = $destPath;

    // Helper: normalize header keys
    $norm = function($s){
        $s = trim($s);
        $s = strtolower($s);
        $s = preg_replace('/[^a-z0-9]+/', '_', $s);
        return $s;
    };

    if ($ext === 'csv') {
        $raw = @file_get_contents($source);
        if ($raw === false) {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Failed to open CSV']);
            exit;
        }
        // Handle BOM/encoding (UTF-8/UTF-16LE/UTF-16BE)
        $enc = 'UTF-8';
        if (strncmp($raw, "\xEF\xBB\xBF", 3) === 0) {
            $raw = substr($raw, 3); // strip UTF-8 BOM
        } elseif (strncmp($raw, "\xFF\xFE", 2) === 0) {
            $enc = 'UTF-16LE';
        } elseif (strncmp($raw, "\xFE\xFF", 2) === 0) {
            $enc = 'UTF-16BE';
        }
        if ($enc !== 'UTF-8') {
            $converted = @mb_convert_encoding($raw, 'UTF-8', $enc);
            if ($converted !== false) {
                $raw = $converted;
            }
        }

        // Create temp stream for robust reading
        $fh = fopen('php://temp', 'r+');
        fwrite($fh, $raw);
        rewind($fh);

        // Collect sample lines for delimiter detection
        $candidates = [',',';','\t','|'];
        $samples = [];
        for ($i=0; $i<5 && !feof($fh); $i++) {
            $line = fgets($fh);
            if ($line === false) break;
            if (trim($line) === '') { $i--; continue; }
            $samples[] = $line;
        }
        if (empty($samples)) {
            fclose($fh);
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Empty CSV file']);
            exit;
        }
        // Determine best delimiter by highest average fields
        $bestDelim = ','; $bestScore = -1;
        foreach ($candidates as $cand) {
            $total = 0; $cnt = 0;
            foreach ($samples as $s) {
                $arr = str_getcsv($s, $cand);
                $total += max(1, count($arr));
                $cnt++;
            }
            $avg = $cnt ? ($total/$cnt) : 0;
            if ($avg > $bestScore) { $bestScore = $avg; $bestDelim = $cand; }
        }

        // Rewind and parse header + rows using chosen delimiter
        rewind($fh);
        $headerRow = null;
        while (!feof($fh)) {
            $headerRow = fgetcsv($fh, 0, $bestDelim);
            if ($headerRow === false) break;
            // Skip blank header or empty lines
            $isBlank = true;
            foreach ((array)$headerRow as $val) { if (trim((string)$val) !== '') { $isBlank = false; break; } }
            if (!$isBlank) break;
        }
        if ($headerRow === false || $headerRow === null) {
            fclose($fh);
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'CSV header not found']);
            exit;
        }
        $headers = array_map($norm, $headerRow);

        while (($data = fgetcsv($fh, 0, $bestDelim)) !== false) {
            // Skip fully blank lines
            $allBlank = true;
            foreach ($data as $v) { if (trim((string)$v) !== '') { $allBlank = false; break; } }
            if ($allBlank) continue;
            $row = [];
            foreach ($headers as $i => $key) {
                $row[$key] = isset($data[$i]) ? trim((string)$data[$i]) : null;
            }
            $rows[] = $row;
        }
        fclose($fh);
    } else {
        // Try PhpSpreadsheet for xls/xlsx
        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (!file_exists($autoload)) {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'XLS/XLSX import requires PhpSpreadsheet. Convert to CSV or install dependencies.']);
            exit;
        }
        require_once $autoload;
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'XLS/XLSX import requires PhpSpreadsheet. Please run: composer require phpoffice/phpspreadsheet, or upload a CSV file.']);
            exit;
        }
        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($source);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($source);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
            // Headers
            for ($col = 1; $col <= $highestColIndex; $col++) {
                $headers[] = $norm((string)$sheet->getCellByColumnAndRow($col, 1)->getValue());
            }
            // Rows
            for ($row = 2; $row <= $highestRow; $row++) {
                $r = [];
                $empty = true;
                for ($col = 1; $col <= $highestColIndex; $col++) {
                    $val = $sheet->getCellByColumnAndRow($col, $row)->getFormattedValue();
                    if ($val !== null && $val !== '') $empty = false;
                    $r[$headers[$col-1] ?? ('col_'.$col)] = is_string($val) ? trim($val) : $val;
                }
                if (!$empty) $rows[] = $r;
            }
        } catch (Throwable $e) {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Failed to read spreadsheet: ' . $e->getMessage()]);
            exit;
        }
    }

    if (empty($rows)) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'No data rows found in file']);
        exit;
    }

    // Map common header aliases
    $mapField = function(array $row, array $candidates) {
        foreach ($candidates as $c) {
            if (isset($row[$c]) && $row[$c] !== '') return $row[$c];
        }
        return null;
    };

    $pdo->beginTransaction();
    $inserted = 0; $updated = 0; $skipped = 0; $errors = 0;
    $errSamples = [];

    // Use INSERT ... ON DUPLICATE KEY UPDATE for guaranteed upsert
    // This ensures ALL fields are updated when re-importing (based on UNIQUE key: employee_id, date)
    $upsertStmt = $pdo->prepare("
        INSERT INTO attendance 
        (employee_id, date, time_in, time_in_status, time_out, time_out_status, status, created_at, updated_at)
        VALUES (:eid, :dt, :tin, :tin_status, :tout, :tout_status, :status, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            time_in = VALUES(time_in),
            time_in_status = VALUES(time_in_status),
            time_out = VALUES(time_out),
            time_out_status = VALUES(time_out_status),
            status = VALUES(status),
            updated_at = NOW()
    ");
    
    // Check if record exists and fetch current status (to preserve on-leave status)
    $checkStmt = $pdo->prepare("SELECT id, status FROM attendance WHERE employee_id = :eid AND date = :dt LIMIT 1");

    // Helper to validate and resolve employee_id (MUST exist in users table)
    $resolveEmployeeId = function($rawId) use ($pdo) {
        $val = trim((string)$rawId);
        if ($val === '') return null;
        
        // First, try to match as employee_id directly (most common case)
        $stmt = $pdo->prepare('SELECT employee_id FROM users WHERE employee_id = ? AND status = "approved" LIMIT 1');
        $stmt->execute([$val]);
        $empId = $stmt->fetchColumn();
        if ($empId) return $empId;
        
        // If not found and looks like numeric user id, try to map via users.id
        if (ctype_digit($val)) {
            $stmt2 = $pdo->prepare('SELECT employee_id FROM users WHERE id = ? AND status = "approved" LIMIT 1');
            $stmt2->execute([$val]);
            $empId2 = $stmt2->fetchColumn();
            if ($empId2) return $empId2;
        }
        
        // Not found in database - return null to skip this record
        return null;
    };

    foreach ($rows as $idx => $r) {
        try {
            // Flexible keys
            $eidRaw = $mapField($r, ['employee_id','emp_id','employee_number','employee_code','employee','id']);
            $dateVal = $mapField($r, ['date','attendance_date','day']);
            $timeInVal = $mapField($r, ['time_in','in','check_in']);
            $timeOutVal = $mapField($r, ['time_out','out','check_out']);

            // Validate employee_id and date first
            if (!$eidRaw || !$dateVal) { 
                $skipped++; 
                if (count($errSamples) < 5) {
                    $errSamples[] = 'Row '.($idx+2).': Missing employee_id or date';
                }
                continue; 
            }

            // Resolve and validate employee_id exists in database
            $eid = $resolveEmployeeId($eidRaw);
            if (!$eid) { 
                $skipped++; 
                if (count($errSamples) < 5) {
                    $errSamples[] = 'Row '.($idx+2).': Employee ID "'.$eidRaw.'" not found in system';
                }
                continue; 
            }
            
            $tinStatus = null;
            $toutStatus = null;

            // Normalize date to Y-m-d with STRICT DD/MM/YYYY parsing
            $date = null;
            $dateStr = trim((string)$dateVal);
            
            // PRIORITY: DD/MM/YYYY format (10/02/2026 = Feb 10, 2026)
            // This is the ONLY format we accept to avoid ambiguity
            $dt = DateTime::createFromFormat('d/m/Y', $dateStr);
            if ($dt !== false) {
                // Strict validation: ensure the parsed date matches input exactly
                $errors = DateTime::getLastErrors();
                if ($errors['warning_count'] == 0 && $errors['error_count'] == 0) {
                    $date = $dt->format('Y-m-d');
                }
            }
            
            // If DD/MM/YYYY failed, try other formats as fallback
            if (!$date) {
                $fallbackFormats = [
                    'Y-m-d',    // 2026-02-10 (already formatted)
                    'd-m-Y',    // 10-02-2026
                    'Y/m/d',    // 2026/02/10
                ];
                
                foreach ($fallbackFormats as $format) {
                    $dt = DateTime::createFromFormat($format, $dateStr);
                    if ($dt !== false) {
                        $errors = DateTime::getLastErrors();
                        if ($errors['warning_count'] == 0 && $errors['error_count'] == 0) {
                            $date = $dt->format('Y-m-d');
                            break;
                        }
                    }
                }
            }
            
            // Skip if date could not be parsed
            if (!$date) { 
                $skipped++; 
                if (count($errSamples) < 5) {
                    $errSamples[] = 'Row '.($idx+2).': Invalid date format "'.$dateStr.'" - use DD/MM/YYYY (e.g., 10/02/2026)';
                }
                continue; 
            }

            // Normalize time fields to Y-m-d H:i:s (DATETIME format) or null
            $timeIn = null; $timeOut = null;
            if ($timeInVal !== null && $timeInVal !== '') {
                $t = strtotime((string)$timeInVal);
                if ($t !== false) $timeIn = date('Y-m-d H:i:s', strtotime($date.' '.date('H:i:s', $t)));
            }
            if ($timeOutVal !== null && $timeOutVal !== '') {
                $t = strtotime((string)$timeOutVal);
                if ($t !== false) $timeOut = date('Y-m-d H:i:s', strtotime($date.' '.date('H:i:s', $t)));
            }

            // FINALIZED TIME RANGE LOGIC
            // TIME IN: 4am-7am=Present, 7:01am-12pm=Late, 10am+=Absent
            // TIME OUT: 1pm-4:59pm=Undertime, 5pm-6pm=Out, 6:01pm-8pm=Overtime
            
            $tinStatus = null;
            if ($timeIn) {
                $tIn = strtotime($timeIn);
                $present_start = strtotime($date . ' 04:00:00'); // 4:00 AM
                $present_end   = strtotime($date . ' 07:00:00'); // 7:00 AM
                $late_end      = strtotime($date . ' 12:00:00'); // 12:00 PM
                $absent_cutoff = strtotime($date . ' 10:00:00'); // 10:00 AM

                if ($tIn >= $absent_cutoff) {
                    // 10:00 AM or later = Absent
                    $tinStatus = 'Absent';
                } elseif ($tIn >= $present_start && $tIn <= $present_end) {
                    // 4:00 AM - 7:00 AM = Present
                    $tinStatus = 'Present';
                } elseif ($tIn > $present_end && $tIn <= $late_end) {
                    // 7:01 AM - 12:00 PM = Late
                    $tinStatus = 'Late';
                } else {
                    // Before 4:00 AM = Present (very early)
                    $tinStatus = 'Present';
                }
            } else {
                $tinStatus = 'Absent';
            }

            $toutStatus = null;
            if ($timeOut) {
                $tOut = strtotime($timeOut);
                $undertime_start = strtotime($date . ' 13:00:00'); // 1:00 PM
                $undertime_end   = strtotime($date . ' 16:59:59'); // 4:59:59 PM
                $out_start       = strtotime($date . ' 17:00:00'); // 5:00 PM
                $out_end         = strtotime($date . ' 18:00:00'); // 6:00 PM
                $overtime_start  = strtotime($date . ' 18:00:01'); // 6:01 PM
                $overtime_end    = strtotime($date . ' 20:00:00'); // 8:00 PM

                if ($tOut >= $undertime_start && $tOut <= $undertime_end) {
                    // 1:00 PM - 4:59 PM = Undertime
                    $toutStatus = 'Undertime';
                } elseif ($tOut >= $out_start && $tOut <= $out_end) {
                    // 5:00 PM - 6:00 PM = Out
                    $toutStatus = 'Out';
                } elseif ($tOut >= $overtime_start && $tOut <= $overtime_end) {
                    // 6:01 PM - 8:00 PM = Overtime
                    $toutStatus = 'Overtime';
                } else {
                    $toutStatus = 'Out';
                }
            }

            // Calculate overall daily status
            $dailyStatus = 'absent'; // default
            if ($tinStatus === 'Absent' || !$timeIn) {
                $dailyStatus = 'absent';
            } elseif ($toutStatus === 'Undertime') {
                $dailyStatus = 'undertime';
            } elseif ($tinStatus === 'Late') {
                $dailyStatus = 'late';
            } elseif ($tinStatus === 'Present') {
                $dailyStatus = 'present';
            }

            // Apply UPSERT: This will INSERT or UPDATE if record exists
            // Based on UNIQUE constraint (employee_id, date)
            
            // First check if record exists and get current status
            $checkStmt->execute([':eid' => $eid, ':dt' => $date]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
            $existingId = $existing ? $existing['id'] : null;
            $existingStatus = $existing ? trim(strtolower($existing['status'] ?? '')) : '';
            
            // PRESERVE on-leave status - don't overwrite with calculated status from CSV
            $finalStatus = $dailyStatus;
            if ($existingStatus === 'on-leave' || $existingStatus === 'on leave' || $existingStatus === 'leave') {
                // Employee is on approved leave - keep that status, don't mark as absent
                $finalStatus = 'on-leave';
            }
            
            // Execute upsert - will update all fields if duplicate key found
            $upsertStmt->execute([
                ':eid' => $eid,
                ':dt' => $date,
                ':tin' => $timeIn,
                ':tin_status' => $tinStatus,
                ':tout' => $timeOut,
                ':tout_status' => $toutStatus,
                ':status' => $finalStatus,
            ]);
            
            if ($existingId) {
                $updated++; // Record was updated
            } else {
                $inserted++; // Record was newly inserted
            }
        } catch (Throwable $rowErr) {
            $errors++;
            if (count($errSamples) < 5) {
                $errSamples[] = 'Row '.($idx+2).': '.$rowErr->getMessage();
            }
        }
    }

    $pdo->commit();

    // Clean any buffered output before sending JSON
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Import completed',
        'stats' => [
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ],
        'error_samples' => $errSamples,
    ]);
    ob_end_flush();
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) { $pdo->rollBack(); }
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    ob_end_flush();
}
