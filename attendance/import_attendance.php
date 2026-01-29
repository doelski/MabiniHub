<?php
require_once __DIR__ . '/../auth_guard.php';
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

try {
    // Only HR can import; adjust roles if needed
    require_role(['hr']);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
        exit;
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $err = isset($_FILES['file']) ? $_FILES['file']['error'] : 'No file uploaded';
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
            echo json_encode(['success' => false, 'error' => 'Failed to create import directory']);
            exit;
        }
    }

    // Sanitize filename and move
    $base = preg_replace('/[^A-Za-z0-9_.-]/', '_', pathinfo($name, PATHINFO_FILENAME));
    $destName = $base . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destPath = $importsDir . '/' . $destName;

    if (!move_uploaded_file($tmp, $destPath)) {
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
            echo json_encode(['success' => false, 'error' => 'XLS/XLSX import requires PhpSpreadsheet. Convert to CSV or install dependencies.']);
            exit;
        }
        require_once $autoload;
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
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
            echo json_encode(['success' => false, 'error' => 'Failed to read spreadsheet: ' . $e->getMessage()]);
            exit;
        }
    }

    if (empty($rows)) {
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

    // Prepare statements
    $sel = $pdo->prepare("SELECT id FROM attendance WHERE employee_id = :eid AND date = :dt LIMIT 1");
    $ins = $pdo->prepare("INSERT INTO attendance (employee_id, date, time_in, time_in_status, time_out, time_out_status) VALUES (:eid, :dt, :tin, :tin_status, :tout, :tout_status)");
    $upd = $pdo->prepare("UPDATE attendance SET time_in = :tin, time_in_status = :tin_status, time_out = :tout, time_out_status = :tout_status WHERE id = :id");

    // Helper to resolve employee identifier to users.employee_id
    $resolveEmployeeId = function($rawId) use ($pdo) {
        $val = trim((string)$rawId);
        if ($val === '') return null;
        // If looks like numeric user id, map to employee_id
        if (ctype_digit($val)) {
            $stmt = $pdo->prepare('SELECT employee_id FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$val]);
            $empId = $stmt->fetchColumn();
            if ($empId) return $empId;
        }
        // Otherwise, if matches an existing employee_id, keep canonical form
        $stmt2 = $pdo->prepare('SELECT employee_id FROM users WHERE employee_id = ? LIMIT 1');
        $stmt2->execute([$val]);
        $empId2 = $stmt2->fetchColumn();
        if ($empId2) return $empId2;
        return $val; // fallback to provided value
    };

    foreach ($rows as $idx => $r) {
        try {
            // Flexible keys
            $eidRaw = $mapField($r, ['employee_id','emp_id','employee_number','employee_code','employee','id']);
            $eid = $resolveEmployeeId($eidRaw);
            $dateVal = $mapField($r, ['date','attendance_date','day']);
            $timeInVal = $mapField($r, ['time_in','in','check_in']);
            $timeOutVal = $mapField($r, ['time_out','out','check_out']);
            // Ignore CSV status fields; we'll compute based on time values
            $tinStatus = null;
            $toutStatus = null;

            if (!$eid || !$dateVal) { $skipped++; continue; }

            // Normalize date to Y-m-d
            $tsDate = strtotime((string)$dateVal);
            if ($tsDate === false) { $skipped++; continue; }
            $date = date('Y-m-d', $tsDate);

            // Normalize time fields to Y-m-d H:i:s or null
            $timeIn = null; $timeOut = null;
            if ($timeInVal !== null && $timeInVal !== '') {
                $t = strtotime((string)$timeInVal);
                if ($t !== false) $timeIn = date('Y-m-d H:i:s', strtotime($date.' '.date('H:i:s', $t)));
            }
            if ($timeOutVal !== null && $timeOutVal !== '') {
                $t = strtotime((string)$timeOutVal);
                if ($t !== false) $timeOut = date('Y-m-d H:i:s', strtotime($date.' '.date('H:i:s', $t)));
            }

            // Normalize statuses
            $normStatus = function($s){
                if ($s === null) return null;
                $s = trim((string)$s);
                if ($s === '') return null;
                $s = ucfirst(strtolower($s));
                // Map some aliases
                $aliases = [
                    'ontime' => 'On-time', 'on-time' => 'On-time', 'on time' => 'On-time',
                    'out' => 'Out', 'overtime' => 'Overtime', 'undertime' => 'Undertime',
                    'present' => 'Present', 'late' => 'Late', 'absent' => 'Absent', 'forgotten' => 'Forgotten'
                ];
                $key = strtolower($s);
                return $aliases[$key] ?? $s;
            };

            // Derive statuses purely from time values (align with recalc rules)
            if ($timeIn) {
                $tIn = strtotime($timeIn);
                $present_start = strtotime($date . ' 06:00:00'); // 6:00 AM
                $present_end   = strtotime($date . ' 08:00:00'); // 8:00 AM
                $late_end      = strtotime($date . ' 12:00:00'); // 12:00 PM
                $undertime_end = strtotime($date . ' 17:00:00'); // 5:00 PM

                if ($tIn < $present_start) {
                    $tinStatus = 'Present';
                } elseif ($tIn <= $present_end) {
                    $tinStatus = 'Present';
                } elseif ($tIn <= $late_end) {
                    $tinStatus = 'Late';
                } elseif ($tIn <= $undertime_end) {
                    $tinStatus = 'Undertime';
                } else {
                    $tinStatus = 'Absent';
                }
            } else {
                $tinStatus = 'Absent';
            }

            if ($timeOut) {
                $tOut = strtotime($timeOut);
                $undertime_end = strtotime($date . ' 16:59:59'); // 4:59:59 PM
                $out_start     = strtotime($date . ' 17:00:00');   // 5:00 PM
                $out_end       = strtotime($date . ' 17:05:00');   // 5:05 PM
                $overtime_start= strtotime($date . ' 18:00:00');   // 6:00 PM

                if ($tOut <= $undertime_end) {
                    $toutStatus = 'Undertime';
                } elseif ($tOut >= $out_start && $tOut <= $out_end) {
                    $toutStatus = 'Out';
                } elseif ($tOut >= $overtime_start) {
                    $toutStatus = 'Overtime';
                } else {
                    $toutStatus = 'Out';
                }
            } else {
                $toutStatus = null; // no time out
            }

            // Apply upsert
            $sel->execute([':eid' => $eid, ':dt' => $date]);
            $existingId = $sel->fetchColumn();
            if ($existingId) {
                $upd->execute([
                    ':tin' => $timeIn,
                    ':tin_status' => $tinStatus,
                    ':tout' => $timeOut,
                    ':tout_status' => $toutStatus,
                    ':id' => $existingId,
                ]);
                $updated++;
            } else {
                $ins->execute([
                    ':eid' => $eid,
                    ':dt' => $date,
                    ':tin' => $timeIn,
                    ':tin_status' => $tinStatus,
                    ':tout' => $timeOut,
                    ':tout_status' => $toutStatus,
                ]);
                $inserted++;
            }
        } catch (Throwable $rowErr) {
            $errors++;
            if (count($errSamples) < 5) {
                $errSamples[] = 'Row '.($idx+2).': '.$rowErr->getMessage();
            }
        }
    }

    $pdo->commit();

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
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
