<?php
require_once('config.php');
require_once 'userController.php';
require_once 'session.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- ROLE CHECKS ---
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? '';
$user_type = $_SESSION['user_role'] ?? '';
$isAdmin = in_array($user_role, ['Admin', 'HR']);
$isRegular = $user_type === 'regular' && in_array($user_role, ['Faculty', 'Staff', 'Head Staff', 'Program Head', 'HR']);

// --- FETCH ACTIVE CURRICULUM (NO HARDCODE) ---
$curriculum_id = null;
$curriculum_title = '';
$curriculum_year_start = '';
$curriculum_year_end = '';
$curriculum_semester = '';
$curriculum_desc = '';
$curriculum_status = '';
$curriculum_date_created = '';
$curr_stmt = $conn->query("SELECT * FROM curriculum WHERE status = 'active' ORDER BY date_created DESC LIMIT 1");

if ($curr_stmt && $row = $curr_stmt->fetch_assoc()) {
    $curriculum_id = $row['curriculum_id'];
    $curriculum_title = $row['curriculum_title'];
    $curriculum_year_start = $row['curriculum_year_start'];
    $curriculum_year_end = $row['curriculum_year_end'];
    $curriculum_semester = $row['semester'];
    $curriculum_desc = $row['description'];
    $curriculum_status = $row['status'];
    $curriculum_date_created = $row['date_created'];
}

// Add this mapping function at the top (after session and config includes)
function mapRoleToEnum($role) {
    $role = strtolower(trim($role));
    switch ($role) {
        case 'faculty': return 'Faculty';
        case 'staff': return 'Staff';
        case 'head staff': return 'Head Staff';
        case 'program head': return 'Program Head';
        case 'hr': return 'HR';
        default: return 'Faculty';
    }
}

// --- ADMIN/HR LOGIC ---
$adminEvaluations = [];
if ($isAdmin) {
    // Helper functions for admin logic
    function getFullName($f) {
        return trim($f['first_name'] . ' ' . ($f['middle_name'] ? $f['middle_name'] . ' ' : '') . $f['last_name']);
    }
    function getAdminEvaluationStatus($conn, $evaluatee_id, $admin_id, $type, $questionnaire_id = 0) {
        $sql = "SELECT COUNT(*) FROM evaluation_responses 
                WHERE evaluated_id = ? 
                  AND evaluator_id = ?";
        $params = [$evaluatee_id, $admin_id];
        $types = "ii";
        if ($questionnaire_id > 0) {
            $sql .= " AND questionnaire_id = ?";
            $params[] = $questionnaire_id;
            $types .= "i";
        }
        $sql .= " AND status = 'completed'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $eval_count = 0;
        $stmt->bind_result($eval_count);
        $stmt->fetch();
        $stmt->close();
        return ($eval_count > 0) ? 'completed' : 'pending';
    }
    // Fetch all faculty and staff
    $faculty = $conn->query("
        SELECT 
            f.id AS id,
            f.first_name,
            f.middle_name,
            f.last_name,
            d.department_name,
            p.program_name,
            f.role,
            'faculty' AS type
        FROM faculty f
        LEFT JOIN departments d ON f.department_id = d.id
        LEFT JOIN programs p ON f.program_id = p.program_id
        WHERE f.role IN ('Faculty', 'Program Head')
    ")->fetch_all(MYSQLI_ASSOC);

    $staff = $conn->query("
        SELECT 
            s.id AS id,
            s.first_name,
            s.middle_name,
            s.last_name,
            d.department_name,
            NULL AS program_name,
            s.role,
            'staff' AS type
        FROM staff s
        LEFT JOIN departments d ON s.department_id = d.id
        WHERE s.role IN ('Staff', 'Head Staff')
    ")->fetch_all(MYSQLI_ASSOC);

    $evaluatees = array_merge($faculty, $staff);

    $pending_evaluatees = [];
    $completed_evaluatees = [];
    foreach ($evaluatees as $f) {
        $id = $f['id'] ?? null;
        $type = $f['type'] ?? null;
        if ($id && $type) {
            $status = getAdminEvaluationStatus($conn, $id, $user_id, $type, 0);
        } else {
            $status = 'pending';
        }
        $f['is_completed'] = ($status === 'completed');
        $f['status'] = $status;
        $f['full_name'] = getFullName($f);

        if ($status === 'completed') {
            $completed_evaluatees[] = $f;
        } else {
            $pending_evaluatees[] = $f;
        }
    }
    $adminEvaluations = array_merge($pending_evaluatees, $completed_evaluatees);
}

// --- REGULAR USER LOGIC ---
if ($isRegular) {
    // Fetch user info
    if ($user_role === 'Faculty' || $user_role === 'Program Head') {
        $stmt = $conn->prepare("
            SELECT f.faculty_id, f.first_name, f.last_name, f.department_id, f.program_id
            FROM faculty f
            WHERE f.user_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $faculty_fullName = "Unknown Faculty";
            $faculty_id = $user_id;
            $faculty_department = '';
            $faculty_program = '';
        } else {
            $faculty_id = $user['faculty_id'];
            $faculty_fullName = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
            $faculty_department = $user['department_id'];
            $faculty_program = $user['program_id'];
        }
    } elseif ($user_role === 'Staff' || $user_role === 'Head Staff') {
        $stmt = $conn->prepare("
            SELECT s.staff_id, s.first_name, s.middle_name, s.last_name
            FROM staff s
            WHERE s.user_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $faculty_fullName = "Unknown Staff";
            $faculty_id = $user_id;
        } else {
            $faculty_id = $user['staff_id'];
            $faculty_fullName = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
        }
        $faculty_department = '';
        $faculty_program = '';
    } elseif ($user_role === 'HR') {
        // HR as self-evaluation (fetch from staff table)
        $stmt = $conn->prepare("
            SELECT s.staff_id, s.first_name, s.last_name
            FROM staff s
            WHERE s.user_id = ? AND s.role = 'HR'
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $faculty_fullName = "Unknown HR";
            $faculty_id = $user_id;
        } else {
            $faculty_id = $user['staff_id'];
            $faculty_fullName = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
        }
        $faculty_department = '';
        $faculty_program = '';
    } else {
        $faculty_id = $user_id;
        $faculty_fullName = htmlspecialchars($_SESSION['fullName'] ?? 'User');
        $faculty_department = '';
        $faculty_program = '';
    }

    // Academic year & semester from curriculum
    $currentAcademicYear = ($curriculum_year_start && $curriculum_year_end) ? "{$curriculum_year_start}-{$curriculum_year_end}" : '';
    $currentSemester = $curriculum_semester ? "{$curriculum_semester} Sem $currentAcademicYear" : $currentAcademicYear;

    // --- SELF EVALUATION CHECK ---
    $selfEvaluationStatus = 'pending';
    if ($user_role === 'Faculty' || $user_role === 'Program Head' ) {
        $checkSelf = $conn->prepare("
            SELECT COUNT(*) AS count 
            FROM evaluation_responses 
            WHERE evaluator_id = ? 
              AND evaluated_id = ?
              AND status = 'completed'
              AND curriculum_id = ?
        ");
        $checkSelf->bind_param("iii", $user_id, $faculty_id, $curriculum_id);
    } elseif ($user_role === 'Staff' || $user_role === 'Head Staff' || $user_role === 'HR' ) {
        $checkSelf = $conn->prepare("
            SELECT COUNT(*) AS count 
            FROM evaluation_responses 
            WHERE evaluator_id = ? 
              AND evaluated_id = ?
              AND status = 'completed'
              AND curriculum_id = ?
        ");
        $checkSelf->bind_param("iii", $user_id, $faculty_id, $curriculum_id);
    }
    if (isset($checkSelf)) {
        $checkSelf->execute();
        $resultSelf = $checkSelf->get_result()->fetch_assoc();
        $checkSelf->close();
        if ($resultSelf && $resultSelf['count'] > 0) {
            $selfEvaluationStatus = 'completed';
        }
    }

    // Get peers in same department and program (exclude self)
    $instructorEvaluations = [];
    if ($user_role === 'Faculty' || $user_role === 'Program Head') {
        if ($faculty_department && $faculty_program) {
            $stmt = $conn->prepare("
                SELECT f.faculty_id, f.first_name, f.middle_name, f.last_name, f.department_id, d.department_name, f.program_id, p.program_name
                FROM faculty f
                LEFT JOIN departments d ON f.department_id = d.id
                LEFT JOIN programs p ON f.program_id = p.program_id
                WHERE f.department_id = ? AND f.program_id = ? AND f.faculty_id != ?
            ");
            $stmt->bind_param("iii", $faculty_department, $faculty_program, $faculty_id);
            $stmt->execute();
            $peers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            // Determine evaluation type for peer evaluation
            $peer_eval_type = ($user_role === 'Program Head') ? 'ProgramHeadToFaculty' : 'Peer';

            // Get the active peer/programhead evaluation questionnaire for this department/program
            $questionnaire_id = 0;
            $q_assign = $conn->prepare("
                SELECT questionnaire_id
                FROM questionnaire_assignments
                WHERE status = 'active'
                  AND evaluation_type = ?
                  AND (
                    department_id = ? OR department_id IS NULL
                  )
                  AND (
                    program_id = ? OR program_id IS NULL
                  )
                ORDER BY department_id DESC, program_id DESC
                LIMIT 1
            ");
            $q_assign->bind_param("sii", $peer_eval_type, $faculty_department, $faculty_program);
            $q_assign->execute();
            $q_assign->bind_result($questionnaire_id);
            $q_assign->fetch();
            $q_assign->close();

            $peerIds = array_column($peers, 'faculty_id');
            $peerStatus = [];
            if (!empty($peerIds)) {
                $inQuery = implode(',', array_fill(0, count($peerIds), '?'));

                // Use curriculum semester and year
                $currentSemester = $curriculum_semester ? "{$curriculum_semester} Sem $currentAcademicYear" : $currentAcademicYear;

                $sql = "SELECT evaluated_id FROM evaluation_responses 
                        WHERE evaluator_id = ? 
                        AND evaluated_id IN ($inQuery) 
                        AND status = 'completed'
                        AND questionnaire_id = ?
                        AND curriculum_id = ?";
                $types = "i" . str_repeat('i', count($peerIds)) . "ii";
                $params = array_merge([$user_id], $peerIds, [$questionnaire_id, $curriculum_id]);
                $check = $conn->prepare($sql);
                $check->bind_param($types, ...$params);
                $check->execute();
                $result = $check->get_result();
                while ($row = $result->fetch_assoc()) {
                    $peerStatus[$row['evaluated_id']] = true;
                }
                $check->close();
            }

            foreach ($peers as $p) {
                $peerId = $p['faculty_id'];
                $middleName = $p['middle_name'] ? ' ' . htmlspecialchars($p['middle_name']) : '';
                $fullName = htmlspecialchars($p['first_name']) . $middleName . ' ' . htmlspecialchars($p['last_name']);

                // Get the number of questions for this questionnaire
                $qCountStmt = $conn->prepare("SELECT COUNT(*) FROM questions WHERE questionnaire_id = ?");
                $qCountStmt->bind_param("i", $questionnaire_id);
                $qCountStmt->execute();
                $qCountStmt->bind_result($question_count);
                $qCountStmt->fetch();
                $qCountStmt->close();

                // Count the number of completed responses for this peer
                $stmt = $conn->prepare("
                    SELECT COUNT(*) 
                    FROM evaluation_responses 
                    WHERE evaluator_id = ? 
                      AND evaluated_id = ? 
                      AND questionnaire_id = ? 
                      AND curriculum_id = ? 
                      AND status = 'completed'
                ");
                $stmt->bind_param(
                    "iiii",
                    $user_id,         
                    $peerId,           
                    $questionnaire_id,  
                    $curriculum_id   
                );
                $stmt->execute();
                $stmt->bind_result($completed_count);
                $stmt->fetch();
                $stmt->close();

                // Only mark as completed if questionnaire and questions exist
                $has_evaluated = ($questionnaire_id > 0 && $question_count > 0 && $completed_count >= $question_count);

                $instructorEvaluations[] = [
                    'id' => $peerId,
                    'name' => $fullName,
                    'course' => "Course Placeholder",
                    'semester' => $currentSemester,
                    'schedule' => "Flexible",
                    'status' => $has_evaluated ? 'completed' : 'pending',
                    'department_name' => $p['department_name'],
                    'program_name' => $p['program_name']
                ];
            }
        }
    } elseif ($user_role === 'Staff' || $user_role === 'Head Staff') {
        $stmt = $conn->prepare("
            SELECT staff_id, first_name, middle_name, last_name, department_id
            FROM staff 
            WHERE staff_id != ?
        ");
        $stmt->bind_param("i", $faculty_id);
        $stmt->execute();
        $peers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $peerIds = array_column($peers, 'staff_id');
        $peerStatus = [];
        if (!empty($peerIds)) {
            $inQuery = implode(',', array_fill(0, count($peerIds), '?'));
            $types = str_repeat('i', count($peerIds) + 2);
            $params = array_merge([$user_id], $peerIds, [$curriculum_id]);

            $sql = "SELECT evaluated_id FROM evaluation_responses WHERE evaluator_id = ? AND evaluated_id IN ($inQuery) AND status = 'completed' AND curriculum_id = ?";
            $check = $conn->prepare($sql);
            $check->bind_param($types, ...$params);
            $check->execute();
            $result = $check->get_result();
            while ($row = $result->fetch_assoc()) {
                $peerStatus[$row['evaluated_id']] = true;
            }
            $check->close();
        }

        foreach ($peers as $p) {
            $peerId = $p['staff_id'];
            $middleName = $p['middle_name'] ? ' ' . htmlspecialchars($p['middle_name']) : '';
            $fullName = htmlspecialchars($p['first_name']) . $middleName . ' ' . htmlspecialchars($p['last_name']);
            $has_evaluated = isset($peerStatus[$peerId]);

            $instructorEvaluations[] = [
                'id' => $peerId,
                'name' => $fullName,
                'course' => "Course Placeholder",
                'semester' => $currentSemester,
                'schedule' => "Flexible",
                'status' => $has_evaluated ? 'completed' : 'pending',
                'department_name' => $p['department_name'],
                'program_name' => $p['program_name']
            ];
        }
    } elseif ($user_role === 'HR') {
        // HR can evaluate all faculty and staff (except self)
        $faculty = $conn->query("
            SELECT faculty_id AS id, first_name, middle_name, last_name, department_id, d.department_name, faculty.program_id, p.program_name, 'faculty' AS type
            FROM faculty
            LEFT JOIN departments d ON faculty.department_id = d.id
            LEFT JOIN programs p ON faculty.program_id = p.program_id
            WHERE faculty_id != $faculty_id
        ")->fetch_all(MYSQLI_ASSOC);

        $staff = $conn->query("
            SELECT staff_id AS id, first_name, middle_name, last_name, department_id, d.department_name, NULL AS program_id, NULL AS program_name, 'staff' AS type
            FROM staff
            LEFT JOIN departments d ON staff.department_id = d.id
            WHERE staff_id != $faculty_id
        ")->fetch_all(MYSQLI_ASSOC);

        $peers = array_merge($faculty, $staff);

        $peerIds = array_column($peers, 'id');
        $peerStatus = [];
        if (!empty($peerIds)) {
            $inQuery = implode(',', array_fill(0, count($peerIds), '?'));
            $types = str_repeat('i', count($peerIds) + 2);
            $params = array_merge([$user_id], $peerIds, [$curriculum_id]);

            $sql = "SELECT evaluated_id FROM evaluation_responses WHERE evaluator_id = ? AND evaluated_id IN ($inQuery) AND status = 'completed' AND curriculum_id = ?";
            $check = $conn->prepare($sql);
            $check->bind_param($types, ...$params);
            $check->execute();
            $result = $check->get_result();
            while ($row = $result->fetch_assoc()) {
                $peerStatus[$row['evaluated_id']] = true;
            }
            $check->close();
        }

        foreach ($peers as $p) {
            $peerId = $p['id'];
            $middleName = $p['middle_name'] ? ' ' . htmlspecialchars($p['middle_name']) : '';
            $fullName = htmlspecialchars($p['first_name']) . $middleName . ' ' . htmlspecialchars($p['last_name']);
            $has_evaluated = isset($peerStatus[$peerId]);

            $instructorEvaluations[] = [
                'id' => $peerId,
                'name' => $fullName,
                'course' => "N/A",
                'semester' => $currentSemester,
                'schedule' => "Flexible",
                'status' => $has_evaluated ? 'completed' : 'pending',
                'department_name' => $p['department_name'],
                'program_name' => $p['program_name']
            ];
        }
    }

    $pendingPeers = array_filter($instructorEvaluations, fn($e) => $e['status'] === 'pending');
    $completedPeers = array_filter($instructorEvaluations, fn($e) => $e['status'] === 'completed');
    $instructorEvaluations = array_merge($pendingPeers, $completedPeers);

    $totalInstructors = count($instructorEvaluations);
    $evaluated = count($completedPeers);
    $pending = $totalInstructors - $evaluated;

    $userName = $faculty_fullName;
    $userType = $user_role;
    $userIdLabel = 'Faculty ID';
    $userIdValue = $faculty_id;
    $academicYear = $currentAcademicYear;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluation Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-green-50 font-sans min-h-screen">
    <?php include 'header.php'; ?>

    <main class="max-w-8xl mx-auto px-2 sm:px-8 py-4 sm:py-12 mt-16 sm:mt-24">
        <div class="bg-white shadow rounded-xl p-6 mb-8">
            <h2 class="text-xl font-semibold mb-2">Welcome to your Dashboard</h2>
            <p class="text-gray-700">Complete your evaluations for the current semester.</p>
        </div>
        <?php if ($isRegular): ?>
            <!-- Regular User Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
                <div class="flex items-center bg-white rounded-xl shadow p-5">
                    <div class="bg-blue-100 text-blue-600 rounded-full p-3 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-4.13a4 4 0 10-8 0 4 4 0 008 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-gray-500 text-sm">Total Faculty</div>
                        <div class="text-2xl font-bold"><?php echo $totalInstructors; ?></div>
                    </div>
                </div>
                <div class="flex items-center bg-white rounded-xl shadow p-5">
                    <div class="bg-green-100 text-green-600 rounded-full p-3 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-gray-500 text-sm">Evaluated Faculty</div>
                        <div class="text-2xl font-bold"><?php echo $evaluated; ?></div>
                    </div>
                </div>
                <div class="flex items-center bg-white rounded-xl shadow p-5">
                    <div class="bg-yellow-100 text-yellow-600 rounded-full p-3 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M12 8v4l3 3" />
                            <circle cx="12" cy="12" r="10" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-gray-500 text-sm">Pending Evaluations</div>
                        <div class="text-2xl font-bold"><?php echo $pending; ?></div>
                    </div>
                </div>
            </div>

            <!-- Self Evaluation -->
            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Self Evaluation</h2>
                <div class="space-y-4">
                    <div class="bg-white rounded-xl shadow hover:shadow-xl transition p-5 flex flex-col sm:flex-row sm:justify-between sm:items-center border-t-4 border-yellow-400">
                        <div>
                            <p class="font-semibold text-gray-800"><?= $faculty_fullName; ?></p>
                            <p class="text-gray-700">Self Evaluation</p>
                            <p class="text-gray-500 text-sm"><?= $currentSemester; ?></p>
                        </div>
                        <div class="mt-2 sm:mt-0 flex items-center gap-2">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $selfEvaluationStatus === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'; ?>">
                                <?= ucfirst($selfEvaluationStatus); ?>
                            </span>
                            <?php if ($selfEvaluationStatus === 'completed'): ?>
                                <a href="viewEvaluation.php?evaluatee_id=<?= $faculty_id ?>&type=self"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                                    View Self Evaluation
                                </a>
                            <?php else: ?>
                                <a href="regular_evaluation_form.php?evaluatee_id=<?= $faculty_id ?>&type=self"
                                    class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                                    Start Self Evaluation
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- HR Evaluation -->
            <section>
                <h2 class="text-xl font-semibold text-gray-800 mb-4">
                    <?php if ($user_role === 'HR'): ?>
                        Faculty and Staff Available for Evaluation
                    <?php else: ?>
                        Peers Available for Evaluation
                    <?php endif; ?>
                </h2>
                <?php if ($totalInstructors === 0): ?>
                    <p class="text-gray-600">No staff assigned for evaluation.</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                        <?php foreach ($instructorEvaluations as $i): ?>
                            <div class="bg-white rounded-xl shadow p-5 text-center border-t-4 <?= $i['status'] === 'pending' ? 'border-yellow-400' : 'border-green-400'; ?>">
                                <div class="w-16 h-16 mx-auto rounded-full bg-emerald-100 flex items-center justify-center mb-3">
                                    <span class="text-2xl font-bold text-emerald-700"><?= strtoupper(substr($i['name'], 0, 1)); ?></span>
                                </div>
                                <?php
                                $nameParts = explode(' ', $i['name'], 2);
                                $peerId = $i['id'];
                                $middleName = '';
                                $middleStmt = $conn->prepare("SELECT middle_name FROM faculty WHERE faculty_id = ?");
                                $middleStmt->bind_param("i", $peerId);
                                $middleStmt->execute();
                                $middleResult = $middleStmt->get_result()->fetch_assoc();
                                $middleStmt->close();
                                if ($middleResult && !empty($middleResult['middle_name'])) {
                                    $middleName = ' ' . htmlspecialchars($middleResult['middle_name']);
                                }
                                $fullName = htmlspecialchars($nameParts[0]) . $middleName . ' ' . htmlspecialchars($nameParts[1]);
                                ?>
                                <p class="font-semibold text-gray-800 text-lg"><?= $fullName; ?></p>
                                <p class="text-gray-600 text-sm">
                                    Department: <?= htmlspecialchars($i['department_name'] ?? 'N/A'); ?><br>
                                    Program: <?= htmlspecialchars($i['program_name'] ?? 'N/A'); ?>
                                </p>
                                <span class="block my-2 px-3 py-1 rounded-full text-xs font-semibold <?= $i['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'; ?>">
                                    <?= ucfirst($i['status']); ?>
                                </span>
                                <?php
                                    $viewType = 'peer';
                                    $evaluateeType = 'faculty';
                                    if ($user_role === 'HR') {
                                        $viewType = 'hrto' . (isset($i['type']) ? $i['type'] : 'faculty');
                                        $evaluateeType = isset($i['type']) && $i['type'] === 'staff' ? 'staff' : 'faculty';
                                    } elseif ($user_role === 'Program Head') {
                                        $viewType = 'programheadtofaculty';
                                    }
                                ?>
                                <?php if ($i['status'] === 'completed'): ?>
                                    <a href="viewEvaluation.php?evaluatee_id=<?= $i['id']; ?>&type=<?= $viewType; ?>&evaluatee_type=<?= $evaluateeType; ?>"
                                        class="block bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold">
                                        View Evaluation
                                    </a>
                                <?php else: ?>
                                    <?php if ($user_role === 'Program Head'): ?>
                                        <a href="regular_evaluation_form.php?evaluatee_id=<?= $i['id']; ?>&type=programheadtofaculty"
                                            class="block bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg font-semibold">
                                            Evaluate as Program Head
                                        </a>
                                    <?php elseif ($user_role === 'HR'): ?>
                                        <a href="regular_evaluation_form.php?evaluatee_id=<?= htmlspecialchars($i['id']); ?>&type=hrto<?= isset($i['type']) ? htmlspecialchars($i['type']) : 'faculty'; ?>"
                                            class="block bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg font-semibold">
                                            Evaluate as HR
                                        </a>
                                    <?php else: ?>
                                        <a href="regular_evaluation_form.php?evaluatee_id=<?= $i['id']; ?>&type=peer"
                                            class="block bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg font-semibold">
                                            Evaluate as Peer
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>