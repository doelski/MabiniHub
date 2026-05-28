<?php
require_once __DIR__ . '/../auth_guard.php';
require_role('department_head');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Dept Head | Analytics</title>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"
    />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
      @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap");
      body {
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI",
          Roboto, "Helvetica Neue", Arial;
        background: #f0f4f8;
        margin: 0;
      }
      .top-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.5rem;
        background: #fff;
        border-bottom: 1px solid #a7c4ff;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
      }
      .container {
        display: flex;
        min-height: 100vh;
        padding-top: 60px;
      }
      .sidebar {
        width: 280px;
        background: #fff;
        padding: 1.5rem;
        border-right: 4px solid #3b82f6;
        position: fixed;
        top: 60px;
        left: 0;
        bottom: 0;
        overflow: auto;
      }
      .main-content {
        flex: 1;
        padding: 2.5rem;
        margin-left: 280px;
        margin-top: 60px;
      }
      .card {
        background: #fff;
        border-radius: 0.75rem;
        padding: 1rem;
        box-shadow: 0 6px 14px rgba(17, 24, 39, 0.06);
      }
    </style>
  </head>
  <body>
    <header class="top-header">
      <div style="display: flex; align-items: center; gap: 12px">
        <img
          src="../assets/logo.png"
          alt="logo"
          style="width: 36px; height: 36px"
        />
        <div style="font-weight: 700; color: #1e3a8a">Dept Head Analytics</div>
      </div>
      <div style="display: flex; align-items: center; gap: 12px">
        <i class="fas fa-bell" style="color: #6b7280"></i>
        <img
          src="../assets/logo.png"
          alt="profile"
          style="width: 32px; height: 32px; border-radius: 50%"
        />
      </div>
    </header>

    <div class="container">
      <aside class="sidebar">
        <nav>
          <ul style="list-style: none; padding: 0; margin: 0">
            <li style="margin-bottom: 8px">
              <a href="dashboard.php"
                ><i class="fas fa-th-large"></i> Dashboard</a
              >
            </li>
            <li style="margin-bottom: 8px">
              <a href="employees.php"
                ><i class="fas fa-users"></i> Employees</a
              >
            </li>
            <li style="margin-bottom: 8px">
              <a href="leave-status.php"
                ><i class="fas fa-calendar-alt"></i> Leave Status</a
              >
            </li>
            <li style="margin-bottom: 8px">
              <a href="analytics.php" class="text-blue-600 font-semibold"
                ><i class="fas fa-chart-pie"></i> Analytics</a
              >
            </li>
          </ul>
        </nav>
      </aside>

      <main class="main-content">
        <div
          style="
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
          "
        >
          <h1 style="font-size: 1.5rem; font-weight: 700">Analytics (Dept)</h1>
          <div style="display: flex; align-items: center; gap: 8px">
            <button
              id="prevMonth"
              class="px-3 py-1 bg-blue-500 text-white rounded"
            >
              Prev
            </button>
            <div id="currentMonthYear" style="font-weight: 600"></div>
            <button
              id="nextMonth"
              class="px-3 py-1 bg-blue-500 text-white rounded"
            >
              Next
            </button>
          </div>
        </div>

        <div
          style="
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 12px;
          "
        >
          <div class="card" id="kpi-total-card">
            <div style="font-size: 0.9rem; color: #6b7280">
              Total requests (month)
            </div>
            <div id="kpi-total" style="font-size: 2rem; font-weight: 700">
              —
            </div>
          </div>
          <div class="card" id="kpi-approved-card">
            <div style="font-size: 0.9rem; color: #6b7280">Approved</div>
            <div id="kpi-approved" style="font-size: 2rem; font-weight: 700">
              —
            </div>
          </div>
          <div class="card" id="kpi-pending-card">
            <div style="font-size: 0.9rem; color: #6b7280">Pending</div>
            <div id="kpi-pending" style="font-size: 2rem; font-weight: 700">
              —
            </div>
          </div>
          <div class="card" id="kpi-declined-card">
            <div style="font-size: 0.9rem; color: #6b7280">Declined</div>
            <div id="kpi-declined" style="font-size: 2rem; font-weight: 700">
              —
            </div>
          </div>
        </div>

        <div
          style="display: grid; grid-template-columns: 2fr 2fr 1fr; gap: 12px"
        >
          <div class="card">
            <div
              style="
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 8px;
              "
            >
              <h3 style="font-weight: 700">By status</h3>
              <span
                id="label-month"
                style="font-size: 0.85rem; color: #6b7280"
              ></span>
            </div>
            <div style="height: 260px"><canvas id="chart-status"></canvas></div>
          </div>
          <div class="card">
            <div
              style="
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 8px;
              "
            >
              <h3 style="font-weight: 700">By leave type</h3>
              <span style="font-size: 0.85rem; color: #6b7280">Top types</span>
            </div>
            <div style="height: 260px"><canvas id="chart-type"></canvas></div>
          </div>
          <div class="card">
            <div
              style="
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 8px;
              "
            >
              <h3 style="font-weight: 700">Peak days</h3>
              <span style="font-size: 0.75rem; color: #6b7280"
                >Most leaves in month</span
              >
            </div>
            <ul
              id="peak-days"
              style="
                list-style: none;
                padding: 0;
                margin: 0;
                display: flex;
                flex-direction: column;
                gap: 8px;
              "
            ></ul>
          </div>
        </div>
      </main>
    </div>

    <script>
      // Dept Head analytics page: uses the same analytics logic but filters by current user's department.
      (function () {
        const API_URL = "../api/get_leave_requests.php";
        let currentDate = new Date();
        let analyticsLeaves = [];
        let currentDept = null;
        let statusChart = null,
          typeChart = null;

        async function loadCurrentUser() {
          try {
            const r = await fetch("../api/current_user.php");
            const j = await r.json();
            if (j && j.logged_in) currentDept = (j.department || "").toString();
          } catch (e) {
            console.warn("current_user failed", e);
          }
        }

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

        async function fetchAnalyticsForMonth(date) {
          const y = date.getFullYear();
          const m = date.getMonth() + 1;
          try {
            const url = `${API_URL}?month=${m}&year=${y}`;
            const res = await fetch(url);
            const js = await res.json();
            let data =
              js && js.success && Array.isArray(js.data) ? js.data : [];
            if (currentDept)
              data = data.filter((d) => String(d.department) === currentDept);
            analyticsLeaves = data;
          } catch (e) {
            console.error("fetch analytics failed", e);
            analyticsLeaves = [];
          }
        }

        function updateKPIs() {
          const total = analyticsLeaves.length;
          const approved = analyticsLeaves.filter(
            (l) => String(l.status) === "approved"
          ).length;
          const pending = analyticsLeaves.filter(
            (l) => String(l.status) === "pending"
          ).length;
          const declined = analyticsLeaves.filter(
            (l) => String(l.status) === "declined"
          ).length;
          document.getElementById("kpi-total").textContent = String(total);
          document.getElementById("kpi-approved").textContent =
            String(approved);
          document.getElementById("kpi-pending").textContent = String(pending);
          document.getElementById("kpi-declined").textContent =
            String(declined);
        }

        function updateStatusChart(scope) {
          const ctx = document.getElementById("chart-status");
          if (!ctx) return;
          const leaves =
            scope === "approved"
              ? analyticsLeaves.filter((l) => String(l.status) === "approved")
              : analyticsLeaves;
          const counts = {
            approved: leaves.filter((l) => String(l.status) === "approved")
              .length,
            pending: leaves.filter((l) => String(l.status) === "pending")
              .length,
            declined: leaves.filter((l) => String(l.status) === "declined")
              .length,
          };
          const data = {
            labels: ["Approved", "Pending", "Declined"],
            datasets: [
              {
                data: [counts.approved, counts.pending, counts.declined],
                backgroundColor: ["#10b981", "#fbbf24", "#f43f5e"],
              },
            ],
          };
          const options = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: "bottom" } },
          };
          if (statusChart) {
            statusChart.data = data;
            statusChart.update();
          } else {
            statusChart = new Chart(ctx, { type: "doughnut", data, options });
          }
        }

        function updateTypeChart(scope) {
          const ctx = document.getElementById("chart-type");
          if (!ctx) return;
          const leaves =
            scope === "approved"
              ? analyticsLeaves.filter((l) => String(l.status) === "approved")
              : analyticsLeaves;
          const byType = new Map();
          leaves.forEach((l) => {
            const t = (l.leave_type || "Unknown").trim();
            byType.set(t, (byType.get(t) || 0) + 1);
          });
          const sorted = Array.from(byType.entries())
            .sort((a, b) => b[1] - a[1])
            .slice(0, 8);
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
          const data = {
            labels,
            datasets: [
              {
                data: values,
                backgroundColor: palette.slice(0, values.length),
              },
            ],
          };
          const options = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
          };
          if (typeChart) {
            typeChart.data = data;
            typeChart.update();
          } else {
            typeChart = new Chart(ctx, { type: "bar", data, options });
          }
        }

        function updatePeakDays(scope) {
          const ul = document.getElementById("peak-days");
          if (!ul) return; // compute counts by date from analyticsLeaves
          const month = currentDate.getMonth();
          const year = currentDate.getFullYear();
          const counts = new Map();
          analyticsLeaves.forEach((l) => {
            const range = parseStartEnd(l.dates);
            if (!range) return;
            datesInRange(range.start, range.end, (d) => {
              if (d.getMonth() !== month || d.getFullYear() !== year) return;
              const key = formatYMD(d);
              counts.set(key, (counts.get(key) || 0) + 1);
            });
          });
          const entries = Array.from(counts.entries()).map(([k, v]) => ({
            date: k,
            count: v,
          }));
          entries.sort(
            (a, b) => b.count - a.count || a.date.localeCompare(b.date)
          );
          const top = entries.slice(0, 5);
          ul.innerHTML = "";
          if (top.length === 0) {
            ul.innerHTML =
              '<li class="text-sm text-gray-500">No data for this month.</li>';
            return;
          }
          const max = Math.max(...top.map((t) => t.count));
          top.forEach((item) => {
            const pct = max ? Math.round((item.count / max) * 100) : 0;
            const li = document.createElement("li");
            li.innerHTML = `<div style="display:flex;justify-content:space-between"><span>${item.date}</span><span style="font-weight:700">${item.count}</span></div><div style="width:100%;height:8px;background:#f3f4f6;border-radius:6px;margin-top:6px"><div style="height:100%;background:#2563eb;border-radius:6px;width:${pct}%"></div></div>`;
            ul.appendChild(li);
          });
        }

        function updateUI() {
          const label = document.getElementById("label-month");
          if (label)
            label.textContent = currentDate.toLocaleDateString("en-US", {
              month: "long",
              year: "numeric",
            });
          const scope = "approved";
          updateKPIs();
          updateStatusChart(scope);
          updateTypeChart(scope);
          updatePeakDays(scope);
        }

        document
          .getElementById("prevMonth")
          .addEventListener("click", async () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            await fetchAnalyticsForMonth(currentDate);
            updateUI();
          });
        document
          .getElementById("nextMonth")
          .addEventListener("click", async () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            await fetchAnalyticsForMonth(currentDate);
            updateUI();
          });

        (async function init() {
          await loadCurrentUser();
          await fetchAnalyticsForMonth(currentDate);
          updateUI();
        })();
      })();
    </script>
  </body>
</html>
