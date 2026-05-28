<?php
require_once __DIR__ . '/../auth_guard.php';
require_api_auth(['hr', 'department_head', 'employee']);
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

try {
    $dept = $_GET['department'] ?? '';
    $search = $_GET['search'] ?? '';
    $date = $_GET['date'] ?? null;
    $month = $_GET['month'] ?? null;
    $year = $_GET['year'] ?? null;
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    $status = $_GET['status'] ?? '';

    // Determine date condition based on what parameters are provided
    $dateCondition = '';
    $dateParams = [];
    
    if ($date) {
        // Single day
        $dateCondition = 'a.date = ?';
        $dateParams = [$date];
    } else if ($month) {
        // Month (format: YYYY-MM)
        $dateCondition = 'DATE_FORMAT(a.date, "%Y-%m") = ?';
        $dateParams = [$month];
    } else if ($year) {
        // Year
        $dateCondition = 'YEAR(a.date) = ?';
        $dateParams = [$year];
    } else if ($startDate && $endDate) {
        // Custom range
        $dateCondition = 'a.date BETWEEN ? AND ?';
        $dateParams = [$startDate, $endDate];
    } else {
        // Default to today
        $date = date('Y-m-d');
        $dateCondition = 'a.date = ?';
        $dateParams = [$date];
    }

    if ($status === 'Absent') {
        // For absent status with date ranges, we'll show employees with no attendance in that period
        if ($date) {
            // Single day - show employees without attendance
            $params = [$date];
            $sql = 'SELECT 
                        u.employee_id,
                        CONCAT(u.firstname, " ", u.lastname) as name,
                        u.department,
                        ? as date,
                        NULL as am_in,
                        NULL as am_out,
                        NULL as pm_in,
                        NULL as pm_out,
                        "Absent" as status,
                        u.id
                    FROM users u
                    WHERE u.status = "approved" 
                    AND u.employee_id IS NOT NULL
                    AND u.employee_id NOT IN (
                        SELECT employee_id FROM attendance WHERE date = ?
                    )';
            $params[] = $date;
        } else {
            // For month/year/range, show attendance records marked as Absent
            $params = $dateParams;
            $sql = 'SELECT a.*, 
                    CONCAT(u.firstname, " ", u.lastname) as name, 
                    u.department, 
                    a.employee_id,
                    a.status
                    FROM attendance a 
                    JOIN users u ON u.employee_id = a.employee_id 
                    WHERE ' . $dateCondition . ' AND (a.status = "absent" OR (a.am_in IS NULL AND a.pm_in IS NULL))';
        }
        
        if ($dept) {
            $sql .= ' AND u.department = ?';
            $params[] = $dept;
        }
        
        if ($search) {
            $sql .= ' AND (CONCAT(u.firstname, " ", u.lastname) LIKE ? OR u.employee_id LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $sql .= ' ORDER BY ' . ($date ? 'u.lastname ASC' : 'a.date DESC, u.lastname ASC');
    } else if ($status === 'Present') {
        // Filter by present status (has am_in or pm_in)
        $params = $dateParams;
        $sql = 'SELECT a.*, 
                CONCAT(u.firstname, " ", u.lastname) as name, 
                u.department, 
                a.employee_id,
                a.status
                FROM attendance a 
                JOIN users u ON u.employee_id = a.employee_id 
                WHERE ' . $dateCondition . ' AND (a.am_in IS NOT NULL OR a.pm_in IS NOT NULL) AND a.status != "on-leave"';

        if ($dept) {
            $sql .= ' AND u.department = ?';
            $params[] = $dept;
        }

        if ($search) {
            $sql .= ' AND (CONCAT(u.firstname, " ", u.lastname) LIKE ? OR u.employee_id LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= ' ORDER BY a.am_in DESC';
    } else if ($status === 'on-leave' || $status === 'On Leave' || strtolower($status) === 'leave') {
        // Filter by status field for on-leave
        $params = $dateParams;
        $sql = 'SELECT a.*, 
                CONCAT(u.firstname, " ", u.lastname) as name, 
                u.department, 
                a.employee_id,
                a.status
                FROM attendance a 
                JOIN users u ON u.employee_id = a.employee_id 
                WHERE ' . $dateCondition . ' AND a.status = \'on-leave\'';

        if ($dept) {
            $sql .= ' AND u.department = ?';
            $params[] = $dept;
        }

        if ($search) {
            $sql .= ' AND (CONCAT(u.firstname, " ", u.lastname) LIKE ? OR u.employee_id LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= ' ORDER BY a.date DESC';
    } else {
        // Normal query for other statuses
        // If status is empty or 'all' we want to show attendance records
        // For date ranges (month/year/custom), we show all matching records
        $params = $dateParams;

        // Base attendance select (explicit columns to make UNION compatible)
        $attendanceSelect = 'SELECT a.id, a.employee_id, a.date, a.am_in, a.am_out, a.pm_in, a.pm_out, a.status, CONCAT(u.firstname, " ", u.lastname) as name, u.department
                FROM attendance a
                JOIN users u ON u.employee_id = a.employee_id
                WHERE ' . $dateCondition;

        if ($dept) {
            $attendanceSelect .= ' AND u.department = ?';
            $params[] = $dept;
        }

        if ($search) {
            $attendanceSelect .= ' AND (CONCAT(u.firstname, " ", u.lastname) LIKE ? OR u.employee_id LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        // For 'all' status with single day, include absent users
        // For month/year/range, just show attendance records
        if ($date) {
            // Build absent users select (employees without attendance for the date)
            $absentParams = [$date, $date];
            $absentSelect = 'SELECT NULL as id, u.employee_id, ? as date, NULL as am_in, NULL as am_out, NULL as pm_in, NULL as pm_out, "absent" as status, CONCAT(u.firstname, " ", u.lastname) as name, u.department
                FROM users u
                WHERE u.status = "approved"
                AND u.employee_id IS NOT NULL
                AND u.employee_id NOT IN (SELECT employee_id FROM attendance WHERE date = ?)';

            if ($dept) {
                $absentSelect .= ' AND u.department = ?';
                $absentParams[] = $dept;
            }

            if ($search) {
                $absentSelect .= ' AND (CONCAT(u.firstname, " ", u.lastname) LIKE ? OR u.employee_id LIKE ?)';
                $absentParams[] = "%$search%";
                $absentParams[] = "%$search%";
            }

            // Combine attendance rows with absent users
            $sql = $attendanceSelect . ' UNION ALL ' . $absentSelect . ' ORDER BY name ASC';

            // Merge params: attendance params followed by absent params
            $params = array_merge($params, $absentParams);
        } else {
            // For month/year/range, just show attendance records
            $sql = $attendanceSelect . ' ORDER BY a.date DESC, a.am_in DESC';
        }
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'date' => $date,
        'records' => $rows
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'records' => []
    ]);
}
