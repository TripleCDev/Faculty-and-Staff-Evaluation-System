<?php
require_once 'config.php';
session_start();
function flash($key) {
    if (!empty($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return '';
}

// --- Handle Add Curriculum ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_curriculum'])) {
    $title = trim($_POST['curriculum_title']);
    $year_start = intval($_POST['curriculum_year_start']);
    $year_end = intval($_POST['curriculum_year_end']);
    $description = trim($_POST['description']);
    $status = ($_POST['status'] ?? 'inactive') === 'active' ? 'active' : 'inactive';
    $semester = $_POST['semester'] ?? '1st';

    // Validation
    if (!$title || !$year_start || !$year_end || !$semester) {
        $_SESSION['error'] = "All required fields must be filled.";
        header("Location: curriculum.php");
        exit;
    }

    // If setting as active, set all others to inactive
    if ($status === 'active') {
        $conn->query("UPDATE curriculum SET status = 'inactive'");
    }

    $stmt = $conn->prepare("INSERT INTO curriculum (curriculum_title, curriculum_year_start, curriculum_year_end, description, status, semester) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $year_start, $year_end, $description, $status, $semester]);
    $_SESSION['success'] = "Curriculum added successfully.";
    header("Location: curriculum.php");
    exit;
}

// --- Handle Edit Curriculum ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_curriculum'])) {
    $id = intval($_POST['curriculum_id']);
    $title = trim($_POST['curriculum_title']);
    $year_start = intval($_POST['curriculum_year_start']);
    $year_end = intval($_POST['curriculum_year_end']);
    $description = trim($_POST['description']);
    $status = ($_POST['status'] ?? 'inactive') === 'active' ? 'active' : 'inactive';
    $semester = $_POST['semester'] ?? '1st';

    if (!$title || !$year_start || !$year_end || !$semester) {
        $_SESSION['error'] = "All required fields must be filled.";
        header("Location: curriculum.php");
        exit;
    }

    // If setting as active, set all others to inactive
    if ($status === 'active') {
        $conn->query("UPDATE curriculum SET status = 'inactive'");
    }

    $stmt = $conn->prepare("UPDATE curriculum SET curriculum_title=?, curriculum_year_start=?, curriculum_year_end=?, description=?, status=?, semester=? WHERE curriculum_id=?");
    $stmt->execute([$title, $year_start, $year_end, $description, $status, $semester, $id]);
    $_SESSION['success'] = "Curriculum updated successfully.";
    header("Location: curriculum.php");
    exit;
}

// --- Handle Toggle Status ---
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    // Get current status
    $stmt = $conn->prepare("SELECT status FROM curriculum WHERE curriculum_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $curr = $result->fetch_assoc();
    if ($curr) {
        $new_status = $curr['status'] === 'active' ? 'inactive' : 'active';
        if ($new_status === 'active') {
            $conn->query("UPDATE curriculum SET status = 'inactive'");
        }
        $stmt2 = $conn->prepare("UPDATE curriculum SET status=? WHERE curriculum_id=?");
        $stmt2->execute([$new_status, $id]);
        $_SESSION['success'] = "Curriculum status updated.";
    }
    header("Location: curriculum.php");
    exit;
}

// --- Fetch all curriculum entries ---
$curriculum = [];
$result = $conn->query(
    "SELECT curriculum_id, curriculum_title, curriculum_year_start, curriculum_year_end, description, status, semester, date_created
     FROM curriculum
     ORDER BY curriculum_year_start DESC, curriculum_title"
);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $curriculum[] = $row;
    }
}

// --- Fetch evaluation counts and stats per curriculum ---
$evaluation_stats = [];
$eval_result = $conn->query("
    SELECT curriculum_id,
           COUNT(DISTINCT evaluation_id) AS eval_count,
           AVG(score) AS avg_score,
           SUM(status='completed') AS completed,
           SUM(status='pending') AS pending
    FROM evaluation_responses
    GROUP BY curriculum_id
");
if ($eval_result) {
    while ($row = $eval_result->fetch_assoc()) {
        $evaluation_stats[$row['curriculum_id']] = $row;
    }
}

// --- Filter bar ---
$selected_curriculum_id = isset($_GET['filter_curriculum']) ? (int)$_GET['filter_curriculum'] : null;
$selected_status = $_GET['filter_status'] ?? '';
$search = trim($_GET['search'] ?? '');

// --- Fetch filtered evaluation responses ---
$where = [];
$params = [];
$types = '';
if ($selected_curriculum_id) {
    $where[] = 'er.curriculum_id = ?';
    $params[] = $selected_curriculum_id;
    $types .= 'i';
}
if ($selected_status && in_array($selected_status, ['pending', 'completed'])) {
    $where[] = 'er.status = ?';
    $params[] = $selected_status;
    $types .= 's';
}
if ($search) {
    $where[] = "(er.evaluation_id LIKE ? OR er.evaluated_id LIKE ? OR er.questionnaire_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'sss';
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Count total for pagination
$count_sql = "SELECT COUNT(*) FROM evaluation_responses er $where_sql";
$count_stmt = $conn->prepare($count_sql);
if ($params) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$count_stmt->bind_result($total_evals);
$count_stmt->fetch();
$count_stmt->close();
$total_pages = ceil($total_evals / $per_page);

// Fetch paginated results
$sql = "
    SELECT er.*, 
           c.curriculum_title, 
           c.curriculum_year_start, 
           c.curriculum_year_end, 
           c.semester,
           q.title AS questionnaire_title,
           CONCAT(u.first_name, ' ', IFNULL(CONCAT(u.middle_name, ' '), ''), u.last_name) AS evaluated_fullname
    FROM evaluation_responses er
    JOIN curriculum c ON er.curriculum_id = c.curriculum_id
    JOIN questionnaires q ON er.questionnaire_id = q.id
    LEFT JOIN users u ON er.evaluated_id = u.id
    $where_sql
    ORDER BY er.evaluated_date DESC
    LIMIT $per_page OFFSET $offset
";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$filtered_evaluations = [];
while ($row = $result->fetch_assoc()) {
    $filtered_evaluations[] = $row;
}
$stmt->close();

// --- Statistics summary for current filter ---
$summary = [
    'total' => $total_evals,
    'avg_score' => 0,
    'completed' => 0,
    'pending' => 0,
    'completed_pct' => 0,
    'pending_pct' => 0
];
if ($selected_curriculum_id && isset($evaluation_stats[$selected_curriculum_id])) {
    $stat = $evaluation_stats[$selected_curriculum_id];
    $summary['avg_score'] = round($stat['avg_score'], 2);
    $summary['completed'] = $stat['completed'];
    $summary['pending'] = $stat['pending'];
    $summary['completed_pct'] = $stat['eval_count'] ? round($stat['completed'] / $stat['eval_count'] * 100, 1) : 0;
    $summary['pending_pct'] = $stat['eval_count'] ? round($stat['pending'] / $stat['eval_count'] * 100, 1) : 0;
} elseif ($total_evals > 0) {
    // Calculate from filtered_evaluations
    $sum = 0; $completed = 0; $pending = 0;
    foreach ($filtered_evaluations as $ev) {
        $sum += $ev['score'];
        if ($ev['status'] === 'completed') $completed++;
        if ($ev['status'] === 'pending') $pending++;
    }
    $summary['avg_score'] = round($sum / $total_evals, 2);
    $summary['completed'] = $completed;
    $summary['pending'] = $pending;
    $summary['completed_pct'] = $total_evals ? round($completed / $total_evals * 100, 1) : 0;
    $summary['pending_pct'] = $total_evals ? round($pending / $total_evals * 100, 1) : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Curriculum Management Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
</head>
<body class="bg-green-50 min-h-screen font-sans">
    <!-- Top Navigation -->
    <div class="bg-white shadow flex flex-col md:flex-row md:items-center md:justify-between px-8 py-6 mb-10">
        <div class="flex items-center gap-4">
            <i class="fa-solid fa-file-alt text-3xl text-[#2563eb]"></i>
            <span class="text-2xl font-bold text-[#23492f]">Curriculum Dashboard</span>
        </div>
        <div class="flex items-center gap-2 mt-6 md:mt-0 w-full md:w-auto">
            <button onclick="openAddModal()" class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg shadow transition">
                <i class="fa fa-plus mr-2"></i>Add Questionnaire
            </button>
        </div>
    </div>
    <main class="max-w-9xl mx-auto px-4 mb-10 sm:px-8 py-8">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-4">
            <div class="bg-white rounded-xl shadow flex flex-col items-center py-6 transition hover:scale-[1.02] hover:shadow-lg">
                <span class="text-xs text-gray-500 mb-1">Total Curriculums</span>
                <span class="text-3xl font-bold text-blue-600"><?= count($curriculum) ?></span>
            </div>
            <div class="bg-white rounded-xl shadow flex flex-col items-center py-6 transition hover:scale-[1.02] hover:shadow-lg">
                <span class="text-xs text-gray-500 mb-1">Active</span>
                <span class="text-3xl font-bold text-green-600"><?= count(array_filter($curriculum, fn($c) => $c['status'] === 'active')) ?></span>
            </div>
            <div class="bg-white rounded-xl shadow flex flex-col items-center py-6 transition hover:scale-[1.02] hover:shadow-lg">
                <span class="text-xs text-gray-500 mb-1">Inactive</span>
                <span class="text-3xl font-bold text-red-600"><?= count(array_filter($curriculum, fn($c) => $c['status'] === 'inactive')) ?></span>
            </div>
            <div class="bg-white rounded-xl shadow flex flex-col items-center py-6 transition hover:scale-[1.02] hover:shadow-lg">
                <span class="text-xs text-gray-500 mb-1">Total Evaluations</span>
                <span class="text-3xl font-bold text-blue-800"><?= $summary['total'] ?></span>
            </div>
        </div>
        <!-- Main Content Grid -->
        <div class="grid dashboard-grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Curriculum List Section -->
            <section>
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-book text-blue-600"></i> Curriculum List
                </h2>
                <!-- Filter/Search Bar for Curriculum (moved before the cards) -->
                <div class="mb-6">
                    <form method="get" class="flex flex-wrap gap-2 items-center w-full md:w-auto">
                        <select name="filter_curriculum" id="filter_curriculum" class="border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-200" title="Filter by Curriculum" onchange="this.form.submit()">
                            <option value="">All Curriculums</option>
                            <?php foreach ($curriculum as $cur): ?>
                                <option value="<?= $cur['curriculum_id'] ?>" <?= $selected_curriculum_id == $cur['curriculum_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cur['curriculum_title']) ?> (<?= $cur['curriculum_year_start'] ?>-<?= $cur['curriculum_year_end'] ?> <?= $cur['semester'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
                <div class="flex flex-col gap-4">
                    <?php foreach ($curriculum as $cur): ?>
                        <div class="bg-white rounded-2xl card-shadow p-6 flex flex-col gap-3 border border-gray-200 relative group min-h-[180px] transition hover:scale-[1.01] hover:shadow-lg">
                            <!-- Edit Button -->
                            <button type="button"
                                class="absolute top-4 right-4 bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded text-xs shadow z-10 transition"
                                title="Edit Curriculum"
                                onclick="openEditModal(
                                    <?= $cur['curriculum_id'] ?>,
                                    '<?= htmlspecialchars(addslashes($cur['curriculum_title'])) ?>',
                                    '<?= $cur['curriculum_year_start'] ?>',
                                    '<?= $cur['curriculum_year_end'] ?>',
                                    `<?= htmlspecialchars(addslashes($cur['description'] ?? '')) ?>`,
                                    '<?= $cur['status'] ?>',
                                    '<?= $cur['semester'] ?>'
                                )">
                                <i class="fa fa-edit"></i>
                            </button>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-lg text-gray-900"><?= htmlspecialchars($cur['curriculum_title']) ?></span>
                                <form method="get" action="curriculum.php" class="inline">
                                    <input type="hidden" name="toggle" value="<?= $cur['curriculum_id'] ?>">
                                    <?php if ($cur['status'] === 'active'): ?>
                                        <button type="submit"
                                            class="ml-2 badge badge-green flex items-center gap-1 hover:opacity-80 transition"
                                            title="Click to set Inactive">
                                            <i class="fa fa-check-circle"></i>
                                            Active
                                        </button>
                                    <?php else: ?>
                                        <button type="submit"
                                            class="ml-2 badge badge-red flex items-center gap-1 hover:opacity-80 transition"
                                            title="Click to set Active">
                                            <i class="fa fa-ban"></i>
                                            Inactive
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </div>
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <i class="fa-solid fa-calendar-days"></i>
                                <?= htmlspecialchars($cur['curriculum_year_start']) ?> - <?= htmlspecialchars($cur['curriculum_year_end']) ?>
                                <span class="badge badge-blue ml-2 bg-blue-100 text-blue-700"><?= htmlspecialchars($cur['semester']) ?> Semester</span>
                            </div>
                            <?php if (!empty($cur['description'])): ?>
                                <div class="text-sm text-gray-700"><?= nl2br(htmlspecialchars($cur['description'])) ?></div>
                            <?php endif; ?>
                            <div class="flex items-center gap-2 text-xs text-gray-400">
                                <i class="fa-regular fa-clock"></i> Created: <?= htmlspecialchars($cur['date_created']) ?>
                            </div>
                            <div class="text-xs text-blue-700 font-semibold mt-2">
                                <?= isset($evaluation_stats[$cur['curriculum_id']]) && $evaluation_stats[$cur['curriculum_id']]['eval_count'] > 0
                                    ? $evaluation_stats[$cur['curriculum_id']]['eval_count'] . ' Evaluation(s) linked'
                                    : 'No evaluations yet' ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <!-- Evaluation Responses Section -->
            <section>
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-table text-blue-600"></i> Evaluation Responses
                </h2>
                <!-- Evaluation Summary -->
                <div class="flex flex-wrap gap-4 mb-4 items-center">
                    <div class="bg-white rounded-xl card-shadow px-6 py-4 flex flex-col items-center min-w-[140px]">
                        <span class="text-xs text-gray-500">Total Evaluations</span>
                        <span class="text-2xl font-bold text-blue-600"><?= $summary['total'] ?></span>
                    </div>
                    <div class="bg-white rounded-xl card-shadow px-6 py-4 flex flex-col items-center min-w-[140px]">
                        <span class="text-xs text-gray-500">Average Score</span>
                        <span class="text-2xl font-bold text-green-700"><?= $summary['avg_score'] ?></span>
                    </div>
                    <!-- Search evaluation responses-->
                    <form method="get" class="flex items-center gap-2 ml-2">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search evaluation responses..." class="border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-200" title="Search by curriculum fields">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2 font-semibold shadow transition">
                            <i class="fa fa-search"></i> Search
                        </button>
                    </form>
                </div>
                <!-- Evaluation Table -->
                <div class="overflow-x-auto rounded-xl shadow">
                    <table class="min-w-full bg-white rounded-xl table">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="px-4 py-2 border-b text-left text-sm font-semibold text-gray-700">Evaluation ID</th>
                                <th class="px-4 py-2 border-b text-left text-sm font-semibold text-gray-700">Questionnaire Title</th>
                                <th class="px-4 py-2 border-b text-left text-sm font-semibold text-gray-700">Evaluated Person</th>
                                <th class="px-4 py-2 border-b text-left text-sm font-semibold text-gray-700">Score</th>
                                <th class="px-4 py-2 border-b text-left text-sm font-semibold text-gray-700">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filtered_evaluations as $i => $ev): ?>
                                <tr class="<?= $i % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?> hover:bg-blue-50 transition">
                                    <td class="px-4 py-2 border-b"><?= htmlspecialchars($ev['evaluation_id']) ?></td>
                                    <td class="px-4 py-2 border-b"><?= htmlspecialchars($ev['questionnaire_title']) ?></td>
                                    <td class="px-4 py-2 border-b"><?= htmlspecialchars($ev['evaluated_fullname']) ?></td>
                                    <td class="px-4 py-2 border-b"><?= htmlspecialchars($ev['score']) ?></td>
                                    <td class="px-4 py-2 border-b"><?= htmlspecialchars($ev['evaluated_date']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($filtered_evaluations)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-gray-400 py-8">No evaluation responses found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="flex justify-center mt-4 gap-2">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                               class="px-3 py-1 rounded <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-blue-100' ?> transition">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <!-- Edit Curriculum Modal -->
    <div id="editModal" class="fixed inset-0 z-50 flex items-center justify-center modal-bg hidden transition">
        <div class="bg-white rounded-lg shadow-lg p-8 modal-card relative w-full max-w-md">
            <button onclick="closeEditModal()" class="absolute top-2 right-3 text-gray-400 hover:text-gray-700 text-xl">&times;</button>
            <h2 class="text-lg font-bold mb-4 flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-blue-600"></i> Edit Curriculum
            </h2>
            <form method="POST" class="flex flex-col gap-4">
                <input type="hidden" name="edit_curriculum" value="1">
                <input type="hidden" name="curriculum_id" id="edit_curriculum_id">
                <label class="font-semibold">Title <span class="text-red-500">*</span></label>
                <input type="text" name="curriculum_title" id="edit_curriculum_title" class="border rounded px-3 py-2 w-full" required>
                <div class="flex gap-2">
                    <div class="flex-1">
                        <label class="font-semibold">Year Start <span class="text-red-500">*</span></label>
                        <input type="number" name="curriculum_year_start" id="edit_curriculum_year_start" min="2000" max="2100" class="border rounded px-3 py-2 w-full" required>
                    </div>
                    <div class="flex-1">
                        <label class="font-semibold">Year End <span class="text-red-500">*</span></label>
                        <input type="number" name="curriculum_year_end" id="edit_curriculum_year_end" min="2000" max="2100" class="border rounded px-3 py-2 w-full" required>
                    </div>
                </div>
                <div>
                    <label class="font-semibold">Semester <span class="text-red-500">*</span></label>
                    <select name="semester" id="edit_semester" class="border rounded px-3 py-2 w-full" required>
                        <option value="1st">1st</option>
                        <option value="2nd">2nd</option>
                        <option value="Summer">Summer</option>
                    </select>
                </div>
                <label class="font-semibold">Description</label>
                <textarea name="description" id="edit_description" class="border rounded px-3 py-2 w-full" rows="2"></textarea>
                <div>
                    <label class="font-semibold">Status</label>
                    <select name="status" id="edit_status" class="border rounded px-3 py-2 w-full">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 mt-2 transition">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Add Curriculum Modal -->
    <div id="addModal" class="fixed inset-0 z-50 flex items-center justify-center modal-bg hidden transition">
        <div class="bg-white rounded-lg shadow-lg p-8 modal-card relative w-full max-w-md">
            <button onclick="closeAddModal()" class="absolute top-2 right-3 text-gray-400 hover:text-gray-700 text-xl">&times;</button>
            <h2 class="text-lg font-bold mb-4 flex items-center gap-2">
                <i class="fa-solid fa-plus text-blue-600"></i> Add Curriculum
            </h2>
            <form method="POST" class="flex flex-col gap-4">
                <input type="hidden" name="add_curriculum" value="1">
                <label class="font-semibold">Title <span class="text-red-500">*</span></label>
                <input type="text" name="curriculum_title" class="border rounded px-3 py-2 w-full" required>
                <div class="flex gap-2">
                    <div class="flex-1">
                        <label class="font-semibold">Year Start <span class="text-red-500">*</span></label>
                        <input type="number" name="curriculum_year_start" min="2000" max="2100" class="border rounded px-3 py-2 w-full" required>
                    </div>
                    <div class="flex-1">
                        <label class="font-semibold">Year End <span class="text-red-500">*</span></label>
                        <input type="number" name="curriculum_year_end" min="2000" max="2100" class="border rounded px-3 py-2 w-full" required>
                    </div>
                </div>
                <div>
                    <label class="font-semibold">Semester <span class="text-red-500">*</span></label>
                    <select name="semester" class="border rounded px-3 py-2 w-full" required>
                        <option value="1st">1st</option>
                        <option value="2nd">2nd</option>
                        <option value="Summer">Summer</option>
                    </select>
                </div>
                <label class="font-semibold">Description</label>
                <textarea name="description" class="border rounded px-3 py-2 w-full" rows="2"></textarea>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 mt-2 transition">Add Curriculum</button>
            </form>
        </div>
    </div>

    <script>
        // Add Modal
        function openAddModal() {
            document.getElementById('addModal').classList.remove('hidden');
        }
        function closeAddModal() {
            document.getElementById('addModal').classList.add('hidden');
        }
        // Edit Modal
        function openEditModal(id, title, year_start, year_end, description, status, semester) {
            document.getElementById('edit_curriculum_id').value = id;
            document.getElementById('edit_curriculum_title').value = title;
            document.getElementById('edit_curriculum_year_start').value = year_start;
            document.getElementById('edit_curriculum_year_end').value = year_end;
            document.getElementById('edit_description').value = description.replace(/\\'/g, "'");
            document.getElementById('edit_status').value = status;
            document.getElementById('edit_semester').value = semester;
            document.getElementById('editModal').classList.remove('hidden');
        }
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
        // Close modals on background click
        document.querySelectorAll('.modal-bg').forEach(bg => {
            bg.addEventListener('click', function(e) {
                if (e.target === this) this.classList.add('hidden');
            });
        });
    </script>
    <style>
        .badge { font-size: 0.85em; padding: 0.2em 0.7em; border-radius: 999px; font-weight: 600; }
        .badge-green { background: #d1fae5; color: #047857; }
        .badge-red { background: #fee2e2; color: #b91c1c; }
        .badge-blue { background: #dbeafe; color: #2563eb; }
        .badge-gray { background: #f3f4f6; color: #374151; }
        .card-shadow { box-shadow: 0 2px 8px 0 rgba(0,0,0,0.07); }
        .table th, .table td { white-space: nowrap; }
    </style>
</body>
</html>