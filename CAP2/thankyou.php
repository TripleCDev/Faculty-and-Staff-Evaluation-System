<?php
session_start();
require_once 'config.php';

// default dashboard
$dashboard = "regularDashboard.php";
if (isset($_SESSION['userType']) && $_SESSION['userType'] === 'Admin') {
    $dashboard = "systemAdminDashboard.php";
}

// Get evaluation type and duplicate flag from query string
$evaluation_type = $_GET['type'] ?? '';
$already = isset($_GET['already']) && $_GET['already'] == 1;

// Custom messages
$adminMessage = "Your admin evaluation has been submitted successfully.";
$regularMessage = "Your evaluation has been submitted successfully.";
$adminDuplicate = "You have already submitted an admin evaluation for this faculty and semester. Duplicate submissions are not allowed.";
$regularDuplicate = "You have already submitted an evaluation for this user. Duplicate submissions are not allowed.";

if ($evaluation_type === 'admin') {
    $dashboard = "systemAdminDashboard.php";
} else {
    $dashboard = "regularDashboard.php";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Thank You</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f8fafc;
            min-height: 100vh;
            margin: 0;
            font-family: 'Roboto', Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .thankyou-container {
            background: #fff;
            padding: 2.5rem 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            text-align: center;
            max-width: 400px;
            width: 100%;
        }
        .thankyou-container h1 {
            color: #467C4F;
            font-size: 2rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }
        .thankyou-container p {
            color: #333;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }
        .thankyou-container a {
            display: inline-block;
            background: #467C4F;
            color: #fff;
            padding: 0.75rem 2rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.2s;
        }
        .thankyou-container a:hover {
            background: #35653E;
        }
        .checkmark {
            font-size: 3rem;
            color: #467C4F;
            margin-bottom: 1rem;
        }
        .warning {
            font-size: 2.5rem;
            color: #eab308;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="thankyou-container">
        <?php if ($already): ?>
            <div class="warning">&#9888;</div>
            <h1>Already Submitted</h1>
            <p>
                <?php if ($evaluation_type === 'admin'): ?>
                    <?= $adminDuplicate ?>
                <?php else: ?>
                    <?= $regularDuplicate ?>
                <?php endif; ?>
            </p>
        <?php else: ?>
            <div class="checkmark">&#10004;</div>
            <h1>Thank You!</h1>
            <p>
                <?php if ($evaluation_type === 'admin'): ?>
                    <?= $adminMessage ?>
                <?php else: ?>
                    <?= $regularMessage ?>
                <?php endif; ?>
            </p>
        <?php endif; ?>
        <?php
        $returnUrl = $_GET['redirect'] ?? (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $dashboard);
        ?>
        <a href="<?= htmlspecialchars($returnUrl) ?>">Return to Previous Page</a>
    </div>
</body>
</html>
<?php
?>
