<?php
// employees.php - Dynamic employee list from database
require_once __DIR__ . '/../auth_guard.php';
require_role('hr');
require_once '../db.php';

$categories = ['Permanent', 'Casual', 'JO', 'OJT'];
$employees = [];
// Group employees by their employment category stored in the `position` column
foreach ($categories as $cat) {
  $stmt = $pdo->prepare("SELECT * FROM users WHERE position = ?");
  $stmt->execute([$cat]);
  $employees[$cat] = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Bayan ng Mabini | Employee System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <script src="https://cdn.tailwindcss.com"></script>
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
            <i class="fas fa-bell notification-icon"></i>
            <img src="../assets/logo.png" alt="Profile" class="profile-image" />
        </div>
    </header>
    <div class="container">
        <aside class="sidebar">
            <nav class="nav-menu">
                <ul>
                    <li class="nav-item">
                        <a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
                    </li>
                    <li class="nav-item active">
                        <a href="#"><i class="fas fa-users"></i> Employees</a>
                    </li>
                    <li class="nav-item">
                        <a href="leave_status.php"><i class="fas fa-calendar-alt"></i> Leave Status</a>
                    </li>
                    <li class="nav-item">
                        <a href="leave_request.php"><i class="fas fa-calendar-plus"></i> Leave Request</a>
                    </li>
                    <li class="nav-item">
                        <a href="manage_events.php"><i class="fas fa-calendar-plus"></i> Manage Events</a>
                    </li>
                </ul>
            </nav>
            <div class="sign-out">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
            </div>
        </aside>
        <main class="main-content">
            <div class="main-content-area">
                <section id="employees-content" class="content-section active">
                    <h2 class="text-2xl font-bold mb-4">Employees</h2>
                    <div class="employee-tabs">
                        <?php foreach ($categories as $i => $cat): ?>
                        <button class="employee-tab-btn<?= $i === 0 ? ' active' : '' ?>" data-category="<?= htmlspecialchars($cat) ?>">
                            <?= htmlspecialchars($cat) ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <div class="employee-grid">
                        <?php foreach ($categories as $cat): ?>
                            <?php foreach ($employees[$cat] as $emp): ?>
                                <div class="employee-card" data-category="<?= htmlspecialchars($cat) ?>" data-name="<?= htmlspecialchars($emp['firstname'] . ' ' . $emp['lastname']) ?>" data-position="<?= htmlspecialchars($emp['position']) ?>" data-department="<?= htmlspecialchars($emp['department']) ?>" data-leaves='{}'>
                                    <span class="active-status"></span>
                                    <img src="assets/profile-placeholder.png" alt="<?= htmlspecialchars($emp['firstname'] . ' ' . $emp['lastname']) ?>" class="profile-image" />
                                    <p class="employee-name"><?= htmlspecialchars($emp['firstname'] . ' ' . $emp['lastname']) ?></p>
                                    <p class="employee-category"><?= htmlspecialchars($cat) ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </main>
    </div>
    <script>
      document.addEventListener("DOMContentLoaded", () => {
        const employeeCards = document.querySelectorAll(".employee-card");
        const modalOverlay = document.createElement("div");
        modalOverlay.className = "modal-overlay";
        document.body.appendChild(modalOverlay);

        employeeCards.forEach((card) => {
          card.addEventListener("click", () => {
            const name = card.dataset.name;
            const position = card.dataset.position;
            const department = card.dataset.department;
            const leaves = JSON.parse(card.dataset.leaves);

            let leaveListHtml = "";
            for (const type in leaves) {
              leaveListHtml += `<li><span>${type}</span><span class="credit-count">${leaves[type]}</span></li>`;
            }

            const modalContent = `
                        <div class="modal-content">
                            <button class="modal-close-btn">&times;</button>
                            <div class="modal-profile-header">
                                <img src="assets/profile-placeholder.png" alt="${name}">
                                <h4>${name}</h4>
                                <p class="employee-details">${position} - ${department}</p>
                            </div>
                            <div class="modal-leave-credits">
                                <h5>Leave Credits</h5>
                                <ul>${leaveListHtml}</ul>
                            </div>
                        </div>
                    `;

            modalOverlay.innerHTML = modalContent;
            modalOverlay.classList.add("show");

            const closeButton = modalOverlay.querySelector(".modal-close-btn");
            closeButton.addEventListener("click", () => {
              modalOverlay.classList.remove("show");
              setTimeout(() => (modalOverlay.innerHTML = ""), 300);
            });

            modalOverlay.addEventListener("click", (e) => {
              if (e.target === modalOverlay) {
                modalOverlay.classList.remove("show");
                setTimeout(() => (modalOverlay.innerHTML = ""), 300);
              }
            });
          });
        });

        // Tab functionality
        const tabButtons = document.querySelectorAll(".employee-tab-btn");
        const employeeGrid = document.querySelector(".employee-grid");

        tabButtons.forEach((button) => {
          button.addEventListener("click", () => {
            const category = button.dataset.category;

            // Remove active class from all buttons
            tabButtons.forEach((btn) => btn.classList.remove("active"));

            // Add active class to the clicked button
            button.classList.add("active");

            // Filter employee cards
            const allCards = employeeGrid.querySelectorAll(".employee-card");
            allCards.forEach((card) => {
              if (card.dataset.category === category) {
                card.style.display = "block";
              } else {
                card.style.display = "none";
              }
            });
          });
        });
      });
    </script>
</body>
</html>
