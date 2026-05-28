<?php
require_once __DIR__ . '/../auth_guard.php';
require_role('hr');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Bayan ng Mabini | Employee System</title>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"
    />
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
      /* All colors are shades of blue or neutral tones to match the request. */
      @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap");

      body {
        font-family: "Inter", sans-serif;
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
        grid-template-columns: repeat(
          auto-fit,
          minmax(200px, 1fr)
        ); /* Adjusted for responsiveness */
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
      }

      .header-box:nth-child(1) {
        border-color: #3b82f6;
      }
      .header-box:nth-child(2) {
        border-color: #93c5fd;
      }
      .header-box:nth-child(3) {
        border-color: #22d3ee;
      }
      .header-box:nth-child(4) {
        border-color: #60a5fa;
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

      .projects-events-container {
        display: flex;
        flex-wrap: wrap; /* Allows wrapping on smaller screens */
        gap: 2rem;
        margin-bottom: 2rem;
      }

      .active-projects-box,
      .events-box {
        background-color: #fff;
        padding: 2rem;
        border-radius: 1rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        flex: 1 1 45%; /* Flex-basis allows them to grow but wrap */
        min-width: 300px; /* Ensures they don't get too narrow */
      }

      .active-projects-box h3,
      .events-box h3 {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 1rem;
      }

      .export-report {
        float: right;
        font-size: 0.9rem;
        color: #2563eb;
        text-decoration: none;
        font-weight: 600;
      }

      table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
      }

      th,
      td {
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
      .status-badge.inprogress {
        background-color: #dbeafe;
        color: #1e40af;
      }
      .status-badge.pending {
        background-color: #fef2f2;
        color: #ef4444;
      }
      .status-badge.completed {
        background-color: #f0fdf4;
        color: #22c55e;
      }

      .event-item {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        padding: 1rem 0;
        border-bottom: 1px solid #e5e7eb;
      }

      .event-item:last-child {
        border-bottom: none;
      }

      .event-date {
        background-color: #e5e7eb;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        text-align: center;
        line-height: 1;
      }

      .event-date .day {
        font-size: 1.5rem;
        font-weight: 700;
        display: block;
      }

      .event-date .month {
        font-size: 0.8rem;
        text-transform: uppercase;
        font-weight: 600;
        color: #6b7280;
      }

      .event-details {
        flex-grow: 1;
      }

      .event-details .event-title {
        font-weight: 600;
        color: #1f2937;
      }

      .event-details .event-location {
        font-size: 0.9rem;
        color: #6b7280;
      }

      .event-time {
        font-size: 0.9rem;
        color: #9ca3af;
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

      .legend-color.complete {
        background-color: #2563eb;
      }
      .legend-color.pending {
        background-color: #93c5fd;
      }
      .legend-color.not-start {
        background-color: #60a5fa;
      }

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
      .employee-tab-btn[data-category="Permanent"].active {
        background-color: #3b82f6;
      }
      .employee-tab-btn[data-category="Casual"].active {
        background-color: #93c5fd;
      }
      .employee-tab-btn[data-category="JO"].active {
        background-color: #22d3ee;
      }
      .employee-tab-btn[data-category="OJT"].active {
        background-color: #60a5fa;
      }

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
      .employee-card[data-category="Permanent"] .employee-category {
        color: #3b82f6;
      }
      .employee-card[data-category="Casual"] .employee-category {
        color: #93c5fd;
      }
      .employee-card[data-category="JO"] .employee-category {
        color: #22d3ee;
      }
      .employee-card[data-category="OJT"] .employee-category {
        color: #60a5fa;
      }

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
          <img src="../assets/logo.png" alt="Mabini Logo" class="logo-image" />
        </div>
        <span class="header-text">Bayan ng Mabini</span>
      </div>
      <div class="header-profile">
        <img src="../assets/logo.png" alt="Profile" class="profile-image" />
      </div>
    </header>

    <div class="container">
      <aside class="sidebar">
        <nav class="nav-menu">
          <ul>
            <li class="nav-item">
              <a href="dashboard.php"
                ><i class="fas fa-th-large"></i> Dashboard</a
              >
            </li>
            <li class="nav-item">
              <a href="employees.php"
                ><i class="fas fa-users"></i> Employees</a
              >
            </li>
            <li class="nav-item active">
              <a href="#"><i class="fas fa-calendar-alt"></i> Leave Status</a>
            </li>
            <li class="nav-item">
              <a href="leave_request.php"
                ><i class="fas fa-calendar-plus"></i> Leave Request</a
              >
            </li>
            <li class="nav-item">
              <a href="manage_events.php"
                ><i class="fa fa-calendar-times"></i> Manage Events</a
              >
            </li>
            <li class="nav-item">
              <a href="analytics.php"
                ><i class="fas fa-chart-line"></i> Analytics</a
              >
            </li>
          </ul>
        </nav>
        <div class="sign-out">
          <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
        </div>
      </aside>

      <main class="main-content">
        <div class="main-content-area">
          <section id="leave-status-content" class="content-section active">
            <div class="flex justify-between items-center mb-6">
              <h2 class="text-2xl font-bold text-gray-800">Leave Status</h2>
              <div class="flex items-center space-x-4">
                <button
                  id="prevMonth"
                  class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                >
                  Previous
                </button>
                <h3
                  id="currentMonthYear"
                  class="text-xl font-semibold text-gray-700"
                ></h3>
                <button
                  id="nextMonth"
                  class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                >
                  Next
                </button>
              </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 pb-8">
              <div
                id="calendar-weekdays"
                class="grid grid-cols-7 text-center font-semibold text-gray-500 mb-2"
              >
                <span>Sun</span>
                <span>Mon</span>
                <span>Tue</span>
                <span>Wed</span>
                <span>Thu</span>
                <span>Fri</span>
                <span>Sat</span>
              </div>
              <div id="calendar-grid" class="grid grid-cols-7 gap-2"></div>
              <div
                id="calendar-legend"
                class="mt-4 flex items-center gap-6 text-sm text-gray-700 flex-wrap"
              >
                <div class="flex items-center gap-2">
                  <span
                    class="inline-block w-3 h-3 rounded-full bg-gray-400 ring-2 ring-green-600 ring-offset-2"
                  ></span>
                  HR-approved (green ring)
                </div>
                <div class="flex items-center gap-2">
                  <span
                    class="inline-block w-3 h-3 rounded-full bg-gray-400 ring-2 ring-blue-500 ring-offset-2"
                  ></span>
                  Dept-approved (blue ring)
                </div>
                <div class="flex items-center gap-2">
                  <span
                    class="inline-block w-3 h-3 rounded-full bg-emerald-500"
                  ></span
                  ><span
                    class="inline-block w-3 h-3 rounded-full bg-rose-500"
                  ></span
                  ><span
                    class="inline-block w-3 h-3 rounded-full bg-indigo-500"
                  ></span>
                  Different color per person
                </div>
              </div>
            </div>

            <!-- Analytics: compact, below calendar -->
            <section id="analytics" class="mt-6">
              <div class="flex items-center justify-between mb-3">
                <h3 class="text-xl font-semibold text-gray-800">
                  Leave analytics
                </h3>
                <div class="flex items-center gap-2 text-sm">
                  <label for="analytics-scope" class="text-gray-600"
                    >Scope</label
                  >
                  <select
                    id="analytics-scope"
                    class="border rounded-md px-2 py-1 text-gray-700"
                  >
                    <option value="approved" selected>Approved only</option>
                    <option value="all">All statuses</option>
                  </select>
                </div>
              </div>

              <!-- KPI cards -->
              <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div
                  class="bg-white rounded-xl shadow p-4 border-t-4 border-blue-500"
                >
                  <div class="text-sm text-gray-500">
                    Total requests (month)
                  </div>
                  <div id="kpi-total" class="text-2xl font-bold text-gray-800">
                    —
                  </div>
                </div>
                <div
                  class="bg-white rounded-xl shadow p-4 border-t-4 border-emerald-500"
                >
                  <div class="text-sm text-gray-500">Approved</div>
                  <div
                    id="kpi-approved"
                    class="text-2xl font-bold text-gray-800"
                  >
                    —
                  </div>
                </div>
                <div
                  class="bg-white rounded-xl shadow p-4 border-t-4 border-amber-300"
                >
                  <div class="text-sm text-gray-500">Pending</div>
                  <div
                    id="kpi-pending"
                    class="text-2xl font-bold text-gray-800"
                  >
                    —
                  </div>
                </div>
                <div
                  class="bg-white rounded-xl shadow p-4 border-t-4 border-rose-500"
                >
                  <div class="text-sm text-gray-500">Declined</div>
                  <div
                    id="kpi-declined"
                    class="text-2xl font-bold text-gray-800"
                  >
                    —
                  </div>
                </div>
              </div>

              <!-- Enhanced Analytics Section -->
              <div class="mt-4 space-y-4">
                <!-- Loading Indicator -->
                <div id="analytics-loading" class="text-center py-8">
                  <div
                    class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-600"
                  ></div>
                  <p class="mt-2 text-gray-600">Loading analytics...</p>
                </div>

                <!-- Decision Support System Alerts -->
                <div id="dss-alerts" class="hidden">
                  <h4
                    class="font-semibold text-gray-800 mb-3 flex items-center gap-2"
                  >
                    <i class="fas fa-brain text-blue-600"></i>
                    Decision Support System
                  </h4>
                  <div
                    id="dss-container"
                    class="grid grid-cols-1 lg:grid-cols-2 gap-3"
                  ></div>
                </div>

                <!-- Analytics Charts with Interpretations -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                  <!-- Status Chart -->
                  <div class="bg-white rounded-xl shadow p-4">
                    <div class="flex items-center justify-between mb-2">
                      <h4 class="font-semibold text-gray-800">
                        Leave Status Distribution
                      </h4>
                      <span
                        id="label-month"
                        class="text-xs text-gray-500"
                      ></span>
                    </div>
                    <div class="h-64">
                      <canvas
                        id="chart-status"
                        aria-label="Leave by status"
                      ></canvas>
                    </div>
                    <div
                      class="mt-3 p-3 bg-blue-50 rounded-lg border-l-4 border-blue-500"
                    >
                      <p class="text-xs text-gray-700" id="interpret-status">
                        Loading interpretation...
                      </p>
                    </div>
                  </div>

                  <!-- Type Chart -->
                  <div class="bg-white rounded-xl shadow p-4">
                    <div class="flex items-center justify-between mb-2">
                      <h4 class="font-semibold text-gray-800">
                        Leave Types Analysis
                      </h4>
                      <span class="text-xs text-gray-500">Top categories</span>
                    </div>
                    <div style="height: 300px">
                      <canvas
                        id="chart-type"
                        aria-label="Leave by type"
                      ></canvas>
                    </div>
                    <div
                      class="mt-3 p-3 bg-purple-50 rounded-lg border-l-4 border-purple-500"
                    >
                      <p class="text-xs text-gray-700" id="interpret-type">
                        Loading interpretation...
                      </p>
                    </div>
                  </div>

                  <!-- Daily Leave Trend -->
                  <div class="bg-white rounded-xl shadow p-4">
                    <div class="flex items-center justify-between mb-2">
                      <h4 class="font-semibold text-gray-800">
                        Daily Leave Trend
                      </h4>
                      <span class="text-xs text-gray-500"
                        >Leave volume over time</span
                      >
                    </div>
                    <div style="height: 280px">
                      <canvas
                        id="chart-daily-trend"
                        aria-label="Daily leave trend"
                      ></canvas>
                    </div>
                    <div
                      class="mt-3 p-3 bg-amber-50 rounded-lg border-l-4 border-amber-500"
                    >
                      <p class="text-xs text-gray-700" id="interpret-peak">
                        Loading interpretation...
                      </p>
                    </div>
                  </div>

                  <!-- Trends Chart -->
                  <div class="bg-white rounded-xl shadow p-4">
                    <div class="flex items-center justify-between mb-2">
                      <h4 class="font-semibold text-gray-800">Leave Trends</h4>
                      <span class="text-xs text-gray-500"
                        >Month-over-month comparison</span
                      >
                    </div>
                    <div class="h-64">
                      <canvas
                        id="chart-trends"
                        aria-label="Leave trends"
                      ></canvas>
                    </div>
                    <div
                      class="mt-3 p-3 bg-green-50 rounded-lg border-l-4 border-green-500"
                    >
                      <p class="text-xs text-gray-700" id="interpret-trend">
                        Loading interpretation...
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Department Analysis -->
                <div class="bg-white rounded-xl shadow p-4">
                  <div class="flex items-center justify-between mb-2">
                    <h4 class="font-semibold text-gray-800">
                      Department Leave Distribution
                    </h4>
                    <span class="text-xs text-gray-500">By department</span>
                  </div>
                  <div class="h-80">
                    <canvas
                      id="chart-department"
                      aria-label="Leave by department"
                    ></canvas>
                  </div>
                  <div
                    class="mt-3 p-3 bg-indigo-50 rounded-lg border-l-4 border-indigo-500"
                  >
                    <p class="text-xs text-gray-700" id="interpret-department">
                      Loading interpretation...
                    </p>
                  </div>
                </div>
              </div>
            </section>

            <!-- Day Details Modal -->
            <div
              id="day-modal"
              class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50"
            >
              <div
                class="bg-white rounded-xl shadow-2xl w-full max-w-xl max-h-[80vh] overflow-y-auto p-4"
              >
                <div class="flex items-center justify-between mb-2">
                  <h4
                    id="day-modal-title"
                    class="text-lg font-semibold text-gray-800"
                  >
                    Leave details
                  </h4>
                  <button
                    id="day-modal-close"
                    class="text-gray-500 hover:text-gray-700 text-xl leading-none"
                  >
                    &times;
                  </button>
                </div>
                <ul id="day-modal-list" class="divide-y divide-gray-200"></ul>
              </div>
            </div>

            <!-- Leave Requests section removed as requested -->
          </section>
        </div>
      </main>
    </div>

    <script>
      // Wire calendar to real API and render approved leaves per day
      document.addEventListener("DOMContentLoaded", () => {
        const calendarGrid = document.getElementById("calendar-grid");
        const currentMonthYear = document.getElementById("currentMonthYear");
        const prevMonthBtn = document.getElementById("prevMonth");
        const nextMonthBtn = document.getElementById("nextMonth");
        const API_URL = "../api/get_leave_requests.php"; // returns lr.* with firstname, lastname, department, position

        let currentDate = new Date();
        let monthLeaves = [];
        let analyticsLeaves = [];
        let isLoading = false;
        let byDayGlobal = new Map(); // keeps last rendered day->list map for click details

        // Helpers
        function formatYMD(d) {
          return [
            d.getFullYear(),
            String(d.getMonth() + 1).padStart(2, "0"),
            String(d.getDate()).padStart(2, "0"),
          ].join("-");
        }

        function parseStartEnd(datesStr) {
          if (!datesStr) return null;
          const matches = String(datesStr).match(/\d{4}-\d{2}-\d{2}/g);
          if (matches && matches.length > 0) {
            const s = new Date(matches[0]);
            const e = new Date(matches[1] || matches[0]);
            if (!isNaN(s) && !isNaN(e)) return { start: s, end: e };
          }
          return null;
        }

        function datesInRange(start, end, cb) {
          const d = new Date(start);
          d.setHours(0, 0, 0, 0);
          const e = new Date(end);
          e.setHours(0, 0, 0, 0);
          while (d <= e) {
            cb(new Date(d));
            d.setDate(d.getDate() + 1);
          }
        }

        function buildMonthCells(date) {
          calendarGrid.innerHTML = "";
          const month = date.getMonth();
          const year = date.getFullYear();
          const firstDay = new Date(year, month, 1).getDay();
          const daysInMonth = new Date(year, month + 1, 0).getDate();

          currentMonthYear.textContent = date.toLocaleDateString("en-US", {
            month: "long",
            year: "numeric",
          });

          for (let i = 0; i < firstDay; i++) {
            const emptyCell = document.createElement("div");
            calendarGrid.appendChild(emptyCell);
          }

          for (let i = 1; i <= daysInMonth; i++) {
            const dayCell = document.createElement("div");
            dayCell.className =
              "calendar-day relative p-2 min-h-24 border rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors cursor-pointer";
            dayCell.innerHTML = `<span class="font-semibold text-gray-700">${i}</span>`;
            dayCell.dataset.date = `${year}-${String(month + 1).padStart(
              2,
              "0"
            )}-${String(i).padStart(2, "0")}`;
            calendarGrid.appendChild(dayCell);
          }
        }

        function renderEventsForMonth(date) {
          const month = date.getMonth();
          const year = date.getFullYear();

          // Prepare a map dateStr -> events[]
          const byDay = new Map();
          const approved = monthLeaves; // already filtered by API to status=approved and target month
          approved.forEach((lr) => {
            const range = parseStartEnd(lr.dates);
            if (!range) return;
            datesInRange(range.start, range.end, (d) => {
              if (d.getMonth() !== month || d.getFullYear() !== year) return;
              const key = formatYMD(d);
              if (!byDay.has(key)) byDay.set(key, []);
              byDay.get(key).push(lr);
            });
          });

          // Render dots into cells
          byDay.forEach((list, ymd) => {
            const cell = calendarGrid.querySelector(`[data-date="${ymd}"]`);
            if (!cell) return;
            // container for dots
            let dotsWrap = cell.querySelector(".dots-wrap");
            if (!dotsWrap) {
              dotsWrap = document.createElement("div");
              dotsWrap.className = "dots-wrap mt-2 flex flex-wrap gap-1";
              cell.appendChild(dotsWrap);
            }
            // sort: HR-approved first
            list.sort(
              (a, b) =>
                (Number(b.approved_by_hr) || 0) -
                (Number(a.approved_by_hr) || 0)
            );
            const maxDots = 12;
            dotsWrap.innerHTML = "";
            list.slice(0, maxDots).forEach((lr) => {
              const span = document.createElement("span");
              const isHR =
                String(lr.approved_by_hr) === "1" ||
                lr.approved_by_hr === 1 ||
                lr.approved_by_hr === true;
              const personClass = personColorClass(
                lr.employee_email ||
                  `${lr.firstname || ""} ${lr.lastname || ""}`
              );
              const name = `${lr.firstname || ""} ${lr.lastname || ""}`.trim();
              span.className = `inline-block w-2.5 h-2.5 rounded-full ${personClass} ring-2 ${
                isHR ? "ring-green-600" : "ring-blue-500"
              } ring-offset-1 ring-offset-gray-50`;
              const dept = lr.department && String(lr.department).trim() ? String(lr.department) : "";
              const pos = lr.position ? String(lr.position) : "";
              const meta = [dept, pos].filter(Boolean).join(" • ");
              const range = parseStartEnd(lr.dates);
              const dateInfo = range
                ? ` — ${formatYMD(range.start)} to ${formatYMD(range.end)}`
                : "";
              span.title = `${name ? name + " — " : ""}${
                lr.leave_type || "Leave"
              }${meta ? " — " + meta : ""}${dateInfo}`;
              span.dataset.date = ymd;
              dotsWrap.appendChild(span);
            });
            if (list.length > maxDots) {
              const more = document.createElement("span");
              more.className =
                "inline-flex items-center justify-center w-4 h-4 rounded-full bg-gray-200 text-[10px] text-gray-700";
              more.textContent = `+${list.length - maxDots}`;
              more.dataset.date = ymd;
              // tooltip summary for overflow
              const hrCount = list.filter(
                (lr) =>
                  String(lr.approved_by_hr) === "1" ||
                  lr.approved_by_hr === 1 ||
                  lr.approved_by_hr === true
              ).length;
              const byType = new Map();
              list.forEach((l) => {
                const k = (l.leave_type || "Unknown").trim();
                byType.set(k, (byType.get(k) || 0) + 1);
              });
              const summaryTypes = Array.from(byType.entries())
                .sort((a, b) => b[1] - a[1])
                .slice(0, 3)
                .map(([k, v]) => `${k} ${v}`)
                .join(", ");
              const shown = Math.min(list.length, maxDots);
              const extra = list.length - shown;
              more.title = `${
                list.length
              } leaves (+${extra} more). HR-approved: ${hrCount}. ${
                summaryTypes ? "Types: " + summaryTypes : ""
              }`;
              dotsWrap.appendChild(more);
            }

            // Highlight the day cell border when any HR-approved leave exists that day
            const anyHR = list.some(
              (lr) =>
                String(lr.approved_by_hr) === "1" ||
                lr.approved_by_hr === 1 ||
                lr.approved_by_hr === true
            );
            cell.classList.remove("border-2", "border-green-500");
            if (anyHR) {
              cell.classList.add("border-2", "border-green-500");
              cell.title =
                (cell.title ? cell.title + "\n" : "") +
                "Has HR-approved leave(s)";
            }

            // Compact tooltip on the cell when overflowing
            if (list.length > maxDots) {
              const hrCount2 = list.filter(
                (lr) =>
                  String(lr.approved_by_hr) === "1" ||
                  lr.approved_by_hr === 1 ||
                  lr.approved_by_hr === true
              ).length;
              const byType2 = new Map();
              list.forEach((l) => {
                const k = (l.leave_type || "Unknown").trim();
                byType2.set(k, (byType2.get(k) || 0) + 1);
              });
              const summaryTypes2 = Array.from(byType2.entries())
                .sort((a, b) => b[1] - a[1])
                .slice(0, 3)
                .map(([k, v]) => `${k} ${v}`)
                .join(", ");
              const shown2 = Math.min(list.length, maxDots);
              const extra2 = list.length - shown2;
              const compact = `${
                list.length
              } leaves (+${extra2} more). HR-approved: ${hrCount2}. ${
                summaryTypes2 ? "Types: " + summaryTypes2 : ""
              }`;
              cell.title = (cell.title ? cell.title + "\n" : "") + compact;
            }
          });
          byDayGlobal = byDay; // save for click-to-view

          // Analytics removed for now
        }

        function renderCalendar(date) {
          buildMonthCells(date);
          renderEventsForMonth(date);
        }

        async function fetchLeavesForMonth(date) {
          if (isLoading) return;
          isLoading = true;
          try {
            // Fetch all approved to ensure cross-month leave spans are included
            const url = `${API_URL}?status=approved`;
            const res = await fetch(url);
            const js = await res.json();
            let data =
              js && js.success && Array.isArray(js.data) ? js.data : [];
            // Show leaves in the calendar only when BOTH HR AND Municipal Admin have approved them
            data = data.filter(
              (l) =>
                (String(l.approved_by_hr) === "1" ||
                  l.approved_by_hr === 1 ||
                  l.approved_by_hr === true) &&
                (String(l.approved_by_municipal) === "1" ||
                  l.approved_by_municipal === 1 ||
                  l.approved_by_municipal === true)
            );
            monthLeaves = data;
          } catch (e) {
            console.error("Failed to load leave requests", e);
            monthLeaves = [];
          } finally {
            isLoading = false;
          }
        }

        // Fetch analytics data for the visible month (based on applied_at)
        async function fetchAnalyticsForMonth(date) {
          const y = date.getFullYear();
          const m = date.getMonth() + 1; // 1-12
          try {
            const url = `${API_URL}?month=${m}&year=${y}`; // all statuses
            const res = await fetch(url);
            const js = await res.json();
            analyticsLeaves =
              js && js.success && Array.isArray(js.data) ? js.data : [];
          } catch (e) {
            console.error("Failed to load analytics leaves", e);
            analyticsLeaves = [];
          }
        }

        // Enhanced analytics with new API
        let statusChart = null;
        let typeChart = null;
        let trendsChart = null;
        let departmentChart = null;
        let dailyTrendChart = null;
        let analyticsData = null;

        // Helper: whether a leave row is HR-approved
        function hrApproved(l) {
          return (
            String(l.approved_by_hr) === "1" ||
            l.approved_by_hr === 1 ||
            l.approved_by_hr === true
          );
        }

        // Fetch enhanced analytics from new API
        async function fetchEnhancedAnalytics(date) {
          const y = date.getFullYear();
          const m = date.getMonth() + 1;
          try {
            console.log(`Fetching analytics for ${y}-${m}...`);
            const res = await fetch(
              `../api/leave_analytics.php?month=${m}&year=${y}`
            );
            if (!res.ok) {
              throw new Error(`HTTP ${res.status}: ${res.statusText}`);
            }
            const json = await res.json();
            console.log("Analytics response:", json);
            if (json && json.success) {
              analyticsData = json.analytics;
              console.log("Analytics data loaded:", analyticsData);
              return analyticsData;
            } else {
              console.error("Analytics API returned success=false", json);
            }
          } catch (e) {
            console.error("Failed to load analytics", e);
            // Show user-friendly error
            const dssContainer = document.getElementById("dss-container");
            if (dssContainer) {
              dssContainer.innerHTML = `
                <div class="col-span-2 p-4 rounded-lg border-l-4 bg-red-50 border-red-500 text-red-800">
                  <div class="flex items-start gap-3">
                    <i class="fas fa-exclamation-triangle text-lg mt-1"></i>
                    <div>
                      <h5 class="font-semibold">Analytics Error</h5>
                      <p class="text-sm">Failed to load analytics data. ${e.message}</p>
                    </div>
                  </div>
                </div>
              `;
            }
          }
          return null;
        }

        function updateKPIs(scope) {
          if (!analyticsData) {
            console.warn("Cannot update KPIs: analyticsData is null");
            return;
          }
          const { by_status, total_requests } = analyticsData;

          const kpiTotal = document.getElementById("kpi-total");
          const kpiApproved = document.getElementById("kpi-approved");
          const kpiPending = document.getElementById("kpi-pending");
          const kpiDeclined = document.getElementById("kpi-declined");

          if (kpiTotal) kpiTotal.textContent = String(total_requests || 0);
          if (kpiApproved)
            kpiApproved.textContent = String(by_status?.approved || 0);
          if (kpiPending)
            kpiPending.textContent = String(by_status?.pending || 0);
          if (kpiDeclined)
            kpiDeclined.textContent = String(by_status?.declined || 0);
        }

        function updateStatusChart() {
          if (!analyticsData) {
            console.warn("Cannot update status chart: analyticsData is null");
            return;
          }
          const ctx = document.getElementById("chart-status");
          if (!ctx) {
            console.warn("Cannot find chart-status canvas element");
            return;
          }

          const { by_status, interpretations } = analyticsData;
          const data = {
            labels: ["Approved", "Pending", "Declined"],
            datasets: [
              {
                data: [
                  by_status.approved,
                  by_status.pending,
                  by_status.declined,
                ],
                backgroundColor: ["#10b981", "#fbbf24", "#f43f5e"],
                borderWidth: 0,
              },
            ],
          };
          const options = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { position: "bottom" },
              tooltip: {
                callbacks: {
                  label: function (context) {
                    const total = context.dataset.data.reduce(
                      (a, b) => a + b,
                      0
                    );
                    const pct =
                      total > 0
                        ? ((context.parsed / total) * 100).toFixed(1)
                        : 0;
                    return `${context.label}: ${context.parsed} (${pct}%)`;
                  },
                },
              },
            },
          };

          if (statusChart) {
            statusChart.data = data;
            statusChart.update();
          } else {
            statusChart = new Chart(ctx, { type: "doughnut", data, options });
          }

          // Update interpretation
          const interpretEl = document.getElementById("interpret-status");
          if (interpretEl && interpretations) {
            interpretEl.textContent = interpretations.status;
          }
        }

        function updateTypeChart() {
          if (!analyticsData) {
            console.warn("Cannot update type chart: analyticsData is null");
            return;
          }
          const ctx = document.getElementById("chart-type");
          if (!ctx) {
            console.warn("Cannot find chart-type canvas element");
            return;
          }

          const { by_type, interpretations } = analyticsData;
          const sorted = by_type
            ? Object.entries(by_type)
                .sort((a, b) => b[1] - a[1])
                .slice(0, 8)
            : [];

          // Handle empty data
          if (sorted.length === 0) {
            sorted.push(["No data", 0]);
          }

          const labels = sorted.map(([k]) => k);
          const values = sorted.map(([, v]) => v);
          const palette = [
            "#3b82f6",
            "#60a5fa",
            "#93c5fd",
            "#22d3ee",
            "#06b6d4",
            "#10b981",
            "#f59e0b",
            "#8b5cf6",
          ];

          // Truncate long labels for display
          const displayLabels = labels.map((label) => {
            if (label.length > 30) {
              return label.substring(0, 27) + "...";
            }
            return label;
          });

          const data = {
            labels: displayLabels,
            datasets: [
              {
                label: "Requests",
                data: values,
                backgroundColor: palette.slice(0, values.length),
                borderWidth: 0,
              },
            ],
          };
          const options = {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: "y", // Make it horizontal for better label display
            plugins: {
              legend: { display: false },
              tooltip: {
                callbacks: {
                  title: function (context) {
                    // Show full label in tooltip
                    return labels[context[0].dataIndex];
                  },
                  label: function (context) {
                    return `${context.parsed.x} request${
                      context.parsed.x !== 1 ? "s" : ""
                    }`;
                  },
                },
              },
            },
            scales: {
              x: {
                beginAtZero: true,
                ticks: { precision: 0 },
                title: {
                  display: true,
                  text: "Number of Requests",
                },
              },
              y: {
                ticks: {
                  autoSkip: false,
                  font: { size: 11 },
                },
              },
            },
          };

          if (typeChart) {
            typeChart.data = data;
            typeChart.update();
          } else {
            typeChart = new Chart(ctx, { type: "bar", data, options });
          }

          // Update interpretation
          const interpretEl = document.getElementById("interpret-type");
          if (interpretEl && interpretations) {
            interpretEl.textContent = interpretations.type;
          }
        }

        function updateTrendsChart() {
          if (!analyticsData) {
            console.warn("Cannot update trends chart: analyticsData is null");
            return;
          }
          const ctx = document.getElementById("chart-trends");
          if (!ctx) {
            console.warn("Cannot find chart-trends canvas element");
            return;
          }

          const { trends, interpretations } = analyticsData;
          const data = {
            labels: ["Previous Month", "Current Month"],
            datasets: [
              {
                label: "Leave Requests",
                data: [trends.previous_month, trends.current_month],
                backgroundColor: ["#93c5fd", "#3b82f6"],
                borderWidth: 0,
              },
            ],
          };

          const changeIcon =
            trends.direction === "increase"
              ? "↑"
              : trends.direction === "decrease"
              ? "↓"
              : "→";

          const options = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false },
              tooltip: {
                callbacks: {
                  afterLabel: function (context) {
                    if (context.dataIndex === 1) {
                      return `${changeIcon} ${Math.abs(
                        trends.change_percentage
                      )}% ${trends.direction}`;
                    }
                    return "";
                  },
                },
              },
            },
            scales: {
              y: { beginAtZero: true, ticks: { precision: 0 } },
            },
          };

          if (trendsChart) {
            trendsChart.data = data;
            trendsChart.update();
          } else {
            trendsChart = new Chart(ctx, { type: "bar", data, options });
          }

          // Update interpretation
          const interpretEl = document.getElementById("interpret-trend");
          if (interpretEl && interpretations) {
            interpretEl.textContent = interpretations.trend;
          }
        }

        function updateDepartmentChart() {
          if (!analyticsData) {
            console.warn(
              "Cannot update department chart: analyticsData is null"
            );
            return;
          }
          const ctx = document.getElementById("chart-department");
          if (!ctx) {
            console.warn("Cannot find chart-department canvas element");
            return;
          }

          const { by_department, interpretations } = analyticsData;
          const depts = by_department ? Object.keys(by_department) : [];

          // Handle empty data
          if (depts.length === 0) {
            depts.push("No data");
            by_department["No data"] = { approved: 0, pending: 0, declined: 0 };
          }

          const approved = depts.map((d) => by_department[d].approved || 0);
          const pending = depts.map((d) => by_department[d].pending || 0);
          const declined = depts.map((d) => by_department[d].declined || 0);

          const data = {
            labels: depts,
            datasets: [
              {
                label: "Approved",
                data: approved,
                backgroundColor: "#10b981",
              },
              {
                label: "Pending",
                data: pending,
                backgroundColor: "#fbbf24",
              },
              {
                label: "Declined",
                data: declined,
                backgroundColor: "#f43f5e",
              },
            ],
          };

          const options = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { position: "top" },
            },
            scales: {
              x: { stacked: true },
              y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } },
            },
          };

          if (departmentChart) {
            departmentChart.data = data;
            departmentChart.update();
          } else {
            departmentChart = new Chart(ctx, { type: "bar", data, options });
          }

          // Update interpretation
          const interpretEl = document.getElementById("interpret-department");
          if (interpretEl && interpretations) {
            interpretEl.textContent = interpretations.department;
          }
        }

        function updateDailyTrendChart() {
          if (!analyticsData) {
            console.warn("Cannot update daily trend: analyticsData is null");
            return;
          }
          const ctx = document.getElementById("chart-daily-trend");
          if (!ctx) {
            console.warn("Cannot find chart-daily-trend canvas element");
            return;
          }

          const { peak_days, total_requests } = analyticsData;

          // Convert peak_days object to sorted array
          const entries = peak_days
            ? Object.entries(peak_days).sort((a, b) => a[0].localeCompare(b[0]))
            : [];

          if (entries.length === 0) {
            entries.push([new Date().toISOString().split("T")[0], 0]);
          }

          const dates = entries.map(([date]) => {
            const d = new Date(date + "T00:00:00");
            return d.toLocaleDateString("en-US", {
              month: "short",
              day: "numeric",
            });
          });
          const counts = entries.map(([, count]) => count);

          // Calculate statistics
          const maxCount = Math.max(...counts);
          const minCount = Math.min(...counts);
          const avgCount = counts.reduce((a, b) => a + b, 0) / counts.length;
          const totalDays = entries.length;

          // Create gradient
          const gradient = ctx
            .getContext("2d")
            .createLinearGradient(0, 0, 0, 280);
          gradient.addColorStop(0, "rgba(59, 130, 246, 0.4)");
          gradient.addColorStop(0.5, "rgba(99, 102, 241, 0.2)");
          gradient.addColorStop(1, "rgba(139, 92, 246, 0.05)");

          const data = {
            labels: dates,
            datasets: [
              {
                label: "Employees on Leave",
                data: counts,
                borderColor: "#3b82f6",
                backgroundColor: gradient,
                tension: 0.4,
                fill: true,
                pointRadius: 5,
                pointHoverRadius: 8,
                pointBackgroundColor: "#3b82f6",
                pointBorderColor: "#fff",
                pointBorderWidth: 3,
                pointHoverBackgroundColor: "#1d4ed8",
                pointHoverBorderWidth: 3,
                borderWidth: 3,
              },
            ],
          };

          const options = {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
              mode: "nearest",
              intersect: false,
            },
            plugins: {
              legend: {
                display: false,
              },
              tooltip: {
                backgroundColor: "rgba(0, 0, 0, 0.8)",
                padding: 12,
                titleFont: { size: 14, weight: "bold" },
                bodyFont: { size: 13 },
                callbacks: {
                  title: function (context) {
                    return context[0].label;
                  },
                  label: function (context) {
                    const count = context.parsed.y;
                    const diffFromAvg = count - avgCount;
                    const diffText =
                      diffFromAvg > 0
                        ? `+${diffFromAvg.toFixed(1)} above average`
                        : diffFromAvg < 0
                        ? `${diffFromAvg.toFixed(1)} below average`
                        : "at average";
                    return [
                      `${count} employee${count !== 1 ? "s" : ""} on leave`,
                      `${diffText}`,
                    ];
                  },
                  afterBody: function (context) {
                    const count = context[0].parsed.y;
                    if (count >= avgCount * 1.5) {
                      return "\n⚠️ High staffing impact";
                    } else if (count >= avgCount) {
                      return "\n📊 Above average";
                    } else if (count < avgCount && count > 0) {
                      return "\n✓ Normal levels";
                    }
                    return "";
                  },
                },
              },
            },
            scales: {
              y: {
                beginAtZero: true,
                grid: {
                  color: "rgba(0, 0, 0, 0.05)",
                  drawBorder: false,
                },
                ticks: {
                  precision: 0,
                  font: { size: 11 },
                  color: "#6b7280",
                  padding: 8,
                },
                title: {
                  display: true,
                  text: "Employees on Leave",
                  font: { size: 12, weight: "600" },
                  color: "#374151",
                  padding: { top: 0, bottom: 10 },
                },
                suggestedMax: maxCount > 0 ? Math.ceil(maxCount * 1.2) : 5,
              },
              x: {
                grid: {
                  display: false,
                  drawBorder: false,
                },
                ticks: {
                  maxRotation: 0,
                  minRotation: 0,
                  autoSkip: true,
                  maxTicksLimit: 10,
                  font: { size: 11 },
                  color: "#6b7280",
                },
              },
            },
          };

          if (dailyTrendChart) {
            dailyTrendChart.data = data;
            dailyTrendChart.options = options;
            dailyTrendChart.update();
          } else {
            dailyTrendChart = new Chart(ctx, { type: "line", data, options });
          }

          // Generate better interpretation
          const interpretEl = document.getElementById("interpret-peak");
          if (interpretEl) {
            let interpretation = "";

            if (totalDays === 0 || maxCount === 0) {
              interpretation =
                "No leave activity recorded for this period. All staff are available.";
            } else if (maxCount === minCount) {
              interpretation = `Consistent leave pattern with ${maxCount} employee${
                maxCount !== 1 ? "s" : ""
              } on leave daily across ${totalDays} days. Stable staffing levels maintained.`;
            } else {
              const variance = maxCount - minCount;
              const highDays = counts.filter((c) => c >= avgCount * 1.3).length;

              interpretation = `Leave volume ranges from ${minCount} to ${maxCount} employees over ${totalDays} days (avg: ${avgCount.toFixed(
                1
              )}). `;

              if (highDays > totalDays * 0.3) {
                interpretation += `${highDays} high-volume days detected - ensure adequate staffing coverage during peak periods.`;
              } else if (variance > avgCount) {
                interpretation += `Significant fluctuation in leave patterns - consider implementing leave scheduling coordination.`;
              } else {
                interpretation += `Moderate variation in daily leave volume - staffing levels are manageable.`;
              }
            }

            interpretEl.textContent = interpretation;
          }
        }

        function updateDSS() {
          if (!analyticsData) {
            console.warn("Cannot update DSS: analyticsData is null");
            return;
          }

          const dssContainer = document.getElementById("dss-container");
          const dssAlerts = document.getElementById("dss-alerts");

          if (!dssContainer || !dssAlerts) {
            console.warn("Cannot find DSS elements");
            return;
          }

          const alerts = analyticsData.decision_support || [];

          if (alerts.length === 0) {
            dssAlerts.classList.add("hidden");
            return;
          }

          dssAlerts.classList.remove("hidden");
          dssContainer.innerHTML = "";

          const iconMap = {
            warning: "fa-exclamation-triangle",
            info: "fa-info-circle",
            success: "fa-check-circle",
          };

          const colorMap = {
            warning: "bg-amber-50 border-amber-500 text-amber-800",
            info: "bg-blue-50 border-blue-500 text-blue-800",
            success: "bg-green-50 border-green-500 text-green-800",
          };

          const priorityBadgeMap = {
            high: "bg-red-100 text-red-800",
            medium: "bg-yellow-100 text-yellow-800",
            low: "bg-gray-100 text-gray-800",
          };

          alerts.forEach((alert) => {
            const card = document.createElement("div");
            card.className = `p-4 rounded-lg border-l-4 ${
              colorMap[alert.type] || colorMap.info
            }`;
            card.innerHTML = `
              <div class="flex items-start gap-3">
                <i class="fas ${
                  iconMap[alert.type] || iconMap.info
                } text-lg mt-1"></i>
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-1">
                    <h5 class="font-semibold">${alert.title}</h5>
                    <span class="text-xs px-2 py-0.5 rounded-full ${
                      priorityBadgeMap[alert.priority]
                    }">${alert.priority.toUpperCase()}</span>
                  </div>
                  <p class="text-sm mb-2">${alert.message}</p>
                  <p class="text-xs italic">💡 ${alert.recommendation}</p>
                </div>
              </div>
            `;
            dssContainer.appendChild(card);
          });
        }

        function updateAnalyticsUI() {
          // Hide loading indicator
          const loadingEl = document.getElementById("analytics-loading");
          if (loadingEl) loadingEl.style.display = "none";

          const label = document.getElementById("label-month");
          if (label)
            label.textContent = currentDate.toLocaleDateString("en-US", {
              month: "long",
              year: "numeric",
            });

          if (!analyticsData) {
            console.error("Cannot update analytics UI: data not loaded");
            return;
          }

          console.log("Updating analytics UI with data:", analyticsData);
          updateKPIs();
          updateStatusChart();
          updateTypeChart();
          updateTrendsChart();
          updateDepartmentChart();
          updateDailyTrendChart();
          updateDSS();
        }

        // Optional: periodic refresh to reflect new approvals
        async function refreshDataAndRender() {
          try {
            await fetchLeavesForMonth(currentDate);
            renderCalendar(currentDate);
          } catch (e) {
            /* non-blocking */
          }
        }

        if (prevMonthBtn && nextMonthBtn) {
          prevMonthBtn.addEventListener("click", async () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            await Promise.all([
              fetchLeavesForMonth(currentDate),
              fetchEnhancedAnalytics(currentDate),
            ]);
            renderCalendar(currentDate);
            updateAnalyticsUI();
          });
          nextMonthBtn.addEventListener("click", async () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            await Promise.all([
              fetchLeavesForMonth(currentDate),
              fetchEnhancedAnalytics(currentDate),
            ]);
            renderCalendar(currentDate);
            updateAnalyticsUI();
          });
        }

        // Initial render
        (async function init() {
          await Promise.all([
            fetchLeavesForMonth(currentDate),
            fetchEnhancedAnalytics(currentDate),
          ]);
          renderCalendar(currentDate);
          updateAnalyticsUI();
          // refresh every 60s
          setInterval(async () => {
            await Promise.all([
              fetchLeavesForMonth(currentDate),
              fetchEnhancedAnalytics(currentDate),
            ]);
            renderCalendar(currentDate);
            updateAnalyticsUI();
          }, 60000);
        })();

        // Person color mapping
        function personColorClass(key) {
          const colors = [
            "bg-rose-500",
            "bg-amber-500",
            "bg-emerald-500",
            "bg-indigo-500",
            "bg-fuchsia-500",
            "bg-teal-500",
            "bg-purple-500",
            "bg-cyan-600",
            "bg-orange-500",
            "bg-lime-600",
          ];
          let hash = 0;
          for (let i = 0; i < String(key || "").length; i++) {
            hash = (hash * 31 + key.charCodeAt(i)) >>> 0;
          }
          return colors[hash % colors.length];
        }

        // Click-to-view details
        calendarGrid.addEventListener("click", (e) => {
          const target = e.target;
          const cell = target.closest("[data-date]");
          if (!cell) return;
          const ymd = cell.dataset.date;
          showDayModal(ymd);
        });

        function showDayModal(ymd) {
          const list = byDayGlobal.get(ymd) || [];
          const modal = document.getElementById("day-modal");
          const title = document.getElementById("day-modal-title");
          const ul = document.getElementById("day-modal-list");
          title.textContent = `Leaves on ${ymd}`;
          ul.innerHTML = "";
          if (list.length === 0) {
            ul.innerHTML =
              '<li class="py-3 text-sm text-gray-500">No leaves on this date.</li>';
          } else {
            list.forEach((lr) => {
              const isHR =
                String(lr.approved_by_hr) === "1" ||
                lr.approved_by_hr === 1 ||
                lr.approved_by_hr === true;
              const name = `${lr.firstname || ""} ${lr.lastname || ""}`.trim();
              const dept = lr.department ? String(lr.department) : "";
              const pos = lr.position ? String(lr.position) : "";
              const personDot = `<span class="inline-block w-2.5 h-2.5 rounded-full ${personColorClass(
                lr.employee_email || name
              )} mr-2"></span>`;
              const ringText = isHR ? "HR-approved" : "Dept-approved";
              const range = parseStartEnd(lr.dates);
              const startYMD = range ? formatYMD(range.start) : "";
              const endYMD = range ? formatYMD(range.end) : "";
              const days = range
                ? Math.round(
                    (range.end - range.start) / (1000 * 60 * 60 * 24)
                  ) + 1
                : 1;
              const monthStart = new Date(
                currentDate.getFullYear(),
                currentDate.getMonth(),
                1
              );
              const monthEnd = new Date(
                currentDate.getFullYear(),
                currentDate.getMonth() + 1,
                0
              );
              const continuesPrev = range ? range.start < monthStart : false;
              const continuesNext = range ? range.end > monthEnd : false;
              const item = document.createElement("li");
              item.className = "py-3 flex items-start justify-between gap-3";
              item.innerHTML = `
                <div class="flex items-start text-sm">
                  ${personDot}
                  <div>
                    <div class="font-semibold text-gray-800">${
                      name || lr.employee_email || "Unknown"
                    }</div>
                    <div class="text-gray-600">${lr.leave_type || "Leave"}</div>
                    <div class="text-xs text-gray-500">From ${startYMD} to ${endYMD} (${days} day${
                days > 1 ? "s" : ""
              }) • ${ringText}</div>
                    ${
                      continuesPrev || continuesNext
                        ? `<div class="text-xs text-amber-600">${
                            continuesPrev ? "Started previous month" : ""
                          }${continuesPrev && continuesNext ? " • " : ""}${
                            continuesNext ? "Continues next month" : ""
                          }</div>`
                        : ""
                    }
                    ${
                      dept || pos
                        ? `<div class="text-xs text-gray-500">${[dept, pos]
                            .filter(Boolean)
                            .join(" • ")}</div>`
                        : ""
                    }
                  </div>
                </div>
                <div class="shrink-0">
                  <a href="../dept_head/civil_form.php?id=${
                    lr.id
                  }" target="_blank" class="px-2 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">View Form</a>
                </div>`;
              ul.appendChild(item);
            });
          }
          modal.classList.remove("hidden");
          modal.classList.add("flex");
        }

        document
          .getElementById("day-modal-close")
          .addEventListener("click", closeDayModal);
        document.getElementById("day-modal").addEventListener("click", (e) => {
          if (e.target.id === "day-modal") closeDayModal();
        });
        function closeDayModal() {
          const modal = document.getElementById("day-modal");
          modal.classList.add("hidden");
          modal.classList.remove("flex");
        }

        // Scope change
        const scopeSel = document.getElementById("analytics-scope");
        if (scopeSel) scopeSel.addEventListener("change", updateAnalyticsUI);
      });
    </script>
  </body>
</html>
