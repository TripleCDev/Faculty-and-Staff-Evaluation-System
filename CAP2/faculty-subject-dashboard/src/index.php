<?php
// index.php

require_once 'db.php';

session_start();

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

$facultyCount = $conn->query("SELECT COUNT(*) FROM faculty")->fetchColumn();
$subjectCount = $conn->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
$assignmentCount = $conn->query("SELECT COUNT(*) FROM assignments")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/tailwind.css">
    <title>Faculty & Subject Management Dashboard</title>
</head>
<body class="bg-gray-100">
    <header class="bg-white shadow">
        <div class="max-w-6xl mx-auto px-4 py-4">
            <h1 class="text-2xl font-bold text-gray-800">Faculty & Subject Management Dashboard</h1>
        </div>
    </header>

    <nav class="bg-gray-200">
        <div class="max-w-6xl mx-auto px-4 py-2">
            <ul class="flex space-x-4">
                <li><a href="faculty/list.php" class="text-gray-700 hover:text-gray-900">Faculty</a></li>
                <li><a href="subject/list.php" class="text-gray-700 hover:text-gray-900">Subjects</a></li>
                <li><a href="assignment/list.php" class="text-gray-700 hover:text-gray-900">Assignments</a></li>
            </ul>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto px-4 py-6">
        <?php if (isset($message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Success!</strong>
                <span class="block sm:inline"><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white p-4 rounded shadow">
                <h2 class="text-lg font-semibold">Total Faculty</h2>
                <p class="text-2xl font-bold"><?= $facultyCount ?></p>
            </div>
            <div class="bg-white p-4 rounded shadow">
                <h2 class="text-lg font-semibold">Total Subjects</h2>
                <p class="text-2xl font-bold"><?= $subjectCount ?></p>
            </div>
            <div class="bg-white p-4 rounded shadow">
                <h2 class="text-lg font-semibold">Total Assignments</h2>
                <p class="text-2xl font-bold"><?= $assignmentCount ?></p>
            </div>
        </div>
    </main>
</body>
</html>