<?php
require_once __DIR__ . '/../auth_guard.php';
// Allow access for Employee, HR, and Department Head roles
require_role(['employee','hr','department_head']);
require_once __DIR__ . '/../db.php';

$userId = $_SESSION['user_id'];

// Fetch user info
$stmt = $pdo->prepare('SELECT id, firstname, lastname, position, employee_id, email, profile_picture FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    echo "User not found.";
    exit();
}

if ((isset($_GET['qr']) && $_GET['qr']) || (!empty($_SESSION['qr_pending']))) {
    require_once __DIR__ . '/../attendance/qr_utils.php';
    $pending = $_GET['qr'] ?? $_SESSION['qr_pending'];
    if ($pending && qr_verify_token($pending, 0)) {
        // Record attendance for the current logged-in user
        $res = qr_record_attendance_for_user($pdo, $_SESSION['user_id']);
        // Clear any pending token stored in session
        unset($_SESSION['qr_pending']);

        // Determine base redirect target based on role/position (same logic as index.php)
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === 'superadmin') {
            $redirect = '../super_admin.php';
        } else {
            $sessRole = strtolower($_SESSION['role'] ?? $_SESSION['position'] ?? '');
            if ($sessRole === 'hr' || $sessRole === 'human resources') {
                $redirect = '../hr/dashboard.php';
            } elseif ($sessRole === 'department_head' || $sessRole === 'dept head' || $sessRole === 'dept_head') {
                $redirect = '../dept_head/dashboard.php';
            } elseif ($sessRole === 'employee') {
                $redirect = '../employee/dashboard.php';
            } else {
                $redirect = '../dashboard.php';
            }
        }

        // If attendance result exists, map it to query params similar to index.php
        if (!empty($res) && is_array($res)) {
            if (!empty($res['success'])) {
                $msg = ($res['action'] === 'time_in') ? 'timein_ok' : 'timeout_ok';
                $timeParam = isset($res['time']) ? '&att_time=' . urlencode($res['time']) : '';
                $statusParam = isset($res['status']) ? '&att_status=' . urlencode($res['status']) : '';
                header('Location: ' . $redirect . '?att=' . $msg . $timeParam . $statusParam);
                exit();
            } else {
                $lowerMsg = strtolower($res['message'] ?? '');
                if (strpos($lowerMsg, 'time out already') !== false || strpos($lowerMsg, 'time out already recorded') !== false) {
                    $timeParam = isset($res['time']) ? '&att_time=' . urlencode($res['time']) : '';
                    $statusParam = isset($res['status']) ? '&att_status=' . urlencode($res['status']) : '';
                    header('Location: ' . $redirect . '?att=already_timedout' . $timeParam . $statusParam);
                    exit();
                }
                header('Location: ' . $redirect . '?att=failed');
                exit();
            }
        }

        // Fallback: redirect to target dashboard without params
        header('Location: ' . $redirect);
        exit();
    } else {
        // Invalid/expired token - set a flag so UI can show feedback (consistent with index.php)
        $_SESSION['qr_pending_invalid'] = true;
        unset($_SESSION['qr_pending']);
        // Redirect back to login page to show invalid QR feedback
        header('Location: ../index.php');
        exit();
    }
}

// Determine identifier used in attendance.employee_id
$employeeId = $user['employee_id'] ?? null;

// If employee_id is missing, there's no attendance records tied; leave as null
$attendanceRows = [];
if ($employeeId) {
    $stmt = $pdo->prepare('SELECT id, employee_id, date, time_in, time_out, time_in_status, time_out_status, status, created_at FROM attendance WHERE employee_id = ? ORDER BY date ASC');
    $stmt->execute([$employeeId]);
    $attendanceRows = $stmt->fetchAll();
}

// Helper to format time and compute tardy/undertime
function fmtTime($dt) {
    if (!$dt) return null;
    try {
        $d = new DateTime($dt);
        return $d->format('h:i A');
    } catch (Exception $e) {
        return null;
    }
}

$records = [];
foreach ($attendanceRows as $r) {
    $timeInRaw = $r['time_in'];
    $timeOutRaw = $r['time_out'];
    $timeInFmt = fmtTime($timeInRaw);
    $timeOutFmt = fmtTime($timeOutRaw);

    // Get status from database
    $timeInStatus = $r['time_in_status'] ?? null;
    $timeOutStatus = $r['time_out_status'] ?? null;
    
    // determine unified display status prioritizing time-out status when available
    if ($timeInStatus === 'Absent' || !$timeInRaw) {
        $status = 'Absent';
    } elseif ($timeOutStatus === 'Undertime') {
        $status = 'Undertime';
    } elseif ($timeOutStatus === 'Overtime') {
        $status = 'Overtime';
    } elseif ($timeOutStatus === 'On-time' || $timeOutStatus === 'Out') {
        $status = 'Present';
    } elseif ($timeInStatus === 'Late') {
        $status = 'Late';
    } else {
        $status = 'Present';
    }

    // Set flags based on database status values
    $tardy = ($timeInStatus === 'Late');
    $undertime = ($timeOutStatus === 'Undertime');
    $overtime = ($timeOutStatus === 'Overtime');

    $records[] = [
        'id' => (int)$r['id'],
        'date' => $r['date'],
        'timeIn' => $timeInFmt,
        'timeOut' => $timeOutFmt,
        'timeInStatus' => $timeInStatus,
        'timeOutStatus' => $timeOutStatus,
        'status' => $status,
        'tardy' => $tardy,
        'undertime' => $undertime,
        'overtime' => $overtime,
    ];
}

// compute summary
$daysPresent = 0; $daysLate = 0; $daysAbsent = 0; $totalTardy = 0; $totalUndertime = 0; $totalOvertime = 0;
foreach ($records as $rec) {
    // Separate counting: Present = only "Present" status, Late = only "Late" status
    if ($rec['timeInStatus'] === 'Present') {
        $daysPresent++;
    } elseif ($rec['timeInStatus'] === 'Late') {
        $daysLate++;
    }
    // Absent = didn't time in (or timed in after 12:01 PM which is marked as Absent)
    if ($rec['timeInStatus'] === 'Absent' || !$rec['timeIn']) {
        $daysAbsent++;
    }
    
    if ($rec['tardy']) $totalTardy++;
    if ($rec['undertime']) $totalUndertime++;
    if ($rec['overtime']) $totalOvertime++;
}
$total = count($records);
$daysActive = $daysPresent + $daysLate; // Active = Present + Late (timed in before 12:01 PM)
$attendanceRate = $total ? round(($daysActive / $total) * 100) : 0;

// prepare payload for client
$payload = [
    'user' => [
        'name' => trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')),
        'position' => $user['position'] ?? '',
        'employee_id' => $employeeId,
        'email' => $user['email'] ?? ''
    ],
    'attendance' => $records,
    'summary' => [
        'daysPresent' => $daysPresent,
        'daysLate' => $daysLate,
        'daysActive' => $daysActive,
        'daysAbsent' => $daysAbsent,
        'totalTardy' => $totalTardy,
        'totalUndertime' => $totalUndertime,
        'totalOvertime' => $totalOvertime,
        'attendanceRate' => $attendanceRate
    ]
    ];

$profilePicture = $user['profile_picture'] ?? '';
?>
<?php
// Determine the correct home dashboard for the logged-in user based on role/position
$home_link = 'dashboard.php'; // default employee dashboard
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === 'superadmin') {
    $home_link = '../super_admin.html';
} else {
    $sessRole = strtolower($_SESSION['role'] ?? $_SESSION['position'] ?? '');
    if ($sessRole === 'hr' || $sessRole === 'human resources') {
        $home_link = '../hr/dashboard.php';
    } elseif ($sessRole === 'department_head' || $sessRole === 'dept head' || $sessRole === 'dept_head') {
        $home_link = '../dept_head/dashboard.php';
    } else {
        $home_link = 'dashboard.php';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>My Attendance</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    body { font-family: 'Inter', sans-serif; background:#f9fafb; margin:0; padding:0; }
    .modal-bg { background: rgba(0,0,0,0.6); backdrop-filter: blur(5px); }
    .chart-container { position: relative; height: 220px; }
    .chart-container-small { position: relative; height: 180px; }
    .card-hover { transition: all 0.2s ease; }
    .card-hover:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .filter-btn { color: #6b7280; }
    .filter-btn.active { background-color: #3b82f6; color: white; }
    .filter-btn:hover:not(.active) { background-color: #f3f4f6; }
    .print-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
    .print-modal.active { display: flex; }
    .print-modal-content { background: #fff; border-radius: 12px; padding: 32px; max-width: 500px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
    .print-modal h2 { font-size: 24px; margin-bottom: 20px; color: #1f2937; }
    .print-modal-buttons { display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; }
    .print-modal-buttons button { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-family: 'Inter', sans-serif; transition: all 0.3s; }
    @media print { body * { visibility: hidden; } #print-area, #print-area * { visibility: visible; } #print-area { position: absolute; left: 0; top: 0; width: 100%; } }
</style>
</head>
<body class="min-h-screen flex flex-col bg-gray-100 p-4 lg:p-10" data-user-id="<?= htmlspecialchars($userId, ENT_QUOTES) ?>">

<!-- Header: centered rounded card, not edge-to-edge -->
<header class="sticky top-0 z-50">
	<div class="max-w-7xl mx-auto">
		<div class="bg-white rounded-xl shadow-md px-4 py-3 flex items-center justify-between">
			<div class="flex items-center space-x-4">
				<div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center overflow-hidden">
					<img src="../assets/logo.png" alt="Logo" class="rounded-full w-full h-full object-cover">
				</div>
				<h1 class="text-xl font-bold text-gray-800">Dashboard</h1>
			</div>
			<div class="flex items-center space-x-4">

                <!-- Home button -->
                <a id="home-button" href="<?= htmlspecialchars($home_link, ENT_QUOTES) ?>" class="text-gray-600 hover:text-blue-600 transition-colors" aria-label="Home" title="Home">
					<i class="fas fa-home text-lg"></i>
				</a>

				<!-- Profile avatar + modal trigger -->
				<img id="profileIcon" src="<?php echo $profilePicture ? htmlspecialchars($profilePicture) : 'https://placehold.co/40x40/FF5733/FFFFFF?text=P'; ?>" alt="Profile" class="w-10 h-10 rounded-full cursor-pointer">
			</div>
		</div>
	</div>
</header>

<!-- Profile Modal -->
<div id="profileModal" class="fixed inset-0 hidden items-center justify-center modal-bg z-50">
	<div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-xs mx-4 flex flex-col items-center">
		<img id="profileModalPhoto" src="<?php echo $profilePicture ? htmlspecialchars($profilePicture) : 'https://placehold.co/80x80/FFD700/000000?text=W+P'; ?>" alt="Profile" class="w-20 h-20 rounded-full mb-4">
		<a href="dashboard.php" class="w-full px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-md hover:bg-blue-100 mb-2 text-center">Go to Dashboard</a>
		<a href="logout.php" class="w-full px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 mb-2 text-center">Log out</a>
		<button id="closeProfileModal" class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">Cancel</button>
	</div>
</div>

<!-- Main content -->
<main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-6 space-y-5">
    <!-- Page Title Section -->
    <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Attendance Overview</h2>
                <p class="text-sm text-gray-600 mt-1">
                    Employee ID: <span class="font-semibold text-gray-900"><?= htmlspecialchars($payload['user']['employee_id'] ?? '—') ?></span>
                    <span class="mx-2 text-gray-400">•</span>
                    <span class="text-gray-500"><?= date('F j, Y') ?></span>
                </p>
            </div>
            <a href="<?= htmlspecialchars($home_link, ENT_QUOTES) ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-all shadow-sm hover:shadow">
                <i class="fas fa-arrow-left text-xs"></i>
                <span>Back to Dashboard</span>
            </a>
        </div>
    </section>

    <!-- Comprehensive Analytics Section -->
    <section class="space-y-4" aria-label="Attendance analytics">
        <!-- Row 1: All Categories Doughnut + Trend Line Chart -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <!-- Overall Attendance Percentage -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-gray-900">Overall Attendance Rate</h3>
                    <div class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-percentage text-purple-600 text-sm"></i>
                    </div>
                </div>
                <div class="flex flex-col items-center justify-center" style="height: 220px;">
                    <div class="text-center">
                        <p class="text-7xl font-bold text-purple-600" id="overallRate"><?= $payload['summary']['attendanceRate'] ?>%</p>
                        <p class="text-sm text-gray-500 mt-2">Attendance Rate</p>
                        <div class="mt-4 space-y-1">
                            <p class="text-xs text-gray-600">
                                <span class="inline-block w-2 h-2 rounded-full bg-green-500 mr-1"></span>
                                Present: <span class="font-semibold"><?= $payload['summary']['daysPresent'] ?></span>
                            </p>
                            <p class="text-xs text-gray-600">
                                <span class="inline-block w-2 h-2 rounded-full bg-yellow-500 mr-1"></span>
                                Late: <span class="font-semibold"><?= $payload['summary']['daysLate'] ?></span>
                            </p>
                            <p class="text-xs text-gray-600">
                                <span class="inline-block w-2 h-2 rounded-full bg-red-500 mr-1"></span>
                                Absent: <span class="font-semibold"><?= $payload['summary']['daysAbsent'] ?></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Trend Line Chart -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-gray-900">Attendance Trend</h3>
                    <div class="w-8 h-8 bg-indigo-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-line text-indigo-600 text-sm"></i>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="trendChart" aria-label="Monthly attendance trend" role="img"></canvas>
                </div>
            </div>
        </div>
    </section>

    <!-- Daily Records Table -->
    <section class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden card-hover" aria-label="Daily attendance records">
        <div class="px-5 py-4 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <h3 class="text-lg font-semibold text-gray-800">Daily Records</h3>
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Filter Type Buttons -->
                    <div class="inline-flex rounded-lg border border-gray-300 bg-white p-1">
                        <button id="filterDay" class="filter-btn px-3 py-1.5 text-xs font-medium rounded-md transition-all active" data-filter="day">
                            <i class="fas fa-calendar-day mr-1"></i>Day
                        </button>
                        <button id="filterMonth" class="filter-btn px-3 py-1.5 text-xs font-medium rounded-md transition-all" data-filter="month">
                            <i class="fas fa-calendar-alt mr-1"></i>Month
                        </button>
                        <button id="filterYear" class="filter-btn px-3 py-1.5 text-xs font-medium rounded-md transition-all" data-filter="year">
                            <i class="fas fa-calendar mr-1"></i>Year
                        </button>
                    </div>
                    <!-- Date Input -->
                    <input type="date" id="dateFilter" class="px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <input type="month" id="monthFilter" class="px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent hidden">
                    <input type="number" id="yearFilter" placeholder="YYYY" min="2000" max="2099" class="px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent w-24 hidden">
                    <button id="resetFilter" class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-all">
                        <i class="fas fa-redo mr-1"></i>Reset
                    </button>
                    <button id="printBtn" class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-all">
                        <i class="fas fa-print mr-1"></i>Print
                    </button>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Time In</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Time In Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Time Out</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Time Out Status</th>
                        <th scope="col" class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody id="attendance-table-body" class="bg-white divide-y divide-gray-200">
                    <!-- Populated by JS -->
                </tbody>
            </table>
        </div>
    </section>
</main>

<!-- Details modal -->
<div id="attendance-details-modal" class="fixed inset-0 hidden items-center justify-center modal-bg z-50" role="dialog" aria-labelledby="modal-title" aria-modal="true">
    <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-4">
            <h4 id="modal-title" class="text-lg font-semibold text-gray-800">Attendance Details</h4>
            <button id="close-details" class="text-gray-400 hover:text-gray-600 text-2xl leading-none" aria-label="Close modal">&times;</button>
        </div>
        <div id="attendance-details-content" class="space-y-3"></div>
    </div>
</div>

<!-- Print Modal -->
<div id="print-modal" class="print-modal">
    <div class="print-modal-content">
        <h2><i class="fas fa-print"></i> Print Attendance Records</h2>
        <p style="color: #6b7280; margin-bottom: 20px;">You are about to print your attendance records based on current filter:</p>
        <div id="print-preview-info" style="background: #f3f4f6; padding: 16px; border-radius: 8px; margin-bottom: 20px;"></div>
        <div class="print-modal-buttons">
            <button onclick="closePrintModal()" style="background: #e5e7eb; color: #374151;">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button onclick="executePrint()" style="background: #3b82f6; color: #fff;">
                <i class="fas fa-print"></i> Print Now
            </button>
        </div>
    </div>
</div>

<!-- Hidden print area -->
<div id="print-area" style="display: none;"></div>

<script>
    window.SERVER_ATTENDANCE = <?= json_encode($payload, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Notifications
    const bell = document.getElementById('notification-bell');
    const badge = document.getElementById('notification-badge');
    const dropdown = document.getElementById('notification-dropdown');

    function renderNotifications(notifs) {
        const nl = document.getElementById('notification-list');
        nl.innerHTML = '';
        if (!notifs || notifs.length === 0) {
            nl.innerHTML = '<div class="text-gray-500 p-2">No notifications.</div>';
            if (badge) badge.style.display = 'none';
            return;
        }
        const hasUnread = notifs.some(n => !n.read);
        if (badge) badge.style.display = hasUnread ? 'block' : 'none';

        notifs.forEach(n => {
            const card = document.createElement('div');
            card.className = 'bg-yellow-50 border border-yellow-200 rounded p-3 shadow-sm';
            const readClass = n.read ? 'opacity-70' : 'opacity-100';
            card.innerHTML = `
                <div class="flex justify-between ${readClass}">
                    <div>
                        <div class="font-semibold text-sm text-yellow-800">Notification:</div>
                        <div class="text-sm text-gray-700">${n.message}</div>
                    </div>
                    <div class="text-xs text-gray-500 ml-4">${n.created_at}</div>
                </div>
            `;
            nl.appendChild(card);
        });
    }

    function loadNotifications() {
        fetch('notifications.php')
           .then(r => r.json())
           .then(data => renderNotifications((data.success && Array.isArray(data.data)) ? data.data : []))
           .catch(() => {
               const nl = document.getElementById('notification-list');
               nl.innerHTML = '<div class="text-gray-500 p-2">Failed to load notifications.</div>';
           });
    }

    bell?.addEventListener('click', () => {
        dropdown.classList.toggle('hidden');
        if (!dropdown.classList.contains('hidden')) loadNotifications();
    });

    document.addEventListener('click', (e) => {
        if (!bell?.contains(e.target) && !dropdown?.contains(e.target)) {
            dropdown?.classList.add('hidden');
        }
    });

    const markAllBtn = document.getElementById('markAllReadBtn');
    const clearBtn = document.getElementById('clearNotifBtn');
    markAllBtn?.addEventListener('click', () => {
        fetch('notifications.php?action=mark_all_read', { method: 'POST' }).then(loadNotifications);
    });
    clearBtn?.addEventListener('click', () => {
        if (!confirm('Clear all notifications?')) return;
        fetch('notifications.php?action=clear_all', { method: 'POST' }).then(loadNotifications);
    });

    // Profile Modal
    const profileIcon = document.getElementById('profileIcon');
    const profileModal = document.getElementById('profileModal');
    const closeProfileModal = document.getElementById('closeProfileModal');

    profileIcon?.addEventListener('click', () => {
        profileModal.classList.remove('hidden');
        profileModal.classList.add('flex');
    });
    closeProfileModal?.addEventListener('click', () => {
        profileModal.classList.add('hidden');
        profileModal.classList.remove('flex');
    });
    profileModal?.addEventListener('click', (e) => {
        if (e.target === profileModal) {
            profileModal.classList.add('hidden');
            profileModal.classList.remove('flex');
        }
    });

    const data = window.SERVER_ATTENDANCE || { user: {}, attendance: [], summary: {} };
    const attendanceRecords = data.attendance || [];
    const summary = data.summary || {};
    
    // Filter state
    let currentFilterType = 'day';
    let filteredRecords = [...attendanceRecords];

    // Filter functions
    function filterByDay(dateStr) {
        if (!dateStr) return attendanceRecords;
        return attendanceRecords.filter(rec => rec.date === dateStr);
    }

    function filterByMonth(monthStr) {
        if (!monthStr) return attendanceRecords;
        return attendanceRecords.filter(rec => rec.date.substring(0, 7) === monthStr);
    }

    function filterByYear(yearStr) {
        if (!yearStr) return attendanceRecords;
        return attendanceRecords.filter(rec => rec.date.substring(0, 4) === yearStr);
    }

    function applyFilter() {
        const dateFilter = document.getElementById('dateFilter');
        const monthFilter = document.getElementById('monthFilter');
        const yearFilter = document.getElementById('yearFilter');

        if (currentFilterType === 'day' && dateFilter.value) {
            filteredRecords = filterByDay(dateFilter.value);
        } else if (currentFilterType === 'month' && monthFilter.value) {
            filteredRecords = filterByMonth(monthFilter.value);
        } else if (currentFilterType === 'year' && yearFilter.value) {
            filteredRecords = filterByYear(yearFilter.value);
        } else {
            filteredRecords = [...attendanceRecords];
        }
        renderDailyRecords();
    }

    // Filter button handlers
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentFilterType = btn.dataset.filter;

            // Show/hide appropriate filter input
            document.getElementById('dateFilter').classList.toggle('hidden', currentFilterType !== 'day');
            document.getElementById('monthFilter').classList.toggle('hidden', currentFilterType !== 'month');
            document.getElementById('yearFilter').classList.toggle('hidden', currentFilterType !== 'year');
            
            // Reset filter values
            document.getElementById('dateFilter').value = '';
            document.getElementById('monthFilter').value = '';
            document.getElementById('yearFilter').value = '';
            filteredRecords = [...attendanceRecords];
            renderDailyRecords();
        });
    });

    // Filter input handlers
    document.getElementById('dateFilter').addEventListener('change', applyFilter);
    document.getElementById('monthFilter').addEventListener('change', applyFilter);
    document.getElementById('yearFilter').addEventListener('input', (e) => {
        if (e.target.value.length === 4) applyFilter();
    });

    // Reset filter
    document.getElementById('resetFilter').addEventListener('click', () => {
        document.getElementById('dateFilter').value = '';
        document.getElementById('monthFilter').value = '';
        document.getElementById('yearFilter').value = '';
        filteredRecords = [...attendanceRecords];
        renderDailyRecords();
    });

    // Render daily records table
    function renderDailyRecords() {
        const tbody = document.getElementById('attendance-table-body');
        tbody.innerHTML = '';
        if (filteredRecords.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">No attendance records found.</td></tr>';
            return;
        }
        filteredRecords.slice().reverse().forEach(rec => {
            // Time In Status Badge
            let timeInStatusBadge = '';
            if (rec.timeIn) {
                const timeInStatus = rec.timeInStatus || (rec.tardy ? 'Late' : 'Present');
                if (timeInStatus === 'Late') {
                    timeInStatusBadge = '<span class="inline-flex items-center text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 px-2 py-0.5"><i class="fas fa-clock mr-1"></i>Late</span>';
                } else if (timeInStatus === 'Present') {
                    timeInStatusBadge = '<span class="inline-flex items-center text-xs font-medium rounded-full bg-green-100 text-green-800 px-2 py-0.5"><i class="fas fa-check mr-1"></i>Present</span>';
                } else if (timeInStatus === 'Undertime') {
                    timeInStatusBadge = '<span class="inline-flex items-center text-xs font-medium rounded-full bg-amber-100 text-amber-800 px-2 py-0.5"><i class="fas fa-hourglass-half mr-1"></i>Undertime</span>';
                } else if (timeInStatus === 'Absent') {
                    timeInStatusBadge = '<span class="inline-flex items-center text-xs font-medium rounded-full bg-red-100 text-red-800 px-2 py-0.5"><i class="fas fa-times mr-1"></i>Absent</span>';
                } else {
                    timeInStatusBadge = '<span class="text-gray-400">—</span>';
                }
            } else {
                timeInStatusBadge = '<span class="text-gray-400">—</span>';
            }

            // Time Out Status Badge
            let timeOutStatusBadge = '';
            if (rec.timeOut) {
                const timeOutStatus = rec.timeOutStatus || (rec.undertime ? 'Undertime' : (rec.overtime ? 'Overtime' : 'Out'));
                if (timeOutStatus === 'Undertime') {
                    timeOutStatusBadge = '<span class="inline-flex items-center text-xs font-medium rounded-full bg-orange-100 text-orange-800 px-2 py-0.5"><i class="fas fa-user-clock mr-1"></i>Undertime</span>';
                } else if (timeOutStatus === 'Overtime') {
                    timeOutStatusBadge = '<span class="inline-flex items-center text-xs font-medium rounded-full bg-blue-100 text-blue-800 px-2 py-0.5"><i class="fas fa-business-time mr-1"></i>Overtime</span>';
                } else if (timeOutStatus === 'On-time' || timeOutStatus === 'Out') {
                    timeOutStatusBadge = '<span class="inline-flex items-center text-xs font-medium rounded-full bg-green-100 text-green-800 px-2 py-0.5"><i class="fas fa-check mr-1"></i>Out</span>';
                } else {
                    timeOutStatusBadge = '<span class="text-gray-400">—</span>';
                }
            } else {
                timeOutStatusBadge = '<span class="text-gray-400">—</span>';
            }

            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50 transition';
            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${rec.date}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">${rec.timeIn || '<span class="text-gray-400">—</span>'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">${timeInStatusBadge}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">${rec.timeOut || '<span class="text-gray-400">—</span>'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">${timeOutStatusBadge}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                    <button data-id="${rec.id}" class="view-details-btn text-blue-600 hover:text-blue-800 font-medium">Details</button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    // Render all charts
    let trendChart = null;
    
    function renderCharts() {
        // Attendance Trend Line Chart (Last 30 days or available data)
        const ctx4 = document.getElementById('trendChart').getContext('2d');
        
        // Prepare trend data (last 30 days)
        const trendData = [];
        const trendLabels = [];
        const sortedRecords = [...attendanceRecords].sort((a, b) => new Date(a.date) - new Date(b.date));
        const last30 = sortedRecords.slice(-30);
        
        let cumulativeActive = 0; // Active = Present + Late
        last30.forEach((rec, idx) => {
            // Count as active if time_in_status is Present or Late
            if (rec.timeInStatus === 'Present' || rec.timeInStatus === 'Late') {
                cumulativeActive++;
            }
            trendLabels.push(new Date(rec.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
            trendData.push(Math.round((cumulativeActive / (idx + 1)) * 100));
        });

        if (trendChart) trendChart.destroy();
        trendChart = new Chart(ctx4, {
            type: 'line',
            data: {
                labels: trendLabels.length > 0 ? trendLabels : ['No Data'],
                datasets: [{ 
                    label: 'Attendance Rate (%)',
                    data: trendData.length > 0 ? trendData : [0],
                    borderColor: '#6366F1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false,
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        max: 100,
                        ticks: { 
                            callback: (value) => value + '%',
                            font: { size: 10 }
                        } 
                    },
                    x: { 
                        ticks: { font: { size: 9 }, maxRotation: 45, minRotation: 45 },
                        grid: { display: false }
                    }
                }, 
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `Rate: ${ctx.parsed.y}%`
                        }
                    }
                } 
            }
        });
    }

    // Details modal
    const detailsModal = document.getElementById('attendance-details-modal');
    const detailsContent = document.getElementById('attendance-details-content');
    document.body.addEventListener('click', (e) => {
        if (e.target.classList.contains('view-details-btn')) {
            const id = parseInt(e.target.dataset.id, 10);
            const rec = attendanceRecords.find(r => r.id === id);
            if (!rec) return;
            // Build status badges (same mapping as table rows)
            let timeInStatusBadge = '<span class="text-gray-400">—</span>';
            if (rec.timeIn) {
                const timeInStatus = rec.timeInStatus || (rec.tardy ? 'Late' : 'Present');
                if (timeInStatus === 'Late') {
                    timeInStatusBadge = '<span class="inline-flex items-center text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 px-2 py-0.5"><i class="fas fa-clock mr-1"></i>Late</span>';
                } else if (timeInStatus === 'Present') {
                    timeInStatusBadge = '<span class="inline-flex items-center text-xs font-medium rounded-full bg-green-100 text-green-800 px-2 py-0.5"><i class="fas fa-check mr-1"></i>Present</span>';
                } else if (timeInStatus === 'Undertime') {
                    timeInStatusBadge = '<span class="inline-flex items-center text-xs font-medium rounded-full bg-amber-100 text-amber-800 px-2 py-0.5"><i class="fas fa-hourglass-half mr-1"></i>Undertime</span>';
                } else if (timeInStatus === 'Absent') {
                    timeInStatusBadge = '<span class="inline-flex items-center text-xs font-medium rounded-full bg-red-100 text-red-800 px-2 py-0.5"><i class="fas fa-times mr-1"></i>Absent</span>';
                }
            }

            let timeOutStatusBadge = '<span class="text-gray-400">—</span>';
            if (rec.timeOut) {
                const timeOutStatus = rec.timeOutStatus || (rec.undertime ? 'Undertime' : (rec.overtime ? 'Overtime' : 'Out'));
                if (timeOutStatus === 'Undertime') {
                    timeOutStatusBadge = '<span class="inline-flex items-center text-xs font-medium rounded-full bg-orange-100 text-orange-800 px-2 py-0.5"><i class="fas fa-user-clock mr-1"></i>Undertime</span>';
                } else if (timeOutStatus === 'Overtime') {
                    timeOutStatusBadge = '<span class="inline-flex items-center text-xs font-medium rounded-full bg-blue-100 text-blue-800 px-2 py-0.5"><i class="fas fa-business-time mr-1"></i>Overtime</span>';
                } else if (timeOutStatus === 'On-time' || timeOutStatus === 'Out') {
                    timeOutStatusBadge = '<span class="inline-flex items-center text-xs font-medium rounded-full bg-green-100 text-green-800 px-2 py-0.5"><i class="fas fa-check mr-1"></i>Out</span>';
                }
            }
            detailsContent.innerHTML = `
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-xs text-gray-500">Date</p>
                        <p class="text-sm font-medium text-gray-900">${rec.date}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Time In</p>
                        <p class="text-sm font-medium text-gray-900">${rec.timeIn || '—'}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Time Out</p>
                        <p class="text-sm font-medium text-gray-900">${rec.timeOut || '—'}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Time In Status</p>
                        <p class="text-sm font-medium text-gray-900">${timeInStatusBadge}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Time Out Status</p>
                        <p class="text-sm font-medium text-gray-900">${timeOutStatusBadge}</p>
                    </div>
                </div>
                ${rec.tardy || rec.undertime || rec.overtime ? `<div class="mt-3 pt-3 border-t border-gray-200">
                    <p class="text-xs text-gray-500 mb-2">Flags</p>
                    <div class="flex gap-2 flex-wrap">
                        ${rec.tardy ? '<span class="inline-flex items-center text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 px-2 py-0.5"><i class="fas fa-clock mr-1"></i>Late</span>' : ''}
                        ${rec.undertime ? '<span class="inline-flex items-center text-xs font-medium rounded-full bg-orange-100 text-orange-800 px-2 py-0.5"><i class="fas fa-user-clock mr-1"></i>Undertime</span>' : ''}
                        ${rec.overtime ? '<span class="inline-flex items-center text-xs font-medium rounded-full bg-blue-100 text-blue-800 px-2 py-0.5"><i class="fas fa-business-time mr-1"></i>Overtime</span>' : ''}
                    </div>
                </div>` : ''}
            `;
            detailsModal.classList.remove('hidden');
        }
        if (e.target.id === 'close-details') {
            detailsModal.classList.add('hidden');
        }
    });

    // Close modal on outside click
    detailsModal.addEventListener('click', (e) => {
        if (e.target === detailsModal) detailsModal.classList.add('hidden');
    });

    // Print functions
    document.getElementById('printBtn').addEventListener('click', openPrintModal);

    function openPrintModal() {
        if (filteredRecords.length === 0) {
            alert('No attendance records to print. Please adjust your filter.');
            return;
        }

        let info = '<div style="margin-bottom: 12px;"><strong>Print Preview:</strong></div>';
        info += '<ul style="list-style: none; padding: 0;">';

        // Filter info
        if (currentFilterType === 'day') {
            const date = document.getElementById('dateFilter').value;
            if (date) {
                info += `<li style="margin-bottom: 8px;"><i class="fas fa-calendar" style="color: #3b82f6; margin-right: 8px;"></i><strong>Date:</strong> ${date}</li>`;
            }
        } else if (currentFilterType === 'month') {
            const month = document.getElementById('monthFilter').value;
            if (month) {
                info += `<li style="margin-bottom: 8px;"><i class="fas fa-calendar-alt" style="color: #3b82f6; margin-right: 8px;"></i><strong>Month:</strong> ${month}</li>`;
            }
        } else if (currentFilterType === 'year') {
            const year = document.getElementById('yearFilter').value;
            if (year) {
                info += `<li style="margin-bottom: 8px;"><i class="fas fa-calendar" style="color: #3b82f6; margin-right: 8px;"></i><strong>Year:</strong> ${year}</li>`;
            }
        }

        info += `<li style="margin-bottom: 8px;"><i class="fas fa-list" style="color: #10b981; margin-right: 8px;"></i><strong>Total Records:</strong> ${filteredRecords.length}</li>`;
        info += '</ul>';

        document.getElementById('print-preview-info').innerHTML = info;
        document.getElementById('print-modal').classList.add('active');
    }

    window.closePrintModal = function() {
        document.getElementById('print-modal').classList.remove('active');
    }

    window.executePrint = function() {
        if (filteredRecords.length === 0) return;

        const userData = window.SERVER_ATTENDANCE.user;

        // Generate print HTML
        let printHTML = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Attendance Record</title>
            <style>
                @page { size: auto; margin: 15mm; }
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { 
                    font-family: 'Arial', 'Helvetica', sans-serif; 
                    padding: 30px;
                    color: #333;
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 30px;
                    padding-bottom: 20px;
                    border-bottom: 4px solid #2563eb;
                }
                .logo-section {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 20px;
                    margin-bottom: 15px;
                }
                .logo {
                    width: 90px;
                    height: 90px;
                    border: 3px solid #2563eb;
                    border-radius: 50%;
                    padding: 5px;
                }
                .municipality-name {
                    font-size: 22px;
                    font-weight: 700;
                    color: #1e40af;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                }
                .title { 
                    font-size: 26px; 
                    font-weight: 700; 
                    margin: 20px 0 15px 0;
                    letter-spacing: 0.5px;
                    line-height: 1.4;
                }
                .title .month { 
                    color: #2563eb; 
                    font-weight: 800;
                }
                .title .year { 
                    color: #dc2626; 
                    font-weight: 800;
                }
                .employee-info {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin: 25px 0;
                    padding: 18px 24px;
                    background: linear-gradient(135deg, #f8fafc 0%, #e0e7ff 100%);
                    border: 2px solid #2563eb;
                    border-radius: 10px;
                    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.15);
                }
                .employee-info div {
                    font-size: 15px;
                    font-weight: 600;
                    color: #1e40af;
                }
                .employee-info strong {
                    font-weight: 800;
                    text-transform: uppercase;
                    color: #1e293b;
                    margin-right: 8px;
                }
                .employee-info span {
                    color: #334155;
                    font-weight: 500;
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin: 25px 0;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                }
                th { 
                    background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
                    color: white;
                    font-weight: 700;
                    text-transform: uppercase;
                    font-size: 13px;
                    letter-spacing: 0.5px;
                    padding: 14px 12px;
                    border: 2px solid #1e40af;
                }
                td { 
                    font-size: 14px;
                    padding: 12px;
                    border: 1px solid #cbd5e1;
                    background: white;
                    color: #1e293b;
                }
                tr:nth-child(even) td {
                    background: #f8fafc;
                }
                tr:hover td {
                    background: #e0f2fe;
                }
                .date-col { 
                    width: 25%; 
                    font-weight: 600;
                    color: #0f172a;
                }
                .time-col { 
                    width: 37.5%; 
                    text-align: center;
                }
                .footer {
                    margin-top: 40px;
                    padding-top: 20px;
                    border-top: 2px solid #cbd5e1;
                    text-align: center;
                    font-size: 11px;
                    color: #64748b;
                }
                .footer strong {
                    color: #1e40af;
                    font-weight: 600;
                }
            </style>
        </head>
        <body>
        `;

        // Get period label
        let monthName = '';
        let yearValue = '';

        if (currentFilterType === 'day') {
            const dateVal = document.getElementById('dateFilter').value;
            if (dateVal) {
                const date = new Date(dateVal);
                monthName = date.toLocaleString('en-US', { month: 'long' }).toUpperCase();
                yearValue = date.getFullYear();
            } else {
                monthName = 'ALL DATES';
                yearValue = new Date().getFullYear();
            }
        } else if (currentFilterType === 'month') {
            const monthVal = document.getElementById('monthFilter').value;
            if (monthVal) {
                const date = new Date(monthVal + '-01');
                monthName = date.toLocaleString('en-US', { month: 'long' }).toUpperCase();
                yearValue = date.getFullYear();
            } else {
                monthName = 'ALL MONTHS';
                yearValue = new Date().getFullYear();
            }
        } else if (currentFilterType === 'year') {
            const yearVal = document.getElementById('yearFilter').value;
            yearValue = yearVal || new Date().getFullYear();
            monthName = 'ALL MONTHS';
        }

        printHTML += `
        <div class="header">
            <div class="logo-section">
                <img src="../assets/logo.png" alt="Municipality Logo" class="logo">
                <div class="municipality-name">Municipality of Mabini</div>
            </div>
            <div class="title">
                <span class="month">${monthName}</span> 
                <span class="year">${yearValue}</span> 
                ATTENDANCE RECORD
            </div>
        </div>

        <div class="employee-info">
            <div><strong>Fullname of the User:</strong> <span>${userData.name}</span></div>
            <div><strong>ID of the User:</strong> <span>${userData.employee_id}</span></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="date-col">DATE</th>
                    <th class="time-col">TIME IN</th>
                    <th class="time-col">TIME OUT</th>
                </tr>
            </thead>
            <tbody>
        `;

        // Add records
        filteredRecords.forEach(rec => {
            const date = rec.date || '';
            const timeIn = rec.timeIn || '—';
            const timeOut = rec.timeOut || '—';

            printHTML += `
                <tr>
                    <td class="date-col">${date}</td>
                    <td class="time-col">${timeIn}</td>
                    <td class="time-col">${timeOut}</td>
                </tr>
            `;
        });

        printHTML += `
            </tbody>
        </table>
        
        <div class="footer">
            <p><strong>Municipality of Mabini</strong> - Official Attendance Record</p>
            <p>Generated on ${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
        </div>
        </body>
        </html>
        `;

        // Create hidden iframe for printing
        const printFrame = document.createElement('iframe');
        printFrame.style.display = 'none';
        document.body.appendChild(printFrame);

        const doc = printFrame.contentWindow.document;
        doc.open();
        doc.write(printHTML);
        doc.close();

        // Wait for images to load then print
        printFrame.contentWindow.onload = function() {
            setTimeout(() => {
                printFrame.contentWindow.focus();
                printFrame.contentWindow.print();
                setTimeout(() => {
                    document.body.removeChild(printFrame);
                    closePrintModal();
                }, 100);
            }, 250);
        };
    }

    // Initial render
    renderDailyRecords();
    renderCharts();
});
</script>

</body>
</html>
