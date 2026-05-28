<?php
require_once __DIR__ . '/_bootstrap.php';
session_start();
require_once __DIR__ . '/../db.php';

// Prevent caching to ensure real-time data
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

try {
    $userId = $_SESSION['user_id'];
    $stmt = $pdo->prepare('SELECT employee_id, email FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $employeeId = $row['employee_id'] ?? null;
    $userEmail = $row['email'] ?? '';
    
    if (!$employeeId) {
        echo json_encode(['success' => false, 'error' => 'No employee id for user']);
        exit();
    }

    $range = $_GET['range'] ?? 'daily';
    $trend = [];
    
    // Calculate date range based on period
    $now = new DateTime();
    $startDate = clone $now;
    
    if ($range === 'daily') {
        $startDate->modify('-30 days');
        $periods = 30;
        $labelFormat = 'Y-m-d';
        $groupBy = 'date';
    } elseif ($range === 'weekly') {
        $startDate->modify('-12 weeks');
        $periods = 12;
        $labelFormat = 'Y-m-d';
        $groupBy = 'week';
    } else { // monthly
        $startDate->modify('-12 months');
        $periods = 12;
        $labelFormat = 'Y-m';
        $groupBy = 'month';
    }

    // Fetch all attendance records for the period
    $stmt = $pdo->prepare('
        SELECT date, am_in, pm_out, status 
        FROM attendance 
        WHERE employee_id = ? AND date >= ? AND date <= ?
        ORDER BY date ASC
    ');
    $stmt->execute([$employeeId, $startDate->format('Y-m-d'), $now->format('Y-m-d')]);
    $allRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Aggregate data based on range
    $aggregated = [];
    $totalPresent = 0;
    $totalLate = 0;
    $totalUndertime = 0;
    $totalOvertime = 0;
    $totalAbsent = 0;
    $totalWorkingDays = 0;

    if ($range === 'daily') {
        for ($i = 29; $i >= 0; $i--) {
            $d = clone $now;
            $d->modify("-{$i} days");
            $dateKey = $d->format('Y-m-d');
            
            // Skip weekends for working days count
            $dayOfWeek = $d->format('N');
            if ($dayOfWeek >= 6) continue; // 6 = Saturday, 7 = Sunday
            
            $totalWorkingDays++;
            
            $record = array_filter($allRecords, fn($r) => $r['date'] === $dateKey);
            $record = reset($record);
            
            // Check present/late based on am_in time
            $present = 0;
            $late = 0;
            $timeinUndertime = 0;
            if ($record && $record['am_in'] && (!$record['status'] || $record['status'] != 'on-leave')) {
                $amInTime = new DateTime($record['am_in']);
                $cutoffTime = new DateTime($dateKey . ' 07:00:00');
                if ($amInTime <= $cutoffTime) {
                    $present = 1;
                } else {
                    $late = 1;
                }
            }
            
            // Check undertime/overtime based on pm_out
            // Undertime: 3:00 PM - 4:59 PM | Normal: 5:00 PM - 6:00 PM | Overtime: 6:01 PM - 7:00 PM
            $undertime = 0;
            $overtime = 0;
            if ($record && $record['pm_out']) {
                $pmOutTime = new DateTime($record['pm_out']);
                $undertimeEnd = new DateTime($dateKey . ' 16:59:59'); // 4:59:59 PM
                $overtimeStart = new DateTime($dateKey . ' 18:00:01'); // 6:00:01 PM
                
                if ($pmOutTime <= $undertimeEnd) {
                    $undertime = 1;
                } elseif ($pmOutTime > $overtimeStart) {
                    $overtime = 1;
                }
            }
            
            $absent = (!$record || (!$present && !$late && !$timeinUndertime)) ? 1 : 0;
            
            $totalPresent += $present;
            $totalLate += $late;
            $totalUndertime += $undertime;
            $totalOvertime += $overtime;
            $totalAbsent += $absent;
            
            $trend[] = [
                'label' => $dateKey,
                'present' => $present,
                'late' => $late,
                'undertime' => $undertime,
                'overtime' => $overtime,
                'absent' => $absent
            ];
        }
    } elseif ($range === 'weekly') {
        for ($w = 11; $w >= 0; $w--) {
            $start = new DateTime('monday this week');
            $start->modify("-{$w} weeks");
            $end = clone $start;
            $end->modify('+4 days'); // Mon-Fri only
            
            // Count working days in this week
            $weekWorkingDays = 5; // Monday to Friday
            $totalWorkingDays += $weekWorkingDays;
            
            $weekRecords = array_filter($allRecords, function($r) use ($start, $end) {
                $date = new DateTime($r['date']);
                return $date >= $start && $date <= $end;
            });
            
            $counts = ['present' => 0, 'late' => 0, 'undertime' => 0, 'overtime' => 0, 'absent' => $weekWorkingDays];
            foreach ($weekRecords as $r) {
                // Check present/late based on am_in
                if ($r['am_in'] && (!$r['status'] || $r['status'] != 'on-leave')) {
                    $amInTime = new DateTime($r['am_in']);
                    $cutoffTime = new DateTime($r['date'] . ' 07:00:00');
                    if ($amInTime <= $cutoffTime) {
                        $counts['present']++;
                        $counts['absent']--;
                    } else {
                        $counts['late']++;
                        $counts['absent']--;
                    }
                }
                
                // Check undertime/overtime based on pm_out
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
            
            $totalPresent += $counts['present'];
            $totalLate += $counts['late'];
            $totalUndertime += $counts['undertime'];
            $totalOvertime += $counts['overtime'];
            $totalAbsent += $counts['absent'];
            
            $trend[] = [
                'label' => 'Week ' . $start->format('M d'),
                'present' => $counts['present'],
                'late' => $counts['late'],
                'undertime' => $counts['undertime'],
                'overtime' => $counts['overtime'],
                'absent' => $counts['absent']
            ];
        }
    } else { // monthly
        for ($m = 11; $m >= 0; $m--) {
            $start = new DateTime('first day of this month');
            $start->modify("-{$m} months");
            $end = clone $start;
            $end->modify('last day of this month');
            
            // Count working days in this month (rough estimate: total days * 5/7)
            $daysInMonth = (int)$start->format('t');
            $monthWorkingDays = (int)($daysInMonth * 5 / 7);
            $totalWorkingDays += $monthWorkingDays;
            
            $monthRecords = array_filter($allRecords, function($r) use ($start, $end) {
                $date = new DateTime($r['date']);
                return $date >= $start && $date <= $end;
            });
            
            $counts = ['present' => 0, 'late' => 0, 'undertime' => 0, 'overtime' => 0, 'absent' => $monthWorkingDays];
            foreach ($monthRecords as $r) {
                // Check present/late based on am_in
                if ($r['am_in'] && (!$r['status'] || $r['status'] != 'on-leave')) {
                    $amInTime = new DateTime($r['am_in']);
                    $cutoffTime = new DateTime($r['date'] . ' 07:00:00');
                    if ($amInTime <= $cutoffTime) {
                        $counts['present']++;
                        $counts['absent']--;
                    } else {
                        $counts['late']++;
                        $counts['absent']--;
                    }
                }
                
                // Check undertime/overtime based on pm_out
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
            
            $totalPresent += $counts['present'];
            $totalLate += $counts['late'];
            $totalUndertime += $counts['undertime'];
            $totalOvertime += $counts['overtime'];
            $totalAbsent += $counts['absent'];
            
            $trend[] = [
                'label' => $start->format('M Y'),
                'present' => $counts['present'],
                'late' => $counts['late'],
                'undertime' => $counts['undertime'],
                'overtime' => $counts['overtime'],
                'absent' => $counts['absent']
            ];
        }
    }

    // Calculate metrics
    $attendanceRate = $totalWorkingDays > 0 ? round((($totalPresent + $totalLate) / $totalWorkingDays) * 100, 1) : 0;
    $punctualityRate = ($totalPresent + $totalLate) > 0 ? round(($totalPresent / ($totalPresent + $totalLate)) * 100, 1) : 0;
    $overtimeRate = $totalWorkingDays > 0 ? round(($totalOvertime / $totalWorkingDays) * 100, 1) : 0;
    $undertimeRate = $totalWorkingDays > 0 ? round(($totalUndertime / $totalWorkingDays) * 100, 1) : 0;
    $absenteeismRate = $totalWorkingDays > 0 ? round(($totalAbsent / $totalWorkingDays) * 100, 1) : 0;

    // Generate interpretations
    $interpretations = [];
    
    // Attendance Rate Interpretation
    if ($attendanceRate >= 95) {
        $interpretations['attendance'] = "Excellent attendance! You've been present for {$attendanceRate}% of working days. Your consistency is outstanding and sets a great example for the team.";
    } elseif ($attendanceRate >= 85) {
        $interpretations['attendance'] = "Good attendance rate at {$attendanceRate}%. You maintain solid presence, though there's room for improvement to reach excellent performance.";
    } elseif ($attendanceRate >= 70) {
        $interpretations['attendance'] = "Moderate attendance at {$attendanceRate}%. Consider strategies to improve consistency and reduce absences to enhance your work performance.";
    } else {
        $interpretations['attendance'] = "Low attendance at {$attendanceRate}%. This significantly impacts your work performance. Please discuss attendance concerns with your supervisor.";
    }

    // Punctuality Interpretation - Consider both punctuality AND attendance
    if (($totalPresent + $totalLate) == 0) {
        // No attendance days recorded, cannot evaluate punctuality
        $interpretations['punctuality'] = "Cannot evaluate punctuality due to no attendance records. Please improve your attendance first to establish a punctuality track record.";
    } elseif ($attendanceRate < 50) {
        // Very low attendance - punctuality is meaningless
        $interpretations['punctuality'] = "Punctuality cannot be properly evaluated with only {$attendanceRate}% attendance rate. Focus on improving overall attendance first before punctuality matters.";
    } elseif ($attendanceRate < 70) {
        // Low attendance - acknowledge punctuality but emphasize attendance issue
        if ($punctualityRate >= 85) {
            $interpretations['punctuality'] = "While you show {$punctualityRate}% punctuality on days present, your low attendance ({$attendanceRate}%) remains the primary concern. Consistent presence is more critical than punctuality at this point.";
        } else {
            $interpretations['punctuality'] = "Poor punctuality at {$punctualityRate}% combined with low attendance ({$attendanceRate}%). Both areas need immediate improvement for acceptable work performance.";
        }
    } elseif ($punctualityRate >= 95) {
        $interpretations['punctuality'] = "Excellent punctuality! You arrive on time {$punctualityRate}% of the days you're present. Your timeliness demonstrates strong professionalism.";
    } elseif ($punctualityRate >= 85) {
        $interpretations['punctuality'] = "Good punctuality at {$punctualityRate}%. Occasional tardiness noted - consider adjusting morning routines for consistent on-time arrival.";
    } elseif ($punctualityRate >= 70) {
        $interpretations['punctuality'] = "Punctuality needs improvement at {$punctualityRate}%. Frequent tardiness affects work productivity. Try setting earlier alarms or adjusting commute times.";
    } else {
        $interpretations['punctuality'] = "Chronic tardiness detected ({$punctualityRate}% on-time rate). This pattern needs immediate attention and may require a performance discussion.";
    }

    // Work Hours Interpretation - Consider both work hours AND attendance
    if (($totalPresent + $totalLate) == 0) {
        // No attendance days recorded, cannot evaluate work hours
        $interpretations['work_hours'] = "Cannot evaluate work hours due to no attendance records. Please improve your attendance to establish a work hours pattern.";
    } elseif ($attendanceRate < 50) {
        // Very low attendance - work hours are meaningless
        $interpretations['work_hours'] = "Work hours cannot be properly evaluated with only {$attendanceRate}% attendance rate. Your primary focus should be on improving overall attendance before evaluating work hour patterns.";
    } elseif ($attendanceRate < 70) {
        // Low attendance - work hours are secondary concern
        $interpretations['work_hours'] = "While work hour data is available, your low attendance ({$attendanceRate}%) is the primary concern. Consistent daily presence is more important than work hour patterns at this stage.";
    } elseif ($overtimeRate > 20) {
        $interpretations['work_hours'] = "High overtime detected ({$overtimeRate}% of days). While dedication is appreciated, ensure proper work-life balance to prevent burnout.";
    } elseif ($undertimeRate > 20) {
        $interpretations['work_hours'] = "Frequent undertime noted ({$undertimeRate}% of days). Consistently leaving early may impact productivity. Discuss workload concerns with your supervisor if needed.";
    } elseif ($overtimeRate > 10) {
        $interpretations['work_hours'] = "Moderate overtime ({$overtimeRate}% of days). Your extra effort is noted. Ensure you're managing time effectively and maintaining work-life balance.";
    } else {
        $interpretations['work_hours'] = "Work hours are well-balanced. You maintain good time management with minimal overtime or undertime.";
    }

    // Generate Decision Support System alerts
    $dss = [];
    
    // Critical: Low Attendance Alert
    if ($attendanceRate < 70) {
        $dss[] = [
            'type' => 'error',
            'priority' => 'high',
            'title' => 'Critical: Low Attendance Pattern',
            'message' => "Your attendance rate is {$attendanceRate}%, significantly below acceptable standards (85%+).",
            'recommendation' => 'Immediate action required: Schedule meeting with HR to discuss attendance concerns. Review any medical or personal issues affecting attendance. Consider flexible work arrangements if applicable.',
            'metrics' => ['attendance_rate' => $attendanceRate, 'absent_days' => $totalAbsent]
        ];
    } elseif ($attendanceRate < 85) {
        $dss[] = [
            'type' => 'warning',
            'priority' => 'medium',
            'title' => 'Attendance Improvement Needed',
            'message' => "Your attendance rate is {$attendanceRate}%, below the good performance threshold.",
            'recommendation' => 'Review reasons for absences. Set personal attendance goals. Use leave planning tools to minimize unplanned absences. Communicate proactively with your supervisor about scheduling needs.',
            'metrics' => ['attendance_rate' => $attendanceRate, 'target' => 85]
        ];
    }

    // Warning: Chronic Tardiness
    if ($totalLate >= ($totalPresent + $totalLate) * 0.3 && $totalLate > 3) {
        $latePercentage = round(($totalLate / ($totalPresent + $totalLate)) * 100, 1);
        $dss[] = [
            'type' => 'warning',
            'priority' => 'high',
            'title' => 'Chronic Tardiness Pattern',
            'message' => "You've been late {$totalLate} times ({$latePercentage}% of attendance days), indicating a consistent tardiness pattern.",
            'recommendation' => 'Adjust morning routine: Set multiple alarms, prepare items night before, consider earlier commute departure. Tardiness impacts team productivity and your professional reputation.',
            'metrics' => ['late_count' => $totalLate, 'late_percentage' => $latePercentage]
        ];
    } elseif ($totalLate >= 5) {
        $dss[] = [
            'type' => 'info',
            'priority' => 'medium',
            'title' => 'Occasional Tardiness Noted',
            'message' => "You've been late {$totalLate} times this period. While not critical, consistent punctuality is expected.",
            'recommendation' => 'Identify common causes of delays (traffic, oversleeping, etc.) and create contingency plans. Aim for zero tardiness next period.',
            'metrics' => ['late_count' => $totalLate]
        ];
    }

    // Warning: Excessive Undertime
    if ($undertimeRate > 25) {
        $dss[] = [
            'type' => 'warning',
            'priority' => 'medium',
            'title' => 'Frequent Early Departures',
            'message' => "You leave early {$undertimeRate}% of the time, which may affect productivity and team collaboration.",
            'recommendation' => 'Ensure you complete full work hours. If personal circumstances require early departure, discuss flexible scheduling with your supervisor. Communicate with team about availability.',
            'metrics' => ['undertime_rate' => $undertimeRate, 'undertime_count' => $totalUndertime]
        ];
    }

    // Info: Overtime Recognition
    if ($overtimeRate > 15) {
        $dss[] = [
            'type' => 'info',
            'priority' => 'medium',
            'title' => 'High Overtime Detected',
            'message' => "You've worked overtime {$overtimeRate}% of days ({$totalOvertime} instances). Your dedication is appreciated.",
            'recommendation' => 'While commitment is valued, ensure you maintain work-life balance. If overtime is due to workload, discuss resource needs with your supervisor. Take regular breaks and time off to prevent burnout.',
            'metrics' => ['overtime_rate' => $overtimeRate, 'overtime_count' => $totalOvertime]
        ];
    }

    // Success: Excellent Performance
    if ($attendanceRate >= 95 && $punctualityRate >= 95 && $totalAbsent <= 2) {
        $dss[] = [
            'type' => 'success',
            'priority' => 'low',
            'title' => 'Outstanding Attendance Performance!',
            'message' => "Excellent work! You maintain {$attendanceRate}% attendance with {$punctualityRate}% punctuality.",
            'recommendation' => 'Keep up the great work! Your consistency and professionalism are exemplary. Continue this pattern to maintain your excellent performance record.',
            'metrics' => ['attendance_rate' => $attendanceRate, 'punctuality_rate' => $punctualityRate]
        ];
    } elseif ($attendanceRate >= 90 && $punctualityRate >= 90) {
        $dss[] = [
            'type' => 'success',
            'priority' => 'low',
            'title' => 'Strong Attendance Performance',
            'message' => "Great job maintaining {$attendanceRate}% attendance and {$punctualityRate}% punctuality.",
            'recommendation' => 'You\'re performing well! Small improvements in consistency could push you to excellent status. Keep up the good habits.',
            'metrics' => ['attendance_rate' => $attendanceRate, 'punctuality_rate' => $punctualityRate]
        ];
    }

    // Absence Pattern Alert
    if ($totalAbsent > $totalWorkingDays * 0.15) {
        $dss[] = [
            'type' => 'warning',
            'priority' => 'high',
            'title' => 'High Absenteeism Pattern',
            'message' => "You have {$totalAbsent} absences out of {$totalWorkingDays} working days ({$absenteeismRate}% absenteeism).",
            'recommendation' => 'Review absence patterns to identify causes. If health-related, consult with HR about medical leave options. If personal, explore flexible work arrangements. Unplanned absences disrupt team workflow.',
            'metrics' => ['absent_days' => $totalAbsent, 'absenteeism_rate' => $absenteeismRate]
        ];
    }

    // Create summary metrics
    $summary = [
        'range' => $range,
        'period_label' => $range === 'daily' ? 'Last 30 Days' : ($range === 'weekly' ? 'Last 12 Weeks' : 'Last 12 Months'),
        'total_working_days' => $totalWorkingDays,
        'total_present' => $totalPresent,
        'total_late' => $totalLate,
        'total_absent' => $totalAbsent,
        'total_undertime' => $totalUndertime,
        'total_overtime' => $totalOvertime,
        'attendance_rate' => $attendanceRate,
        'punctuality_rate' => $punctualityRate,
        'overtime_rate' => $overtimeRate,
        'undertime_rate' => $undertimeRate,
        'absenteeism_rate' => $absenteeismRate,
        'overall_score' => round(($attendanceRate * 0.6 + $punctualityRate * 0.4), 1),
        'performance_label' => $attendanceRate >= 95 ? 'Excellent' : ($attendanceRate >= 85 ? 'Good' : ($attendanceRate >= 70 ? 'Moderate' : 'Poor'))
    ];

    echo json_encode([
        'success' => true,
        'analytics' => [
            'trend' => $trend,
            'summary' => $summary,
            'interpretations' => $interpretations,
            'decision_support' => $dss,
            'generated_at' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
