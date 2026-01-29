<?php
require_once __DIR__ . '/../auth_guard.php';
require_role(['hr', 'department_head', 'employee']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Attendance Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Poppins', sans-serif; 
            background: #f3f4f6; 
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h1 { font-size: 32px; margin-bottom: 8px; }
        .subtitle { font-size: 14px; opacity: 0.9; }
        .controls {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            flex-wrap: wrap;
        }
        label { font-weight: 600; color: #374151; }
        select {
            padding: 10px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }
        select:hover { border-color: #667eea; }
        select:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .card {
            background: #fff;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }
        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 16px;
        }
        .card-title {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .card-value {
            font-size: 36px;
            font-weight: 700;
            color: #1f2937;
        }
        .total { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; }
        .present { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; }
        .late { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #fff; }
        .absent { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: #fff; }
        .realtime {
            background: #fff;
            padding: 16px;
            border-radius: 12px;
            text-align: center;
            font-size: 13px;
            color: #6b7280;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .pulse {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            display: inline-block;
            margin-right: 6px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }
        .status-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .status-btn.active {
            box-shadow: 0 4px 16px rgba(0,0,0,0.3);
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1><i class="fas fa-chart-line"></i> Attendance Dashboard</h1>
                <div class="subtitle">Real-time attendance monitoring system</div>
            </div>
            <a href="../hr/dashboard.php" style="display:inline-flex; align-items:center; gap:0.5rem; padding:0.75rem 1.25rem; background:rgba(255,255,255,0.2); color:#fff; border-radius:0.5rem; text-decoration:none; font-weight:600; border:2px solid rgba(255,255,255,0.3); transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                <i class="fas fa-arrow-left" style="font-size:0.9rem;"></i>
                <span style="font-size:0.95rem;">Back to HR Dashboard</span>
            </a>
        </div>
    </div>

    <div class="controls">
        <label for="dept-filter"><i class="fas fa-filter"></i> Filter by Department:</label>
        <select id="dept-filter">
            <option value="">All Departments</option>
            <option value="Office of the Municipal Mayor">Office of the Municipal Mayor</option>
            <option value="Office of the Municipal Vice Mayor">Office of the Municipal Vice Mayor</option>
            <option value="Office of the Sangguiniang Bayan">Office of the Sangguiniang Bayan</option>
            <option value="Office of the Municipal Administrator">Office of the Municipal Administrator</option>
            <option value="Office of the Municipal Engineer">Office of the Municipal Engineer</option>
            <option value="Office of the MPDC">Office of the MPDC</option>
            <option value="Office of the Municipal Budget Officer">Office of the Municipal Budget Officer</option>
            <option value="Office of the Municipal Assessor">Office of the Municipal Assessor</option>
            <option value="Office of the Municipal Accountant">Office of the Municipal Accountant</option>
            <option value="Office of the Municipal Civil Registrar">Office of the Municipal Civil Registrar</option>
            <option value="Office of the Municipal Treasurer">Office of the Municipal Treasurer</option>
            <option value="Office of the Municipal Social Welfare and Development Officer">Office of the Municipal Social Welfare and Development Officer</option>
            <option value="Office of the Municipal Health Officer">Office of the Municipal Health Officer</option>
            <option value="Office of the Municipal Agriculturist">Office of the Municipal Agriculturist</option>
            <option value="Office of the MDRRMO">Office of the MDRRMO</option>
            <option value="Office of the Municipal Legal Officer">Office of the Municipal Legal Officer</option>
            <option value="Office of the Municipal General Services Officer">Office of the Municipal General Services Officer</option>
        </select>
        
        <label for="date-range-type" style="margin-left: 16px;"><i class="fas fa-calendar-alt"></i> View by:</label>
        <select id="date-range-type">
            <option value="day">Day</option>
            <option value="month">Month</option>
            <option value="year">Year</option>
            <option value="custom">Custom Range</option>
        </select>
    </div>

    <div class="grid">
        <div class="card">
            <div class="card-icon total"><i class="fas fa-users"></i></div>
            <div class="card-title">Total Employees</div>
            <div class="card-value" id="total">-</div>
        </div>
        <div class="card">
            <div class="card-icon" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); color: #fff;"><i class="fas fa-user-check"></i></div>
            <div class="card-title">Active Today</div>
            <div class="card-value" id="active">-</div>
        </div>
        <div class="card">
            <div class="card-icon present"><i class="fas fa-check-circle"></i></div>
            <div class="card-title">Present</div>
            <div class="card-value" id="present">-</div>
        </div>
        <div class="card">
            <div class="card-icon late"><i class="fas fa-clock"></i></div>
            <div class="card-title">Late</div>
            <div class="card-value" id="late">-</div>
        </div>
        <div class="card">
            <div class="card-icon absent"><i class="fas fa-times-circle"></i></div>
            <div class="card-title">Absent Today</div>
            <div class="card-value" id="absent">-</div>
        </div>
    </div>

    <div class="realtime">
        <span class="pulse"></span>Auto-refreshing every 5 seconds | Last update: <strong id="last-update">-</strong>
    </div>

    <!-- Status Filter Buttons -->
    <div style="margin-top: 20px; display: flex; gap: 12px; flex-wrap: wrap; justify-content: center;">
        <button class="status-btn active" data-status="all" style="padding: 12px 24px; background: #667eea; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-family: 'Poppins', sans-serif; font-size: 14px; transition: all 0.3s; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);">
            <i class="fas fa-list"></i> All Records
        </button>
        <button class="status-btn" data-status="Present" style="padding: 12px 24px; background: #10b981; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-family: 'Poppins', sans-serif; font-size: 14px; transition: all 0.3s;">
            <i class="fas fa-check-circle"></i> Present Only
        </button>
        <button class="status-btn" data-status="Late" style="padding: 12px 24px; background: #f59e0b; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-family: 'Poppins', sans-serif; font-size: 14px; transition: all 0.3s;">
            <i class="fas fa-clock"></i> Late Only
        </button>
        <button class="status-btn" data-status="Absent" style="padding: 12px 24px; background: #ef4444; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-family: 'Poppins', sans-serif; font-size: 14px; transition: all 0.3s;">
            <i class="fas fa-times-circle"></i> Absent Only
        </button>
        <button class="status-btn" data-status="Out" style="padding: 12px 24px; background: #14b8a6; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-family: 'Poppins', sans-serif; font-size: 14px; transition: all 0.3s;">
            <i class="fas fa-user-check"></i> Out
        </button>
        <button class="status-btn" data-status="Undertime" style="padding: 12px 24px; background: #fb923c; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-family: 'Poppins', sans-serif; font-size: 14px; transition: all 0.3s;">
            <i class="fas fa-hourglass-half"></i> Undertime
        </button>
        <button class="status-btn" data-status="Overtime" style="padding: 12px 24px; background: #3b82f6; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-family: 'Poppins', sans-serif; font-size: 14px; transition: all 0.3s;">
            <i class="fas fa-clock"></i> Overtime
        </button>
    </div>

    <!-- Attendance Records Section -->
    <div style="margin-top: 30px;">
        <div class="controls" style="margin-bottom: 16px;">
            <div id="date-inputs" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                <input type="date" id="date-filter" value="<?= date('Y-m-d') ?>" style="padding: 10px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 14px;">
                <input type="month" id="month-filter" value="<?= date('Y-m') ?>" style="padding: 10px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 14px; display: none;">
                <input type="number" id="year-filter" value="<?= date('Y') ?>" min="2020" max="2100" style="padding: 10px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 14px; display: none;">
                <div id="custom-range" style="display: none; gap: 8px; align-items: center;">
                    <input type="date" id="start-date" value="<?= date('Y-m-01') ?>" style="padding: 10px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 14px;">
                    <span style="font-weight: 600; color: #374151;">to</span>
                    <input type="date" id="end-date" value="<?= date('Y-m-d') ?>" style="padding: 10px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 14px;">
                </div>
            </div>
            <input type="text" id="search" placeholder="Search by name or ID" style="padding: 10px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 14px;">
            <button id="refresh-btn" style="padding: 10px 16px; background: #667eea; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-family: 'Poppins', sans-serif;">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
            <button id="export-csv" style="padding: 10px 16px; background: #10b981; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-family: 'Poppins', sans-serif;">
                <i class="fas fa-file-csv"></i> Export CSV
            </button>
            <button id="export-excel" style="padding: 10px 16px; background: #10b981; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-family: 'Poppins', sans-serif;">
                <i class="fas fa-file-excel"></i> Export Excel
            </button>
            <?php if (function_exists('has_role') && has_role('hr')): ?>
                <?php
                    $canXlsx = false;
                    $autoload = __DIR__ . '/../vendor/autoload.php';
                    if (file_exists($autoload)) {
                        require_once $autoload;
                        $canXlsx = class_exists('\\PhpOffice\\PhpSpreadsheet\\IOFactory');
                    }
                ?>
                <button id="import-excel" style="padding: 10px 16px; background: #22c55e; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-family: 'Poppins', sans-serif;">
                    <i class="fas fa-file-upload"></i> Import Excel
                </button>
                <input type="file" id="import-excel-input" accept="<?= $canXlsx ? '.xls,.xlsx,.csv' : '.csv' ?>" style="display: none;" />
            <?php endif; ?>
            <button id="expand-all-btn" style="padding: 10px 16px; background: #8b5cf6; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-family: 'Poppins', sans-serif; display: none;">
                <i class="fas fa-expand-alt"></i> Expand All
            </button>
        </div>

        <div style="background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 20px; margin: 0;"><i class="fas fa-table"></i> Attendance Records</h2>
                <div id="filter-info" style="font-size: 13px; opacity: 0.9;"></div>
            </div>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f9fafb;">
                            <th style="padding: 16px; text-align: left; font-weight: 700; color: #374151; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb;">Employee ID</th>
                            <th style="padding: 16px; text-align: left; font-weight: 700; color: #374151; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb;">Name</th>
                            <th style="padding: 16px; text-align: left; font-weight: 700; color: #374151; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb;">Department</th>
                            <th style="padding: 16px; text-align: left; font-weight: 700; color: #374151; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb;">Date</th>
                            <th style="padding: 16px; text-align: left; font-weight: 700; color: #374151; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb;">Time In</th>
                            <th style="padding: 16px; text-align: left; font-weight: 700; color: #374151; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb;">Time In Status</th>
                            <th style="padding: 16px; text-align: left; font-weight: 700; color: #374151; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb;">Time Out</th>
                            <th style="padding: 16px; text-align: left; font-weight: 700; color: #374151; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb;">Time Out Status</th>
                        </tr>
                    </thead>
                    <tbody id="records-tbody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if (function_exists('has_role') && has_role('hr')): ?>
    <script>
        window.CAN_XLSX = <?= (isset($canXlsx) && $canXlsx) ? 'true' : 'false' ?>;
    </script>
    <?php endif; ?>
    <script>
        let currentRecords = [];
        let statusFilter = 'all';
        let dateRangeType = 'day';
        let expandedEmployees = new Set(); // Track which employees are expanded

        // Update date input visibility based on selected range type
        function updateDateInputs(){
            const type = document.getElementById('date-range-type').value;
            dateRangeType = type;
            
            document.getElementById('date-filter').style.display = type === 'day' ? 'block' : 'none';
            document.getElementById('month-filter').style.display = type === 'month' ? 'block' : 'none';
            document.getElementById('year-filter').style.display = type === 'year' ? 'block' : 'none';
            document.getElementById('custom-range').style.display = type === 'custom' ? 'flex' : 'none';
            
            loadRecords();
        }
        
        // Update filter information display
        function updateFilterInfo(search, dept){
            const filterInfo = document.getElementById('filter-info');
            let info = [];
            
            if(search){
                info.push('<i class="fas fa-search"></i> Searching: "' + search + '"');
            }
            if(dept){
                info.push('<i class="fas fa-building"></i> ' + dept);
            }
            if(statusFilter !== 'all'){
                info.push('<i class="fas fa-filter"></i> Status: ' + statusFilter);
            }
            
            let dateInfo = '';
            if(dateRangeType === 'day'){
                dateInfo = document.getElementById('date-filter').value;
            } else if(dateRangeType === 'month'){
                dateInfo = document.getElementById('month-filter').value;
            } else if(dateRangeType === 'year'){
                dateInfo = 'Year ' + document.getElementById('year-filter').value;
            } else if(dateRangeType === 'custom'){
                dateInfo = document.getElementById('start-date').value + ' to ' + document.getElementById('end-date').value;
            }
            if(dateInfo) info.unshift('<i class="fas fa-calendar"></i> ' + dateInfo);
            
            filterInfo.innerHTML = info.join(' <span style="margin: 0 8px;">|</span> ');
        }

        async function fetchDashboard(){
            const dept = document.getElementById('dept-filter').value;
            const url = 'get_dashboard.php' + (dept ? '?department=' + encodeURIComponent(dept) : '');
            
            try {
                const res = await fetch(url);
                const data = await res.json();
                
                if(data.success){
                    document.getElementById('total').textContent = data.total_employees;
                    document.getElementById('active').textContent = data.active || (data.present + data.late);
                    document.getElementById('present').textContent = data.present;
                    document.getElementById('late').textContent = data.late;
                    document.getElementById('absent').textContent = data.absent;
                    document.getElementById('last-update').textContent = new Date().toLocaleTimeString();
                }
            } catch(err) {
                console.error('Dashboard fetch error:', err);
            }
        }

        async function loadRecords(){
            const dept = document.getElementById('dept-filter').value;
            const search = document.getElementById('search').value;
            
            // Update filter info display
            updateFilterInfo(search, dept);
            
            let url = 'get_attendance.php?';
            
            // Build URL based on date range type
            if(dateRangeType === 'day'){
                const date = document.getElementById('date-filter').value;
                url += 'date=' + encodeURIComponent(date);
            } else if(dateRangeType === 'month'){
                const month = document.getElementById('month-filter').value;
                url += 'month=' + encodeURIComponent(month);
            } else if(dateRangeType === 'year'){
                const year = document.getElementById('year-filter').value;
                url += 'year=' + encodeURIComponent(year);
            } else if(dateRangeType === 'custom'){
                const startDate = document.getElementById('start-date').value;
                const endDate = document.getElementById('end-date').value;
                url += 'start_date=' + encodeURIComponent(startDate) + '&end_date=' + encodeURIComponent(endDate);
            }
            
            if(dept) url += '&department=' + encodeURIComponent(dept);
            if(search) url += '&search=' + encodeURIComponent(search);
            if(statusFilter !== 'all') url += '&status=' + encodeURIComponent(statusFilter);

            try {
                const res = await fetch(url);
                const data = await res.json();
                
                if(data.success){
                    currentRecords = data.records;
                    renderRecords(currentRecords);
                } else {
                    document.getElementById('records-tbody').innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: #ef4444;"><i class="fas fa-exclamation-triangle"></i> Error: ' + (data.error || 'Failed to load') + '</td></tr>';
                }
            } catch(err) {
                console.error('Records fetch error:', err);
                document.getElementById('records-tbody').innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: #ef4444;"><i class="fas fa-exclamation-triangle"></i> Connection error. Please check if the database is running.</td></tr>';
            }
        }

        function renderRecords(records){
            const tbody = document.getElementById('records-tbody');
            const expandBtn = document.getElementById('expand-all-btn');
            
            if(records.length === 0){
                let msg = 'No attendance records found';
                if(statusFilter !== 'all') msg += ' for status: ' + statusFilter;
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: #6b7280;"><i class="fas fa-info-circle"></i> ' + msg + '</td></tr>';
                expandBtn.style.display = 'none';
                return;
            }
            
            tbody.innerHTML = '';
            
            // If viewing by month/year/custom range, show grouped by employee with summary
            if(dateRangeType !== 'day' && records.length > 0){
                expandBtn.style.display = 'inline-block';
                // Add info message for grouped view
                const infoRow = document.createElement('tr');
                infoRow.innerHTML = `
                    <td colspan="8" style="padding: 16px; background: #eff6ff; border-left: 4px solid #3b82f6; color: #1e40af; font-size: 13px;">
                        <i class="fas fa-info-circle"></i> <strong>Grouped View:</strong> Records are grouped by employee. Click on any employee row to expand and see their detailed daily attendance.
                    </td>
                `;
                tbody.appendChild(infoRow);
                
                const employeeMap = new Map();
                
                // Group records by employee
                records.forEach(rec => {
                    const empId = rec.employee_id;
                    if(!employeeMap.has(empId)){
                        employeeMap.set(empId, {
                            employee_id: empId,
                            name: rec.name,
                            department: rec.department,
                            records: [],
                            present: 0,
                            late: 0,
                            absent: 0,
                            undertime: 0,
                            overtime: 0
                        });
                    }
                    const emp = employeeMap.get(empId);
                    emp.records.push(rec);
                    
                    // Count statuses
                    const timeInStatus = rec.time_in_status || 'Absent';
                    if(timeInStatus === 'Present') emp.present++;
                    else if(timeInStatus === 'Late') emp.late++;
                    else if(timeInStatus === 'Absent') emp.absent++;
                    
                    if(rec.time_out_status === 'Undertime') emp.undertime++;
                    if(rec.time_out_status === 'Overtime') emp.overtime++;
                });
                
                // Render grouped records
                employeeMap.forEach(emp => {
                    // Employee summary row
                    const summaryTr = document.createElement('tr');
                    summaryTr.style.background = 'linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%)';
                    summaryTr.style.fontWeight = '700';
                    summaryTr.style.cursor = 'pointer';
                    summaryTr.onclick = function(){ 
                        const detailsRow = this.nextElementSibling;
                        const chevron = this.querySelector('.fa-chevron-down, .fa-chevron-right');
                        const empId = emp.employee_id;
                        
                        if(detailsRow.style.display === 'none'){
                            detailsRow.style.display = '';
                            chevron.classList.remove('fa-chevron-right');
                            chevron.classList.add('fa-chevron-down');
                            expandedEmployees.add(empId);
                        } else {
                            detailsRow.style.display = 'none';
                            chevron.classList.remove('fa-chevron-down');
                            chevron.classList.add('fa-chevron-right');
                            expandedEmployees.delete(empId);
                        }
                    };
                    // Check if this employee was previously expanded
                    const isExpanded = expandedEmployees.has(emp.employee_id);
                    const chevronClass = isExpanded ? 'fa-chevron-down' : 'fa-chevron-right';
                    
                    summaryTr.innerHTML = `
                        <td style="padding: 16px;"><i class="fas ${chevronClass}" style="margin-right: 8px; transition: transform 0.3s;"></i><strong>${emp.employee_id}</strong></td>
                        <td style="padding: 16px;">${emp.name}</td>
                        <td style="padding: 16px;">${emp.department}</td>
                        <td colspan="5" style="padding: 16px;">
                            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                                <span style="padding: 4px 10px; border-radius: 6px; font-size: 11px; background: #dcfce7; color: #16a34a;"><i class="fas fa-check-circle"></i> Present: ${emp.present}</span>
                                <span style="padding: 4px 10px; border-radius: 6px; font-size: 11px; background: #fef3c7; color: #d97706;"><i class="fas fa-clock"></i> Late: ${emp.late}</span>
                                <span style="padding: 4px 10px; border-radius: 6px; font-size: 11px; background: #fee2e2; color: #dc2626;"><i class="fas fa-times-circle"></i> Absent: ${emp.absent}</span>
                                <span style="padding: 4px 10px; border-radius: 6px; font-size: 11px; background: #fed7aa; color: #ea580c;"><i class="fas fa-hourglass-half"></i> Undertime: ${emp.undertime}</span>
                                <span style="padding: 4px 10px; border-radius: 6px; font-size: 11px; background: #dbeafe; color: #2563eb;"><i class="fas fa-clock"></i> Overtime: ${emp.overtime}</span>
                                <span style="padding: 4px 10px; border-radius: 6px; font-size: 11px; background: #e0e7ff; color: #4338ca;">Total Days: ${emp.records.length}</span>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(summaryTr);
                    
                    // Details row (preserve expanded state)
                    const detailsTr = document.createElement('tr');
                    detailsTr.style.display = isExpanded ? '' : 'none';
                    detailsTr.innerHTML = `
                        <td colspan="8" style="padding: 0;">
                            <table style="width: 100%; margin: 0;">
                                <tbody>
                                    ${emp.records.map(rec => {
                                        const timeIn = rec.time_in ? new Date(rec.time_in).toLocaleTimeString() : '-';
                                        const timeOut = rec.time_out ? new Date(rec.time_out).toLocaleTimeString() : '-';
                                        
                                        const timeInStatus = rec.time_in_status || 'Absent';
                                        let timeInBg = '#fee2e2';
                                        let timeInColor = '#dc2626';
                                        if(timeInStatus === 'Present') { timeInBg = '#dcfce7'; timeInColor = '#16a34a'; }
                                        else if(timeInStatus === 'Late') { timeInBg = '#fef3c7'; timeInColor = '#d97706'; }
                                        else if(timeInStatus === 'Undertime') { timeInBg = '#fde68a'; timeInColor = '#b45309'; }
                                        
                                        const timeOutStatus = rec.time_out_status || '-';
                                        let timeOutBg = '#f3f4f6';
                                        let timeOutColor = '#6b7280';
                                        if(timeOutStatus === 'On-time' || timeOutStatus === 'Out') { timeOutBg = '#d1fae5'; timeOutColor = '#059669'; }
                                        else if(timeOutStatus === 'Undertime') { timeOutBg = '#fed7aa'; timeOutColor = '#ea580c'; }
                                        else if(timeOutStatus === 'Overtime') { timeOutBg = '#dbeafe'; timeOutColor = '#2563eb'; }
                                        
                                        return `
                                            <tr style="background: #fafafa; border-bottom: 1px solid #e5e7eb;">
                                                <td style="padding: 12px 16px; padding-left: 48px; width: 12.5%;"></td>
                                                <td style="padding: 12px 16px; width: 12.5%;"></td>
                                                <td style="padding: 12px 16px; width: 12.5%;"></td>
                                                <td style="padding: 12px 16px; width: 12.5%;"><strong>${rec.date || ''}</strong></td>
                                                <td style="padding: 12px 16px; width: 12.5%;">${timeIn}</td>
                                                <td style="padding: 12px 16px; width: 12.5%;"><span style="padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; background: ${timeInBg}; color: ${timeInColor};">${timeInStatus}</span></td>
                                                <td style="padding: 12px 16px; width: 12.5%;">${timeOut}</td>
                                                <td style="padding: 12px 16px; width: 12.5%;"><span style="padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; background: ${timeOutBg}; color: ${timeOutColor};">${timeOutStatus}</span></td>
                                            </tr>
                                        `;
                                    }).join('')}
                                </tbody>
                            </table>
                        </td>
                    `;
                    tbody.appendChild(detailsTr);
                });
            } else {
                // Day view - show all records as before
                expandBtn.style.display = 'none';
                records.forEach(rec=>{
                    const tr = document.createElement('tr');
                    tr.style.borderBottom = '1px solid #e5e7eb';
                    tr.style.transition = 'background 0.2s';
                    tr.onmouseover = function(){ this.style.background = '#f9fafb'; };
                    tr.onmouseout = function(){ this.style.background = ''; };
                    
                    const timeIn = rec.time_in ? new Date(rec.time_in).toLocaleTimeString() : '-';
                    const timeOut = rec.time_out ? new Date(rec.time_out).toLocaleTimeString() : '-';
                    
                    // Time In Status
                    const timeInStatus = rec.time_in_status || 'Absent';
                    let timeInBg = '#fee2e2';
                    let timeInColor = '#dc2626';
                    if(timeInStatus === 'Present') { timeInBg = '#dcfce7'; timeInColor = '#16a34a'; }
                    else if(timeInStatus === 'Late') { timeInBg = '#fef3c7'; timeInColor = '#d97706'; }
                    else if(timeInStatus === 'Undertime') { timeInBg = '#fde68a'; timeInColor = '#b45309'; }
                    
                    // Time Out Status
                    const timeOutStatus = rec.time_out_status || '-';
                    let timeOutBg = '#f3f4f6';
                    let timeOutColor = '#6b7280';
                    if(timeOutStatus === 'On-time' || timeOutStatus === 'Out') { timeOutBg = '#d1fae5'; timeOutColor = '#059669'; }
                    else if(timeOutStatus === 'Undertime') { timeOutBg = '#fed7aa'; timeOutColor = '#ea580c'; }
                    else if(timeOutStatus === 'Overtime') { timeOutBg = '#dbeafe'; timeOutColor = '#2563eb'; }
                    
                    tr.innerHTML = `
                        <td style="padding: 16px;"><strong>${rec.employee_id || ''}</strong></td>
                        <td style="padding: 16px;">${rec.name || ''}</td>
                        <td style="padding: 16px;">${rec.department || ''}</td>
                        <td style="padding: 16px;">${rec.date || ''}</td>
                        <td style="padding: 16px;">${timeIn}</td>
                        <td style="padding: 16px;"><span style="padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; text-transform: uppercase; background: ${timeInBg}; color: ${timeInColor};">${timeInStatus}</span></td>
                        <td style="padding: 16px;">${timeOut}</td>
                        <td style="padding: 16px;"><span style="padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; text-transform: uppercase; background: ${timeOutBg}; color: ${timeOutColor};">${timeOutStatus}</span></td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        }

        function getDateRangeLabel(){
            if(dateRangeType === 'day'){
                return document.getElementById('date-filter').value;
            } else if(dateRangeType === 'month'){
                return document.getElementById('month-filter').value;
            } else if(dateRangeType === 'year'){
                return document.getElementById('year-filter').value;
            } else if(dateRangeType === 'custom'){
                return document.getElementById('start-date').value + '_to_' + document.getElementById('end-date').value;
            }
            return 'report';
        }

        function exportCSV(){
            if(currentRecords.length === 0){ alert('No data to export'); return; }
            let csv = 'Employee ID,Name,Department,Date,Time In,Time In Status,Time Out,Time Out Status\n';
            currentRecords.forEach(rec=>{
                csv += `"${rec.employee_id || ''}","${rec.name || ''}","${rec.department || ''}","${rec.date || ''}","${rec.time_in || ''}","${rec.time_in_status || 'Absent'}","${rec.time_out || ''}","${rec.time_out_status || ''}"\n`;
            });
            downloadFile(csv, 'attendance_' + getDateRangeLabel() + '.csv', 'text/csv');
        }

        function exportExcel(){
            if(currentRecords.length === 0){ alert('No data to export'); return; }
            let html = '<html><head><meta charset="utf-8"></head><body><table border="1">';
            html += '<tr><th>Employee ID</th><th>Name</th><th>Department</th><th>Date</th><th>Time In</th><th>Time In Status</th><th>Time Out</th><th>Time Out Status</th></tr>';
            currentRecords.forEach(rec=>{
                html += `<tr><td>${rec.employee_id || ''}</td><td>${rec.name || ''}</td><td>${rec.department || ''}</td><td>${rec.date || ''}</td><td>${rec.time_in || ''}</td><td>${rec.time_in_status || 'Absent'}</td><td>${rec.time_out || ''}</td><td>${rec.time_out_status || ''}</td></tr>`;
            });
            html += '</table></body></html>';
            downloadFile(html, 'attendance_' + getDateRangeLabel() + '.xls', 'application/vnd.ms-excel');
        }

        function downloadFile(content, filename, mimeType){
            const blob = new Blob([content], {type: mimeType});
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.click();
            URL.revokeObjectURL(url);
        }

        // Event listeners
        document.getElementById('date-range-type').addEventListener('change', updateDateInputs);
        document.getElementById('dept-filter').addEventListener('change', function(){
            fetchDashboard();
            loadRecords();
        });
        document.getElementById('date-filter').addEventListener('change', loadRecords);
        document.getElementById('month-filter').addEventListener('change', loadRecords);
        document.getElementById('year-filter').addEventListener('change', loadRecords);
        document.getElementById('start-date').addEventListener('change', loadRecords);
        document.getElementById('end-date').addEventListener('change', loadRecords);
        document.getElementById('search').addEventListener('input', loadRecords);
        document.getElementById('refresh-btn').addEventListener('click', function(){
            fetchDashboard();
            loadRecords();
        });
        document.getElementById('export-csv').addEventListener('click', exportCSV);
        document.getElementById('export-excel').addEventListener('click', exportExcel);
        
        // Import Excel button and input (only present for HR)
        const importBtn = document.getElementById('import-excel');
        const importInput = document.getElementById('import-excel-input');
        if (importBtn && importInput) {
            importBtn.addEventListener('click', function(){
                importInput.click();
            });
            importInput.addEventListener('change', async function(){
                if(!this.files || this.files.length === 0) return;
                const file = this.files[0];
                const extAllowed = (window.CAN_XLSX ? ['xls','xlsx','csv'] : ['csv']);
                const ext = file.name.split('.').pop().toLowerCase();
                if(!extAllowed.includes(ext)){
                    const msg = window.CAN_XLSX
                        ? 'Please select a valid Excel/CSV file (.xls, .xlsx, .csv).'
                        : 'XLS/XLSX import requires PhpSpreadsheet. Please upload a CSV file or ask to install PhpSpreadsheet.';
                    alert(msg);
                    this.value = '';
                    return;
                }
                importBtn.disabled = true;
                importBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
                try {
                    const fd = new FormData();
                    fd.append('file', file);
                    const res = await fetch('import_attendance.php', { method: 'POST', body: fd });
                    const data = await res.json();
                    if(data.success){
                        alert(data.message || 'Upload successful. File saved.');
                        loadRecords();
                    } else {
                        alert('Import failed: ' + (data.error || 'Unknown error'));
                    }
                } catch(err){
                    console.error('Import error:', err);
                    alert('Connection error while importing.');
                } finally {
                    importBtn.disabled = false;
                    importBtn.innerHTML = '<i class="fas fa-file-upload"></i> Import Excel';
                    this.value = '';
                }
            });
        }
        
        // Expand/Collapse all button
        let allExpanded = false;
        document.getElementById('expand-all-btn').addEventListener('click', function(){
            const tbody = document.getElementById('records-tbody');
            const summaryRows = tbody.querySelectorAll('tr[style*="cursor: pointer"]');
            
            allExpanded = !allExpanded;
            
            // Clear or populate the expanded employees set
            if(allExpanded){
                expandedEmployees.clear();
                summaryRows.forEach(row => {
                    const empId = row.querySelector('strong').textContent;
                    expandedEmployees.add(empId);
                    const detailsRow = row.nextElementSibling;
                    const chevron = row.querySelector('.fa-chevron-down, .fa-chevron-right');
                    if(detailsRow){
                        detailsRow.style.display = '';
                    }
                    if(chevron){
                        chevron.classList.remove('fa-chevron-right');
                        chevron.classList.add('fa-chevron-down');
                    }
                });
            } else {
                expandedEmployees.clear();
                summaryRows.forEach(row => {
                    const detailsRow = row.nextElementSibling;
                    const chevron = row.querySelector('.fa-chevron-down, .fa-chevron-right');
                    if(detailsRow){
                        detailsRow.style.display = 'none';
                    }
                    if(chevron){
                        chevron.classList.remove('fa-chevron-down');
                        chevron.classList.add('fa-chevron-right');
                    }
                });
            }
            
            this.innerHTML = allExpanded ? '<i class="fas fa-compress-alt"></i> Collapse All' : '<i class="fas fa-expand-alt"></i> Expand All';
        });

        // Status filter buttons
        document.querySelectorAll('.status-btn').forEach(btn => {
            btn.addEventListener('click', function(){
                // Remove active class from all buttons
                document.querySelectorAll('.status-btn').forEach(b => b.classList.remove('active'));
                // Add active class to clicked button
                this.classList.add('active');
                // Set filter and reload
                statusFilter = this.getAttribute('data-status');
                loadRecords();
            });
        });

        // Auto-refresh dashboard every 5 seconds
        setInterval(fetchDashboard, 5000);
        
        // Auto-refresh records every 10 seconds
        setInterval(loadRecords, 10000);
        
        // Initial load
        fetchDashboard();
        loadRecords();
    </script>
</body>
</html>