<?php
session_start();
require_once 'config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Not logged in.";
    exit;
}

$user_id = $_SESSION['user_id'];
$evaluatee_id = $_GET['evaluatee_id'] ?? null;
$type = $_GET['type'] ?? null; // 'peer', 'self', 'admin', 'programhead', 'staff'
$evaluatee_type = $_GET['evaluatee_type'] ?? 'faculty'; // 'faculty' or 'staff'
$course_id = $_GET['course_id'] ?? null;

if (!$evaluatee_id || !$type) {
    echo "Invalid request.";
    exit;
}

// Initialize
$evaluation = [];
$criteria_count = 0;
$average_score = 0;
$status = 'Pending';
$evaluator_name = '';
$evaluation_id = null;

// Fetch data depending on type and evaluatee_type
if ($evaluatee_type === 'staff') {
    // Staff to Staff Evaluation
    $stmt = $conn->prepare("
        SELECT er.evaluation_id, er.evaluator_id, er.evaluated_id AS staff_id, s.first_name, s.middle_name, s.last_name, er.score, er.comments, er.evaluated_date
        FROM evaluation_responses er
        LEFT JOIN staff s ON er.evaluated_id = s.staff_id
        WHERE er.evaluated_id = ? AND er.status = 'completed'
        ORDER BY er.evaluated_date DESC
        LIMIT 1
    ");
    $stmt->bind_param('i', $evaluatee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $evaluation = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $criteria_count = count($evaluation);
    $average_score = $criteria_count ? $evaluation[0]['score'] : 0;
    $status = $criteria_count ? 'Completed' : 'Pending';
    $evaluation_id = $criteria_count ? $evaluation[0]['evaluation_id'] : null;

    // Fetch evaluator name
    if ($criteria_count && isset($evaluation[0]['evaluator_id'])) {
        $stmt = $conn->prepare("SELECT first_name, middle_name, last_name FROM staff WHERE staff_id = ?");
        $stmt->bind_param("i", $evaluation[0]['evaluator_id']);
        $stmt->execute();
        $evaluator = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $evaluator_name = trim($evaluator['first_name'] . ' ' . ($evaluator['middle_name'] ? $evaluator['middle_name'] . ' ' : '') . $evaluator['last_name']);
    } else {
        $evaluator_name = 'Staff';
    }
} else {
    // Faculty to Faculty (Peer/Self/Admin/Program Head)
    switch ($type) {
        case 'admin':
            $stmt = $conn->prepare("
                SELECT er.evaluation_id, er.evaluator_id, er.evaluated_id AS faculty_id, f.first_name, f.middle_name, f.last_name, er.score, er.comments, er.evaluated_date
                FROM evaluation_responses er
                LEFT JOIN faculty f ON er.evaluated_id = f.faculty_id
                WHERE er.evaluated_id = ? AND er.evaluator_id IN (SELECT id FROM users WHERE role = 'HR') AND er.status = 'completed'
                ORDER BY er.evaluated_date DESC
                LIMIT 1
            ");
            $stmt->bind_param('i', $evaluatee_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $evaluation = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $criteria_count = count($evaluation);
            $average_score = $criteria_count ? $evaluation[0]['score'] : 0;
            $status = $criteria_count ? 'Completed' : 'Pending';
            $evaluation_id = $criteria_count ? $evaluation[0]['evaluation_id'] : null;
            $evaluator_name = 'Administrator';
            break;

        case 'peer':
        case 'self':
            $stmt = $conn->prepare("
                SELECT er.evaluation_id, er.evaluator_id, er.evaluated_id AS faculty_id, f.first_name, f.middle_name, f.last_name, er.score, er.comments, er.evaluated_date
                FROM evaluation_responses er
                LEFT JOIN faculty f ON er.evaluated_id = f.faculty_id
                WHERE er.evaluated_id = ? AND er.status = 'completed'
                ORDER BY er.evaluated_date DESC
                LIMIT 1
            ");
            $stmt->bind_param('i', $evaluatee_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $evaluation = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $criteria_count = count($evaluation);
            $average_score = $criteria_count ? $evaluation[0]['score'] : 0;
            $status = $criteria_count ? 'Completed' : 'Pending';
            $evaluation_id = $criteria_count ? $evaluation[0]['evaluation_id'] : null;

            if ($type === 'peer' && $criteria_count && isset($evaluation[0]['evaluator_id'])) {
                $stmt = $conn->prepare("SELECT first_name, middle_name, last_name FROM faculty WHERE faculty_id = ?");
                $stmt->bind_param("i", $evaluation[0]['evaluator_id']);
                $stmt->execute();
                $evaluator = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                $evaluator_name = $evaluator ? trim($evaluator['first_name'] . ' ' . ($evaluator['middle_name'] ? $evaluator['middle_name'] . ' ' : '') . $evaluator['last_name']) : 'Peer';
            } elseif ($type === 'self') {
                $evaluator_name = 'Self';
            } else {
                $evaluator_name = 'Faculty';
            }
            break;

        case 'programhead':
            $stmt = $conn->prepare("SELECT faculty_id, department_id FROM faculty WHERE user_id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $ph = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$ph) {
                echo "Program Head record not found.";
                exit;
            }

            $ph_department = $ph['department_id'];

            $stmt = $conn->prepare("
                SELECT er.evaluation_id, er.evaluator_id, er.evaluated_id AS faculty_id, f.first_name, f.middle_name, f.last_name, er.score, er.comments, er.evaluated_date
                FROM evaluation_responses er
                LEFT JOIN faculty f ON er.evaluated_id = f.faculty_id
                WHERE er.evaluated_id = ? AND f.department_id = ? AND er.status = 'completed'
                ORDER BY er.evaluated_date DESC
                LIMIT 1
            ");
            $stmt->bind_param("ii", $evaluatee_id, $ph_department);
            $stmt->execute();
            $result = $stmt->get_result();
            $evaluation = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $criteria_count = count($evaluation);
            $average_score = $criteria_count ? $evaluation[0]['score'] : 0;
            $status = $criteria_count ? 'Completed' : 'Pending';
            $evaluation_id = $criteria_count ? $evaluation[0]['evaluation_id'] : null;
            $evaluator_name = 'Program Head';
            break;

        case 'hrtofaculty':
            // HR evaluating faculty logic (can be similar to peer or admin logic)
            $evaluatee_type = 'faculty';
            $stmt = $conn->prepare("
                SELECT er.evaluation_id, er.evaluator_id, er.evaluated_id AS faculty_id, f.first_name, f.middle_name, f.last_name, er.score, er.comments, er.evaluated_date
                FROM evaluation_responses er
                LEFT JOIN faculty f ON er.evaluated_id = f.faculty_id
                WHERE er.evaluated_id = ? AND er.evaluator_id IN (SELECT id FROM users WHERE role = 'HR') AND er.status = 'completed'
                ORDER BY er.evaluated_date DESC
                LIMIT 1
            ");
            $stmt->bind_param('i', $evaluatee_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $evaluation = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $criteria_count = count($evaluation);
            $average_score = $criteria_count ? $evaluation[0]['score'] : 0;
            $status = $criteria_count ? 'Completed' : 'Pending';
            $evaluation_id = $criteria_count ? $evaluation[0]['evaluation_id'] : null;
            $evaluator_name = 'Administrator';
            break;

        case 'hrtostaff':
            // HR evaluating staff logic
            $evaluatee_type = 'staff';
            $stmt = $conn->prepare("
                SELECT er.evaluation_id, er.evaluator_id, er.evaluated_id AS staff_id, s.first_name, s.middle_name, s.last_name, er.score, er.comments, er.evaluated_date
                FROM evaluation_responses er
                LEFT JOIN staff s ON er.evaluated_id = s.staff_id
                WHERE er.evaluated_id = ? AND er.status = 'completed'
                ORDER BY er.evaluated_date DESC
                LIMIT 1
            ");
            $stmt->bind_param('i', $evaluatee_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $evaluation = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $criteria_count = count($evaluation);
            $average_score = $criteria_count ? $evaluation[0]['score'] : 0;
            $status = $criteria_count ? 'Completed' : 'Pending';
            $evaluation_id = $criteria_count ? $evaluation[0]['evaluation_id'] : null;

            // Fetch evaluator name
            if ($criteria_count && isset($evaluation[0]['evaluator_id'])) {
                $stmt = $conn->prepare("SELECT first_name, middle_name, last_name FROM staff WHERE staff_id = ?");
                $stmt->bind_param("i", $evaluation[0]['evaluator_id']);
                $stmt->execute();
                $evaluator = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                $evaluator_name = trim($evaluator['first_name'] . ' ' . ($evaluator['middle_name'] ? $evaluator['middle_name'] . ' ' : '') . $evaluator['last_name']);
            } else {
                $evaluator_name = 'Staff';
            }
            break;

        default:
            echo "Unknown evaluation type.";
            exit;
    }
}

// --- Function to display per-question answers ---
function renderPerQuestionAnswers($conn, $evaluation_id, $evaluatee_type = 'faculty')
{
    $qstmt = $conn->prepare("
        SELECT 
            er.question_id, 
            q.question_text, 
            er.answer,
            co.option_point
        FROM evaluation_responses er
        JOIN questions q ON er.question_id = q.id
        LEFT JOIN criteria_options co 
            ON co.option_text = er.answer
        WHERE er.evaluation_id = ?
        ORDER BY q.id ASC
    ");
    $qstmt->bind_param("i", $evaluation_id);
    $qstmt->execute();
    $qres = $qstmt->get_result();
    if ($qres->num_rows > 0) {
        echo '<div class="mt-10">';
        echo '<h3 class="text-lg font-bold text-emerald-700 mb-6 flex items-center gap-2">';
        echo '<svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2l4 -4"></path></svg>';
        echo 'Per-Question Answers</h3>';
        echo '<div class="overflow-x-auto">';
        echo '<table class="min-w-full bg-white border border-emerald-100 rounded-xl shadow-sm">';
        echo '<thead class="bg-emerald-50">';
        echo '<tr>';
        echo '<th class="py-3 px-6 text-left text-emerald-800 font-semibold border-b border-emerald-100">#</th>';
        echo '<th class="py-3 px-6 text-left text-emerald-800 font-semibold border-b border-emerald-100">Question</th>';
        echo '<th class="py-3 px-6 text-left text-emerald-800 font-semibold border-b border-emerald-100">Answer</th>';
        echo '<th class="py-3 px-6 text-left text-emerald-800 font-semibold border-b border-emerald-100">Score</th>';
        echo '</tr>';
        echo '</thead><tbody>';
        $num = 1;
        while ($qrow = $qres->fetch_assoc()) {
            echo '<tr class="hover:bg-emerald-50 transition">';
            echo '<td class="py-3 px-6 border-b border-emerald-100 text-emerald-700 font-bold">' . $num++ . '</td>';
            echo '<td class="py-3 px-6 border-b border-emerald-100 text-gray-800">' . htmlspecialchars($qrow['question_text']) . '</td>';
            echo '<td class="py-3 px-6 border-b border-emerald-100">';
            echo '<span class="inline-block bg-emerald-100 text-emerald-800 font-semibold px-4 py-1 rounded-full shadow-sm">';
            echo htmlspecialchars($qrow['answer']);
            echo '</span></td>';
            echo '<td class="py-3 px-6 border-b border-emerald-100">';
            if ($qrow['option_point'] !== null) {
                echo '<span class="inline-block bg-green-100 text-green-800 font-semibold px-3 py-1 rounded-full shadow-sm">';
                echo htmlspecialchars($qrow['option_point']);
                echo '</span>';
            } else {
                echo '<span class="text-gray-400">N/A</span>';
            }
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div></div>';
    }
    $qstmt->close();
}

// Determine evaluation label
$evaluationLabel = '';
switch ($type) {
    case 'self':
        $evaluationLabel = 'Self Evaluation';
        break;
    case 'peer':
        $evaluationLabel = 'Faculty to Faculty (Peer) Evaluation';
        break;
    case 'programheadtofaculty':
        $evaluationLabel = 'Program Head to Faculty Evaluation';
        break;
    case 'admin':
        $evaluationLabel = 'Admin Evaluation';
        break;
        case 'hrtofaculty':
            $evaluationLabel = 'HR to Faculty Evaluation';
            break;
        case 'hrtostaff':
            $evaluationLabel = 'HR to Staff Evaluation';
            break;
    default:
        $evaluationLabel = 'Evaluation';
}

// Calculate total score for this evaluation_id
if ($evaluation_id) {
    $score_stmt = $conn->prepare("
        SELECT SUM(score) AS score
        FROM evaluation_responses
        WHERE evaluation_id = ?
    ");
    $score_stmt->bind_param("i", $evaluation_id);
    $score_stmt->execute();
    $score_result = $score_stmt->get_result();
    $score_row = $score_result->fetch_assoc();
    $average_score = $score_row['score'] ?? 0;
    $score_stmt->close();
}

// Fetch curriculum info for this evaluation (if curriculum_id is available)
$curriculum_title = '';
$curriculum_year = '';
$curr_stmt = $conn->prepare("SELECT c.curriculum_title, c.curriculum_year_start, c.curriculum_year_end FROM curriculum c
    JOIN evaluation_responses er ON er.curriculum_id = c.curriculum_id
    WHERE er.evaluation_id = ? LIMIT 1");
$curr_stmt->bind_param("i", $evaluation_id);
$curr_stmt->execute();
$curr_stmt->bind_result($curr_title, $curr_start, $curr_end);
if ($curr_stmt->fetch()) {
    $curriculum_title = $curr_title;
    $curriculum_year = $curr_start . ' - ' . $curr_end;
}
$curr_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Instructor Evaluation Results</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-green-50 min-h-screen font-sans mb-[5rem]">
    <main class="max-w-4xl mx-auto px-4 py-10">

        <!-- Main Card Container -->
        <div class="bg-white/90 rounded-2xl shadow-2xl p-10 mb-10 border border-emerald-100 max-w-4xl mx-auto">

            <!-- Top: Score & Status Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-10">
                <div
                    class="bg-emerald-50 rounded-xl shadow p-6 flex flex-col items-center border-l-4 border-emerald-500">
                    <span class="text-gray-500 text-sm mb-2 tracking-wide">Total Score</span>
                    <span class="text-4xl font-extrabold text-emerald-700 mb-1"><?= $average_score ?></span>
                    <span class="text-xs text-gray-400">Based on evaluation criteria</span>
                </div>
                <div class="bg-green-50 rounded-xl shadow p-6 flex flex-col items-center border-l-4 border-green-400">
                    <span class="text-gray-500 text-sm mb-2 tracking-wide">Status</span>
                    <span
                        class="inline-block px-5 py-1 rounded-full text-base font-semibold <?= $status === 'Completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                        <?= $status ?>
                    </span>
                    <span class="text-xs text-gray-400 mt-1">Evaluation completion status</span>
                </div>
            </div>

            <!-- Evaluation Details -->
            <div class="rounded-xl bg-white shadow-inner p-8 mb-10 border border-emerald-100">
                <h2 class="text-xl font-bold text-emerald-800 mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m0 0H3"></path>
                    </svg>
                    Evaluation Details
                    <span
                        class="ml-3 px-3 py-1 rounded bg-yellow-100 text-yellow-800 text-xs font-semibold"><?= $evaluationLabel ?></span>
                </h2>
                <?php if ($criteria_count === 0): ?>
                    <p class="text-red-600 font-semibold">No evaluation records found.</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-green-900 text-base">
                        <div>
                            <span class="font-semibold">Evaluator:</span>
                            <span>
                                <?php
                                if ($evaluatee_type === 'staff' && isset($evaluation[0]['evaluator_id'])) {
                                    // Staff or Head Staff evaluator
                                    $stmt = $conn->prepare("SELECT first_name, middle_name, last_name, role FROM staff WHERE staff_id = ?");
                                    $stmt->bind_param("i", $evaluation[0]['evaluator_id']);
                                    $stmt->execute();
                                    $evaluator = $stmt->get_result()->fetch_assoc();
                                    $stmt->close();
                                    if ($evaluator) {
                                        echo htmlspecialchars($evaluator['role']);
                                        if (!empty($evaluator['role'])) {
                                            echo ' <span class="text-xs text-gray-500">(' . htmlspecialchars($evaluator['role']) . ')</span>';
                                        }
                                    } else {
                                        echo 'Unknown';
                                    }
                                } elseif ($type === 'peer' && isset($evaluation[0]['evaluator_id'])) {
                                    // Faculty or Program Head evaluator
                                    $stmt = $conn->prepare("SELECT first_name, middle_name, last_name, role FROM users WHERE id = ?");
                                    $stmt->bind_param("i", $evaluation[0]['evaluator_id']);
                                    $stmt->execute();
                                    $evaluator = $stmt->get_result()->fetch_assoc();
                                    $stmt->close();
                                    if ($evaluator) {
                                        echo htmlspecialchars(
                                            $evaluator['first_name'] . ' ' .
                                            ($evaluator['middle_name'] ? $evaluator['middle_name'] . ' ' : '') .
                                            $evaluator['last_name']
                                        );
                                        if (!empty($evaluator['role'])) {
                                            echo ' <span class="text-xs text-gray-500">(' . htmlspecialchars($evaluator['role']) . ')</span>';
                                        }
                                    } else {
                                        echo 'Unknown';
                                    }
                                } elseif (isset($evaluation[0]['evaluator_id'])) {
                                    $stmt = $conn->prepare("SELECT first_name, middle_name, last_name, role FROM users WHERE id = ?");
                                    $stmt->bind_param("i", $evaluation[0]['evaluator_id']);
                                    $stmt->execute();
                                    $evaluator = $stmt->get_result()->fetch_assoc();
                                    $stmt->close();
                                    if ($evaluator) {
                                        echo htmlspecialchars(
                                            $evaluator['first_name'] . ' ' .
                                            ($evaluator['middle_name'] ? $evaluator['middle_name'] . ' ' : '') .
                                            $evaluator['last_name']
                                        );
                                        if (!empty($evaluator['role'])) {
                                            echo ' <span class="text-xs text-gray-500">(' . htmlspecialchars($evaluator['role']) . ')</span>';
                                        }
                                    } else {
                                        echo 'Unknown';
                                    }
                                } elseif ($type === 'admin') {
                                    echo 'Administrator';
                                } elseif ($type === 'programheadtofaculty') {
                                    echo 'Program Head';
                                } elseif ($type === 'self') {
                                    echo 'Self';
                                } elseif ($type === 'hrtofaculty') {
                                    echo 'HR';
                                } elseif ($type === 'hrtostaff') {
                                    echo 'HR';
                                } else {
                                    echo 'Faculty';
                                }
                                ?>
                            </span>
                        </div>
                        <div>
                            <span class="font-semibold">Evaluation Date:</span>
                            <span>
                                <?php
                                if ($criteria_count > 0 && isset($evaluation[0]['evaluated_date'])) {
                                    echo htmlspecialchars($evaluation[0]['evaluated_date']);
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </span>
                        </div>
                        <div>
                            <span class="font-semibold">Total Score:</span>
                            <span class="font-bold text-green-700"><?= $average_score ?></span>
                        </div>
                        <div>
                            <span class="font-semibold">Comments:</span>
                            <span
                                class="ml-2 text-emerald-700 font-bold"><?= htmlspecialchars($evaluation[0]['comments'] ?? 'No comment provided') ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Per-Question Answers Section -->
            <?php
            if ($criteria_count) {
                renderPerQuestionAnswers($conn, $evaluation_id, $evaluatee_type);
            }
            ?>

            <!-- Return Button -->
            <div class="flex justify-center mt-10">
                <button onclick="window.history.back();"
                    class="bg-emerald-700 hover:bg-emerald-800 text-white font-semibold px-8 py-3 rounded-lg shadow transition-all duration-200">
                    &larr; Return to Overview
                </button>
            </div>
        </div>
    </main>
</body>

</html>