<?php
// edit.php

require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_faculty'])) {
    $faculty_id = $_POST['faculty_id'];
    $name = $_POST['name'];
    $department = $_POST['department'];
    $position = $_POST['position'];

    $stmt = $conn->prepare("UPDATE faculty SET name = :name, department = :department, position = :position WHERE id = :id");
    $stmt->execute([
        ':name' => $name,
        ':department' => $department,
        ':position' => $position,
        ':id' => $faculty_id
    ]);

    header("Location: list.php?success=Faculty updated successfully");
    exit;
}

if (isset($_GET['id'])) {
    $faculty_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM faculty WHERE id = :id");
    $stmt->execute([':id' => $faculty_id]);
    $faculty = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    header("Location: list.php?error=Faculty not found");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Faculty</title>
    <link rel="stylesheet" href="../assets/tailwind.css">
</head>
<body>
    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">Edit Faculty</h1>
        <form method="POST" class="bg-white p-6 rounded shadow-md">
            <input type="hidden" name="faculty_id" value="<?= htmlspecialchars($faculty['id']) ?>">
            <div class="mb-4">
                <label class="block text-gray-700">Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($faculty['name']) ?>" required class="border rounded w-full p-2">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">Department</label>
                <input type="text" name="department" value="<?= htmlspecialchars($faculty['department']) ?>" required class="border rounded w-full p-2">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">Position</label>
                <input type="text" name="position" value="<?= htmlspecialchars($faculty['position']) ?>" required class="border rounded w-full p-2">
            </div>
            <button type="submit" name="update_faculty" class="bg-blue-500 text-white px-4 py-2 rounded">Update Faculty</button>
        </form>
    </div>
</body>
</html>