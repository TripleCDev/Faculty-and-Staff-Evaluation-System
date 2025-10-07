<?php
// faculty-subject-dashboard/src/faculty/list.php

require_once '../db.php';

try {
    $stmt = $conn->prepare("SELECT * FROM faculty");
    $stmt->execute();
    $facultyMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching faculty members: " . $e->getMessage());
}

$departments = []; // Fetch departments for filtering if needed
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/tailwind.css">
    <title>Faculty List</title>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <h1 class="text-2xl font-bold mb-4">Faculty List</h1>
        <div class="mb-4">
            <input type="text" id="search" placeholder="Search by department/program" class="border rounded p-2">
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($facultyMembers as $faculty): ?>
                <div class="bg-white p-4 rounded-lg shadow">
                    <h2 class="text-xl font-semibold"><?= htmlspecialchars($faculty['name']) ?></h2>
                    <p class="text-gray-600">Department: <?= htmlspecialchars($faculty['department']) ?></p>
                    <p class="text-gray-600">Position: <?= htmlspecialchars($faculty['position']) ?></p>
                    <div class="mt-4">
                        <a href="edit.php?id=<?= $faculty['id'] ?>" class="text-blue-500 hover:underline">Edit</a>
                        <a href="delete.php?id=<?= $faculty['id'] ?>" class="text-red-500 hover:underline ml-2">Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-6">
            <a href="add.php" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Add New Faculty</a>
        </div>
    </div>
    <script>
        document.getElementById('search').addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            const cards = document.querySelectorAll('.grid > div');
            cards.forEach(card => {
                const department = card.querySelector('p').textContent.toLowerCase();
                if (department.includes(filter)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>