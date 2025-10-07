<?php
session_start();
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'Admin') {
    header('Location: index.php');
    exit;
}

require_once('config.php');
require_once 'userController.php';
require_once 'session.php';

// Redirect to login if session is not active
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Example admin info
$admin = $user ?? [];
$admin_id = htmlspecialchars($admin['user_id'] ?? '');
$adminName = htmlspecialchars($admin['fullName'] ?? "Admin User");

// DRY header variables
$userName = $adminName;
$userType = 'Admin'; // ✅ Align with DB
$userIdLabel = 'Admin ID';
$userIdValue = $admin_id;
$academicYear = '2025-2026'; // Or use a dynamic value if available

// --- UPDATED: Get all faculty using new PK ---
$facultySql = "SELECT id FROM faculty";
$facultyRes = $conn->query($facultySql);
$facultyIds = [];
while ($row = $facultyRes->fetch_assoc()) {
    $facultyIds[] = $row['id'];
}
$totalFaculty = count($facultyIds);

// --- UPDATED: Get number of attendance questions using new schema ---
$section_title = 'Attendance in School Activities'; // Example, adjust as needed
$qid_stmt = $conn->prepare("SELECT id FROM questionnaires WHERE title = ?");
$qid_stmt->bind_param('s', $section_title);
$qid_stmt->execute();
$qid_stmt->bind_result($questionnaire_id);
$qid_stmt->fetch();
$qid_stmt->close();

$question_count = 0;
if ($questionnaire_id) {
    $qstmt = $conn->prepare("SELECT COUNT(*) FROM questions WHERE questionnaire_id = ?");
    $qstmt->bind_param('i', $questionnaire_id);
    $qstmt->execute();
    $qstmt->bind_result($question_count);
    $qstmt->fetch();
    $qstmt->close();
}

// --- UPDATED: Get number of faculty fully evaluated by this admin using evaluation_responses ---
$admin_id = $_SESSION['user_id'];
$completed = 0;
foreach ($facultyIds as $fid) {
    // Count completed answers for this faculty by this admin for this questionnaire
    $stmt = $conn->prepare("SELECT COUNT(*) FROM evaluation_responses WHERE evaluator_id = ? AND evaluated_id = ? AND questionnaire_id = ? AND status = 'completed'");
    $stmt->bind_param('iii', $admin_id, $fid, $questionnaire_id);
    $stmt->execute();
    $stmt->bind_result($eval_count);
    $stmt->fetch();
    $stmt->close();
    if ($eval_count >= $question_count && $question_count > 0) {
        $completed++;
    }
}
$pendingEvalCount = $totalFaculty - $completed;

// Get userType and role from session
$userType = $_SESSION['userType'] ?? '';
$userRole = $_SESSION['role'] ?? ''; // Make sure you set this in your login/session logic

// HR can access both modules
$showFacultyAttendance = false; // REMOVE ADMIN EVALUATION MODULE
$showRankings = ($userType === 'Admin');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        html,
        body {
            height: 100%;
        }

        body {
            min-height: 100vh;
            background: #f4f6fa;
        }

        .sidebar {
            z-index: 50;
        }

        .main-content-area {
            position: fixed;
            left: 260px;
            top: 110px;
            right: 0;
            bottom: 0;
            width: auto;
            height: auto;
            padding: 0;
            background: #f4f6fa;
        }

        @media (max-width: 900px) {
            .sidebar {
                position: static !important;
                width: 100% !important;
                height: auto !important;
                border-right: none
            }

            .main-content-area {
                margin-left: 0;
                padding-top: 70px;
                left: 0;
                top: 110px;
            }
        }

        @media (max-width: 640px) {
            .main-content-area {
                top: 180px;
            }
        }
    </style>
</head>

<body class="bg-[#f4f6fa] min-h-screen">
    <?php include 'header.php'; ?>
    <!-- Sidebar-->
    <div class="sidebar fixed left-0 top-[110px] h-[calc(100vh-110px)] w-[260px] flex flex-col py-6 px-4 z-40 overflow-y-auto bg-white border-r shadow-lg">
        <div class="flex flex-col items-center mb-10">
            <img src="SCHOOL LOGO.PNG" alt="Logo" class="h-16 w-16 rounded-full bg-white p-1 shadow mb-2" />
            <span class="text-xl font-bold text-[#467C4F] mt-2">Admin Panel</span>
        </div>
        <!-- Sidebar Navigation -->
        <nav class="flex flex-col gap-1">
            <button class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition text-left hover:bg-emerald-50 focus:bg-emerald-100 font-medium text-[#467C4F]" data-content="dashboard">
                <span class="material-icons text-emerald-700">dashboard</span>
                <span>Dashboard</span>
            </button>
            <!-- Admin Evaluation removed -->
            <?php if ($showRankings): ?>
            <button class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition text-left hover:bg-emerald-50 focus:bg-emerald-100 font-medium text-[#467C4F]" data-content="rankings">
                <span class="material-icons text-emerald-700">emoji_events</span>
                <span>Faculty & Staff Rankings</span>
            </button>
            <?php endif; ?>
            <!-- Evaluation Management Dropdown -->
            <div class="relative" id="evalFormDropdown">
                <button type="button"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg transition text-left hover:bg-emerald-50 focus:bg-emerald-100 font-medium text-[#467C4F] w-full"
                    onclick="toggleDropdown('evalFormDropdownMenu', 'evalFormDropdownChevron')">
                    <span class="material-icons text-emerald-700">description</span>
                    <span>Evaluation Management</span>
                    <span id="evalFormDropdownChevron" class="material-icons ml-auto transition-transform">expand_more</span>
                </button>
                <!-- Dropdown submenu -->
                <ul id="evalFormDropdownMenu"
                    class="hidden flex-col left-full top-0 ml-2 w-56 bg-white text-[#23492f] rounded shadow-lg z-20">
                    <li>
                        <a href="questionnaires.php" class="block px-4 py-2 hover:bg-[#e6f4ea] eval-submenu" data-content="questionnaires">Questionnaires</a>
                    </li>
                    <li>
                        <a href="facultySection.php" class="block px-4 py-2 hover:bg-[#e6f4ea] eval-submenu" data-content="faculty-section">Faculty and Staff</a>
                    </li>
                    <li>
                        <a href="curriculum.php" class="block px-4 py-2 hover:bg-[#e6f4ea] eval-submenu" data-content="curriculum">Curriculum</a>
                    </li>
                </ul>
            </div>
            <button class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition text-left hover:bg-emerald-50 focus:bg-emerald-100 font-medium text-[#467C4F]" data-content="accounts">
                <span class="material-icons text-emerald-700">person</span>
                <span>Users Account Management</span>
            </button>
        </nav>
    </div>
    <!-- Main Content -->
    <div class="main-content-area">
        <iframe
            id="mainContentFrame"
            src="dashboardHome.php"
            style="width:100%; height:100vh; min-height:100vh; border:none; background:#f4f6fa;"
            frameborder="0"
        ></iframe>
    </div>
    <script>
        // Dropdown toggle
        function toggleDropdown(menuId, chevronId) {
            const menu = document.getElementById(menuId);
            const chevron = document.getElementById(chevronId);
            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
                chevron.classList.add('rotate-180');
            } else {
                menu.classList.add('hidden');
                chevron.classList.remove('rotate-180');
            }
        }
        // Submenu click handler
        document.querySelectorAll('.eval-submenu').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                let page = 'dashboardHome.php';
                switch (link.getAttribute('data-content')) {
                    case 'questionnaires':
                        page = 'questionnaires.php#questionnaires';
                        break;
                    case 'faculty-section':
                        page = 'facultySection.php#faculty';
                        break;
                    case 'curriculum':
                        page = 'curriculum.php#curriculum';
                        break;
                }
                document.getElementById('mainContentFrame').src = page;
                document.getElementById('evalFormDropdownMenu').classList.add('hidden');
                document.getElementById('evalFormDropdownChevron').classList.remove('rotate-180');
            });
        });
        // Sidebar main links
        document.querySelectorAll('.sidebar-link').forEach(btn => {
            btn.addEventListener('click', function() {
                const content = btn.getAttribute('data-content');
                let page = 'dashboardHome.php';
                if (content === 'dashboard') page = 'dashboardHome.php';
                if (content === 'rankings') page = 'facultyandstaffRanking.php';
                if (content === 'accounts') page = 'userAccountManagement.php';
                if (content === 'evaluation-management') page = 'evaluationManagement.php';
                document.getElementById('mainContentFrame').src = page;
            });
        });
    </script>
</body>
</html>
