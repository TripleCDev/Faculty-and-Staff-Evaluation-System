<?php
session_start();
if (!empty($_SESSION['flash_success'])) {
    echo '<div id="flash-success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 text-center">' . htmlspecialchars($_SESSION['flash_success']) . '</div>';
    unset($_SESSION['flash_success']);
    echo '<script>
        setTimeout(function() {
            var el = document.getElementById("flash-success");
            if (el) el.style.display = "none";
        }, 1000);
    </script>';
}

if (!empty($_SESSION['flash_error'])) {
    echo '<div id="flash-error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-center">' . htmlspecialchars($_SESSION['flash_error']) . '</div>';
    unset($_SESSION['flash_error']);
    echo '<script>
        setTimeout(function() {
            var el = document.getElementById("flash-error");
            if (el) el.style.display = "none";
        }, 1000);
    </script>';
}

$localhost = "localhost";
$user = "root";
$password = "";
$database = "evaluation_system";
try {
    $conn = new PDO("mysql:host=$localhost;dbname=$database", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle Add Questionnaire POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_questionnaire'])) {
    $title = trim(ucfirst(strtolower($_POST['title'])));
    $description = trim(ucfirst(strtolower($_POST['description'])));
    $category = trim($_POST['category']);
    $criteria_id = $_POST['criteria_id'] ?? null;
    $weight_percentage = isset($_POST['weight_percentage']) ? floatval($_POST['weight_percentage']) : 100.00;
    $questions = array_filter(array_map('trim', explode("\n", $_POST['questions'])));
    $status = 'active';

    // Insert questionnaire with criteria_id and weight_percentage
    $stmt = $conn->prepare("INSERT INTO questionnaires (title, description, category, status, criteria_id, weight_percentage, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute([$title, $description, $category, $status, $criteria_id, $weight_percentage]);
    $questionnaire_id = $conn->lastInsertId();

    // Insert questions
    if ($questionnaire_id && $questions) {
        $qstmt = $conn->prepare("INSERT INTO questions (questionnaire_id, question_text, created_at) VALUES (?, ?, NOW())");
        foreach ($questions as $qline) {
            $qtext = trim($qline);
            $qstmt->execute([$questionnaire_id, $qtext]);
        }
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle mark as active/inactive POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_assignment_status'], $_POST['questionnaire_id'])) {
    $questionnaire_id = $_POST['questionnaire_id'];
    $new_status = $_POST['toggle_assignment_status']; // 'active' or 'inactive'

    // Update questionnaires table
    $qstmt = $conn->prepare("UPDATE questionnaires SET status = ? WHERE id = ?");
    $qstmt->execute([$new_status, $questionnaire_id]);

    // Update all related assignments
    $astmt = $conn->prepare("UPDATE questionnaire_assignments SET status = ? WHERE questionnaire_id = ?");
    $astmt->execute([$new_status, $questionnaire_id]);

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Edit Assignment POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_assignment_id'])) {
    $assignment_id = $_POST['edit_assignment_id'];
    $edit_questionnaire_id = $_POST['edit_questionnaire_id'];
    $edit_department_id = ($_POST['edit_department_id'] === '' || $_POST['edit_department_id'] === 'all') ? null : $_POST['edit_department_id'];
    $edit_program_id = ($_POST['edit_program_id'] === '' || $_POST['edit_program_id'] === 'all') ? null : $_POST['edit_program_id'];
    $edit_evaluation_type = $_POST['edit_evaluation_type'] ?? null;
    $edit_faculty_id = isset($_POST['edit_faculty_id']) && $_POST['edit_faculty_id'] !== '' ? $_POST['edit_faculty_id'] : null;
    $edit_staff_id = isset($_POST['edit_staff_id']) && $_POST['edit_staff_id'] !== '' ? $_POST['edit_staff_id'] : null;

    $stmt = $conn->prepare("UPDATE questionnaire_assignments SET questionnaire_id = ?, department_id = ?, program_id = ?, evaluation_type = ?, faculty_id = ?, staff_id = ? WHERE id = ?");
    $stmt->execute([
        $edit_questionnaire_id,
        $edit_department_id,
        $edit_program_id,
        $edit_evaluation_type,
        $edit_faculty_id,
        $edit_staff_id,
        $assignment_id
    ]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$evalTypes = [
    'Self' => 'Self',
    'Peer' => 'Peer',
    'ProgramHeadToFaculty' => 'Program Head',
    'Admin' => 'Admin',
    'Staff' => 'Staff',
    'HeadToStaff' => 'Head to Staff'
];

$departments = $conn->query("SELECT id, department_name FROM departments ORDER BY department_name")->fetchAll(PDO::FETCH_ASSOC);
$programs = $conn->query("SELECT program_id, program_name FROM programs ORDER BY program_name")->fetchAll(PDO::FETCH_ASSOC);
$curriculum = $conn->query("SELECT curriculum_id, curriculum_title, curriculum_year_start, curriculum_year_end, semester, status FROM curriculum WHERE status = 'active'")->fetchAll(PDO::FETCH_ASSOC);

// Handle Assign Action POST (NEW: handle assignment of questionnaires to evaluation types, departments, programs)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_action'])) {
    $questionnaire_id = $_POST['questionnaire_id'];
    $assign_group = $_POST['assign_group'] ?? '';

    if ($assign_group === 'eval_type') {
        $selectedEvalTypes = $_POST['assign_eval_types'] ?? [];
        foreach ($evalTypes as $evalKey => $evalLabel) {
            // Check if a row exists for this questionnaire and eval type
            $exists = $conn->prepare("SELECT id FROM questionnaire_assignments WHERE questionnaire_id = ? AND evaluation_type = ?");
            $exists->execute([$questionnaire_id, $evalKey]);
            $row = $exists->fetch(PDO::FETCH_ASSOC);

            if (in_array($evalKey, $selectedEvalTypes)) {
                if (!$row) {
                    $stmt = $conn->prepare("INSERT INTO questionnaire_assignments (questionnaire_id, evaluation_type, status, assigned_at) VALUES (?, ?, 'active', NOW())");
                    $stmt->execute([$questionnaire_id, $evalKey]);
                }
            } else {
                if ($row) {
                    $stmt = $conn->prepare("DELETE FROM questionnaire_assignments WHERE id = ?");
                    $stmt->execute([$row['id']]);
                }
            }
        }
    } elseif ($assign_group === 'department') {
        $selectedDepartments = $_POST['assign_departments'] ?? [];
        foreach ($departments as $d) {
            // Find the assignment row for this questionnaire with a non-null evaluation_type
            $exists = $conn->prepare("SELECT id FROM questionnaire_assignments WHERE questionnaire_id = ? AND evaluation_type IS NOT NULL");
            $exists->execute([$questionnaire_id]);
            $row = $exists->fetch(PDO::FETCH_ASSOC);

            if (in_array($d['id'], $selectedDepartments)) {
                if ($row) {
                    // Update department_id in the existing assignment row
                    $stmt = $conn->prepare("UPDATE questionnaire_assignments SET department_id = ? WHERE id = ?");
                    $stmt->execute([$d['id'], $row['id']]);
                } else {
                    // If no assignment row exists, insert a new one (with department only)
                    $stmt = $conn->prepare("INSERT INTO questionnaire_assignments (questionnaire_id, department_id, status, assigned_at) VALUES (?, ?, 'active', NOW())");
                    $stmt->execute([$questionnaire_id, $d['id']]);
                }
            } else {
                // Unassign department by setting department_id to NULL
                $stmt = $conn->prepare("UPDATE questionnaire_assignments SET department_id = NULL WHERE questionnaire_id = ? AND department_id = ?");
                $stmt->execute([$questionnaire_id, $d['id']]);
            }
        }
    } elseif ($assign_group === 'program') {
        $selectedPrograms = $_POST['assign_programs'] ?? [];
        foreach ($programs as $p) {
            // Find the assignment row for this questionnaire with a non-null evaluation_type
            $exists = $conn->prepare("SELECT id FROM questionnaire_assignments WHERE questionnaire_id = ? AND evaluation_type IS NOT NULL");
            $exists->execute([$questionnaire_id]);
            $row = $exists->fetch(PDO::FETCH_ASSOC);

            if (in_array($p['program_id'], $selectedPrograms)) {
                if ($row) {
                    // Update program_id in the existing assignment row
                    $stmt = $conn->prepare("UPDATE questionnaire_assignments SET program_id = ? WHERE id = ?");
                    $stmt->execute([$p['program_id'], $row['id']]);
                } else {
                    // If no assignment row exists, insert a new one (with program only)
                    $stmt = $conn->prepare("INSERT INTO questionnaire_assignments (questionnaire_id, program_id, status, assigned_at) VALUES (?, ?, 'active', NOW())");
                    $stmt->execute([$questionnaire_id, $p['program_id']]);
                }
            } else {
                // Unassign program by setting program_id to NULL
                $stmt = $conn->prepare("UPDATE questionnaire_assignments SET program_id = NULL WHERE questionnaire_id = ? AND program_id = ?");
                $stmt->execute([$questionnaire_id, $p['program_id']]);
            }
        }
    } elseif ($assign_group === 'curriculum') {
        $selectedCurriculum = $_POST['assign_curriculum'] ?? [];
        foreach ($curriculum as $c) {
            // Find the assignment row for this questionnaire with a non-null evaluation_type
            $exists = $conn->prepare("SELECT id FROM questionnaire_assignments WHERE questionnaire_id = ? AND evaluation_type IS NOT NULL");
            $exists->execute([$questionnaire_id]);
            $row = $exists->fetch(PDO::FETCH_ASSOC);

            if (in_array($c['curriculum_id'], $selectedCurriculum)) {
                if ($row) {
                    // Update curriculum_id in the existing assignment row
                    $stmt = $conn->prepare("UPDATE questionnaire_assignments SET curriculum_id = ? WHERE id = ?");
                    $stmt->execute([$c['curriculum_id'], $row['id']]);
                } else {
                    // If no assignment row exists, insert a new one (with program only)
                    $stmt = $conn->prepare("INSERT INTO questionnaire_assignments (questionnaire_id, curriculum_id, status, assigned_at) VALUES (?, ?, 'active', NOW())");
                    $stmt->execute([$questionnaire_id, $c['curriculum_id']]);
                }
            } else {
                // Unassign program by setting curriculum_id to NULL
                $stmt = $conn->prepare("UPDATE questionnaire_assignments SET curriculum_id = NULL WHERE questionnaire_id = ? AND curriculum_id = ?");
                $stmt->execute([$questionnaire_id, $c['curriculum_id']]);
            }
        }
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch all questionnaires (after possible insert)
$questionnaires = $conn->query("SELECT * FROM questionnaires ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all questions for each questionnaire
$questionsByQ = [];
$qres = $conn->query("SELECT questionnaire_id, question_text FROM questions ORDER BY id ASC");
foreach ($qres as $row) {
    $questionsByQ[$row['questionnaire_id']][] = $row['question_text'];
}

// Fetch all faculty, departments, programs for linking
$faculty = $conn->query("SELECT id, CONCAT(first_name, ' ', last_name) AS name FROM faculty ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all assignments
$assignments = $conn->query("
    SELECT qa.*, q.title,
        f.first_name, f.last_name,
        d.department_name,
        p.program_name
    FROM questionnaire_assignments qa
    LEFT JOIN questionnaires q ON qa.questionnaire_id = q.id
    LEFT JOIN faculty f ON qa.faculty_id = f.id
    LEFT JOIN staff s ON qa.staff_id = s.id
    LEFT JOIN departments d ON qa.department_id = d.id
    LEFT JOIN programs p ON qa.program_id = p.program_id
    ORDER BY qa.assigned_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all criteria for dropdown (UPDATED: match new criteria_options schema)
$criteriaList = [];
$criteriaRes = $conn->query("SELECT id, name, type FROM criteria ORDER BY name");
foreach ($criteriaRes as $c) {
    // Fetch options for this criteria, now including scale_type and option_order
    $optStmt = $conn->prepare("SELECT scale_type, option_text, option_point, option_order FROM criteria_options WHERE criteria_id = ? ORDER BY option_order ASC, id ASC");
    $optStmt->execute([$c['id']]);
    $optionsArr = [];
    while ($optRow = $optStmt->fetch(PDO::FETCH_ASSOC)) {
        $label = $optRow['option_text'];
        if (isset($optRow['option_point']) && $optRow['option_point'] !== null) {
            $label .= " ({$optRow['option_point']})";
        }
        if (isset($optRow['scale_type']) && $optRow['scale_type']) {
            $label .= " [{$optRow['scale_type']}]";
        }
        $optionsArr[] = $label;
    }
    $c['options'] = implode(', ', $optionsArr);
    $criteriaList[] = $c;
}

// Fetch assignments for Program Head to Faculty evaluation
$stmt = $conn->prepare("SELECT * FROM questionnaire_assignments WHERE evaluation_type = 'ProgramHeadToFaculty' AND status = 'active'");
$stmt->execute();
$programHeadAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch assignments for Faculty to Faculty (Peer) evaluation
$stmt = $conn->prepare("SELECT * FROM questionnaire_assignments WHERE evaluation_type = 'Peer' AND status = 'active'");
$stmt->execute();
$facultyAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch assignments for Admin evaluation
$stmt = $conn->prepare("SELECT * FROM questionnaire_assignments WHERE evaluation_type = 'Admin' AND status = 'active'");
$stmt->execute();
$adminAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Questionnaires Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <style>
        html,
        body {
            height: auto;
            min-height: 0;
        }

        body {
            background: #f4f6fa;
        }

        .q-card {
            transition: box-shadow 0.2s, transform 0.2s;
        }

        .q-card:hover {
            box-shadow: 0 8px 32px 0 rgba(34, 73, 47, 0.15);
            transform: translateY(-2px) scale(1.02);
        }
    </style>
</head>

<body class="bg-green-50 font-sans min-h-screen mb-[20rem]">
    <!-- Sticky Top Navigation -->
    <div class="bg-white shadow flex flex-col md:flex-row md:items-center 
                md:justify-between px-8 py-6 sticky top-0 z-40">
        <div class="flex items-center gap-4">
            <i class="fa-solid fa-file-alt text-3xl text-[#2563eb]"></i>
            <span class="text-2xl font-bold text-[#23492f]">Questionnaires Management</span>
        </div>
        <div class="flex items-center gap-2 mt-6 md:mt-0 w-full md:w-auto">
            <i class="fas fa-search text-lg"></i>
            <input type="text" placeholder="Search questionnaire..."
                class="w-full md:w-[30rem] border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-[#467C4F] transition"
                id="questionnaireSearch">
            <button onclick="openAddModal()" class="bg-[#2563eb] hover:bg-[#1746a2] text-white font-semibold 
                   px-6 py-3 rounded-lg shadow text-base transition mt-6 md:mt-0 
                   w-full md:w-auto">
                <i class="fa fa-plus mr-2"></i>Add Questionnaire
            </button>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('questionnaireSearch');
            searchInput.setAttribute('autocomplete', 'off');
            searchInput.addEventListener('input', function () {
                const val = this.value.toLowerCase();
                document.querySelectorAll('.grid > div').forEach(card => {
                    const titleElem = card.querySelector('.title');
                    const title = titleElem ? titleElem.textContent.toLowerCase() : '';
                    card.style.display = title.includes(val) ? '' : 'none';
                });
            });
        });
    </script>
    <!-- Main Dashboard Content -->
    <main class="max-w-9xl mx-auto px-4 mb-10 sm:px-8 py-8">
        <!-- All Questionnaires Section -->
        <h2 class="text-xl font-bold text-[#2563eb] mb-4 flex items-center gap-2">
            <i class="fa-solid fa-list-check"></i> All Questionnaires
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
            <?php foreach ($questionnaires as $q): ?>
                <div
                    class="q-card bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-200 p-6 flex flex-col gap-3 border border-[#e0e7ef] relative group min-h-[260px]">

                    <!-- Edit Button (top-right corner) -->
                    <button type="button"
                        class="absolute top-4 right-4 bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded text-xs shadow z-10"
                        title="Edit Questionnaire" onclick="openEditModal(
                                <?= $q['id'] ?>,
                                <?= json_encode($q['id']) ?>,
                                '',
                                '',
                                '',
                                ''
                            )">
                       <span><i class="fa fa-edit"></i>&nbsp;Edit</span>
                    </button>

                    <!-- Title and Status -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="title font-bold text-lg text-[#23492f]"><?= htmlspecialchars($q['title']) ?></span>
                        <form method="post" class="inline" style="display:inline;">
                            <input type="hidden" name="questionnaire_id" value="<?= $q['id'] ?>">
                            <?php if (strtolower($q['status']) === 'active'): ?>
                                <button type="submit" name="toggle_assignment_status" value="inactive"
                                    class="ml-2 bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-xs font-semibold flex items-center gap-1 hover:bg-green-200 transition"
                                    title="Set Inactive">
                                    <i class="fa fa-check-circle"></i> Active
                                </button>
                            <?php else: ?>
                                <button type="submit" name="toggle_assignment_status" value="active"
                                    class="ml-2 bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-xs font-semibold flex items-center gap-1 hover:bg-red-200 transition"
                                    title="Set Active">
                                    <i class="fa fa-ban"></i> Inactive
                                </button>
                            <?php endif; ?>
                        </form>
                        <?php if (!empty($q['category'])): ?>
                            <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs font-semibold ml-2">
                                <?= htmlspecialchars($q['category']) ?>
                            </span>
                        <?php endif; ?>
                        <span class="bg-orange-100 text-orange-800 px-2 py-0.5 rounded text-xs font-semibold ml-2">
                            <?= number_format($q['weight_percentage'], 2) ?>%
                        </span>
                    </div>

                    <!-- Description -->
                    <?php if (!empty($q['description'])): ?>
                        <div class="text-sm text-gray-700"><?= htmlspecialchars($q['description']) ?></div>
                    <?php endif; ?>

                    <!-- Criteria/Scale -->
                    <?php
                    if (!empty($q['criteria_id'])) {
                        foreach ($criteriaList as $c) {
                            if ($c['id'] == $q['criteria_id']) {
                                echo '<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-1">';
                                echo '<div class="font-semibold text-yellow-900 mb-2 flex items-center gap-2"><i class="fa fa-sliders-h text-yellow-600"></i> ' . htmlspecialchars($c['name']) . ' <span class="text-xs text-gray-500 ml-2">' . htmlspecialchars($c['type']) . '</span></div>';
                                // Fetch options for this criteria
                                $optStmt = $conn->prepare("SELECT option_text, option_point FROM criteria_options WHERE criteria_id = ? ORDER BY option_order ASC, id ASC");
                                $optStmt->execute([$c['id']]);
                                echo '<ul class="text-yellow-900 text-sm pl-2 space-y-1">';
                                while ($optRow = $optStmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<li class="flex items-center gap-2"><span class="font-medium">' . htmlspecialchars($optRow['option_text']) . '</span> <span class="bg-yellow-200 text-yellow-800 px-2 py-0.5 rounded-full text-xs font-semibold">(' . htmlspecialchars($optRow['option_point']) . ')</span></li>';
                                }
                                echo '</ul>';
                                echo '</div>';
                                break;
                            }
                        }
                    }
                    ?>

                    <div class="text-xs text-gray-400 mt-1">Created: <?= htmlspecialchars($q['created_at']) ?></div>

                    <?php if (!empty($questionsByQ[$q['id']])): ?>
                        <div class="mt-2 text-xs max-h-[30vh] overflow-y-auto">
                            <div class="font-semibold text-[#2563eb] mb-1 text-xs">Questions:</div>
                            <ol class="list-decimal list-inside space-y-0.5">
                                <?php foreach ($questionsByQ[$q['id']] as $qtext): ?>
                                    <li><?= htmlspecialchars($qtext) ?></li>
                                <?php endforeach; ?>
                            </ol>
                        </div>
                    <?php endif; ?>

                    <!-- Assign To Section -->
                    <div class="mt-3 border-t pt-2">
                        <div class="font-semibold text-xs text-[#467C4F] mb-1">Assign To:</div>
                        <!-- Evaluation Types -->
                        <form method="post" class="flex flex-wrap gap-2 text-xs mb-1">
                            <input type="hidden" name="questionnaire_id" value="<?= $q['id'] ?>">
                            <input type="hidden" name="assign_action" value="1">
                            <input type="hidden" name="assign_group" value="eval_type">
                            <input type="hidden" name="current_department_id" value="<?= $currentDepartmentId ?>">
                            <input type="hidden" name="current_program_id" value="<?= $currentProgramId ?>">
                            <input type="hidden" name="current_curriculum_id" value="<?= $currentCurriculumId ?>">
                            <?php foreach ($evalTypes as $evalKey => $evalLabel): ?>
                                <?php
                                $isChecked = false;
                                foreach ($assignments as $a) {
                                    if ($a['questionnaire_id'] == $q['id'] && $a['evaluation_type'] == $evalKey) {
                                        $isChecked = true;
                                        break;
                                    }
                                }
                                ?>
                                <label class="flex items-center gap-1 bg-purple-50 text-purple-800 px-2 py-0.5 rounded border border-purple-200 cursor-pointer">
                                    <input type="checkbox" name="assign_eval_types[]" value="<?= $evalKey ?>"
                                        <?= $isChecked ? 'checked' : '' ?>
                                        onchange="this.form.submit()">
                                    <?= $evalLabel ?>
                                </label>
                            <?php endforeach; ?>
                        </form>
                        <!-- Departments -->
                        <form method="post" class="flex flex-wrap gap-2 text-xs mb-1">
                            <input type="hidden" name="questionnaire_id" value="<?= $q['id'] ?>">
                            <input type="hidden" name="assign_action" value="1">
                            <input type="hidden" name="assign_group" value="department">
                            <?php foreach ($departments as $d): ?>
                                <?php
                                $isChecked = false;
                                foreach ($assignments as $a) {
                                    if ($a['questionnaire_id'] == $q['id'] && $a['department_id'] == $d['id']) {
                                        $isChecked = true;
                                        break;
                                    }
                                }
                                ?>
                                <label class="flex items-center gap-1 bg-green-50 text-green-800 px-2 py-0.5 rounded border border-green-200 cursor-pointer">
                                    <input type="checkbox" name="assign_departments[]" value="<?= $d['id'] ?>"
                                        <?= $isChecked ? 'checked' : '' ?>
                                        onchange="this.form.submit()">
                                    <?= htmlspecialchars($d['department_name']) ?>
                                </label>
                            <?php endforeach; ?>
                        </form>
                        <!-- Programs -->
                        <form method="post" class="flex flex-wrap gap-2 text-xs mb-1">
                            <input type="hidden" name="questionnaire_id" value="<?= $q['id'] ?>">
                            <input type="hidden" name="assign_action" value="1">
                            <input type="hidden" name="assign_group" value="program">
                            <?php foreach ($programs as $p): ?>
                                <?php
                                $isChecked = false;
                                foreach ($assignments as $a) {
                                    if ($a['questionnaire_id'] == $q['id'] && $a['program_id'] == $p['program_id']) {
                                        $isChecked = true;
                                        break;
                                    }
                                }
                                ?>
                                <label class="flex items-center gap-1 bg-yellow-50 text-yellow-800 px-2 py-0.5 rounded border border-yellow-200 cursor-pointer">
                                    <input type="checkbox" name="assign_programs[]" value="<?= $p['program_id'] ?>"
                                        <?= $isChecked ? 'checked' : '' ?>
                                        onchange="this.form.submit()">
                                    <?= htmlspecialchars($p['program_name']) ?>
                                </label>
                            <?php endforeach; ?>
                        </form>

                        <form method="post" class="flex flex-wrap gap-2 text-xs mb-1">
                            <input type="hidden" name="questionnaire_id" value="<?= $q['id'] ?>">
                            <input type="hidden" name="assign_action" value="1">
                            <input type="hidden" name="assign_group" value="curriculum">
                            <?php foreach ($curriculum as $c): ?>
                                <?php
                                $isChecked = false;
                                foreach ($assignments as $a) {
                                    if ($a['questionnaire_id'] == $q['id'] && $a['curriculum_id'] == $c['curriculum_id']) {
                                        $isChecked = true;
                                        break;
                                    }
                                }
                                ?>
                                <label class="flex items-center gap-1 bg-yellow-50 text-yellow-800 px-2 py-0.5 rounded border border-yellow-200 cursor-pointer">
                                    <input type="checkbox" name="assign_curriculum[]" value="<?= $c['curriculum_id'] ?>"
                                        <?= $isChecked ? 'checked' : '' ?>
                                        onchange="this.form.submit()">
                                    <?= htmlspecialchars($c['curriculum_title']) ?>
                                    <?= htmlspecialchars($c['curriculum_year_start']) ?> - <?= htmlspecialchars($c['curriculum_year_end']) ?>
                                    <?= htmlspecialchars($c['semester']) ?>
                                    Sem
                                </label>
                            <?php endforeach; ?>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        </section>
        <!-- Add Questionnaire Modal -->
        <div id="addModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-start justify-center z-50 hidden">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl p-8 max-h-[90vh] overflow-y-auto relative">
                <button type="button" onclick="closeAddModal()"
                    class="absolute top-4 right-4 text-gray-400 hover:text-red-500 text-2xl z-10">
                    <i class="fa fa-times"></i>
                </button>
                <h3 class="text-2xl font-bold mb-1 text-[#2563eb] flex items-center gap-2">
                    <i class="fa-solid fa-file-circle-plus"></i>
                    Add Questionnaire
                </h3>
                <p class="text-gray-500 mb-6 text-base">Create a new questionnaire to evaluate faculty or staff.</p>
                <form method="POST" class="flex flex-col gap-6" id="addQuestionnaireForm" autocomplete="off"
                    onsubmit="return validateQuestionnaireForm();">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="qTitle" class="block font-semibold mb-1 text-[#23492f]">Title <span
                                            class="text-red-500">*</span></label>
                                    <div class="text-xs text-gray-500 mt-1">Enter the title of the questionnaire.</div>
                                    <input type="text" name="title" id="qTitle"
                                        placeholder="(e.g., Self Evaluation, Peer Review)"
                                        class="border rounded px-3 py-2 w-full focus:ring-2 focus:ring-blue-200"
                                        required>
                                </div>
                                <div>
                                    <label for="qCategory"
                                        class="block font-semibold mb-1 text-[#23492f]">Category</label>
                                    <div class="text-xs text-gray-500 mt-1">Enter a category for this questionnaire.
                                    </div>
                                    <input type="text" name="category" id="qCategory"
                                        placeholder="(e.g., Personal Characteristics, Teaching Effectiveness)"
                                        class="border rounded px-3 py-2 w-full">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <!-- Criteria -->
                                    <label for="qCriteria" class="block font-semibold mb-1 text-[#23492f]">
                                        Criteria <span class="text-red-500">*</span>
                                        <div class="text-xs text-gray-500 mt-1">Choose the scale for answering the
                                            questions.</div>
                                    </label>
                                    <select name="criteria_id" id="qCriteria" class="border rounded px-3 py-2 w-full"
                                        required onchange="showCriteriaOptions(this)">
                                        <option value="" data-options="">Select Criteria</option>
                                        <?php foreach ($criteriaList as $c): ?>
                                            <?php
                                            $optStmt = $conn->prepare("SELECT option_text FROM criteria_options WHERE criteria_id = ? ORDER BY option_order ASC, id ASC");
                                            $optStmt->execute([$c['id']]);
                                            $option_texts = [];
                                            while ($optRow = $optStmt->fetch(PDO::FETCH_ASSOC)) {
                                                $option_texts[] = $optRow['option_text'];
                                            }
                                            $option_preview = implode(', ', $option_texts);
                                            ?>
                                            <option value="<?= $c['id'] ?>"
                                                data-options="<?= htmlspecialchars($c['options']) ?>">
                                                <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['type']) ?>)
                                                <?= $option_preview ? ' - ' . htmlspecialchars($option_preview) : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="text-xs text-gray-500 mt-1" id="criteriaOptionsHelper">
                                        <!-- Will be filled by JS -->
                                    </div>
                                </div>
                                <div>
                                    <!-- Weight Percentage -->
                                    <label for="qWeightPercentage" class="block font-semibold mb-1 text-[#23492f]">
                                        Weight (%) <span class="text-red-500">*</span>
                                        <div class="text-xs text-gray-500 mt-1">Assign a weight value to this
                                            questionnaire.
                                        </div>
                                    </label>
                                   <input type="number" name="weight_percentage" id="qWeightPercentage" min="0"
                                    max="100" step="0.01" class="border rounded px-3 py-2 w-full"
                                    placeholder="e.g., 1-100" required>
                                </div>
                            </div>
                            <div>
                                <label for="qDescription"
                                    class="block font-semibold mb-1 text-[#23492f]">Description</label>
                                <div class="text-xs text-gray-500 mt-1">Write a short description of this questionnaire.
                                </div>
                                <textarea name="description" id="qDescription"
                                    placeholder="(e.g., Used to assess punctuality, communication, and teamwork among staff.)"
                                    class="border rounded px-3 py-2 w-full focus:ring-2 focus:ring-blue-200"
                                    rows="2"></textarea>
                            </div>
                            <div>
                                <label for="qQuestions" class="block font-semibold mb-1 text-[#23492f]">Questions <span
                                        class="text-red-500">*</span></label>
                                <textarea name="questions" id="qQuestions" rows="4"
                                    class="border rounded px-3 py-2 w-full focus:ring-2 focus:ring-blue-200"
                                    placeholder="Add one question per line." required></textarea>
                            </div>
                        <div>
                            <div class="flex justify-end gap-3 mt-6">
                                <button type="button" onclick="closeAddModal()"
                                    class="px-5 py-2 rounded bg-gray-200 hover:bg-gray-300 text-base font-semibold">Cancel</button>
                                <button type="submit" name="add_questionnaire"
                                    class="px-5 py-2 rounded bg-[#10b981] text-white hover:bg-[#059669] text-base font-semibold shadow">
                                    <i class="fa fa-plus mr-2"></i>Add
                                </button>
                            </div>
                        </div>
                </form>
            </div>
        </div>

        <!-- Edit Assignment Modal -->
        <div id="editModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl p-10">
                <h3 class="text-2xl font-bold mb-6 text-[#2563eb] flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square"></i>
                    Edit Assignment
                </h3>
                <form method="POST" class="flex flex-col gap-6" id="editAssignmentForm">
                    <input type="hidden" name="edit_assignment_id" id="edit_assignment_id">

                    <!-- Questionnaire -->
                    <div>
                        <label class="font-semibold mb-1 block" for="edit_questionnaire_id">Questionnaire <span
                                class="text-red-500">*</span></label>
                        <div class="text-xs text-gray-500 mb-1">Select the questionnaire to assign.</div>
                        <select name="edit_questionnaire_id" id="edit_questionnaire_id"
                            class="border rounded px-3 py-2 w-full" required>
                            <option value="">Select Questionnaire</option>
                            <?php foreach ($questionnaires as $q): ?>
                                <option value="<?= $q['id'] ?>"><?= htmlspecialchars($q['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Criteria -->
                        <div>
                            <label class="font-semibold mb-1 block" for="edit_criteria_id">Criteria <span
                                    class="text-red-500">*</span></label>
                            <div class="text-xs text-gray-500 mb-1">Choose the scale for answering the questions.</div>
                            <select name="edit_criteria_id" id="edit_criteria_id"
                                class="border rounded px-3 py-2 w-full" required>
                                <option value="">Select Criteria</option>
                                <?php foreach ($criteriaList as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?>
                                        (<?= htmlspecialchars($c['type']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Weight Percentage -->
                        <div>
                            <label class="font-semibold mb-1 block" for="edit_weight_percentage">Weight (%) <span
                                    class="text-red-500">*</span></label>
                            <div class="text-xs text-gray-500 mb-1">Assign a weight value to this questionnaire.</div>
                            <input type="number" name="edit_weight_percentage" id="edit_weight_percentage" min="0"
                                max="100" step="0.01" class="border rounded px-3 py-2 w-full" required>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" onclick="closeEditModal()"
                            class="px-5 py-2 rounded bg-gray-200 hover:bg-gray-300 text-base">Cancel</button>
                        <button type="submit"
                            class="px-5 py-2 rounded bg-[#10b981] text-white hover:bg-[#059669] text-base">
                            <i class="fa fa-save mr-2"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            // Modal open/close logic
            function openAddModal() {
                document.getElementById('addModal').classList.remove('hidden');
            }
            function closeAddModal() {
                document.getElementById('addModal').classList.add('hidden');
                document.getElementById('addQuestionnaireForm').reset();
            }

            // Edit Assignment Modal logic
            function openEditModal(id, questionnaire_id, department_id, program_id, evaluation_type, criteria_id, weight_percentage) {
                document.getElementById('editModal').classList.remove('hidden');
                document.getElementById('editAssignmentForm').edit_assignment_id.value = id;
                document.getElementById('editAssignmentForm').edit_questionnaire_id.value = questionnaire_id || '';
                document.getElementById('editAssignmentForm').edit_department_id.value = department_id || '';
                document.getElementById('editAssignmentForm').edit_program_id.value = program_id || '';
                document.getElementById('editAssignmentForm').edit_evaluation_type.value = evaluation_type || '';
                document.getElementById('editAssignmentForm').edit_criteria_id.value = criteria_id || '';
                document.getElementById('editAssignmentForm').edit_weight_percentage.value = weight_percentage || '';
                setTimeout(toggleEditProgramDropdown, 10);
            }
            function closeEditModal() {
                document.getElementById('editModal').classList.add('hidden');
                document.getElementById('editAssignmentForm').reset();
            }

            // Close modals on background click
            document.addEventListener('click', function (e) {
                if (e.target.id === 'addModal') closeAddModal();
                if (e.target.id === 'editModal') closeEditModal();
            });
        </script>
</body>

</html>