<?php
// Start session securely
function secureSessionStart() {
    $cookieParams = session_get_cookie_params();

    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,               // Session cookie expires when browser closes
            'path' => $cookieParams['path'],
            'domain' => $cookieParams['domain'],
            'secure' => isset($_SERVER['HTTPS']),  // Only send cookie over HTTPS if applicable
            'httponly' => true,            // JS can't access cookie (prevent XSS)
            'samesite' => 'Strict'         // Prevent CSRF
        ]);
        session_start();
    }
}

// Call this at the top of every page that needs a session
secureSessionStart();

// Function to check if user is logged in and optionally if user type matches
function checkUserSession(string $requiredUserType = null) {
    if (!isset($_SESSION['user_id'], $_SESSION['user_type'])) {
        header("Location: index.php");
        exit();
    }

    if ($requiredUserType !== null && $_SESSION['user_type'] !== $requiredUserType) {
        header("HTTP/1.1 403 Forbidden");
        echo "Access denied.";
        exit();
    }

    return [
        'user_id' => $_SESSION['user_id'],
        'userType' => $_SESSION['user_type'],
        'fullName' => $_SESSION['full_name'] ?? '',
        'avatar' => $_SESSION['avatar'] ?? 'default-avatar.png',
        // Add more session data here if needed
    ];
}

// Call this after successful login to set session securely
function secureLogin(int $studentID, string $userType, string $fullName, string $avatar = '') {
    session_regenerate_id(true);

    $_SESSION['user_id'] = $studentID;
    $_SESSION['user_type'] = $userType;
    $_SESSION['full_name'] = $fullName;
    $_SESSION['avatar'] = $avatar;
}

// Call this to logout securely
function secureLogout() {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
}
?>
