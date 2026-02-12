<?php
session_start();
require_once 'db.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    // Super admin
    if ($_SESSION['user_id'] === 'superadmin') {
        header('Location: super_admin.php');
        exit();
    }
    // Prefer role (new column). Fall back to position for older users.
    $sessRole = strtolower($_SESSION['role'] ?? $_SESSION['position'] ?? '');
    if ($sessRole === 'hr' || $sessRole === 'human resources') {
        header('Location: hr/dashboard.php');
        exit();
    } elseif ($sessRole === 'department_head' || $sessRole === 'dept head' || $sessRole === 'dept_head') {
        header('Location: dept_head/dashboard.php');
        exit();
    } elseif ($sessRole === 'employee') {
        header('Location: employee/dashboard.php');
        exit();
    } else {
        header('Location: dashboard.php');
        exit();
    }
}
if (!isset($error)) $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
    
    // Removed hardcoded bypass; rely on regular DB-auth and password reset flow
    // Normal user login
    $stmt = $pdo->prepare('SELECT id, password, role, status, position FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        // Normalize status and role checks to be forgiving for older data
        $status = strtolower($user['status'] ?? '');
        if ($status !== 'approved') {
            if ($status === 'pending' || $status === 'pending') {
                $error = 'Your account is pending approval by the Super Admin.';
            } elseif ($status === 'declined') {
                $error = 'Your account registration was declined. Contact the administrator.';
            } else {
                // Unknown status - block login for safety
                $error = 'Your account is not active. Please contact the administrator.';
            }
        } else {
            // Successful login
            $_SESSION['user_id'] = $user['id'];
            // Store both role and position for backward compatibility
            $_SESSION['role'] = $user['role'] ?? '';
            $_SESSION['position'] = $user['position'] ?? '';
            $_SESSION['email'] = $email;

            $roleNorm = strtolower($user['role'] ?? $user['position'] ?? '');
            // Special routing for admin emails (post-DB auth only; no password hardcoding)
            if ($email === 'mabiniadmin@gmail.com') {
                $_SESSION['user_id'] = 'superadmin';
                $_SESSION['position'] = 'Super Admin';
                header('Location: super_admin.php');
                exit();
            }
            if ($email === 'municipaladmin@gmail.com') {
                $_SESSION['municipal_logged_in'] = true;
                $_SESSION['municipal_email'] = $email;
                header('Location: municipal/approved_leaves.php');
                exit();
            }

            if ($roleNorm === 'hr' || $roleNorm === 'human resources') {
                header('Location: hr/dashboard.php');
            } elseif ($roleNorm === 'department_head' || $roleNorm === 'dept head' || $roleNorm === 'dept_head') {
                header('Location: dept_head/dashboard.php');
            } elseif ($roleNorm === 'employee') {
                header('Location: employee/dashboard.php');
            } else {
                header('Location: dashboard.php');
            }
            exit();
        }
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mabini | Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

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
            transition: all 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            opacity: 0;
            visibility: hidden;
        }

        .modal-bg.is-open {
            opacity: 1;
            visibility: visible;
        }

        .modal-container {
            display: flex;
            max-width: 900px;
            width: 90%;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 100px rgba(30, 64, 175, 0.1);
            position: relative;
            background: #fff;
            transform: scale(0.7) translateY(-50px) rotateX(10deg);
            transition: all 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            opacity: 0;
        }
        
        .modal-bg.is-open .modal-container {
            transform: scale(1) translateY(0) rotateX(0deg);
            opacity: 1;
        }

        .modal-title {
            font-size: 2.25rem;
            font-weight: 700;
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            animation: titleSlide 0.8s ease-out 0.3s both;
        }

        .modal-desc {
            font-size: 1rem;
            color: #6b7280;
            margin-bottom: 2rem;
            animation: titleSlide 0.8s ease-out 0.4s both;
        }

        @keyframes titleSlide {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
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

        .modal-left {
            padding: 3rem;
            flex: 1.5;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: linear-gradient(to bottom, #ffffff 0%, #f9fafb 100%);
        }

        .modal-right {
            flex: 2;
            display: none;
            position: relative;
            overflow: hidden;
        }
        
        .modal-right::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(30, 64, 175, 0.1) 0%, rgba(59, 130, 246, 0.2) 100%);
            z-index: 1;
        }
        
        .modal-right img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            animation: imageZoom 20s ease-in-out infinite alternate;
        }

        @keyframes imageZoom {
            0% {
                transform: scale(1);
            }
            100% {
                transform: scale(1.1);
            }
        }

        .input-button {
            padding: 0.875rem 2rem;
            outline: none;
            border: none;
            color: #fff;
            border-radius: 9999px;
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(30, 64, 175, 0.3);
            position: relative;
            overflow: hidden;
        }

        .input-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .input-button:hover {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(30, 64, 175, 0.4);
        }

        .input-button:hover::before {
            left: 100%;
        }

        .input-button:active {
            transform: translateY(0);
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
            padding: 0.875rem 1.25rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 1.25rem;
            transition: all 0.3s ease;
            position: relative;
            background: white;
            animation: inputSlide 0.6s ease-out backwards;
        }

        .input-block:nth-child(1) {
            animation-delay: 0.5s;
        }

        .input-block:nth-child(2) {
            animation-delay: 0.6s;
        }

        @keyframes inputSlide {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .input-block input {
            outline: none;
            border: none;
            padding: 0.25rem 0 0;
            font-size: 1rem;
            background: transparent;
            color: #1f2937;
        }

        .input-block input::placeholder {
            color: #d1d5db;
        }

        .input-block:focus-within {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            transform: translateY(-2px);
        }

        .input-block:hover {
            border-color: #9ca3af;
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
            top: 1.25rem;
            right: 1.25rem;
            width: 36px;
            height: 36px;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid #e5e7eb;
            border-radius: 50%;
            cursor: pointer;
            color: #6b7280;
            transition: all 0.3s ease;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-button:hover {
            background: #fee2e2;
            border-color: #ef4444;
            color: #dc2626;
            transform: rotate(90deg);
        }

        .logo-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .logo-button {
            cursor: pointer;
            border-radius: 50%;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: logoEntrance 1.5s ease-in-out;
            max-width: 150px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .logo-button:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.25);
        }

        .logo-button:active {
            transform: scale(0.95);
        }

        .logo-reminder {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            font-size: 0.95rem;
            font-weight: 500;
            color: #1e40af;
            animation: reminderPulse 2s ease-in-out infinite;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: 2px solid rgba(30, 64, 175, 0.2);
        }

        .logo-reminder i {
            animation: pointerBounce 1s ease-in-out infinite;
        }

        @keyframes reminderPulse {
            0%, 100% {
                transform: translateY(0);
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            }
            50% {
                transform: translateY(-5px);
                box-shadow: 0 8px 20px rgba(30, 64, 175, 0.2);
            }
        }

        @keyframes pointerBounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-3px);
            }
        }

        .modal-bg.is-open ~ .logo-container {
            opacity: 0;
            pointer-events: none;
            transform: scale(0.5);
        }

        .logo-container {
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        @keyframes logoEntrance {
            0% {
                opacity: 0;
                transform: scale(0.5) rotate(-10deg);
            }
            50% {
                transform: scale(1.1) rotate(5deg);
            }
            100% {
                opacity: 1;
                transform: scale(1) rotate(0deg);
            }
        }

        @keyframes logoClick {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1) rotate(5deg);
            }
            100% {
                transform: scale(1);
            }
        }

        @media(min-width: 768px) {
            .modal-right {
                display: block;
            }
        }
    </style>
</head>
<body>
    <div class="logo-container">
        <img src="assets/logo.png" alt="Mabini Logo" class="logo-button" id="open-modal-logo">
        <div class="logo-reminder">
            <i class="fas fa-hand-pointer"></i>
            Click the logo to log in
        </div>
    </div>

    <div class="modal-bg">
        <div class="modal-container">
            <button class="close-button" id="close-button">
                <i class="fas fa-times text-xl"></i>
            </button>
            <div class="modal-left">
                <h1 class="modal-title">Welcome Back!</h1>
                <p class="modal-desc">Please log in to your account.</p>
                <?php if ($error): ?>
                    <div class="modal-message" style="color:#dc2626;background:#fee2e2;display:block;">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                <form id="login-form" method="post" action="">
                    <div class="input-block">
                        <label for="email" class="input-label">Email</label>
                        <input type="email" name="email" id="email" placeholder="email@example.com" required>
                    </div>
                    <div class="input-block">
                        <label for="password" class="input-label">Password</label>
                        <input type="password" name="password" id="password" placeholder="••••••••" required>
                        <i id="password-toggle" class="fas fa-eye password-toggle"></i>
                    </div>
                    <div class="modal-buttons">
                        <a href="#" id="forgot-password-link">Forgot your password?</a>
                        <button type="submit" class="input-button">Login</button>
                    </div>
                </form>
            </div>
            <div class="modal-right">
                <img src="assets/mabinibg1.jpg" alt="A view of the Mabini, Batangas landscape">
            </div>
        </div>
    </div>

    <script>
        const body = document.querySelector("body");
        const modalBg = document.querySelector(".modal-bg");
        const openButton = document.getElementById("open-modal-logo");
        const closeButton = document.getElementById("close-button");
        const loginForm = document.getElementById("login-form");
        const passwordInput = document.getElementById("password");
        const passwordToggle = document.getElementById("password-toggle");
        const forgotPasswordLink = document.getElementById("forgot-password-link");
        // const statusMessage = document.getElementById("status-message");

        const openModal = () => {
            modalBg.classList.add("is-open");
            body.style.overflow = "hidden";
        };

        const closeModal = () => {
            modalBg.classList.remove("is-open");
            body.style.overflow = "auto";
        };

        openButton.addEventListener("click", () => {
            openButton.classList.add('clicked');
            setTimeout(() => {
                openModal();
                openButton.classList.remove('clicked');
            }, 300);
        });
        
        closeButton.addEventListener("click", closeModal);
        
        // Removed click outside to close - login container will stay visible
        // modalBg.addEventListener("click", (e) => {
        //     if (e.target === modalBg) {
        //         closeModal();
        //     }
        // });

        // Removed ESC key to close - only close button works now
        // document.onkeydown = evt => {
        //     evt = evt || window.event;
        //     if (evt.key === "Escape" || evt.keyCode === 27) {
        //         closeModal();
        //     }
        // };

        // Remove JS form redirect, let PHP handle login
        // loginForm.addEventListener('submit', (e) => {
        //     e.preventDefault();
        //     window.location.href = 'index.html';
        // });

        passwordToggle.addEventListener('click', () => {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            passwordToggle.classList.toggle('fa-eye');
            passwordToggle.classList.toggle('fa-eye-slash');
        });

        forgotPasswordLink.addEventListener('click', (e) => {
            e.preventDefault();
            window.location.href = 'forgot_password.php';
        });

        // Auto-open modal on page load
        window.addEventListener('load', () => {
            setTimeout(() => {
                openModal();
            }, 800); // Wait 800ms after page load before opening
        });
    </script>
</body>
</html>