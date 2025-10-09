<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

$userType = $_SESSION['userType'] ?? '';
$userRole = $_SESSION['role'] ?? '';
// Get active curriculum year range from curriculum table
$academicYear = 'N/A';
$stmt = $conn->prepare("SELECT curriculum_year_start, curriculum_year_end FROM curriculum WHERE status = 'active' ORDER BY curriculum_id DESC LIMIT 1");
$stmt->execute();
$stmt->bind_result($year_start, $year_end);
if ($stmt->fetch()) {
  $academicYear = $year_start . '-' . $year_end;
}
$stmt->close();

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
        $userName = trim(ucwords(strtolower($first_name)) . ' ' . ($middle_name ? ucwords(strtolower($middle_name)) . ' ' : '') . ucwords(strtolower($last_name)));
    }
    $stmt->close();
} elseif (isset($_SESSION['staff_id'])) {
    $staff_id = $_SESSION['staff_id'];
    $stmt = $conn->prepare("SELECT first_name, middle_name, last_name FROM staff WHERE staff_id = ?");
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $stmt->bind_result($first_name, $middle_name, $last_name);
    if ($stmt->fetch()) {
        $userName = trim(ucwords(strtolower($first_name)) . ' ' . ($middle_name ? ucwords(strtolower($middle_name)) . ' ' : '') . ucwords(strtolower($last_name)));
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
        $userName = trim(ucwords(strtolower($first_name)) . ' ' . ($middle_name ? ucwords(strtolower($middle_name)) . ' ' : '') . ucwords(strtolower($last_name)));
        $stmt->close();
    } else {
        $stmt->close();
        // Try staff
        $stmt = $conn->prepare("SELECT first_name, middle_name, last_name FROM staff WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($first_name, $middle_name, $last_name);
        if ($stmt->fetch()) {
            $userName = trim(ucwords(strtolower($first_name)) . ' ' . ($middle_name ? ucwords(strtolower($middle_name)) . ' ' : '') . ucwords(strtolower($last_name)));
            $stmt->close();
        } else {
            $stmt->close();
            $stmt = $conn->prepare("SELECT first_name, middle_name, last_name FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->bind_result($first_name, $middle_name, $last_name);
            if ($stmt->fetch()) {
                $userName = trim(ucwords(strtolower($first_name)) . ' ' . ($middle_name ? ucwords(strtolower($middle_name)) . ' ' : '') . ucwords(strtolower($last_name)));
            }
            $stmt->close();
        }
    }
}

// Get department name for current user (faculty, staff, or users table)
$departmentName = '';
if (isset($_SESSION['faculty_id'])) {
    $faculty_id = $_SESSION['faculty_id'];
    $stmt = $conn->prepare("SELECT d.department_name FROM faculty f LEFT JOIN departments d ON f.department_id = d.id WHERE f.faculty_id = ?");
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();
    $stmt->bind_result($departmentName);
    $stmt->fetch();
    $stmt->close();
} elseif (isset($_SESSION['staff_id'])) {
    $staff_id = $_SESSION['staff_id'];
    $stmt = $conn->prepare("SELECT d.department_name FROM staff s LEFT JOIN departments d ON s.department_id = d.id WHERE s.staff_id = ?");
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $stmt->bind_result($departmentName);
    $stmt->fetch();
    $stmt->close();
} elseif (isset($_SESSION['user_id'])) {
  $user_id = $_SESSION['user_id'];
  $dept_id = null;

  // Try to get department_id from faculty table
  $stmt = $conn->prepare("SELECT department_id FROM faculty WHERE user_id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $stmt->bind_result($dept_id);
  if ($stmt->fetch() && $dept_id) {
    $stmt->close();
  } else {
    $stmt->close();
    // Try to get department_id from staff table
    $stmt = $conn->prepare("SELECT department_id FROM staff WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($dept_id);
    if ($stmt->fetch() && $dept_id) {
      $stmt->close();
    } else {
      $stmt->close();
      $dept_id = null;
    }
  }

  // Get department name from departments table if dept_id is found
  if ($dept_id) {
    $stmt = $conn->prepare("SELECT department_name FROM departments WHERE id = ?");
    $stmt->bind_param("i", $dept_id);
    $stmt->execute();
    $stmt->bind_result($departmentName);
    $stmt->fetch();
    $stmt->close();
  } else {
    $departmentName = '';
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
<header class="fixed top-0 left-0 w-full z-50 min-h-[90px] flex items-center overflow-hidden"
  style="background-image: url('ICONS/CM HEADER.jpg'); background-size: cover; background-position: center;">
  <div class="absolute inset-0 bg-emerald-700/90"></div>
  <div class="relative z-10 flex flex-col sm:flex-row items-center w-full gap-4 sm:gap-8 px-4 sm:px-8 py-4 sm:py-6">
    <img src="SCHOOL LOGO.PNG" alt="School Logo"
         class="h-12 w-12 sm:h-14 sm:w-14 rounded-full bg-white shadow mb-2 sm:mb-0 sm:mr-6 flex-shrink-0">
    <div class="flex flex-col min-w-[180px] sm:min-w-[220px] text-center sm:text-left">
      <h1 class="text-white text-xl sm:text-2xl font-bold mb-1 drop-shadow">Faculty and Staff Evaluation</h1>
      <span class="text-emerald-100 text-sm sm:text-base font-light drop-shadow">
      Academic Year 
      <?php
      $academicYear = 'N/A';
      $stmt = $conn->prepare("SELECT curriculum_year_start, curriculum_year_end FROM curriculum WHERE status = 'active' ORDER BY curriculum_id DESC LIMIT 1");
      $stmt->execute();
      $stmt->bind_result($year_start, $year_end);
      if ($stmt->fetch()) {
        $academicYear = $year_start . '-' . $year_end;
      }
      $stmt->close();
      echo htmlspecialchars($academicYear);
      ?>
      </span>
    </div>
    <div class="sm:ml-auto flex flex-col sm:flex-row items-center gap-4 sm:gap-6 mt-2 sm:mt-0 w-full sm:w-auto">
      <div class="flex flex-col items-center sm:items-end text-white">
        <span class="font-semibold text-base sm:text-lg drop-shadow"><?= htmlspecialchars($userName) ?></span>
        <span class="text-emerald-100 text-xs sm:text-sm drop-shadow">
          <?= htmlspecialchars($userIdValue) ?>
        </span>
        <?php if ($departmentName): ?>
            <span class="text-emerald-200 text-xs sm:text-sm drop-shadow">
                <?= htmlspecialchars($departmentName) ?>
            </span>
        <?php endif; ?>
      </div>
      <!-- Logout Button -->
      <a href="logout.php" title="Log Out" class="ml-0 sm:ml-2">
        <img src="ICONS/LOGOUT.png" alt="Log Out"
             class="w-6 h-6 sm:w-7 sm:h-7 object-contain filter invert brightness-0">
      </a>
    </div>
  </div>
</header>

