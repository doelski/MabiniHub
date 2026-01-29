<?php
require_once __DIR__ . '/auth_guard.php';
prevent_if_authenticated();
require_once 'db.php';
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Trim and normalize inputs
    $lastname = trim($_POST['lastname'] ?? '');
    $firstname = trim($_POST['firstname'] ?? '');
    $mi = trim($_POST['mi'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $role = trim($_POST['role'] ?? 'employee');
    $contact_no = preg_replace('/\s+/', '', $_POST['contact_no'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $employee_id = trim($_POST['employee_id'] ?? '');
    $gender = trim($_POST['gender'] ?? '');

    // Server-side validations
    $nameRegex = "/^[A-Za-z\s\-']+$/"; // letters, spaces, hyphen, apostrophe
    $miRegex = "/^[A-Za-z]?$/";         // optional single letter
    $phoneRegex = "/^09\d{9}$/";       // PH mobile: 11 digits starting with 09

    if (!preg_match($nameRegex, $lastname) || !preg_match($nameRegex, $firstname)) {
        $error = 'Names must contain letters only (no numbers).';
    } elseif ($mi !== '' && !preg_match($miRegex, $mi)) {
        $error = 'Middle initial must be a single letter.';
    } elseif ($contact_no !== '' && !preg_match($phoneRegex, $contact_no)) {
        $error = 'Contact number must be 11 digits starting with 09.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } elseif (!preg_match('/@gmail\.com$/i', $email)) {
        $error = 'Email must be a Gmail address (ending in @gmail.com).';
    } elseif (!isset($_SESSION['verified_email']) || $_SESSION['verified_email'] !== $email) {
        $error = 'Please verify your Gmail address before registering.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (empty($department) || empty($lastname) || empty($firstname) || empty($position) || empty($email) || empty($employee_id)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Ensure position is one of the allowed employment categories
        $allowedPositions = ['Permanent','Casual','JO','OJT'];
        if (!in_array($position, $allowedPositions)) {
            $error = 'Invalid position selected.';
        } else {
            // Enforce only one Department Head per department
            if ($role === 'department_head') {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'department_head' AND department = ? LIMIT 1");
                $stmt->execute([$department]);
                if ($stmt->fetch()) {
                    $error = 'A Department Head is already assigned to this department.';
                }
            }

            if (!$error) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email already registered.';
            } else {
                // Check if employee_id is already taken
                $stmt = $pdo->prepare('SELECT id FROM users WHERE employee_id = ?');
                $stmt->execute([$employee_id]);
                if ($stmt->fetch()) {
                    $error = 'Employee ID already exists. Please use a unique ID.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    // All new accounts require super admin approval: set status to 'pending'
                    $pendingStatus = 'pending';

                    $stmt = $pdo->prepare('INSERT INTO users (email, password, department, lastname, firstname, mi, position, role, contact_no, status, employee_id, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    if ($stmt->execute([$email, $hash, $department, $lastname, $firstname, $mi, $position, $role, $contact_no, $pendingStatus, $employee_id, $gender])) {
                        $success = 'Registration submitted! Your Employee ID: <strong>' . $employee_id . '</strong>. Awaiting super admin approval.';
                    } else {
                        $error = 'Registration failed. Please try again.';
                    }
                }
            }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mabini | Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            color: #4b5563;
            background-image: url('assets/mabinibg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
        }
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(8px);
            z-index: -1;
        }
        .modal-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: all 0.5s ease-in-out;
            opacity: 1;
            pointer-events: auto;
        }
        .modal-container {
            display: flex;
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            position: relative;
            background: #fff;
            transform: scale(1);
            transition: transform 0.4s ease-in-out;
        }
        .modal-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        .modal-desc {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 1.5rem;
        }
        .modal-message {
            margin-bottom: 1rem;
            color: #1e40af;
            font-size: 0.875rem;
            font-weight: 500;
            text-align: center;
            padding: 0.5rem;
            background-color: #e0f2fe;
            border-radius: 4px;
        }
        .modal-message.error {
            color: #dc2626;
            background: #fee2e2;
        }
        .modal-message.success {
            color: #16a34a;
            background: #dcfce7;
        }
        .modal-left {
            padding: 2.5rem;
            flex: 1.5;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            overflow-y: auto;
            max-height: 90vh;
        }
        .modal-right {
            flex: 2;
            display: none;
            position: relative;
        }
        .modal-right img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .input-button {
            padding: 0.65rem 1.25rem;
            outline: none;
            border: none;
            color: #fff;
            border-radius: 9999px;
            background: #1e40af;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 0.95rem;
            transition: background 0.3s ease;
            cursor: pointer;
        }
        .input-button:hover {
            background: #1e3a8a;
        }
        .input-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.05em;
            color: #1e40af;
            transition: color 0.3s;
        }
        .input-block {
            display: flex;
            flex-direction: column;
            padding: 0.6rem 0.85rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }
        .input-block input {
            outline: none;
            border: none;
            padding: 0.25rem 0 0;
            font-size: 1rem;
            background: transparent;
        }
        .input-block input::placeholder {
            color: #d1d5db;
        }
        .input-block:focus-within {
            border-color: #1e40af;
            box-shadow: 0 0 0 2px rgba(30, 64, 175, 0.2);
        }
        .input-block .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #9ca3af;
            transition: color 0.2s;
        }
        .input-block .password-toggle:hover {
            color: #4b5563;
        }
        .modal-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }
        .modal-buttons a {
            color: #6b7280;
            font-size: 0.875rem;
            transition: color 0.2s;
        }
        .modal-buttons a:hover {
            color: #1e40af;
        }
        .close-button {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 32px;
            height: 32px;
            background: transparent;
            border: none;
            cursor: pointer;
            color: #9ca3af;
            transition: color 0.2s;
            z-index: 10;
        }
        .close-button:hover {
            color: #4b5563;
        }
        .logo-button {
            cursor: pointer;
            border-radius: 50%;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .logo-button:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        @media(min-width: 768px) {
            .modal-right {
                display: block;
            }
        }
    </style>
</head>
<body>
    <img src="assets/logo.png" alt="Mabini Logo" class="logo-button" id="open-modal-logo">
    <div class="modal-bg">
        <div class="modal-container">
            <button class="close-button" id="close-button" onclick="window.location.href='index.php';return false;">
                <i class="fas fa-times text-xl"></i>
            </button>
            <div class="modal-left">
                <h1 class="modal-title">Create Account</h1>
                <p class="modal-desc">Register a new account below.</p>
                <?php if ($error): ?>
                    <div class="modal-message error">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="modal-message success">
                        <?= $success ?>
                    </div>
                <?php endif; ?>
                <div id="client-message" class="modal-message error" style="display:none;"></div>
                <form id="register-form" method="post" action="">
                    <!-- Step 1: Name -->
                    <div class="step" id="step-1">
                        <div class="input-block">
                            <label for="lastname" class="input-label">Last Name</label>
                            <input type="text" name="lastname" id="lastname" placeholder="Last Name" required pattern="[A-Za-z \-']+" title="Letters, spaces, hyphen (-) and apostrophe (') only">
                        </div>
                        <div class="input-block">
                            <label for="firstname" class="input-label">First Name</label>
                            <input type="text" name="firstname" id="firstname" placeholder="First Name" required pattern="[A-Za-z \-']+" title="Letters, spaces, hyphen (-) and apostrophe (') only">
                        </div>
                        <div class="input-block">
                            <label for="mi" class="input-label">Middle Initial</label>
                            <input type="text" name="mi" id="mi" placeholder="M" maxlength="1" pattern="[A-Za-z]" title="Single letter only">
                        </div>
                        <div class="modal-buttons">
                            <button type="button" class="input-button" id="next-1">Next</button>
                        </div>
                    </div>
                    <!-- Step 2: Department/Position -->
                    <div class="step" id="step-2" style="display:none;">
                        <div class="input-block">
                            <label for="department" class="input-label">Department</label>
                            <select name="department" id="department" required>
                                <option value="">Select Department</option>
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
                        <div class="input-block">
                            <label for="position" class="input-label">Position</label>
                            <select name="position" id="position" required>
                                <option value="">Select Position</option>
                                <option value="Permanent">Permanent</option>
                                <option value="Casual">Casual</option>
                                <option value="JO">JO</option>
                                <option value="OJT">OJT</option>
                            </select>
                        </div>
                        <div class="input-block">
                            <label for="role" class="input-label">Role (how this account will be used)</label>
                            <select name="role" id="role" required>
                                <option value="employee">Employee</option>
                                <option value="department_head">Department Head</option>
                                <option value="hr">HR</option>
                            </select>
                        </div>
                        <div class="input-block">
                            <label for="employee_id" class="input-label">Employee ID</label>
                            <input type="text" name="employee_id" id="employee_id" placeholder="e.g., EMP2025-0001" required pattern="[A-Za-z0-9\-]+" title="Enter unique Employee ID">
                        </div>
                        <div class="input-block">
                            <label for="contact_no" class="input-label">Contact No.</label>
                            <input type="text" name="contact_no" id="contact_no" placeholder="09XXXXXXXXX" inputmode="numeric" pattern="09[0-9]{9}" maxlength="11" title="Must start with 09 and be 11 digits" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                        </div>
                        <div class="input-block">
                            <label for="gender" class="input-label">Gender</label>
                            <select name="gender" id="gender" required>
                                <option value="">Select Gender</option>
                                <option value="M">Male</option>
                                <option value="F">Female</option>
                            </select>
                        </div>
                        <div class="modal-buttons">
                            <button type="button" class="input-button" id="prev-2">Back</button>
                            <button type="button" class="input-button" id="next-2">Next</button>
                        </div>
                    </div>
                    <!-- Step 3: Email/Password -->
                    <div class="step" id="step-3" style="display:none;">
                                            <div class="input-block">
                                                <label for="email" class="input-label">Gmail Address</label>
                                                <input type="email" name="email" id="email" placeholder="example@gmail.com" required pattern="^[^@\s]+@gmail\.com$" title="Must be a valid @gmail.com address">
                                            </div>
                                            <div class="input-block" id="email-verification-block">
                                                <label class="input-label">Email Verification</label>
                                                <div style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center;">
                                                    <button type="button" class="input-button" id="send-code-btn" style="flex:1;">Send Code</button>
                                                    <input type="text" id="verification_code" placeholder="Enter 6-digit code" maxlength="6" pattern="[0-9]{6}" style="flex:1;display:none;border:1px solid #e5e7eb;border-radius:8px;padding:0.5rem;background:#f9fafb;" inputmode="numeric">
                                                    <button type="button" class="input-button" id="verify-code-btn" style="flex:1;display:none;">Verify</button>
                                                </div>
                                                <small id="verification-hint" style="display:block;margin-top:0.5rem;color:#6b7280;">You must verify your Gmail before setting a password.</small>
                                            </div>
                        <div class="input-block">
                            <label for="password" class="input-label">Password</label>
                                                <input type="password" name="password" id="password" placeholder="••••••••" required disabled>
                            <i id="password-toggle" class="fas fa-eye password-toggle"></i>
                        </div>
                        <div class="input-block">
                            <label for="confirm_password" class="input-label">Confirm Password</label>
                                                <input type="password" name="confirm_password" id="confirm_password" placeholder="••••••••" required disabled>
                            <i id="confirm-toggle" class="fas fa-eye password-toggle"></i>
                        </div>
                        <div class="modal-buttons">
                            <button type="button" class="input-button" id="prev-3">Back</button>
                                                <button type="submit" class="input-button" id="register-btn" disabled>Register</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-right">
                <img src="assets/mabinibg1.jpg" alt="A view of the Mabini, Batangas landscape">
            </div>
        </div>
    </div>
    <script>
        // Multi-step form logic with validation
        const step1 = document.getElementById('step-1');
        const step2 = document.getElementById('step-2');
        const step3 = document.getElementById('step-3');
        const clientMessage = document.getElementById('client-message');

        const nameRegex = /^[A-Za-z \-']+$/;
        const miRegex = /^[A-Za-z]?$/;
        const phoneRegex = /^09\d{9}$/;

        function showClientError(msg) {
            clientMessage.textContent = msg;
            clientMessage.style.display = 'block';
        }
        function clearClientError() {
            clientMessage.textContent = '';
            clientMessage.style.display = 'none';
        }

        document.getElementById('next-1').onclick = function() {
            const ln = document.getElementById('lastname').value.trim();
            const fn = document.getElementById('firstname').value.trim();
            const mi = document.getElementById('mi').value.trim();
            if (!ln || !fn) { showClientError('Please fill in your name.'); return; }
            if (!nameRegex.test(ln) || !nameRegex.test(fn)) { showClientError('Name fields must be letters only.'); return; }
            if (mi && !miRegex.test(mi)) { showClientError('Middle initial must be a single letter.'); return; }
            clearClientError();
            step1.style.display = 'none';
            step2.style.display = 'block';
        };
        document.getElementById('next-2').onclick = async function() {
            const dept = document.getElementById('department').value;
            const pos = document.getElementById('position').value;
            const role = document.getElementById('role').value;
            const empId = document.getElementById('employee_id').value.trim();
            const gender = document.getElementById('gender').value;
            const phone = document.getElementById('contact_no').value.trim();
            if (!dept || !pos || !role || !empId || !gender) { showClientError('Please fill in all required fields in Step 2.'); return; }
            if (phone && !phoneRegex.test(phone)) { showClientError('Contact number must start with 09 and be 11 digits.'); return; }
            // Additional client-side check: only one Department Head per department
            if (role === 'department_head') {
                try {
                    const res = await fetch('api/dept_heads.php');
                    const heads = await res.json();
                    const exists = Array.isArray(heads) && heads.some(h => (h.department || '') === dept);
                    if (exists) {
                        showClientError('A Department Head is already assigned to this department.');
                        return;
                    }
                } catch (e) {
                    // If the check fails, fail closed to prevent duplicates
                    showClientError('Unable to verify this department’s head right now. Please try again later.');
                    return;
                }
            }
            clearClientError();
            step2.style.display = 'none';
            step3.style.display = 'block';
        };
        document.getElementById('prev-2').onclick = function() {
            clearClientError();
            step2.style.display = 'none';
            step1.style.display = 'block';
        };
        document.getElementById('prev-3').onclick = function() {
            clearClientError();
            step3.style.display = 'none';
            step2.style.display = 'block';
        };
        // Password toggle logic
        const passwordInput = document.getElementById("password");
        const passwordToggle = document.getElementById("password-toggle");
        const confirmInput = document.getElementById("confirm_password");
        const confirmToggle = document.getElementById("confirm-toggle");
        passwordToggle.addEventListener('click', () => {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            passwordToggle.classList.toggle('fa-eye');
            passwordToggle.classList.toggle('fa-eye-slash');
        });
        confirmToggle.addEventListener('click', () => {
            const type = confirmInput.type === 'password' ? 'text' : 'password';
            confirmInput.type = type;
            confirmToggle.classList.toggle('fa-eye');
            confirmToggle.classList.toggle('fa-eye-slash');
        });

        // Position-based role filtering
        const positionSelect = document.getElementById('position');
        const roleSelect = document.getElementById('role');
        
        function filterRoles() {
            const position = positionSelect.value;
            const roleOptions = roleSelect.querySelectorAll('option');
            
            if (position === 'OJT' || position === 'JO') {
                // JO/OJT can only be Employee
                roleOptions.forEach(opt => {
                    if (opt.value === 'employee') {
                        opt.style.display = '';
                        opt.disabled = false;
                    } else {
                        opt.style.display = 'none';
                        opt.disabled = true;
                    }
                });
                roleSelect.value = 'employee';
                roleSelect.disabled = true;
            } else if (position === 'Casual') {
                // Casual can be Employee or Department Head (NO HR)
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
                if (roleSelect.value === 'hr') {
                    roleSelect.value = 'employee';
                }
                roleSelect.disabled = false;
            } else if (position === 'Permanent') {
                // Permanent can be any role
                roleOptions.forEach(opt => {
                    opt.style.display = '';
                    opt.disabled = false;
                });
                roleSelect.disabled = false;
            }
        }
        
        positionSelect.addEventListener('change', filterRoles);
        filterRoles();

        // Email verification logic
        const sendCodeBtn = document.getElementById('send-code-btn');
        const verifyCodeBtn = document.getElementById('verify-code-btn');
        const verificationInput = document.getElementById('verification_code');
        const registerBtn = document.getElementById('register-btn');

        let codeSent = false;
        sendCodeBtn.addEventListener('click', async () => {
            clearClientError();
            const email = document.getElementById('email').value.trim();
            if (!email.match(/@gmail\.com$/i)) { showClientError('Please enter a valid @gmail.com address.'); return; }
            sendCodeBtn.disabled = true;
            sendCodeBtn.textContent = 'Sending...';
            // Reveal code input & verify button early so user sees where to enter
            verificationInput.style.display = 'block';
            verifyCodeBtn.style.display = 'block';
            try {
                const res = await fetch('api/send_verification_code.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({email: email, purpose: 'registration'})
                });
                const data = await res.json();
                if (!res.ok || data.status !== 'ok') {
                    showClientError(data.message || 'Failed to send code.');
                    sendCodeBtn.disabled = false;
                    sendCodeBtn.textContent = 'Send Code';
                    return;
                }
                codeSent = true;
                showClientError('Code sent! Please check your Gmail inbox.');
                clientMessage.classList.remove('error');
                clientMessage.classList.add('success');
                // Enable resend after 60 seconds to prevent spam
                let countdown = 60;
                sendCodeBtn.textContent = `Resend Code (${countdown}s)`;
                sendCodeBtn.disabled = true;
                const timer = setInterval(() => {
                    countdown--;
                    if (countdown > 0) {
                        sendCodeBtn.textContent = `Resend Code (${countdown}s)`;
                    } else {
                        clearInterval(timer);
                        sendCodeBtn.textContent = 'Resend Code';
                        sendCodeBtn.disabled = false;
                    }
                }, 1000);
            } catch (e) {
                showClientError('Network or server error sending code. If this persists, contact admin.');
                sendCodeBtn.disabled = false;
                sendCodeBtn.textContent = 'Send Code';
            }
        });

        verifyCodeBtn.addEventListener('click', async () => {
            clearClientError();
            if (!codeSent) { showClientError('Send a code first.'); return; }
            const email = document.getElementById('email').value.trim();
            const code = verificationInput.value.trim();
            if (!code.match(/^[0-9]{6}$/)) { showClientError('Enter the 6-digit code.'); return; }
            verifyCodeBtn.disabled = true;
            verifyCodeBtn.textContent = 'Verifying...';
            try {
                const res = await fetch('api/verify_registration_code.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({email: email, code: code})
                });
                const data = await res.json();
                if (!res.ok || data.status !== 'ok') {
                    showClientError(data.message || 'Invalid code.');
                    verifyCodeBtn.disabled = false;
                    verifyCodeBtn.textContent = 'Verify';
                    return;
                }
                // Success
                clientMessage.textContent = 'Email verified! You may set your password.';
                clientMessage.style.display = 'block';
                clientMessage.classList.remove('error');
                clientMessage.classList.add('success');
                document.getElementById('email').readOnly = true;
                passwordInput.disabled = false;
                confirmInput.disabled = false;
                registerBtn.disabled = false;
                verifyCodeBtn.textContent = 'Verified';
                verifyCodeBtn.disabled = true;
                sendCodeBtn.disabled = true;
            } catch (e) {
                showClientError('Network error verifying code.');
                verifyCodeBtn.disabled = false;
                verifyCodeBtn.textContent = 'Verify';
            }
        });
    </script>
</body>
</html>
