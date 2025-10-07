<?php
// assignment/assign.php

require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $faculty_id = $_POST['faculty_id'];
    $subject_id = $_POST['subject_id'];
    $semester = $_POST['semester'];
    $academic_year = $_POST['academic_year'];

    $stmt = $conn->prepare("INSERT INTO assignments (faculty_id, subject_id, semester, academic_year) VALUES (:faculty_id, :subject_id, :semester, :academic_year)");
    $stmt->execute([
        ':faculty_id' => $faculty_id,
        ':subject_id' => $subject_id,
        ':semester' => $semester,
        ':academic_year' => $academic_year
    ]);

    header("Location: ../index.php?success=assignment_added");
    exit;
}

$faculties = $conn->query("SELECT id, name FROM faculty")->fetchAll(PDO::FETCH_ASSOC);
$subjects = $conn->query("SELECT id, name FROM subjects")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../assets/tailwind.css" rel="stylesheet">
    <title>Assign Subjects to Faculty</title>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-5">
        <h1 class="text-2xl font-bold mb-4">Assign Subjects to Faculty</h1>
        <form method="POST" class="bg-white p-6 rounded shadow-md">
            <div class="mb-4">
                <label for="faculty_id" class="block text-sm font-medium text-gray-700">Select Faculty</label>
                <select name="faculty_id" id="faculty_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-500">
                    <option value="">-- Select Faculty --</option>
                    <?php foreach ($faculties as $faculty): ?>
                        <option value="<?= $faculty['id'] ?>"><?= htmlspecialchars($faculty['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label for="subject_id" class="block text-sm font-medium text-gray-700">Select Subject</label>
                <select name="subject_id" id="subject_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-500">
                    <option value="">-- Select Subject --</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label for="semester" class="block text-sm font-medium text-gray-700">Semester</label>
                <input type="text" name="semester" id="semester" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-500" placeholder="e.g. Fall 2023">
            </div>
            <div class="mb-4">
                <label for="academic_year" class="block text-sm font-medium text-gray-700">Academic Year</label>
                <input type="text" name="academic_year" id="academic_year" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-500" placeholder="e.g. 2023-2024">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700">Assign Subject</button>
        </form>
    </div>
</body>
</html>