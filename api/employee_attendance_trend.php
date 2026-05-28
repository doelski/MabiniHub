<?php
require_once __DIR__ . '/_bootstrap.php';
session_start();
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

try {
    // get employee id for logged-in user
    $userId = $_SESSION['user_id'];
    $stmt = $pdo->prepare('SELECT employee_id FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $employeeId = $row['employee_id'] ?? null;
    if (!$employeeId) {
        echo json_encode(['success' => false, 'error' => 'No employee id for user']);
        exit();
    }

    $range = $_GET['range'] ?? 'daily';
    $trend = [];

    if ($range === 'daily') {
        // last 30 days
        for ($i = 29; $i >= 0; $i--) {
            $d = new DateTime();
            $d->modify("-{$i} days");
            $date = $d->format('Y-m-d');
            $stmt = $pdo->prepare('SELECT am_in, pm_out, status FROM attendance WHERE employee_id = ? AND date = ? LIMIT 1');
            $stmt->execute([$employeeId, $date]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if present (has am_in) and not on leave
            $hasAttendance = $r && $r['am_in'] && (!$r['status'] || $r['status'] != 'on-leave');
            $present = 0;
            $late = 0;
            if ($hasAttendance) {
                $amInTime = new DateTime($r['am_in']);
                $cutoffTime = new DateTime($date . ' 07:00:00');
                if ($amInTime <= $cutoffTime) {
                    $present = 1;
                } else {
                    $late = 1;
                }
            }
            
            // Check undertime and overtime based on pm_out
            // Undertime: 3:00 PM - 4:59 PM | Normal: 5:00 PM - 6:00 PM | Overtime: 6:01 PM - 7:00 PM
            $undertime = 0;
            $overtime = 0;
            if ($r && $r['pm_out']) {
                $pmOutTime = new DateTime($r['pm_out']);
                $undertimeEnd = new DateTime($date . ' 16:59:59'); // 4:59:59 PM
                $normalEnd = new DateTime($date . ' 18:00:00'); // 6:00 PM
                $overtimeStart = new DateTime($date . ' 18:00:01'); // 6:00:01 PM
                
                if ($pmOutTime <= $undertimeEnd) {
                    $undertime = 1;
                } elseif ($pmOutTime > $overtimeStart) {
                    $overtime = 1;
                }
            }
            
            $trend[] = ['label' => $date, 'present' => $present, 'late' => $late, 'undertime' => $undertime, 'overtime' => $overtime];
        }
    } elseif ($range === 'weekly') {
        // last 12 weeks
        for ($w = 11; $w >= 0; $w--) {
            $start = new DateTime('monday this week');
            $start->modify("-{$w} weeks");
            $end = clone $start;
            $end->modify('+6 days');
            $stmt = $pdo->prepare('SELECT date, am_in, pm_out, status FROM attendance WHERE employee_id = ? AND date BETWEEN ? AND ?');
            $stmt->execute([$employeeId, $start->format('Y-m-d'), $end->format('Y-m-d')]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $counts = ['present' => 0, 'late' => 0, 'undertime' => 0, 'overtime' => 0];
            foreach ($rows as $r) {
                // Check present/late
                if ($r['am_in'] && (!$r['status'] || $r['status'] != 'on-leave')) {
                    $amInTime = new DateTime($r['am_in']);
                    $cutoffTime = new DateTime($r['date'] . ' 07:00:00');
                    if ($amInTime <= $cutoffTime) {
                        $counts['present']++;
                    } else {
                        $counts['late']++;
                    }
                }
                
                // Check undertime/overtime
                // Undertime: 3:00 PM - 4:59 PM | Normal: 5:00 PM - 6:00 PM | Overtime: 6:01 PM - 7:00 PM
                if ($r['pm_out']) {
                    $pmOutTime = new DateTime($r['pm_out']);
                    $undertimeEnd = new DateTime($r['date'] . ' 16:59:59'); // 4:59:59 PM
                    $overtimeStart = new DateTime($r['date'] . ' 18:00:01'); // 6:00:01 PM
                    
                    if ($pmOutTime <= $undertimeEnd) {
                        $counts['undertime']++;
                    } elseif ($pmOutTime > $overtimeStart) {
                        $counts['overtime']++;
                    }
                }
            }
            $trend[] = ['label' => $start->format('Y-m-d'), 'present' => $counts['present'], 'late' => $counts['late'], 'undertime' => $counts['undertime'], 'overtime' => $counts['overtime']];
        }
    } else {
        // monthly - last 12 months
        for ($m = 11; $m >= 0; $m--) {
            $start = new DateTime('first day of this month');
            $start->modify("-{$m} months");
            $end = clone $start;
            $end->modify('last day of this month');
            $stmt = $pdo->prepare('SELECT date, am_in, pm_out, status FROM attendance WHERE employee_id = ? AND date BETWEEN ? AND ?');
            $stmt->execute([$employeeId, $start->format('Y-m-d'), $end->format('Y-m-d')]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $counts = ['present' => 0, 'late' => 0, 'undertime' => 0, 'overtime' => 0];
            foreach ($rows as $r) {
                // Check present/late
                if ($r['am_in'] && (!$r['status'] || $r['status'] != 'on-leave')) {
                    $amInTime = new DateTime($r['am_in']);
                    $cutoffTime = new DateTime($r['date'] . ' 07:00:00');
                    if ($amInTime <= $cutoffTime) {
                        $counts['present']++;
                    } else {
                        $counts['late']++;
                    }
                }
                
                // Check undertime/overtime
                // Undertime: 3:00 PM - 4:59 PM | Normal: 5:00 PM - 6:00 PM | Overtime: 6:01 PM - 7:00 PM
                if ($r['pm_out']) {
                    $pmOutTime = new DateTime($r['pm_out']);
                    $undertimeEnd = new DateTime($r['date'] . ' 16:59:59'); // 4:59:59 PM
                    $overtimeStart = new DateTime($r['date'] . ' 18:00:01'); // 6:00:01 PM
                    
                    if ($pmOutTime <= $undertimeEnd) {
                        $counts['undertime']++;
                    } elseif ($pmOutTime > $overtimeStart) {
                        $counts['overtime']++;
                    }
                }
            }
            $trend[] = ['label' => $start->format('Y-m'), 'present' => $counts['present'], 'late' => $counts['late'], 'undertime' => $counts['undertime'], 'overtime' => $counts['overtime']];
        }
    }

    echo json_encode(['success' => true, 'range' => $range, 'trend' => $trend]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

