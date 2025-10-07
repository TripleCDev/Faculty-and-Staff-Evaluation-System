<?php
// add.php

require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_name = $_POST['subject_name'];
    $department = $_POST['department'];
    $credits = $_POST['credits'];

    $stmt = $conn->prepare("INSERT INTO subjects (subject_name, department, credits) VALUES (:subject_name, :department, :credits)");
    $stmt->execute([
        ':subject_name' => $subject_name,
        ':department' => $department,
        ':credits' => $credits
    ]);

    header("Location: list.php?success=subject_added");
    exit;
}

$departments = $conn->query("SELECT * FROM departments")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/tailwind.css">
    <title>Add Subject</title>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-5">
        <h1 class="text-2xl font-bold mb-4">Add New Subject</h1>
        <form method="POST" class="bg-white p-6 rounded shadow-md">
            <div class="mb-4">
                <label for="subject_name" class="block text-sm font-medium text-gray-700">Subject Name</label>
                <input type="text" name="subject_name" id="subject_name" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="department" class="block text-sm font-medium text-gray-700">Department</label>
                <select name="department" id="department" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-500">
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= htmlspecialchars($dept['department_name']) ?>"><?= htmlspecialchars($dept['department_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label for="credits" class="block text-sm font-medium text-gray-700">Credits</label>
                <input type="number" name="credits" id="credits" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-500">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700">Add Subject</button>
        </form>
    </div>
</body>
</html>