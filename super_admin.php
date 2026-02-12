<?php
require_once __DIR__ . '/auth_guard.php';
require_role('super_admin');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Super Admin | Database Management</title>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"
    />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
      body {
        font-family: "Inter", sans-serif;
        background: linear-gradient(120deg, #e0e7ff 0%, #f0f4f8 100%);
        margin: 0;
      }
      .container {
        display: flex;
        min-height: 100vh;
      }
      .sidebar {
        width: 280px;
        background: #fff;
        box-shadow: 2px 0 10px rgba(30, 64, 175, 0.07);
        padding: 2rem 1.5rem;
        border-right: 4px solid #2563eb;
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        z-index: 10;
      }
      .main-content {
        flex-grow: 1;
        padding: 3rem 2.5rem;
        margin-left: 280px;
        min-height: 100vh;
      }
      .header-logo {
        display: flex;
        align-items: center;
        margin-bottom: 2rem;
      }
      .header-logo img {
        width: 48px;
        height: 48px;
        margin-right: 14px;
      }
      .header-text {
        font-size: 1.3rem;
        font-weight: 700;
        color: #2563eb;
        letter-spacing: 0.04em;
      }
      h1,
      h2 {
        color: #1e3a8a;
        font-weight: 700;
        margin-top: 2.5rem;
        margin-bottom: 1.5rem;
      }
      h1 {
        font-size: 2.2rem;
        margin-top: 0;
      }
      table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-bottom: 2.5rem;
        background: #fff;
        border-radius: 1.2rem;
        overflow: hidden;
        box-shadow: 0 6px 24px rgba(30, 64, 175, 0.07);
      }
      th,
      td {
        padding: 0.85rem 1rem;
        border-bottom: 1px solid #e5e7eb;
        font-size: 0.98rem;
        line-height: 1.35;
        vertical-align: top;
        white-space: normal; /* allow wrapping */
        word-break: break-word;
        overflow-wrap: anywhere; /* prevent long strings from cutting */
      }
      /* tighten action/status column spacing */
      .action-col,
      .status-col {
        padding-top: 0.6rem;
        padding-bottom: 0.6rem;
      }
      .action-cell .action-btn {
        margin-top: 0;
      }
      th {
        background: #2563eb;
        color: #fff;
        font-weight: 700;
        letter-spacing: 0.03em;
        border-bottom: 2px solid #1e40af;
      }
      tr:last-child td {
        border-bottom: none;
      }
      /* Let the table auto-size based on content and wrap text */
      table { table-layout: auto; }
      /* Accounts table column minimums to avoid cut content at 100% zoom */
      .table-accounts thead th:nth-child(1),
      .table-accounts tbody td:nth-child(1) { min-width: 100px; }
      .table-accounts thead th:nth-child(2),
      .table-accounts tbody td:nth-child(2) { min-width: 150px; }
      .table-accounts thead th:nth-child(3),
      .table-accounts tbody td:nth-child(3) { min-width: 180px; }
      .table-accounts thead th:nth-child(4),
      .table-accounts tbody td:nth-child(4) { min-width: 220px; }
      .table-accounts thead th:nth-child(5),
      .table-accounts tbody td:nth-child(5) { min-width: 120px; }
      .table-accounts thead th:nth-child(6),
      .table-accounts tbody td:nth-child(6) { min-width: 130px; }
      .table-accounts thead th:nth-child(7),
      .table-accounts tbody td:nth-child(7) { min-width: 110px; }
      .table-accounts thead th:nth-child(8),
      .table-accounts tbody td:nth-child(8) { min-width: 130px; }

      /* Events table column minimums */
      .table-events thead th:nth-child(1),
      .table-events tbody td:nth-child(1) { min-width: 160px; }
      .table-events thead th:nth-child(2),
      .table-events tbody td:nth-child(2) { min-width: 110px; }
      .table-events thead th:nth-child(3),
      .table-events tbody td:nth-child(3) { min-width: 110px; }
      .table-events thead th:nth-child(4),
      .table-events tbody td:nth-child(4) { min-width: 140px; }
      .table-events thead th:nth-child(5),
      .table-events tbody td:nth-child(5) { min-width: 200px; }
      .table-events thead th:nth-child(6),
      .table-events tbody td:nth-child(6) { min-width: 130px; }
      .action-btn {
        padding: 0.5rem 1.2rem;
        border-radius: 0.5rem;
        font-size: 1rem;
        font-weight: 600;
        margin-right: 0.5rem;
        border: none;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(30, 64, 175, 0.07);
        transition: background 0.2s, color 0.2s, box-shadow 0.2s;
      }
      .approve {
        background: #16a34a;
        color: #fff;
      }
      .approve:hover {
        background: #15803d;
      }
      .decline {
        background: #dc2626;
        color: #fff;
      }
      .decline:hover {
        background: #b91c1c;
      }
      .edit {
        background: #2563eb;
        color: #fff;
      }
      .edit:hover {
        background: #1e40af;
      }
      .delete {
        background: #ef4444;
        color: #fff;
      }
      .delete:hover {
        background: #b91c1c;
      }
      .sign-out {
        margin-top: 2rem;
      }
      .sign-out a {
        color: #dc2626;
        text-decoration: none;
        font-weight: 600;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: color 0.2s;
      }
      .sign-out a:hover {
        color: #b91c1c;
      }
      /* Modal styles */
      .modal {
        display: none;
        position: fixed;
        z-index: 100;
        left: 0;
        top: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(30, 58, 138, 0.18);
        align-items: center;
        justify-content: center;
      }
      .modal.active {
        display: flex;
      }
      .modal-content {
        background: #fff;
        border-radius: 1.2rem;
        box-shadow: 0 8px 32px rgba(30, 64, 175, 0.13);
        padding: 2.5rem 2rem 2rem 2rem;
        min-width: 340px;
        max-width: 95vw;
        min-height: 120px;
        max-height: 90vh; /* limit modal height */
        overflow-y: auto; /* allow scrolling inside modal */
        position: relative;
        animation: modalIn 0.18s cubic-bezier(0.4, 2, 0.6, 1) 1;
      }
      @keyframes modalIn {
        from {
          transform: translateY(40px) scale(0.97);
          opacity: 0;
        }
        to {
          transform: none;
          opacity: 1;
        }
      }
      .modal-close {
        position: absolute;
        top: 1.2rem;
        right: 1.2rem;
        background: none;
        border: none;
        font-size: 1.5rem;
        color: #9ca3af;
        cursor: pointer;
        transition: color 0.2s;
      }
      .modal-close:hover {
        color: #ef4444;
      }
      .modal-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: #1e3a8a;
        margin-bottom: 1.2rem;
      }
      .modal-actions {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
        justify-content: flex-end;
      }
      /* Make html/body take full height so containers can use 100vh reliably */
      html,
      body {
        height: 100%;
      }

      /* Ensure the page fills the viewport and main content adapts */
      .flex.min-h-screen {
        min-height: 100vh;
      }
      /* Sidebar stays fixed; only main scrolls */
      aside {
        position: fixed;
        top: 0;
        left: 0;
        width: 256px; /* match Tailwind w-64 */
        height: 100vh;
        overflow: hidden; /* prevent sidebar from scrolling */
      }
      main {
        margin-left: 256px; /* create space for fixed sidebar */
        height: 100vh; /* fill viewport height */
        overflow-y: auto; /* scroll only the main content */
      }

      /* Action buttons: use a flexible wrapping container so they don't overlap */
      .action-group {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        position: relative; /* ensure menus/buttons can layer */
        z-index: 2;
      }
      /* Action cell helper to vertically align actions with row content */
      .action-cell {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        position: relative;
      }
      .action-btn {
        padding: 0.35rem 0.8rem;
        border-radius: 0.55rem;
        font-size: 0.92rem;
        font-weight: 600;
        margin: 0;
        border: none;
        cursor: pointer;
        box-shadow: 0 1px 6px rgba(30, 64, 175, 0.06);
        transition: background 0.2s, color 0.2s, box-shadow 0.2s;
        white-space: normal;
      }

      /* Wizard styles for multi-step Add Account */
      .wizard-step {
        display: none;
      }
      .wizard-step.active {
        display: block;
      }
      .wizard-progress {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1rem;
      }
      .wizard-dot {
        width: 10px;
        height: 10px;
        border-radius: 999px;
        background: #e6eefc;
      }
      .wizard-dot.active {
        background: #2563eb;
      }

      /* Tweak padding and size on smaller screens */
      @media (max-width: 768px) {
        th,
        td {
          padding: 0.6rem 0.8rem;
          font-size: 0.92rem;
        }
        .action-btn {
          padding: 0.35rem 0.6rem;
          font-size: 0.85rem;
        }
        .modal-content {
          padding: 1rem;
        }
        /* On small screens, let the sidebar stack and page scroll normally */
        aside {
          position: relative;
          width: 100%;
          height: auto;
          overflow: visible;
        }
        main {
          margin-left: 0;
          height: auto;
          padding: 1rem;
          overflow: visible;
        }
      }
      /* Search suggestions */
      .search-wrap {
        position: relative;
      }
      .suggestions {
        position: absolute;
        z-index: 50;
        left: 0;
        right: 0;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin-top: 6px;
        box-shadow: 0 6px 24px rgba(30, 64, 175, 0.08);
        max-height: 260px;
        overflow: auto;
      }
      .suggestion-item {
        padding: 8px 10px;
        cursor: pointer;
        border-bottom: 1px solid #f3f4f6;
      }
      .suggestion-item:hover {
        background: #f3f4f6;
      }
      .suggestion-item {
        margin: 2px 0;
      }
      .search-input {
        min-width: 220px;
      }
      .actions-menu {
        pointer-events: auto;
      }
      .actions-menu {
        z-index: 9999 !important;
      }
    </style>
    <!-- Modal for editing (structure, JS will fill content) -->
    <div id="modal" class="modal">
      <div class="modal-content">
        <button class="modal-close" onclick="closeModal()">
          <i class="fas fa-times"></i>
        </button>
        <div id="modal-body"></div>
        <div class="modal-actions" id="modal-actions"></div>
      </div>
    </div>
  </head>
  <body>
    <div class="flex min-h-screen bg-[#f6f8fb]">
      <!-- Sidebar -->
      <aside
        class="flex flex-col w-64 bg-white border-r border-blue-100 shadow-sm min-h-screen"
      >
        <div class="flex items-center gap-3 px-6 py-6 border-b border-blue-100">
          <img
            src="assets/logo.png"
            class="w-12 h-12 rounded-full border-2 border-blue-400"
            alt="Logo"
          />
          <div class="font-bold text-lg text-blue-900 leading-tight">
            Bayan ng Mabini
          </div>
        </div>
        <nav class="flex-1 px-4 py-6">
          <ul class="space-y-2">
            <li>
              <button
                class="w-full flex items-center gap-3 px-4 py-2 rounded-lg text-blue-700 bg-blue-50 font-semibold focus:outline-none"
                id="tab-accounts-btn"
              >
                <i class="fas fa-users"></i> Accounts
              </button>
            </li>
            <li>
              <button
                class="w-full flex items-center gap-3 px-4 py-2 rounded-lg text-gray-700 hover:bg-blue-50 font-semibold focus:outline-none"
                id="tab-events-btn"
              >
                <i class="fas fa-calendar-alt"></i> Events
              </button>
            </li>
          </ul>
        </nav>
        <div class="px-6 pb-6 mt-auto">
          <a
            href="hr/logout.php"
            class="flex items-center gap-2 text-red-600 font-semibold hover:underline"
          >
            <i class="fas fa-sign-out-alt"></i> Sign Out
          </a>
        </div>
      </aside>
      <!-- Main Content -->
      <main class="flex-1 p-8">
        <!-- Topbar -->
        <div class="flex items-center justify-between mb-8">
          <div class="text-2xl font-bold text-blue-900">
            Super Admin Dashboard
          </div>
          <div class="flex items-center gap-4">
            <span class="hidden md:block font-medium text-blue-900"
              >Super Admin</span
            >
            <img
              src="assets/logo.png"
              class="w-10 h-10 rounded-full border-2 border-blue-400"
              alt="Profile"
            />
          </div>
        </div>
        <!-- Tabs are handled in the sidebar; top tabs removed to avoid duplication -->
        <!-- Accounts Tab -->
        <section id="accounts-section">
          <div class="bg-white rounded-xl shadow p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="text-xl font-bold text-blue-900">
                Account Management
              </div>
              <div class="flex items-center gap-3">
                <div class="search-wrap" style="min-width: 220px">
                  <input
                    id="account-search"
                    class="search-input w-64 border rounded px-3 py-2"
                    placeholder="Search accounts (name)"
                    autocomplete="off"
                  />
                  <div
                    id="search-suggestions"
                    class="suggestions"
                    style="display: none"
                  ></div>
                </div>
                <button
                  class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold flex items-center gap-2"
                  id="add-account-btn"
                >
                  <i class="fas fa-user-plus"></i> Add Account
                </button>
              </div>
            </div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-blue-100 table-accounts">
                <thead class="bg-blue-50">
                  <tr>
                    <th
                      class="px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider"
                    >
                      Employee ID
                    </th>
                    <th
                      class="px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider"
                    >
                      Name
                    </th>
                    <th
                      class="px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider"
                    >
                      Email
                    </th>
                    <th
                      class="px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider"
                    >
                      Department
                    </th>
                    <th
                      class="px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider"
                    >
                      Position
                    </th>
                    <th
                      class="px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider"
                    >
                      Role
                    </th>
                    <th
                      class="px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider"
                    >
                      Status
                    </th>
                    <th
                      class="px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider"
                    >
                      Action
                    </th>
                  </tr>
                </thead>
                <tbody
                  id="accounts-table-body"
                  class="bg-white divide-y divide-white"
                >
                  <!-- JS will render rows here -->
                </tbody>
              </table>
            </div>
          </div>
        </section>
        <!-- Events Tab -->
        <section id="events-section" style="display: none">
          <div class="bg-white rounded-xl shadow p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="text-xl font-bold text-blue-900">
                Event Management
              </div>
              <button
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold flex items-center gap-2"
                id="add-event-btn"
                style="display: none"
              >
                <i class="fas fa-calendar-plus"></i> Add Event
              </button>
            </div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-blue-100 table-events">
                <thead class="bg-blue-50">
                  <tr>
                    <th
                      class="px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider"
                    >
                      Title
                    </th>
                    <th
                      class="px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider"
                    >
                      Date
                    </th>
                    <th
                      class="px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider"
                    >
                      Time
                    </th>
                    <th
                      class="px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider"
                    >
                      Location
                    </th>
                    <th
                      class="px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider"
                    >
                      Description
                    </th>
                    <th
                      class="px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider"
                    >
                      Action
                    </th>
                  </tr>
                </thead>
                <tbody
                  id="events-table-body"
                  class="bg-white divide-y divide-white"
                >
                  <!-- JS will render rows here -->
                </tbody>
              </table>
            </div>
          </div>
        </section>
      </main>
    </div>
    <!-- CRUD Modal -->
    <div id="crud-modal" class="modal">
      <div class="modal-content max-w-lg w-full">
        <button class="modal-close" onclick="closeCrudModal()">
          <i class="fas fa-times"></i>
        </button>
        <div id="crud-modal-body"></div>
        <div class="modal-actions" id="crud-modal-actions"></div>
      </div>
    </div>
    <script>
      // Modal logic for CRUD
      function openCrudModal(title, bodyHtml, actionsHtml) {
        document.getElementById("crud-modal-body").innerHTML =
          `<div class='modal-title'>${title}</div>` + bodyHtml;
        document.getElementById("crud-modal-actions").innerHTML =
          actionsHtml || "";
        document.getElementById("crud-modal").classList.add("active");
      }
      function closeCrudModal() {
        document.getElementById("crud-modal").classList.remove("active");
      }
      window.closeCrudModal = closeCrudModal;

      // State
      let users = [],
        events = [];
      async function fetchData() {
        try {
          const [usersData, eventsData] = await Promise.all([
            fetch("api/super_admin_users.php").then((r) => r.json()),
            fetch("api/super_admin_events.php").then((r) => r.json()),
          ]);
          users = usersData;
          events = eventsData;
          renderAccounts(users);
          renderEvents(events);
        } catch (err) {
          console.error("Error fetching data:", err);
          // Don't block the UI, just log the error
        }
      }
      function renderAccounts(users) {
        const tbody = document.getElementById("accounts-table-body");
        tbody.innerHTML = "";
        users.forEach((user) => {
          const statusLabel = user.status
            ? user.status.charAt(0).toUpperCase() + user.status.slice(1)
            : "";
          const isPending =
            (user.status || "").toString().toLowerCase() === "pending";
          const isArchived = Number(user.is_archived || 0) === 1;
          tbody.innerHTML += `<tr>
          <td>${user.employee_id ?? ""}</td>
          <td>${user.lastname}, ${user.firstname} ${user.mi || ""}</td>
          <td>${user.email}</td>
          <td>${user.department}</td>
          <td>${user.position}</td>
          <td>${user.role || "N/A"}</td>
          <td>${statusLabel} ${
            isArchived
              ? '<span class="ml-2 text-xs px-2 py-1 rounded bg-gray-200 text-gray-700">Archived</span>'
              : ""
          }</td>
          <td>
            <div class="action-cell">
              <button class="action-btn edit" onclick="toggleActionsMenu(event, ${
                user.id
              })">Actions ▾</button>
              <div id="actions-menu-${
                user.id
              }" class="actions-menu" style="display:none; position:absolute; right:0; top:38px; background:#fff; border:1px solid #e5e7eb; border-radius:8px; box-shadow:0 8px 32px rgba(30,64,175,0.08); z-index:60;">
                <div style="padding:8px 10px; display:flex; flex-direction:column; gap:6px; min-width:160px;">
                  <button class='action-btn edit' style='width:100%; text-align:left;' onclick='editUser(${
                    user.id
                  })'>Edit</button>
                  ${
                    isPending
                      ? `<button class='action-btn approve' style='width:100%; text-align:left;' onclick='approveUser(${user.id})'>Approve</button>`
                      : ""
                  }
                  ${
                    isPending
                      ? `<button class='action-btn decline' style='width:100%; text-align:left;' onclick='declineUser(${user.id})'>Decline</button>`
                      : ""
                  }
                  ${
                    isArchived
                      ? `<button class='action-btn approve' style='width:100%; text-align:left;' onclick='restoreUser(${user.id})'>Restore</button>
                         <button class='action-btn delete' style='width:100%; text-align:left;' onclick='deleteUserPermanent(${user.id})'>Delete</button>`
                      : `<button class='action-btn delete' style='width:100%; text-align:left;' onclick='archiveUser(${user.id})'>Archive</button>`
                  }
                </div>
              </div>
            </div>
          </td>
        </tr>`;
        });
      }
      // CRUD logic for accounts
      document.getElementById("add-account-btn").onclick = function () {
        // Build a 3-step wizard form for adding account to avoid overcrowding
        const wizardHtml = `
        <div class='wizard-progress'>
          <div class='wizard-dot active' data-step='1'></div>
          <div class='wizard-dot' data-step='2'></div>
          <div class='wizard-dot' data-step='3'></div>
        </div>
        <form id='add-account-form' class='space-y-4'>
          <div class='wizard-step active' data-step='1'>
            <div><label class='block text-sm font-medium'>Last Name</label><input name='lastname' class='w-full border rounded px-3 py-2' required pattern='[A-Za-z \\-\']+' title='Letters, spaces, hyphen (-) and apostrophe (\') only'></div>
            <div><label class='block text-sm font-medium'>First Name</label><input name='firstname' class='w-full border rounded px-3 py-2' required pattern='[A-Za-z \\-\']+' title='Letters, spaces, hyphen (-) and apostrophe (\') only'></div>
            <div><label class='block text-sm font-medium'>Middle Initial</label><input name='mi' maxlength='1' class='w-full border rounded px-3 py-2' pattern='[A-Za-z]' title='Single letter only'></div>
          </div>
          <div class='wizard-step' data-step='2'>
            <div><label class='block text-sm font-medium'>Department</label>
              <select name='department' class='w-full border rounded px-3 py-2' required>
                <option value=''>Select Department</option>
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
            </div>
            <div><label class='block text-sm font-medium'>Position</label>
              <select name='position' id='add-position' class='w-full border rounded px-3 py-2' required>
                <option value=''>Select Position</option>
                <option value='Permanent'>Permanent</option>
                <option value='Casual'>Casual</option>
                <option value='JO'>JO</option>
                <option value='OJT'>OJT</option>
              </select>
            </div>
            <div><label class='block text-sm font-medium'>Contact No.</label><input name='contact_no' class='w-full border rounded px-3 py-2' placeholder='0917xxxxxxx' inputmode='numeric' pattern='09[0-9]{9}' maxlength='11' title='Must start with 09 and be 11 digits' oninput="this.value=this.value.replace(/[^0-9]/g,'')" /></div>
            <div><label class='block text-sm font-medium'>Gender</label>
              <select name='gender' class='w-full border rounded px-3 py-2' required>
                <option value=''>Select Gender</option>
                <option value='M'>Male</option>
                <option value='F'>Female</option>
              </select>
            </div>
          </div>
          <div class='wizard-step' data-step='3'>
            <div><label class='block text-sm font-medium'>Employee ID</label><input name='employee_id' class='w-full border rounded px-3 py-2' placeholder='e.g., EMP2025-0001' required pattern='[A-Za-z0-9\-]+' title='Enter unique Employee ID'></div>
            <div><label class='block text-sm font-medium'>Role</label>
              <select name='role' id='add-role' class='w-full border rounded px-3 py-2' required>
                <option value='employee'>Employee</option>
                <option value='department_head' data-requires='Permanent,Casual'>Department Head</option>
                <option value='hr' data-requires='Permanent,Casual'>HR</option>
              </select>
            </div>
            <div><label class='block text-sm font-medium'>Status</label><select name='status' class='w-full border rounded px-3 py-2'><option value='pending'>Pending</option><option value='approved'>Approved</option><option value='declined'>Declined</option></select></div>
            <div><label class='block text-sm font-medium'>Email</label><input name='email' type='email' class='w-full border rounded px-3 py-2' required></div>
            <div><label class='block text-sm font-medium'>Password</label><input name='password' type='password' class='w-full border rounded px-3 py-2' required></div>
          </div>
        </form>
        <div class='flex items-center justify-between mt-4'>
          <div>
            <button id='wizard-back' class='action-btn' style='display:none'>Back</button>
          </div>
          <div>
            <button id='wizard-next' class='action-btn edit'>Next</button>
            <button id='wizard-submit' class='action-btn edit' style='display:none'>Add</button>
          </div>
        </div>
      `;

        openCrudModal("Add Account", wizardHtml, "");

        // Wizard behavior
        let currentStep = 1;
        const totalSteps = 3;
        const setStep = (n) => {
          currentStep = n;
          document
            .querySelectorAll(".wizard-step")
            .forEach((el) => el.classList.remove("active"));
          document
            .querySelectorAll(".wizard-step[data-step]")
            [n - 1].classList.add("active");
          document.querySelectorAll(".wizard-dot").forEach((d, i) => {
            d.classList.toggle("active", i < n);
          });
          document.getElementById("wizard-back").style.display =
            n === 1 ? "none" : "";
          document.getElementById("wizard-next").style.display =
            n === totalSteps ? "none" : "";
          document.getElementById("wizard-submit").style.display =
            n === totalSteps ? "" : "none";
        };
        // attach handlers (use setTimeout to ensure DOM inside modal exists)
        setTimeout(() => {
          // Position-based role filtering for add form
          const addPositionSelect = document.getElementById('add-position');
          const addRoleSelect = document.getElementById('add-role');
          
          function filterAddRoles() {
            const position = addPositionSelect.value;
            const roleOptions = addRoleSelect.querySelectorAll('option');
            
            if (position === 'OJT' || position === 'JO') {
              roleOptions.forEach(opt => {
                if (opt.value === 'employee') {
                  opt.style.display = '';
                  opt.disabled = false;
                } else {
                  opt.style.display = 'none';
                  opt.disabled = true;
                }
              });
              addRoleSelect.value = 'employee';
              addRoleSelect.disabled = true;
            } else if (position === 'Casual') {
              // For Casual: only allow Department Head or Employee, NO HR
              roleOptions.forEach(opt => {
                if (opt.value === 'employee' || opt.value === 'department_head') {
                  opt.style.display = '';
                  opt.disabled = false;
                } else if (opt.value === 'hr') {
                  opt.style.display = 'none';
                  opt.disabled = true;
                }
              });
              // Reset to employee if currently selected role is hr
              if (addRoleSelect.value === 'hr') {
                addRoleSelect.value = 'employee';
              }
              addRoleSelect.disabled = false;
            } else if (position === 'Permanent') {
              // For Permanent: allow all roles
              roleOptions.forEach(opt => {
                opt.style.display = '';
                opt.disabled = false;
              });
              addRoleSelect.disabled = false;
            }
          }
          
          addPositionSelect.addEventListener('change', filterAddRoles);
          filterAddRoles();
          
          document
            .getElementById("wizard-next")
            .addEventListener("click", (e) => {
              e.preventDefault();
              // simple validation for required inputs in current step
              const stepEl = document.querySelector(".wizard-step.active");
              const invalid = Array.from(
                stepEl.querySelectorAll("[required]")
              ).some((i) => !i.value.trim());
              if (invalid) {
                // highlight or simple alert
                alert("Please fill required fields before continuing.");
                return;
              }
              // additional format validation per step
              const nameRegex = /^[A-Za-z \-']+$/;
              const phoneRegex = /^09\d{9}$/;
              const stepNum = Number(stepEl.getAttribute("data-step"));
              if (stepNum === 1) {
                const ln = stepEl
                  .querySelector("input[name='lastname']")
                  .value.trim();
                const fn = stepEl
                  .querySelector("input[name='firstname']")
                  .value.trim();
                const mi = stepEl
                  .querySelector("input[name='mi']")
                  .value.trim();
                if (!nameRegex.test(ln) || !nameRegex.test(fn)) {
                  alert("Name fields must be letters only.");
                  return;
                }
                if (mi && !/^[A-Za-z]$/.test(mi)) {
                  alert("Middle initial must be a single letter.");
                  return;
                }
              } else if (stepNum === 2) {
                const phone = stepEl
                  .querySelector("input[name='contact_no']")
                  .value.trim();
                if (phone && !phoneRegex.test(phone)) {
                  alert("Contact number must start with 09 and be 11 digits.");
                  return;
                }
              }
              setStep(Math.min(totalSteps, currentStep + 1));
            });
          document
            .getElementById("wizard-back")
            .addEventListener("click", (e) => {
              e.preventDefault();
              setStep(Math.max(1, currentStep - 1));
            });
          document
            .getElementById("wizard-submit")
            .addEventListener("click", (e) => {
              e.preventDefault();
              const form = document.getElementById("add-account-form");
              const data = Object.fromEntries(new FormData(form).entries());
              // final validation
              const nameRegex2 = /^[A-Za-z \-']+$/;
              const phoneRegex2 = /^09\d{9}$/;
              if (
                !nameRegex2.test(data.lastname) ||
                !nameRegex2.test(data.firstname)
              ) {
                alert("Name fields must be letters only.");
                return;
              }
              if (data.mi && !/^[A-Za-z]$/.test(data.mi)) {
                alert("Middle initial must be a single letter.");
                return;
              }
              if (data.contact_no && !phoneRegex2.test(data.contact_no)) {
                alert("Contact number must start with 09 and be 11 digits.");
                return;
              }
              // Enforce only one Department Head per department (client-side precheck)
              const ensureDeptHeadAvailability = async () => {
                if ((data.role || "") === "department_head") {
                  try {
                    const r = await fetch("api/dept_heads.php");
                    const heads = await r.json();
                    const exists =
                      Array.isArray(heads) &&
                      heads.some(
                        (h) => (h.department || "") === (data.department || "")
                      );
                    if (exists) {
                      alert(
                        "A Department Head is already assigned to this department."
                      );
                      return false;
                    }
                  } catch (e) {
                    alert(
                      "Unable to verify this department’s head right now. Please try again later."
                    );
                    return false;
                  }
                }
                return true;
              };
              ensureDeptHeadAvailability().then((ok) => {
                if (!ok) return;
                // keep original fetch API and payload
                fetch("api/super_admin_users.php", {
                  method: "POST",
                  headers: { "Content-Type": "application/json" },
                  body: JSON.stringify({ action: "add", ...data }),
                })
                  .then(async (r) => {
                    let res = null;
                    try {
                      res = await r.json();
                    } catch (_) {}
                    if (!r.ok) {
                      alert(
                        res?.error ||
                          "Validation failed. Please check the inputs."
                      );
                      return Promise.reject(res);
                    }
                    return res;
                  })
                  .then((res) => {
                    // Show the generated Employee ID when available
                    if (res && (res.employee_id || res.emp_id)) {
                      const empId = res.employee_id || res.emp_id;
                      alert("Account added successfully! Employee ID: " + empId);
                    } else if (
                      res &&
                      (res.id || res.user_id || res.insert_id)
                    ) {
                      // Fallback to numeric id display if API doesn’t return employee_id
                      const newId = res.id || res.user_id || res.insert_id;
                      alert("Account added successfully! ID: " + newId);
                    } else if (res && res.success) {
                      alert("Account added successfully!");
                    }
                    closeCrudModal();
                    fetchData();
                  })
                  .catch((err) => {
                    console.error("Error:", err);
                  });
              });
            });
        }, 80);
      };
      window.submitAddAccount = function () {
        const form = document.getElementById("add-account-form");
        const data = Object.fromEntries(new FormData(form).entries());
        fetch("api/super_admin_users.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ action: "add", ...data }),
        })
          .then((r) => {
            if (!r.ok) {
              return r.json().catch(() => null).then(err => {
                throw new Error(err?.error || "Failed to add account");
              });
            }
            return r.json().catch(() => null);
          })
          .then((res) => {
            if (res && res.success) {
              if (res.employee_id || res.emp_id) {
                const empId = res.employee_id || res.emp_id;
                alert("Account added successfully! Employee ID: " + empId);
              } else if (res.id || res.user_id || res.insert_id) {
                const newId = res.id || res.user_id || res.insert_id;
                alert("Account added successfully! ID: " + newId);
              } else {
                alert("Account added successfully!");
              }
            }
            closeCrudModal();
            return fetchData();
          })
          .catch((err) => {
            console.error("Error adding account:", err);
            // Close modal and refresh data even on error
            closeCrudModal();
            fetchData();
          });
      };
      window.editUser = function (id) {
        // Build a 3-step wizard for editing (split fields to avoid overflow)
        const user = users.find((u) => u.id == id) || {};
        const wizardHtml = `
          <div class='wizard-progress'>
            <div class='wizard-dot active' data-step='1'></div>
            <div class='wizard-dot' data-step='2'></div>
            <div class='wizard-dot' data-step='3'></div>
          </div>
          <form id='edit-account-form' class='space-y-4'>
            <div class='wizard-step active' data-step='1'>
              <div><label class='block text-sm font-medium'>Last Name</label><input name='lastname' class='w-full border rounded px-3 py-2' value='${
                user.lastname || ""
              }' required></div>
              <div><label class='block text-sm font-medium'>First Name</label><input name='firstname' class='w-full border rounded px-3 py-2' value='${
                user.firstname || ""
              }' required></div>
              <div><label class='block text-sm font-medium'>Middle Initial</label><input name='mi' maxlength='1' class='w-full border rounded px-3 py-2' value='${
                user.mi || ""
              }'></div>
            </div>
            <div class='wizard-step' data-step='2'>
              <div><label class='block text-sm font-medium'>Department</label>
                <select name='department' class='w-full border rounded px-3 py-2' required>
                  <option value=''>Select Department</option>
                  <option value="Office of the Municipal Mayor" ${
                    user.department === "Office of the Municipal Mayor"
                      ? "selected"
                      : ""
                  }>Office of the Municipal Mayor</option>
                  <option value="Office of the Municipal Vice Mayor" ${
                    user.department === "Office of the Municipal Vice Mayor"
                      ? "selected"
                      : ""
                  }>Office of the Municipal Vice Mayor</option>
                  <option value="Office of the Sangguiniang Bayan" ${
                    user.department === "Office of the Sangguiniang Bayan"
                      ? "selected"
                      : ""
                  }>Office of the Sangguiniang Bayan</option>
                  <option value="Office of the Municipal Administrator" ${
                    user.department === "Office of the Municipal Administrator"
                      ? "selected"
                      : ""
                  }>Office of the Municipal Administrator</option>
                  <option value="Office of the Municipal Engineer" ${
                    user.department === "Office of the Municipal Engineer"
                      ? "selected"
                      : ""
                  }>Office of the Municipal Engineer</option>
                  <option value="Office of the MPDC" ${
                    user.department === "Office of the MPDC" ? "selected" : ""
                  }>Office of the MPDC</option>
                  <option value="Office of the Municipal Budget Officer" ${
                    user.department === "Office of the Municipal Budget Officer"
                      ? "selected"
                      : ""
                  }>Office of the Municipal Budget Officer</option>
                  <option value="Office of the Municipal Assessor" ${
                    user.department === "Office of the Municipal Assessor"
                      ? "selected"
                      : ""
                  }>Office of the Municipal Assessor</option>
                  <option value="Office of the Municipal Accountant" ${
                    user.department === "Office of the Municipal Accountant"
                      ? "selected"
                      : ""
                  }>Office of the Municipal Accountant</option>
                  <option value="Office of the Municipal Civil Registrar" ${
                    user.department ===
                    "Office of the Municipal Civil Registrar"
                      ? "selected"
                      : ""
                  }>Office of the Municipal Civil Registrar</option>
                  <option value="Office of the Municipal Treasurer" ${
                    user.department === "Office of the Municipal Treasurer"
                      ? "selected"
                      : ""
                  }>Office of the Municipal Treasurer</option>
                  <option value="Office of the Municipal Social Welfare and Development Officer" ${
                    user.department ===
                    "Office of the Municipal Social Welfare and Development Officer"
                      ? "selected"
                      : ""
                  }>Office of the Municipal Social Welfare and Development Officer</option>
                  <option value="Office of the Municipal Health Officer" ${
                    user.department === "Office of the Municipal Health Officer"
                      ? "selected"
                      : ""
                  }>Office of the Municipal Health Officer</option>
                  <option value="Office of the Municipal Agriculturist" ${
                    user.department === "Office of the Municipal Agriculturist"
                      ? "selected"
                      : ""
                  }>Office of the Municipal Agriculturist</option>
                  <option value="Office of the MDRRMO" ${
                    user.department === "Office of the MDRRMO" ? "selected" : ""
                  }>Office of the MDRRMO</option>
                  <option value="Office of the Municipal Legal Officer" ${
                    user.department === "Office of the Municipal Legal Officer"
                      ? "selected"
                      : ""
                  }>Office of the Municipal Legal Officer</option>
                  <option value="Office of the Municipal General Services Officer" ${
                    user.department ===
                    "Office of the Municipal General Services Officer"
                      ? "selected"
                      : ""
                  }>Office of the Municipal General Services Officer</option>
                </select>
              </div>
              <div><label class='block text-sm font-medium'>Position</label>
                <select name='position' id='edit-position' class='w-full border rounded px-3 py-2' required>
                  <option value=''>Select Position</option>
                  <option value='Permanent' ${
                    user.position === "Permanent" ? "selected" : ""
                  }>Permanent</option>
                  <option value='Casual' ${
                    user.position === "Casual" ? "selected" : ""
                  }>Casual</option>
                  <option value='JO' ${
                    user.position === "JO" ? "selected" : ""
                  }>JO</option>
                  <option value='OJT' ${
                    user.position === "OJT" ? "selected" : ""
                  }>OJT</option>
                </select>
              </div>
              <div><label class='block text-sm font-medium'>Contact No.</label><input name='contact_no' class='w-full border rounded px-3 py-2' value='${
                user.contact_no || ""
              }' inputmode='numeric' pattern='09[0-9]{9}' maxlength='11' title='Must start with 09 and be 11 digits' oninput="this.value=this.value.replace(/[^0-9]/g,'')" /></div>
              <div><label class='block text-sm font-medium'>Gender</label>
                <select name='gender' class='w-full border rounded px-3 py-2' required>
                  <option value=''>Select Gender</option>
                  <option value='M' ${
                    user.gender === 'M' ? 'selected' : ''
                  }>Male</option>
                  <option value='F' ${
                    user.gender === 'F' ? 'selected' : ''
                  }>Female</option>
                </select>
              </div>
            </div>
            <div class='wizard-step' data-step='3'>
              <div><label class='block text-sm font-medium'>Employee ID</label><input name='employee_id' class='w-full border rounded px-3 py-2' value='${
                user.employee_id || ""
              }' placeholder='e.g., EMP2025-0001' pattern='[A-Za-z0-9\-]+' title='Enter unique Employee ID'></div>
              <div><label class='block text-sm font-medium'>Role</label>
                <select name='role' id='edit-role' class='w-full border rounded px-3 py-2' required>
                  <option value='employee' ${
                    user.role === "employee" ? "selected" : ""
                  }>Employee</option>
                  <option value='department_head' data-requires='Permanent,Casual' ${
                    user.role === "department_head" ? "selected" : ""
                  }>Department Head</option>
                  <option value='hr' data-requires='Permanent,Casual' ${
                    user.role === "hr" ? "selected" : ""
                  }>HR</option>
                </select>
              </div>
              <div><label class='block text-sm font-medium'>Status</label><select name='status' class='w-full border rounded px-3 py-2'>
                <option value='pending' ${
                  user.status === "pending" ? "selected" : ""
                }>Pending</option>
                <option value='approved' ${
                  user.status === "approved" ? "selected" : ""
                }>Approved</option>
                <option value='declined' ${
                  user.status === "declined" ? "selected" : ""
                }>Declined</option>
              </select></div>
              <div><label class='block text-sm font-medium'>Email</label><input name='email' type='email' class='w-full border rounded px-3 py-2' value='${
                user.email || ""
              }' required></div>
              <div><label class='block text-sm font-medium'>Password (leave blank to keep current)</label><input name='password' type='password' class='w-full border rounded px-3 py-2'></div>
            </div>
          </form>
          <div class='flex items-center justify-between mt-4'>
            <div>
              <button id='edit-wizard-back' class='action-btn' style='display:none'>Back</button>
            </div>
            <div>
              <button id='edit-wizard-next' class='action-btn edit'>Next</button>
              <button id='edit-wizard-submit' class='action-btn edit' style='display:none'>Save</button>
            </div>
          </div>
        `;
        openCrudModal("Edit Account", wizardHtml, "");
        // wizard logic
        let curStep = 1;
        const total = 3;
        
        // Position-based role filtering for edit form
        setTimeout(() => {
          const editPositionSelect = document.getElementById('edit-position');
          const editRoleSelect = document.getElementById('edit-role');
          
          function filterEditRoles() {
            const position = editPositionSelect.value;
            const roleOptions = editRoleSelect.querySelectorAll('option');
            
            if (position === 'OJT' || position === 'JO') {
              roleOptions.forEach(opt => {
                if (opt.value === 'employee') {
                  opt.style.display = '';
                  opt.disabled = false;
                } else {
                  opt.style.display = 'none';
                  opt.disabled = true;
                }
              });
              if (editRoleSelect.value !== 'employee') {
                editRoleSelect.value = 'employee';
              }
              editRoleSelect.disabled = true;
            } else if (position === 'Casual') {
              // For Casual: only allow Department Head or Employee, NO HR
              roleOptions.forEach(opt => {
                if (opt.value === 'employee' || opt.value === 'department_head') {
                  opt.style.display = '';
                  opt.disabled = false;
                } else if (opt.value === 'hr') {
                  opt.style.display = 'none';
                  opt.disabled = true;
                }
              });
              // Reset to employee if currently selected role is hr
              if (editRoleSelect.value === 'hr') {
                editRoleSelect.value = 'employee';
              }
              editRoleSelect.disabled = false;
            } else if (position === 'Permanent') {
              // For Permanent: allow all roles
              roleOptions.forEach(opt => {
                opt.style.display = '';
                opt.disabled = false;
              });
              editRoleSelect.disabled = false;
            }
          }
          
          editPositionSelect.addEventListener('change', filterEditRoles);
          filterEditRoles();
        }, 100);
        
        const setStep = (n) => {
          curStep = n;
          document
            .querySelectorAll("#crud-modal .wizard-step")
            .forEach((el, i) => el.classList.toggle("active", i === n - 1));
          document
            .querySelectorAll("#crud-modal .wizard-dot")
            .forEach((d, i) => d.classList.toggle("active", i < n));
          document.getElementById("edit-wizard-back").style.display =
            n === 1 ? "none" : "";
          document.getElementById("edit-wizard-next").style.display =
            n === total ? "none" : "";
          document.getElementById("edit-wizard-submit").style.display =
            n === total ? "" : "none";
        };
        setTimeout(() => {
          document
            .getElementById("edit-wizard-next")
            .addEventListener("click", (e) => {
              e.preventDefault();
              const stepEl = document.querySelector(
                "#crud-modal .wizard-step.active"
              );
              const invalid = Array.from(
                stepEl.querySelectorAll("[required]")
              ).some((i) => !i.value.trim());
              if (invalid) {
                alert("Please fill required fields");
                return;
              }
              setStep(Math.min(total, curStep + 1));
            });
          document
            .getElementById("edit-wizard-back")
            .addEventListener("click", (e) => {
              e.preventDefault();
              setStep(Math.max(1, curStep - 1));
            });
          document
            .getElementById("edit-wizard-submit")
            .addEventListener("click", (e) => {
              e.preventDefault();
              const form = document.getElementById("edit-account-form");
              const data = Object.fromEntries(new FormData(form).entries());
              // Enforce only one Department Head per department on edit (allow if it's the same user)
              const proceedEdit = async () => {
                if ((data.role || "") === "department_head") {
                  try {
                    const r = await fetch("api/dept_heads.php");
                    const heads = await r.json();
                    const conflict =
                      Array.isArray(heads) &&
                      heads.some(
                        (h) =>
                          (h.department || "") === (data.department || "") &&
                          String(h.id) !== String(id)
                      );
                    if (conflict) {
                      alert(
                        "A Department Head is already assigned to this department."
                      );
                      return false;
                    }
                  } catch (e) {
                    alert(
                      "Unable to verify this department’s head right now. Please try again later."
                    );
                    return false;
                  }
                }
                return true;
              };
              proceedEdit().then((ok) => {
                if (!ok) return;
                fetch("api/super_admin_users.php", {
                  method: "POST",
                  headers: { "Content-Type": "application/json" },
                  body: JSON.stringify({ action: "edit", id, ...data }),
                })
                  .then(async (r) => {
                    let res = null;
                    try {
                      res = await r.json();
                    } catch (_) {}
                    if (!r.ok) {
                      alert(
                        res?.error ||
                          "Validation failed. Please check the inputs."
                      );
                      return Promise.reject(res);
                    }
                    return res;
                  })
                  .then(() => {
                    closeCrudModal();
                    fetchData();
                  });
              });
            });
        }, 80);
      };
      window.submitEditAccount = function (id) {
        const form = document.getElementById("edit-account-form");
        const data = Object.fromEntries(new FormData(form).entries());
        fetch("api/super_admin_users.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ action: "edit", id, ...data }),
        })
          .then((r) => r.json())
          .then(() => {
            closeCrudModal();
            fetchData();
          });
      };
      window.archiveUser = function (id) {
        openCrudModal(
          "Archive Account",
          `<div>Archive this account? The user will be hidden until restored.</div>`,
          `<button class='action-btn delete' onclick='submitArchiveAccount(${id})'>Archive</button>`
        );
      };
      window.submitArchiveAccount = function (id) {
        fetch("api/super_admin_users.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ action: "archive", id }),
        })
          .then((r) => r.json())
          .then(() => {
            closeCrudModal();
            fetchData();
          });
      };
      window.restoreUser = function (id) {
        fetch("api/super_admin_users.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ action: "restore", id }),
        })
          .then((r) => r.json())
          .then(() => {
            fetchData();
          });
      };
      window.deleteUserPermanent = function (id) {
        openCrudModal(
          "Delete Account",
          `<div>Permanently delete this account? This cannot be undone.</div>`,
          `<button class='action-btn delete' onclick='submitDeleteAccountPermanent(${id})'>Delete</button>`
        );
      };
      window.submitDeleteAccountPermanent = function (id) {
        fetch("api/super_admin_users.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ action: "delete_permanent", id }),
        })
          .then((r) => r.json())
          .then(() => {
            closeCrudModal();
            fetchData();
          });
      };
      window.approveUser = function (id) {
        fetch("api/super_admin_users.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ action: "approve", id }),
        })
          .then((r) => r.json())
          .then(() => {
            fetchData();
          });
      };
      window.declineUser = function (id) {
        fetch("api/super_admin_users.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ action: "decline", id }),
        })
          .then((r) => r.json())
          .then(() => {
            fetchData();
          });
      };

      // Events CRUD
      function renderEvents(events) {
        const tbody = document.getElementById("events-table-body");
        tbody.innerHTML = "";
        events.forEach((event) => {
          const isArchived = Number(event.is_archived || 0) === 1;
          tbody.innerHTML += `<tr>
          <td>${event.title}</td>
          <td>${event.date}</td>
          <td>${event.time}</td>
          <td>${event.location}</td>
          <td>${event.description}</td>
          <td>
            <div class="action-group">
              <button class="action-btn edit" onclick="editEvent(${
                event.id
              })">Edit</button>
              ${
                isArchived
                  ? `<button class='action-btn approve' onclick='restoreEvent(${event.id})'>Restore</button>
                   <button class='action-btn delete' onclick='deleteEventPermanent(${event.id})'>Delete</button>`
                  : `<button class='action-btn delete' onclick='archiveEvent(${event.id})'>Archive</button>`
              }
            </div>
          </td>
        </tr>`;
        });
      }
      document.getElementById("add-event-btn").onclick = function () {
        openCrudModal(
          "Add Event",
          `
        <form id='add-event-form' class='space-y-4'>
          <div><label class='block text-sm font-medium'>Title</label><input name='title' class='w-full border rounded px-3 py-2' required></div>
          <div><label class='block text-sm font-medium'>Date</label><input name='date' type='date' class='w-full border rounded px-3 py-2' required></div>
          <div><label class='block text-sm font-medium'>Time</label><input name='time' class='w-full border rounded px-3 py-2'></div>
          <div><label class='block text-sm font-medium'>Location</label><input name='location' class='w-full border rounded px-3 py-2' required></div>
          <div><label class='block text-sm font-medium'>Description</label><textarea name='description' class='w-full border rounded px-3 py-2'></textarea></div>
        </form>
      `,
          `<button class='action-btn edit' onclick='submitAddEvent()'>Add</button>`
        );
      };
      window.submitAddEvent = function () {
        const form = document.getElementById("add-event-form");
        const data = Object.fromEntries(new FormData(form).entries());
        fetch("api/super_admin_events.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ action: "add", ...data }),
        })
          .then((r) => r.json())
          .then(() => {
            closeCrudModal();
            fetchData();
          });
      };
      window.editEvent = function (id) {
        const event = events.find((e) => e.id == id);
        openCrudModal(
          "Edit Event",
          `
        <form id='edit-event-form' class='space-y-4'>
          <div><label class='block text-sm font-medium'>Title</label><input name='title' class='w-full border rounded px-3 py-2' value='${event.title}' required></div>
          <div><label class='block text-sm font-medium'>Date</label><input name='date' type='date' class='w-full border rounded px-3 py-2' value='${event.date}' required></div>
          <div><label class='block text-sm font-medium'>Time</label><input name='time' class='w-full border rounded px-3 py-2' value='${event.time}'></div>
          <div><label class='block text-sm font-medium'>Location</label><input name='location' class='w-full border rounded px-3 py-2' value='${event.location}' required></div>
          <div><label class='block text-sm font-medium'>Description</label><textarea name='description' class='w-full border rounded px-3 py-2'>${event.description}</textarea></div>
        </form>
      `,
          `<button class='action-btn edit' onclick='submitEditEvent(${id})'>Save</button>`
        );
      };
      window.submitEditEvent = function (id) {
        const form = document.getElementById("edit-event-form");
        const data = Object.fromEntries(new FormData(form).entries());
        fetch("api/super_admin_events.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ action: "edit", id, ...data }),
        })
          .then((r) => r.json())
          .then(() => {
            closeCrudModal();
            fetchData();
          });
      };
      window.archiveEvent = function (id) {
        openCrudModal(
          "Archive Event",
          `<div>Archive this event?</div>`,
          `<button class='action-btn delete' onclick='submitArchiveEvent(${id})'>Archive</button>`
        );
      };
      window.submitArchiveEvent = function (id) {
        fetch("api/super_admin_events.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ action: "archive", id }),
        })
          .then((r) => r.json())
          .then(() => {
            closeCrudModal();
            fetchData();
          });
      };
      window.restoreEvent = function (id) {
        fetch("api/super_admin_events.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ action: "restore", id }),
        })
          .then((r) => r.json())
          .then(() => {
            fetchData();
          });
      };
      window.deleteEventPermanent = function (id) {
        openCrudModal(
          "Delete Event",
          `<div>Permanently delete this event? This cannot be undone.</div>`,
          `<button class='action-btn delete' onclick='submitDeleteEventPermanent(${id})'>Delete</button>`
        );
      };
      window.submitDeleteEventPermanent = function (id) {
        fetch("api/super_admin_events.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ action: "delete_permanent", id }),
        })
          .then((r) => r.json())
          .then(() => {
            closeCrudModal();
            fetchData();
          });
      };

      // Top tabs were removed from the header; sidebar buttons control tabs now.
      document.getElementById("tab-accounts-btn").onclick = function () {
        document.getElementById("accounts-section").style.display = "";
        document.getElementById("events-section").style.display = "none";
        this.classList.add("text-blue-700", "bg-blue-50");
        document
          .getElementById("tab-events-btn")
          .classList.remove("text-blue-700", "bg-blue-50");
        // Hide add event button when not on events tab
        document.getElementById("add-event-btn").style.display = "none";
      };
      document.getElementById("tab-events-btn").onclick = function () {
        document.getElementById("accounts-section").style.display = "none";
        document.getElementById("events-section").style.display = "";
        this.classList.add("text-blue-700", "bg-blue-50");
        document
          .getElementById("tab-accounts-btn")
          .classList.remove("text-blue-700", "bg-blue-50");
        // Show add event button only on events tab
        document.getElementById("add-event-btn").style.display = "";
      };
      // On load, ensure add event button is only visible on events tab
      document.getElementById("add-event-btn").style.display = "none";

      // Search suggestions (debounced) - do not auto-fill input, show suggestions only
      (function () {
        const input = document.getElementById("account-search");
        const sugBox = document.getElementById("search-suggestions");
        let timer = null;
        function clearSuggestions() {
          sugBox.innerHTML = "";
          sugBox.style.display = "none";
        }
        function showSuggestions(list) {
          if (!list || !list.length) {
            clearSuggestions();
            return;
          }
          sugBox.innerHTML = "";
          list.slice(0, 8).forEach((u) => {
            const div = document.createElement("div");
            div.className = "suggestion-item";
            // show employee ID and name to keep UI compact and informative
            const tag =
              u.employee_id && String(u.employee_id).trim()
                ? `[${u.employee_id}] `
                : "";
            div.textContent = `${tag}${u.lastname}, ${u.firstname}`;
            div.addEventListener("click", () => {
              // open edit modal for selected user; do NOT mutate the search input value
              if (typeof window.editUser === "function") window.editUser(u.id);
              clearSuggestions();
            });
            sugBox.appendChild(div);
          });
          sugBox.style.display = "block";
        }
        input &&
          input.addEventListener("input", (e) => {
            const q = e.target.value.trim().toLowerCase();
            if (timer) clearTimeout(timer);
            if (!q) {
              clearSuggestions();
              return;
            }
            timer = setTimeout(() => {
              // substring match on lastname or firstname only, rank startsWith higher
              const matches = users
                .map((u) => ({ u, score: 0 }))
                .map((o) => {
                  const name = `${o.u.lastname} ${o.u.firstname}`.toLowerCase();
                  if (name.startsWith(q)) o.score = 3;
                  else if (name.includes(q)) o.score = 2;
                  else o.score = 0;
                  return {
                    id: o.u.id,
                    employee_id: o.u.employee_id,
                    score: o.score,
                    lastname: o.u.lastname,
                    firstname: o.u.firstname,
                  };
                })
                .filter((x) => x.score > 0)
                .sort((a, b) => b.score - a.score);
              showSuggestions(
                matches.map((m) => ({
                  id: m.id,
                  employee_id: m.employee_id,
                  lastname: m.lastname,
                  firstname: m.firstname,
                }))
              );
            }, 220);
          });
        // hide suggestions when clicking outside
        document.addEventListener("click", (ev) => {
          if (!document.querySelector(".search-wrap")?.contains(ev.target))
            clearSuggestions();
        });
      })();

      // Actions menu helpers
      // Portal-style floating actions menu (single instance)
      (function () {
        let floatingMenu = null;
        function ensureMenu() {
          if (floatingMenu) return floatingMenu;
          floatingMenu = document.createElement("div");
          floatingMenu.id = "floating-actions-menu";
          floatingMenu.style.position = "fixed";
          floatingMenu.style.zIndex = 99999;
          floatingMenu.style.display = "none";
          floatingMenu.style.minWidth = "110px";
          floatingMenu.style.border = "1px solid #e5e7eb";
          floatingMenu.style.borderRadius = "8px";
          floatingMenu.style.boxShadow = "0 12px 40px rgba(30,64,175,0.08)";
          floatingMenu.style.background = "#fff";
          floatingMenu.style.padding = "6px";
          floatingMenu.style.display = "none";
          document.body.appendChild(floatingMenu);
          return floatingMenu;
        }
        window.toggleActionsMenu = function (evt, id) {
          evt.stopPropagation();
          const user = users.find((u) => u.id == id);
          if (!user) return;
          const menu = ensureMenu();
          // build menu content
          menu.innerHTML = "";
          const btn = (text, cls, onClick) => {
            const b = document.createElement("button");
            b.className = "action-btn " + (cls || "");
            // Compact styles specific to floating menu
            b.style.width = "100%";
            b.style.textAlign = "left";
            b.style.margin = "2px 0";
            b.style.padding = "3px 8px";
            b.style.borderRadius = "5px";
            b.style.fontSize = "0.9rem";
            b.style.boxShadow = "none";
            b.style.fontSize = "0.95rem";
            b.textContent = text;
            b.onclick = (e) => {
              e.stopPropagation();
              onClick && onClick();
              closeFloatingMenu();
            };
            return b;
          };
          menu.appendChild(btn("Edit", "edit", () => window.editUser(id)));
          if ((user.status || "").toString().toLowerCase() === "pending") {
            menu.appendChild(
              btn("Approve", "approve", () => window.approveUser(id))
            );
            menu.appendChild(
              btn("Decline", "decline", () => window.declineUser(id))
            );
          }
          const isArchived = Number(user.is_archived || 0) === 1;
          if (isArchived) {
            menu.appendChild(
              btn("Restore", "approve", () => window.restoreUser(id))
            );
            menu.appendChild(
              btn("Delete", "delete", () => window.deleteUserPermanent(id))
            );
          } else {
            menu.appendChild(
              btn("Archive", "delete", () => window.archiveUser(id))
            );
          }
          // position menu near button and clamp within viewport
          const rect = evt.currentTarget.getBoundingClientRect();
          const gap = 8;
          // show to measure
          menu.style.display = "block";
          menu.style.top = "0px";
          menu.style.left = "-9999px";
          const menuRect = menu.getBoundingClientRect();
          // compute preferred positions
          let top = rect.bottom + gap;
          let left = rect.right - menuRect.width; // align right edge
          // flip above if not enough space below
          if (top + menuRect.height > window.innerHeight - 8) {
            top = rect.top - menuRect.height - gap;
          }
          // clamp to viewport
          top = Math.max(
            8,
            Math.min(top, window.innerHeight - menuRect.height - 8)
          );
          left = Math.max(
            8,
            Math.min(left, window.innerWidth - menuRect.width - 8)
          );
          menu.style.top = top + "px";
          menu.style.left = left + "px";
        };
        function closeFloatingMenu() {
          const m = document.getElementById("floating-actions-menu");
          if (m) m.style.display = "none";
        }
        window.closeAllActionsMenus = closeFloatingMenu;
        document.addEventListener("click", () => {
          closeFloatingMenu();
        });
        window.addEventListener("scroll", () => closeFloatingMenu(), true);
        window.addEventListener("resize", () => closeFloatingMenu());
      })();

      fetchData();
    </script>
  </body>
</html>