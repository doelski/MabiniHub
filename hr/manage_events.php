<?php
require_once __DIR__ . '/../auth_guard.php';
require_role('hr');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Bayan ng Mabini | Employee System - Manage Events</title>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"
    />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
      @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap");
      body {
        font-family: "Inter", sans-serif;
        background-color: #f0f4f8;
        margin: 0;
        padding: 0;
      }
      .container {
        display: flex;
        min-height: 100vh;
      }
      .sidebar {
        width: 280px;
        background-color: #fff;
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 1.5rem 1rem;
        border-right: 4px solid #3b82f6;
        flex-shrink: 0;
        position: fixed;
        top: 60px;
        left: 0;
        bottom: 0;
        overflow-y: auto;
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
        color: #1e3a8a;
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
      }
      .tab-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 2rem;
      }
      .tab-title {
        font-size: 2rem;
        font-weight: 700;
        color: #1e3a8a;
      }
      .tab-btns {
        display: flex;
        gap: 1rem;
      }
      .tab-btn {
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
        font-weight: 500;
        border-radius: 0.5rem;
        cursor: pointer;
        border: 1px solid #d1d5db;
        background: #fff;
        color: #1e3a8a;
        transition: background 0.2s, color 0.2s;
      }
      .tab-btn.active,
      .tab-btn:hover {
        background: #3b82f6;
        color: #fff;
        border-color: #3b82f6;
      }
      @media (max-width: 1024px) {
        .main-content {
          padding: 1.5rem;
          margin-left: 0;
          margin-top: 60px;
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
      }
    </style>
  </head>
  <body>
    <header
      class="top-header"
      style="
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        background: #fff;
        border-bottom: 1px solid #a7c4ff;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        padding: 1rem 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
      "
    >
      <div class="header-left" style="display: flex; align-items: center">
        <div class="header-logo">
          <img
            src="../assets/logo.png"
            alt="Mabini Logo"
            style="width: 40px; height: 40px; margin-right: 10px"
          />
        </div>
        <span
          class="header-text"
          style="font-size: 1.2rem; font-weight: 600; color: #1e3a8a"
          >Bayan ng Mabini</span
        >
      </div>
      <div class="header-profile" style="display: flex; align-items: center">
        <img
          src="../assets/logo.png"
          alt="Profile"
          style="
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
          "
        />
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
            <li class="nav-item active">
              <a href="manage_events.php"
                ><i class="fas fa-calendar-times"></i> Manage Events</a
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
        <div class="tab-header">
          <span class="tab-title">Manage Events</span>
          <div class="tab-btns">
            <button id="openAddEventModal" class="tab-btn">Add Event</button>
          </div>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 lg:p-8">
          <h2 class="text-2xl font-bold text-gray-800 mb-6">All Events</h2>
          <div id="events-container" class="space-y-6"></div>
        </div>
        <!-- Add Event Modal -->
        <div id="addEventModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
          <div class="bg-white rounded-xl shadow-lg p-8 w-full max-w-md relative">
            <button id="closeAddEventModal" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 text-2xl">&times;</button>
            <h3 class="text-xl font-bold text-gray-800 mb-4">Add New Event</h3>
            <form id="addEventForm" class="space-y-4">
              <div>
                <label for="event-title" class="block text-sm font-medium text-gray-700">Event Title</label>
                <input type="text" id="event-title" name="event-title" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
              </div>
              <div>
                <label for="event-date" class="block text-sm font-medium text-gray-700">Date</label>
                <input type="date" id="event-date" name="event-date" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
              </div>
              <div>
                <label for="event-time" class="block text-sm font-medium text-gray-700">Time</label>
                <input type="time" id="event-time" name="event-time" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
              </div>
              <div>
                <label for="event-location" class="block text-sm font-medium text-gray-700">Location</label>
                <input type="text" id="event-location" name="event-location" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
              </div>
              <div>
                <label for="event-description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea id="event-description" name="event-description" rows="3" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
              </div>
              <div class="flex justify-end space-x-2">
                <button type="button" id="cancelAddEvent" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-100 transition-colors">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition-colors">Save Event</button>
              </div>
            </form>
          </div>
        </div>
        </div>
      </main>
    </div>
    <script>
      document.addEventListener("DOMContentLoaded", () => {
        const eventsContainer = document.getElementById("events-container");
        const addEventModal = document.getElementById("addEventModal");
        const openAddEventModal = document.getElementById("openAddEventModal");
        const closeAddEventModal = document.getElementById("closeAddEventModal");
        const cancelAddEvent = document.getElementById("cancelAddEvent");
        const addEventForm = document.getElementById("addEventForm");

        function loadEvents() {
          fetch("../api/get_events.php")
            .then((response) => response.json())
            .then((events) => {
              eventsContainer.innerHTML = "";
              if (events && events.length > 0) {
                events.forEach((event) => {
                  const eventDiv = document.createElement("div");
                  eventDiv.className =
                    "bg-gray-50 p-6 rounded-xl shadow-sm border border-gray-200 flex justify-between items-center";
                  eventDiv.innerHTML = `
                                <div>
                                    <h3 class="text-lg font-bold text-gray-800">${event.title}</h3>
                                    <p class="text-sm text-gray-600">${event.date} ${event.time ? "- " + event.time : ""} - ${event.location}</p>
                                    <p class="text-sm text-gray-500">${event.description}</p>
                                </div>
                                <div class="flex gap-2 items-center">
                                  ${Number(event.is_archived||0) ? `
                                    <span class="text-xs px-2 py-1 rounded bg-gray-200 text-gray-700">Archived</span>
                                    <button class="restore-btn px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700" data-id="${event.id}">Restore</button>
                                    <button class="permadelete-btn px-3 py-2 bg-red-600 text-white rounded hover:bg-red-700" data-id="${event.id}">Delete</button>
                                  ` : `
                                    <button class="archive-btn px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700" data-id="${event.id}">Archive</button>
                                  `}
                                </div>
                            `;
                  eventsContainer.appendChild(eventDiv);
                });
              } else {
                eventsContainer.innerHTML = '<div class="text-gray-500">No events found.</div>';
              }
            });
        }
        loadEvents();

        // Add Event Modal logic
        openAddEventModal.addEventListener('click', () => {
          addEventModal.classList.remove('hidden');
        });
        closeAddEventModal.addEventListener('click', () => {
          addEventModal.classList.add('hidden');
        });
        cancelAddEvent.addEventListener('click', () => {
          addEventModal.classList.add('hidden');
        });

        addEventForm.addEventListener('submit', function(e) {
          e.preventDefault();
          const data = {
            title: document.getElementById('event-title').value,
            date: document.getElementById('event-date').value,
            time: document.getElementById('event-time').value,
            location: document.getElementById('event-location').value,
            description: document.getElementById('event-description').value
          };
          fetch('../api/add_event.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
          })
          .then(res => res.json())
          .then(result => {
            if (result.success) {
              addEventModal.classList.add('hidden');
              addEventForm.reset();
              loadEvents();
            } else {
              alert('Failed to add event');
            }
          });
        });

        eventsContainer.addEventListener("click", function (e) {
          const id = e.target.getAttribute("data-id");
          if (!id) return;
          if (e.target.classList.contains("archive-btn")) {
            if (confirm("Archive this event?")) {
              fetch("../api/delete_event.php", { // now performs archive
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id }),
              })
                .then((res) => res.json())
                .then((data) => { if (data.success) loadEvents(); else alert("Archive failed"); });
            }
          } else if (e.target.classList.contains("restore-btn")) {
            fetch("../api/restore_event.php", {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ id }),
            })
              .then((res) => res.json())
              .then((data) => { if (data.success) loadEvents(); else alert("Restore failed"); });
          } else if (e.target.classList.contains("permadelete-btn")) {
            if (confirm("Permanently delete this event? This cannot be undone.")) {
              fetch("../api/delete_event_permanent.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id }),
              })
                .then((res) => res.json())
                .then((data) => { if (data.success) loadEvents(); else alert("Delete failed"); });
            }
          }
        });
      });
    </script>
  </body>
</html>
