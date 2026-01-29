<?php
// dashboard.php
require_once __DIR__ . '/../auth_guard.php';
require_role('employee');
require_once '../db.php';

// Helper: format name as "firstname MI. lastname" (uppercase)
function format_dept_head_name($firstname, $mi, $lastname) {
  $firstname = trim((string)$firstname);
  $mi = trim((string)$mi);
  $lastname = trim((string)$lastname);
  
  $parts = [];
  if ($firstname !== '') $parts[] = $firstname;
  if ($mi !== '') {
    // Take first character and add dot
    $parts[] = strtoupper(mb_substr($mi, 0, 1, 'UTF-8')) . '.';
  }
  if ($lastname !== '') $parts[] = $lastname;
  
  $name = implode(' ', $parts);
  return mb_strtoupper($name, 'UTF-8');
}

// Auto-mark absent at 17:00 if not already run today (runs for all approved users)
date_default_timezone_set('Asia/Manila');
$nowH = (int)date('H');
if ($nowH >= 17) {
    $lastRunFile = __DIR__ . '/../attendance/last_absent_run.txt';
    $last = is_readable($lastRunFile) ? trim(file_get_contents($lastRunFile)) : '';
    if ($last !== date('Y-m-d')) {
        // include the mark_absent script but suppress its output
        ob_start();
        @include_once __DIR__ . '/../attendance/mark_absent.php';
        @ob_end_clean();
    }
}
$attendanceFlash = null;
// Map ?att= flags (set by QR login flow) to human messages
if (!empty($_GET['att'])) {
    $att = $_GET['att'];
    if ($att === 'timein_ok') {
        $attendanceFlash = ['text' => '✓ Time In recorded', 'type' => 'success'];
    } elseif ($att === 'timeout_ok' || $att === 'time_out_ok') {
        $attendanceFlash = ['text' => '✓ Time Out recorded', 'type' => 'success'];
    } elseif ($att === 'already_timedout') {
        $attendanceFlash = ['text' => 'ℹ️ Time Out was already recorded for today', 'type' => 'info'];
    } elseif ($att === 'failed') {
        $attendanceFlash = ['text' => '⚠️ Attendance recording failed. Please try again.', 'type' => 'error'];
    }
}
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT firstname, lastname, mi, position, profile_picture, email FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user) {
    $fullName = format_dept_head_name($user['firstname'], $user['mi'] ?? '', $user['lastname']);
    $position = $user['position'];
    $profilePicture = $user['profile_picture'] ?? '';
    $userEmail = $user['email'] ?? '';
} else {
    $fullName = 'Employee';
    $position = '';
    $profilePicture = '';
    $userEmail = '';
}
// Check if user is JO or OJT (they should not have leave access)
$positionLower = strtolower(trim($position));
$isJoOrOjt = (strpos($positionLower, 'jo') !== false || strpos($positionLower, 'ojt') !== false);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
        .container {
            display: block;
        }
        .modal-bg {
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
        }
        /* To-dos analytics sticky header and layout tweaks */
        #todos-analytics { position: sticky; top: 1.25rem; align-self: start; }
        #todos-analytics .overview-header { position: sticky; top: 0.75rem; z-index: 20; background: transparent; padding-top: 0.25rem; }
        #todos-list { max-height: 36rem; overflow-y: auto; padding-right: 0.5rem; }

        /* Keep analytics panel in normal flow; on larger screens only adjust list height.
           Avoid position:fixed to preserve original layout. */
        #todos-last-updated { display: inline-block; width: 7.5rem; text-align: right; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, 'Roboto Mono', 'Courier New', monospace; color: #9CA3AF; }
        @media (min-width: 768px) {
            /* Slightly increase list height on larger viewports but keep the analytics panel in-flow */
            #todos-list { max-height: calc(100vh - 14rem); }
        }

        /* Announcement/Events section styling */
        .announcement-container {
            max-height: 500px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .announcement-container::-webkit-scrollbar {
            width: 8px;
        }

        .announcement-container::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        .announcement-container::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .announcement-container::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .event-item {
            display: flex;
            align-items: flex-start;
            gap: 1.25rem;
            padding: 1.25rem;
            margin-bottom: 0.75rem;
            border-radius: 0.75rem;
            border: 1px solid #e5e7eb;
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
            transition: all 0.3s ease;
        }

        .event-item:hover {
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
            transform: translateY(-2px);
        }

        .event-item:last-child {
            margin-bottom: 0;
        }

        .event-date {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            text-align: center;
            line-height: 1;
            min-width: 70px;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }

        .event-date .day {
            font-size: 1.75rem;
            font-weight: 700;
            display: block;
            color: #ffffff;
        }

        .event-date .month {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 600;
            color: #dbeafe;
            margin-top: 0.25rem;
        }

        .event-details {
            flex-grow: 1;
        }

        .event-details .event-title {
            font-weight: 700;
            font-size: 1rem;
            color: #1f2937;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .event-details .event-description {
            font-size: 0.875rem;
            color: #6b7280;
            line-height: 1.5;
            margin-bottom: 0.5rem;
        }

        .event-time {
            font-size: 0.8rem;
            color: #3b82f6;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .event-time i {
            font-size: 0.75rem;
        }
    </style>
</head>
<body class="bg-gray-100 p-6 lg:p-10">

    <header class="bg-white rounded-xl shadow-md p-4 flex items-center justify-between z-50 sticky top-0">
        <div class="flex items-center space-x-4">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                <img src="../assets/logo.png" alt="Logo" class="rounded-full">
            </div>
            <h1 id="header-title" class="text-xl font-bold text-gray-800">Dashboard</h1>
        </div>
        <div class="flex items-center space-x-4">
            <div id="notification-bell-container" class="relative">
                <button id="notification-bell" class="text-gray-600 hover:text-blue-600 transition-colors relative">
                    <i class="fas fa-bell text-lg"></i>
                    <span id="notification-badge" style="display:none;" class="absolute -top-2 -right-2 bg-red-600 text-white rounded-full px-2 py-0.5 text-xs font-bold">!</span>
                </button>
                <div id="notification-dropdown" class="hidden absolute right-0 mt-2 w-96 bg-white border border-gray-200 rounded shadow-lg z-50">
                    <div class="flex items-center justify-between px-4 py-2 border-b">
                        <strong class="text-sm">Notifications</strong>
                        <div class="flex items-center gap-2">
                            <button id="markAllReadBtn" class="text-xs text-blue-600 hover:underline">Mark all read</button>
                            <button id="clearNotifBtn" class="text-xs text-red-600 hover:underline">Clear</button>
                        </div>
                    </div>
                    <div id="notification-list" class="p-3 space-y-3 max-h-80 overflow-y-auto"></div>
                    <div class="px-4 py-2 border-t text-center text-xs text-gray-500">Showing latest notifications</div>
                </div>
            </div>
            <img id="profileIcon" src="<?php echo $profilePicture ? htmlspecialchars($profilePicture) : 'https://placehold.co/40x40/FF5733/FFFFFF?text=P'; ?>" alt="Profile" class="w-10 h-10 rounded-full cursor-pointer">
            <!-- Profile Modal -->
            <div id="profileModal" class="fixed inset-0 hidden items-center justify-center modal-bg z-50">
                <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-xs mx-4 flex flex-col items-center">
                    <img id="profileModalPhoto" src="<?php echo $profilePicture ? htmlspecialchars($profilePicture) : 'https://placehold.co/80x80/FFD700/000000?text=W+P'; ?>" alt="Profile" class="w-20 h-20 rounded-full mb-4">
                    <button id="logoutBtn" class="w-full px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 mb-2">Log out</button>
                    <button id="closeProfileModal" class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">Cancel</button>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-grow p-4 overflow-y-auto mt-6">
        <div id="dashboard-page" class="container">
            <?php if (!empty($attendanceFlash)): ?>
                <?php
                    // Prepare formatted time for display
                    $attTimeRaw = $_GET['att_time'] ?? null;
                    if ($attTimeRaw) {
                        try {
                            $dt = new DateTime($attTimeRaw, new DateTimeZone('Asia/Manila'));
                            $attTime = $dt->format('g:i A');
                        } catch (Exception $e) {
                            $attTime = htmlspecialchars($attTimeRaw);
                        }
                    } else {
                        $attTime = '';
                    }

                    // Attendance status (e.g. Present, Late, Undertime, Overtime)
                    $attStatusRaw = $_GET['att_status'] ?? '';
                    $attStatus = $attStatusRaw ? htmlspecialchars($attStatusRaw) : '';
                    // choose a subtle color for the badge based on status
                    $statusColor = '#6b7280'; // default gray
                    $s = strtolower($attStatusRaw);
                    if (strpos($s, 'present') !== false) $statusColor = '#10b981';
                    elseif (strpos($s, 'late') !== false) $statusColor = '#f59e0b';
                    elseif (strpos($s, 'undertime') !== false) $statusColor = '#fb923c';
                    elseif (strpos($s, 'on-time') !== false || strpos($s, 'ontime') !== false) $statusColor = '#3b82f6';
                    elseif (strpos($s, 'overtime') !== false) $statusColor = '#6366f1';
                ?>

                <!-- Small top-right attendance popup (auto-hide after 3s) -->
                <div id="attendanceToast" style="position:fixed;right:20px;top:20px;z-index:1050;display:none;">
                    <div style="display:flex;align-items:center;gap:12px;background:#fff;padding:12px 14px;border-radius:10px;box-shadow:0 8px 30px rgba(0,0,0,0.15);min-width:260px;">
                        <img src="<?php echo $profilePicture ? htmlspecialchars($profilePicture) : 'https://placehold.co/48x48/FFD700/000000?text=W+P'; ?>" alt="Profile" style="width:48px;height:48px;border-radius:8px;object-fit:cover;"/>
                        <div style="flex:1;">
                            <div style="font-weight:700;color:#111827;"><?php echo htmlspecialchars($fullName); ?></div>
                            <div style="font-size:13px;color:#374151;margin-top:4px;">
                                <?php echo htmlspecialchars($attendanceFlash['text']); ?>
                                <?php if ($attStatus): ?>
                                    <div style="margin-top:6px;">
                                        <span style="display:inline-block;padding:4px 8px;border-radius:999px;color:#ffffff;background:<?php echo $statusColor; ?>;font-size:12px;font-weight:600;"><?php echo $attStatus; ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($attTime): ?>
                                    <div style="font-size:12px;color:#6b7280;margin-top:6px;">Recorded at <strong><?php echo htmlspecialchars($attTime); ?></strong></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <button id="attendanceDismiss" aria-label="Close" style="background:transparent;border:none;color:#6b7280;font-size:18px;cursor:pointer;">&times;</button>
                    </div>
                </div>

                <script>
                    (function(){
                        const toast = document.getElementById('attendanceToast');
                        const dismiss = document.getElementById('attendanceDismiss');
                        function show() {
                            toast.style.display = 'block';
                            toast.style.opacity = '0';
                            toast.style.transition = 'opacity 0.2s ease';
                            requestAnimationFrame(() => { toast.style.opacity = '1'; });
                            // auto-hide after 3 seconds
                            setTimeout(hide, 3000);
                        }
                        function hide() {
                            toast.style.opacity = '0';
                            setTimeout(() => { toast.style.display = 'none'; }, 250);
                        }
                        dismiss.addEventListener('click', hide);
                        // click outside dismiss not needed — small toast has dismiss button
                        show();
                    })();
                </script>
            <?php endif; ?>
            <div class="bg-blue-600 w-full rounded-xl shadow-lg p-6 flex flex-col items-start text-white relative overflow-hidden mb-6">
                <div class="absolute inset-0 bg-blue-700 bg-opacity-20 backdrop-blur-sm z-10"></div>
                <div class="relative z-20 flex items-center space-x-6">
                    <img id="profilePicture" src="<?php echo $profilePicture ? htmlspecialchars($profilePicture) : 'https://placehold.co/80x80/FFD700/000000?text=W+P'; ?>" alt="Profile" class="w-20 h-20 rounded-full border-4 border-white">
                    <div class="flex flex-col text-left">
                        <h2 id="profileName" class="text-3xl font-extrabold tracking-tight"><?php echo htmlspecialchars($fullName); ?></h2>
                        <p id="profilePosition" class="text-sm opacity-80 mt-1"><?php echo htmlspecialchars($position); ?></p>
                    </div>
                    <button id="editProfileBtn" class="bg-white text-blue-600 text-sm px-3 py-1 rounded-full hover:bg-gray-100 transition-colors">
                        Edit Profile
                    </button>
                </div>
            </div>

            <div class="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-4">
                <div class="bg-white rounded-xl shadow-md p-6 lg:col-span-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
                    <div class="flex flex-wrap justify-around gap-4 text-center">
                        <?php if (!$isJoOrOjt): ?>
                        <a href="apply_leave.php" class="flex flex-col items-center space-y-2 cursor-pointer">
                            <div class="bg-blue-100 p-4 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M17 17h.01" />
                                </svg>
                            </div>
                            <span class="text-sm text-gray-600">Apply for Leave</span>
                        </a>
                        <a href="leave_status.php" class="flex flex-col items-center space-y-2 cursor-pointer">
                            <div class="bg-purple-100 p-4 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z" />
                                </svg>
                            </div>
                            <span class="text-sm text-gray-600">Leave Status</span>
                        </a>
                        <?php endif; ?>
                        <a href="task.php" class="flex flex-col items-center space-y-2 cursor-pointer">
                            <div class="bg-green-100 p-4 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <span class="text-sm text-gray-600">Task</span>
                        </a>
                        <!-- My QR Code removed: personal QR page removed per new rotating QR flow -->
                        <a href="attendance.php" class="flex flex-col items-center space-y-2 cursor-pointer">
                            <div class="bg-yellow-100 p-4 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="text-sm text-gray-600">View Attendance</span>
                        </a>
                        <a href="events.php" class="flex flex-col items-center space-y-2 cursor-pointer">
                            <div class="bg-teal-100 p-4 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h.01M7 11h.01M16 11h.01m-2-2h.01m-6-4h.01M8 21h8a2 2 0 002-2V7a2 2 0 00-2-2H8a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <span class="text-sm text-gray-600">Events</span>
                        </a>
                    </div>
                </div>

                <?php if (!$isJoOrOjt): ?>
                <div class="bg-white rounded-xl shadow-md p-6 lg:col-span-2">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Leave Credits</h3>
                    <div id="leave-credits-container" class="relative">
                        <div id="leave-credits-grid" class="grid grid-cols-2 gap-4 max-h-[32rem] overflow-y-auto pr-2">
                            <div class="text-center text-gray-500 p-6">Loading leave credits...</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="bg-white rounded-xl shadow-md p-6 lg:col-span-2">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">To-dos</h3>
                        <div class="flex space-x-2 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 100-2 1 1 0 000 2zm0 7a1 1 0 100-2 1 1 0 000 2zm0 7a1 1 0 100-2 1 1 0 000 2z" />
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                    <div id="todos-grid" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <div id="todos-list" class="space-y-3">
                                <!-- Real-time tasks will be injected here as professional cards -->
                            </div>
                        </div>
                        <div id="todos-analytics" class="md:col-span-1 bg-gray-50 p-4 rounded-lg">
                            <div class="flex items-center justify-between mb-3 overview-header">
                                <h4 class="text-sm font-semibold text-gray-700">Tasks Overview</h4>
                                <span id="todos-last-updated" class="text-xs text-gray-400">--</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 mb-3">
                                <div class="p-2 bg-white rounded shadow-sm text-center">
                                    <div class="text-xs text-gray-500">Pending</div>
                                    <div id="count-pending" class="text-xl font-bold text-yellow-600">0</div>
                                </div>
                                <div class="p-2 bg-white rounded shadow-sm text-center">
                                    <div class="text-xs text-gray-500">In Progress</div>
                                    <div id="count-inprogress" class="text-xl font-bold text-blue-600">0</div>
                                </div>
                                <div class="p-2 bg-white rounded shadow-sm text-center">
                                    <div class="text-xs text-gray-500">Completed</div>
                                    <div id="count-completed" class="text-xl font-bold text-green-600">0</div>
                                </div>
                                <div class="p-2 bg-white rounded shadow-sm text-center">
                                    <div class="text-xs text-gray-500">Missed</div>
                                    <div id="count-missed" class="text-xl font-bold text-red-600">0</div>
                                </div>
                            </div>
                            <div class="bg-white p-3 rounded shadow-sm">
                                <canvas id="todosChart" width="200" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6 lg:col-span-2">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Attendance Comparison Chart</h3>
                        <div class="flex items-center space-x-4 text-sm text-gray-600">
                            <label class="flex items-center space-x-1">
                                <input type="radio" name="chart-period" value="daily" checked>
                                <span>Daily</span>
                            </label>
                            <label class="flex items-center space-x-1">
                                <input type="radio" name="chart-period" value="weekly">
                                <span>Week</span>
                            </label>
                            <label class="flex items-center space-x-1">
                                <input type="radio" name="chart-period" value="monthly">
                                <span>Month</span>
                            </label>
                        </div>
                    </div>
                    <canvas id="attendanceChart" class="mb-4"></canvas>
                    <div id="attendance-interpretation" class="mt-4 p-3 bg-blue-50 border-l-4 border-blue-500 text-sm text-gray-700 rounded">
                        <p class="text-xs text-gray-500 mb-1"><i class="fas fa-chart-line mr-1"></i> Chart Interpretation</p>
                        <p id="attendance-interpret-text">Loading interpretation...</p>
                    </div>
                </div>

                <!-- Decision Support System Section -->
                <div id="dss-section" class="bg-white rounded-xl shadow-md p-6 lg:col-span-2" style="display: none;">
                    <div class="flex items-center gap-2 mb-4">
                        <i class="fas fa-brain text-blue-600 text-xl"></i>
                        <h3 class="text-lg font-semibold text-gray-800">Smart Insights & Recommendations</h3>
                    </div>
                    <div id="dss-container" class="space-y-3">
                        <!-- DSS alerts will be populated here -->
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6 lg:col-span-2">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800"><i class="fas fa-bullhorn mr-2 text-blue-600"></i>Announcement(s)</h3>
                        <div class="flex space-x-2 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 100-2 1 1 0 000 2zm0 7a1 1 0 100-2 1 1 0 000 2zm0 7a1 1 0 100-2 1 1 0 000 2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="announcement-container">
                        <div id="events-list">
                            <!-- Events will be loaded here -->
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6 flex flex-col items-center justify-center">
                    <div id="miniPerfBadge" class="w-24 h-24 rounded-full bg-green-100 flex items-center justify-center font-bold text-green-600 text-lg">
                        <span id="miniPerformancePct">--%</span>
                    </div>
                    <h3 id="miniPerformanceLabel" class="text-2xl font-bold text-gray-800 mt-4">--</h3>
                    <p id="miniPerformanceDesc" class="text-sm text-gray-500 mt-1 text-center">Score compared to last month</p>
                    <div id="mini-performance-interpretation" class="mt-3 text-xs text-gray-600 text-center px-2">
                        <p id="mini-performance-interpret-text">--</p>
                    </div>
                    <button id="miniPerformanceDetailsBtn" class="bg-blue-600 rounded-full px-4 py-1 text-white text-xs mt-4">Details</button>
                </div>
            </div>
        </div>

        <div id="editProfileModal" class="fixed inset-0 hidden items-center justify-center modal-bg">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm mx-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Edit Profile</h3>
                    <button id="closeModalBtn" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <form id="editProfileForm">
                    <div class="mb-4 text-center">
                        <img id="profilePhotoPreview" src="<?php echo $profilePicture ? htmlspecialchars($profilePicture) : 'https://placehold.co/80x80/FFD700/000000?text=W+P'; ?>" alt="Profile" class="w-24 h-24 rounded-full mx-auto mb-2 border-4 border-gray-200">
                        <label class="cursor-pointer bg-gray-200 px-3 py-1 text-sm rounded-full hover:bg-gray-300">
                            Change Photo
                            <input type="file" id="photoInput" class="hidden" accept="image/*">
                        </label>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" id="cancelBtn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

    </main>

    <!-- Performance Details Modal -->
    <div id="perfInfoModal" class="fixed inset-0 hidden items-center justify-center modal-bg z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-lg mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 id="perfModalTitle" class="text-lg font-semibold text-gray-800">Performance Details</h3>
                <button id="perfModalClose" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            <div id="perfModalBody" class="text-sm text-gray-700 space-y-3">
                <div class="flex items-center gap-4">
                    <div id="perfModalPercent" class="w-20 h-20 rounded-full bg-green-100 flex items-center justify-center font-bold text-green-600">--%</div>
                    <div>
                        <div id="perfModalSummary" class="font-semibold text-gray-800">--</div>
                        <div id="perfModalDesc" class="text-gray-500 text-sm">--</div>
                    </div>
                </div>
                <div id="perfModalCounts" class="grid grid-cols-2 gap-2">
                    <div class="p-2 bg-gray-50 rounded"><div class="text-xs text-gray-500">Present</div><div id="perfPresent" class="font-semibold">0</div></div>
                    <div class="p-2 bg-gray-50 rounded"><div class="text-xs text-gray-500">Late</div><div id="perfLate" class="font-semibold">0</div></div>
                    <div class="p-2 bg-gray-50 rounded"><div class="text-xs text-gray-500">Undertime</div><div id="perfUndertime" class="font-semibold">0</div></div>
                    <div class="p-2 bg-gray-50 rounded"><div class="text-xs text-gray-500">Overtime</div><div id="perfOvertime" class="font-semibold">0</div></div>
                    <div class="p-2 bg-gray-50 rounded"><div class="text-xs text-gray-500">Absent</div><div id="perfAbsent" class="font-semibold">0</div></div>
                </div>
                <div id="perfModalRecommendation" class="text-sm text-gray-700"></div>
            </div>
        </div>
    </div>

    <script>
        // Realtime To-dos: professional UI + analytics (counts + donut) polling tasks assigned to the logged-in user
        (function(){
            const listEl = document.getElementById('todos-list');
            const lastUpdatedEl = document.getElementById('todos-last-updated');
            const countPendingEl = document.getElementById('count-pending');
            const countInprogressEl = document.getElementById('count-inprogress');
            const countCompletedEl = document.getElementById('count-completed');
            const countMissedEl = document.getElementById('count-missed');
            const chartEl = document.getElementById('todosChart');
            if (!listEl || !chartEl) return;

            let todosChart = null;
            function initChart(){
                try{
                    const ctx = chartEl.getContext('2d');
                    todosChart = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Pending','In Progress','Completed','Missed'],
                            datasets: [{
                                data: [0,0,0,0],
                                backgroundColor: ['#f59e0b','#3b82f6','#10b981','#ef4444']
                            }]
                        },
                        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth:12 } } } }
                    });
                }catch(e){ console.warn('Chart init failed', e); }
            }

            function formatDue(d){ if(!d) return ''; try{ const dt = new Date(d.replace(' ', 'T')); return dt.toLocaleString(); }catch(e){ return d; } }

            function renderList(tasks){
                const filtered = tasks.filter(t => t.status === 'pending' || t.status === 'in_progress');
                listEl.innerHTML = '';
                if (filtered.length === 0) {
                    listEl.innerHTML = '<div class="py-6 text-center text-sm text-gray-500">No pending tasks</div>';
                    return;
                }

                filtered.forEach(t => {
                    const card = document.createElement('div');
                    card.className = 'bg-white p-3 rounded-lg shadow-sm flex items-start justify-between gap-4 hover:shadow-md transition';

                    const left = document.createElement('div');
                    left.className = 'flex-1 min-w-0';
                    const title = document.createElement('div');
                    title.className = 'font-semibold text-gray-800 truncate';
                    title.textContent = t.title || '(untitled)';
                    const meta = document.createElement('div');
                    meta.className = 'text-xs text-gray-500 mt-1';
                    meta.textContent = (t.assigned_by_email?('Assigned by '+t.assigned_by_email):'') + (t.due_date?(' • due '+ formatDue(t.due_date)): '');
                    left.appendChild(title);
                    left.appendChild(meta);

                    const right = document.createElement('div');
                    right.className = 'flex flex-col items-end gap-2';
                    const badge = document.createElement('div');
                    badge.className = 'text-xs px-2 py-1 rounded-full font-semibold ' + (t.status==='pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800');
                    badge.textContent = t.status === 'pending' ? 'Pending' : 'In Progress';
                    const view = document.createElement('a');
                    view.className = 'text-xs text-gray-400 hover:text-blue-600';
                    view.href = 'task.php';
                    view.textContent = 'View';
                    right.appendChild(badge);
                    right.appendChild(view);

                    card.appendChild(left);
                    card.appendChild(right);
                    listEl.appendChild(card);
                });
            }

            function updateAnalytics(stats){
                countPendingEl.textContent = stats.pending;
                countInprogressEl.textContent = stats.in_progress;
                countCompletedEl.textContent = stats.completed;
                countMissedEl.textContent = stats.missed;
                if(todosChart && todosChart.data && Array.isArray(todosChart.data.datasets)){
                    todosChart.data.datasets[0].data = [stats.pending, stats.in_progress, stats.completed, stats.missed];
                    todosChart.update();
                }
                const now = new Date();
                if(lastUpdatedEl) lastUpdatedEl.textContent = now.toLocaleTimeString();
            }

            async function fetchTasks(){
                try{
                    const r = await fetch('../api/tasks_list_employee.php');
                    if (!r.ok) throw new Error('network');
                    const js = await r.json();
                    if (!js || !js.success || !Array.isArray(js.tasks)) {
                        listEl.innerHTML = '<div class="py-2 text-sm text-red-500">Failed to load tasks</div>';
                        return;
                    }

                    // compute missed flag and stats
                    const now = new Date();
                    let stats = { pending:0, in_progress:0, completed:0, missed:0 };
                    js.tasks.forEach(t => {
                        const status = (t.status||'').toLowerCase();
                        let isMissed = false;
                        if(t.due_date){ try{ const due = new Date((t.due_date||'').replace(' ', 'T')); if(due && !isNaN(due.getTime()) && due < now && status !== 'completed') isMissed = true; }catch(e){} }
                        if(isMissed) stats.missed++;
                        if(status === 'pending') stats.pending++;
                        else if(status === 'in_progress') stats.in_progress++;
                        else if(status === 'completed') stats.completed++;
                    });

                    renderList(js.tasks);
                    updateAnalytics(stats);
                }catch(e){
                    console.error('Failed to fetch tasks', e);
                    listEl.innerHTML = '<div class="py-2 text-sm text-red-500">Failed to load tasks</div>';
                }
            }

            // initialize
            initChart();
            fetchTasks();
            const POLL_MS = 1000; // poll every 1 second for realtime
            let pollId = setInterval(fetchTasks, POLL_MS);
            // Update the 'last updated' clock every second
            let clockInterval = setInterval(()=>{ if(lastUpdatedEl) lastUpdatedEl.textContent = new Date().toLocaleTimeString(); }, 1000);
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    clearInterval(pollId); pollId = null;
                    clearInterval(clockInterval); clockInterval = null;
                } else {
                    if (!pollId) { pollId = setInterval(fetchTasks, POLL_MS); fetchTasks(); }
                    if (!clockInterval) { clockInterval = setInterval(()=>{ if(lastUpdatedEl) lastUpdatedEl.textContent = new Date().toLocaleTimeString(); }, 1000); }
                }
            });
        })();
        </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
           // Declare variables once at the top
           const _currentUserEmail = <?php echo json_encode($userEmail); ?>;
           const _isJoOrOjt = <?php echo json_encode($isJoOrOjt); ?>;
           
           // Leave application status check removed - handled in apply_leave.php page itself
           
           // Notification logic
           let notifications = [];
           const bell = document.getElementById('notification-bell');
           const badge = document.getElementById('notification-badge');
           const dropdown = document.getElementById('notification-dropdown');
           const list = document.getElementById('notification-list');
           // Fetch notifications for employee and render as scrollable cards
           function renderNotifications(notifs) {
               const nl = document.getElementById('notification-list');
               nl.innerHTML = '';
               if (!notifs || notifs.length === 0) {
                   nl.innerHTML = '<div class="text-gray-500 p-2">No notifications.</div>';
                   badge.style.display = 'none';
                   return;
               }

               // show badge if there's any unread (simple heuristic)
               const hasUnread = notifs.some(n => !n.read);
               badge.style.display = hasUnread ? 'block' : 'none';

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
                   .then(response => response.json())
                   .then(data => {
                       notifications = (data.success && Array.isArray(data.data)) ? data.data : [];
                       renderNotifications(notifications);
                   })
                   .catch(() => {
                       document.getElementById('notification-list').innerHTML = '<div class="text-gray-500 p-2">Failed to load notifications.</div>';
                   });
           }

           loadNotifications();
                     // --- Real-time Leave Credits widget for current user ---
                     const leaveGridHost = document.getElementById('leave-credits-grid');
                     async function fetchLeaveCreditsForCurrentUser(){
                             if (!leaveGridHost) return;
                             if (_isJoOrOjt) {
                                     // JO/OJT employees don't have leave credits
                                     return;
                             }
                             if (!_currentUserEmail) {
                                     leaveGridHost.innerHTML = '<div class="text-gray-500 p-4">Not available (not signed in)</div>';
                                     return;
                             }
                             try{
                                     const r = await fetch('../api/employee_leave_credits.php?email=' + encodeURIComponent(_currentUserEmail));
                                     if (!r.ok) throw new Error('network');
                                     const js = await r.json();
                                     if (!js || !js.success || !Array.isArray(js.data)) {
                                             leaveGridHost.innerHTML = '<div class="text-center text-red-500 p-4">Failed to load leave credits.</div>';
                                             return;
                                     }
                                     const items = js.data;
                                     // Clear and render
                                     leaveGridHost.innerHTML = '';
                                     items.forEach(it => {
                                               const div = document.createElement('div');
                                               div.className = 'h-full min-h-[220px] box-border overflow-hidden flex flex-col items-start p-5 md:p-6 bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow space-y-4';
                                             const rawUsed = Number(it.used) || 0;
                                             const total = Math.max(0, Number(it.total) || 0);
                                             const used = Math.min(Math.max(0, rawUsed), total);
                                             const avail = Math.max(0, total - used);
                                             const usedPct = total > 0 ? Math.min(100, Math.round((used / total) * 100)) : 0;
                                             const availPct = Math.max(0, 100 - usedPct);
                                             const nameLen = (it.type || '').length;
                                             let titleSize = 'text-[15px]';
                                             if (nameLen > 24) titleSize = 'text-[14px]';
                                             if (nameLen > 30) titleSize = 'text-sm';
                                             if (nameLen > 36) titleSize = 'text-[13px]';
                                             if (nameLen > 44) titleSize = 'text-xs';
                                             div.innerHTML = `
                                                 <div class="flex items-start justify-between w-full">
                                                     <span class="${titleSize} font-semibold text-gray-900 break-words leading-snug pr-4 min-w-0">${(it.type||'')}</span>
                                                     <span class="text-[11px] px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 border border-gray-200 whitespace-nowrap shrink-0">${total} ${it.unit||'days'}</span>
                                                 </div>
                                                 <div class="w-full mt-1 flex items-center gap-4 md:gap-6 flex-wrap justify-between flex-1">
                                                     <div class="relative w-24 h-24 shrink-0 mx-auto sm:mx-0 p-1">
                                                         <canvas width="88" height="88"></canvas>
                                                         <div class="absolute inset-0 flex items-center justify-center">
                                                             <span class="text-sm font-semibold text-gray-800">${availPct}%</span>
                                                         </div>
                                                     </div>
                                                     <div class="flex-1 min-w-[240px] px-1.5">
                                                         <div class="flex items-center justify-between text-sm pr-4 md:pr-5">
                                                             <span class="inline-flex items-center gap-2 text-gray-600"><span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>Used</span>
                                                             <span class="font-semibold text-gray-800 shrink-0 mr-1 md:mr-2">${used} ${it.unit||'days'}</span>
                                                         </div>
                                                         <div class="mt-1 mx-4 progress-used"></div>
                                                         <div class="flex items-center justify-between text-sm mt-3 pr-4 md:pr-5">
                                                             <span class="inline-flex items-center gap-2 text-gray-600"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>Available</span>
                                                             <span class="font-semibold text-gray-800 shrink-0 mr-1 md:mr-2">${avail} ${it.unit||'days'}</span>
                                                         </div>
                                                         <div class="mt-1 mx-4 progress-avail"></div>
                                                     </div>
                                                 </div>
                                             `;
                                             leaveGridHost.appendChild(div);

                                             // Helper to draw progress bars
                                             const makeProgressSVG = (pct, fill, track) => {
                                                     const w = Math.max(0, Math.min(100, Number(pct) || 0));
                                                     const hasFill = w > 0;
                                                     return `
                                                         <svg viewBox="0 0 100 8" width="100%" height="8" preserveAspectRatio="none" style="display:block">
                                                             <rect x="0" y="0" width="100" height="8" rx="4" fill="${track}"></rect>
                                                             ${hasFill ? `<rect x="0" y="0" width="${w}" height="8" rx="4" fill="${fill}"></rect>` : ''}
                                                         </svg>
                                                     `;
                                             };

                                             const usedHost = div.querySelector('.progress-used');
                                             const availHost = div.querySelector('.progress-avail');
                                             if (usedHost) usedHost.innerHTML = makeProgressSVG(usedPct, '#6366F1', '#E0E7FF');
                                             if (availHost) availHost.innerHTML = makeProgressSVG(availPct, '#10B981', '#D1FAE5');

                                             // Draw donut chart for this leave type
                                             const canvas = div.querySelector('canvas');
                                             if (canvas && window.Chart) {
                                                     try{
                                                             const ctx = canvas.getContext('2d');
                                                             new Chart(ctx, {
                                                                     type: 'doughnut',
                                                                     data: {
                                                                             labels: ['Used','Available'],
                                                                             datasets: [{ data: [used, avail], backgroundColor: ['#6366F1', '#10B981'], borderWidth: 0 }]
                                                                     },
                                                                     options: { responsive: false, cutout: '68%', plugins: { legend: { display: false }, tooltip: { enabled: false } } }
                                                             });
                                                     }catch(e){ console.warn('leave chart error', e); }
                                             }
                                     });
                             }catch(e){
                                     console.error('Failed to fetch leave credits', e);
                                     if (leaveGridHost) leaveGridHost.innerHTML = '<div class="text-center text-red-500 p-4">Failed to load leave credits.</div>';
                             }
                     }
                                 // initial fetch + polling for updates (not continuous spinning)
                                 fetchLeaveCreditsForCurrentUser();
                                 const LEAVE_POLL_MS = 5000; // poll every 5s by default to reduce load
                                 let leavePollId = setInterval(fetchLeaveCreditsForCurrentUser, LEAVE_POLL_MS);
                                 let lastSummary = null; // used to avoid unnecessary re-renders when nothing changed
                                 let failureCount = 0;
                                 // Wrap the original function to check for summary changes and apply backoff on failures
                                 const _origFetch = fetchLeaveCreditsForCurrentUser;
                                 async function _polledFetch() {
                                         try {
                                                 const r = await fetch('../api/employee_leave_credits.php?email=' + encodeURIComponent(_currentUserEmail));
                                                 if (!r.ok) throw new Error('network');
                                                 const js = await r.json();
                                                 failureCount = 0; // reset failures on success
                                                 const summary = js && js.summary ? js.summary : null;
                                                 // If summary unchanged, skip expensive DOM/chart updates
                                                 if (lastSummary && JSON.stringify(lastSummary) === JSON.stringify(summary)) {
                                                         // nothing changed; no-op
                                                         return;
                                                 }
                                                 lastSummary = summary;
                                                 // otherwise, delegate to original renderer by reusing parsed data
                                                 // We'll recreate the DOM using the fetched items
                                                 if (!js || !js.success || !Array.isArray(js.data)) {
                                                         if (leaveGridHost) leaveGridHost.innerHTML = '<div class="text-center text-red-500 p-4">Failed to load leave credits.</div>';
                                                         return;
                                                 }
                                                 const items = js.data;
                                                 // Clear and render (reuse same rendering logic)
                                                 leaveGridHost.innerHTML = '';
                                                 items.forEach(it => {
                                                         const div = document.createElement('div');
                                                         div.className = 'h-full box-border overflow-hidden flex flex-col items-start p-5 md:p-6 bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow space-y-4';
                                                         const rawUsed = Number(it.used) || 0;
                                                         const total = Math.max(0, Number(it.total) || 0);
                                                         const used = Math.min(Math.max(0, rawUsed), total);
                                                         const avail = Math.max(0, total - used);
                                                         const usedPct = total > 0 ? Math.min(100, Math.round((used / total) * 100)) : 0;
                                                         const availPct = Math.max(0, 100 - usedPct);
                                                         const nameLen = (it.type || '').length;
                                                         let titleSize = 'text-[15px]';
                                                         if (nameLen > 24) titleSize = 'text-[14px]';
                                                         if (nameLen > 30) titleSize = 'text-sm';
                                                         if (nameLen > 36) titleSize = 'text-[13px]';
                                                         if (nameLen > 44) titleSize = 'text-xs';
                                                         div.innerHTML = `
                                                             <div class="flex items-start justify-between w-full">
                                                                 <span class="${titleSize} font-semibold text-gray-900 break-words leading-snug pr-4 min-w-0">${(it.type||'')}</span>
                                                                 <span class="text-[11px] px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 border border-gray-200 whitespace-nowrap shrink-0">${total} ${it.unit||'days'}</span>
                                                             </div>
                                                             <div class="w-full mt-1 flex items-center gap-4 md:gap-6 flex-wrap justify-between flex-1">
                                                                 <div class="relative w-24 h-24 shrink-0 mx-auto sm:mx-0 p-1">
                                                                     <canvas width="88" height="88"></canvas>
                                                                     <div class="absolute inset-0 flex items-center justify-center">
                                                                         <span class="text-sm font-semibold text-gray-800">${availPct}%</span>
                                                                     </div>
                                                                 </div>
                                                                 <div class="flex-1 min-w-[240px] px-1.5">
                                                                     <div class="flex items-center justify-between text-sm pr-4 md:pr-5">
                                                                         <span class="inline-flex items-center gap-2 text-gray-600"><span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>Used</span>
                                                                         <span class="font-semibold text-gray-800 shrink-0 mr-1 md:mr-2">${used} ${it.unit||'days'}</span>
                                                                     </div>
                                                                     <div class="mt-1 mx-4 progress-used"></div>
                                                                     <div class="flex items-center justify-between text-sm mt-3 pr-4 md:pr-5">
                                                                         <span class="inline-flex items-center gap-2 text-gray-600"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>Available</span>
                                                                         <span class="font-semibold text-gray-800 shrink-0 mr-1 md:mr-2">${avail} ${it.unit||'days'}</span>
                                                                     </div>
                                                                     <div class="mt-1 mx-4 progress-avail"></div>
                                                                 </div>
                                                             </div>
                                                         `;
                                                         leaveGridHost.appendChild(div);

                                                         // Helper to draw progress bars
                                                         const makeProgressSVG = (pct, fill, track) => {
                                                                 const w = Math.max(0, Math.min(100, Number(pct) || 0));
                                                                 const hasFill = w > 0;
                                                                 return `
                                                                     <svg viewBox="0 0 100 8" width="100%" height="8" preserveAspectRatio="none" style="display:block">
                                                                         <rect x="0" y="0" width="100" height="8" rx="4" fill="${track}"></rect>
                                                                         ${hasFill ? `<rect x="0" y="0" width="${w}" height="8" rx="4" fill="${fill}"></rect>` : ''}
                                                                     </svg>
                                                                 `;
                                                         };

                                                         const usedHost = div.querySelector('.progress-used');
                                                         const availHost = div.querySelector('.progress-avail');
                                                         if (usedHost) usedHost.innerHTML = makeProgressSVG(usedPct, '#6366F1', '#E0E7FF');
                                                         if (availHost) availHost.innerHTML = makeProgressSVG(availPct, '#10B981', '#D1FAE5');

                                                         // Draw donut chart for this leave type
                                                         const canvas = div.querySelector('canvas');
                                                         if (canvas && window.Chart) {
                                                                 try{
                                                                         const ctx = canvas.getContext('2d');
                                                                         new Chart(ctx, {
                                                                                 type: 'doughnut',
                                                                                 data: {
                                                                                         labels: ['Used','Available'],
                                                                                         datasets: [{ data: [used, avail], backgroundColor: ['#6366F1', '#10B981'], borderWidth: 0 }]
                                                                                 },
                                                                                 options: { responsive: false, cutout: '68%', plugins: { legend: { display: false }, tooltip: { enabled: false } } }
                                                                         });
                                                                 }catch(e){ console.warn('leave chart error', e); }
                                                         }
                                                 });
                                         } catch (err) {
                                                 failureCount++;
                                                 console.error('leave credits poll failed', err);
                                                 // on repeated failures, back off to save resources
                                                 const backoff = Math.min(30000, LEAVE_POLL_MS * Math.pow(2, failureCount));
                                                 if (leavePollId) { clearInterval(leavePollId); leavePollId = setInterval(_polledFetch, backoff); }
                                         }
                                 }

                                 // replace polling target with our wrapped fetch to respect change detection and backoff
                                 if (leavePollId) { clearInterval(leavePollId); }
                                 leavePollId = setInterval(_polledFetch, LEAVE_POLL_MS);
                                 document.addEventListener('visibilitychange', () => {
                                         if (document.hidden) { if (leavePollId) { clearInterval(leavePollId); leavePollId = null; } }
                                         else { if (!leavePollId) leavePollId = setInterval(_polledFetch, LEAVE_POLL_MS); _polledFetch(); }
                                 });
           // Toggle dropdown on bell click
           bell.addEventListener('click', () => {
               dropdown.classList.toggle('hidden');
               if (!dropdown.classList.contains('hidden')) {
                   // refresh when opening
                   loadNotifications();
               }
           });
           // Hide dropdown when clicking outside
           document.addEventListener('click', (e) => {
               if (!bell.contains(e.target) && !dropdown.contains(e.target)) {
                   dropdown.classList.add('hidden');
               }
           });
            // Profile Modal logic
               // Notification actions: Mark all read & Clear
               const markAllBtn = document.getElementById('markAllReadBtn');
               const clearBtn = document.getElementById('clearNotifBtn');
               if (markAllBtn) {
                   markAllBtn.addEventListener('click', () => {
                       fetch('notifications.php?action=mark_all_read', { method: 'POST' })
                           .then(() => loadNotifications());
                   });
               }
               if (clearBtn) {
                   clearBtn.addEventListener('click', () => {
                       if (!confirm('Clear all notifications?')) return;
                       fetch('notifications.php?action=clear_all', { method: 'POST' })
                           .then(() => loadNotifications());
                   });
               }
           
               // end notification actions
           
                // Profile Modal logic
            const profileIcon = document.getElementById('profileIcon');
            const profileModal = document.getElementById('profileModal');
            const logoutBtn = document.getElementById('logoutBtn');
            const closeProfileModal = document.getElementById('closeProfileModal');
            profileIcon.addEventListener('click', () => {
                profileModal.classList.remove('hidden');
                profileModal.classList.add('flex');
            });
            closeProfileModal.addEventListener('click', () => {
                profileModal.classList.add('hidden');
                profileModal.classList.remove('flex');
            });
            logoutBtn.addEventListener('click', () => {
                window.location.href = 'logout.php';
            });
            profileModal.addEventListener('click', (e) => {
                if (e.target === profileModal) {
                    profileModal.classList.add('hidden');
                    profileModal.classList.remove('flex');
                }
            });
            // Employee performance & attendance charts (real-time)
            const perfCanvas = document.getElementById('performanceChart');
            const attCanvas = document.getElementById('attendanceChart');

            let perfChart = null;
            let attChart = null;
            const periodRadios = document.querySelectorAll('input[name="chart-period"]');
            let currentPeriod = 'daily';

            function createPerfChart(ctx) {
                return new Chart(ctx, {
                    type: 'line',
                    data: { labels: [], datasets: [{ label: 'Performance %', data: [], borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.08)', tension: 0.4 }] },
                    options: { responsive: true, scales: { y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } } }, plugins: { legend: { display: false } } }
                });
            }

            function createAttendanceChart(ctx) {
                return new Chart(ctx, {
                    type: 'bar',
                    data: { labels: [], datasets: [
                        { label: 'Present', data: [], backgroundColor: '#10B981', stack: 's1' },
                        { label: 'Late', data: [], backgroundColor: '#F59E0B', stack: 's1' },
                        { label: 'Undertime', data: [], backgroundColor: '#FB923C', stack: 's1' },
                        { label: 'Overtime', data: [], backgroundColor: '#3B82F6', stack: 's1' },
                        { label: 'Absent', data: [], backgroundColor: '#EF4444', stack: 's1' },
                    ] },
                    options: { responsive: true, scales: { y: { beginAtZero: true } }, plugins: { legend: { position: 'bottom' } } }
                });
            }

            if (perfCanvas) perfChart = createPerfChart(perfCanvas.getContext('2d'));
            if (attCanvas) attChart = createAttendanceChart(attCanvas.getContext('2d'));

            let analyticsData = null;

            async function fetchEmployeeAnalytics(range = 'daily') {
                try {
                    // Add timestamp to prevent caching
                    const timestamp = new Date().getTime();
                    const res = await fetch(`../api/employee_attendance_analytics.php?range=${range}&_t=${timestamp}`, {
                        cache: 'no-store',
                        headers: {
                            'Cache-Control': 'no-cache',
                            'Pragma': 'no-cache'
                        }
                    });
                    const json = await res.json();
                    if (!json.success) {
                        console.error('Analytics fetch failed:', json.error);
                        return null;
                    }
                    return json.analytics || null;
                } catch (e) {
                    console.error('Failed to fetch employee analytics', e);
                    return null;
                }
            }

            function updateInterpretations(analytics) {
                if (!analytics || !analytics.interpretations) return;
                
                const interpretEl = document.getElementById('attendance-interpret-text');
                if (interpretEl && analytics.interpretations.attendance) {
                    interpretEl.textContent = analytics.interpretations.attendance;
                }

                const miniInterpretEl = document.getElementById('mini-performance-interpret-text');
                if (miniInterpretEl && analytics.summary) {
                    const summary = analytics.summary;
                    let miniText = '';
                    if (summary.performance_label === 'Excellent') {
                        miniText = '🌟 Outstanding! Keep up the great work!';
                    } else if (summary.performance_label === 'Good') {
                        miniText = '👍 Solid performance with room to excel';
                    } else if (summary.performance_label === 'Moderate') {
                        miniText = '📈 Improvement needed for better results';
                    } else {
                        miniText = '⚠️ Immediate attention required';
                    }
                    miniInterpretEl.textContent = miniText;
                }
            }

            function updateDSS(analytics) {
                if (!analytics || !analytics.decision_support) return;
                
                const dssSection = document.getElementById('dss-section');
                const dssContainer = document.getElementById('dss-container');
                
                if (!dssSection || !dssContainer) return;
                
                const alerts = analytics.decision_support;
                
                if (alerts.length === 0) {
                    dssSection.style.display = 'none';
                    return;
                }
                
                dssSection.style.display = 'block';
                dssContainer.innerHTML = '';
                
                const iconMap = {
                    error: 'fa-exclamation-circle',
                    warning: 'fa-exclamation-triangle',
                    info: 'fa-info-circle',
                    success: 'fa-check-circle'
                };
                
                const colorMap = {
                    error: 'bg-red-50 border-red-500 text-red-800',
                    warning: 'bg-amber-50 border-amber-500 text-amber-800',
                    info: 'bg-blue-50 border-blue-500 text-blue-800',
                    success: 'bg-green-50 border-green-500 text-green-800'
                };
                
                const priorityBadgeMap = {
                    high: 'bg-red-100 text-red-800',
                    medium: 'bg-yellow-100 text-yellow-800',
                    low: 'bg-gray-100 text-gray-800'
                };
                
                alerts.forEach(alert => {
                    const card = document.createElement('div');
                    card.className = `p-4 rounded-lg border-l-4 ${colorMap[alert.type] || colorMap.info}`;
                    card.innerHTML = `
                        <div class="flex items-start gap-3">
                            <i class="fas ${iconMap[alert.type] || iconMap.info} text-lg mt-1"></i>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <h5 class="font-semibold text-sm">${alert.title}</h5>
                                    <span class="text-xs px-2 py-0.5 rounded-full ${priorityBadgeMap[alert.priority] || priorityBadgeMap.low}">${alert.priority.toUpperCase()}</span>
                                </div>
                                <p class="text-sm mb-2">${alert.message}</p>
                                <p class="text-xs italic">💡 ${alert.recommendation}</p>
                            </div>
                        </div>
                    `;
                    dssContainer.appendChild(card);
                });
            }

            async function updateEmployeeCharts(range = 'daily') {
                analyticsData = await fetchEmployeeAnalytics(range);
                if (!analyticsData) {
                    console.error('No analytics data received');
                    return;
                }
                
                const { trend, summary, interpretations, decision_support } = analyticsData;
                console.log('Analytics Summary:', summary); // Debug: Check attendance_rate value
                
                const labels = trend.map(t => t.label);
                const present = trend.map(t => t.present);
                const late = trend.map(t => t.late);
                const undertime = trend.map(t => t.undertime);
                const overtime = trend.map(t => t.overtime);
                const absent = trend.map(t => t.absent || 0);

                // Use attendance_rate for display (actual attendance percentage)
                const attendancePct = summary.attendance_rate || 0;
                const overallPct = summary.overall_score || 0;

                if (attChart) {
                    attChart.data.labels = labels;
                    attChart.data.datasets[0].data = present;
                    attChart.data.datasets[1].data = late;
                    attChart.data.datasets[2].data = undertime;
                    attChart.data.datasets[3].data = overtime;
                    // if the chart includes the absent series (added), populate it
                    if (attChart.data.datasets.length > 4) {
                        attChart.data.datasets[4].data = absent;
                    }
                    attChart.update();
                }

                if (perfChart) {
                    perfChart.data.labels = labels;
                    let cumulative = 0;
                    const pctSeries = trend.map((t, idx) => {
                        cumulative += (Number(t.present) + Number(t.late));
                        return Math.round((cumulative / (idx + 1)) * 100);
                    });
                    perfChart.data.datasets[0].data = pctSeries;
                    perfChart.update();
                }

                // Update interpretations and DSS
                updateInterpretations(analyticsData);
                updateDSS(analyticsData);

                // update summary and details dataset
                const summaryEl = document.getElementById('performanceSummary');
                if (summaryEl) summaryEl.textContent = `${Math.round(attendancePct)}% attendance over selected period`;
                
                const detailsPayload = { 
                    overallPct: Math.round(attendancePct), // Use attendance_rate for percentage display
                    summary: summary,
                    interpretations: interpretations,
                    present: summary.total_present,
                    late: summary.total_late,
                    undertime: summary.total_undertime,
                    overtime: summary.total_overtime,
                    absent: summary.total_absent,
                    attendance_rate: summary.attendance_rate,
                    punctuality_rate: summary.punctuality_rate
                };
                
                const detailsBtn = document.getElementById('performanceDetailsBtn');
                if (detailsBtn) detailsBtn.dataset.details = JSON.stringify(detailsPayload);

                // Update mini performance card
                const miniPctEl = document.getElementById('miniPerformancePct');
                const miniLabelEl = document.getElementById('miniPerformanceLabel');
                const miniDescEl = document.getElementById('miniPerformanceDesc');
                const miniDetailsBtn = document.getElementById('miniPerformanceDetailsBtn');
                
                if (miniPctEl) miniPctEl.textContent = Math.round(attendancePct) + '%';
                
                const label = summary.performance_label || 'Poor';
                if (miniLabelEl) miniLabelEl.textContent = label;
                if (miniDescEl) miniDescEl.textContent = `${summary.period_label} (${summary.total_working_days} working days)`;
                if (miniDetailsBtn) miniDetailsBtn.dataset.details = JSON.stringify(detailsPayload);
                
                // color badge based on label
                const miniBadge = document.getElementById('miniPerfBadge');
                if (miniBadge) {
                    miniBadge.className = 'w-24 h-24 rounded-full flex items-center justify-center font-bold text-lg';
                    if (label === 'Excellent') { miniBadge.classList.add('bg-green-100','text-green-600'); }
                    else if (label === 'Good') { miniBadge.classList.add('bg-blue-100','text-blue-700'); }
                    else if (label === 'Moderate') { miniBadge.classList.add('bg-yellow-100','text-yellow-600'); }
                    else { miniBadge.classList.add('bg-red-100','text-red-600'); }
                }
            }

            // Function to update performance modal in real-time (if open)
            function updatePerformanceModalIfOpen() {
                const modal = document.getElementById('perfInfoModal');
                if (modal && !modal.classList.contains('hidden')) {
                    // Modal is open, refresh its content with latest data
                    const perfDetailsBtn = document.getElementById('performanceDetailsBtn');
                    if (perfDetailsBtn && perfDetailsBtn.dataset.details) {
                        const d = JSON.parse(perfDetailsBtn.dataset.details);
                        showPerformanceInfo(d);
                    }
                }
            }

            // wire radio buttons
            periodRadios.forEach(r => r.addEventListener('change', (e) => {
                if (e.target.checked) {
                    currentPeriod = e.target.value;
                    updateEmployeeCharts(currentPeriod);
                }
            }));

            // initial load
            updateEmployeeCharts(currentPeriod);
            // auto-refresh every 5s - also update modal if it's open
            setInterval(() => {
                updateEmployeeCharts(currentPeriod);
                updatePerformanceModalIfOpen();
            }, 5000);

            // Details buttons
            const perfDetailsBtn = document.getElementById('performanceDetailsBtn');
            if (perfDetailsBtn) {
                perfDetailsBtn.addEventListener('click', () => {
                    const dstr = perfDetailsBtn.dataset.details;
                    if (!dstr) return alert('No details available yet.');
                    const d = JSON.parse(dstr);
                    showPerformanceInfo(d);
                });
            }

            const miniPerfBtn = document.getElementById('miniPerformanceDetailsBtn');
            if (miniPerfBtn) {
                miniPerfBtn.addEventListener('click', () => {
                    const dstr = miniPerfBtn.dataset.details;
                    if (!dstr) return alert('No details available yet.');
                    const d = JSON.parse(dstr);
                    showPerformanceInfo(d);
                });
            }

            function showPerformanceInfo(d) {
                // Populate modal fields
                const modal = document.getElementById('perfInfoModal');
                const percentEl = document.getElementById('perfModalPercent');
                const summaryEl = document.getElementById('perfModalSummary');
                const descEl = document.getElementById('perfModalDesc');
                const presentEl = document.getElementById('perfPresent');
                const lateEl = document.getElementById('perfLate');
                const undertimeEl = document.getElementById('perfUndertime');
                const overtimeEl = document.getElementById('perfOvertime');
                const absentEl = document.getElementById('perfAbsent');
                const recEl = document.getElementById('perfModalRecommendation');

                const pct = d.overallPct || 0;
                percentEl.textContent = pct + '%';
                presentEl.textContent = d.present || 0;
                lateEl.textContent = d.late || 0;
                undertimeEl.textContent = d.undertime || 0;
                overtimeEl.textContent = d.overtime || 0;
                if (absentEl) absentEl.textContent = d.absent || (d.summary && d.summary.total_absent) || 0;

                // Use summary data if available
                const summary = d.summary;
                const interpretations = d.interpretations;
                
                let label = 'Poor';
                let desc = 'Needs improvement';
                
                if (summary) {
                    label = summary.performance_label || label;
                    
                    // Smart punctuality display based on attendance rate
                    const attendanceRate = summary.attendance_rate || 0;
                    const punctualityRate = summary.punctuality_rate || 0;
                    let punctualityDisplay = '';
                    
                    if (attendanceRate < 50) {
                        // Very low attendance - don't show punctuality percentage
                        punctualityDisplay = 'N/A (Low Attendance)';
                    } else if (attendanceRate < 70) {
                        // Low attendance - show with context
                        punctualityDisplay = `${punctualityRate}% (⚠️ Low Attendance)`;
                    } else {
                        // Normal attendance - show punctuality normally
                        punctualityDisplay = `${punctualityRate}%`;
                    }
                    
                    desc = `Attendance: ${summary.attendance_rate}% | Punctuality: ${punctualityDisplay}`;
                } else {
                    if (pct >= 85) { label = 'Excellent'; desc = 'Score better than previous period'; }
                    else if (pct >= 70) { label = 'Good'; desc = 'Satisfactory performance'; }
                    else if (pct >= 50) { label = 'Moderate'; desc = 'Average performance'; }
                }

                summaryEl.textContent = `${label} (${pct}%)`;
                descEl.textContent = desc;

                // Use interpretation as recommendation
                let recommendation = '';
                if (interpretations) {
                    recommendation = '<div class="space-y-2">';
                    if (interpretations.attendance) {
                        recommendation += `<div><strong>📊 Attendance:</strong> ${interpretations.attendance}</div>`;
                    }
                    if (interpretations.punctuality) {
                        recommendation += `<div><strong>⏰ Punctuality:</strong> ${interpretations.punctuality}</div>`;
                    }
                    if (interpretations.work_hours) {
                        recommendation += `<div><strong>🕒 Work Hours:</strong> ${interpretations.work_hours}</div>`;
                    }
                    recommendation += '</div>';
                } else {
                    if (pct < 60) recommendation = '⚠️ Low attendance. Follow up with your supervisor and HR. Review reasons for absence and consider targeted reminders.';
                    else if (pct < 85) recommendation = '📈 Address tardiness and undertime to improve overall presence.';
                    else recommendation = '✅ Maintain current practices and optionally acknowledge high performance.';
                }
                recEl.innerHTML = recommendation;

                // Color the percent badge based on label
                percentEl.className = 'w-20 h-20 rounded-full flex items-center justify-center font-bold text-lg';
                if (label === 'Excellent') { percentEl.classList.add('bg-green-100','text-green-600'); }
                else if (label === 'Good') { percentEl.classList.add('bg-blue-100','text-blue-700'); }
                else if (label === 'Moderate') { percentEl.classList.add('bg-yellow-100','text-yellow-600'); }
                else { percentEl.classList.add('bg-red-100','text-red-600'); }

                // Show modal
                modal.classList.remove('hidden');
                modal.classList.add('flex');

                // Wire close
                const closeBtn = document.getElementById('perfModalClose');
                closeBtn.onclick = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); };
                modal.onclick = (e) => { if (e.target === modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); } };
            }

            // Edit Profile Modal functionality
            const editProfileModal = document.getElementById('editProfileModal');
            const editProfileBtn = document.getElementById('editProfileBtn');
            const closeModalBtn = document.getElementById('closeModalBtn');
            const cancelBtn = document.getElementById('cancelBtn');
            const editProfileForm = document.getElementById('editProfileForm');
            const photoInput = document.getElementById('photoInput');
            const profilePhotoPreview = document.getElementById('profilePhotoPreview');
            const profilePicture = document.getElementById('profilePicture');
            let newPhotoDataUrl = null;

            editProfileBtn.addEventListener('click', () => {
                profilePhotoPreview.src = profilePicture.src;
                newPhotoDataUrl = null;
                editProfileModal.classList.remove('hidden');
                editProfileModal.classList.add('flex');
            });

            function hideModal() {
                editProfileModal.classList.add('hidden');
                editProfileModal.classList.remove('flex');
            }

            closeModalBtn.addEventListener('click', hideModal);
            cancelBtn.addEventListener('click', hideModal);
            editProfileModal.addEventListener('click', (e) => {
                if (e.target === editProfileModal) {
                    hideModal();
                }
            });

            photoInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        profilePhotoPreview.src = e.target.result;
                        newPhotoDataUrl = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });

            editProfileForm.addEventListener('submit', (e) => {
                e.preventDefault();
                if (newPhotoDataUrl) {
                    // Save to DB via API
                    fetch('../api/employee_info.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ user_id: <?php echo json_encode($user_id); ?>, profile_picture: newPhotoDataUrl })
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            profilePicture.src = newPhotoDataUrl;
                            document.getElementById('profileIcon').src = newPhotoDataUrl;
                            document.getElementById('profileModalPhoto').src = newPhotoDataUrl;
                            document.getElementById('profilePhotoPreview').src = newPhotoDataUrl;
                        }
                        hideModal();
                    });
                } else {
                    hideModal();
                }
            });
            // Fetch and display events from database
            fetch('../api/get_events.php')
                .then(response => response.json())
                .then(data => {
                    data = (data || []).filter(e => !(Number(e.is_archived||0)===1));
                    const eventsList = document.getElementById('events-list');
                    eventsList.innerHTML = '';
                    if (data && data.length > 0) {
                        data.forEach(event => {
                            const eventDate = new Date(event.date + ' ' + (event.time || '00:00'));
                            const day = eventDate.getDate();
                            const month = eventDate.toLocaleDateString('en-US', { month: 'short' }).toUpperCase();
                            const timeStr = event.time ? eventDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : '';
                            
                            const eventDiv = document.createElement('div');
                            eventDiv.className = 'event-item';
                            eventDiv.innerHTML = `
                                <div class="event-date">
                                    <span class="day">${day}</span>
                                    <span class="month">${month}</span>
                                </div>
                                <div class="event-details">
                                    <div class="event-title">${event.title}</div>
                                    ${event.description ? `<div class="event-description">${event.description}</div>` : ''}
                                    ${timeStr ? `<div class="event-time"><i class="fas fa-clock"></i>${timeStr}</div>` : ''}
                                </div>
                            `;
                            eventsList.appendChild(eventDiv);
                        });
                    } else {
                        eventsList.innerHTML = '<div style="text-align: center; padding: 3rem; color: #9ca3af;"><i class="fas fa-calendar-times" style="font-size: 2.5rem; margin-bottom: 1rem; display: block;"></i><div style="font-size: 0.875rem;">No announcements found.</div></div>';
                    }
                })
                .catch(err => {
                    document.getElementById('events-list').innerHTML = '<div style="text-align: center; padding: 3rem; color: #ef4444;"><i class="fas fa-exclamation-circle" style="font-size: 2.5rem; margin-bottom: 1rem; display: block;"></i><div style="font-size: 0.875rem;">Failed to load announcements.</div></div>';
                });
    });
    </script>
</body>
</html>