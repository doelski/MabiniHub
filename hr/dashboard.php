<?php
// dashboard.php
require_once __DIR__ . '/../auth_guard.php';
require_role('hr');
// fetch basic user info for attendance quick-link
require_once __DIR__ . '/../db.php';
$hr_user_id = $_SESSION['user_id'];
$hr_employee_id = '';
$hr_fullname = '';

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
        ob_start();
        @include_once __DIR__ . '/../attendance/mark_absent.php';
        @ob_end_clean();
    }
}
// Auto-mark forgotten at 21:00 if not already run today
if ($nowH >= 21) {
    $lastForgotFile = __DIR__ . '/../attendance/last_forgotten_run.txt';
    $lastF = is_readable($lastForgotFile) ? trim(file_get_contents($lastForgotFile)) : '';
    if ($lastF !== date('Y-m-d')) {
        ob_start();
        @include_once __DIR__ . '/../attendance/auto_timeout.php';
        // Record last run date
        @file_put_contents($lastForgotFile, date('Y-m-d'));
        @ob_end_clean();
    }
}
try {
    $stmt = $pdo->prepare('SELECT employee_id, firstname, lastname, mi FROM users WHERE id = ?');
    $stmt->execute([$hr_user_id]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($u) {
        $hr_employee_id = $u['employee_id'] ?? '';
        $hr_fullname = format_dept_head_name($u['firstname'] ?? '', $u['mi'] ?? '', $u['lastname'] ?? '');
    }
} catch (Exception $e) {
    // non-blocking
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bayan ng Mabini | Employee System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* All colors are shades of blue or neutral tones to match the request. */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f4f8; /* A light blue-gray */
            margin: 0;
            padding: 0;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px; /* Fixed width for desktop */
            background-color: #ffffff;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 1.5rem 1rem;
            border-right: 4px solid #3b82f6; /* Blue border */
            flex-shrink: 0; /* Prevents sidebar from shrinking */
            position: fixed; /* Fix the sidebar */
            top: 60px; /* Adjust based on header height */
            left: 0;
            bottom: 0;
            overflow-y: auto; /* Enable scrolling for sidebar content if needed */
        }

        .logo-container {
            display: flex;
            align-items: center;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #d1d5db;
        }

        .logo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 2px solid #55a2ea;
            padding: 3px;
        }

        .logo-text {
            font-size: 1rem;
            font-weight: 600;
            margin-left: 0.75rem;
            color: #1e3a8a; /* Dark blue */
            line-height: 1.25;
        }

        .nav-menu ul {
            list-style: none;
            padding: 0;
            margin: 1rem 0;
        }

        .nav-item a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            color: #4b5563;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            border-radius: 0.5rem;
            transition: background-color 0.2s, color 0.2s, transform 0.2s;
        }

        .nav-item a:hover,
        .nav-item.active a {
            background-color: #dbeafe; /* Light blue */
            color: #1d4ed8;
            font-weight: 600;
            transform: translateY(-2px);
        }

        .nav-item a i {
            width: 20px;
            text-align: center;
            margin-right: 1rem;
        }

        .sign-out {
            margin-top: auto;
        }

        .sign-out a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #dc2626;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            border-radius: 0.5rem;
            transition: background-color 0.2s, transform 0.2s;
        }

        .sign-out a:hover {
            background-color: #fee2e2;
            transform: translateY(-2px);
        }

        .main-content {
            flex-grow: 1;
            padding: 2.5rem;
            margin-left: 280px; /* Add margin to prevent content from going under the sidebar */
            margin-top: 60px; /* Add margin to prevent content from going under the header */
            overflow-y: auto;
        }

        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
        }

        .header-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); /* Adjusted for responsiveness */
            gap: 1.5rem; /* Reduced gap for smaller screens */
            margin-bottom: 2rem;
        }

        .header-box {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-top: 4px solid;
            width: 100%; /* Ensures it fills its grid cell */
            position: relative; /* For absolute positioning of indicator */
        }

        .header-box:nth-child(1) { border-color: #3b82f6; }
        .header-box:nth-child(2) { border-color: #93c5fd; }
        .header-box:nth-child(3) { border-color: #22d3ee; }
        .header-box:nth-child(4) { border-color: #60a5fa; }
        
        .live-indicator-dot {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 10px;
            height: 10px;
            background-color: #10b981;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);
            animation: pulse 2s infinite;
        }

        .header-box .category {
            font-size: 0.9rem;
            color: #4b5563;
            font-weight: 500;
            display: block;
            margin-bottom: 0.5rem;
        }

        .header-box .count {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1f2937;
        }

        .header-box .active-count {
            font-size: 0.8rem;
            color: #9ca3af;
            font-weight: 500;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.1); }
        }

        .projects-events-container {
            display: flex;
            flex-wrap: wrap; /* Allows wrapping on smaller screens */
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .active-projects-box, .events-box {
            background-color: #fff;
            padding: 0;
            border-radius: 1rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            flex: 1 1 45%; /* Flex-basis allows them to grow but wrap */
            min-width: 300px; /* Ensures they don't get too narrow */
            max-height: 600px;
            display: flex;
            flex-direction: column;
            border: 1px solid #e5e7eb;
            transition: box-shadow 0.3s ease;
        }

        .active-projects-box:hover, .events-box:hover {
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
        }

        .active-projects-box h3, .events-box h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0;
            padding: 1.5rem 2rem;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-bottom: 2px solid #3b82f6;
            border-radius: 1rem 1rem 0 0;
        }

        .projects-header-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            margin: 0;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-bottom: 2px solid #3b82f6;
            border-radius: 1rem 1rem 0 0;
        }

        .projects-header-wrapper h3 {
            margin: 0;
            padding: 0;
            background: none;
            border: none;
            border-radius: 0;
        }

        .tasks-scrollable-content,
        .events-scrollable-content {
            overflow-y: auto;
            flex: 1;
            padding: 1.5rem 2rem;
        }

        .tasks-scrollable-content::-webkit-scrollbar,
        .events-scrollable-content::-webkit-scrollbar {
            width: 8px;
        }

        .tasks-scrollable-content::-webkit-scrollbar-track,
        .events-scrollable-content::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        .tasks-scrollable-content::-webkit-scrollbar-thumb,
        .events-scrollable-content::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .tasks-scrollable-content::-webkit-scrollbar-thumb:hover,
        .events-scrollable-content::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        th, td {
            text-align: left;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
        }

        .progress-bar {
            background-color: #e5e7eb;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            width: 100px;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-badge.inprogress { background-color: #dbeafe; color: #1e40af; }
        .status-badge.pending { background-color: #fef2f2; color: #ef4444; }
        .status-badge.completed { background-color: #f0fdf4; color: #22c55e; }

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

        .all-projects-chart {
            background-color: #fff;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center; /* Center content when stacked */
            gap: 2rem;
            flex-wrap: wrap; /* Allows chart and legend to wrap */
        }

        .chart-container {
            width: 150px;
            height: 150px;
            position: relative;
        }

        .chart-legend h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.75rem;
        }

        .chart-legend ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .chart-legend li {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            color: #4b5563;
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 3px;
            margin-right: 0.75rem;
        }

        .legend-color.complete { background-color: #2563eb; }
        .legend-color.pending { background-color: #93c5fd; }
        .legend-color.not-start { background-color: #60a5fa; }

        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background-color: #ffffff;
            border-bottom: 1px solid #a7c4ff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            position: fixed; 
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .header-left {
            display: flex;
            align-items: center;
        }

        .header-logo .logo-image {
            width: 40px;
            height: 40px;
            margin-right: 10px;
        }

        .header-text {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1e3a8a;
        }

        .header-profile {
            display: flex;
            align-items: center;
        }
        
        .header-profile .profile-image {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
            margin-left: 10px;
        }

        .header-profile .notification-icon {
            font-size: 1.25rem;
            color: #6b7280;
            cursor: pointer;
            transition: color 0.2s;
        }

        .header-profile .notification-icon:hover {
            color: #3b82f6;
        }


        /* Employees Section Styles */
        .employee-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            overflow-x: auto;
        }

        .employee-tab-btn {
            padding: 0.75rem 1.5rem;
            font-size: 0.95rem;
            font-weight: 500;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background-color 0.2s, color 0.2s, transform 0.2s;
            border: 1px solid #d1d5db;
        }

        .employee-tab-btn.active {
            font-weight: 600;
            transform: translateY(-2px);
            color: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-color: transparent;
        }

        /* Category-specific button colors matching dashboard boxes */
        .employee-tab-btn[data-category="Permanent"].active { background-color: #3b82f6; }
        .employee-tab-btn[data-category="Casual"].active { background-color: #93c5fd; }
        .employee-tab-btn[data-category="JO"].active { background-color: #22d3ee; }
        .employee-tab-btn[data-category="OJT"].active { background-color: #60a5fa; }

        .employee-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .employee-card {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            text-align: center;
            position: relative;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }

        .employee-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.1);
        }

        .employee-card .active-status {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 12px;
            height: 12px;
            background-color: #10b981; /* Green color for active status */
            border-radius: 50%;
            border: 2px solid #fff;
        }

        .employee-card .profile-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e5e7eb;
            margin-bottom: 1rem;
        }

        .employee-card .employee-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .employee-card .employee-category {
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        /* Category-specific card colors */
        .employee-card[data-category="Permanent"] .employee-category { color: #3b82f6; }
        .employee-card[data-category="Casual"] .employee-category { color: #93c5fd; }
        .employee-card[data-category="JO"] .employee-category { color: #22d3ee; }
        .employee-card[data-category="OJT"] .employee-category { color: #60a5fa; }


        /* Modal Popup Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
            z-index: 1001;
        }

        .modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-content {
            background-color: #fff;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 500px;
            position: relative;
            transform: scale(0.9);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            max-height: 90vh; /* Limit height to prevent full screen takeover */
            overflow-y: auto; /* Enable scrolling for modal content */
        }

        .modal-overlay.show .modal-content {
            transform: scale(1);
        }

        .modal-close-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #9ca3af;
            cursor: pointer;
            transition: color 0.2s;
        }

        .modal-close-btn:hover {
            color: #ef4444;
        }
        
        .modal-profile-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .modal-profile-header img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #3b82f6;
            margin-bottom: 1rem;
        }
        
        .modal-profile-header h4 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
        }
        
        .modal-profile-header .employee-details {
            font-size: 1rem;
            color: #6b7280;
            margin-top: 0.5rem;
        }

        .modal-leave-credits {
            margin-top: 1rem;
            text-align: left;
        }
        
        .modal-leave-credits h5 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
        }
        
        .modal-leave-credits ul {
            list-style: none;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .modal-leave-credits li {
            background-color: #f3f4f6;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.95rem;
            color: #374151;
        }
        
        .modal-leave-credits .credit-count {
            font-weight: 600;
            color: #1d4ed8;
        }
        
        @media (max-width: 1024px) {
            .header-container {
                grid-template-columns: 1fr 1fr;
            }

            .projects-events-container {
                flex-direction: column;
            }

            .all-projects-chart {
                flex-direction: column;
                text-align: center;
            }

            .chart-legend {
                margin-top: 2rem;
            }
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                height: auto;
            }

            .sidebar {
                width: 100%;
                height: auto;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                padding: 1rem;
                position: relative;
                border-right: none;
                border-bottom: 4px solid #3b82f6;
            }
            
            .nav-menu {
                display: none;
            }
            
            .main-content {
                padding: 1.5rem;
                margin-left: 0;
                margin-top: 60px;
            }
            
            .header-container {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .employee-tabs {
                flex-wrap: nowrap;
            }
        }
    </style>
</head>
<body>

    <header class="top-header">
        <div class="header-left">
            <div class="header-logo">
                <img src="../assets/logo.png" alt="Mabini Logo" class="logo-image">
            </div>
            <span class="header-text">HR</span>
        </div>
        <div class="header-profile">
            <i id="open-notif-btn" class="fas fa-bell notification-icon" title="Compose notification"></i>
            <?php
            // prefer employee_id for search; fallback to name
            $hr_att_search = '';
            if (!empty($hr_employee_id)) {
                $hr_att_search = urlencode($hr_employee_id);
            } elseif (!empty($hr_fullname)) {
                $hr_att_search = urlencode($hr_fullname);
            }
            ?>
            <a href="../employee/attendance.php" title="My Attendance" class="attendance-btn" style="margin-left:0.75rem; display:inline-flex; align-items:center; gap:0.5rem; padding:0.375rem 0.75rem; background:#f59e0b; color:#fff; border-radius:0.5rem; text-decoration:none; font-weight:600;">
                <i class="fas fa-user-clock" style="font-size:0.9rem;"></i>
                <span style="font-size:0.9rem;">My Attendance</span>
            </a>
            <a href="../attendance/dashboard.php" title="View All Attendance" style="margin-left:0.5rem; display:inline-flex; align-items:center; gap:0.5rem; padding:0.375rem 0.75rem; background:#3b82f6; color:#fff; border-radius:0.5rem; text-decoration:none; font-weight:600;">
                <i class="fas fa-users" style="font-size:0.9rem;"></i>
                <span style="font-size:0.9rem;">All Attendance</span>
            </a>
            <img src="../assets/logo.png" alt="Profile" class="profile-image">
        </div>
    </header>

    <?php
    // Show attendance result modal if redirected back with attendance params (att, att_time, att_status)
    $att_msg = $_GET['att'] ?? '';
    $att_time = $_GET['att_time'] ?? '';
    $att_status = $_GET['att_status'] ?? '';
    if (!empty($att_msg)):
    ?>
    <?php
    // Prepare styling class based on attendance message
    $att_color = '#3b82f6'; // default blue
    if ($att_msg === 'timein_ok') $att_color = '#10b981';
    elseif ($att_msg === 'timeout_ok') $att_color = '#6366f1';
    elseif ($att_msg === 'already_timedout') $att_color = '#f59e0b';
    $att_title_safe = htmlspecialchars($title ?? (ucwords(str_replace('_',' ',$att_msg))), ENT_QUOTES);
    $att_time_safe = htmlspecialchars($att_time ?: '—', ENT_QUOTES);
    $att_status_safe = htmlspecialchars($att_status ?: '—', ENT_QUOTES);
    ?>

    <div id="attModal" class="fixed inset-0 flex items-center justify-center z-50" style="display:none;">
        <div class="bg-white max-w-md w-full mx-4 rounded-lg shadow-lg ring-1 ring-black ring-opacity-5">
            <div class="px-6 py-5">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <div id="attIcon" class="w-12 h-12 rounded-full flex items-center justify-center" style="background:<?php echo $att_color; ?>;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19a7 7 0 110-14 7 7 0 010 14z" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo $att_title_safe; ?></h3>
                        <p class="text-sm text-gray-600 mt-2">Time: <strong class="text-gray-800"><?php echo $att_time_safe; ?></strong></p>
                        <p class="text-sm text-gray-600 mt-1">Status: <span class="inline-block px-2 py-1 rounded-full text-xs font-semibold" style="background:<?php echo $att_color; ?>; color:#fff"><?php echo $att_status_safe; ?></span></p>
                    </div>
                </div>
                <div class="mt-4 text-right">
                    <button id="closeAttModal" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function(){
        var m = document.getElementById('attModal');
        if(!m) return;
        m.style.display = 'flex';
        var closeBtn = document.getElementById('closeAttModal');
        if(closeBtn){
            closeBtn.addEventListener('click', function(){
                m.style.display='none';
                if(window.history && window.history.replaceState){
                    try{
                        var url = new URL(window.location);
                        url.searchParams.delete('att');
                        url.searchParams.delete('att_time');
                        url.searchParams.delete('att_status');
                        window.history.replaceState({}, document.title, url.pathname + url.search);
                    }catch(e){/* ignore */}
                }
            });
        }
    })();
    </script>
    <?php endif; ?>

    <div class="container">
        <aside class="sidebar">
            <nav class="nav-menu">
                <ul>
                    <li class="nav-item active">
                        <a href="#"><i class="fas fa-th-large"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a href="employees.html"><i class="fas fa-users"></i> Employees</a>
                    </li>
                    <li class="nav-item">
                        <a href="leave_status.php"><i class="fas fa-calendar-alt"></i> Leave Status</a>
                    </li>
                    <li class="nav-item">
                        <a href="leave_request.php"><i class="fas fa-calendar-plus"></i> Leave Request</a>
                    </li>
                    <li class="nav-item">
                        <a href="manage_events.php"><i class="fa fa-calendar-times"></i> Manage Events</a>
                    </li>
                    <li class="nav-item">
                        <a href="analytics.php"><i class="fas fa-chart-line"></i> Analytics</a>
                    </li>
                </ul>
            </nav>
            <div class="sign-out">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
            </div>
        </aside>

        <main class="main-content">
            <div class="main-content-area">
                <section id="dashboard-content" class="content-section active">
                    <div class="header-container">
                        <div class="header-box">
                            <span class="live-indicator-dot"></span>
                            <span class="category">Permanent</span>
                            <div class="count" id="permanent-total">-</div>
                            <span class="active-count"><span id="permanent-active">-</span> Active</span>
                        </div>
                        <div class="header-box">
                            <span class="live-indicator-dot"></span>
                            <span class="category">Casual</span>
                            <div class="count" id="casual-total">-</div>
                            <span class="active-count"><span id="casual-active">-</span> Active</span>
                        </div>
                        <div class="header-box">
                            <span class="live-indicator-dot"></span>
                            <span class="category">JO</span>
                            <div class="count" id="jo-total">-</div>
                            <span class="active-count"><span id="jo-active">-</span> Active</span>
                        </div>
                        <div class="header-box">
                            <span class="live-indicator-dot"></span>
                            <span class="category">OJT</span>
                            <div class="count" id="ojt-total">-</div>
                            <span class="active-count"><span id="ojt-active">-</span> Active</span>
                        </div>
                    </div>
                    <div class="projects-events-container">
                        <div class="active-projects-box">
                            <div class="projects-header-wrapper">
                                <h3><i class="fas fa-chart-line mr-2"></i>Active Projects</h3>
                                <?php if (in_array(strtolower($_SESSION['role'] ?? ''), ['admin','super_admin'])): ?>
                                <div class="flex items-center gap-2" id="admin-filters">
                                    <label class="text-sm text-gray-500">View:</label>
                                    <button id="filter-backlog" class="py-1 px-3 rounded text-sm bg-blue-50 text-blue-700 font-semibold">Most backlog</button>
                                    <button id="filter-productive" class="py-1 px-3 rounded text-sm bg-white text-gray-700 border border-gray-200">Most productive</button>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="tasks-scrollable-content">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full">
                                        <thead>
                                            <tr>
                                                <th class="py-3 px-4 font-semibold text-sm text-gray-500 uppercase tracking-wider">Department</th>
                                                <th class="py-3 px-4 font-semibold text-sm text-gray-500 uppercase tracking-wider">Department Head</th>
                                                <th class="py-3 px-4 font-semibold text-sm text-gray-500 uppercase tracking-wider">Completed</th>
                                                <th class="py-3 px-4 font-semibold text-sm text-gray-500 uppercase tracking-wider">Backlog</th>
                                                <th class="py-3 px-4 font-semibold text-sm text-gray-500 uppercase tracking-wider">Total</th>
                                                <th class="py-3 px-4 font-semibold text-sm text-gray-500 uppercase tracking-wider">Progress</th>
                                                <th class="py-3 px-4 font-semibold text-sm text-gray-500 uppercase tracking-wider">Last Updated</th>
                                            </tr>
                                        </thead>
                                        <tbody id="dept-tbody">
                                            <!-- Departments will be loaded here via AJAX -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="events-box">
                            <h3><i class="fas fa-calendar-alt mr-2"></i>Events</h3>
                            <div class="events-scrollable-content">
                                <div id="events-list">
                                    <!-- Events will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Real-time Analytics Section -->
                    <div class="bg-white rounded-xl shadow-md p-6" style="margin-top: 2rem;">
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
                    <div id="dss-section" class="bg-white rounded-xl shadow-md p-6" style="display: none; margin-top: 2rem;">
                        <div class="flex items-center gap-2 mb-4">
                            <i class="fas fa-brain text-blue-600 text-xl"></i>
                            <h3 class="text-lg font-semibold text-gray-800">Smart Insights & Recommendations</h3>
                        </div>
                        <div id="dss-container" class="space-y-3">
                            <!-- DSS alerts will be populated here -->
                        </div>
                    </div>

                    <!-- Performance Details Card -->
                    <div class="bg-white rounded-xl shadow-md p-6 flex flex-col items-center justify-center" style="margin-top: 2rem;">
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
                </section>
            </div>
        </main>
    </div>

    <!-- Performance Details Modal -->
    <div id="perfInfoModal" class="fixed inset-0 hidden items-center justify-center z-50" style="background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(5px);">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-lg mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 id="perfModalTitle" class="text-lg font-semibold text-gray-800">Performance Details</h3>
                <button id="perfModalClose" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
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
                // Notification inbox handlers for HR dashboard
                (function(){
                    // create dropdown container
                                        const tpl = `
                                        <div id="notif-dropdown" style="position:fixed;top:60px;right:20px;width:380px;max-height:460px;overflow:hidden;background:#fff;border:1px solid #e6eefc;border-radius:10px;box-shadow:0 10px 40px rgba(15,23,42,0.12);display:none;z-index:1200;font-family:Inter, sans-serif">
                                            <div style="padding:12px 14px;border-bottom:1px solid #f1f8ff;display:flex;align-items:center;justify-content:space-between;background:linear-gradient(90deg,#fbfeff,#f7fbff);">
                                                <div style="display:flex;flex-direction:column">
                                                    <strong style="font-size:1rem;color:#0f172a">Notifications</strong>
                                                    <span id="notif-sub" style="font-size:0.82rem;color:#64748b;margin-top:2px">Recent activity and alerts</span>
                                                </div>
                                                <div style="display:flex;gap:8px;align-items:center">
                                                    <button id="notif-mark-all" style="background:#e6f0ff;border:1px solid #c7e0ff;color:#0b61d3;padding:6px 10px;border-radius:8px;cursor:pointer;font-size:0.85rem">Mark all</button>
                                                    <button id="notif-clear-read" style="background:#fff;border:1px solid #e5e7eb;color:#374151;padding:6px 10px;border-radius:8px;cursor:pointer;font-size:0.85rem">Clear read</button>
                                                </div>
                                            </div>
                                            <div id="notif-list" style="padding:8px;overflow:auto;height:360px;"> </div>
                                            <div id="notif-empty" style="padding:16px;text-align:center;color:#6b7280;display:none">You're all caught up — no notifications</div>
                                            <div style="padding:10px;border-top:1px solid #f1f8ff;background:#fbfdff;text-align:center;font-size:0.85rem;color:#64748b">Updated every 15s</div>
                                        </div>`;
                    document.body.insertAdjacentHTML('beforeend', tpl);

                    const bell = document.getElementById('open-notif-btn');
                    const dropdown = document.getElementById('notif-dropdown');
                    const listEl = document.getElementById('notif-list');
                    const emptyEl = document.getElementById('notif-empty');
                    const markAllBtn = document.getElementById('notif-mark-all');
                    let polling = null;

                    async function fetchNotifications(){
                        try{
                            const res = await fetch('../api/notifications_list.php?limit=50', { credentials: 'include' });
                            const data = await res.json();
                            if(!data || !data.success){ renderEmpty(); return; }
                            const notes = Array.isArray(data.notifications) ? data.notifications : [];
                            renderList(notes);
                            updateBadge((data.unread || 0));
                        }catch(e){ console.error('notif fetch', e); renderEmpty(); }
                    }

                    function renderEmpty(){ listEl.innerHTML=''; emptyEl.style.display='block'; }

                    function timeAgo(ts){ try{ const d = new Date(ts); return d.toLocaleString(); }catch(e){ return ts||''; } }

                    function renderList(notes){
                        emptyEl.style.display = notes.length ? 'none' : 'block';
                        listEl.innerHTML = notes.map(n => {
                            const unread = Number(n.is_read) ? '' : 'font-weight:700;color:#0b1220;';
                            const msg = (n.message || '').replace(/</g,'&lt;');
                            const typeBadge = n.type ? `<span style="background:#eef2ff;border:1px solid #d7ebff;color:#0b61d3;padding:3px 6px;border-radius:999px;font-size:0.72rem;margin-left:6px">${n.type}</span>` : '';
                            return `<div data-id="${n.id}" class="notif-row" style="padding:10px;border-bottom:1px solid #f3f7fb;display:flex;gap:10px;align-items:flex-start">
                                <div style="width:6px;height:36px;border-radius:4px;background:${Number(n.is_read)?'#e6eefc':'#0b61d3'};flex-shrink:0"></div>
                                <div style="flex:1;min-width:0">
                                  <div style="${unread}">${msg}${typeBadge}</div>
                                  <div style="font-size:0.78rem;color:#64748b;margin-top:6px">${timeAgo(n.created_at)}</div>
                                </div>
                                <div style="margin-left:8px;white-space:nowrap;display:flex;flex-direction:column;gap:6px">
                                  ${Number(n.is_read) ? '' : '<button class="notif-mark-read" style="background:#0b61d3;color:#fff;border:none;padding:6px 8px;border-radius:8px;cursor:pointer;font-size:0.82rem">Mark</button>'}
                                </div>
                            </div>`;
                        }).join('');
                        // attach handlers for mark buttons
                        listEl.querySelectorAll('.notif-mark-read').forEach(btn => {
                            btn.addEventListener('click', async (ev)=>{
                                const row = ev.target.closest('[data-id]');
                                const id = row?.getAttribute('data-id');
                                if(!id) return;
                                await markRead(id);
                                await fetchNotifications();
                            });
                        });
                    }

                    function updateBadge(n){
                        // small red dot/count on bell
                        let badge = bell.querySelector('.notif-badge');
                        if(!badge){ badge = document.createElement('span'); badge.className='notif-badge'; badge.style.cssText='background:#ef4444;color:#fff;padding:2px 6px;border-radius:999px;font-size:0.75rem;margin-left:6px'; bell.appendChild(badge); }
                        badge.textContent = n>0?String(n):'';
                        badge.style.display = n>0 ? '' : 'none';
                    }

                    async function markRead(id){
                        try{ await fetch('../api/notifications_mark_read.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id}), credentials: 'include' }); }catch(e){ console.error(e); }
                    }

                    async function markAll(){
                        try{ await fetch('../api/notifications_mark_read.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({}), credentials: 'include' }); await fetchNotifications(); }catch(e){ console.error(e); }
                    }

                    async function clearRead(){
                        if(!confirm('Clear all read notifications? This will permanently remove them for you.')) return;
                        try{
                            const res = await fetch('../api/notifications_clear.php', { method:'POST', credentials: 'include' });
                            const data = await res.json();
                            if(data && data.success){ await fetchNotifications(); }
                        }catch(e){ console.error('clearRead', e); }
                    }

                    bell && bell.addEventListener('click', async ()=>{
                        if(!dropdown) return;
                        if(dropdown.style.display === 'none' || !dropdown.style.display){
                            dropdown.style.display = 'block';
                            await fetchNotifications();
                            polling = setInterval(fetchNotifications, 15000);
                        } else {
                            dropdown.style.display = 'none';
                            if(polling){ clearInterval(polling); polling = null; }
                        }
                    });

                    markAllBtn && markAllBtn.addEventListener('click', async ()=>{ if(confirm('Mark all notifications as read?')){ await markAll(); } });
                    const clearBtn = document.getElementById('notif-clear-read');
                    clearBtn && clearBtn.addEventListener('click', async ()=>{ await clearRead(); });

                    // close dropdown when clicking outside
                    document.addEventListener('click', (ev)=>{ if(!ev.target.closest || (!ev.target.closest('#notif-dropdown') && !ev.target.closest('#open-notif-btn'))){ if(dropdown && dropdown.style.display==='block'){ dropdown.style.display='none'; if(polling){ clearInterval(polling); polling=null; } } } });

                    // initial fetch for badge only
                    fetchNotifications();
                })();
        document.addEventListener('DOMContentLoaded', function() {
            // Real-time employee counts with active status based on attendance
            Promise.all([
                fetch('../api/get_employees.php').then(res => res.json()),
                fetch('../api/get_active_employees.php').then(res => res.json())
            ])
            .then(([employeesData, activeData]) => {
                if (employeesData.success && activeData.success) {
                    const employees = employeesData.employees;
                    
                    // Total counts by position
                    const countPermanent = employees.filter(e => e.position === 'Permanent').length;
                    const countCasual = employees.filter(e => e.position === 'Casual').length;
                    const countJO = employees.filter(e => e.position === 'JO').length;
                    const countOJT = employees.filter(e => e.position === 'OJT').length;
                    
                    const permanentTotal = document.getElementById('permanent-total');
                    const casualTotal = document.getElementById('casual-total');
                    const joTotal = document.getElementById('jo-total');
                    const ojtTotal = document.getElementById('ojt-total');
                    
                    if (permanentTotal) permanentTotal.textContent = countPermanent;
                    if (casualTotal) casualTotal.textContent = countCasual;
                    if (joTotal) joTotal.textContent = countJO;
                    if (ojtTotal) ojtTotal.textContent = countOJT;
                    
                    // Active counts based on today's attendance (who have am_in OR pm_in today)
                    const activeCounts = activeData.active;
                    const permanentActive = document.getElementById('permanent-active');
                    const casualActive = document.getElementById('casual-active');
                    const joActive = document.getElementById('jo-active');
                    const ojtActive = document.getElementById('ojt-active');
                    
                    if (permanentActive) permanentActive.textContent = activeCounts.Permanent;
                    if (casualActive) casualActive.textContent = activeCounts.Casual;
                    if (joActive) joActive.textContent = activeCounts.JO;
                    if (ojtActive) ojtActive.textContent = activeCounts.OJT;
                }
            })
            .catch(err => {
                console.error('Error loading employee data:', err);
            });

            // Current sort mode for department table: 'backlog' | 'productive'
            window.deptSort = window.deptSort || 'backlog';

            // Load department task summary and update table in realtime
            async function loadDeptTasks() {
                try {
                    const res = await fetch('../api/tasks_by_department.php', { credentials: 'include' });
                    const data = await res.json();
                    if (!data || !data.success) return;
                    const tbody = document.getElementById('dept-tbody');
                    if (!tbody) return;
                    // Client-side sort depending on selected filter
                    let deps = Array.isArray(data.departments) ? data.departments.slice() : [];
                    if (window.deptSort === 'productive') {
                        deps.sort((a,b) => (b.completed - a.completed) || (b.total - a.total));
                    } else {
                        deps.sort((a,b) => (b.backlog - a.backlog) || (b.total - a.total));
                    }
                    tbody.innerHTML = '';
                    deps.forEach(d => {
                        // Skip departments that are invalid
                        if (!d.department || d.department === 'Unknown' || d.department.trim() === '') return;
                        
                        const pct = Number(d.progress_percent) || 0;
                        let color = '#55a2ea';
                        if (pct >= 75) color = '#22c55e';
                        else if (pct < 40) color = '#ef4444';
                        const last = d.last_updated ? formatDate(d.last_updated) : '-';
                        const head = d.department_head_name ? escapeHtml(d.department_head_name) : '-';
                        const department = escapeHtml(d.department);
                        const row = `
                            <tr>
                                <td class="py-4 px-4 whitespace-nowrap">${department}</td>
                                <td class="py-4 px-4 whitespace-nowrap">${head}</td>
                                <td class="py-4 px-4 whitespace-nowrap">${d.completed}</td>
                                <td class="py-4 px-4 whitespace-nowrap">${d.backlog}</td>
                                <td class="py-4 px-4 whitespace-nowrap">${d.total}</td>
                                <td class="py-4 px-4">
                                    <div class="progress-bar" title="${pct}%">
                                        <div class="progress-fill" style="width: ${pct}%; background-color: ${color};"></div>
                                    </div>
                                    <span style="margin-left:8px;font-weight:600">${pct}%</span>
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap">${last}</td>
                            </tr>
                        `;
                        tbody.insertAdjacentHTML('beforeend', row);
                    });
                } catch (e) {
                    console.error('Failed to load department tasks', e);
                }
            }

            // Setup admin filter buttons (if present)
            function setupDeptFilters(){
                const bBacklog = document.getElementById('filter-backlog');
                const bProd = document.getElementById('filter-productive');
                if(!bBacklog || !bProd) return;
                function setActive(mode){
                    window.deptSort = mode;
                    if(mode === 'productive'){
                        bProd.classList.add('bg-blue-600','text-white');
                        bProd.classList.remove('bg-white','text-gray-700');
                        bBacklog.classList.remove('bg-blue-600','text-white');
                        bBacklog.classList.add('bg-blue-50','text-blue-700');
                    } else {
                        bBacklog.classList.add('bg-blue-600','text-white');
                        bBacklog.classList.remove('bg-blue-50','text-blue-700');
                        bProd.classList.remove('bg-blue-600','text-white');
                        bProd.classList.add('bg-white','text-gray-700');
                    }
                    loadDeptTasks();
                }
                bBacklog.addEventListener('click', ()=> setActive('backlog'));
                bProd.addEventListener('click', ()=> setActive('productive'));
                // initialize UI
                setActive(window.deptSort || 'backlog');
            }

            function formatDate(ts) {
                try { const d = new Date(ts); return d.toLocaleString(); } catch(e) { return ts || ''; }
            }

            function escapeHtml(str){ return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

            // initialize admin filter UI (if present)
            setupDeptFilters();

            // initial load and poll every 10 seconds for near-realtime
            loadDeptTasks();
            setInterval(loadDeptTasks, 10000);

            // Chart and events (existing code)
            const chartData = {
                labels: ['Complete', 'Pending', 'Not Start'],
                datasets: [{
                    label: 'Project Status',
                    data: [3, 2, 4],
                    backgroundColor: [
                        '#2563eb', // Complete (Blue)
                        '#93c5fd', // Pending (Light Blue)
                        '#60a5fa'  // Not Start (Lighter Blue)
                    ],
                    hoverOffset: 4
                }]
            };
            const config = {
                type: 'doughnut',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            };
            const projectChart = document.getElementById('projectChart');
            if(projectChart) {
                new Chart(projectChart, config);
            }
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
                        eventsList.innerHTML = '<div style="text-align: center; padding: 2rem; color: #9ca3af;"><i class="fas fa-calendar-times" style="font-size: 2rem; margin-bottom: 0.5rem;"></i><div>No events found.</div></div>';
                    }
                })
                .catch(err => {
                    document.getElementById('events-list').innerHTML = '<div style="text-align: center; padding: 2rem; color: #ef4444;"><i class="fas fa-exclamation-circle" style="font-size: 2rem; margin-bottom: 0.5rem;"></i><div>Failed to load events.</div></div>';
                });
            
            // Real-time dashboard updates (all accounts, no filtering)
            function updateDashboardStats() {
                fetch('../api/hr_dashboard.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update category counts
                            document.getElementById('permanent-total').textContent = data.categories.Permanent.total;
                            document.getElementById('permanent-active').textContent = data.categories.Permanent.active;
                            
                            document.getElementById('casual-total').textContent = data.categories.Casual.total;
                            document.getElementById('casual-active').textContent = data.categories.Casual.active;
                            
                            document.getElementById('jo-total').textContent = data.categories.JO.total;
                            document.getElementById('jo-active').textContent = data.categories.JO.active;
                            
                            document.getElementById('ojt-total').textContent = data.categories.OJT.total;
                            document.getElementById('ojt-active').textContent = data.categories.OJT.active;
                        }
                    })
                    .catch(err => console.error('Failed to update dashboard stats:', err));
            }
            
            // Initial load
            updateDashboardStats();
            
            // Auto-refresh every 5 seconds
            setInterval(updateDashboardStats, 5000);

            // Real-time Analytics for HR (based on their personal attendance)
            (function(){
                const attCanvas = document.getElementById('attendanceChart');
                if (!attCanvas) return;

                let attChart = null;
                const periodRadios = document.querySelectorAll('input[name="chart-period"]');
                let currentPeriod = 'daily';

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

                attChart = createAttendanceChart(attCanvas.getContext('2d'));

                async function fetchEmployeeAnalytics(range = 'daily') {
                    try {
                        const timestamp = new Date().getTime();
                        const res = await fetch(`../api/employee_attendance_analytics.php?range=${range}&_t=${timestamp}`, {
                            cache: 'no-store',
                            headers: { 'Cache-Control': 'no-cache', 'Pragma': 'no-cache' }
                        });
                        const json = await res.json();
                        if (!json.success) return null;
                        return json.analytics || null;
                    } catch (e) {
                        console.error('Failed to fetch analytics', e);
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
                        if (summary.performance_label === 'Excellent') miniText = '🌟 Outstanding! Keep up the great work!';
                        else if (summary.performance_label === 'Good') miniText = '👍 Solid performance with room to excel';
                        else if (summary.performance_label === 'Moderate') miniText = '📈 Improvement needed for better results';
                        else miniText = '⚠️ Immediate attention required';
                        miniInterpretEl.textContent = miniText;
                    }
                }

                function updateDSS(analytics) {
                    if (!analytics || !analytics.decision_support) return;
                    const dssSection = document.getElementById('dss-section');
                    const dssContainer = document.getElementById('dss-container');
                    if (!dssSection || !dssContainer) return;
                    const alerts = analytics.decision_support;
                    if (alerts.length === 0) { dssSection.style.display = 'none'; return; }
                    dssSection.style.display = 'block';
                    dssContainer.innerHTML = '';
                    const iconMap = { error: 'fa-exclamation-circle', warning: 'fa-exclamation-triangle', info: 'fa-info-circle', success: 'fa-check-circle' };
                    const colorMap = { error: 'bg-red-50 border-red-500 text-red-800', warning: 'bg-amber-50 border-amber-500 text-amber-800', info: 'bg-blue-50 border-blue-500 text-blue-800', success: 'bg-green-50 border-green-500 text-green-800' };
                    const priorityBadgeMap = { high: 'bg-red-100 text-red-800', medium: 'bg-yellow-100 text-yellow-800', low: 'bg-gray-100 text-gray-800' };
                    alerts.forEach(alert => {
                        const card = document.createElement('div');
                        card.className = `p-4 rounded-lg border-l-4 ${colorMap[alert.type] || colorMap.info}`;
                        card.innerHTML = `<div class="flex items-start gap-3"><i class="fas ${iconMap[alert.type] || iconMap.info} text-lg mt-1"></i><div class="flex-1"><div class="flex items-center gap-2 mb-1"><h5 class="font-semibold text-sm">${alert.title}</h5><span class="text-xs px-2 py-0.5 rounded-full ${priorityBadgeMap[alert.priority] || priorityBadgeMap.low}">${alert.priority.toUpperCase()}</span></div><p class="text-sm mb-2">${alert.message}</p><p class="text-xs italic">💡 ${alert.recommendation}</p></div></div>`;
                        dssContainer.appendChild(card);
                    });
                }

                async function updateEmployeeCharts(range = 'daily') {
                    const analyticsData = await fetchEmployeeAnalytics(range);
                    if (!analyticsData) return;
                    const { trend, summary, interpretations, decision_support } = analyticsData;
                    const labels = trend.map(t => t.label);
                    const present = trend.map(t => t.present);
                    const late = trend.map(t => t.late);
                    const undertime = trend.map(t => t.undertime);
                    const overtime = trend.map(t => t.overtime);
                    const absent = trend.map(t => t.absent || 0);
                    const attendancePct = summary.attendance_rate || 0;

                    if (attChart) {
                        attChart.data.labels = labels;
                        attChart.data.datasets[0].data = present;
                        attChart.data.datasets[1].data = late;
                        attChart.data.datasets[2].data = undertime;
                        attChart.data.datasets[3].data = overtime;
                        if (attChart.data.datasets.length > 4) attChart.data.datasets[4].data = absent;
                        attChart.update();
                    }

                    updateInterpretations(analyticsData);
                    updateDSS(analyticsData);

                    const detailsPayload = { 
                        overallPct: Math.round(attendancePct),
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

                    const miniPctEl = document.getElementById('miniPerformancePct');
                    const miniLabelEl = document.getElementById('miniPerformanceLabel');
                    const miniDescEl = document.getElementById('miniPerformanceDesc');
                    const miniDetailsBtn = document.getElementById('miniPerformanceDetailsBtn');
                    if (miniPctEl) miniPctEl.textContent = Math.round(attendancePct) + '%';
                    const label = summary.performance_label || 'Poor';
                    if (miniLabelEl) miniLabelEl.textContent = label;
                    if (miniDescEl) miniDescEl.textContent = `${summary.period_label} (${summary.total_working_days} working days)`;
                    if (miniDetailsBtn) miniDetailsBtn.dataset.details = JSON.stringify(detailsPayload);
                    const miniBadge = document.getElementById('miniPerfBadge');
                    if (miniBadge) {
                        miniBadge.className = 'w-24 h-24 rounded-full flex items-center justify-center font-bold text-lg';
                        if (label === 'Excellent') miniBadge.classList.add('bg-green-100','text-green-600');
                        else if (label === 'Good') miniBadge.classList.add('bg-blue-100','text-blue-700');
                        else if (label === 'Moderate') miniBadge.classList.add('bg-yellow-100','text-yellow-600');
                        else miniBadge.classList.add('bg-red-100','text-red-600');
                    }
                }

                function updatePerformanceModalIfOpen() {
                    const modal = document.getElementById('perfInfoModal');
                    if (modal && !modal.classList.contains('hidden')) {
                        const miniDetailsBtn = document.getElementById('miniPerformanceDetailsBtn');
                        if (miniDetailsBtn && miniDetailsBtn.dataset.details) {
                            showPerformanceInfo(JSON.parse(miniDetailsBtn.dataset.details));
                        }
                    }
                }

                periodRadios.forEach(r => r.addEventListener('change', (e) => {
                    if (e.target.checked) { currentPeriod = e.target.value; updateEmployeeCharts(currentPeriod); }
                }));

                const miniPerfBtn = document.getElementById('miniPerformanceDetailsBtn');
                if (miniPerfBtn) {
                    miniPerfBtn.addEventListener('click', () => {
                        const dstr = miniPerfBtn.dataset.details;
                        if (!dstr) return alert('No details available yet.');
                        showPerformanceInfo(JSON.parse(dstr));
                    });
                }

                function showPerformanceInfo(d) {
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
                    if (absentEl) absentEl.textContent = d.absent || 0;

                    const summary = d.summary;
                    const interpretations = d.interpretations;
                    let label = 'Poor';
                    let desc = 'Needs improvement';
                    
                    if (summary) {
                        label = summary.performance_label || label;
                        const attendanceRate = summary.attendance_rate || 0;
                        const punctualityRate = summary.punctuality_rate || 0;
                        let punctualityDisplay = '';
                        if (attendanceRate < 50) punctualityDisplay = 'N/A (Low Attendance)';
                        else if (attendanceRate < 70) punctualityDisplay = `${punctualityRate}% (⚠️ Low Attendance)`;
                        else punctualityDisplay = `${punctualityRate}%`;
                        desc = `Attendance: ${summary.attendance_rate}% | Punctuality: ${punctualityDisplay}`;
                    }

                    summaryEl.textContent = `${label} (${pct}%)`;
                    descEl.textContent = desc;

                    let recommendation = '';
                    if (interpretations) {
                        recommendation = '<div class="space-y-2">';
                        if (interpretations.attendance) recommendation += `<div><strong>📊 Attendance:</strong> ${interpretations.attendance}</div>`;
                        if (interpretations.punctuality) recommendation += `<div><strong>⏰ Punctuality:</strong> ${interpretations.punctuality}</div>`;
                        if (interpretations.work_hours) recommendation += `<div><strong>🕒 Work Hours:</strong> ${interpretations.work_hours}</div>`;
                        recommendation += '</div>';
                    }
                    recEl.innerHTML = recommendation;

                    percentEl.className = 'w-20 h-20 rounded-full flex items-center justify-center font-bold text-lg';
                    if (label === 'Excellent') percentEl.classList.add('bg-green-100','text-green-600');
                    else if (label === 'Good') percentEl.classList.add('bg-blue-100','text-blue-700');
                    else if (label === 'Moderate') percentEl.classList.add('bg-yellow-100','text-yellow-600');
                    else percentEl.classList.add('bg-red-100','text-red-600');

                    modal.classList.remove('hidden');
                    modal.classList.add('flex');

                    const closeBtn = document.getElementById('perfModalClose');
                    closeBtn.onclick = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); };
                    modal.onclick = (e) => { if (e.target === modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); } };
                }

                updateEmployeeCharts(currentPeriod);
                setInterval(() => { updateEmployeeCharts(currentPeriod); updatePerformanceModalIfOpen(); }, 5000);
            })();
        });
    </script>
</body>
</html>

