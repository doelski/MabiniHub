<?php
require_once __DIR__ . '/../auth_guard.php';
require_role('department_head');
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

      /* Specific Leave Management Styles */
      .tab-buttons {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
        flex-wrap: wrap; /* Allows wrapping on smaller screens */
      }

      .tab-button {
        padding: 0.75rem 2rem;
        border-radius: 0.5rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        white-space: nowrap; /* Prevents text from wrapping inside the button */
      }

      .tab-button.active {
        background-color: #3b82f6;
        color: #ffffff;
        box-shadow: 0 4px 6px rgba(59, 130, 246, 0.2);
      }

      .tab-button:not(.active) {
        background-color: #dbeafe;
        color: #60a5fa;
      }

      .tab-content {
        display: none; /* Hide all content by default */
      }

      .tab-content.active {
        display: block; /* Show active content */
      }

      .table-container {
        background-color: #ffffff;
        padding: 1.5rem;
        border-radius: 1rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        overflow-x: auto; /* Allows horizontal scrolling for table on small screens */
      }

      .table-header-controls {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
      }

      .table-header-controls .filter-icon,
      .table-header-controls .export-button {
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
      }

      .table-header-controls .filter-icon {
        color: #6b7280;
        border: 1px solid #d1d5db;
        background-color: #fff;
      }

      .table-header-controls .export-button {
        background-color: #22c55e;
        color: #ffffff;
        border: none;
      }

      .table-actions {
        position: relative;
      }

      .table-actions .actions-button {
        background-color: #3b82f6;
        color: #ffffff;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        border: none;
        font-size: 0.85rem;
        font-weight: 500;
        cursor: pointer;
      }

      .table-actions .actions-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        background-color: #ffffff;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        border-radius: 0.5rem;
        padding: 0.5rem;
        z-index: 10;
        display: none;
        width: 150px;
      }

      .table-actions.open .actions-dropdown {
        display: block;
      }

      .table-actions .dropdown-item {
        display: block;
        padding: 0.5rem 1rem;
        color: #4b5563;
        text-decoration: none;
        transition: background-color 0.2s;
        border-radius: 0.25rem;
      }

      .table-actions .dropdown-item:hover {
        background-color: #f3f4f6;
      }

      /* Updated styling for the Recall button */
      .recall-button {
        background-color: #3b82f6;
        color: #ffffff;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        border: none;
        font-size: 0.85rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
      }

      .recall-button:hover {
        background-color: #2563eb;
        box-shadow: 0 4px 6px rgba(59, 130, 246, 0.2);
      }

      .view-link {
        color: #3b82f6;
        font-weight: 600;
        text-decoration: underline;
      }

      /* Leave Recall Modal Specific Styles */
      .leave-recall-modal .modal-content {
        max-width: 600px;
        text-align: left;
      }

      .leave-recall-modal h3 {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 0.5rem;
      }

      .leave-recall-modal p {
        font-size: 0.9rem;
        color: #6b7280;
        margin-bottom: 1.5rem;
      }

      .leave-recall-modal .form-group {
        margin-bottom: 1rem;
      }

      .leave-recall-modal label {
        display: block;
        font-size: 0.85rem;
        font-weight: 500;
        color: #4b5563;
        margin-bottom: 0.25rem;
      }

      .leave-recall-modal input,
      .leave-recall-modal textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        font-size: 0.95rem;
      }

      .leave-recall-modal .form-row {
        display: flex;
        gap: 1rem;
      }

      .leave-recall-modal .form-buttons {
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
        margin-top: 2rem;
      }

      .leave-recall-modal .form-buttons button {
        padding: 0.75rem 2rem;
        border-radius: 0.5rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        color: #ffffff;
        border: none;
      }

      .leave-recall-modal .form-buttons .initiate-button {
        background-color: #10b981;
      }

      .leave-recall-modal .form-buttons .cancel-button {
        background-color: #ef4444;
      }

      .leave-recall-modal
        input[type="date"]::-webkit-calendar-picker-indicator {
        cursor: pointer;
      }

      .status-badge.approve {
        background-color: #d1fae5;
        color: #065f46;
      }
      .status-badge.decline {
        background-color: #fee2e2;
        color: #991b1b;
      }

      /* Decline Reason Modal Styles */
      #decline-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
        z-index: 1002;
      }

      #decline-modal.show {
        opacity: 1;
        visibility: visible;
      }

      .decline-modal-content {
        background-color: #fff;
        padding: 2rem;
        border-radius: 1rem;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
        width: 90%;
        max-width: 500px;
        position: relative;
        transform: scale(0.9);
        transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      }

      #decline-modal.show .decline-modal-content {
        transform: scale(1);
      }

      .decline-modal-close-btn {
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

      .decline-modal-close-btn:hover {
        color: #ef4444;
      }

      .decline-reason-input {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        font-size: 0.95rem;
        margin-bottom: 1rem;
      }

      .decline-modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
      }

      .decline-modal-actions button {
        padding: 0.75rem 2rem;
        border-radius: 0.5rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        color: #ffffff;
        border: none;
      }

      .decline-modal-actions .submit-decline-btn {
        background-color: #ef4444;
      }

      .decline-modal-actions .cancel-decline-btn {
        background-color: #6b7280;
      }

      @media (max-width: 768px) {
        .decline-modal-content {
          padding: 1.5rem;
        }

        .decline-reason-input {
          font-size: 0.9rem;
        }

        .decline-modal-actions button {
          padding: 0.5rem 1rem;
          font-size: 0.85rem;
        }
      }

      /* Enhanced checkbox visibility in dept head modal */
      #dept7b-modal input[type="checkbox"] {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        width: 18px;
        height: 18px;
        border: 2px solid #000;
        display: inline-block;
        position: relative;
        vertical-align: middle;
        background: transparent;
        margin-top: 0.1rem;
        border-radius: 3px;
        cursor: pointer;
      }

      #dept7b-modal input[type="checkbox"]:checked::after {
        content: "";
        position: absolute;
        left: 5px;
        top: 1px;
        width: 6px;
        height: 11px;
        border: solid #000;
        border-width: 0 3px 3px 0;
        transform: rotate(45deg);
      }

      /* Make sure modal textarea wraps properly */
      #dept_disapproval_reason_modal {
        font-family: inherit;
        line-height: 1.5;
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
              <a href="leave-status.php"
                ><i class="fas fa-calendar-alt"></i> Leave Status</a
              >
            </li>
            <li class="nav-item">
              <a href="task-status.php"
                ><i class="fas fa-tasks"></i> Task Status</a
              >
            </li>
            <li class="nav-item active">
              <a href="leave-request.php"
                ><i class="fas fa-calendar-plus"></i> Leave Request</a
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
          <section id="leave-management-content" class="content-section active">
            <h2 class="text-2xl font-bold mb-6">Leave Management</h2>

            <div class="tab-buttons">
              <button class="tab-button active" data-tab="leave-request">
                Leave Request
              </button>
              <button class="tab-button" data-tab="leave-history">
                Leave History
              </button>
            </div>

            <div id="leave-request" class="tab-content active">
              <div class="table-container">
                <div class="table-header-controls">
                  <a
                    href="apply_leave.php"
                    class="export-button"
                    style="
                      background-color: #3b82f6;
                      color: #fff;
                      margin-left: 0.5rem;
                      display: inline-flex;
                      align-items: center;
                      gap: 0.5rem;
                      padding: 0.5rem 0.75rem;
                      border-radius: 0.375rem;
                      text-decoration: none;
                    "
                  >
                    <i class="fas fa-plus"></i> Apply Leave
                  </a>
                </div>
                <table class="min-w-full border border-gray-300">
                  <thead>
                    <tr>
                      <th class="border border-gray-300 px-4 py-2 text-left">
                        Name(s)
                      </th>
                      <th class="border border-gray-300 px-4 py-2 text-left">
                        Duration(s)
                      </th>
                      <th class="border border-gray-300 px-4 py-2 text-left">
                        Remaining Credits
                      </th>
                      <th class="border border-gray-300 px-4 py-2 text-left">
                        Dates
                      </th>
                      <th class="border border-gray-300 px-4 py-2 text-center">
                        Form
                      </th>
                      <th class="border border-gray-300 px-4 py-2 text-center">
                        Actions
                      </th>
                    </tr>
                  </thead>
                  <tbody id="leave-request">
                    <!-- Dynamic leave request rows will be rendered here by JavaScript -->
                  </tbody>
                </table>
              </div>
            </div>

            <div id="leave-recall" class="tab-content">
              <div class="table-container">
                <div class="table-header-controls">
                  <i class="fas fa-filter filter-icon"></i>
                  <button class="export-button">
                    Export &nbsp;<i class="fas fa-download"></i>
                  </button>
                </div>
                <table class="min-w-full border border-gray-300">
                  <thead>
                    <tr>
                      <th class="border border-gray-300 px-4 py-2 text-left">
                        Name(s)
                      </th>
                      <th class="border border-gray-300 px-4 py-2 text-left">
                        Duration(s)
                      </th>
                      <th class="border border-gray-300 px-4 py-2 text-left">
                        Dates
                      </th>
                      <th class="border border-gray-300 px-4 py-2 text-center">
                        Form
                      </th>
                      <th class="border border-gray-300 px-4 py-2 text-center">
                        Action
                      </th>
                    </tr>
                  </thead>
                  <tbody id="leave-recall">
                    <tr>
                      <td class="border border-gray-300 px-4 py-2">
                        Juan Dela Cruz
                      </td>
                      <td class="border border-gray-300 px-4 py-2">3 days</td>
                      <td class="border border-gray-300 px-4 py-2">
                        Oct 12-14, 2025
                      </td>
                      <td class="border border-gray-300 px-4 py-2 text-center">
                        <button class="view-form-btn">View Form</button>
                      </td>
                      <td class="border border-gray-300 px-4 py-2 text-center">
                        <button class="recall-btn">Recall</button>
                      </td>
                    </tr>
                    <!-- More dynamic rows can be rendered here by JavaScript -->
                  </tbody>
                </table>
              </div>
            </div>

            <div id="leave-history" class="tab-content">
              <div class="table-container">
                <table class="min-w-full border border-gray-300">
                  <thead>
                    <tr>
                      <th class="border border-gray-300 px-4 py-2 text-left">
                        Name(s)
                      </th>
                      <th class="border border-gray-300 px-4 py-2 text-left">
                        Duration(s)
                      </th>
                      <th class="border border-gray-300 px-4 py-2 text-left">
                        Dates
                      </th>
                      <th class="border border-gray-300 px-4 py-2 text-center">
                        Form
                      </th>
                      <th class="border border-gray-300 px-4 py-2 text-center">
                        Status
                      </th>
                    </tr>
                  </thead>
                  <tbody id="leave-history">
                    <!-- Dynamic leave history rows will be rendered here by JavaScript -->
                  </tbody>
                </table>
              </div>
            </div>
          </section>
        </div>
      </main>
    </div>

    <!-- Decline Reason Modal -->
    <div id="decline-modal" class="modal-overlay">
      <div class="modal-content decline-modal-content">
        <button class="modal-close-btn" id="decline-cancel-btn">&times;</button>
        <h3 class="text-center font-bold">Decline Leave Request</h3>
        <p class="text-center text-sm text-gray-500 mb-6">
          Please provide a reason for declining this leave request.
        </p>
        <form id="decline-form" class="space-y-4">
          <div class="form-group">
            <label for="decline-reason-input">Reason</label>
            <textarea
              id="decline-reason-input"
              name="reason"
              rows="3"
              required
              class="decline-reason-input"
            ></textarea>
          </div>
          <div class="form-buttons decline-modal-actions">
            <button type="submit" class="initiate-button submit-decline-btn">
              Submit
            </button>
            <button
              type="button"
              class="cancel-button cancel-decline-btn"
              id="decline-cancel-btn"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>

    <script>
      // Global toggle: show archived items in Leave History (default hidden)
      window.__showArchivedHistory = false;

      document.addEventListener("DOMContentLoaded", () => {
        // Leave details modal removed

        const tabs = document.querySelectorAll(".tab-button");
        const contents = document.querySelectorAll(".tab-content");

        // Function to handle tab switching
        function switchTab(tabName) {
          contents.forEach((content) => {
            if (content.id === tabName) {
              content.classList.add("active");
            } else {
              content.classList.remove("active");
            }
          });

          tabs.forEach((tab) => {
            if (tab.dataset.tab === tabName) {
              tab.classList.add("active");
            } else {
              tab.classList.remove("active");
            }
          });
        }

        tabs.forEach((tab) => {
          tab.addEventListener("click", () => {
            switchTab(tab.dataset.tab);
          });
        });

        // Inject a prominent "Show archived" toggle into Leave History controls
        try {
          const histContainer = document.querySelector(
            "#leave-history .table-container"
          );
          if (
            histContainer &&
            !document.getElementById("history-archive-toggle")
          ) {
            histContainer.insertAdjacentHTML(
              "afterbegin",
              `
              <div id="history-archive-toggle" class="mb-3">
                <div class="flex items-center gap-3 px-3 py-2 rounded-lg border border-amber-300 bg-amber-50 text-amber-800 shadow-sm">
                  <i class="fas fa-archive"></i>
                  <label class="inline-flex items-center gap-2 font-semibold" title="Show archived leave requests in history">
                    <input type="checkbox" id="cbShowArchivedHistory" class="w-4 h-4 accent-amber-600">
                    <span>Show archived</span>
                  </label>
                  <span class="text-xs text-amber-700">(Archived items are hidden by default across the system)</span>
                </div>
              </div>
            `
            );
            const cb = document.getElementById("cbShowArchivedHistory");
            if (cb) {
              cb.addEventListener("change", function () {
                window.__showArchivedHistory = !!this.checked;
                fetchLeaveRequests();
              });
            }
          }
        } catch (e) {
          /* ignore UI injection errors */
        }

        // Dropdown functionality for "Actions" button in Leave History
        document.querySelectorAll(".table-actions").forEach((actionDiv) => {
          const button = actionDiv.querySelector(".actions-button");
          button.addEventListener("click", (e) => {
            e.stopPropagation(); // Prevents click from bubbling up and closing other dropdowns
            // Close all other open dropdowns first
            document
              .querySelectorAll(".table-actions.open")
              .forEach((openDiv) => {
                if (openDiv !== actionDiv) {
                  openDiv.classList.remove("open");
                }
              });
            actionDiv.classList.toggle("open");
          });
        });

        // Close dropdowns when clicking outside
        window.addEventListener("click", (e) => {
          document
            .querySelectorAll(".table-actions.open")
            .forEach((openDiv) => {
              if (!openDiv.contains(e.target)) {
                openDiv.classList.remove("open");
              }
            });
        });

        // Modal functionality for Leave Recall (disabled if modal removed)
        const recallModal = document.getElementById("leave-recall-modal");
        if (recallModal) {
          window.showModal = function () {
            recallModal.classList.add("show");
          };
          window.closeModal = function () {
            recallModal.classList.remove("show");
          };
        }
        // Use event delegation for recall buttons
        document
          .querySelector("#leave-recall tbody")
          .addEventListener("click", function (e) {
            if (e.target && e.target.classList.contains("recall-btn")) {
              // Get the row and request data from the button
              const row = e.target.closest("tr");
              // Find the request id from the view button or data attribute
              const viewBtn = row.querySelector(".view-button, .view-form-btn");
              let id = null;
              if (viewBtn && viewBtn.getAttribute("onclick")) {
                const match = viewBtn
                  .getAttribute("onclick")
                  .match(/'([0-9]+)'/);
                if (match) id = match[1];
              }
              // Find the request object from the global data (store it)
              if (id && window._leaveRequests) {
                const request = window._leaveRequests.find((r) => r.id == id);
                if (request) openRecallModal(request);
              }
            }
          });

        // Decline modal logic
        const declineModal = document.getElementById("decline-modal");
        const declineForm = document.getElementById("decline-form");
        const declineCancelBtns = document.querySelectorAll(
          "#decline-cancel-btn"
        );
        declineCancelBtns.forEach((btn) => {
          btn.onclick = function () {
            declineModal.classList.remove("show");
            window._declineRequestId = null;
          };
        });
        if (declineForm) {
          declineForm.onsubmit = function (e) {
            e.preventDefault();
            const reason = document
              .getElementById("decline-reason-input")
              .value.trim();
            const id = window._declineRequestId;
            if (!reason || !id) return;
            // Update backend with decline and reason
            fetch(`../api/update_leave_status.php`, {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ id, status: "declined", reason }),
            })
              .then((response) => response.json())
              .then((data) => {
                if (data.success) {
                  // Send notification to employee
                  const request = window._leaveRequests.find((r) => r.id == id);
                  if (request && request.employee_email) {
                    fetch("../api/add_notification.php", {
                      method: "POST",
                      headers: { "Content-Type": "application/json" },
                      body: JSON.stringify({
                        recipient_email: request.employee_email,
                        message: `Your leave request has been declined. Reason: ${reason}`,
                        type: "declined",
                      }),
                    });
                  }
                  alert("Leave request declined successfully!");
                  declineModal.classList.remove("show");
                  window._declineRequestId = null;
                  fetchLeaveRequests();
                } else {
                  alert("Failed to decline leave request.");
                }
              })
              .catch((error) => {
                console.error("Error declining leave request:", error);
                alert("An error occurred while declining the leave request.");
              });
          };
        }
      });

      async function fetchLeaveRequests() {
        try {
          const url = window.__showArchivedHistory
            ? "../api/get_leave_requests.php?include_archived=1"
            : "../api/get_leave_requests.php";
          const [resReq, resUser] = await Promise.all([
            fetch(url),
            fetch("../api/current_user.php"),
          ]);
          const reqJson = await resReq.json();
          const userJson = await resUser.json();

          if (!reqJson.success) return;

          const currentEmail =
            userJson && userJson.logged_in
              ? (userJson.email || "").toString().toLowerCase()
              : null;

          let requests = Array.isArray(reqJson.data) ? reqJson.data : [];
          if (currentEmail) {
            requests = requests.filter(
              (r) =>
                (r.dept_head_email || "").toString().toLowerCase() ===
                currentEmail
            );
          }

          window._leaveRequests = requests; // Store globally for recall modal

          // Leave Request (pending only)
          const tbodyRequest = document.querySelector("#leave-request tbody");
          tbodyRequest.innerHTML = "";
          // Leave History (approved/declined/recall)
          const tbodyHistory = document.querySelector("#leave-history tbody");
          tbodyHistory.innerHTML = "";
          // Leave Recall (approved/declined only)
          const tbodyRecall = document.querySelector("#leave-recall tbody");
          tbodyRecall.innerHTML = "";

          requests.forEach((request) => {
            const name =
              (request.firstname ? request.firstname : "") +
              (request.lastname ? " " + request.lastname : "");
            // Leave Request Table (pending only)
            if (request.status === "pending") {
              const row = document.createElement("tr");
              row.innerHTML = `

                    <td class="border border-gray-300 px-4 py-2 text-left">${name}</td>
                    <td class="border border-gray-300 px-4 py-2 text-left">${
                      request.leave_type || ""
                    }</td>
                    <td class="border border-gray-300 px-4 py-2 text-left remaining-days" data-email="${
                      request.employee_email || ""
                    }" data-leave-type="${(request.leave_type || "").replace(
                /"/g,
                "&quot;"
              )}"><span class="text-gray-400 text-xs">Loading…</span></td>
                    <td class="border border-gray-300 px-4 py-2 text-left">${
                      request.dates || ""
                    }</td>
                    <td class="border border-gray-300 px-4 py-2 text-center">
                      <button class="bg-blue-500 text-white px-2 py-1 rounded view-button" onclick="viewForm('${
                        request.id
                      }')">View</button>
                      <button class="bg-indigo-600 text-white px-2 py-1 rounded ml-2" onclick="openDept7BModal('${
                        request.id
                      }')">Edit </button>
                    </td>
                    <td class="border border-gray-300 px-4 py-2 text-center">
                      <select class="action-dropdown px-2 py-1 rounded border border-gray-300" data-id="${
                        request.id
                      }" onchange="handleAction(this)">
                        <option value="">Select Action</option>
                        <option value="approved">Approve</option>
                        <option value="declined">Decline</option>
                      </select>
                    </td>
                  `;
              tbodyRequest.appendChild(row);
            }

            // Leave History Table (approved/declined/recall)
            if (["approved", "declined", "recall"].includes(request.status)) {
              const archived = Number(request.is_archived || 0) === 1;
              if (!window.__showArchivedHistory && archived) {
                // Hide archived items by default
              } else {
                const historyRow = document.createElement("tr");
                if (archived) historyRow.classList.add("opacity-70");
                const statusLabel =
                  request.status.charAt(0).toUpperCase() +
                  request.status.slice(1);
                const archiveBtns = archived
                  ? `<span class=\"text-xs px-2 py-1 rounded bg-gray-200 text-gray-700 mr-2\">Archived</span>
                     <button class=\"bg-green-600 hover:bg-green-700 text-white px-2 py-1 rounded\" onclick=\"restoreLeave(${request.id})\">Restore</button>`
                  : `<button class=\"bg-amber-500 hover:bg-amber-600 text-white px-2 py-1 rounded\" onclick=\"archiveLeave(${request.id})\">Archive</button>`;
                historyRow.innerHTML = `
                      <td class="border border-gray-300 px-4 py-2 text-left">${name}</td>
                      <td class="border border-gray-300 px-4 py-2 text-left">${
                        request.leave_type || ""
                      }</td>
                      <td class="border border-gray-300 px-4 py-2 text-left">${
                        request.dates || ""
                      }</td>
                      <td class="border border-gray-300 px-4 py-2 text-center">
                        <button class="bg-blue-500 text-white px-2 py-1 rounded view-button mr-2" onclick="viewForm('${
                          request.id
                        }')">View</button>
                        ${archiveBtns}
                      </td>
                      <td class="border border-gray-300 px-4 py-2 text-center">${statusLabel}</td>
                    `;
                tbodyHistory.appendChild(historyRow);
              }
            }

            // Only add to recall table if approved (not declined)
            if (request.status === "approved") {
              const archived = Number(request.is_archived || 0) === 1;
              if (!window.__showArchivedHistory && archived) {
                // Hide archived items by default in recall tab too
              } else {
                const recallRow = document.createElement("tr");
                recallRow.innerHTML = `
                    <td class="border border-gray-300 px-4 py-2 text-left">${name}</td>
                    <td class="border border-gray-300 px-4 py-2 text-left">${
                      request.leave_type || ""
                    }</td>
                    <td class="border border-gray-300 px-4 py-2 text-left">${
                      request.dates || ""
                    }</td>
                    <td class="border border-gray-300 px-4 py-2 text-center">
                      <button class="bg-blue-500 text-white px-2 py-1 rounded view-button" onclick="viewForm('${
                        request.id
                      }')">View Form</button>
                    </td>
                    <td class="border border-gray-300 px-4 py-2 text-center">
                      <button class="recall-btn bg-yellow-500 text-white px-2 py-1 rounded" onclick="openRecallModal(${JSON.stringify(
                        request
                      )})">Recall</button>
                    </td>
                  `;
                tbodyRecall.appendChild(recallRow);
              }
            }
          });

          // After rendering pending rows, fetch remaining credits per unique employee and fill the column
          const pendingEmails = Array.from(
            new Set(
              requests
                .filter((r) => r && r.status === "pending" && r.employee_email)
                .map((r) => r.employee_email)
            )
          );

          const creditsByEmail = {};
          const fetches = pendingEmails.map((email) =>
            fetch(
              `../api/employee_leave_credits.php?email=${encodeURIComponent(
                email
              )}`
            )
              .then((r) => r.json())
              .then((js) => {
                if (js && js.success) creditsByEmail[email] = js;
              })
              .catch(() => {})
          );

          Promise.all(fetches).then(() => {
            // Helper: normalize leave type similar to backend
            const ENTITLEMENTS = {
              "Vacation Leave": true,
              "Mandatory / Forced Leave": true,
              "Sick Leave": true,
              "Maternity Leave": true,
              "Paternity Leave": true,
              "Special Privilege Leave": true,
              "Solo Parent Leave": true,
              "Study Leave": true,
              "10-Day VAWC Leave": true,
              "Rehabilitation Leave": true,
              "Special Leave Benefits for Women": true,
              "Special Emergency (Calamity) Leave": true,
              "Adoption Leave": true,
            };
            const TYPE_ALIASES = [
              ["vacation", "Vacation Leave"],
              ["mandatory", "Mandatory / Forced Leave"],
              ["forced", "Mandatory / Forced Leave"],
              ["sick", "Sick Leave"],
              ["maternity", "Maternity Leave"],
              ["paternity", "Paternity Leave"],
              ["privilege", "Special Privilege Leave"],
              ["solo parent", "Solo Parent Leave"],
              ["study", "Study Leave"],
              ["vawc", "10-Day VAWC Leave"],
              ["rehabilitation", "Rehabilitation Leave"],
              ["women", "Special Leave Benefits for Women"],
              ["emergency", "Special Emergency (Calamity) Leave"],
              ["calamity", "Special Emergency (Calamity) Leave"],
              ["adoption", "Adoption Leave"],
            ];
            function normalizeLeaveType(raw) {
              const r = String(raw || "").trim();
              if (!r) return null;
              // exact
              if (ENTITLEMENTS[r]) return r;
              const low = r.toLowerCase();
              for (const [needle, mapped] of TYPE_ALIASES) {
                if (low.indexOf(needle) !== -1) return mapped;
              }
              return null;
            }
            function fmtDays(n) {
              const v = Number(n);
              if (!isFinite(v)) return "N/A";
              return Number.isInteger(v) ? `${v}d` : `${v.toFixed(2)}d`;
            }
            function renderBadgeAndBar(available, total) {
              const a = Math.max(0, Number(available) || 0);
              const t = Math.max(0, Number(total) || 0);
              let ratio = t > 0 ? Math.min(1, a / t) : 0;
              // Color scheme based on remaining ratio
              let badgeBg = "bg-green-100",
                badgeText = "text-green-700",
                bar = "bg-green-500";
              if (a <= 0) {
                badgeBg = "bg-red-100";
                badgeText = "text-red-700";
                bar = "bg-red-500";
                ratio = 0;
              } else if (ratio < 0.2) {
                badgeBg = "bg-yellow-100";
                badgeText = "text-yellow-700";
                bar = "bg-yellow-500";
              }
              const percent = Math.round(ratio * 100);
              const ofText = t > 0 ? `of ${t}` : "";
              return `
                <div class="flex items-center gap-2">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold ${badgeBg} ${badgeText}">${fmtDays(
                a
              )}</span>
                  <span class="text-xs text-gray-500">${ofText}</span>
                </div>
                <div class="mt-1 h-1.5 w-24 bg-gray-200 rounded" title="${a} of ${t} days remaining">
                  <div class="h-1.5 rounded ${bar}" style="width: ${percent}%;"></div>
                </div>
              `;
            }

            document
              .querySelectorAll("#leave-request td.remaining-days")
              .forEach((td) => {
                const email = td.getAttribute("data-email") || "";
                const rawType = td.getAttribute("data-leave-type") || "";
                const credits = creditsByEmail[email];
                if (!credits || !credits.success) {
                  td.innerHTML =
                    '<span class="text-gray-400 text-xs">N/A</span>';
                  return;
                }
                const norm = normalizeLeaveType(rawType);
                if (norm && Array.isArray(credits.data)) {
                  const item = credits.data.find((x) => x.type === norm);
                  if (item) {
                    td.innerHTML = renderBadgeAndBar(
                      item.available,
                      item.total
                    );
                    return;
                  }
                }
                // Fallback to overall available days if specific type not found
                if (
                  credits.summary &&
                  typeof credits.summary.availableDays !== "undefined"
                ) {
                  const a = Number(credits.summary.availableDays) || 0;
                  td.innerHTML = `
                    <div class="flex items-center gap-2">
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">${fmtDays(
                        a
                      )}</span>
                      <span class="text-xs text-gray-500">overall</span>
                    </div>
                  `;
                } else {
                  td.innerHTML =
                    '<span class="text-gray-400 text-xs">N/A</span>';
                }
              });
          });
        } catch (error) {
          console.error("Failed to fetch leave requests", error);
        }
      }

      // Archive / Restore handlers for Leave History
      async function archiveLeave(id) {
        if (!confirm("Archive this leave record?")) return;
        try {
          const res = await fetch("../api/leave_archive.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id }),
          });
          const data = await res.json();
          if (!data || !data.success) {
            alert(data && data.error ? data.error : "Failed to archive");
            return;
          }
          fetchLeaveRequests();
        } catch (e) {
          alert("Network error");
        }
      }

      async function restoreLeave(id) {
        try {
          const res = await fetch("../api/leave_restore.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id }),
          });
          const data = await res.json();
          if (!data || !data.success) {
            alert(data && data.error ? data.error : "Failed to restore");
            return;
          }
          fetchLeaveRequests();
        } catch (e) {
          alert("Network error");
        }
      }

      // Open recall modal with real-time info
      function openRecallModal(request) {
        const modal = document.getElementById("leave-recall-modal");
        if (!modal) {
          alert("Leave recall modal is disabled.");
          return;
        }
        modal.classList.add("show");
        document.getElementById("recall-employee-name").value = (
          (request.firstname || "") +
          (request.lastname ? " " + request.lastname : "")
        ).trim();
        document.getElementById("recall-reason").value = request.reason || "";
        // Robust date extraction (YYYY-MM-DD)
        let startDate = "",
          endDate = "";
        if (request.dates) {
          const matches = request.dates.match(/\d{4}-\d{2}-\d{2}/g);
          if (matches && matches.length > 0) {
            startDate = matches[0];
            endDate = matches[1] ? matches[1] : matches[0];
          }
        }
        document.getElementById("recall-start-date").value = startDate;
        document.getElementById("recall-end-date").value = endDate;
        // Calculate days remaining (difference between end date and today)
        let daysRemaining = "";
        if (endDate) {
          const end = new Date(endDate);
          const today = new Date();
          end.setHours(0, 0, 0, 0);
          today.setHours(0, 0, 0, 0);
          const diff = Math.ceil((end - today) / (1000 * 60 * 60 * 24));
          daysRemaining = diff > 0 ? diff : 0;
        }
        document.getElementById("recall-days-remaining").value = daysRemaining;
        document.getElementById("recall-new-resumption-date").value =
          request.new_resumption_date || "";
        // Fetch users for relief dropdown
        fetch("../api/get_users.php")
          .then((response) => response.json())
          .then((data) => {
            const select = document.getElementById("recall-relief-officer");
            select.innerHTML =
              '<option value="">Select Relief Officer</option>';
            if (data.success && Array.isArray(data.data)) {
              data.data.forEach((user) => {
                const option = document.createElement("option");
                option.value = user.email;
                option.textContent =
                  user.firstname +
                  " " +
                  user.lastname +
                  " (" +
                  user.email +
                  ")";
                select.appendChild(option);
              });
              // If request.relief_officer matches, preselect
              if (request.relief_officer) {
                select.value = request.relief_officer;
              }
            }
          });
        document.getElementById("initiate-recall-btn").onclick = function (e) {
          e.preventDefault();
          initiateRecall(request.id);
        };
      }

      // Initiate recall action
      function initiateRecall(id) {
        // Find the request object for the employee email
        const request = window._leaveRequests.find((r) => r.id == id);
        fetch(`../api/update_leave_status.php`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id, status: "recall" }),
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              // Send notification to employee
              if (request && request.employee_email) {
                fetch("../api/add_notification.php", {
                  method: "POST",
                  headers: { "Content-Type": "application/json" },
                  body: JSON.stringify({
                    recipient_email: request.employee_email,
                    message: `Your leave has been recalled by the department head. Please check your dashboard for details.`,
                    type: "recall",
                  }),
                });
              }
              alert("Leave request recalled successfully!");
              const m = document.getElementById("leave-recall-modal");
              if (m) m.classList.remove("show");
              fetchLeaveRequests();
            } else {
              alert("Failed to recall leave request.");
            }
          })
          .catch((error) => {
            console.error("Error recalling leave request:", error);
            alert("An error occurred while recalling the leave request.");
          });
      }

      // Fetch leave requests on page load
      document.addEventListener("DOMContentLoaded", fetchLeaveRequests);

      // Optionally, refresh the table every minute
      setInterval(fetchLeaveRequests, 60000);

      // View form function
      function viewForm(id) {
        window.location.href = `civil_form.php?id=${id}`;
      }

      // Handle approve/decline action
      function handleAction(select) {
        const id = select.getAttribute("data-id");
        const action = select.value;
        if (!action) return;
        if (action === "declined") {
          // Show decline modal and store id
          window._declineRequestId = id;
          document.getElementById("decline-modal").classList.add("show");
        } else {
          // Before approving, ensure the Dept Head edited the form (saved 7.B recommendation/signature)
          try {
            const req = window._leaveRequests.find(
              (r) => String(r.id) === String(id)
            );
            let edited = false;
            // Check direct recommendation field (legacy) or details.hr.section7.authorized_officer_7b
            if (req) {
              if (
                req.recommendation &&
                String(req.recommendation).toLowerCase().includes("approval")
              )
                edited = true;
              if (!edited && req.authorized_officer_recommendation)
                edited = true;
              if (!edited && req.details) {
                try {
                  const d =
                    typeof req.details === "string"
                      ? JSON.parse(req.details)
                      : req.details;
                  if (
                    d &&
                    d.hr &&
                    d.hr.section7 &&
                    d.hr.section7.authorized_officer_7b
                  )
                    edited = true;
                } catch (e) {
                  /* ignore */
                }
              }
            }
            if (!edited) {
              alert(
                'You must edit the form (7.B recommendation/signature) before approving. Please click "Edit" and add your recommendation and signature.'
              );
              // reset select
              select.value = "";
              return;
            }

            // Approve action: update backend immediately
            fetch(`../api/update_leave_status.php`, {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ id, status: action }),
            })
              .then((response) => response.json())
              .then((data) => {
                if (data.success) {
                  alert(`Leave request ${action}d successfully!`);
                  fetchLeaveRequests();
                } else {
                  alert("Failed to update leave request.");
                }
              })
              .catch((error) => {
                console.error("Error updating leave request:", error);
                alert("An error occurred while updating the leave request.");
              });
          } catch (e) {
            console.error("approval check", e);
            alert("Error checking form state. Cannot approve at this time.");
            select.value = "";
          }
        }
      }

      // Decline modal logic
      document.addEventListener("DOMContentLoaded", function () {
        const declineModal = document.getElementById("decline-modal");
        const declineForm = document.getElementById("decline-form");
        const declineCancelBtns = document.querySelectorAll(
          "#decline-cancel-btn"
        );
        declineCancelBtns.forEach((btn) => {
          btn.onclick = function () {
            declineModal.classList.remove("show");
            window._declineRequestId = null;
          };
        });
        if (declineForm) {
          declineForm.onsubmit = function (e) {
            e.preventDefault();
            const reason = document
              .getElementById("decline-reason-input")
              .value.trim();
            const id = window._declineRequestId;
            if (!reason || !id) return;
            // Update backend with decline and reason
            fetch(`../api/update_leave_status.php`, {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ id, status: "declined", reason }),
            })
              .then((response) => response.json())
              .then((data) => {
                if (data.success) {
                  // Send notification to employee
                  const request = window._leaveRequests.find((r) => r.id == id);
                  if (request && request.employee_email) {
                    fetch("../api/add_notification.php", {
                      method: "POST",
                      headers: { "Content-Type": "application/json" },
                      body: JSON.stringify({
                        recipient_email: request.employee_email,
                        message: `Your leave request has been declined. Reason: ${reason}`,
                        type: "declined",
                      }),
                    });
                  }
                  alert("Leave request declined successfully!");
                  declineModal.classList.remove("show");
                  window._declineRequestId = null;
                  fetchLeaveRequests();
                } else {
                  alert("Failed to decline leave request.");
                }
              })
              .catch((error) => {
                console.error("Error declining leave request:", error);
                alert("An error occurred while declining the leave request.");
              });
          };
        }
      });

      /* Dept Head 7.B Modal - edit recommendation signature */
      // inject modal HTML near the end of body
      (function insertDept7BModal() {
        const tpl = `
        <div id="dept7b-modal" class="modal-overlay">
          <div class="modal-content" style="max-width: 700px;">
            <button class="modal-close-btn" id="dept7b-modal-close">&times;</button>
            <div class="modal-profile-header">
              <h4 id="dept7b-modal-title">Edit 7.B — Recommendation (Dept Head)</h4>
              <div class="employee-details text-sm text-gray-500" id="dept7b-current-name">Fill out the recommendation and upload your signature.</div>
            </div>
            <div class="modal-leave-credits">
              <!-- 7.B RECOMMENDATION Section -->
              <div class="form-group mt-4">
                <label class="font-semibold text-base mb-2 block">7.B RECOMMENDATION</label>
                <div class="space-y-3">
                  <label class="flex items-start">
                    <input type="checkbox" id="dept_rec_appr_modal" name="dept_recommendation_modal" value="For approval" class="mt-1" style="min-width: 18px; min-height: 18px; border: 2px solid #000; cursor: pointer;" />
                    <span class="ml-2 text-sm">For approval</span>
                  </label>
                  <label class="flex items-start">
                    <input type="checkbox" id="dept_rec_disappr_modal" name="dept_recommendation_modal" value="For disapproval" class="mt-1" style="min-width: 18px; min-height: 18px; border: 2px solid #000; cursor: pointer;" />
                    <span class="ml-2 text-sm">For disapproval due to</span>
                  </label>
                </div>
                
                <div class="mt-3">
                  <label class="text-sm font-medium mb-1 block">Reason for disapproval (if applicable):</label>
                  <textarea 
                    id="dept_disapproval_reason_modal" 
                    rows="4" 
                    class="w-full p-2 border border-gray-300 rounded text-sm"
                    placeholder="Enter reason for disapproval here. The text will automatically wrap to new lines."
                    style="resize: vertical; min-height: 80px; word-wrap: break-word; white-space: pre-wrap;"
                  ></textarea>
                  <p class="text-xs text-gray-500 mt-1">Note: Text will wrap automatically and be split into 3 lines on the form.</p>
                </div>
              </div>

              <div class="form-group mt-4">
                <div class="mt-1">
                  <p class="text-sm text-gray-600">Your signature will be saved and reused for future requests. Upload once and it will be stored in your account.</p>
                </div>
                <div class="mt-3">
                  <label class="text-sm font-medium">Signed by (auto-filled)</label>
                  <input id="dept7b_name" type="text" class="w-full mt-1 p-2 border border-gray-300 rounded text-sm" readonly />
                </div>
                
                <div class="mt-3">
                  <label class="text-sm font-medium">Upload Department Head E-Signature</label>
                  <div id="existing_signature_preview" class="mt-2 mb-2" style="display:none;">
                    <p class="text-xs text-green-600 mb-1">✓ You have an existing signature on file</p>
                    <img id="existing_sig_img" src="" alt="Existing signature" style="max-width: 200px; max-height: 60px; border: 1px solid #ccc; padding: 4px; background: white;" />
                    <p class="text-xs text-gray-500 mt-1">Upload a new file below to replace it (old signature will be deleted)</p>
                  </div>
                  <input id="dept7b_sig" type="file" accept="image/*" class="mt-2 w-full" />
                  <p class="text-xs text-gray-500 mt-1">Upload once and it will be saved to your account for future use</p>
                </div>
              </div>
            </div>
            <div class="form-buttons" style="margin-top:1.5rem;">
              <button id="dept7b-save-btn" class="initiate-button" style="background-color:#10b981; padding: 0.75rem 1.5rem;">Save & Preview Form</button>
              <button id="dept7b-cancel-btn" class="cancel-button" style="background-color:#6b7280; margin-left:0.5rem; padding: 0.75rem 1.5rem;">Cancel</button>
            </div>
          </div>
        </div>`;
        document.body.insertAdjacentHTML("beforeend", tpl);
        // wire close handlers
        document
          .getElementById("dept7b-modal-close")
          .addEventListener("click", closeDept7BModal);
        document
          .getElementById("dept7b-cancel-btn")
          .addEventListener("click", closeDept7BModal);
        document
          .getElementById("dept7b-save-btn")
          .addEventListener("click", saveDept7B);
        // Handle mutually exclusive checkboxes for recommendation
        document
          .getElementById("dept_rec_appr_modal")
          .addEventListener("change", function () {
            if (this.checked) {
              document.getElementById("dept_rec_disappr_modal").checked = false;
            }
          });
        document
          .getElementById("dept_rec_disappr_modal")
          .addEventListener("change", function () {
            if (this.checked) {
              document.getElementById("dept_rec_appr_modal").checked = false;
            }
          });
      })();

      function openDept7BModal(id) {
        window._editingDept7BId = id;
        const modal = document.getElementById("dept7b-modal");
        // clear inputs
        const fi = document.getElementById("dept7b_sig");
        if (fi) fi.value = null;

        // Clear and reset recommendation checkboxes
        document.getElementById("dept_rec_appr_modal").checked = false;
        document.getElementById("dept_rec_disappr_modal").checked = false;
        document.getElementById("dept_disapproval_reason_modal").value = "";

        // Try to prefill from loaded requests data
        try {
          const req = window._leaveRequests.find(
            (r) => String(r.id) === String(id)
          );
          if (req) {
            // 1) Prefill from top-level fields if present
            if (req.recommendation === "For approval") {
              document.getElementById("dept_rec_appr_modal").checked = true;
            } else if (req.recommendation === "For disapproval") {
              document.getElementById("dept_rec_disappr_modal").checked = true;
            }

            // Collect disapproval reasons preferring top-level columns
            const reasonsTop = [];
            if (req.disapproval_reason1)
              reasonsTop.push(req.disapproval_reason1);
            if (req.disapproval_reason2)
              reasonsTop.push(req.disapproval_reason2);
            if (req.disapproval_reason3)
              reasonsTop.push(req.disapproval_reason3);

            // 2) If no top-level values, try to parse details JSON for fallback storage
            let detailsObj = null;
            try {
              detailsObj =
                typeof req.details === "string"
                  ? JSON.parse(req.details)
                  : req.details;
            } catch (e) {
              detailsObj = null;
            }

            // If recommendation wasn't set from top-level, check fallback locations
            if (
              !document.getElementById("dept_rec_appr_modal").checked &&
              !document.getElementById("dept_rec_disappr_modal").checked
            ) {
              if (detailsObj) {
                // recommendation_fallback is used when DB columns are missing
                const rf = detailsObj.recommendation_fallback || {};
                if (
                  rf.recommendation === "For approval" ||
                  rf.recommendation === "For disapproval"
                ) {
                  if (rf.recommendation === "For approval")
                    document.getElementById(
                      "dept_rec_appr_modal"
                    ).checked = true;
                  else
                    document.getElementById(
                      "dept_rec_disappr_modal"
                    ).checked = true;
                }
                // Also check older/alternate locations
                if (
                  !document.getElementById("dept_rec_appr_modal").checked &&
                  !document.getElementById("dept_rec_disappr_modal").checked
                ) {
                  if (detailsObj.recommendation === "For approval")
                    document.getElementById(
                      "dept_rec_appr_modal"
                    ).checked = true;
                  else if (detailsObj.recommendation === "For disapproval")
                    document.getElementById(
                      "dept_rec_disappr_modal"
                    ).checked = true;
                }
              }
            }

            // Determine disapproval reason lines: prefer top-level, then recommendation_fallback
            let reasons = reasonsTop.slice();
            if (reasons.length === 0 && detailsObj) {
              const rf = detailsObj.recommendation_fallback || {};
              if (rf.disapproval_reason1) reasons.push(rf.disapproval_reason1);
              if (rf.disapproval_reason2) reasons.push(rf.disapproval_reason2);
              if (rf.disapproval_reason3) reasons.push(rf.disapproval_reason3);
            }

            // As another fallback, some code stores reasons under details.hr.section7 (legacy/alternate)
            if (
              reasons.length === 0 &&
              detailsObj &&
              detailsObj.hr &&
              detailsObj.hr.section7
            ) {
              const s7 = detailsObj.hr.section7;
              if (s7.disapproval_reason1) reasons.push(s7.disapproval_reason1);
              if (s7.disapproval_reason2) reasons.push(s7.disapproval_reason2);
              if (s7.disapproval_reason3) reasons.push(s7.disapproval_reason3);
            }

            if (reasons.length > 0) {
              document.getElementById("dept_disapproval_reason_modal").value =
                reasons.join("\n");
            }
          }
        } catch (e) {
          console.error(e);
        }

        // auto-fill name from current_user
        fetch("../api/current_user.php")
          .then((r) => r.json())
          .then((js) => {
            let u = null;
            if (!js) return;
            if (js.user) u = js.user;
            else if (js.logged_in || js.firstname) u = js;
            // Use First MI. Last format per requirement (uppercase)
            let name = "";
            if (u) {
              const parts = [];
              if (u.firstname && u.firstname.trim())
                parts.push(u.firstname.trim());
              if (u.mi && u.mi.trim()) {
                parts.push(u.mi.trim().charAt(0).toUpperCase() + ".");
              }
              if (u.lastname && u.lastname.trim())
                parts.push(u.lastname.trim());
              name = parts.join(" ").toUpperCase();
            }
            document.getElementById("dept7b_name").value = name;

            const hdr = document.getElementById("dept7b-current-name");
            if (hdr)
              hdr.textContent =
                "Fill out the recommendation and upload your signature. Uploading as: " +
                (name || "");

            // Check for existing signature
            if (u && u.email) {
              fetch(
                `../api/get_user_signature.php?email=${encodeURIComponent(
                  u.email
                )}`
              )
                .then((r) => r.json())
                .then((sigData) => {
                  if (sigData.success && sigData.hasSignature && sigData.url) {
                    // Show existing signature preview
                    document.getElementById(
                      "existing_signature_preview"
                    ).style.display = "block";
                    document.getElementById("existing_sig_img").src =
                      sigData.url;
                  } else {
                    document.getElementById(
                      "existing_signature_preview"
                    ).style.display = "none";
                  }
                })
                .catch(() => {
                  document.getElementById(
                    "existing_signature_preview"
                  ).style.display = "none";
                });
            }
          })
          .catch(() => {});
        modal.classList.add("show");
      }

      function closeDept7BModal() {
        const modal = document.getElementById("dept7b-modal");
        if (modal) modal.classList.remove("show");
        window._editingDept7BId = null;
      }

      async function saveDept7B() {
        const id = window._editingDept7BId;
        if (!id) return alert("No request selected");

        // Get recommendation values
        const recAppr = document.getElementById("dept_rec_appr_modal").checked;
        const recDisappr = document.getElementById(
          "dept_rec_disappr_modal"
        ).checked;
        const disapprovalText = document
          .getElementById("dept_disapproval_reason_modal")
          .value.trim();

        // Validate: must select one recommendation
        if (!recAppr && !recDisappr) {
          return alert(
            "Please select a recommendation (For approval or For disapproval)"
          );
        }

        // If both are checked, show error
        if (recAppr && recDisappr) {
          return alert("Please select only one recommendation option");
        }

        const recommendation = recAppr ? "For approval" : "For disapproval";

        // Split disapproval reason into 3 lines (max ~55 chars per line)
        const splitTextIntoLines = (text, maxCharsPerLine = 55) => {
          if (!text) return ["", "", ""];

          const words = text.split(/\s+/);
          const lines = ["", "", ""];
          let currentLine = 0;

          for (const word of words) {
            // Check if we need to move to next line
            const currentText = lines[currentLine];
            const testLine = currentText ? currentText + " " + word : word;

            if (testLine.length <= maxCharsPerLine) {
              // Word fits on current line
              lines[currentLine] = testLine;
            } else {
              // Word doesn't fit, move to next line
              if (currentLine < 2) {
                // Move to next line
                currentLine++;
                lines[currentLine] = word;
              } else {
                // Already on line 3, force add with space if it fits within reasonable limit
                if (
                  currentText.length + word.length + 1 <=
                  maxCharsPerLine + 10
                ) {
                  lines[currentLine] = currentText + " " + word;
                } else {
                  // Truncate with ellipsis
                  lines[currentLine] = currentText + "...";
                  break;
                }
              }
            }
          }

          return lines;
        };

        const [reason1, reason2, reason3] = splitTextIntoLines(
          disapprovalText,
          55
        );

        const name = document.getElementById("dept7b_name").value || "";

        // Get current user email for signature storage
        let userEmail = null;
        try {
          const userResp = await fetch("../api/current_user.php");
          const userJson = await userResp.json();
          if (
            userJson &&
            (userJson.email || (userJson.user && userJson.user.email))
          ) {
            userEmail = userJson.email || userJson.user.email;
          }
        } catch (e) {
          console.error("Error fetching user email:", e);
        }

        // convert file to dataURI and save to employee_signatures table
        const fi = document.getElementById("dept7b_sig");
        const toDataURI = (file) =>
          new Promise((res, rej) => {
            const r = new FileReader();
            r.onload = () => res(r.result);
            r.onerror = () => rej();
            r.readAsDataURL(file);
          });

        const sigs = [];
        let signatureUploaded = false;

        if (fi && fi.files && fi.files[0]) {
          try {
            const dataUri = await toDataURI(fi.files[0]);

            // Save signature to employee_signatures table if we have user email
            if (userEmail) {
              try {
                const sigResp = await fetch("../api/save_signature.php", {
                  method: "POST",
                  headers: { "Content-Type": "application/json" },
                  body: JSON.stringify({
                    employee_email: userEmail,
                    signature_data_uri: dataUri,
                  }),
                });
                const sigJson = await sigResp.json();
                if (sigJson.success) {
                  console.log("Signature saved to employee_signatures table");
                  signatureUploaded = true;
                  // Use the saved path from employee_signatures for this request
                  sigs.push({ key: "7b", data_uri: dataUri });
                } else {
                  console.error("Failed to save signature:", sigJson.error);
                  // Still try to save with the request
                  sigs.push({ key: "7b", data_uri: dataUri });
                  signatureUploaded = true;
                }
              } catch (e) {
                console.error("Error saving signature:", e);
                // Still try to save with the request
                sigs.push({ key: "7b", data_uri: dataUri });
                signatureUploaded = true;
              }
            } else {
              // No email, just save with request
              sigs.push({ key: "7b", data_uri: dataUri });
              signatureUploaded = true;
            }
          } catch (e) {
            console.error("Error converting file to data URI:", e);
          }
        } else if (userEmail) {
          // No new file uploaded, try to use existing signature from employee_signatures
          try {
            const existingSigResp = await fetch(
              `../api/get_user_signature.php?email=${encodeURIComponent(
                userEmail
              )}`
            );
            const existingSigJson = await existingSigResp.json();
            if (
              existingSigJson.success &&
              existingSigJson.hasSignature &&
              existingSigJson.url
            ) {
              console.log(
                "Using existing signature from employee_signatures table"
              );
              // Fetch the existing signature as blob and convert to data URI
              const imgResp = await fetch(existingSigJson.url);
              const blob = await imgResp.blob();
              const existingDataUri = await new Promise((resolve) => {
                const reader = new FileReader();
                reader.onloadend = () => resolve(reader.result);
                reader.readAsDataURL(blob);
              });
              sigs.push({ key: "7b", data_uri: existingDataUri });
              signatureUploaded = true;
            }
          } catch (e) {
            console.error("Error fetching existing signature:", e);
          }
        }

        // Validate that signature is provided (either new upload or existing)
        if (!signatureUploaded) {
          return alert(
            "Please upload your signature or ensure you have an existing signature on file."
          );
        }

        // build section7 object with authorized_officer_7b
        const section7 = { authorized_officer_7b: name };
        const payload = {
          id,
          section7,
          signatures: sigs,
          recommendation,
          disapproval_reason1: reason1,
          disapproval_reason2: reason2,
          disapproval_reason3: reason3,
        };

        try {
          const resp = await fetch("../api/update_hr_section7.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload),
          });
          const js = await resp.json();
          if (js && js.success) {
            // Open preview so the dept head can verify placement immediately
            const previewUrl = `civil_form.php?id=${id}&live=1&from=dept7b`;
            window.open(previewUrl, "_blank");
            alert(
              "Recommendation and signature saved. Your signature has been stored and will be reused for future requests. A preview has been opened so you can verify the form before finalizing."
            );
            closeDept7BModal();
            fetchLeaveRequests();
          } else {
            const errorMsg = js.error || "unknown";
            if (
              errorMsg.includes("DB error") ||
              errorMsg.includes("Unknown column")
            ) {
              alert(
                "Database Error: Missing columns detected.\n\n" +
                  "Please run the database migration:\n" +
                  "1. Open the Supabase SQL Editor and run the latest database SQL.\n" +
                  "2. Wait for success message\n" +
                  "3. Try saving again\n\n" +
                  "Error details: " +
                  errorMsg
              );
            } else {
              alert("Failed to save: " + errorMsg);
            }
          }
        } catch (e) {
          console.error(e);
          alert("Network error");
        }
      }
    </script>
  </body>
</html>
