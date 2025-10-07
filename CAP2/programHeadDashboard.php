<?php
require_once('config.php');
require_once 'userController.php';
require_once 'session.php';

// Redirect to login if session is not active
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fetch faculty info
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT f.faculty_id, f.first_name, f.last_name
    FROM faculty f
    JOIN users u ON f.user_id = u.id
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle missing faculty record gracefully
if (!$user) {
    $faculty_fullName = "Unknown Faculty";
    $faculty_id = $user_id;
} else {
    $faculty_id = $user['faculty_id'];
    $faculty_fullName = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
}

// Academic year & semester
$currentAcademicYear = "2025-2026";
$currentSemester = "1st Semester $currentAcademicYear";

// --- SELF EVALUATION CHECK ---
$selfEvaluationStatus = 'pending';

$checkSelf = $conn->prepare("
    SELECT COUNT(*) AS count 
    FROM evaluation_faculty 
    WHERE evaluator_id = ? AND faculty_id = ?
");
$checkSelf->bind_param("ii", $faculty_id, $faculty_id);
$checkSelf->execute();
$resultSelf = $checkSelf->get_result()->fetch_assoc();
$checkSelf->close();

if ($resultSelf && $resultSelf['count'] > 0) {
    $selfEvaluationStatus = 'completed';
}

// Fetch department and program
$stmt = $conn->prepare("SELECT department_id, program_id, first_name, last_name FROM faculty WHERE faculty_id = ?");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$facultyInfo = $stmt->get_result()->fetch_assoc();
$stmt->close();

$faculty_department = $facultyInfo['department_id'] ?? '';
$faculty_program = $facultyInfo['program_id'] ?? '';
$faculty_fullName = htmlspecialchars(($facultyInfo['first_name'] ?? 'Unknown') . ' ' . ($facultyInfo['last_name'] ?? ''));

// Get peers in same department and program (exclude self)
$stmt = $conn->prepare("
    SELECT faculty_id, first_name, last_name 
    FROM faculty 
    WHERE department_id = ? AND program_id = ? AND faculty_id != ?
");
$stmt->bind_param("iii", $faculty_department, $faculty_program, $faculty_id);
$stmt->execute();
$peers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// For each peer, check evaluation status
$instructorEvaluations = [];
foreach ($peers as $p) {
    $peerId = $p['faculty_id'];
    $peerName = htmlspecialchars($p['first_name'] . ' ' . $p['last_name']);

    $check = $conn->prepare("SELECT COUNT(*) AS count FROM evaluation_faculty WHERE evaluator_id = ? AND faculty_id = ?");
    $check->bind_param("ii", $user_id, $peerId);
    $check->execute();
    $row = $check->get_result()->fetch_assoc();
    $check->close();

    $has_evaluated = ($row['count'] > 0);

    $instructorEvaluations[] = [
        'id' => $peerId,
        'name' => $peerName,
        'course' => "Course Placeholder",
        'semester' => $currentSemester,
        'schedule' => "Flexible",
        'status' => $has_evaluated ? 'completed' : 'pending'
    ];
}

// Sort: pending first, then completed
$pendingPeers = array_filter($instructorEvaluations, fn($e) => $e['status'] === 'pending');
$completedPeers = array_filter($instructorEvaluations, fn($e) => $e['status'] === 'completed');
$instructorEvaluations = array_merge($pendingPeers, $completedPeers);

$totalInstructors = count($instructorEvaluations);
$evaluated = count($completedPeers);
$pending = $totalInstructors - $evaluated;

// DRY header variables
$userName = $faculty_fullName;
$userType = 'Faculty';
$userIdLabel = 'Faculty ID';
$userIdValue = $faculty_id;
$academicYear = $currentAcademicYear;

// DRY question fetcher
require_once 'getQuestions.php';
$questions = getEvaluationQuestions($conn, 'Some Section', $faculty_department, 'Faculty');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program Head Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-green-50 font-sans min-h-screen">
    <?php include 'header.php'; ?>

    <main class="max-w-7xl mx-auto px-2 sm:px-8 py-4 sm:py-12 mt-16 sm:mt-24">

        <!-- Welcome Banner -->
        <div class="bg-white shadow rounded-xl p-6 mb-8">
            <h2 class="text-xl font-semibold mb-2">Welcome to your Dashboard</h2>
            <p class="text-gray-700">Complete your peer and self evaluations for the current semester.</p>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
            <div class="flex items-center bg-white rounded-xl shadow p-5">
                <div class="bg-blue-100 text-blue-600 rounded-full p-3 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-4.13a4 4 0 10-8 0 4 4 0 008 0z" />
                    </svg>
                </div>
                <div>
                    <div class="text-gray-500 text-sm">Total Staff</div>
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
                    <div class="text-gray-500 text-sm">Evaluated Staff</div>
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
                        <?php if ($selfEvaluationStatus === 'pending'): ?>
                            <a href="evaluation_form.php?evaluatee_id=<?= $faculty_id ?>&type=self"
                               class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                                Start Self Evaluation
                            </a>
                        <?php else: ?>
                            <a href="viewEvaluation.php?evaluatee_id=<?= $faculty_id ?>&type=self"
                               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                                View Self Evaluation
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Staff Evaluation -->
        <section>
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Peers Available for Evaluation</h2>
            <?php if ($totalInstructors === 0): ?>
                <p class="text-gray-600">No staff assigned for evaluation.</p>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <?php foreach ($instructorEvaluations as $i): ?>
                        <div class="bg-white rounded-xl shadow p-5 text-center border-t-4 <?= $i['status'] === 'pending' ? 'border-yellow-400' : 'border-green-400'; ?>">
                            <div class="w-16 h-16 mx-auto rounded-full bg-emerald-100 flex items-center justify-center mb-3">
                                <span class="text-2xl font-bold text-emerald-700"><?= strtoupper(substr($i['name'], 0, 1)); ?></span>
                            </div>
                            <p class="font-semibold text-gray-800 text-lg"><?= $i['name']; ?></p>
                            <span class="block my-2 px-3 py-1 rounded-full text-xs font-semibold <?= $i['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'; ?>">
                                <?= ucfirst($i['status']); ?>
                            </span>
                            <?php if ($i['status'] === 'pending'): ?>
                                <a href="evaluation_form.php?evaluatee_id=<?= $i['id']; ?>&type=peer"
                                   class="block bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg font-semibold">
                                   Evaluate Now
                                </a>
                            <?php else: ?>
                                <a href="viewEvaluation.php?evaluatee_id=<?= $i['id']; ?>&type=peer"
                                   class="block bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold">
                                   View Peer Evaluation
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </main>
</body>
</html>
