<?php
session_start();

// Database connection
$host = 'localhost';
$db   = 'evaluation_system';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- FILTERS ---
$department_filter = $_GET['department'] ?? '';
$curriculum_filter = $_GET['curriculum'] ?? '';

// Department options
$department_options = ['' => 'All Departments'];
$dept_res = $conn->query("SELECT id, department_name FROM departments ORDER BY id ASC");
while ($row = $dept_res->fetch_assoc()) {
    $department_options[$row['id']] = $row['department_name'];
}

// Get semesters from evaluation_responses (since curriculum no longer has semester)
$semesters = [];
$sem_res = $conn->query("SELECT DISTINCT semester FROM curriculum WHERE semester IS NOT NULL AND semester != '' ORDER BY semester DESC");
while ($row = $sem_res->fetch_assoc()) {
    if ($row['semester'] && !in_array($row['semester'], $semesters)) $semesters[] = $row['semester'];
}
rsort($semesters);

// --- BUILD WHERE CLAUSE ---
$where = [];
if ($department_filter) $where[] = "f.department_id = " . intval($department_filter);
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

// --- GET ALL EVALUATION TYPES AND THEIR WEIGHTS ---
$eval_types = [];
$qres = $conn->query("
    SELECT DISTINCT qa.evaluation_type, q.weight_percentage
    FROM questionnaire_assignments qa
    INNER JOIN questionnaires q ON qa.questionnaire_id = q.id
    WHERE q.status = 'Active' AND qa.evaluation_type IS NOT NULL AND qa.evaluation_type != ''
");
while ($row = $qres->fetch_assoc()) {
    $eval_types[$row['evaluation_type']] = floatval($row['weight_percentage']);
}

// --- FACULTY RANKING ---
$sql = "
SELECT 
    f.id AS faculty_id,
    CONCAT(f.first_name, ' ', f.last_name) AS faculty_name,
    d.department_name
FROM faculty f
LEFT JOIN departments d ON f.department_id = d.id
$where_sql
";
$result = $conn->query($sql);

$facultyList = [];
while ($row = $result->fetch_assoc()) {
    $faculty_id = $row['faculty_id'];
    $curriculum_where = '';
    if ($curriculum_filter) {
        $curriculum_id = intval($curriculum_filter);
        $curriculum_where = " AND er.curriculum_id = $curriculum_id";
    }

    $eval_sql = "
        SELECT 
            qa.evaluation_type,
            q.weight_percentage,
            AVG(er.score) as avg_score
        FROM evaluation_responses er
        INNER JOIN questionnaires q ON er.questionnaire_id = q.id
        INNER JOIN questionnaire_assignments qa ON er.questionnaire_id = qa.questionnaire_id
        WHERE er.evaluated_id = $faculty_id
          AND er.status = 'completed'
          $curriculum_where
        GROUP BY qa.evaluation_type, q.weight_percentage
    ";
    $evals = $conn->query($eval_sql);

    $scores = [];
    $weighted_sum = 0;
    $total_weight = 0;

    while ($e = $evals->fetch_assoc()) {
        $type = $e['evaluation_type'];
        $score = floatval($e['avg_score']);
        $weight = floatval($e['weight_percentage']);
        if ($type && $weight > 0) {
            $scores[$type] = $score;
            $weighted_sum += $score * ($weight / 100);
            $total_weight += ($weight / 100);
        }
    }

    $row['scores'] = $scores;
    $row['final_score'] = $total_weight > 0 ? $weighted_sum / $total_weight : 0;
    $facultyList[] = $row;
}

// --- STAFF RANKING ---
$sql_staff = "
SELECT 
    s.id AS staff_id,
    CONCAT(s.first_name, ' ', s.last_name) AS staff_name,
    d.department_name
FROM staff s
LEFT JOIN departments d ON s.department_id = d.id
" . ($department_filter ? "WHERE s.department_id = " . intval($department_filter) : "") . "
ORDER BY s.last_name, s.first_name
";
$result_staff = $conn->query($sql_staff);

$staffList = [];
while ($row = $result_staff->fetch_assoc()) {
    $staff_id = $row['staff_id'];
    $curriculum_where = '';
    if ($curriculum_filter) {
        $curriculum_id = intval($curriculum_filter);
        $curriculum_where = " AND er.curriculum_id = $curriculum_id";
    }
    $eval_sql = "
        SELECT 
            qa.evaluation_type,
            q.weight_percentage,
            AVG(er.score) as avg_score
        FROM evaluation_responses er
        INNER JOIN questionnaires q ON er.questionnaire_id = q.id
        INNER JOIN questionnaire_assignments qa ON er.questionnaire_id = qa.questionnaire_id
        WHERE er.evaluated_id = $staff_id
          AND er.status = 'completed'
          $curriculum_where
        GROUP BY qa.evaluation_type, q.weight_percentage
    ";
    $evals = $conn->query($eval_sql);

    $scores = [];
    $weighted_sum = 0;
    $total_weight = 0;

    while ($e = $evals->fetch_assoc()) {
        $type = $e['evaluation_type'];
        $score = floatval($e['avg_score']);
        $weight = floatval($e['weight_percentage']);
        if ($type && $weight > 0) {
            $scores[$type] = $score;
            $weighted_sum += $score * ($weight / 100);
            $total_weight += ($weight / 100);
        }
    }

    $row['scores'] = $scores;
    $row['faculty_name'] = $row['staff_name'];
    $row['final_score'] = $total_weight > 0 ? $weighted_sum / $total_weight : 0;
    $staffList[] = $row;
}

// Merge and sort all
$allList = array_merge($facultyList, $staffList);
usort($allList, fn($a,$b) => $b['final_score'] <=> $a['final_score']);
foreach ($allList as $i => &$f) $f['rank'] = $i + 1;
unset($f);

// Download Excel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_excel'])) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=faculty_staff_rankings.xls");
    echo "<table border='1'>";
    echo "<tr>
        <th>Rank</th>
        <th>Faculty/Staff Name</th>
        <th>Department</th>";
    foreach ($eval_types as $type => $weight) {
        echo "<th>" . htmlspecialchars($type) . " (" . number_format($weight, 2) . "%)</th>";
    }
    echo "<th>Total Weighted Score</th>
    </tr>";
    foreach ($allList as $f) {
        echo "<tr>";
        echo "<td>{$f['rank']}</td>";
        echo "<td>" . htmlspecialchars($f['faculty_name']) . "</td>";
        echo "<td>" . htmlspecialchars($f['department_name']) . "</td>";
        foreach ($eval_types as $type => $weight) {
            echo "<td>" . (isset($f['scores'][$type]) ? number_format($f['scores'][$type], 2) : '-') . "</td>";
        }
        echo "<td>" . number_format($f['final_score'], 2) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    exit;
}

// Get curriculum options
$curriculum_options = ['' => 'All Curriculums'];
$cur_res = $conn->query("SELECT curriculum_id, curriculum_title, curriculum_year_start, curriculum_year_end, semester FROM curriculum ORDER BY curriculum_year_start DESC, curriculum_title ASC");
while ($row = $cur_res->fetch_assoc()) {
    $label = $row['curriculum_title'] . " ({$row['curriculum_year_start']}-{$row['curriculum_year_end']} {$row['semester']})";
    $curriculum_options[$row['curriculum_id']] = $label;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Faculty & Staff Rankings – Academic Year 2025–2026</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html, body { height: 100%; }
        body { min-height: 100vh; background: #f4f6fa; }
        .table-responsive { max-width: 100vw; overflow-x: auto; }
    </style>
</head>
<body class="bg-[#f4f6fa] min-h-screen mb-[10rem]">
    <div class="w-full max-w-[1800px] mx-auto px-2 sm:px-6 py-8 flex flex-col min-h-screen">
        <?php
        if ($curriculum_filter && isset($curriculum_options[$curriculum_filter])) {
            $cur_detail_res = $conn->query("SELECT curriculum_title, curriculum_year_start, curriculum_year_end, semester FROM curriculum WHERE curriculum_id = " . intval($curriculum_filter) . " LIMIT 1");
            if ($cur_detail = $cur_detail_res->fetch_assoc()) {
                $display_title = htmlspecialchars($cur_detail['curriculum_title']) . " (" . 
                    htmlspecialchars($cur_detail['curriculum_year_start']) . "–" . 
                    htmlspecialchars($cur_detail['curriculum_year_end']) . 
                    ($cur_detail['semester'] ? " " . htmlspecialchars($cur_detail['semester']) : "") . 
                    ")";
            }
        }
        ?>
        <h1 class="text-3xl sm:text-4xl font-bold text-green-900 mb-4">
            Faculty & Staff Rankings
        </h1>
        <div class="flex flex-col md:flex-row md:items-end gap-4 mb-8">
            <form method="get" class="flex flex-col sm:flex-row gap-4 flex-1">
            <div class="flex flex-col flex-1 min-w-[200px]">
                <label class="block text-base font-medium text-gray-700 mb-1">Department</label>
                <select name="department" class="rounded border-gray-300 px-4 py-3 text-base" onchange="this.form.submit()">
                <?php foreach ($department_options as $value => $label): ?>
                    <option value="<?= htmlspecialchars($value) ?>" <?= $department_filter == $value ? 'selected' : '' ?>>
                    <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
                </select>
            </div>
            <div class="flex flex-col flex-1 min-w-[180px]">
                <label class="block text-base font-medium text-gray-700 mb-1">Curriculum</label>
                <select name="curriculum" class="rounded border-gray-300 px-4 py-3 text-base" onchange="this.form.submit()">
                    <?php foreach ($curriculum_options as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>" <?= $curriculum_filter == $value ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            </form>
            <form method="post" action="facultyandstaffRanking.php" class="flex-shrink-0">
            <input type="hidden" name="download_excel" value="1">
            <input type="hidden" name="department" value="<?= htmlspecialchars($department_filter) ?>">
            <input type="hidden" name="semester" value="<?= htmlspecialchars($semester_filter) ?>">
            <input type="hidden" name="curriculum" value="<?= htmlspecialchars($curriculum_filter) ?>">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold shadow text-base w-full">
                Download Excel
            </button>
            </form>
        </div>
        <!-- Table -->
        <div class="flex-1 table-responsive bg-white rounded-2xl shadow p-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 rounded-lg overflow-hidden text-base">
                <thead class="bg-green-100">
                    <tr>
                        <th class="px-6 py-4 text-xs font-bold text-green-900 uppercase text-center">Rank</th>
                        <th class="px-6 py-4 text-xs font-bold text-green-900 uppercase text-center">Faculty/Staff Name</th>
                        <th class="px-6 py-4 text-xs font-bold text-green-900 uppercase text-center">Department</th>
                        <?php foreach ($eval_types as $type => $weight): ?>
                            <th class="px-6 py-4 text-xs font-bold text-green-900 uppercase text-center">
                                <?= htmlspecialchars($type) ?><br>
                                <span class="text-xs text-gray-500">(<?= number_format($weight, 2) ?>%)</span>
                            </th>
                        <?php endforeach; ?>
                        <th class="px-6 py-4 text-xs font-bold text-green-900 uppercase text-center">Total Weighted Score</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <?php if (count($allList)): foreach ($allList as $f): ?>
                    <?php
                        $rowClass = '';
                        if ($f['rank'] == 1) {
                            $rowClass = 'bg-yellow-400 text-yellow-900 font-extrabold';
                        } elseif ($f['rank'] == 2) {
                            $rowClass = 'bg-gray-300 text-gray-900 font-bold';
                        } elseif ($f['rank'] == 3) {
                            $rowClass = 'bg-orange-300 text-orange-900 font-bold';
                        } elseif ($f['rank'] >= 4 && $f['rank'] <= 10) {
                            $rowClass = 'bg-green-50 text-green-700 font-semibold';
                        }
                    ?>
                    <tr class="hover:bg-green-50 <?= $rowClass ?>">
                        <td class="px-6 py-4 font-bold text-center"><?= $f['rank'] ?></td>
                        <td class="px-6 py-4 text-center"><?= htmlspecialchars($f['faculty_name']) ?></td>
                        <td class="px-6 py-4 text-center"><?= htmlspecialchars($f['department_name']) ?></td>
                        <?php foreach ($eval_types as $type => $weight): ?>
                            <td class="px-6 py-4 text-center"><?= isset($f['scores'][$type]) ? number_format($f['scores'][$type], 2) : '-' ?></td>
                        <?php endforeach; ?>
                        <td class="px-6 py-4 font-bold text-green-800 text-center"><?= number_format($f['final_score'], 2) ?></td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="<?= 4 + count($eval_types) ?>" class="text-center text-red-700 py-6 text-lg">No ranking data available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>
