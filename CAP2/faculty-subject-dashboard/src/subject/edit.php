<?php
// src/subject/edit.php

require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_subject'])) {
    $subject_id = $_POST['subject_id'];
    $subject_name = $_POST['subject_name'];
    $department = $_POST['department'];

    $stmt = $conn->prepare("UPDATE subjects SET subject_name = :subject_name, department = :department WHERE id = :id");
    $stmt->execute([
        ':subject_name' => $subject_name,
        ':department' => $department,
        ':id' => $subject_id
    ]);

    header("Location: list.php?success=subject_updated");
    exit;
}

$subject_id = $_GET['id'] ?? null;
if ($subject_id) {
    $stmt = $conn->prepare("SELECT * FROM subjects WHERE id = :id");
    $stmt->execute([':id' => $subject_id]);
    $subject = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    header("Location: list.php?error=subject_not_found");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Subject</title>
    <link rel="stylesheet" href="../assets/tailwind.css">
</head>
<body>
    <div class="container mx-auto mt-10">
        <h2 class="text-2xl font-bold mb-4">Edit Subject</h2>
        <form method="POST" class="bg-white p-6 rounded shadow-md">
            <input type="hidden" name="subject_id" value="<?= htmlspecialchars($subject['id']) ?>">
            <div class="mb-4">
                <label for="subject_name" class="block text-sm font-medium text-gray-700">Subject Name</label>
                <input type="text" name="subject_name" id="subject_name" value="<?= htmlspecialchars($subject['subject_name']) ?>" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="department" class="block text-sm font-medium text-gray-700">Department</label>
                <input type="text" name="department" id="department" value="<?= htmlspecialchars($subject['department']) ?>" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-500">
            </div>
            <div class="flex justify-end">
                <button type="submit" name="edit_subject" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Update Subject</button>
            </div>
        </form>
    </div>
</body>
</html>