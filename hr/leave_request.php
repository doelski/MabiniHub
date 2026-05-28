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
            <li class="nav-item active">
              <a href="#"><i class="fas fa-calendar-plus"></i> Leave Request</a>
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
                  </button>
                  <!-- Apply Leave button for HR -->
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
                        Leave Type
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
                  <tbody id="hr-leave-request">
                    <!-- Dynamic leave request rows will be rendered here by JavaScript -->
                  </tbody>
                </table>
              </div>
            </div>

            <div id="leave-history" class="tab-content">
              <div class="table-container">
                <div class="table-header-controls">
                </div>
                <table class="min-w-full border border-gray-300">
                  <thead>
                    <tr>
                      <th class="border border-gray-300 px-4 py-2 text-left">
                        Name(s)
                      </th>
                      <th class="border border-gray-300 px-4 py-2 text-left">
                        Leave Type
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
                  <tbody id="hr-leave-history">
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
      document.addEventListener("DOMContentLoaded", () => {
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

        // Leave recall modal removed

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
            // Update backend with decline and reason, mark as declined by HR
            fetch(`../api/update_leave_status.php`, {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({
                id,
                status: "declined",
                reason,
                declined_by_hr: true,
              }),
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
                        message: `Your leave request has been declined by HR. Reason: ${reason}`,
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

      function fetchLeaveRequests() {
        fetch("../api/get_hr_leave_requests.php")
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              window._leaveRequests = data.data;
              // Leave Request (department head approved, not HR approved yet)
              const tbodyRequest = document.querySelector("#hr-leave-request");
              tbodyRequest.innerHTML = "";
              // Leave History (HR approved/declined)
              const tbodyHistory = document.querySelector("#hr-leave-history");
              tbodyHistory.innerHTML = "";

              data.data.forEach((request) => {
                const name =
                  (request.firstname ? request.firstname : "") +
                  (request.lastname ? " " + request.lastname : "");

                // Leave Request Table (dept head approved, pending HR approval)
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
                )}">…</td>
                  <td class="border border-gray-300 px-4 py-2 text-left">${
                    request.dates || ""
                  }</td>
                  <td class="border border-gray-300 px-4 py-2 text-center">
                    <button class="bg-blue-500 text-white px-2 py-1 rounded view-button" onclick="viewForm('${
                      request.id
                    }')">View</button>
                    <button class="ml-2 bg-yellow-500 text-white px-2 py-1 rounded edit-hr-button" onclick="openHRSectionModal('${
                      request.id
                    }')">Edit</button>
                  </td>
                  <td class="border border-gray-300 px-4 py-2 text-center">
                    <select class="action-dropdown px-2 py-1 rounded border border-gray-300" data-id="${
                      request.id
                    }" onchange="handleHRAction(this)">
                      <option value="">Select Action</option>
                      <option value="approved">Approve</option>
                      <option value="declined">Decline</option>
                    </select>
                  </td>
                `;
                tbodyRequest.appendChild(row);
              });

              // After rendering rows, fetch remaining credits per unique employee and fill the column
              const uniqueEmails = Array.from(
                new Set(
                  (data.data || [])
                    .map((r) => r.employee_email)
                    .filter((e) => !!e)
                )
              );

              const creditsByEmail = {};
              const fetches = uniqueEmails.map((email) =>
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
                  .querySelectorAll("#hr-leave-request td.remaining-days")
                  .forEach((td) => {
                    const email = td.getAttribute("data-email") || "";
                    const rawType = td.getAttribute("data-leave-type") || "";
                    const credits = creditsByEmail[email];
                    td.innerHTML =
                      '<span class="text-gray-400 text-xs">Loading…</span>';
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

              // Ensure prominent Show Archived toggle exists above HR Leave History
              (function ensureArchiveToggle(){
                const histContainer = document.querySelector('#leave-history .table-container');
                if (histContainer && !document.getElementById('hr-history-archive-toggle')) {
                  histContainer.insertAdjacentHTML('afterbegin', `
                    <div id="hr-history-archive-toggle" class="mb-3">
                      <div class="flex items-center gap-3 px-3 py-2 rounded-lg border border-amber-300 bg-amber-50 text-amber-800 shadow-sm">
                        <i class="fas fa-archive"></i>
                        <label class="inline-flex items-center gap-2 font-semibold" title="Show archived leave requests in HR history">
                          <input type="checkbox" id="cbHRShowArchivedHistory" class="w-4 h-4 accent-amber-600">
                          <span>Show archived</span>
                        </label>
                        <span class="text-xs text-amber-700">(Archived items are hidden by default)</span>
                      </div>
                    </div>`);
                  const cb = document.getElementById('cbHRShowArchivedHistory');
                  if (cb) {
                    cb.addEventListener('change', function(){
                      window.__hrShowArchivedHistory = !!this.checked;
                      fetchLeaveRequests();
                    });
                  }
                }
              })();

              // Fetch all leave requests for history, include archived if toggle is on
              const urlAll = window.__hrShowArchivedHistory ? "../api/get_leave_requests.php?include_archived=1" : "../api/get_leave_requests.php";
              fetch(urlAll)
                .then((response) => response.json())
                .then((allData) => {
                  if (allData.success) {
                    const tbodyHistory = document.querySelector('#hr-leave-history');
                    tbodyHistory.innerHTML = '';
                    const rows = (allData.data || [])
                      .filter((r) => String(r.approved_by_hr || '0') === '1')
                      .map((request) => {
                        const name = `${request.firstname ? request.firstname : ''}${request.lastname ? ' ' + request.lastname : ''}`;
                        const archived = Number(request.is_archived || 0) === 1;
                        if (!window.__hrShowArchivedHistory && archived) return null;
                        const statusBadge = (request.status === 'approved' && request.approved_by_hr)
                          ? '<span class="status-badge approve">Approved by HR</span>'
                          : (request.status === 'declined' ? '<span class="status-badge decline">Declined</span>' : '');
                        const actionBtns = archived
                          ? `<span class=\"text-xs px-2 py-1 rounded bg-gray-200 text-gray-700 mr-2\">Archived</span>
                             <button class=\"bg-green-600 hover:bg-green-700 text-white px-2 py-1 rounded mr-2\" onclick=\"restoreLeave(${request.id})\">Restore</button>
                             <button class=\"bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded\" onclick=\"deleteLeave(${request.id})\">Delete</button>`
                          : `<button class=\"bg-amber-500 hover:bg-amber-600 text-white px-2 py-1 rounded\" onclick=\"archiveLeave(${request.id})\">Archive</button>`;
                        const trClass = archived ? 'opacity-70' : '';
                        return `
                          <tr class="${trClass}">
                            <td class="border border-gray-300 px-4 py-2 text-left">${name}</td>
                            <td class="border border-gray-300 px-4 py-2 text-left">${request.leave_type || ''}</td>
                            <td class="border border-gray-300 px-4 py-2 text-left">${request.dates || ''}</td>
                            <td class="border border-gray-300 px-4 py-2 text-center">
                              <button class="bg-blue-500 text-white px-2 py-1 rounded mr-2" onclick="viewForm('${request.id}')">View</button>
                              ${actionBtns}
                            </td>
                            <td class="border border-gray-300 px-4 py-2 text-center">${statusBadge}</td>
                          </tr>`;
                      })
                      .filter(Boolean)
                      .join('');
                    tbodyHistory.innerHTML = rows || '<tr><td colspan="5" class="text-center text-gray-500 py-3">No history yet.</td></tr>';
                  }
                });
            } else {
              console.error("Failed to fetch HR leave requests:", data.error);
            }
          })
          .catch((error) =>
            console.error("Error fetching HR leave requests:", error)
          );
      }

      // View form function
      function viewForm(id) {
        window.location.href = `../dept_head/civil_form.php?id=${id}`;
      }

      // HR Archive / Restore / Delete handlers
      async function archiveLeave(id){
        if(!confirm('Archive this leave record?')) return;
        try{
          const res = await fetch('../api/leave_archive.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id}) });
          const js = await res.json(); if(!js.success){ alert(js.error||'Failed to archive'); return; }
          fetchLeaveRequests();
        }catch(e){ alert('Network error'); }
      }
      async function restoreLeave(id){
        try{
          const res = await fetch('../api/leave_restore.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id}) });
          const js = await res.json(); if(!js.success){ alert(js.error||'Failed to restore'); return; }
          fetchLeaveRequests();
        }catch(e){ alert('Network error'); }
      }
      async function deleteLeave(id){
        if(!confirm('Permanently delete this leave request? This cannot be undone.')) return;
        try{
          const res = await fetch('../api/leave_delete_permanent.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id}) });
          const js = await res.json(); if(!js.success){ alert(js.error||'Failed to delete'); return; }
          fetchLeaveRequests();
        }catch(e){ alert('Network error'); }
      }

      // Handle HR approve/decline action
      function handleHRAction(select) {
        const id = select.getAttribute("data-id");
        const action = select.value;
        if (!action) return;
        if (action === "declined") {
          // Show decline modal and store id
          window._declineRequestId = id;
          document.getElementById("decline-modal").classList.add("show");
        } else if (action === "approved") {
          // Before HR approves, ensure HR has filled/edited Section 7 (signatures or certifier name)
          try {
            const req = window._leaveRequests.find(
              (r) => String(r.id) === String(id)
            );
            let edited = false;
            if (req) {
              // check details.hr.section7.certifier_name or details.hr.signatures (certifier/final)
              if (req.details) {
                try {
                  const d =
                    typeof req.details === "string"
                      ? JSON.parse(req.details)
                      : req.details;
                  if (d && d.hr) {
                    const s7 = d.hr.section7 || {};
                    const sigs = d.hr.signatures || {};
                    if (
                      (s7 && (s7.certifier_name || s7.final_official)) ||
                      (sigs &&
                        (sigs.certifier ||
                          sigs.final ||
                          sigs["certifier"] ||
                          sigs["final"]))
                    )
                      edited = true;
                  }
                } catch (e) {
                  /* ignore parse errors */
                }
              }
            }
            if (!edited) {
              alert(
                'You must edit the form (HR Section 7 signatures) before approving. Please click "Edit" and upload the HR signatures.'
              );
              select.value = "";
              return;
            }

            // HR Approve action: update backend with approved_by_hr = 1
            fetch(`../api/update_leave_status.php`, {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({
                id,
                status: "approved",
                approved_by_hr: true,
              }),
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
                        message: `Your leave request has been approved by HR!`,
                        type: "approved",
                      }),
                    });
                  }
                  alert("Leave request approved successfully!");
                  fetchLeaveRequests();
                } else {
                  alert("Failed to approve leave request.");
                }
              })
              .catch((error) => {
                console.error("Error approving leave request:", error);
                alert("An error occurred while approving the leave request.");
              });
          } catch (e) {
            console.error("HR approval check", e);
            alert(
              "Error checking HR Section 7 state. Cannot approve at this time."
            );
            select.value = "";
          }
        }
      }

      // Fetch leave requests on page load
      document.addEventListener("DOMContentLoaded", fetchLeaveRequests);

      // Optionally, refresh the table every minute
      setInterval(fetchLeaveRequests, 60000);
    </script>

    <!-- HR Section 7 Modal -->
    <div id="hr-section-modal" class="modal-overlay">
      <div class="modal-content" style="max-width: 700px">
        <button class="modal-close-btn" id="hr-modal-close">&times;</button>
        <div class="modal-profile-header">
          <h4 id="hr-modal-title">
            Edit HR Section 7.A — Certification of Leave Credits
          </h4>
          <div class="employee-details text-sm text-gray-500">
            Fill out the leave credits certification and upload HR signature.
          </div>
        </div>

        <div class="modal-leave-credits">
          <!-- 7.A CERTIFICATION OF LEAVE CREDITS -->
          <div class="form-group mt-4">
            <label class="font-semibold text-base mb-2 block"
              >7.A CERTIFICATION OF LEAVE CREDITS</label
            >

            <div class="mt-3">
              <label class="text-sm font-medium mb-1 block"
                >As of (Date):</label
              >
              <input
                type="date"
                id="hr_certification_date"
                class="w-full p-2 border border-gray-300 rounded text-sm"
              />
            </div>

            <div class="mt-4 border border-gray-300 rounded">
              <div
                class="grid grid-cols-3 gap-0 border-b border-gray-300 bg-gray-50"
              >
                <div class="p-2 font-semibold text-sm"></div>
                <div
                  class="p-2 font-semibold text-sm text-center border-l border-gray-300"
                >
                  Vacation Leave
                </div>
                <div
                  class="p-2 font-semibold text-sm text-center border-l border-gray-300"
                >
                  Sick Leave
                </div>
              </div>

              <div class="grid grid-cols-3 gap-0 border-b border-gray-300">
                <div class="p-2 text-sm font-medium">Total Earned</div>
                <div class="p-2 border-l border-gray-300">
                  <input
                    type="number"
                    step="0.01"
                    id="hr_vl_total_earned"
                    class="w-full p-1 border border-gray-300 rounded text-sm"
                    placeholder="0.00"
                  />
                </div>
                <div class="p-2 border-l border-gray-300">
                  <input
                    type="number"
                    step="0.01"
                    id="hr_sl_total_earned"
                    class="w-full p-1 border border-gray-300 rounded text-sm"
                    placeholder="0.00"
                  />
                </div>
              </div>

              <div class="grid grid-cols-3 gap-0 border-b border-gray-300">
                <div class="p-2 text-sm font-medium">Less this application</div>
                <div class="p-2 border-l border-gray-300">
                  <input
                    type="number"
                    step="0.01"
                    id="hr_vl_less_application"
                    class="w-full p-1 border border-gray-300 rounded text-sm"
                    placeholder="0.00"
                  />
                </div>
                <div class="p-2 border-l border-gray-300">
                  <input
                    type="number"
                    step="0.01"
                    id="hr_sl_less_application"
                    class="w-full p-1 border border-gray-300 rounded text-sm"
                    placeholder="0.00"
                  />
                </div>
              </div>

              <div class="grid grid-cols-3 gap-0">
                <div class="p-2 text-sm font-medium">Balance</div>
                <div class="p-2 border-l border-gray-300">
                  <input
                    type="number"
                    step="0.01"
                    id="hr_vl_balance"
                    class="w-full p-1 border border-gray-300 rounded text-sm bg-gray-50"
                    placeholder="0.00"
                    readonly
                  />
                </div>
                <div class="p-2 border-l border-gray-300">
                  <input
                    type="number"
                    step="0.01"
                    id="hr_sl_balance"
                    class="w-full p-1 border border-gray-300 rounded text-sm bg-gray-50"
                    placeholder="0.00"
                    readonly
                  />
                </div>
              </div>
            </div>

            <p class="text-xs text-gray-500 mt-2">
              Note: Balance will auto-calculate (Total Earned - Less this
              application)
            </p>
          </div>

          <div class="form-group mt-4">
            <label class="font-semibold">HR Certifier Signature</label>
            <p class="text-xs text-gray-500 mt-2">
              Upload the HR certifier signature for Section 7.A.
            </p>

            <div class="mt-3">
              <label class="text-sm font-medium">Signed by (auto-filled)</label>
              <input
                id="hr_certifier_name"
                type="text"
                class="w-full mt-1 p-2 border border-gray-300 rounded text-sm"
                readonly
              />
            </div>

            <!-- Existing Signature Display -->
            <div
              id="hr_existing_signature_container"
              style="display: none; margin-top: 1rem"
            >
              <p
                style="
                  color: #059669;
                  font-size: 0.875rem;
                  margin-bottom: 0.5rem;
                "
              >
                ✓ You have an existing signature on file
              </p>
              <div
                style="
                  border: 2px solid #d1d5db;
                  border-radius: 0.5rem;
                  padding: 1rem;
                  background: #f9fafb;
                "
              >
                <img
                  id="hr_existing_signature_img"
                  src=""
                  alt="Existing Signature"
                  style="max-width: 300px; max-height: 100px; display: block"
                />
              </div>
              <p style="color: #6b7280; font-size: 0.75rem; margin-top: 0.5rem">
                Upload a new file below to replace it (old signature will be
                deleted)
              </p>
            </div>

            <div class="mt-3">
              <label class="text-sm font-medium"
                >Upload HR Certifier E-Signature</label
              >
              <input
                id="hr_sig_certifier"
                type="file"
                accept="image/*"
                class="mt-2 w-full"
              />
              <p
                style="color: #6b7280; font-size: 0.75rem; margin-top: 0.25rem"
              >
                Upload once and it will be saved to your account for future use
              </p>
            </div>
          </div>
        </div>

        <div class="form-buttons" style="margin-top: 1.5rem">
          <button
            id="hr-save-btn"
            class="initiate-button"
            style="background-color: #10b981; padding: 0.75rem 1.5rem"
          >
            Save & Preview Form
          </button>
          <button
            id="hr-cancel-btn"
            class="cancel-button"
            style="
              background-color: #6b7280;
              margin-left: 0.5rem;
              padding: 0.75rem 1.5rem;
            "
          >
            Cancel
          </button>
        </div>
      </div>
    </div>

    <script>
      // Load existing HR signature
      async function loadExistingHRSignature() {
        try {
          const response = await fetch("../api/get_hr_signature.php");
          const data = await response.json();

          if (data.success && data.signature_path) {
            // Show existing signature
            document.getElementById(
              "hr_existing_signature_container"
            ).style.display = "block";
            document.getElementById("hr_existing_signature_img").src =
              "../" + data.signature_path + "?t=" + Date.now();
          } else {
            // No existing signature
            document.getElementById(
              "hr_existing_signature_container"
            ).style.display = "none";
          }
        } catch (error) {
          console.error("Error loading HR signature:", error);
          document.getElementById(
            "hr_existing_signature_container"
          ).style.display = "none";
        }
      }

      // HR Section modal logic
      function openHRSectionModal(id) {
        window._editingHRRequestId = id;
        const modal = document.getElementById("hr-section-modal");

        // Clear the HR Section 7 signature field
        const fi = document.getElementById("hr_sig_certifier");
        if (fi) fi.value = null;

        // Clear leave credits fields
        document.getElementById("hr_certification_date").value = "";
        document.getElementById("hr_vl_total_earned").value = "";
        document.getElementById("hr_vl_less_application").value = "";
        document.getElementById("hr_vl_balance").value = "";
        document.getElementById("hr_sl_total_earned").value = "";
        document.getElementById("hr_sl_less_application").value = "";
        document.getElementById("hr_sl_balance").value = "";

        // Try to prefill from loaded requests data
        try {
          const req = window._leaveRequests.find(
            (r) => String(r.id) === String(id)
          );
          if (req) {
            // Prefill leave credits if exists
            if (req.certification_date) {
              document.getElementById("hr_certification_date").value =
                req.certification_date;
            }
            if (req.vl_total_earned) {
              document.getElementById("hr_vl_total_earned").value = parseFloat(
                req.vl_total_earned
              );
            }
            if (req.vl_less_this_application) {
              document.getElementById("hr_vl_less_application").value =
                parseFloat(req.vl_less_this_application);
            }
            if (req.vl_balance) {
              document.getElementById("hr_vl_balance").value = parseFloat(
                req.vl_balance
              );
            }
            if (req.sl_total_earned) {
              document.getElementById("hr_sl_total_earned").value = parseFloat(
                req.sl_total_earned
              );
            }
            if (req.sl_less_this_application) {
              document.getElementById("hr_sl_less_application").value =
                parseFloat(req.sl_less_this_application);
            }
            if (req.sl_balance) {
              document.getElementById("hr_sl_balance").value = parseFloat(
                req.sl_balance
              );
            }
          }
        } catch (e) {
          console.error(e);
        }

        // Load existing HR signature if available
        loadExistingHRSignature();

        // Attempt to fetch current HR account name and cache it for the save
        window._hrCurrentName = "";
        try {
          fetch("../api/current_user.php")
            .then((r) => r.json())
            .then((js) => {
              let u = null;
              if (!js) return;
              if (js.user) u = js.user;
              else if (js.logged_in || js.firstname) u = js;
              if (u) {
                const name = [u.lastname || "", u.firstname || "", u.mi || ""]
                  .map((s) => s.trim())
                  .filter(Boolean)
                  .join(" ");
                window._hrCurrentName = name || u.name || "";
                document.getElementById("hr_certifier_name").value =
                  window._hrCurrentName.toUpperCase();
                const hdr = document.querySelector(
                  "#hr-section-modal .employee-details"
                );
                if (hdr)
                  hdr.textContent =
                    "Fill out the leave credits certification and upload HR signature. Certifying as: " +
                    (window._hrCurrentName || "HR");
              }
            })
            .catch(() => {});
        } catch (e) {}

        modal.classList.add("show");
      }

      function closeHRSectionModal() {
        const modal = document.getElementById("hr-section-modal");
        modal.classList.remove("show");
        window._editingHRRequestId = null;
      }

      document
        .getElementById("hr-modal-close")
        .addEventListener("click", closeHRSectionModal);
      document
        .getElementById("hr-cancel-btn")
        .addEventListener("click", closeHRSectionModal);

      // Auto-calculate balance for VL
      document
        .getElementById("hr_vl_total_earned")
        .addEventListener("input", calculateVLBalance);
      document
        .getElementById("hr_vl_less_application")
        .addEventListener("input", calculateVLBalance);

      function calculateVLBalance() {
        const total =
          parseFloat(document.getElementById("hr_vl_total_earned").value) || 0;
        const less =
          parseFloat(document.getElementById("hr_vl_less_application").value) ||
          0;
        const balance = total - less;
        document.getElementById("hr_vl_balance").value = balance.toFixed(2);
      }

      // Auto-calculate balance for SL
      document
        .getElementById("hr_sl_total_earned")
        .addEventListener("input", calculateSLBalance);
      document
        .getElementById("hr_sl_less_application")
        .addEventListener("input", calculateSLBalance);

      function calculateSLBalance() {
        const total =
          parseFloat(document.getElementById("hr_sl_total_earned").value) || 0;
        const less =
          parseFloat(document.getElementById("hr_sl_less_application").value) ||
          0;
        const balance = total - less;
        document.getElementById("hr_sl_balance").value = balance.toFixed(2);
      }

      document
        .getElementById("hr-save-btn")
        .addEventListener("click", async function () {
          const id = window._editingHRRequestId;
          if (!id) return alert("No request selected");

          // Get leave credits values
          const certificationDate = document.getElementById(
            "hr_certification_date"
          ).value;
          const vlTotalEarned =
            document.getElementById("hr_vl_total_earned").value;
          const vlLessApplication = document.getElementById(
            "hr_vl_less_application"
          ).value;
          const vlBalance = document.getElementById("hr_vl_balance").value;
          const slTotalEarned =
            document.getElementById("hr_sl_total_earned").value;
          const slLessApplication = document.getElementById(
            "hr_sl_less_application"
          ).value;
          const slBalance = document.getElementById("hr_sl_balance").value;

          // Validate: certification date is required
          if (!certificationDate) {
            return alert("Please enter the certification date (As of)");
          }

          // Build section7: include HR certifier name and hardcoded final official
          const section7 = {
            certifier_name: window._hrCurrentName || "",
            final_official: "ATTY. MARIA CONCEPCION R. HERNANDEZ-BELOSO",
          };

          // Collect signature file (HR certifier only)
          const toDataURI = (file) =>
            new Promise((res, rej) => {
              const r = new FileReader();
              r.onload = () => res(r.result);
              r.onerror = () => rej();
              r.readAsDataURL(file);
            });

          const sigs = [];
          let hrSignatureDataUri = null;

          // HR Certifier signature (7A)
          const fiCert = document.getElementById("hr_sig_certifier");
          if (fiCert && fiCert.files && fiCert.files[0]) {
            try {
              const dataUri = await toDataURI(fiCert.files[0]);
              sigs.push({ key: "certifier", data_uri: dataUri });
              hrSignatureDataUri = dataUri; // Save for HR signature table
            } catch (e) {
              /* ignore */
            }
          }

          // Save HR signature to hr_signatures table if uploaded
          if (hrSignatureDataUri) {
            try {
              const sigResp = await fetch("../api/save_hr_signature.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                  signature_data_uri: hrSignatureDataUri,
                }),
              });
              const sigResult = await sigResp.json();
              if (!sigResult.success) {
                console.error("Failed to save HR signature:", sigResult.error);
              }
            } catch (e) {
              console.error("Error saving HR signature:", e);
            }
          }

          const payload = {
            id,
            section7,
            signatures: sigs,
            leave_credits: {
              certification_date: certificationDate,
              vl_total_earned: vlTotalEarned,
              vl_less_this_application: vlLessApplication,
              vl_balance: vlBalance,
              sl_total_earned: slTotalEarned,
              sl_less_this_application: slLessApplication,
              sl_balance: slBalance,
            },
          };

          try {
            const resp = await fetch("../api/update_hr_section7.php", {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify(payload),
            });
            const js = await resp.json();
            if (js && js.success) {
              // Open preview of the civil form so user can confirm the signature placement
              const previewUrl = `../dept_head/civil_form.php?id=${id}&live=1`;
              window.open(previewUrl, "_blank");
              alert(
                "Leave credits and HR signature saved. A preview of the form has been opened in a new tab. Please verify that all information appears correctly before finalizing."
              );
              closeHRSectionModal();
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
                alert("Failed to save HR section: " + errorMsg);
              }
            }
          } catch (e) {
            console.error(e);
            alert("Network error saving HR section");
          }
        });
    </script>
  </body>
</html>
