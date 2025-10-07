<?php
session_start();
require_once 'config.php';

// 1. Get evaluator info (from session)
$user_id = $_SESSION['user_id'] ?? null;
$userType = $_SESSION['userType'] ?? ($_SESSION['role'] ?? null);

// 2. Get evaluatee info (from POST)
$evaluatee_id = $_POST['evaluatee_id'] ?? null;
$evaluatee_type = $_POST['evaluatee_type'] ?? '';
$evaluation_type = $_POST['evaluation_type'] ?? '';
$questionnaire_id = $_POST['questionnaire_id'] ?? null;
$curriculum_id = isset($_POST['curriculum_id']) ? (int)$_POST['curriculum_id'] : null;

// 3. Validate required data
if (!$user_id || !$evaluatee_id || !$userType || !$evaluatee_type || !$curriculum_id) {
    die("Missing required fields.");
}

// 3.1 Check if curriculum_id exists in curriculum table (foreign key safety)
$checkCurr = $conn->prepare("SELECT curriculum_id FROM curriculum WHERE curriculum_id = ?");
$checkCurr->bind_param("i", $curriculum_id);
$checkCurr->execute();
$checkCurr->store_result();
if ($checkCurr->num_rows === 0) {
    die("Invalid curriculum selected. Please contact admin.");
}
$checkCurr->close();

// 4. Fetch criteria_id for the questionnaire
$criteria_id = null;
if ($questionnaire_id) {
    $stmt = $conn->prepare("SELECT criteria_id FROM questionnaires WHERE id = ?");
    $stmt->bind_param("i", $questionnaire_id);
    $stmt->execute();
    $stmt->bind_result($criteria_id);
    $stmt->fetch();
    $stmt->close();
}

// 5. Fetch options and option_points for this criteria from criteria_options table
$options = [];
$optionPointsMap = [];
if ($criteria_id) {
    $stmt = $conn->prepare("SELECT option_text, option_point FROM criteria_options WHERE criteria_id = ? ORDER BY id ASC");
    $stmt->bind_param("i", $criteria_id);
    $stmt->execute();
    $stmt->bind_result($option_text, $option_point);
    while ($stmt->fetch()) {
        $options[] = $option_text;
        $optionPointsMap[$option_text] = is_null($option_point) ? 0 : (float)$option_point;
    }
    $stmt->close();
}

//  Calculate total score using the mapping
$totalScore = 0;
$responses = [];
foreach ($_POST as $key => $value) {
    if (preg_match('/^q(\d+)$/', $key, $matches)) {
        $responses[$matches[1]] = $value;
        $score = isset($optionPointsMap[$value]) ? $optionPointsMap[$value] : 0;
        $totalScore += $score;
    }
}

//  Handle comments safely
$comments = isset($_POST['comments']) ? trim($_POST['comments']) : null;
if ($comments === '') {
    $comments = null;
}
$status   = 'completed';
$evaluated_date = date('Y-m-d H:i:s');


if (strtolower($userType) === 'admin') {
    $check = $conn->prepare(
        "SELECT COUNT(*) FROM evaluation_responses 
         WHERE evaluator_id = ? 
           AND evaluated_id = ? 
           AND questionnaire_id = ? 
           AND curriculum_id = ? 
           AND status = 'completed'
           AND evaluator_id IS NOT NULL
           AND evaluated_id IS NOT NULL"
    );
    $check->bind_param("iiii", $user_id, $evaluatee_id, $questionnaire_id, $curriculum_id);
    $check->execute();
    $check->bind_result($already_count);
    $check->fetch();
    $check->close();

    if ($already_count > 0) {
        $conn->close();
        header('Location: thankyou.php?type=admin&already=1');
        exit;
    }
}

// 9. Insert per-question answers into evaluation_responses (new schema)
$evaluator_type = $_SESSION['role'] ?? 'Faculty';

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

$evaluated_type = mapRoleToEnum($evaluatee_type);

// STEP 1: Generate a new evaluation_id
$eval_query = "SELECT IFNULL(MAX(evaluation_id), 0) + 1 AS next_id FROM evaluation_responses";
$result = $conn->query($eval_query);
$row = $result->fetch_assoc();
$evaluation_id = $row['next_id']; 

foreach ($responses as $question_id => $answer) {
    // Extract the option text before the '(' if present
    $option_key = trim(preg_replace('/\s*\(.*\)$/', '', $answer));
    $option_point = isset($optionPointsMap[$option_key]) ? $optionPointsMap[$option_key] : 0;

    $resp_stmt = $conn->prepare(
        "INSERT INTO evaluation_responses (
            evaluation_id, question_id, answer, evaluator_id,
            evaluated_id, questionnaire_id, curriculum_id,
            score, comments, status, evaluated_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $score = $option_point;
    $evaluator_id = $user_id;
    $evaluated_id = $evaluatee_id;
    $resp_stmt->bind_param(
        "iisiidissss",
        $evaluation_id,
        $question_id,
        $answer,
        $evaluator_id,
        $evaluated_id,
        $questionnaire_id,
        $curriculum_id,
        $score,
        $comments,
        $status,
        $evaluated_date
    );

    if (!$resp_stmt->execute()) {
        die("Insert failed: " . $resp_stmt->error);
    }
    $resp_stmt->close();
}
$conn->close();

// 10. Redirect to thank you page
if (strtolower($userType) === 'admin') {
    header("Location: thankyou.php?type=admin&redirect=adminEvaluation.php");
} else {
    header("Location: thankyou.php?type=peer&redirect=regularDashboard.php");
}
exit;
?>
