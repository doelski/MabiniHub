<?php
/**
 * Authentication Guard System
 * Provides centralized authentication and authorization for all pages
 * Usage: require_once 'auth_guard.php'; at the top of protected pages
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user's role
 * @return string|null
 */
function get_user_role() {
    if (!is_logged_in()) {
        return null;
    }
    
    // Super admin special case
    if ($_SESSION['user_id'] === 'superadmin') {
        return 'super_admin';
    }
    
    // Normalize role from session (prefer 'role' over 'position')
    $role = strtolower($_SESSION['role'] ?? $_SESSION['position'] ?? '');
    
    // Normalize various role names to standard values
    if ($role === 'human resources' || $role === 'hr') {
        return 'hr';
    } elseif ($role === 'department_head' || $role === 'dept head' || $role === 'dept_head') {
        return 'department_head';
    } elseif ($role === 'employee') {
        return 'employee';
    }
    
    return $role ?: null;
}

/**
 * Check if user has specific role
 * @param string|array $allowed_roles Single role or array of allowed roles
 * @return bool
 */
function has_role($allowed_roles) {
    $current_role = get_user_role();
    
    if (!$current_role) {
        return false;
    }
    
    if (is_array($allowed_roles)) {
        return in_array($current_role, $allowed_roles);
    }
    
    return $current_role === $allowed_roles;
}

/**
 * Require authentication - redirect to login if not authenticated
 * @param string $redirect_to Optional custom redirect URL
 */
function require_auth($redirect_to = '../index.php') {
    if (!is_logged_in()) {
        // Clear any existing session data
        session_unset();
        session_destroy();
        
        // Redirect to login page
        header('Location: ' . $redirect_to);
        exit();
    }
}

/**
 * Require specific role(s) - redirect if user doesn't have required role
 * @param string|array $allowed_roles Single role or array of allowed roles
 * @param string $redirect_to Optional custom redirect URL
 */
function require_role($allowed_roles, $redirect_to = '../index.php') {
    // First check if user is logged in
    require_auth($redirect_to);
    
    // Then check if user has required role
    if (!has_role($allowed_roles)) {
        // User is logged in but doesn't have permission
        // Redirect to their appropriate dashboard
        redirect_to_dashboard();
        exit();
    }
}

/**
 * Redirect user to their appropriate dashboard based on role
 */
function redirect_to_dashboard() {
    $role = get_user_role();
    
    switch ($role) {
        case 'super_admin':
            header('Location: ' . get_base_path() . 'super_admin.php');
            break;
        case 'hr':
            header('Location: ' . get_base_path() . 'hr/dashboard.php');
            break;
        case 'department_head':
            header('Location: ' . get_base_path() . 'dept_head/dashboard.php');
            break;
        case 'employee':
            header('Location: ' . get_base_path() . 'employee/dashboard.php');
            break;
        default:
            // If role is unknown, log out for security
            session_unset();
            session_destroy();
            header('Location: ' . get_base_path() . 'index.php');
            break;
    }
    exit();
}

/**
 * Get base path relative to current file location
 * @return string
 */
function get_base_path() {
    return '/';
}

/**
 * Check if current user is super admin
 * @return bool
 */
function is_super_admin() {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] === 'superadmin';
}

/**
 * Check if current user is municipal admin
 * @return bool
 */
function is_municipal_admin() {
    return isset($_SESSION['municipal_logged_in']) && $_SESSION['municipal_logged_in'] === true;
}

/**
 * Require municipal admin access
 * @param string $redirect_to Optional custom redirect URL
 */
function require_municipal_auth($redirect_to = '../index.php') {
    if (!is_municipal_admin()) {
        session_unset();
        session_destroy();
        header('Location: ' . $redirect_to);
        exit();
    }
}

/**
 * Prevent access if already logged in (for login/register pages)
 * @param string $redirect_to Optional custom redirect URL (will use role-based if not provided)
 */
function prevent_if_authenticated($redirect_to = null) {
    if (is_logged_in()) {
        if ($redirect_to) {
            header('Location: ' . $redirect_to);
        } else {
            redirect_to_dashboard();
        }
        exit();
    }
}

/**
 * API authentication - returns JSON error instead of redirecting
 * @param string|array $allowed_roles Optional roles to check
 * @return bool Returns true if authenticated (and authorized if roles specified)
 */
function require_api_auth($allowed_roles = null) {
    header('Content-Type: application/json');

    // Accept either standard app login or municipal admin session
    $authenticated = is_logged_in() || is_municipal_admin();
    if (!$authenticated) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required. Please log in.'
        ]);
        exit();
    }

    // When roles are provided, allow municipal admins implicitly
    if ($allowed_roles !== null) {
        $has = has_role($allowed_roles);
        if (!$has && !is_municipal_admin()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'You do not have permission to access this resource.'
            ]);
            exit();
        }
    }

    return true;
}

/**
 * Log out current user
 * @param string $redirect_to Where to redirect after logout
 */
function logout_user($redirect_to = 'index.php') {
    // Clear all session data
    $_SESSION = array();
    
    // Destroy session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy session
    session_destroy();
    
    // Redirect to login
    header('Location: ' . $redirect_to);
    exit();
}
