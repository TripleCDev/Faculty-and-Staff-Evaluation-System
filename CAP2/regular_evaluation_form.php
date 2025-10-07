<?php
session_start();
require_once 'config.php';

// --- 1. Get current user info ---
$currentUserId = $_SESSION['user_id'] ?? null;
$userType = $_SESSION['userType'] ?? ($_SESSION['role'] ?? 'Faculty');
$userName = $_SESSION['fullName'] ?? 'User';
$department_name = $_SESSION['department_name'] ?? '';

// --- Fetch active curriculum (for evaluation_responses) ---
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

// --- 2. Get evaluatee and evaluation type from GET/POST ---
$evaluatee_id = $_GET['evaluatee_id'] ?? $_GET['faculty_id'] ?? $_POST['evaluatee_id'] ?? $_POST['faculty_id'] ?? null;
$evaluation_type = strtolower($_GET['type'] ?? $_POST['type'] ?? 'peer'); 
$already_submitted = false;
$questions = [];
$section = '';
$department = null;
$program = null;
$emptyMsg = 'No evaluation questions have been set by the admin yet.';
$evaluatee_name = '';

// --- 3. Get evaluatee info ---
$evaluatee_type = $_GET['evaluatee_type'] ?? $_POST['evaluatee_type'] ?? 'faculty'; // default to faculty

if ($evaluatee_id) {
    if ($evaluatee_type === 'staff') {
        // Fetch staff info
        $stmt = $conn->prepare("SELECT first_name, middle_name, last_name FROM staff WHERE staff_id = ?");
        $stmt->bind_param("i", $evaluatee_id);
        $stmt->execute();
        $stmt->bind_result($eval_first, $eval_middle, $eval_last);
        $stmt->fetch();
        $stmt->close();
        $evaluatee_name = trim($eval_first . ' ' . ($eval_middle ? $eval_middle . ' ' : '') . $eval_last);

        // Assign questionnaire for staff (by priority: staff_id, then general)
        $questionnaire_id = 0;
        $q_assign = $conn->prepare("
            SELECT questionnaire_id
            FROM questionnaire_assignments
            WHERE status = 'active'
              AND (role = 'Staff' OR role = 'Head Staff')
              AND (
                staff_id = ?
                OR (staff_id IS NULL)
              )
              AND (evaluation_type = 'Staff' OR evaluation_type = 'HeadToStaff')
            ORDER BY staff_id DESC
            LIMIT 1
        ");
        $q_assign->bind_param("i", $evaluatee_id);
        $q_assign->execute();
        $q_assign->bind_result($questionnaire_id);
        $q_assign->fetch();
        $q_assign->close();

        $questions = [];
        if ($questionnaire_id) {
            $q_stmt = $conn->prepare("SELECT id, question_text FROM questions WHERE questionnaire_id = ? ORDER BY id ASC");
            $q_stmt->bind_param("i", $questionnaire_id);
            $q_stmt->execute();
            $q_stmt->bind_result($qid, $qtext);
            while ($q_stmt->fetch()) {
                $questions[] = ['id' => $qid, 'question_text' => $qtext];
            }
            $q_stmt->close();
            $emptyMsg = 'No questions found for the assigned questionnaire.';
        } else {
            $emptyMsg = 'No questionnaire assigned for this staff.';
        }
    } else {
        // Default: faculty
        $stmt = $conn->prepare("SELECT first_name, middle_name, last_name, department_id, program_id FROM faculty WHERE faculty_id = ?");
        $stmt->bind_param("i", $evaluatee_id);
        $stmt->execute();
        $stmt->bind_result($eval_first, $eval_middle, $eval_last, $eval_dept, $eval_prog);
        $stmt->fetch();
        $stmt->close();
        $evaluatee_name = trim($eval_first . ' ' . ($eval_middle ? $eval_middle . ' ' : '') . $eval_last);
        $department = $eval_dept;
        $program = $eval_prog;

        // --- 4. Get assigned questionnaire for this faculty (active only, by priority) ---
        $questionnaire_id = 0;
        if ($evaluation_type === 'self') {
            // Self Evaluation
            $q_assign = $conn->prepare("
                SELECT questionnaire_id
                FROM questionnaire_assignments
                WHERE status = 'active'
                  AND evaluation_type = 'Self'
                  AND (
                    faculty_id = ?
                    OR (department_id = ? AND faculty_id IS NULL)
                    OR (program_id = ? AND faculty_id IS NULL AND department_id IS NULL)
                    OR (faculty_id IS NULL AND department_id IS NULL AND program_id IS NULL)
                  )
                ORDER BY
                    faculty_id DESC,
                    department_id DESC,
                    program_id DESC
                LIMIT 1
            ");
        } elseif ($evaluation_type === 'programheadtofaculty') {
            // Program Head to Faculty Evaluation
            $q_assign = $conn->prepare("
                SELECT questionnaire_id
                FROM questionnaire_assignments
                WHERE status = 'active'
                  AND evaluation_type = 'ProgramHeadToFaculty'
                  AND (
                    faculty_id = ?
                    OR (department_id = ? AND faculty_id IS NULL)
                    OR (program_id = ? AND faculty_id IS NULL AND department_id IS NULL)
                    OR (faculty_id IS NULL AND department_id IS NULL AND program_id IS NULL)
                  )
                ORDER BY
                    faculty_id DESC,
                    department_id DESC,
                    program_id DESC
                LIMIT 1
            ");
        } else {
            // Peer to Peer (Faculty to Faculty) Evaluation
            $q_assign = $conn->prepare("
                SELECT questionnaire_id
                FROM questionnaire_assignments
                WHERE status = 'active'
                  AND evaluation_type = 'Peer'
                  AND (
                    faculty_id = ?
                    OR (department_id = ? AND faculty_id IS NULL)
                    OR (program_id = ? AND faculty_id IS NULL AND department_id IS NULL)
                    OR (faculty_id IS NULL AND department_id IS NULL AND program_id IS NULL)
                  )
                ORDER BY
                    faculty_id DESC,
                    department_id DESC,
                    program_id DESC
                LIMIT 1
            ");
        }
        $q_assign->bind_param("iii", $evaluatee_id, $department, $program);
        $q_assign->execute();
        $q_assign->bind_result($questionnaire_id);
        $q_assign->fetch();
        $q_assign->close();

        $questions = [];
        if ($questionnaire_id) {
            // Fetch questions for this questionnaire
            $q_stmt = $conn->prepare("SELECT id, question_text FROM questions WHERE questionnaire_id = ? ORDER BY id ASC");
            $q_stmt->bind_param("i", $questionnaire_id);
            $q_stmt->execute();
            $q_stmt->bind_result($qid, $qtext);
            while ($q_stmt->fetch()) {
                $questions[] = ['id' => $qid, 'question_text' => $qtext];
            }
            $q_stmt->close();
            $emptyMsg = 'No questions found for the assigned questionnaire.';
        } else {
            $emptyMsg = 'No questionnaire assigned for this faculty.';
        }
    }
}

// --- 5. Fetch assigned questionnaire's criteria options ---
$criteria_options = [];
if (isset($questionnaire_id) && $questionnaire_id) {
    // Get the criteria_id for this questionnaire
    $criteria_id = null;
    $crit_stmt = $conn->prepare("SELECT criteria_id FROM questionnaires WHERE id = ?");
    $crit_stmt->bind_param("i", $questionnaire_id);
    $crit_stmt->execute();
    $crit_stmt->bind_result($criteria_id);
    $crit_stmt->fetch();
    $crit_stmt->close();

    if ($criteria_id) {
        $stmt = $conn->prepare("SELECT c.option_text, c.option_point FROM criteria_options c WHERE c.criteria_id = ?");
        $stmt->bind_param("i", $criteria_id);
        $stmt->execute();
        $stmt->bind_result($option_text, $option_point);
        while ($stmt->fetch()) {
            $criteria_options[] = ['text' => $option_text, 'point' => $option_point];
        }
        $stmt->close();
    }
}

// --- 6. Check if already submitted (for this evaluator/evaluatee/questionnaire/curriculum) ---
if ($currentUserId && $evaluatee_id && $questionnaire_id && $curriculum_id) {
    $check = $conn->prepare("SELECT COUNT(*) FROM evaluation_responses WHERE evaluator_id = ? AND evaluated_id = ? AND questionnaire_id = ? AND curriculum_id = ? AND status = 'completed'");
    $check->bind_param("iiii", $currentUserId, $evaluatee_id, $questionnaire_id, $curriculum_id);
    $check->execute();
    $check->bind_result($already_count);
    $check->fetch();
    $check->close();
    $already_submitted = $already_count > 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Evaluation Form</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#f4f6fa] min-h-screen">
    <?php
    if (!isset($noHeader) || !$noHeader) {
        include 'header.php';
    }
    ?>

    <main class="max-w-4xl mx-auto px-4 py-12 mt-24 bg-white/80 rounded-2xl shadow-lg">
        <a href="javascript:history.back()" 
           class="inline-flex items-center mb-6 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-semibold shadow transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Back
        </a>

        <h2 class="text-2xl font-bold text-emerald-800 mb-6">
            Evaluation Form
            <?php if (!empty($evaluatee_name)): ?>
                <span class="block text-lg text-gray-600 font-normal">for <?= htmlspecialchars($evaluatee_name) ?></span>
            <?php endif; ?>
            <?php if ($evaluation_type === 'Self'): ?>
                <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-semibold align-middle">Self Evaluation</span>
            <?php elseif ($evaluation_type === 'ProgramHeadtoFaculty'): ?>
                <span class="ml-2 px-2 py-1 bg-purple-100 text-purple-700 rounded text-xs font-semibold align-middle">Program Head to Faculty Evaluation</span>
            <?php elseif ($evaluation_type === 'Peer'): ?>
                <span class="ml-2 px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs font-semibold align-middle">Faculty to Faculty (Peer) Evaluation</span>
            <?php endif; ?>
        </h2>

        <?php if ($already_submitted): ?>
            <div class="bg-white rounded-xl shadow-lg p-8 text-center text-emerald-700 text-xl font-semibold mb-8">
                You have already submitted your evaluation. Thank you!
            </div>
        <?php elseif (empty($questions)): ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 my-6 rounded text-center text-lg">
                <?= htmlspecialchars($emptyMsg) ?>
            </div>
        <?php else: ?>
            <form action="/CAP2/submit_evaluation.php" method="post" class="space-y-8">
                <input type="hidden" name="evaluatee_id" value="<?= htmlspecialchars($evaluatee_id) ?>">
                <input type="hidden" name="evaluatee_type" value="<?= htmlspecialchars($evaluatee_type) ?>">
                <input type="hidden" name="curriculum_id" value="<?= htmlspecialchars($curriculum_id) ?>">
                <input type="hidden" name="questionnaire_id" value="<?= htmlspecialchars($questionnaire_id ?? '') ?>">
                <input type="hidden" name="evaluation_type" value="<?= htmlspecialchars($evaluation_type) ?>">

                <div class="mb-8">
                    <h3 class="text-xl font-bold text-emerald-700 mb-1">Evaluation</h3>
                    <p class="text-gray-500">Please rate each statement honestly.</p>
                </div>

                <?php foreach ($questions as $idx => $q): ?>
                    <div class="bg-white rounded-lg shadow px-4 py-3 mb-4 flex flex-col md:flex-row md:items-center md:justify-between border-l-4 border-emerald-400">
                        <div class="flex-1 mb-2 md:mb-0">
                            <span class="text-emerald-700 font-bold text-base mr-2"><?= $idx + 1 ?>.</span>
                            <span class="font-medium text-gray-800 text-sm"><?= htmlspecialchars($q['question_text']) ?></span>
                        </div>
                        <div class="flex justify-center gap-x-3 items-center my-1">
                            <?php foreach ($criteria_options as $opt): ?>
                                <label class="flex flex-col items-center min-w-[55px] cursor-pointer">
                                    <input type="radio" name="q<?= $q['id'] ?>" value="<?= htmlspecialchars($opt['text']) ?>" required
                                        class="accent-emerald-600 w-5 h-5 mb-1 transition-all duration-150 focus:ring-emerald-400">
                                    <span class="text-[11px] font-medium text-gray-700"><?= htmlspecialchars($opt['text']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="mb-8">
                    <label for="comments" class="block text-gray-700 font-semibold mb-2">Comments (optional):</label>
                    <textarea id="comments" name="comments" rows="4"
                        class="w-full rounded-lg border border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 shadow-sm p-3 transition"
                        placeholder="Add any comments or feedback here..."></textarea>
                </div>
                <div class="text-center mt-10">
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-10 py-3 rounded-xl text-lg font-bold shadow-lg transition">
                        Submit Evaluation
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>
