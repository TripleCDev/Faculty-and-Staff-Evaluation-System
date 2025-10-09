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
        $isFaculty = $conn->query("SELECT id FROM faculty WHERE id = $faculty_id")->num_rows > 0;
        $isStaff = $conn->query("SELECT id FROM staff WHERE id = $faculty_id")->num_rows > 0;

        if ($isFaculty) {
            // Get user_id and faculty_id
            $user_id = null;
            $faculty_id_val = null;
            $user_stmt = $conn->prepare("SELECT user_id, faculty_id FROM faculty WHERE id = ?");
            $user_stmt->bind_param('i', $faculty_id);
            $user_stmt->execute();
            $user_stmt->bind_result($user_id, $faculty_id_val);
            $user_stmt->fetch();
            $user_stmt->close();

            // Find active self questionnaire assignment
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
            $questionnaire_id = null;
            $assign_stmt->bind_result($questionnaire_id);
            $assign_stmt->fetch();
            $assign_stmt->close();

            if (!$questionnaire_id) {
                return '<span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-200 text-gray-800 font-semibold text-xs">N/A</span>';
            }

            // Get total questions for this questionnaire
            $total_questions = 0;
            $qstmt = $conn->prepare("SELECT COUNT(*) FROM questions WHERE questionnaire_id = ?");
            $qstmt->bind_param('i', $questionnaire_id);
            $qstmt->execute();
            $qstmt->bind_result($total_questions);
            $qstmt->fetch();
            $qstmt->close();

            // Count completed self responses
            $completed_questions = 0;
            $r_stmt = $conn->prepare("
                SELECT COUNT(DISTINCT er.question_id)
                FROM evaluation_responses er
                WHERE er.evaluator_id = ?
                  AND er.evaluated_id = ?
                  AND er.questionnaire_id = ?
                  AND er.status = 'completed'
            ");
            $r_stmt->bind_param('iii', $user_id, $faculty_id_val, $questionnaire_id);
            $r_stmt->execute();
            $r_stmt->bind_result($completed_questions);
            $r_stmt->fetch();
            $r_stmt->close();

            if ($completed_questions >= $total_questions && $total_questions > 0) {
                $status = '<span class="inline-flex items-center px-3 py-1 rounded-full bg-green-200 text-green-800 font-semibold text-xs">Completed</span>';
            } elseif ($completed_questions > 0 && $completed_questions < $total_questions) {
                $status = '<span class="inline-flex items-center px-3 py-1 rounded-full bg-orange-200 text-orange-800 font-semibold text-xs">In Progress</span>';
            } else {
                $status = '<span class="inline-flex items-center px-3 py-1 rounded-full bg-yellow-200 text-yellow-800 font-semibold text-xs">Pending</span>';
            }
            return $status;
        } elseif ($isStaff) {
            // Staff logic
            $assign_stmt = $conn->prepare("
                SELECT questionnaire_id 
                FROM questionnaire_assignments 
                WHERE (staff_id = ? OR staff_id IS NULL)
                  AND (evaluation_type = 'Self' OR evaluation_type = 'self' OR evaluation_type IS NULL)
                  AND status = 'active'
                ORDER BY id DESC
                LIMIT 1
            ");
            $assign_stmt->bind_param('i', $faculty_id);
            $assign_stmt->execute();
            $questionnaire_id = null;
            $assign_stmt->bind_result($questionnaire_id);
            $assign_stmt->fetch();
            $assign_stmt->close();

            if (!$questionnaire_id) {
                return '<span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-200 text-gray-800 font-semibold text-xs">N/A</span>';
            }

            $total_questions = 0;
            $qstmt = $conn->prepare("SELECT COUNT(*) FROM questions WHERE questionnaire_id = ?");
            $qstmt->bind_param('i', $questionnaire_id);
            $qstmt->execute();
            $qstmt->bind_result($total_questions);
            $qstmt->fetch();
            $qstmt->close();

            // Get staff user_id and staff_id
            $user_id = null;
            $staff_id_val = null;
            $user_stmt = $conn->prepare("SELECT user_id, staff_id FROM staff WHERE id = ?");
            $user_stmt->bind_param('i', $faculty_id);
            $user_stmt->execute();
            $user_stmt->bind_result($user_id, $staff_id_val);
            $user_stmt->fetch();
            $user_stmt->close();

            $completed_questions = 0;
            $r_stmt = $conn->prepare("
                SELECT COUNT(DISTINCT er.question_id)
                FROM evaluation_responses er
                INNER JOIN staff s ON er.evaluator_id = s.user_id
                WHERE s.id = ?
                  AND er.evaluated_id = s.staff_id
                  AND er.questionnaire_id = ?
                  AND er.status = 'completed'
            ");
            $r_stmt->bind_param('ii', $faculty_id, $questionnaire_id);
            $r_stmt->execute();
            $r_stmt->bind_result($completed_questions);
            $r_stmt->fetch();
            $r_stmt->close();

            if ($completed_questions >= $total_questions && $total_questions > 0) {
                $status = '<span class="inline-flex items-center px-3 py-1 rounded-full bg-green-200 text-green-800 font-semibold text-xs">Completed</span>';
            } elseif ($completed_questions > 0 && $completed_questions < $total_questions) {
                $status = '<span class="inline-flex items-center px-3 py-1 rounded-full bg-orange-200 text-orange-800 font-semibold text-xs">In Progress</span>';
            }

            return $status;
        } else {
            return '<span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-200 text-gray-800 font-semibold text-xs">N/A</span>';
        }
    }

    // 🟣 PEER REVIEW
    elseif ($type === 'peer') {
        // Detect if this is a faculty or staff
        $isFaculty = $conn->query("SELECT id FROM faculty WHERE id = $faculty_id")->num_rows > 0;
        $isStaff = $conn->query("SELECT id FROM staff WHERE id = $faculty_id")->num_rows > 0;

        if ($isFaculty) {
            // Faculty logic (as before)
            $user_id = null;
            $user_stmt = $conn->prepare("SELECT user_id FROM faculty WHERE id = ?");
            $user_stmt->bind_param('i', $faculty_id);
            $user_stmt->execute();
            $user_stmt->bind_result($user_id);
            $user_stmt->fetch();
            $user_stmt->close();

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
            $stmt->bind_param('ii', $user_id, $user_id);
            $completedPeers = 0;
            $stmt->execute();
            $stmt->bind_result($completedPeers);
            $stmt->fetch();
            $stmt->close();

            if ($totalPeers > 0 && $completedPeers >= $totalPeers) {
                $status = '<span class="inline-flex items-center px-3 py-1 rounded-full bg-green-200 text-green-800 font-semibold text-xs">Completed</span>';
            }

            return $status;
        } elseif ($isStaff) {
            // Staff logic
            $user_id = null;
            $staff_id_val = null;
            $role = null;
            $department_id = null;
            $user_stmt = $conn->prepare("SELECT user_id, staff_id, role, department_id FROM staff WHERE id = ?");
            $user_stmt->bind_param('i', $faculty_id);
            $user_stmt->execute();
            $user_stmt->bind_result($user_id, $staff_id_val, $role, $department_id);
            $user_stmt->fetch();
            $user_stmt->close();

            // Only count staff peers in the same department (excluding self, Head Staff, HR, and those with NULL department)
            $peerIds = [];
            $peerSql = "SELECT staff_id FROM staff WHERE id != ? AND role = 'Staff' AND department_id = ?";
            $peerStmt = $conn->prepare($peerSql);
            $peerStmt->bind_param('ii', $faculty_id, $department_id);
            $peerStmt->execute();
            $peer_staff_id = null;
            $peerStmt->bind_result($peer_staff_id);
            while ($peerStmt->fetch()) {
                $peerIds[] = $peer_staff_id;
            }
            $peerStmt->close();
            $totalPeers = count($peerIds);

            // Count completed peer evaluations for those peers
            $completedPeers = 0;
            if ($totalPeers > 0) {
                // Build dynamic IN clause
                $in = implode(',', array_fill(0, count($peerIds), '?'));
                $types = str_repeat('i', count($peerIds) + 1);
                $sql = "SELECT COUNT(DISTINCT evaluated_id) FROM evaluation_responses WHERE evaluator_id=? AND evaluated_id IN ($in) AND status='completed'";
                $stmt = $conn->prepare($sql);

                // Prepare parameters for bind_param
                $params = array_merge([$user_id], $peerIds);

                // Use call_user_func_array for dynamic bind_param
                $bind_names[] = $types;
                foreach ($params as $key => $value) {
                    $bind_names[] = &$params[$key];
                }
                call_user_func_array([$stmt, 'bind_param'], $bind_names);

                $stmt->execute();
                $stmt->bind_result($completedPeers);
                $stmt->fetch();
                $stmt->close();
            }

            if ($totalPeers > 0 && $completedPeers >= $totalPeers) {
                $status = '<span class="inline-flex items-center px-3 py-1 rounded-full bg-green-200 text-green-800 font-semibold text-xs">Completed</span>';
            }

            return $status;
        } else {
            return '<span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-200 text-gray-800 font-semibold text-xs">N/A</span>';
        }
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
                            <th class="py-3 px-4 text-left">HR Review</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        foreach ($allPeopleList as $person):
                            $person_id = $person['id'];
                            $role = $person['role'];
                            // Format name professionally
                            $name = ucwords(strtolower($person['first_name'])) . ' ' . ucwords(strtolower($person['last_name']));
                            $isFaculty = in_array($person_id, $facultyIds);
                        ?>
<tr class="border-b last:border-b-0">
    <td class="py-3 px-4 font-semibold"><?= $i++ ?></td>
    <td class="py-3 px-4"><?= htmlspecialchars($name) ?></td>
    <td class="py-3 px-4"><?= htmlspecialchars($role) ?></td>
    <?php if ($isFaculty): ?>
        <td class="py-3 px-4"><?= getEvalStatus($conn, $person_id, 'self') ?></td>
        <td class="py-3 px-4"><?= getEvalStatus($conn, $person_id, 'peer') ?></td>
        <td class="py-3 px-4">
            <?php if (strtolower($role) === 'program head'): ?>
                <?php
                $self = getEvalStatus($conn, $person_id, 'self');
                $peer = getEvalStatus($conn, $person_id, 'peer');
                if (strpos($self, 'Completed') !== false && strpos($peer, 'Completed') !== false) {
                    echo '<span class="inline-flex items-center px-3 py-1 rounded-full bg-green-200 text-green-800 font-semibold text-xs">Completed</span>';
                } else {
                    echo '<span class="inline-flex items-center px-3 py-1 rounded-full bg-yellow-200 text-yellow-800 font-semibold text-xs">Pending</span>';
                }
                ?>
            <?php else: ?>
                <span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-200 text-gray-800 font-semibold text-xs">N/A</span>
            <?php endif; ?>
        </td>
        <td class="py-3 px-4"><span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-200 text-gray-800 font-semibold text-xs">N/A</span></td>
    <?php else: ?>
        <?php if (strtolower($role) === 'hr'): ?>
            <td class="py-3 px-4"><?= getEvalStatus($conn, $person_id, 'self') ?></td>
            <td class="py-3 px-4"><span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-200 text-gray-800 font-semibold text-xs">N/A</span></td>
            <td class="py-3 px-4"><span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-200 text-gray-800 font-semibold text-xs">N/A</span></td>
            <td class="py-3 px-4">
                <?php
                // Get HR's user_id from users table
                $hr_staff_id = $person['id'];
                $hr_user_id = null;
                $user_stmt = $conn->prepare("SELECT user_id FROM staff WHERE id = ?");
                $user_stmt->bind_param('i', $hr_staff_id);
                $user_stmt->execute();
                $user_stmt->bind_result($hr_user_id);
                $user_stmt->fetch();
                $user_stmt->close();

                // Get all faculty_ids for evaluation
                $facultySql = "SELECT faculty_id FROM faculty";
                $facultyRes = $conn->query($facultySql);
                $facultyIdsForHR = [];
                while ($facultyRes && $frow = $facultyRes->fetch_assoc()) {
                    $facultyIdsForHR[] = $frow['faculty_id'];
                }
                $completedCount = 0;
                foreach ($facultyIdsForHR as $fid) {
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM evaluation_responses WHERE evaluator_id = ? AND evaluated_id = ? AND status = 'completed'");
                    $stmt->bind_param('ii', $hr_user_id, $fid);
                    $stmt->execute();
                    $stmt->bind_result($cnt);
                    $stmt->fetch();
                    $stmt->close();
                    if ($cnt > 0) $completedCount++;
                }
                if ($completedCount === count($facultyIdsForHR) && count($facultyIdsForHR) > 0) {
                    echo '<span class="inline-flex items-center px-3 py-1 rounded-full bg-green-200 text-green-800 font-semibold text-xs">Completed</span>';
                } else {
                    echo '<span class="inline-flex items-center px-3 py-1 rounded-full bg-yellow-200 text-yellow-800 font-semibold text-xs">Pending</span>';
                }
                ?>
            </td>
        <?php else: ?>
            <td class="py-3 px-4"><?= getEvalStatus($conn, $person_id, 'self') ?></td>
            <td class="py-3 px-4"><?= getEvalStatus($conn, $person_id, 'peer') ?></td>
            <td class="py-3 px-4"><span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-200 text-gray-800 font-semibold text-xs">N/A</span></td>
            <td class="py-3 px-4"><span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-200 text-gray-800 font-semibold text-xs">N/A</span></td>
        <?php endif; ?>
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
