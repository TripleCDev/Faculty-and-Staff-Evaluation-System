<?php
require_once('config.php');

$faculty_id = isset($_GET['faculty_id']) ? intval($_GET['faculty_id']) : 0;
$staff_id = isset($_GET['staff_id']) ? intval($_GET['staff_id']) : 0;

if ($faculty_id > 0) {
    $view_type = 'faculty';
} elseif ($staff_id > 0) {
    $view_type = 'staff';
} else {
    die("Invalid faculty or staff ID.");
}

// Connect using PDO
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

if ($view_type === 'faculty') {
    // Fetch faculty info
    $stmt = $conn->prepare("
        SELECT f.first_name, f.middle_name, f.last_name, f.role, d.department_name, p.program_name
        FROM faculty f
        LEFT JOIN departments d ON f.department_id = d.id
        LEFT JOIN programs p ON f.program_id = p.program_id
        WHERE f.faculty_id = ?
    ");
    $stmt->execute([$faculty_id]);
    $person = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$person) {
        die("Faculty not found.");
    }

    // Fetch admin evaluations for faculty (from evaluation_responses)
    $admin_evals = $conn->prepare("
        SELECT evaluation_id, evaluator_id AS admin_id, SUM(score) AS total_score, comments, evaluated_date
        FROM evaluation_responses
        WHERE evaluated_id = ? AND evaluator_id IN (SELECT id FROM users WHERE role = 'HR') AND status = 'completed'
        GROUP BY evaluation_id, comments, evaluated_date
        ORDER BY evaluated_date DESC
    ");
    $admin_evals->execute([$faculty_id]);
    $admin_evals_data = $admin_evals->fetchAll(PDO::FETCH_ASSOC);

    // Fetch other evaluations for faculty (peer/self/program head)
    $other_evals = $conn->prepare("
        SELECT evaluation_id, evaluator_id, SUM(score) AS total_score, comments, evaluated_date
        FROM evaluation_responses
        WHERE evaluated_id = ? AND (evaluator_id NOT IN (SELECT id FROM users WHERE role = 'HR')) AND status = 'completed'
        GROUP BY evaluation_id, comments, evaluated_date
        ORDER BY evaluated_date DESC
    ");
    $other_evals->execute([$faculty_id]);
    $other_evals_data = $other_evals->fetchAll(PDO::FETCH_ASSOC);

    // Get all scores from evaluation_responses (merged average)
    $merged_sql = "
        SELECT AVG(score) as merged_avg, COUNT(*) as total
        FROM evaluation_responses
        WHERE evaluated_id = ? AND status = 'completed'
    ";
    $merged_stmt = $conn->prepare($merged_sql);
    $merged_stmt->execute([$faculty_id]);
    $merged = $merged_stmt->fetch(PDO::FETCH_ASSOC);

} else { // staff
    // Fetch staff info
    $stmt = $conn->prepare("
        SELECT s.first_name, s.middle_name, s.last_name, s.role, d.department_name
        FROM staff s
        LEFT JOIN departments d ON s.department_id = d.id
        WHERE s.staff_id = ?
    ");
    $stmt->execute([$staff_id]);
    $person = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$person) {
        die("Staff not found.");
    }

    // Fetch all evaluations for staff (from evaluation_responses)
    $admin_evals_data = []; // No admin/staff distinction in your schema, but you can filter if needed
    $other_evals = $conn->prepare("
        SELECT evaluation_id, evaluator_id, SUM(score) AS total_score, comments, evaluated_date
        FROM evaluation_responses
        WHERE evaluated_id = ? AND status = 'completed'
        GROUP BY evaluation_id, comments, evaluated_date
        ORDER BY evaluated_date DESC
    ");
    $other_evals->execute([$staff_id]);
    $other_evals_data = $other_evals->fetchAll(PDO::FETCH_ASSOC);

    // Get all scores (average)
    $merged_sql = "
        SELECT AVG(score) as merged_avg, COUNT(*) as total
        FROM evaluation_responses
        WHERE evaluated_id = ? AND status = 'completed'
    ";
    $merged_stmt = $conn->prepare($merged_sql);
    $merged_stmt->execute([$staff_id]);
    $merged = $merged_stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<?php
function format_name($p) {
    return trim($p['first_name'] . ' ' . ($p['middle_name'] ? $p['middle_name'] . ' ' : '') . $p['last_name']);
}
?>
<?php
// Helper function to fetch per-question ratings for an evaluation
function get_eval_answers($conn, $evaluation_id) {
    $sql = "
        SELECT q.question_text, r.answer
        FROM evaluation_responses r
        JOIN questions q ON r.question_id = q.id
        WHERE r.evaluation_id = ?
        ORDER BY q.id ASC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$evaluation_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Evaluation Summary - <?= htmlspecialchars(format_name($person)) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom scrollbar for consistency */
        ::-webkit-scrollbar {
            width: 10px;
            background: #e0f2f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #34d399;
            border-radius: 8px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #059669;
        }
        html {
            scrollbar-width: thin;
            scrollbar-color: #34d399 #e0f2f1;
        }
    </style>
</head>
<body class="bg-green-50 font-sans min-h-screen">
    <main class="max-w-7xl mx-auto px-2 sm:px-8 py-0 sm:py-6 mt-4 sm:mt-8 pb-8 mb-[10rem]">
        <div class="bg-white shadow rounded-2xl p-6">
            <h1 class="text-2xl font-bold mb-2 text-emerald-900 tracking-tight">Evaluation Summary</h1>
            <div class="text-lg font-semibold text-emerald-800"><?= htmlspecialchars(format_name($person)) ?></div>
            <div class="text-gray-600"><?= htmlspecialchars($person['role']) ?></div>
            <div class="text-gray-500 mb-4">
                <?= htmlspecialchars($person['department_name'] ?? '') ?>
                <?= isset($person['program_name']) && $person['program_name'] ? ' - ' . htmlspecialchars($person['program_name']) : '' ?>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-8 py-4 flex flex-col items-center shadow">
                    <span class="text-base font-semibold text-emerald-700">Overall Average Score</span>
                    <span class="text-4xl font-extrabold text-emerald-600 mt-1 mb-1">
                        <?= $merged && $merged['merged_avg'] ? number_format($merged['merged_avg'], 2) : 'N/A' ?>
                    </span>
                    <span class="text-gray-500 text-sm">Total Evaluations: <?= $merged && $merged['total'] ? $merged['total'] : 0 ?></span>
                </div>
            </div>
        </div>
        <div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 auto-rows-auto mt-6">
                <?php
                // Merge all evaluations into one array with a type label
                $all_evals = [];
                foreach ($admin_evals_data as $eval) {
                    $eval['eval_type'] = 'Other';
                    $all_evals[] = $eval;
                }
                foreach ($other_evals_data as $eval) {
                    $eval['eval_type'] = 'Other';
                    $all_evals[] = $eval;
                }
                // Sort all evaluations by evaluated_date DESC
                usort($all_evals, function($a, $b) {
                    return strtotime($b['evaluated_date']) - strtotime($a['evaluated_date']);
                });
                ?>
                <?php if (count($all_evals) > 0): ?>
                    <?php foreach ($all_evals as $idx => $eval): ?>
                        <div class="bg-green-50 border-green-300 rounded-xl shadow flex flex-col transition hover:shadow-lg hover:ring-2 hover:ring-emerald-200 border border-emerald-100">
                            <div class="flex flex-col gap-1 px-5 py-4 border-b border-emerald-100 rounded-t-xl bg-emerald-50/60">
                                <div class="flex justify-between items-center">
                                    <span class="font-semibold text-emerald-900">Evaluation #<?= $idx+1 ?></span>
                                    <span class="text-xs text-blue-700"><?= htmlspecialchars($eval['eval_type']) ?></span>
                                </div>
                                <span class="text-xs text-gray-500"><?= date('F j, Y', strtotime($eval['evaluated_date'])) ?></span>
                            </div>
                            <div class="px-5 py-4 flex-1 flex flex-col bg-white rounded-b-xl">
                                <div class="mb-2">
                                    <span class="font-semibold text-gray-700">Total Score:</span>
                                    <span class="font-bold text-lg text-emerald-800">
                                        <?= htmlspecialchars($eval['total_score']) ?>
                                    </span>
                                </div>
                                <div class="mb-2">
                                    <span class="font-semibold text-gray-700">Comments:</span>
                                    <span class="text-gray-800"><?= $eval['comments'] ? htmlspecialchars($eval['comments']) : '<span class="italic text-gray-400">No comment provided</span>' ?></span>
                                </div>
                                <div class="mb-2">
                                    <span class="font-semibold text-gray-700">Per-Question Ratings:</span>
                                    <?php
                                    $answers = get_eval_answers($conn, $eval['evaluation_id']);
                                    ?>
                                    <?php if ($answers && count($answers) > 0): ?>
                                        <table class="w-full mt-2 mb-2 border rounded">
                                            <thead class="bg-blue-100">
                                                <tr>
                                                    <th class="py-1 px-2 text-left text-xs font-semibold text-gray-700">Question</th>
                                                    <th class="py-1 px-2 text-center text-xs font-semibold text-gray-700">Rating</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($answers as $a): ?>
                                                    <tr class="border-b last:border-b-0">
                                                        <td class="py-1 px-2 text-xs text-gray-800"><?= htmlspecialchars($a['question_text']) ?></td>
                                                        <td class="py-1 px-2 text-center text-xs font-semibold text-blue-700">
                                                            <?= htmlspecialchars($a['answer']) ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php else: ?>
                                        <div class="text-gray-400 text-xs italic">No per-question ratings available.</div>
                                    <?php endif; ?>
                                </div>
                                <?php
                                // Fetch curriculum info for this evaluation (if curriculum_id is available)
                                $curriculum_title = '';
                                $curriculum_year = '';
                                $curr_stmt = $conn->prepare("SELECT c.curriculum_title, c.curriculum_year_start, c.curriculum_year_end FROM curriculum c
                                    JOIN evaluation_responses er ON er.curriculum_id = c.curriculum_id
                                    WHERE er.evaluation_id = ? LIMIT 1");
                                $curr_stmt->execute([$eval['evaluation_id']]);
                                $curr_result = $curr_stmt->fetch(PDO::FETCH_ASSOC);
                                if ($curr_result) {
                                    $curriculum_title = $curr_result['curriculum_title'];
                                    $curriculum_year = $curr_result['curriculum_year_start'] . ' - ' . $curr_result['curriculum_year_end'];
                                }
                                ?>
                                <?php if ($curriculum_title): ?>
                                    <div class="text-sm text-blue-700 font-semibold mb-2">
                                        Curriculum: <?= htmlspecialchars($curriculum_title) ?> (<?= htmlspecialchars($curriculum_year) ?>)
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-gray-500 col-span-3 text-center py-8">No evaluation data available for this faculty or staff member.</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="flex justify-end mt-8">
            <a href="facultySection.php" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2 rounded-lg font-semibold shadow transition">
                Back to Faculty and Staff List
            </a>
        </div>
    </main>
</body>
</html>
