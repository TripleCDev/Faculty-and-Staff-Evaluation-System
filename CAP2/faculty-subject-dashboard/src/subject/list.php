<?php
// subject/list.php

require_once '../db.php';

// Fetch subjects from the database
$stmt = $conn->prepare("SELECT * FROM subjects");
$stmt->execute();
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../assets/tailwind.css" rel="stylesheet">
    <title>Subject List</title>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <h1 class="text-2xl font-bold mb-4">Subject List</h1>
        <div class="mb-4">
            <a href="add.php" class="bg-blue-500 text-white px-4 py-2 rounded">Add New Subject</a>
        </div>
        <input type="text" id="search" placeholder="Search subjects..." class="border rounded px-4 py-2 mb-4 w-full" onkeyup="filterSubjects()">
        <table class="min-w-full bg-white border border-gray-300">
            <thead>
                <tr>
                    <th class="border px-4 py-2">Subject Name</th>
                    <th class="border px-4 py-2">Department</th>
                    <th class="border px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody id="subjectTable">
                <?php foreach ($subjects as $subject): ?>
                    <tr>
                        <td class="border px-4 py-2"><?= htmlspecialchars($subject['subject_name']) ?></td>
                        <td class="border px-4 py-2"><?= htmlspecialchars($subject['department']) ?></td>
                        <td class="border px-4 py-2">
                            <a href="edit.php?id=<?= $subject['id'] ?>" class="text-yellow-500">Edit</a>
                            <a href="delete.php?id=<?= $subject['id'] ?>" class="text-red-500 ml-4" onclick="return confirm('Are you sure you want to delete this subject?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        function filterSubjects() {
            const searchInput = document.getElementById('search').value.toLowerCase();
            const subjectRows = document.querySelectorAll('#subjectTable tr');

            subjectRows.forEach(row => {
                const subjectName = row.cells[0].textContent.toLowerCase();
                const departmentName = row.cells[1].textContent.toLowerCase();
                if (subjectName.includes(searchInput) || departmentName.includes(searchInput)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>