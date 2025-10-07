<?php
// add.php

require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $department = $_POST['department'];
    $position = $_POST['position'];

    $stmt = $conn->prepare("INSERT INTO faculty (name, department, position) VALUES (:name, :department, :position)");
    $stmt->execute([
        ':name' => $name,
        ':department' => $department,
        ':position' => $position
    ]);

    header("Location: list.php?success=faculty_added");
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
    <title>Add Faculty</title>
</head>
<body>
    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">Add New Faculty Member</h1>
        <form method="POST" class="bg-white p-6 rounded shadow-md">
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                <input type="text" name="name" id="name" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-opacity-50" placeholder="Enter faculty name">
            </div>
            <div class="mb-4">
                <label for="department" class="block text-sm font-medium text-gray-700">Department</label>
                <select name="department" id="department" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-opacity-50">
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= htmlspecialchars($dept['id']) ?>"><?= htmlspecialchars($dept['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label for="position" class="block text-sm font-medium text-gray-700">Position</label>
                <input type="text" name="position" id="position" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-opacity-50" placeholder="Enter faculty position">
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded-md hover:bg-blue-600">Add Faculty</button>
        </form>
    </div>
</body>
</html>