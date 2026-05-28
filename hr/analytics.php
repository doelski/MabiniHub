<?php
require_once __DIR__ . '/../auth_guard.php';
require_role('hr');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HR Analytics Dashboard | Bayan ng Mabini</title>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"
    />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
      @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap");

      body {
        font-family: "Inter", sans-serif;
        background-color: #f0f4f8;
        margin: 0;
        padding: 0;
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

      .attendance-btn {
        margin-left: 0.75rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.375rem 0.75rem;
        background: #f59e0b;
        color: #fff;
        border-radius: 0.5rem;
        text-decoration: none;
        font-weight: 600;
        transition: background 0.2s;
      }

      .attendance-btn:hover {
        background: #d97706;
      }

      .container {
        display: flex;
        min-height: 100vh;
      }

      .sidebar {
        width: 280px;
        background-color: #ffffff;
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 1rem 1rem;
        padding-top: 0.5rem;
        border-right: 4px solid #3b82f6;
        flex-shrink: 0;
        position: fixed;
        top: 60px;
        left: 0;
        bottom: 0;
        overflow-y: auto;
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
        background-color: #dbeafe;
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
        margin-left: 280px;
        margin-top: 60px;
        overflow-y: auto;
        transition: opacity 0.3s ease;
      }

      .header-banner {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: white;
        padding: 2rem;
        border-radius: 1rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      }

      .header-banner h1 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
      }

      .header-banner p {
        font-size: 1rem;
        opacity: 0.9;
      }

      .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
      }

      .stat-card {
        background-color: #fff;
        padding: 1.5rem;
        border-radius: 1rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        border-top: 4px solid;
        position: relative;
      }

      .stat-card.blue {
        border-color: #3b82f6;
      }
      .stat-card.green {
        border-color: #10b981;
      }
      .stat-card.yellow {
        border-color: #f59e0b;
      }
      .stat-card.red {
        border-color: #ef4444;
      }
      .stat-card.purple {
        border-color: #8b5cf6;
      }
      .stat-card.cyan {
        border-color: #06b6d4;
      }

      .stat-card .label {
        font-size: 0.875rem;
        color: #6b7280;
        font-weight: 500;
        margin-bottom: 0.5rem;
      }

      .stat-card .value {
        font-size: 2rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.25rem;
      }

      .stat-card .sub-value {
        font-size: 0.875rem;
        color: #9ca3af;
      }

      .live-indicator {
        position: absolute;
        top: 1rem;
        right: 1rem;
        display: flex;
        align-items: center;
        font-size: 0.75rem;
        color: #10b981;
        font-weight: 600;
      }

      .live-dot {
        width: 8px;
        height: 8px;
        background-color: #10b981;
        border-radius: 50%;
        margin-right: 0.5rem;
        animation: pulse 2s infinite;
      }

      @keyframes pulse {
        0%,
        100% {
          opacity: 1;
          transform: scale(1);
        }
        50% {
          opacity: 0.7;
          transform: scale(1.1);
        }
      }

      .chart-container {
        background-color: #fff;
        padding: 1.5rem;
        border-radius: 1rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
      }

      .chart-container h3 {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 1rem;
      }

      .chart-wrapper {
        position: relative;
        height: 450px;
      }

      .risk-alert {
        background-color: #fef2f2;
        border-left: 4px solid #ef4444;
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
      }

      .risk-alert.warning {
        background-color: #fffbeb;
        border-left-color: #f59e0b;
      }

      .risk-alert.info {
        background-color: #eff6ff;
        border-left-color: #3b82f6;
      }

      .risk-alert h4 {
        font-weight: 600;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
      }

      .risk-alert h4 i {
        margin-right: 0.5rem;
      }

      .department-table {
        width: 100%;
        border-collapse: collapse;
        background-color: #fff;
      }

      .department-table thead {
        background-color: #f9fafb;
      }

      .department-table th,
      .department-table td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
      }

      .department-table th {
        font-weight: 600;
        color: #374151;
        font-size: 0.875rem;
      }

      .department-table tbody tr:hover {
        background-color: #f9fafb;
      }

      .badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
      }

      .badge.high {
        background-color: #fee2e2;
        color: #991b1b;
      }
      .badge.medium {
        background-color: #fef3c7;
        color: #92400e;
      }
      .badge.low {
        background-color: #d1fae5;
        color: #065f46;
      }

      .filter-section {
        background-color: #fff;
        padding: 1.5rem;
        border-radius: 1rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
      }

      .filter-section h3 {
        font-size: 1.125rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 1rem;
      }

      .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
      }

      .filter-item label {
        display: block;
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.5rem;
      }

      .filter-item select {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        font-size: 0.875rem;
      }

      .loading {
        text-align: center;
        padding: 2rem;
        color: #6b7280;
      }

      .loading i {
        font-size: 2rem;
        animation: spin 1s linear infinite;
      }

      @keyframes spin {
        0% {
          transform: rotate(0deg);
        }
        100% {
          transform: rotate(360deg);
        }
      }

      .grid-2 {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
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

        .grid-2 {
          grid-template-columns: 1fr;
        }

        .stats-grid {
          grid-template-columns: 1fr;
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
            <li class="nav-item">
              <a href="leave_status.php"
                ><i class="fas fa-calendar-alt"></i> Leave Status</a
              >
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
            <li class="nav-item active">
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
        <div class="header-banner">
          <h1>
            <i class="fas fa-chart-line"></i> DSS-Enhanced Analytics Dashboard
          </h1>
          <p>
            Cross-Departmental Comparisons, Risk Detection & Decision Support
            for 17 Municipal Departments
          </p>
          <div
            id="filterStatus"
            style="margin-top: 0.5rem; font-size: 0.875rem; opacity: 0.9"
          ></div>
        </div>

        <!-- Loading State -->
        <div id="loadingState" class="loading">
          <i class="fas fa-spinner"></i>
          <p>Loading real-time analytics data...</p>
        </div>

        <!-- Main Content (Hidden until loaded) -->
        <div id="mainContent" style="display: none">
          <!-- Key Metrics -->
          <div class="stats-grid">
            <div class="stat-card blue">
              <div class="live-indicator">
                <div class="live-dot"></div>
                LIVE
              </div>
              <div class="label">Total Employees</div>
              <div class="value" id="totalEmployees">0</div>
              <div class="sub-value">Across 17 departments</div>
            </div>

            <div class="stat-card green">
              <div class="live-indicator">
                <div class="live-dot"></div>
                LIVE
              </div>
              <div class="label">Active Today</div>
              <div class="value" id="activeToday">0</div>
              <div class="sub-value">
                <span id="attendanceRate">0</span>% Attendance Rate
              </div>
            </div>

            <div class="stat-card yellow">
              <div class="live-indicator">
                <div class="live-dot"></div>
                LIVE
              </div>
              <div class="label">Pending Leave Requests</div>
              <div class="value" id="pendingLeaves">0</div>
              <div class="sub-value">Requires HR approval</div>
            </div>

            <div class="stat-card red">
              <div class="live-indicator">
                <div class="live-dot"></div>
                LIVE
              </div>
              <div class="label">High-Risk Departments</div>
              <div class="value" id="highRiskDepts">0</div>
              <div class="sub-value">Based on analytics</div>
            </div>

            <div class="stat-card purple">
              <div class="live-indicator">
                <div class="live-dot"></div>
                LIVE
              </div>
              <div class="label">Avg. Leave Days/Employee</div>
              <div class="value" id="avgLeaveDays">0</div>
              <div class="sub-value" id="avgLeaveDaysPeriod">
                Selected period
              </div>
            </div>

            <div class="stat-card cyan">
              <div class="live-indicator">
                <div class="live-dot"></div>
                LIVE
              </div>
              <div class="label">Departments Analyzed</div>
              <div class="value" id="deptsAnalyzed">17</div>
              <div class="sub-value">Municipal offices</div>
            </div>
          </div>

          <!-- Risk Alerts -->
          <div id="riskAlertsContainer"></div>

          <!-- Filter Section -->
          <div class="filter-section">
            <h3><i class="fas fa-filter"></i> Filters & Analysis Options</h3>
            <div class="filter-grid">
              <div class="filter-item">
                <label for="viewMode">View By</label>
                <select id="viewMode" onchange="handleViewModeChange()" 
                        style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem;">
                  <option value="date" selected>Specific Date</option>
                  <option value="month">Whole Month</option>
                  <option value="year">Whole Year</option>
                </select>
              </div>
              <div class="filter-item" id="dateFilterContainer" style="display: block;">
                <label for="dateFilter">Select Date</label>
                <input type="date" id="dateFilter" onchange="refreshAnalytics()" 
                       style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem;" />
              </div>
              <div class="filter-item" id="monthFilterContainer" style="display: none;">
                <label for="monthFilter">Select Month</label>
                <input type="month" id="monthFilter" onchange="refreshAnalytics()" 
                       style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem;" />
              </div>
              <div class="filter-item" id="yearFilterContainer" style="display: none;">
                <label for="yearFilter">Select Year</label>
                <input type="number" id="yearFilter" min="2020" max="2030" onchange="refreshAnalytics()" 
                       style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem;" />
              </div>
              <div class="filter-item">
                <label for="departmentFilter">Department</label>
                <select id="departmentFilter" onchange="refreshAnalytics()">
                  <option value="all">All Departments</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Charts Grid -->
          <div class="grid-2">
            <!-- Department Comparison Chart -->
            <div class="chart-container">
              <h3>
                <i class="fas fa-building"></i> Department Employee Distribution
              </h3>
              <div class="chart-wrapper">
                <canvas id="deptDistributionChart"></canvas>
              </div>
            </div>

            <!-- Attendance Trend Chart -->
            <div class="chart-container">
              <h3 id="attendanceTrendTitle">
                <i class="fas fa-chart-area"></i> Attendance Trends
              </h3>
              <div class="chart-wrapper">
                <canvas id="attendanceTrendChart"></canvas>
              </div>
            </div>
          </div>

          <div class="grid-2">
            <!-- Leave Types Distribution -->
            <div class="chart-container">
              <h3>
                <i class="fas fa-calendar-check"></i> Leave Types Distribution
              </h3>
              <div class="chart-wrapper">
                <canvas id="leaveTypesChart"></canvas>
              </div>
            </div>

            <!-- Position Distribution -->
            <div class="chart-container">
              <h3>
                <i class="fas fa-user-tie"></i> Employee Position Distribution
              </h3>
              <div class="chart-wrapper">
                <canvas id="positionDistributionChart"></canvas>
              </div>
            </div>
          </div>

          <!-- Department Performance Table -->
          <div class="chart-container">
            <h3><i class="fas fa-table"></i> Department Performance Matrix</h3>
            <div style="overflow-x: auto">
              <table class="department-table">
                <thead>
                  <tr>
                    <th>Department</th>
                    <th>Employees</th>
                    <th>Attendance Rate</th>
                    <th>Active Today</th>
                    <th>Pending Leaves</th>
                    <th>Avg. Leave Days</th>
                    <th>Risk Level</th>
                  </tr>
                </thead>
                <tbody id="departmentTableBody">
                  <tr>
                    <td
                      colspan="7"
                      style="text-align: center; padding: 2rem; color: #6b7280"
                    >
                      Loading department data...
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Cross-Departmental Heatmap -->
          <div class="chart-container">
            <h3><i class="fas fa-fire"></i> Department Risk Heatmap</h3>
            <div class="chart-wrapper">
              <canvas id="riskHeatmapChart"></canvas>
            </div>
          </div>
        </div>
      </main>
    </div>

    <script>
      let analyticsData = null;
      let charts = {};

      // Initialize on page load
      document.addEventListener("DOMContentLoaded", function () {
        // Set date to today
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        
        document.getElementById("dateFilter").value = `${year}-${month}-${day}`;
        document.getElementById("monthFilter").value = `${year}-${month}`;
        document.getElementById("yearFilter").value = year;
        
        loadDepartmentList();
        loadAnalytics();
        // Refresh data every 30 seconds
        setInterval(loadAnalytics, 30000);
      });

      // Handle view mode change to show appropriate picker
      function handleViewModeChange() {
        const viewMode = document.getElementById("viewMode").value;
        const dateContainer = document.getElementById("dateFilterContainer");
        const monthContainer = document.getElementById("monthFilterContainer");
        const yearContainer = document.getElementById("yearFilterContainer");
        
        // Hide all
        dateContainer.style.display = "none";
        monthContainer.style.display = "none";
        yearContainer.style.display = "none";
        
        // Show the selected one
        if (viewMode === "date") {
          dateContainer.style.display = "block";
        } else if (viewMode === "month") {
          monthContainer.style.display = "block";
        } else if (viewMode === "year") {
          yearContainer.style.display = "block";
        }
        
        refreshAnalytics();
      }

      // Load department list for filter
      async function loadDepartmentList() {
        try {
          const response = await fetch(
            "../api/hr_analytics_dashboard.php?getDepartments=1"
          );
          const data = await response.json();

          if (data.success && data.all_departments) {
            const select = document.getElementById("departmentFilter");
            let options = '<option value="all">All Departments</option>';
            data.all_departments.forEach((dept) => {
              options += `<option value="${dept}">${dept}</option>`;
            });
            select.innerHTML = options;
          }
        } catch (error) {
          console.error("Error loading departments:", error);
        }
      }

      async function loadAnalytics() {
        try {
          // Get filter values
          const viewMode = document.getElementById("viewMode").value;
          const departmentFilter = document.getElementById("departmentFilter").value;
          
          let dateParam, monthParam, yearParam;
          
          if (viewMode === "date") {
            dateParam = document.getElementById("dateFilter").value;
          } else if (viewMode === "month") {
            const monthValue = document.getElementById("monthFilter").value;
            if (monthValue) {
              const [year, month] = monthValue.split('-');
              monthParam = month;
              yearParam = year;
            }
          } else if (viewMode === "year") {
            yearParam = document.getElementById("yearFilter").value;
          }

          // Build query string
          const params = new URLSearchParams({
            viewMode: viewMode,
            departmentFilter: departmentFilter,
          });
          
          if (dateParam) params.append("date", dateParam);
          if (monthParam) params.append("month", monthParam);
          if (yearParam) params.append("year", yearParam);

          const response = await fetch(
            "../api/hr_analytics_dashboard.php?" + params.toString()
          );
          const data = await response.json();

          if (data.success) {
            analyticsData = data;
            updateDashboard(data);
            document.getElementById("loadingState").style.display = "none";
            document.getElementById("mainContent").style.display = "block";
          } else {
            console.error("Error loading analytics:", data.error);
            showError(
              "Failed to load analytics data: " +
                (data.error || "Unknown error")
            );
          }
        } catch (error) {
          console.error("Error fetching analytics:", error);
          showError(
            "Failed to fetch analytics data. Please check your connection."
          );
        }
      }

      function updateDashboard(data) {
        // Update key metrics
        document.getElementById("totalEmployees").textContent =
          data.overview.total_employees;
        document.getElementById("activeToday").textContent =
          data.overview.active_today;
        document.getElementById("attendanceRate").textContent =
          data.overview.attendance_rate.toFixed(1);
        document.getElementById("pendingLeaves").textContent =
          data.overview.pending_leaves;
        document.getElementById("highRiskDepts").textContent =
          data.overview.high_risk_departments;
        document.getElementById("avgLeaveDays").textContent =
          data.overview.avg_leave_days_per_employee.toFixed(1);

        // Update period label
        const periodLabels = {
          today: "Today",
          week: "This week",
          month: "This month",
          quarter: "This quarter",
          year: "This year",
        };
        const currentPeriod = data.filters.timeRange || "month";
        document.getElementById("avgLeaveDaysPeriod").textContent =
          periodLabels[currentPeriod];
        
        // Update "Active Today" card label dynamically
        const activeTodayCard = document.querySelector('.stat-card.green .label');
        const activeTodaySubValue = document.querySelector('.stat-card.green .sub-value');
        
        if (currentPeriod === "today") {
          activeTodayCard.textContent = "Active Today";
          activeTodaySubValue.innerHTML = '<span id="attendanceRate">' + 
            data.overview.attendance_rate.toFixed(1) + '</span>% Attendance Rate';
        } else {
          const activeLabels = {
            week: "Active This Week",
            month: "Active This Month",
            quarter: "Active This Quarter",
            year: "Active This Year",
          };
          activeTodayCard.textContent = activeLabels[currentPeriod] || "Active in Period";
          activeTodaySubValue.innerHTML = '<span id="attendanceRate">' + 
            data.overview.attendance_rate.toFixed(1) + '</span>% Attendance Rate';
        }

        // Update attendance trend chart title
        const trendTitles = {
          today: "Attendance Trends (Today)",
          week: "Attendance Trends (This Week)",
          month: "Attendance Trends (This Month)",
          quarter: "Attendance Trends (This Quarter)",
          year: "Attendance Trends (This Year)",
        };
        document.getElementById("attendanceTrendTitle").innerHTML =
          '<i class="fas fa-chart-area"></i> ' + (trendTitles[currentPeriod] || "Attendance Trends");

        // Update filter status
        updateFilterStatus(data.filters);

        // Update department filter
        updateDepartmentFilter(data.departments);

        // Update risk alerts
        updateRiskAlerts(data.risk_alerts);

        // Update charts
        updateDepartmentDistributionChart(data.departments);
        updateAttendanceTrendChart(data.attendance_trend);
        updateLeaveTypesChart(data.leave_types);
        updatePositionDistributionChart(data.position_distribution);
        updateDepartmentTable(data.departments);
        updateRiskHeatmap(data.departments);
      }

      function updateFilterStatus(filters) {
        let statusText = '<i class="fas fa-filter"></i> Active Filters: ';
        
        if (filters.viewMode === "date" && filters.date) {
          const date = new Date(filters.date);
          const options = { year: 'numeric', month: 'long', day: 'numeric' };
          statusText += date.toLocaleDateString('en-US', options);
        } else if (filters.viewMode === "month" && filters.month && filters.year) {
          const monthNames = ["January", "February", "March", "April", "May", "June",
                             "July", "August", "September", "October", "November", "December"];
          statusText += monthNames[parseInt(filters.month) - 1] + " " + filters.year;
        } else if (filters.viewMode === "year" && filters.year) {
          statusText += "Year " + filters.year;
        }

        if (filters.departmentFilter !== "all") {
          statusText += " | Department: <strong>" + filters.departmentFilter + "</strong>";
        } else {
          statusText += " | All Departments";
        }

        document.getElementById("filterStatus").innerHTML = statusText;
      }

      function updateDepartmentFilter(departments) {
        const select = document.getElementById("departmentFilter");
        const currentValue = select.value;

        // Keep "All Departments" option and add department options
        let options = '<option value="all">All Departments</option>';

        // Get all departments from database
        if (analyticsData && analyticsData.filters.departmentFilter === "all") {
          departments.forEach((dept) => {
            options += `<option value="${dept.department}">${dept.department}</option>`;
          });
        } else {
          // When filtered, get all departments by making a quick call
          departments.forEach((dept) => {
            options += `<option value="${dept.department}">${dept.department}</option>`;
          });
        }

        select.innerHTML = options;
        select.value = currentValue;
      }

      function updateRiskAlerts(alerts) {
        const container = document.getElementById("riskAlertsContainer");

        if (alerts.length === 0) {
          container.innerHTML = `
                    <div class="risk-alert info">
                        <h4><i class="fas fa-check-circle"></i> All Clear</h4>
                        <p>No critical risk alerts detected across departments.</p>
                    </div>
                `;
          return;
        }

        let html = "";
        alerts.forEach((alert) => {
          const alertClass =
            alert.severity === "high"
              ? "risk-alert"
              : alert.severity === "medium"
              ? "risk-alert warning"
              : "risk-alert info";
          const icon =
            alert.severity === "high"
              ? "fa-exclamation-triangle"
              : alert.severity === "medium"
              ? "fa-exclamation-circle"
              : "fa-info-circle";

          html += `
                    <div class="${alertClass}">
                        <h4><i class="fas ${icon}"></i> ${alert.title}</h4>
                        <p>${alert.message}</p>
                    </div>
                `;
        });
        container.innerHTML = html;
      }

      function updateDepartmentDistributionChart(departments) {
        const ctx = document.getElementById("deptDistributionChart");

        if (charts.deptDistribution) {
          charts.deptDistribution.destroy();
        }

        const labels = departments.map((d) => d.department);
        const data = departments.map((d) => d.employee_count);

        charts.deptDistribution = new Chart(ctx, {
          type: "bar",
          data: {
            labels: labels,
            datasets: [
              {
                label: "Employee Count",
                data: data,
                backgroundColor: "rgba(59, 130, 246, 0.6)",
                borderColor: "rgba(59, 130, 246, 1)",
                borderWidth: 1,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: false,
              },
            },
            scales: {
              x: {
                ticks: {
                  autoSkip: false,
                  maxRotation: 45,
                  minRotation: 45,
                  font: {
                    size: 11,
                    weight: "bold",
                  },
                },
              },
              y: {
                beginAtZero: true,
                ticks: {
                  stepSize: 1,
                },
              },
            },
          },
        });
      }

      function updateAttendanceTrendChart(trendData) {
        const ctx = document.getElementById("attendanceTrendChart");

        if (charts.attendanceTrend) {
          charts.attendanceTrend.destroy();
        }

        const labels = trendData.map((d) => d.date);
        const presentData = trendData.map((d) => d.present);
        const lateData = trendData.map((d) => d.late);
        const absentData = trendData.map((d) => d.absent);

        charts.attendanceTrend = new Chart(ctx, {
          type: "line",
          data: {
            labels: labels,
            datasets: [
              {
                label: "Present",
                data: presentData,
                borderColor: "rgba(16, 185, 129, 1)",
                backgroundColor: "rgba(16, 185, 129, 0.1)",
                tension: 0.4,
              },
              {
                label: "Late",
                data: lateData,
                borderColor: "rgba(245, 158, 11, 1)",
                backgroundColor: "rgba(245, 158, 11, 0.1)",
                tension: 0.4,
              },
              {
                label: "Absent",
                data: absentData,
                borderColor: "rgba(239, 68, 68, 1)",
                backgroundColor: "rgba(239, 68, 68, 0.1)",
                tension: 0.4,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: "top",
              },
            },
            scales: {
              y: {
                beginAtZero: true,
              },
            },
          },
        });
      }

      function updateLeaveTypesChart(leaveTypes) {
        const ctx = document.getElementById("leaveTypesChart");

        if (charts.leaveTypes) {
          charts.leaveTypes.destroy();
        }

        const labels = leaveTypes.map((l) => l.leave_type);
        const data = leaveTypes.map((l) => l.count);

        const colors = [
          "rgba(59, 130, 246, 0.6)",
          "rgba(16, 185, 129, 0.6)",
          "rgba(245, 158, 11, 0.6)",
          "rgba(239, 68, 68, 0.6)",
          "rgba(139, 92, 246, 0.6)",
          "rgba(6, 182, 212, 0.6)",
        ];

        charts.leaveTypes = new Chart(ctx, {
          type: "doughnut",
          data: {
            labels: labels,
            datasets: [
              {
                data: data,
                backgroundColor: colors,
                borderWidth: 2,
                borderColor: "#fff",
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: "right",
              },
            },
          },
        });
      }

      function updatePositionDistributionChart(positions) {
        const ctx = document.getElementById("positionDistributionChart");

        if (charts.positionDistribution) {
          charts.positionDistribution.destroy();
        }

        const labels = positions.map((p) => p.position);
        const data = positions.map((p) => p.count);

        charts.positionDistribution = new Chart(ctx, {
          type: "pie",
          data: {
            labels: labels,
            datasets: [
              {
                data: data,
                backgroundColor: [
                  "rgba(59, 130, 246, 0.6)",
                  "rgba(147, 197, 253, 0.6)",
                  "rgba(34, 211, 238, 0.6)",
                  "rgba(96, 165, 250, 0.6)",
                ],
                borderWidth: 2,
                borderColor: "#fff",
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: "right",
              },
            },
          },
        });
      }

      function updateDepartmentTable(departments) {
        const tbody = document.getElementById("departmentTableBody");

        if (departments.length === 0) {
          tbody.innerHTML = `
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem; color: #6b7280;">
                            No department data available
                        </td>
                    </tr>
                `;
          return;
        }

        let html = "";
        departments.forEach((dept) => {
          const riskBadgeClass =
            dept.risk_level === "High"
              ? "high"
              : dept.risk_level === "Medium"
              ? "medium"
              : "low";

          html += `
                    <tr>
                        <td><strong>${dept.department}</strong></td>
                        <td>${dept.employee_count}</td>
                        <td>${dept.attendance_rate.toFixed(1)}%</td>
                        <td>${dept.active_today}</td>
                        <td>${dept.pending_leaves}</td>
                        <td>${dept.avg_leave_days.toFixed(1)}</td>
                        <td><span class="badge ${riskBadgeClass}">${
            dept.risk_level
          }</span></td>
                    </tr>
                `;
        });
        tbody.innerHTML = html;
      }

      function updateRiskHeatmap(departments) {
        const ctx = document.getElementById("riskHeatmapChart");

        if (charts.riskHeatmap) {
          charts.riskHeatmap.destroy();
        }

        const labels = departments.map((d) => d.department);
        
        // Attendance Risk: 100 - attendance_rate (higher = worse)
        // Only show if there's actual risk (attendance < 100%)
        const attendanceRisk = departments.map((d) => {
          const risk = 100 - d.attendance_rate;
          return risk > 0 ? risk : 0; // Don't show negative risk
        });
        
        // Leave Risk: Calculate based on pending leaves percentage and avg leave days
        // Formula: (pending_leaves / employee_count * 100) + (avg_leave_days * 10)
        // This gives a weighted score where both pending leaves and leave usage matter
        const leaveRisk = departments.map((d) => {
          const pendingRisk = d.employee_count > 0 ? (d.pending_leaves / d.employee_count) * 100 : 0;
          const avgLeaveRisk = d.avg_leave_days * 10; // Scale up avg leave days
          return Math.min(100, pendingRisk + avgLeaveRisk); // Cap at 100
        });

        charts.riskHeatmap = new Chart(ctx, {
          type: "bar",
          data: {
            labels: labels,
            datasets: [
              {
                label: "Attendance Risk (%)",
                data: attendanceRisk,
                backgroundColor: "rgba(239, 68, 68, 0.6)",
                borderColor: "rgba(239, 68, 68, 1)",
                borderWidth: 1,
              },
              {
                label: "Leave Risk (%)",
                data: leaveRisk,
                backgroundColor: "rgba(245, 158, 11, 0.6)",
                borderColor: "rgba(245, 158, 11, 1)",
                borderWidth: 1,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: "top",
              },
              tooltip: {
                callbacks: {
                  afterLabel: function(context) {
                    const index = context.dataIndex;
                    const dept = departments[index];
                    if (context.dataset.label === "Leave Risk (%)") {
                      return [
                        `Pending Leaves: ${dept.pending_leaves}`,
                        `Avg Leave Days: ${dept.avg_leave_days.toFixed(1)}`
                      ];
                    } else if (context.dataset.label === "Attendance Risk (%)") {
                      return `Attendance Rate: ${dept.attendance_rate.toFixed(1)}%`;
                    }
                  }
                }
              }
            },
            scales: {
              x: {
                ticks: {
                  autoSkip: false,
                  maxRotation: 45,
                  minRotation: 45,
                  font: {
                    size: 11,
                    weight: "bold",
                  },
                },
              },
              y: {
                beginAtZero: true,
                max: 100,
                title: {
                  display: true,
                  text: "Risk Level (%)",
                },
              },
            },
          },
        });
      }

      function refreshAnalytics() {
        // Show loading state with transition
        const mainContent = document.getElementById("mainContent");
        mainContent.style.opacity = "0.5";

        // Reload analytics with filters
        loadAnalytics().then(() => {
          mainContent.style.opacity = "1";
        });
      }

      function showError(message) {
        document.getElementById("loadingState").innerHTML = `
                <i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i>
                <p style="color: #ef4444;">${message}</p>
                <button onclick="loadAnalytics()" style="margin-top: 1rem; padding: 0.5rem 1rem; background-color: #3b82f6; color: white; border: none; border-radius: 0.5rem; cursor: pointer;">
                    Retry
                </button>
            `;
      }
    </script>
  </body>
</html>
