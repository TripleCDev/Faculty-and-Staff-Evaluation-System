<?php
session_start();
require_once('config.php');
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'Admin') {
    header('Location: index.php');
    exit;
}

// --- Dashboard logic here (copy from your main dashboard content) ---

// Get total faculty (using new PK)
$facultyCount = 0;
$facultySql = "SELECT COUNT(*) as cnt FROM faculty";
$facultyRes = $conn->query($facultySql);
if ($facultyRes && $row = $facultyRes->fetch_assoc()) {
    $facultyCount = (int) $row['cnt'];
}
$staffCount = 0;
$staffSql = "SELECT COUNT(*) as cnt FROM staff";
$staffRes = $conn->query($staffSql);
if ($staffRes && $row = $staffRes->fetch_assoc()) {
    $staffCount = (int) $row['cnt'];
}

// Get all faculty (use id as PK)
$facultySql = "SELECT id, first_name, last_name, role FROM faculty";
$facultyRes = $conn->query($facultySql);
$facultyIds = [];
$facultyList = [];
while ($row = $facultyRes->fetch_assoc()) {
    $facultyIds[] = $row['id'];
    $facultyList[] = $row;
}

// Get all staff (assuming similar schema)
$staffSql = "SELECT id, first_name, last_name, role FROM staff";
$staffRes = $conn->query($staffSql);
$staffList = [];
while ($staffRes && $row = $staffRes->fetch_assoc()) {
    $staffList[] = $row;
}

// Merge faculty and staff for the table
$allPeopleList = array_merge($facultyList, $staffList);

// Example: use a section title for a specific questionnaire
$section_title = 'Attendance in School Activities';
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

// Get number of faculty fully evaluated by this admin (using evaluation_responses)
$admin_id = $_SESSION['user_id'];
$completed = 0;
foreach ($facultyIds as $fid) {
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
$pendingEvalCount = $facultyCount - $completed;

// Updated evaluation status function for new schema
// ✅ Revised getEvalStatus() — correctly uses questionnaire_id from questionnaires table
function getEvalStatus($conn, $faculty_id, $type, $admin_id = null)
{
    if (!$faculty_id) {
        return '<span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-200 text-gray-800 font-semibold text-xs">N/A</span>';
    }

    $status = '<span class="inline-flex items-center px-3 py-1 rounded-full bg-yellow-200 text-yellow-800 font-semibold text-xs">Pending</span>';

    // 🟢 SELF ASSESSMENT
    if ($type === 'self') {
        $questionnaire_id = null;

        // 1️⃣ Find assigned questionnaire for "Self" evaluation
        $assign_stmt = $conn->prepare("
    SELECT questionnaire_id 
    FROM questionnaire_assignments 
    WHERE (faculty_id = ? OR faculty_id IS NULL)
      AND (evaluation_type = 'Self' OR evaluation_type = 'self' OR evaluation_type IS NULL)
      AND status = 'active'
    ORDER BY id DESC
    LIMIT 1
");
        $assign_stmt->bind_param('i', $faculty_id);
        $assign_stmt->execute();
        $assign_stmt->bind_result($questionnaire_id);
        $assign_stmt->fetch();
        $assign_stmt->close();

        if (!$questionnaire_id) {
            return '<span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-200 text-gray-800 font-semibold text-xs">N/A</span>';
        }

        // 2️⃣ Count total questions for that questionnaire
        $total_questions = 0;
        $qstmt = $conn->prepare("SELECT COUNT(*) FROM questions WHERE questionnaire_id = ?");
        $qstmt->bind_param('i', $questionnaire_id);
        $qstmt->execute();
        $qstmt->bind_result($total_questions);
        $qstmt->fetch();
        $qstmt->close();

        // 3️⃣ Count completed responses by the same faculty
        // 3️⃣ Count completed responses by the same faculty (corrected mapping)
        $completed_questions = 0;
        $r_stmt = $conn->prepare("
    SELECT COUNT(DISTINCT er.question_id)
    FROM evaluation_responses er
    INNER JOIN faculty f ON er.evaluator_id = f.user_id
    WHERE f.id = ?
      AND er.evaluated_id = f.faculty_id
      AND er.questionnaire_id = ?
      AND er.status = 'completed'
        ");
        $r_stmt->bind_param('ii', $faculty_id, $questionnaire_id);
        $r_stmt->execute();
        $r_stmt->bind_result($completed_questions);
        $r_stmt->fetch();
        $r_stmt->close();

        // 4️⃣ Determine status
        if ($completed_questions >= $total_questions && $total_questions > 0) {
            $status = '<span class="inline-flex items-center px-3 py-1 rounded-full bg-green-200 text-green-800 font-semibold text-xs">Completed</span>';
        } elseif ($completed_questions > 0 && $completed_questions < $total_questions) {
            $status = '<span class="inline-flex items-center px-3 py-1 rounded-full bg-orange-200 text-orange-800 font-semibold text-xs">In Progress</span>';
        }

        // Debug display (optional)
        $debug = "<div style='color:red; font-size:10px;'>FID:$faculty_id | QID:$questionnaire_id | QCOUNT:$total_questions | DONE:$completed_questions</div>";

        return $status . $debug;
    }

    // 🟣 PEER REVIEW
    elseif ($type === 'peer') {
        $peerCountSql = "SELECT COUNT(*) FROM faculty WHERE id != ?";
        $peerCountStmt = $conn->prepare($peerCountSql);
        $peerCountStmt->bind_param('i', $faculty_id);
        $totalPeers = 0;
        $peerCountStmt->execute();
        $peerCountStmt->bind_result($totalPeers);
        $peerCountStmt->fetch();
        $peerCountStmt->close();

        $sql = "SELECT COUNT(DISTINCT evaluated_id) FROM evaluation_responses WHERE evaluator_id=? AND evaluated_id != ? AND status='completed'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $faculty_id, $faculty_id);
        $completedPeers = 0;
        $stmt->execute();
        $stmt->bind_result($completedPeers);
        $stmt->fetch();
        $stmt->close();

        if ($totalPeers > 0 && $completedPeers >= $totalPeers) {
            $status = '<span class="inline-flex items-center px-3 py-1 rounded-full bg-green-200 text-green-800 font-semibold text-xs">Completed</span>';
        }

        return $status;
    }

    // 🟡 PROGRAM HEAD REVIEW
    elseif ($type === 'program_head') {
        $headSql = "SELECT id FROM faculty WHERE role='Program Head' LIMIT 1";
        $headRes = $conn->query($headSql);
        $head_id = null;
        if ($headRes && $headRow = $headRes->fetch_assoc()) {
            $head_id = $headRow['id'];
        }

        if ($head_id) {
            $sql = "SELECT COUNT(*) FROM evaluation_responses WHERE evaluator_id=? AND evaluated_id=? AND status='completed'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $head_id, $faculty_id);
            $count = 0;
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            if ($count > 0) {
                $status = '<span class="inline-flex items-center px-3 py-1 rounded-full bg-green-200 text-green-800 font-semibold text-xs">Completed</span>';
            }
            return $status;
        }

        return '<span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-200 text-gray-800 font-semibold text-xs">N/A</span>';
    }

    // 🔵 ADMIN REVIEW
    elseif ($type === 'admin') {
        $sql = "SELECT COUNT(*) FROM evaluation_responses WHERE evaluator_id=? AND evaluated_id=? AND status='completed'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $admin_id, $faculty_id);
        $count = 0;
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            $status = '<span class="inline-flex items-center px-3 py-1 rounded-full bg-green-200 text-green-800 font-semibold text-xs">Completed</span>';
        }

        return $status;
    }

    return $status;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>

<body class="bg-[#f4f6fa] min-h-screen overflow-y-auto">
    <main class="p-10 fade max-w-[1800px] mx-auto mb-[10rem]">
        <h1 class="text-3xl font-bold text-[#467C4F] mb-6 flex items-center gap-3">
            <span class="material-icons text-emerald-700 text-4xl">dashboard</span>
            Admin Dashboard
        </h1>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
            <div class="bg-white rounded-2xl shadow p-8 flex flex-col items-start">
            <span class="material-icons text-blue-500 text-3xl mb-2">groups</span>
            <div class="text-[#467C4F] text-lg font-semibold mb-1">Total Faculty</div>
            <div class="text-4xl font-bold mb-2"><?= $facultyCount ?></div>
            <div class="text-gray-500 text-sm">Faculty registered in the system</div>
            </div>
            <div class="bg-white rounded-2xl shadow p-8 flex flex-col items-start">
            <span class="material-icons text-green-500 text-3xl mb-2">supervisor_account</span>
            <div class="text-[#467C4F] text-lg font-semibold mb-1">Total Staff</div>
            <div class="text-4xl font-bold mb-2"><?= $staffCount ?></div>
            <div class="text-gray-500 text-sm">Staff accounts managed</div>
            </div>
            <div class="bg-white rounded-2xl shadow p-8 flex flex-col items-start">
            <span class="material-icons text-yellow-500 text-3xl mb-2">pending_actions</span>
            <div class="text-[#467C4F] text-lg font-semibold mb-1">Pending Evaluations</div>
            <div class="text-4xl font-bold mb-2"><?= $pendingEvalCount ?></div>
            <div class="text-gray-500 text-sm">Evaluations awaiting completion</div>
            </div>
        </div>
        <!-- Faculty and Staff Evaluation Status -->
        <div class="bg-white rounded-2xl shadow p-8 mt-6">
            <h2 class="text-2xl font-bold text-[#17643b] mb-4 flex items-center gap-2">
                <span class="material-icons text-blue-600">group</span>
                Faculty and Staff Evaluation Status
            </h2>
            <div class="overflow-x-auto">
                <table class="min-w-full rounded-lg overflow-hidden">
                    <thead>
                        <tr class="bg-blue-100 text-[#17643b] text-sm font-bold">
                            <th class="py-3 px-4 text-left">#</th>
                            <th class="py-3 px-4 text-left">Name</th>
                            <th class="py-3 px-4 text-left">Role</th>
                            <th class="py-3 px-4 text-left">Self Assessment</th>
                            <th class="py-3 px-4 text-left">Peer Review</th>
                            <th class="py-3 px-4 text-left">Program Head Review</th>
                            <th class="py-3 px-4 text-left">Admin Review</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        foreach ($allPeopleList as $person):
                            $person_id = $person['id'];
                            $role = $person['role'];
                            $name = $person['first_name'] . ' ' . $person['last_name'];
                            $isFaculty = in_array($person_id, $facultyIds);
                        ?>
    <tr class="border-b last:border-b-0">
        <td class="py-3 px-4 font-semibold"><?= $i++ ?></td>
        <td class="py-3 px-4"><?= htmlspecialchars($name) ?></td>
        <td class="py-3 px-4"><?= htmlspecialchars($role) ?></td>
        <?php if ($isFaculty): ?>
            <td class="py-3 px-4"><?= getEvalStatus($conn, $person_id, 'self') ?></td>
            <td class="py-3 px-4"><?= getEvalStatus($conn, $person_id, 'peer') ?></td>
            <td class="py-3 px-4"><?= getEvalStatus($conn, $person_id, 'program_head') ?></td>
            <td class="py-3 px-4"><?= getEvalStatus($conn, $person_id, 'admin', $admin_id) ?></td>
        <?php else: ?>
            <td class="py-3 px-4"><span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-200 text-gray-800 font-semibold text-xs">N/A</span></td>
            <td class="py-3 px-4"><span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-200 text-gray-800 font-semibold text-xs">N/A</span></td>
            <td class="py-3 px-4"><span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-200 text-gray-800 font-semibold text-xs">N/A</span></td>
            <td class="py-3 px-4"><span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-200 text-gray-800 font-semibold text-xs">N/A</span></td>
        <?php endif; ?>
    </tr>
<?php endforeach; ?>
</tbody>
                </table>
            </div>
        </div>
    </main>
</body>

</html>