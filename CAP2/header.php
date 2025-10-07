<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

$userType = $_SESSION['userType'] ?? '';
$userRole = $_SESSION['role'] ?? '';
$academicYear = $_SESSION['academicYear'] ?? '2025-2026';

// Default value
$userName = 'User';

// Try to get full name from database for all user types
if (isset($_SESSION['faculty_id'])) {
    $faculty_id = $_SESSION['faculty_id'];
    $stmt = $conn->prepare("SELECT first_name, middle_name, last_name FROM faculty WHERE faculty_id = ?");
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();
    $stmt->bind_result($first_name, $middle_name, $last_name);
    if ($stmt->fetch()) {
        $userName = trim($first_name . ' ' . ($middle_name ? $middle_name . ' ' : '') . $last_name);
    }
    $stmt->close();
} elseif (isset($_SESSION['staff_id'])) {
    $staff_id = $_SESSION['staff_id'];
    $stmt = $conn->prepare("SELECT first_name, middle_name, last_name FROM staff WHERE staff_id = ?");
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $stmt->bind_result($first_name, $middle_name, $last_name);
    if ($stmt->fetch()) {
        $userName = trim($first_name . ' ' . ($middle_name ? $middle_name . ' ' : '') . $last_name);
    }
    $stmt->close();
} elseif (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    // Try faculty first
    $stmt = $conn->prepare("SELECT first_name, middle_name, last_name FROM faculty WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($first_name, $middle_name, $last_name);
    if ($stmt->fetch()) {
        $userName = trim($first_name . ' ' . ($middle_name ? $middle_name . ' ' : '') . $last_name);
        $stmt->close();
    } else {
        $stmt->close();
        // Try staff
        $stmt = $conn->prepare("SELECT first_name, middle_name, last_name FROM staff WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($first_name, $middle_name, $last_name);
        if ($stmt->fetch()) {
            $userName = trim($first_name . ' ' . ($middle_name ? $middle_name . ' ' : '') . $last_name);
            $stmt->close();
        } else {
            $stmt->close();
            // Try users table as fallback (for admin/HR)
            $stmt = $conn->prepare("SELECT first_name, middle_name, last_name FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->bind_result($first_name, $middle_name, $last_name);
            if ($stmt->fetch()) {
                $userName = trim($first_name . ' ' . ($middle_name ? $middle_name . ' ' : '') . $last_name);
            }
            $stmt->close();
        }
    }
}

// Compose user type and role for display
$userIdValue = $userType;
if (!empty($userRole)) {
    $userIdValue .= ' - ' . $userRole;
}
$userIdLabel = 'User Type & Role';
?>
<!-- Header -->
<header class="fixed top-0 left-0 w-full z-50 min-h-[110px] flex items-center overflow-hidden" style="background-image: url('ICONS/CM HEADER.jpg'); background-size: cover; background-position: center;">
  <div class="absolute inset-0 bg-emerald-700/90"></div>
  <div class="relative z-10 flex items-center w-full gap-8 px-8 py-6">
    <img src="SCHOOL LOGO.PNG" alt="School Logo" class="h-14 w-14 rounded-full bg-white shadow mr-6">
    <div class="flex flex-col min-w-[220px]">
      <h1 class="text-white text-2xl font-bold mb-1 drop-shadow">Faculty and Staff Evaluation</h1>
      <span class="text-emerald-100 text-base font-light drop-shadow">Academic Year <?= htmlspecialchars($academicYear) ?></span>
    </div>
    <div class="ml-auto flex items-center gap-6">
      <div class="flex flex-col items-end text-white">
        <span class="font-semibold text-lg drop-shadow"><?= htmlspecialchars($userName) ?></span>
        <span class="text-emerald-100 text-sm drop-shadow">
          <?= htmlspecialchars($userIdValue) ?>
        </span>
      </div>
      <!-- Logout Button -->
      <a href="logout.php" title="Log Out" class="ml-2">
        <img src="ICONS/LOGOUT.png" alt="Log Out" class="w-7 h-7 object-contain filter invert brightness-0">
      </a>
    </div>
  </div>
</header>

