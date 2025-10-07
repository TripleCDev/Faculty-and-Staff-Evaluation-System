<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';
session_start();

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    header("Location: index.php?error=All fields are required.");
    exit();
}

// Fetch user by username (case-insensitive)
$sql = "SELECT id, userName, password, userType, role, status FROM users WHERE LOWER(userName) = LOWER(?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();

    // Block inactive users
    if (strtolower($user['status']) === 'inactive') {
        $stmt->close();
        $conn->close();
        header("Location: index.php?error=Your account is inactive. Please contact admin.");
        exit();
    }

    // Password check (plain text for now)
    if ($password === $user['password']) {
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['userType']   = $user['userType'];
        $_SESSION['user_name']  = $user['userName'];
        $_SESSION['fullName']   = $user['userName'];
        $_SESSION['role']       = $user['role'];

        // Redirect based on userType and role
        if ($user['userType'] === 'Regular' && in_array($user['role'], ['Faculty', 'Staff', 'Program Head', 'Head Staff', 'HR'])) {
            $_SESSION['user_role'] = 'regular';
            $redirect = "regularDashboard.php";
        } elseif ($user['userType'] === 'Admin') {
            $_SESSION['user_role'] = 'admin';
            $redirect = "systemAdminDashboard.php";
        } else {
            $redirect = "index.php?error=Unauthorized role.";
        }

        $stmt->close();
        $conn->close();
        header("Location: $redirect");
        exit();

    } else {
        $stmt->close();
        $conn->close();
        header("Location: index.php?error=Invalid password.");
        exit();
    }
} else {
    $stmt->close();
    $conn->close();
    header("Location: index.php?error=User not found.");
    exit();
}
?>
