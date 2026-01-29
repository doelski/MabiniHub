<?php
require_once __DIR__ . '/../auth_guard.php';
require_role('hr');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HR | Apply for Leave</title>
    <link
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"
      rel="stylesheet"
    />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
      @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap");
      body {
        font-family: "Inter", sans-serif;
        background-color: #f3f4f6;
      }
      .modal-bg {
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(5px);
        z-index: 1000;
      }
    </style>
  </head>
  <body class="bg-gray-100 p-6 lg:p-10">
    <header
      class="bg-white rounded-xl shadow-md p-4 flex items-center justify-between z-10 sticky top-0"
    >
      <div class="flex items-center space-x-4">
        <div
          class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center"
        >
          <img src="../assets/logo.png" alt="Logo" class="rounded-full" />
        </div>
        <h1 id="header-title" class="text-xl font-bold text-gray-800">
          HR Dashboard
        </h1>
      </div>
      <div class="flex items-center space-x-4">
        <a
          href="dashboard.php"
          class="text-gray-600 hover:text-blue-600 transition-colors"
        >
        </a>
        <a
          href="dashboard.php"
          class="text-gray-600 hover:text-blue-600 transition-colors"
        >
          <i class="fas fa-home text-lg"></i>
        </a>
        <img
          id="profileIcon"
          src="https://placehold.co/40x40/FF5733/FFFFFF?text=P"
          alt="Profile"
          class="w-10 h-10 rounded-full cursor-pointer"
        />
        <!-- Profile Modal -->
        <div
          id="profileModal"
          class="fixed inset-0 hidden items-center justify-center modal-bg z-50"
        >
          <div
            class="bg-white rounded-lg shadow-xl p-6 w-full max-w-xs mx-4 flex flex-col items-center"
          >
            <img
              src="https://placehold.co/80x80/FFD700/000000?text=W+P"
              alt="Profile"
              class="w-20 h-20 rounded-full mb-4"
            />
            <button
              id="logoutBtn"
              class="w-full px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 mb-2"
            >
              Log out
            </button>
            <button
              id="closeProfileModal"
              class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </header>
    <script>
      document.addEventListener("DOMContentLoaded", () => {
        // Profile Modal logic
        const profileIcon = document.getElementById("profileIcon");
        const profileModal = document.getElementById("profileModal");
        const logoutBtn = document.getElementById("logoutBtn");
        const closeProfileModal = document.getElementById("closeProfileModal");
        profileIcon.addEventListener("click", () => {
          profileModal.classList.remove("hidden");
          profileModal.classList.add("flex");
        });
        closeProfileModal.addEventListener("click", () => {
          profileModal.classList.add("hidden");
          profileModal.classList.remove("flex");
        });
        logoutBtn.addEventListener("click", () => {
          window.location.href = "logout.php";
        });
        profileModal.addEventListener("click", (e) => {
          if (e.target === profileModal) {
            profileModal.classList.add("hidden");
            profileModal.classList.remove("flex");
          }
        });
      });
    </script>
    <main class="flex-grow p-4 overflow-y-auto mt-6">
      <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <div class="flex items-center text-gray-600">
          <a href="dashboard.php" class="cursor-pointer hover:text-blue-600"
            >Dashboard</a
          >
          <span class="mx-2">&gt;</span>
          <span class="font-semibold">Apply for Leave</span>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Leave Application</h2>
        <div
          id="leave-cards"
          class="flex gap-6 overflow-x-auto pb-4 -mx-2 px-2"
          style="opacity: 0; transition: opacity 0.3s ease-in-out;"
        >
          <!-- Card: Vacation Leave -->
          <a
            href="leave_form.php?type=Vacation Leave"
            data-leavetype="Vacation Leave"
            class="block flex-shrink-0 bg-white rounded-xl shadow-sm p-6 text-center hover:shadow-lg transform hover:-translate-y-1 transition"
            style="min-width: calc((100% - 3rem) / 3); max-width: 380px"
          >
            <div class="text-3xl md:text-4xl font-extrabold text-gray-800">
              <span class="text-5xl leave-days-number">15</span>
              <span class="ml-2 text-sm font-medium text-gray-500 align-top"
                >Days</span
              >
            </div>
            <div class="text-base font-semibold text-gray-600 mt-2">
              Vacation Leave
            </div>
            <p class="text-sm text-gray-500 mt-1">
              Planned time off for rest and personal activities.
            </p>
            <div
              class="insufficient hidden mt-4 p-2 bg-red-50 text-red-600 text-sm rounded"
            >
              Insufficient balance of leave
            </div>
            <div class="mt-6">
              <button
                class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200"
              >
                Apply
              </button>
            </div>
          </a>

          <!-- Card: Sick Leave -->
          <a
            href="leave_form.php?type=Sick Leave"
            data-leavetype="Sick Leave"
            class="block flex-shrink-0 bg-white rounded-xl shadow-sm p-6 text-center hover:shadow-lg transform hover:-translate-y-1 transition"
            style="min-width: calc((100% - 3rem) / 3); max-width: 380px"
          >
            <div class="text-3xl md:text-4xl font-extrabold text-gray-800">
              <span class="text-5xl leave-days-number">15</span>
              <span class="ml-2 text-sm font-medium text-gray-500 align-top"
                >Days</span
              >
            </div>
            <div class="text-base font-semibold text-gray-600 mt-2">
              Sick Leave
            </div>
            <p class="text-sm text-gray-500 mt-1">
              For medical leave and recovery.
            </p>
            <div
              class="insufficient hidden mt-4 p-2 bg-red-50 text-red-600 text-sm rounded"
            >
              Insufficient balance of leave
            </div>
            <div class="mt-6">
              <button
                class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200"
              >
                Apply
              </button>
            </div>
          </a>

          <!-- Card: Maternity Leave -->
          <a
            href="leave_form.php?type=Maternity Leave"
            data-leavetype="Maternity Leave"
            data-gender-required="F"
            class="block flex-shrink-0 bg-white rounded-xl shadow-sm p-6 text-center hover:shadow-lg transform hover:-translate-y-1 transition"
            style="min-width: calc((100% - 3rem) / 3); max-width: 380px"
          >
            <div class="text-3xl md:text-4xl font-extrabold text-gray-800">
              <span class="text-5xl leave-days-number">105</span>
              <span class="ml-2 text-sm font-medium text-gray-500 align-top"
                >Days</span
              >
            </div>
            <div class="text-base font-semibold text-gray-600 mt-2">
              Maternity Leave
            </div>
            <p class="text-sm text-gray-500 mt-1">
              Leave for childbirth and early childcare.
            </p>
            <div
              class="insufficient hidden mt-4 p-2 bg-red-50 text-red-600 text-sm rounded"
            >
              Insufficient balance of leave
            </div>
            <div class="mt-6">
              <button
                class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200"
              >
                Apply
              </button>
            </div>
          </a>

          <!-- Card: Paternity Leave -->
          <a
            href="leave_form.php?type=Paternity Leave"
            data-leavetype="Paternity Leave"
            data-gender-required="M"
            class="block flex-shrink-0 bg-white rounded-xl shadow-sm p-6 text-center hover:shadow-lg transform hover:-translate-y-1 transition"
            style="min-width: calc((100% - 3rem) / 3); max-width: 380px"
          >
            <div class="text-3xl md:text-4xl font-extrabold text-gray-800">
              <span class="text-5xl leave-days-number">7</span>
              <span class="ml-2 text-sm font-medium text-gray-500 align-top"
                >Days</span
              >
            </div>
            <div class="text-base font-semibold text-gray-600 mt-2">
              Paternity Leave
            </div>
            <p class="text-sm text-gray-500 mt-1">Time off for new fathers.</p>
            <div
              class="insufficient hidden mt-4 p-2 bg-red-50 text-red-600 text-sm rounded"
            >
              Insufficient balance of leave
            </div>
            <div class="mt-6">
              <button
                class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200"
              >
                Apply
              </button>
            </div>
          </a>

          <!-- Card: Mandatory Forced Leave -->
          <a
            href="leave_form.php?type=Mandatory Forced Leave"
            data-leavetype="Mandatory / Forced Leave"
            class="block flex-shrink-0 bg-white rounded-xl shadow-sm p-6 text-center hover:shadow-lg transform hover:-translate-y-1 transition"
            style="min-width: calc((100% - 3rem) / 3); max-width: 380px"
          >
            <div class="text-3xl md:text-4xl font-extrabold text-gray-800">
              <span class="text-5xl leave-days-number">5</span>
              <span class="ml-2 text-sm font-medium text-gray-500 align-top"
                >Days</span
              >
            </div>
            <div class="text-base font-semibold text-gray-600 mt-2">
              Mandatory Forced Leave
            </div>
            <p class="text-sm text-gray-500 mt-1">
              Organizationally mandated time off.
            </p>
            <div
              class="insufficient hidden mt-4 p-2 bg-red-50 text-red-600 text-sm rounded"
            >
              Insufficient balance of leave
            </div>
            <div class="mt-6">
              <button
                class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200"
              >
                Apply
              </button>
            </div>
          </a>

          <!-- Card: Study Leave -->
          <a
            href="leave_form.php?type=Study Leave"
            data-leavetype="Study Leave"
            class="block flex-shrink-0 bg-white rounded-xl shadow-sm p-6 text-center hover:shadow-lg transform hover:-translate-y-1 transition"
            style="min-width: calc((100% - 3rem) / 3); max-width: 380px"
          >
            <div class="text-3xl md:text-4xl font-extrabold text-gray-800">
              <span class="text-5xl leave-days-number">180</span>
              <span class="ml-2 text-sm font-medium text-gray-500 align-top"
                >Days</span
              >
            </div>
            <div class="text-base font-semibold text-gray-600 mt-2">
              Study Leave
            </div>
            <p class="text-sm text-gray-500 mt-1">
              For extended study or research programs.
            </p>
            <div
              class="insufficient hidden mt-4 p-2 bg-red-50 text-red-600 text-sm rounded"
            >
              Insufficient balance of leave
            </div>
            <div class="mt-6">
              <button
                class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200"
              >
                Apply
              </button>
            </div>
          </a>

          <!-- Card: 10-Day VAWC Leave -->
          <a
            href="leave_form.php?type=10-Day VAWC Leave"
            data-leavetype="10-Day VAWC Leave"
            data-gender-required="F"
            class="block flex-shrink-0 bg-white rounded-xl shadow-sm p-6 text-center hover:shadow-lg transform hover:-translate-y-1 transition"
            style="min-width: calc((100% - 3rem) / 3); max-width: 380px"
          >
            <div class="text-3xl md:text-4xl font-extrabold text-gray-800">
              <span class="text-5xl leave-days-number">10</span>
              <span class="ml-2 text-sm font-medium text-gray-500 align-top"
                >Days</span
              >
            </div>
            <div class="text-base font-semibold text-gray-600 mt-2">
              10-Day VAWC Leave
            </div>
            <p class="text-sm text-gray-500 mt-1">
              Leave for victims of violence against women and children.
            </p>
            <div
              class="insufficient hidden mt-4 p-2 bg-red-50 text-red-600 text-sm rounded"
            >
              Insufficient balance of leave
            </div>
            <div class="mt-6">
              <button
                class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200"
              >
                Apply
              </button>
            </div>
          </a>

          <!-- Card: Special Leave for Women -->
          <a
            href="leave_form.php?type=Special Leave for Women"
            data-leavetype="Special Leave Benefits for Women"
            data-gender-required="F"
            class="block flex-shrink-0 bg-white rounded-xl shadow-sm p-6 text-center hover:shadow-lg transform hover:-translate-y-1 transition"
            style="min-width: calc((100% - 3rem) / 3); max-width: 380px"
          >
            <div class="text-3xl md:text-4xl font-extrabold text-gray-800">
              <span class="text-5xl leave-days-number">60</span>
              <span class="ml-2 text-sm font-medium text-gray-500 align-top"
                >Days</span
              >
            </div>
            <div class="text-base font-semibold text-gray-600 mt-2">
              Special Leave for Women
            </div>
            <p class="text-sm text-gray-500 mt-1">
              Supportive leave policies for women.
            </p>
            <div
              class="insufficient hidden mt-4 p-2 bg-red-50 text-red-600 text-sm rounded"
            >
              Insufficient balance of leave
            </div>
            <div class="mt-6">
              <button
                class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200"
              >
                Apply
              </button>
            </div>
          </a>

          <!-- Card: Special Emergency (Calamity) Leave -->
          <a
            href="leave_form.php?type=Special Emergency (Calamity) Leave"
            data-leavetype="Special Emergency (Calamity) Leave"
            class="block flex-shrink-0 bg-white rounded-xl shadow-sm p-6 text-center hover:shadow-lg transform hover:-translate-y-1 transition"
            style="min-width: calc((100% - 3rem) / 3); max-width: 380px"
          >
            <div class="text-3xl md:text-4xl font-extrabold text-gray-800">
              <span class="text-5xl leave-days-number">5</span>
              <span class="ml-2 text-sm font-medium text-gray-500 align-top"
                >Days</span
              >
            </div>
            <div class="text-base font-semibold text-gray-600 mt-2">
              Special Emergency (Calamity) Leave
            </div>
            <p class="text-sm text-gray-500 mt-1">
              For calamity-related emergencies and relief.
            </p>
            <div
              class="insufficient hidden mt-4 p-2 bg-red-50 text-red-600 text-sm rounded"
            >
              Insufficient balance of leave
            </div>
            <div class="mt-6">
              <button
                class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200"
              >
                Apply
              </button>
            </div>
          </a>

          <!-- Card: Adaption Leave -->
          <a
            href="leave_form.php?type=Adaption Leave"
            data-leavetype="Adoption Leave"
            class="block flex-shrink-0 bg-white rounded-xl shadow-sm p-6 text-center hover:shadow-lg transform hover:-translate-y-1 transition"
            style="min-width: calc((100% - 3rem) / 3); max-width: 380px"
          >
            <div class="text-3xl md:text-4xl font-extrabold text-gray-800">
              <span class="text-5xl leave-days-number">60</span>
              <span class="ml-2 text-sm font-medium text-gray-500 align-top"
                >Days</span
              >
            </div>
            <div class="text-base font-semibold text-gray-600 mt-2">
              Adaption Leave
            </div>
            <p class="text-sm text-gray-500 mt-1">
              Leave for transition and adaptation needs.
            </p>
            <div
              class="insufficient hidden mt-4 p-2 bg-red-50 text-red-600 text-sm rounded"
            >
              Insufficient balance of leave
            </div>
            <div class="mt-6">
              <button
                class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200"
              >
                Apply
              </button>
            </div>
          </a>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold text-gray-800">Leave History</h3>
          <div class="flex items-center space-x-4">
            <button
              id="btnFilter"
              class="flex items-center space-x-2 px-4 py-2 text-sm font-semibold text-blue-600 bg-white border border-blue-600 rounded-lg hover:bg-blue-50 transition-colors"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                />
              </svg>
              <span>Filter</span>
            </button>
            <button
              id="btnExport"
              class="flex items-center space-x-2 px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                />
              </svg>
              <span>Export</span>
            </button>
          </div>
        </div>
        <!-- Filter Panel (hidden by default) -->
        <div
          id="filterPanel"
          class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200 hidden"
        >
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-xs text-gray-600 mb-1">Status</label>
              <select
                id="filterStatus"
                class="w-full border rounded p-2 text-sm"
              >
                <option value="">All</option>
                <option value="Pending">Pending</option>
                <option value="Approved">Approved</option>
                <option value="Declined">Declined</option>
              </select>
            </div>
            <div>
              <label class="block text-xs text-gray-600 mb-1">Leave Type</label>
              <select id="filterType" class="w-full border rounded p-2 text-sm">
                <option value="">All</option>
              </select>
            </div>
            <div>
              <label class="block text-xs text-gray-600 mb-1">Start From</label>
              <input
                type="date"
                id="filterFrom"
                class="w-full border rounded p-2 text-sm"
              />
            </div>
            <div>
              <label class="block text-xs text-gray-600 mb-1">End To</label>
              <input
                type="date"
                id="filterTo"
                class="w-full border rounded p-2 text-sm"
              />
            </div>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th
                  scope="col"
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Name
                </th>
                <th
                  scope="col"
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Duration
                </th>
                <th
                  scope="col"
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Start Date
                </th>
                <th
                  scope="col"
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  End Date
                </th>
                <th
                  scope="col"
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Form
                </th>
                <th
                  scope="col"
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Contact No.
                </th>
                <th
                  scope="col"
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Status
                </th>
              </tr>
            </thead>
            <tbody
              id="leaveHistoryBody"
              class="bg-white divide-y divide-gray-200"
            >
              <!-- Rows populated by JS from database -->
            </tbody>
          </table>
        </div>
      </div>
    </main>
    <script>
      // Dept head uses the same history UI but will fetch from the employee API (server session will determine results)
      (function () {
        let API_URL = "/capstone/api/employee_leave_history.php";
        const uEmail = localStorage.getItem("userEmail");
        if (uEmail) API_URL += `?email=${encodeURIComponent(uEmail)}`;

        const state = { raw: [], filtered: [], poll: null };

        function renderTable() {
          const tbody = document.getElementById("leaveHistoryBody");
          tbody.innerHTML = "";
          state.filtered.forEach((item) => {
            const statusClass =
              item.status === "Approved"
                ? "text-green-600"
                : item.status === "Declined"
                ? "text-red-600"
                : "text-yellow-600";
            const tr = document.createElement("tr");
            tr.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${
                      item.name || ""
                    }</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${
                      item.duration || ""
                    }</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${
                      item.startDate || ""
                    }</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${
                      item.endDate || ""
                    }</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 underline"><a href="../dept_head/civil_form.php?id=${
                      item.formId
                    }" target="_blank">View Form</a></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${
                      item.contactNo || ""
                    }</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold ${statusClass}">${
              item.status || ""
            }</td>
                `;
            tbody.appendChild(tr);
          });
        }

        function applyFilters() {
          const status = document.getElementById("filterStatus").value || "";
          const type = document.getElementById("filterType").value || "";
          const from = document.getElementById("filterFrom").value || "";
          const to = document.getElementById("filterTo").value || "";
          const fromTime = from ? new Date(from + "T00:00:00") : null;
          const toTime = to ? new Date(to + "T23:59:59") : null;
          state.filtered = state.raw.filter((item) => {
            if (status && item.status !== status) return false;
            if (type && !(item.leaveType || "").includes(type)) return false;
            if (fromTime || toTime) {
              const s = item.startDate
                ? new Date(item.startDate)
                : item.appliedAt
                ? new Date(item.appliedAt)
                : null;
              const e = item.endDate ? new Date(item.endDate) : s;
              if (fromTime && e && e < fromTime) return false;
              if (toTime && s && s > toTime) return false;
            }
            return true;
          });
          renderTable();
        }

        function populateTypeFilter() {
          const sel = document.getElementById("filterType");
          if (!sel) return;
          const current = sel.value;
          const uniqueTypes = Array.from(
            new Set(state.raw.map((x) => x.leaveType || "").filter(Boolean))
          );
          sel.innerHTML =
            '<option value="">All</option>' +
            uniqueTypes
              .map(
                (t) =>
                  `<option value="${t.replace(/"/g, "&quot;")}">${t}</option>`
              )
              .join("");
          if (current && uniqueTypes.includes(current)) sel.value = current;
        }

        async function fetchHistory() {
          try {
            const res = await fetch(API_URL);
            if (!res.ok) throw new Error("HTTP " + res.status);
            const js = await res.json();
            if (!js || !js.success) throw new Error(js?.error || "Failed");
            state.raw = js.data || [];
            populateTypeFilter();
            applyFilters();
          } catch (e) {
            console.error("Failed to load history", e);
          }
        }

        function startPolling() {
          if (state.poll) clearInterval(state.poll);
          state.poll = setInterval(fetchHistory, 10000);
        }
        function stopPolling() {
          if (state.poll) clearInterval(state.poll);
          state.poll = null;
        }

        function exportCSV() {
          const rows = state.filtered;
          const headers = [
            "Form ID",
            "Name",
            "Leave Type",
            "Duration",
            "Start Date",
            "End Date",
            "Contact No.",
            "Status",
            "Applied At",
          ];
          const csv = [headers.join(",")]
            .concat(
              rows.map((r) =>
                [
                  r.formId,
                  r.name || "",
                  r.leaveType || "",
                  r.duration || "",
                  r.startDate || "",
                  r.endDate || "",
                  r.contactNo || "",
                  r.status || "",
                  r.appliedAt || "",
                ]
                  .map((v) => '"' + String(v).replace(/"/g, '""') + '"')
                  .join(",")
              )
            )
            .join("\r\n");
          const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
          const url = URL.createObjectURL(blob);
          const a = document.createElement("a");
          a.href = url;
          const d = new Date();
          a.download = `leave_history_${d.getFullYear()}-${(d.getMonth() + 1)
            .toString()
            .padStart(2, "0")}-${d.getDate().toString().padStart(2, "0")}.csv`;
          document.body.appendChild(a);
          a.click();
          document.body.removeChild(a);
          URL.revokeObjectURL(url);
        }

        // Wire UI
        document.getElementById("btnFilter").addEventListener("click", () => {
          document.getElementById("filterPanel").classList.toggle("hidden");
        });
        document
          .getElementById("btnExport")
          .addEventListener("click", exportCSV);
        ["filterStatus", "filterType", "filterFrom", "filterTo"].forEach(
          (id) => {
            const el = document.getElementById(id);
            if (el) el.addEventListener("change", applyFilters);
          }
        );

        window.addEventListener("beforeunload", stopPolling);
        fetchHistory().then(startPolling);
      })();
    </script>
    <script>
      // Fetch live leave credits and update the cards (show Insufficient state)
      (function () {
        async function resolveUserEmail() {
          try {
            // ALWAYS fetch from API first to ensure fresh data
            const r = await fetch("/capstone/api/current_user.php");
            if (!r.ok) {
              console.error('[HR] Failed to fetch current user:', r.status);
              return "";
            }
            const js = await r.json();
            if (js && js.logged_in && js.email) {
              console.log('[HR] API returned email:', js.email);
              try {
                // Update localStorage with fresh email
                localStorage.setItem("userEmail", js.email);
              } catch (e) {
                console.error('[HR] Failed to set localStorage:', e);
              }
              return js.email;
            }
            console.error('[HR] API returned invalid data:', js);
          } catch (e) {
            console.error('[HR] Exception in resolveUserEmail:', e);
          }
          return "";
        }

        function normalizeKey(s) {
          return (s || "")
            .toString()
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, " ")
            .trim();
        }

        // Ensure DOM is fully loaded before running
        if (document.readyState === 'loading') {
          document.addEventListener('DOMContentLoaded', initLeaveApplication);
        } else {
          initLeaveApplication();
        }

        async function initLeaveApplication() {
          console.log('[HR] Initializing leave application check...');
          const email = await resolveUserEmail();
          console.log('[HR] Resolved email:', email);
          if (!email) {
            console.error('[HR] No email found, cannot check leave status');
            return;
          }

          // Check if user can apply for leave
          let canApplyLeave = true;
          try {
            console.log('[HR] Checking leave status for:', email);
            const statusResp = await fetch(`/capstone/api/get_employee_leave_status.php?email=${encodeURIComponent(email)}`);
            console.log('[HR] Status response:', statusResp.status);
            
            if (!statusResp.ok) {
              console.error('[HR] API response not OK:', statusResp.status);
            } else {
              const statusData = await statusResp.json();
              console.log('[HR] Status data:', statusData);
              if (statusData && statusData.success) {
                canApplyLeave = statusData.data.can_apply_leave === 1;
                console.log('[HR] Can apply leave:', canApplyLeave);
              } else {
                console.error('[HR] API returned error:', statusData);
              }
            }
          } catch (e) {
            console.error("[HR] Failed to fetch leave application status", e);
          }

          console.log('[HR] Final canApplyLeave value:', canApplyLeave);

          // If user cannot apply for leave, show warning and disable all cards
          if (!canApplyLeave) {
            console.log('[HR] DISABLING LEAVE APPLICATION');
            const container = document.getElementById('leave-cards');
            console.log('[HR] Container found:', container);
            if (container) {
              // Add a notice at the top
              const notice = document.createElement('div');
              notice.className = 'col-span-full mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded';
              notice.innerHTML = `
                <div class="flex items-center gap-3">
                  <i class="fas fa-exclamation-triangle text-2xl"></i>
                  <div>
                    <p class="font-semibold">Leave Application Disabled</p>
                    <p class="text-sm">Your ability to apply for leave has been disabled by HR. Please contact HR for more information.</p>
                  </div>
                </div>
              `;
              container.parentElement.insertBefore(notice, container);
              console.log('[HR] Warning banner added');
            }

            // Disable all leave application cards
            const cards = document.querySelectorAll("#leave-cards a[data-leavetype]");
            console.log('[HR] Found cards:', cards.length);
            cards.forEach((card) => {
              const btn = card.querySelector("button");
              if (btn) {
                btn.textContent = "Disabled";
                btn.disabled = true;
                btn.classList.remove("bg-blue-600", "hover:bg-blue-700");
                btn.classList.add("bg-gray-400", "text-gray-700", "cursor-not-allowed");
              }
              card.addEventListener("click", (ev) => {
                ev.preventDefault();
                alert('Leave application is currently disabled. Please contact HR.');
              });
              card.style.opacity = '0.6';
              card.style.pointerEvents = 'none';
            });
            console.log('[HR] All cards disabled');
            // Continue to load actual leave credits (no return here!)
          }

          // Fetch current user info to get gender
          let userGender = null;
          try {
            const userResp = await fetch("/capstone/api/current_user.php");
            const userData = await userResp.json();
            if (userData && userData.logged_in) {
              userGender = userData.gender; // 'M' or 'F'
            }
          } catch (e) {
            console.error("Failed to fetch user info", e);
          }

          // Filter leave cards by gender before showing them
          document
            .querySelectorAll("#leave-cards a[data-leavetype]")
            .forEach((card) => {
              const requiredGender = card.dataset.genderRequired;
              // Hide cards that require a specific gender that doesn't match
              if (requiredGender && userGender && requiredGender !== userGender) {
                card.style.display = "none";
                return; // Skip processing this card
              }
            });

          const API = "/capstone/api/employee_leave_credits.php";

          try {
            const resp = await fetch(API);
            const js = await resp.json();
            if (!js || !js.success || !Array.isArray(js.data)) return;
            const items = js.data;
            const lookup = {};
            items.forEach((it) => {
              lookup[normalizeKey(it.type)] = it;
            });

            document
              .querySelectorAll("#leave-cards a[data-leavetype]")
              .forEach((card) => {
                const cardType = card.dataset.leavetype || "";
                const norm = normalizeKey(cardType);
                let item = lookup[norm];
                if (!item) {
                  item = items.find((it) =>
                    normalizeKey(it.type).includes(norm)
                  );
                }

                const daysEl = card.querySelector(".leave-days-number");
                const insufEl = card.querySelector(".insufficient");
                const btn = card.querySelector("button");

                if (!item) {
                  return;
                }

                const avail = Number(item.available || 0);
                if (daysEl) daysEl.textContent = String(avail);

                if (avail <= 0) {
                  if (insufEl) insufEl.classList.remove("hidden");
                  if (btn) {
                    btn.textContent = "Insufficient";
                    btn.disabled = true;
                    btn.classList.remove("bg-blue-600", "hover:bg-blue-700");
                    btn.classList.add(
                      "bg-gray-300",
                      "text-gray-700",
                      "cursor-not-allowed"
                    );
                    btn.setAttribute("aria-disabled", "true");
                  }
                  card.addEventListener("click", (ev) => {
                    ev.preventDefault();
                  });
                } else {
                  if (insufEl) insufEl.classList.add("hidden");
                  if (btn) {
                    btn.textContent = "Apply";
                    btn.disabled = false;
                    btn.classList.remove(
                      "bg-gray-300",
                      "text-gray-700",
                      "cursor-not-allowed"
                    );
                    btn.classList.add("bg-blue-600", "hover:bg-blue-700");
                    btn.removeAttribute("aria-disabled");
                  }
                }
              });
          } catch (err) {
            console.error("Failed to load leave credits", err);
          }
          
          // Show cards after data is loaded
          console.log('[HR] Showing cards after data load');
          const cardsContainer = document.getElementById('leave-cards');
          if (cardsContainer) {
            cardsContainer.style.opacity = '1';
          }
        } // End of initLeaveApplication function
      })();
    </script>
  </body>
</html>