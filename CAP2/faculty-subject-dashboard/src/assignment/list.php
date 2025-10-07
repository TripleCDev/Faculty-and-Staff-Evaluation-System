<?php
// src/assignment/list.php

require_once '../db.php';

// Fetch assignments from the database
$stmt = $conn->prepare("SELECT a.id, f.name AS faculty_name, s.name AS subject_name, a.semester, a.academic_year 
                          FROM assignments a 
                          JOIN faculty f ON a.faculty_id = f.id 
                          JOIN subjects s ON a.subject_id = s.id 
                          ORDER BY a.id");
$stmt->execute();
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../assets/tailwind.css" rel="stylesheet">
    <title>Assignment List</title>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <h1 class="text-2xl font-bold mb-4">Assignments</h1>
        <div class="mb-4">
            <a href="assign.php" class="bg-blue-500 text-white px-4 py-2 rounded">Assign Subject to Faculty</a>
        </div>
        <table class="min-w-full bg-white border border-gray-300">
            <thead>
                <tr>
                    <th class="py-2 px-4 border-b">ID</th>
                    <th class="py-2 px-4 border-b">Faculty</th>
                    <th class="py-2 px-4 border-b">Subject</th>
                    <th class="py-2 px-4 border-b">Semester</th>
                    <th class="py-2 px-4 border-b">Academic Year</th>
                    <th class="py-2 px-4 border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assignments as $assignment): ?>
                    <tr>
                        <td class="py-2 px-4 border-b"><?= htmlspecialchars($assignment['id']) ?></td>
                        <td class="py-2 px-4 border-b"><?= htmlspecialchars($assignment['faculty_name']) ?></td>
                        <td class="py-2 px-4 border-b"><?= htmlspecialchars($assignment['subject_name']) ?></td>
                        <td class="py-2 px-4 border-b"><?= htmlspecialchars($assignment['semester']) ?></td>
                        <td class="py-2 px-4 border-b"><?= htmlspecialchars($assignment['academic_year']) ?></td>
                        <td class="py-2 px-4 border-b">
                            <a href="assign.php?id=<?= $assignment['id'] ?>" class="text-blue-500 hover:underline">Reassign</a>
                            <a href="delete.php?id=<?= $assignment['id'] ?>" class="text-red-500 hover:underline ml-4" onclick="return confirm('Are you sure you want to delete this assignment?');">Remove</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>