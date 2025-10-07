<?php

$localhost = "localhost";
$user = "root";
$password = "";
$database = "evaluation_system";
try {
    $conn = new PDO("mysql:host=$localhost;dbname=$database", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle Add Faculty (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_faculty'])) {
    $fullName = $_POST['fullName'];
    $department_id = $_POST['department_id'];
    $position = $_POST['position'];

    // Split full name into first, middle, last (simple version)
    $nameParts = explode(' ', $fullName, 3);
    $first_name = $nameParts[0] ?? '';
    $middle_name = $nameParts[1] ?? '';
    $last_name = $nameParts[2] ?? '';

    // Insert into users table
    $stmt = $conn->prepare("INSERT INTO users (fullName, userType, department_id, position) VALUES (?, 'Faculty', ?, ?)");
    $stmt->execute([$fullName, $department_id, $position]);
    $user_id = $conn->lastInsertId();

    // Insert into faculty table
    $faculty_id = $user_id; // or generate your own
    $role = 'Faculty';
    $stmt2 = $conn->prepare("INSERT INTO faculty (user_id, faculty_id, first_name, middle_name, last_name, department_id, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt2->execute([$user_id, $faculty_id, $first_name, $middle_name, $last_name, $department_id, $role]);

    header("Location: facultySection.php?success=added");
    exit;
}

// Fetch departments
$departments = $conn->query("SELECT id, department_name FROM departments ORDER BY department_name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch faculty (from faculty table)
$sql = "SELECT f.id, f.faculty_id, f.first_name, f.middle_name, f.last_name, f.role, d.department_name, p.program_name
    FROM faculty f
    LEFT JOIN departments d ON f.department_id = d.id
    LEFT JOIN programs p ON f.program_id = p.program_id
    WHERE f.role IN ('Faculty', 'Program Head', 'Admin', 'Instructor')
    ORDER BY f.last_name, f.first_name";
$faculty = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Fetch staff (from staff table)
$sql_staff = "SELECT s.id, s.staff_id, s.first_name, s.middle_name, s.last_name, s.role, d.department_name
    FROM staff s
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE s.role IN ('Staff', 'Head Staff')
    ORDER BY s.last_name, s.first_name";
$staff = $conn->query($sql_staff)->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Faculty Section</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
</head>
<body class="bg-green-50 min-h-screen font-sans">
    <!-- Top Navigation -->
    <div class="bg-white shadow flex flex-col md:flex-row md:items-center md:justify-between px-8 py-6 mb-10">
        <div class="flex items-center gap-4">
            <i class="fa-solid fa-users text-3xl text-[#2563eb]"></i>
            <span class="text-2xl font-bold text-[#23492f]">Faculty and Staff Dashboard</span>
        </div>
        <div class="flex items-center gap-2 mt-6 md:mt-0 w-full md:w-auto">
            <i class="fas fa-search text-lg"></i>
            <input type="text" placeholder="Search faculty..."
                class="w-full md:w-[30rem] border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-[#467C4F] transition"
                id="facultySearch">
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('facultySearch');
            searchInput.setAttribute('autocomplete', 'off');
            searchInput.addEventListener('input', function () {
                const val = this.value.toLowerCase();
                document.querySelectorAll('.grid > div').forEach(card => {
                    card.style.display = card.textContent.toLowerCase().includes(val) ? '' : 'none';
                });
            });
        });
    </script>

    <!-- Faculty and Staff Cards -->
    <?php if (empty($faculty) && empty($staff)): ?>
        <div class="flex justify-center items-center h-64">
            <div class="bg-white rounded-xl shadow p-8 flex flex-col items-center">
                <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15.232 15.232a6 6 0 11-8.464-8.464 6 6 0 018.464 8.464zm6.768 6.768l-4.35-4.35"/>
                </svg>
                <p class="text-gray-500 text-lg font-semibold">No faculty or staff found.</p>
                <p class="text-gray-400 mt-2 text-base">There are currently no faculty or staff members available for evaluation.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="w-full max-w-[1800px] mx-auto flex-1 px-2 sm:px-4 pb-2">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-5 gap-6">
                <?php foreach ($faculty as $f): ?>
                    <div class="bg-white rounded-2xl shadow p-6 flex flex-col gap-2 border border-[#e0e7ef] hover:shadow-2xl transition w-full min-h-[220px] h-auto">
                        <div class="flex items-center gap-3 mb-2">
                            <i class="fa-solid fa-user-tie text-3xl text-[#2563eb]"></i>
                            <div>
                                <div class="font-bold text-lg text-[#23492f]">
                                    <?= htmlspecialchars($f['first_name'] . ' ' . $f['last_name']) ?>
                            </div>
                            <div class="text-sm text-gray-500"><?= htmlspecialchars($f['role']) ?></div>
                        </div>
                    </div>
                    <div class="text-sm text-[#467C4F] mb-2">
                        <i class="fa-solid fa-building-columns"></i>
                        <?= htmlspecialchars($f['department_name'] ?? 'No Department') ?>
                        <?php if (!empty($f['program_name'])): ?>
                            <span class="text-gray-400"> - <?= htmlspecialchars($f['program_name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <a href="evaluationSummary.php?faculty_id=<?= urlencode($f['faculty_id']) ?>"
                       class="mt-3 inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg text-center transition">
                        View Evaluation Summary
                    </a>
                </div>
                <?php endforeach; ?>

                <?php foreach ($staff as $s): ?>
                    <div class="bg-white rounded-2xl shadow p-6 flex flex-col gap-2 border border-[#e0e7ef] hover:shadow-2xl transition w-full min-h-[220px] h-auto">
                        <div class="flex items-center gap-3 mb-2">
                            <i class="fa-solid fa-user text-3xl text-[#2563eb]"></i>
                            <div>
                                <div class="font-bold text-lg text-[#23492f]">
                                    <?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?>
                            </div>
                            <div class="text-sm text-gray-500"><?= htmlspecialchars($s['role']) ?></div>
                        </div>
                    </div>
                    <div class="text-sm text-[#467C4F] mb-2">
                        <i class="fa-solid fa-building-columns"></i>
                        <?= htmlspecialchars($s['department_name'] ?? 'No Department') ?>
                    </div>
                    <a href="evaluationSummary.php?staff_id=<?= urlencode($s['staff_id']) ?>"
                       class="mt-3 inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg text-center transition">
                        View Evaluation Summary
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>

