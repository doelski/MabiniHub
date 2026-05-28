<?php
require_once __DIR__ . '/../auth_guard.php';
require_role('hr');
require_once __DIR__ . '/../db.php';

$user_email = $_SESSION['email'] ?? '';
$user_department = '';
$user_firstname = '';
$user_lastname = '';
$user_mi = '';
$user_position = '';
$user_salary = '';
if ($user_email) {
    // try users table first
    $stmt = $pdo->prepare('SELECT firstname, lastname, mi, department, position FROM users WHERE email = ?');
    $stmt->execute([$user_email]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($u) {
        $user_department = $u['department'] ?? '';
        $user_firstname = $u['firstname'] ?? '';
        $user_lastname = $u['lastname'] ?? '';
        $user_mi = $u['mi'] ?? '';
        $user_position = $u['position'] ?? '';
    } else {
        // fallback to employees table if exists
        $stmt = $pdo->prepare('SELECT firstname AS "firstName", lastname AS "lastName", middlename AS "middleName", department, position, salary FROM employees WHERE email = ?');
        $stmt->execute([$user_email]);
        $u2 = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($u2) {
            $user_department = $u2['department'] ?? '';
            $user_firstname = $u2['firstName'] ?? '';
            $user_lastname = $u2['lastName'] ?? '';
            $user_mi = $u2['middleName'] ?? '';
            $user_position = $u2['position'] ?? '';
            $user_salary = $u2['salary'] ?? '';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Leave Form</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .modal-bg { background-color: rgba(0,0,0,0.5); backdrop-filter: blur(5px); z-index:1000; }
        .modal-bg.hidden { display:none; }
    </style>
</head>
<body class="bg-gray-100 p-6 lg:p-10">

    <header class="bg-white rounded-xl shadow-md p-4 flex items-center justify-between z-10 sticky top-0">
        <div class="flex items-center space-x-4">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                <img src="../assets/logo.png" alt="Logo" class="rounded-full">
            </div>
            <h1 id="header-title" class="text-xl font-bold text-gray-800">HR — Apply for Leave</h1>
        </div>
        <div class="flex items-center space-x-4">
            <a href="dashboard.php" class="text-gray-600 hover:text-blue-600 transition-colors"></a>
            <div class="flex items-center space-x-4">
                <img src="https://placehold.co/40x40/FF5733/FFFFFF?text=H" alt="Profile" class="w-10 h-10 rounded-full cursor-pointer">
            </div>
        </div>
    </header>

    <main class="flex-grow p-4 overflow-y-auto mt-6">
      <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <div class="flex items-center text-gray-600">
          <a href="apply_leave.php" class="cursor-pointer hover:text-blue-600">Apply for Leave</a>
          <span class="mx-2">&gt;</span>
          <span id="current-leave-type"></span>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
          <span class="font-bold text-blue-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M17 17h.01" />
            </svg>
          </span>
          <span id="form-leave-type">Leave Form</span>
        </h2>
        <p class="text-sm text-gray-500 mb-6" id="form-description">Fill the required fields below to apply for leave as HR. Note: HR submissions will be flagged to be routed to the Municipal Administrator for final approval.</p>

        <form id="leaveAppForm" method="post" enctype="multipart/form-data">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="startDate" class="block text-sm font-medium text-gray-700">Start Date</label>
              <input type="date" id="startDate" name="startDate" required class="mt-1 block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
              <label for="endDate" class="block text-sm font-medium text-gray-700">End Date</label>
              <input type="date" id="endDate" name="endDate" required class="mt-1 block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
              <label for="duration" class="block text-sm font-medium text-gray-700">Duration (Working Days)</label>
              <input type="text" id="duration" name="duration" readonly class="mt-1 block w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
              <label for="salaryInput" class="block text-sm font-medium text-gray-700">Salary</label>
              <input type="number" id="salaryInput" name="salaryInput" value="<?= htmlspecialchars($user_salary) ?>" required class="mt-1 block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" step="0.01" min="0">
            </div>
          </div>
          <div class="mt-6">
            <label for="reason" class="block text-sm font-medium text-gray-700">Reason for Leave</label>
            <textarea id="reason" name="reason" rows="3" required class="mt-1 block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
          </div>
          <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700">Commutation (6.D.)</label>
            <div class="mt-2 space-x-4">
              <label class="inline-flex items-center">
                <input type="radio" name="commutation" value="Requested" class="form-radio text-blue-600" checked>
                <span class="ml-2 text-gray-700">Requested</span>
              </label>
              <label class="inline-flex items-center">
                <input type="radio" name="commutation" value="Not Requested" class="form-radio text-blue-600">
                <span class="ml-2 text-gray-700">Not Requested</span>
              </label>
            </div>
          </div>
          <!-- Relief officer and Department Head selection removed for HR submissions -->
          <div class="mt-6">
            <label for="signature" class="block text-sm font-medium text-gray-700">Upload E-Signature</label>
            <input type="file" id="signature" name="signature" accept="image/*" required class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
          </div>
          <div class="mt-6" id="detailsOfLeave"></div>
          <div class="mt-8">
            <input type="hidden" id="submit_to_municipal" name="submit_to_municipal" value="1" />
            <p class="text-xs text-gray-500 mb-4">This HR leave request will be routed directly to the Municipal Administrator. No Department Head selection is required.</p>
            <div class="flex justify-end space-x-4">
              <button type="reset" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-100 transition-colors">Reset</button>
              <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition-colors">Submit</button>
            </div>
          </div>
        </form>
      </div>
    </main>

    <script>
  const serverUserEmail = <?= json_encode($user_email) ?>;
  const serverUserDepartment = <?= json_encode($user_department) ?>;
    document.addEventListener('DOMContentLoaded', () => {
      let hasServerSignature = false;
      // If a signature already exists on the server, make the upload optional and inform the user
      fetch('../api/employee_signature.php')
        .then(r => r.json())
        .then(sig => {
          if (sig && sig.success && sig.hasSignature) {
            hasServerSignature = true;
            const sigInput = document.getElementById('signature');
            const label = document.querySelector('label[for="signature"]');
            if (sigInput) {
              sigInput.removeAttribute('required');
            }
            if (label) {
              label.innerHTML = 'E-Signature (existing on file will be reused if none is uploaded)';
            }
            const help = document.createElement('p');
            help.className = 'text-xs text-gray-500 mt-1';
            help.textContent = 'We found your saved signature. You may skip uploading; it will be reused.';
            const parent = sigInput ? sigInput.parentElement : null;
            if (parent) parent.appendChild(help);
          }
        })
        .catch(() => {/* non-blocking */});

      // Department Head selection removed for HR submissions.

      // Load relief officers dynamically from employees API
        // Relief officer selection removed for HR; no need to load employee list here.

      const leaveForm = document.getElementById('leaveAppForm');
      const formLeaveType = document.getElementById('form-leave-type');
      const currentLeaveType = document.getElementById('current-leave-type');
      const formDescription = document.getElementById('form-description');

      // Get the leave type from the URL query parameter
      const urlParams = new URLSearchParams(window.location.search);
      const leaveType = urlParams.get('type') || 'Leave Form';

      formLeaveType.textContent = leaveType;
      currentLeaveType.textContent = leaveType;
      formDescription.textContent = `Fill the required fields below to apply for ${leaveType} (HR submission will be routed to Municipal Admin).`;

      // Auto-compute duration
      document.getElementById('startDate').addEventListener('change', computeDuration);
      document.getElementById('endDate').addEventListener('change', computeDuration);
      function computeDuration() {
        const start = new Date(document.getElementById('startDate').value);
        const end = new Date(document.getElementById('endDate').value);
        if (start && end && end >= start) {
          const diff = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
          document.getElementById('duration').value = diff;
        } else {
          document.getElementById('duration').value = '';
        }
      }

      // Section 6.B dynamic fields (same as dept_head implementation)
      const detailsDiv = document.getElementById('detailsOfLeave');
      if (leaveType === 'Vacation Leave' || leaveType === 'Special Privilege Leave') {
        detailsDiv.innerHTML = `
          <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
          <select id="vl_location" name="vl_location" class="form-input border rounded w-full mb-2">
            <option value="Within PH">Within the Philippines</option>
            <option value="Abroad">Abroad (Specify below)</option>
          </select>
          <input type="text" id="vl_location_specify" name="vl_location_specify" class="form-input border rounded w-full mb-2" placeholder="Specify location if abroad">
        `;
      } else if (leaveType === 'Sick Leave') {
        detailsDiv.innerHTML = `
          <label class="block text-sm font-medium text-gray-700 mb-2">Sick Leave Type</label>
          <select id="sl_type" name="sl_type" class="form-input border rounded w-full mb-2">
            <option value="In Hospital">In Hospital</option>
            <option value="Out Patient">Out Patient</option>
          </select>
          <input type="text" id="sl_illness" name="sl_illness" class="form-input border rounded w-full mb-2" placeholder="Specify illness">
        `;
      } else if (leaveType === 'Special Leave for Women') {
        detailsDiv.innerHTML = `
          <label class="block text-sm font-medium text-gray-700 mb-2">Special Leave for Women (Specify Illness)</label>
          <input type="text" id="splwomen_illness" name="splwomen_illness" class="form-input border rounded w-full mb-2">
        `;
      } else if (leaveType === 'Study Leave') {
        detailsDiv.innerHTML = `
          <label class="block text-sm font-medium text-gray-700 mb-2">Study Leave Purpose</label>
          <select id="study_purpose" name="study_purpose" class="form-input border rounded w-full mb-2">
            <option value="Master's Degree">Completion of Master's Degree</option>
            <option value="BAR Review">BAR/Board Examination Review</option>
            <option value="Other">Other</option>
          </select>
          <input type="text" id="study_other" name="study_other" class="form-input border rounded w-full mb-2" placeholder="Specify other purpose">
        `;
      } else if (leaveType === 'Monetization of Leave Credits' || leaveType === 'Terminal Leave') {
        detailsDiv.innerHTML = `<p class="text-xs">No additional details required.</p>`;
      }

      // Prefill some fields from server-side variables
      if (serverUserEmail) {
        document.getElementById('salaryInput').value = document.getElementById('salaryInput').value || '';
      }

      // On submit, validate fields then save data locally and redirect to Civil Form for final submission
      document.getElementById('leaveAppForm').onsubmit = function(e) {
        e.preventDefault();

        function markInvalid(el) {
          if (!el) return;
          el.classList.add('border-red-500');
          el.focus();
        }

        ['vl_location_specify','sl_illness','study_other','signature'].forEach(id => {
          const el = document.getElementById(id);
          if (el) el.classList.remove('border-red-500');
        });

        if (leaveType === 'Vacation Leave' || leaveType === 'Special Privilege Leave') {
          const loc = document.getElementById('vl_location') ? document.getElementById('vl_location').value : '';
          const specify = document.getElementById('vl_location_specify') ? document.getElementById('vl_location_specify').value.trim() : '';
          if (loc === 'Abroad' && specify === '') {
            alert('Please specify the abroad location for Vacation/Special Privilege Leave.');
            markInvalid(document.getElementById('vl_location_specify'));
            return false;
          }
        }

        if (leaveType === 'Sick Leave') {
          const slType = document.getElementById('sl_type') ? document.getElementById('sl_type').value : '';
          const illness = document.getElementById('sl_illness') ? document.getElementById('sl_illness').value.trim() : '';
          if ((slType === 'In Hospital' || slType === 'Out Patient') && illness === '') {
            alert('Please specify the illness for Sick Leave.');
            markInvalid(document.getElementById('sl_illness'));
            return false;
          }
        }

        if (leaveType === 'Study Leave') {
          const purpose = document.getElementById('study_purpose') ? document.getElementById('study_purpose').value : '';
          const other = document.getElementById('study_other') ? document.getElementById('study_other').value.trim() : '';
          if (purpose === 'Other' && other === '') {
            alert('Please specify the study purpose.');
            markInvalid(document.getElementById('study_other'));
            return false;
          }
        }

        // Dept Head validation skipped (removed for HR)

        const sigInput = document.getElementById('signature');
        const hasFile = sigInput && sigInput.files && sigInput.files[0];
        if (sigInput && sigInput.hasAttribute('required') && !hasFile) {
          alert('Please upload your e-signature.');
          markInvalid(sigInput);
          return false;
        }

        if (!hasFile && hasServerSignature) {
          const okReuse = confirm('A saved e-signature was found. Do you want to use your saved signature for this application?');
          if (!okReuse) {
            alert('Please upload a new e-signature if you don\'t want to reuse the saved one.');
            markInvalid(sigInput);
            return false;
          }
        }

        const details = {
          leave_types: [leaveType],
          section6b: {}
        };

        if (leaveType === 'Vacation Leave' || leaveType === 'Special Privilege Leave') {
          details.section6b.vl = {
            location: document.getElementById('vl_location') ? document.getElementById('vl_location').value : '',
            specify: document.getElementById('vl_location_specify') ? document.getElementById('vl_location_specify').value.trim() : ''
          };
        } else if (leaveType === 'Sick Leave') {
          details.section6b.sl = {
            type: document.getElementById('sl_type') ? document.getElementById('sl_type').value : '',
            illness: document.getElementById('sl_illness') ? document.getElementById('sl_illness').value.trim() : ''
          };
        } else if (leaveType === 'Special Leave for Women') {
          details.section6b.splwomen = {
            illness: document.getElementById('splwomen_illness') ? document.getElementById('splwomen_illness').value.trim() : ''
          };
        } else if (leaveType === 'Study Leave') {
          details.section6b.study = {
            purpose: document.getElementById('study_purpose') ? document.getElementById('study_purpose').value : '',
            other: document.getElementById('study_other') ? document.getElementById('study_other').value.trim() : ''
          };
        }

        details.dates = (document.getElementById('startDate').value || '') + ' - ' + (document.getElementById('endDate').value || '');
        details.duration = document.getElementById('duration').value || '';
        details.commutation = document.querySelector('input[name="commutation"]:checked').value || 'Not Requested';
    details.reliefOfficer = ''; // Relief officer removed for HR submissions - leave empty
        details.salary = document.getElementById('salaryInput').value || '';

        function showFormAlert(msg) {
          let alert = document.getElementById('applyAlert');
          if (!alert) {
            alert = document.createElement('div');
            alert.id = 'applyAlert';
            alert.className = 'mb-4 p-3 rounded text-sm bg-red-50 text-red-700 border border-red-200';
            const container = document.querySelector('form#leaveAppForm');
            if (container) container.parentNode.insertBefore(alert, container);
          }
          alert.textContent = msg;
          alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function proceedToCivilForm(signatureDataUri) {
          try {
            localStorage.setItem('leaveDetails', JSON.stringify(details));
            localStorage.setItem('leaveType', leaveType);
            localStorage.setItem('leaveStartDate', document.getElementById('startDate').value);
            localStorage.setItem('leaveEndDate', document.getElementById('endDate').value);
            localStorage.setItem('leaveDuration', details.duration);
            localStorage.setItem('leaveReason', document.getElementById('reason').value);
            localStorage.setItem('leaveCommutation', details.commutation);
            // Relief officer removed for HR submissions; skip storing it
            localStorage.setItem('leaveSalary', details.salary);
            // Flag that this was submitted from HR so the civil form/submitter can route to Municipal Admin
            localStorage.setItem('submit_to_municipal', '1');
            if (serverUserEmail) localStorage.setItem('userEmail', serverUserEmail);
            if (signatureDataUri) localStorage.setItem('leaveSignatureData', signatureDataUri);
            const returnParam = urlParams.get('return') || '';
            if (returnParam) {
              try { localStorage.setItem('leave_return_to', returnParam); } catch(e) {}
            }
          } catch(e) {}
            // Redirect HR flow to the HR Civil Service Form (not the employee version)
            const civilUrl = './civil_form.html' + (urlParams.get('return') ? ('?return=' + encodeURIComponent(urlParams.get('return'))) : '');
            window.location.href = civilUrl;
        }

        (function checkCreditsAndProceed() {
          const dur = parseInt(details.duration, 10) || 0;
          const userEmailForCheck = serverUserEmail || localStorage.getItem('userEmail') || '';
          if (!userEmailForCheck) { finalizeProceed(); return; }

          const creditsApi = `../api/employee_leave_credits.php?email=${encodeURIComponent(userEmailForCheck)}`;

          fetch(creditsApi)
            .then(r => r.json())
            .then(js => {
              if (!js || !js.success || !Array.isArray(js.data)) { finalizeProceed(); return; }
              const items = js.data;
              const normTarget = (leaveType || '').toString().toLowerCase().replace(/[^a-z0-9]+/g,' ').trim();
              let matched = items.find(it => (it.type || '').toString().toLowerCase().replace(/[^a-z0-9]+/g,' ').trim() === normTarget);
              if (!matched) matched = items.find(it => (it.type||'').toString().toLowerCase().includes(normTarget) || normTarget.includes((it.type||'').toString().toLowerCase()));
              if (!matched) { finalizeProceed(); return; }
              const avail = Number(matched.available || 0);
              if (dur <= 0) { showFormAlert('Please select valid start and end dates to compute duration.'); return; }
              if (dur > avail) { showFormAlert(`Insufficient balance: requested ${dur} day(s) but only ${avail} available for ${matched.type}.`); return; }
              finalizeProceed();
            })
            .catch(err => { console.error('Failed to validate leave credits', err); finalizeProceed(); });

          function finalizeProceed() {
            if (hasFile) {
              const reader = new FileReader();
              reader.onload = () => proceedToCivilForm(reader.result);
              reader.onerror = () => proceedToCivilForm(null);
              reader.readAsDataURL(sigInput.files[0]);
            } else {
              proceedToCivilForm(null);
            }
          }
        })();
      };
    });
    </script>
</body>
</html>
