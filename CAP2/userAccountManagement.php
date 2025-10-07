<?php
require_once('config.php');
require_once('session.php');



// --- Handle CRUD actions (add, edit, reset password, toggle status) here ---
$feedback = '';
$rowHighlightId = null;

// Initialize variables to avoid undefined warnings
$department = '';
$program = '';

// Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name']);
    $user_id_input = trim($_POST['user_id']);
    $userName = $_POST['userName'];
    $password = 'TCM2025CAP';
    $userType = $_POST['userType'];
    $role = $_POST['role'] ?? '';
    $created_at = date('Y-m-d H:i:s');
    $enrollment_status = $_POST['enrollment_status'] ?? 'Active';
    $status = $_POST['status'] ?? 'Active';

    // Department/program logic
    $department = $_POST['hidden_new_department_name'] ?? $_POST['department'] ?? '';
    $program = $_POST['program'] ?? '';
    $department_id = null;
    if ($department) {
        $stmt = $conn->prepare("INSERT IGNORE INTO departments (department_name) VALUES (?)");
        $stmt->bind_param('s', $department);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("SELECT id FROM departments WHERE department_name = ?");
        $stmt->bind_param('s', $department);
        $stmt->execute();
        $stmt->bind_result($department_id);
        $stmt->fetch();
        $stmt->close();
    }

    // Insert program if not exists, get program_id
    $program_id = null;
    if ($program) {
        $stmt = $conn->prepare("INSERT IGNORE INTO programs (program_name) VALUES (?)");
        $stmt->bind_param('s', $program);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("SELECT program_id FROM programs WHERE program_name = ?");
        $stmt->bind_param('s', $program);
        $stmt->execute();
        $stmt->bind_result($program_id);
        $stmt->fetch();
        $stmt->close();
    }

    // Status logic
    $status = 'Active';

    // Check if user already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE userName = ?");
    $stmt->bind_param('s', $userName);
    $stmt->execute();
    $stmt->bind_result($exists);
    $stmt->fetch();
    $stmt->close();

    if ($exists > 0) {
        $feedback = "User already exists!";
    } else {
        // Insert into users table
        $stmt = $conn->prepare("INSERT INTO users (userName, password, userType, first_name, middle_name, last_name, role, created_at, enrollment_status, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssssssss', $userName, $password, $userType, $first_name, $middle_name, $last_name, $role, $created_at, $enrollment_status, $status);
        if ($stmt->execute()) {
            $user_id = $conn->insert_id;
            $feedback = "User added successfully!";
            $rowHighlightId = $user_id;
            if ($userType === 'Admin') {
                // No need to insert into faculty or staff, just redirect to systemAdminDashboard.php if needed
                header("Location: systemAdminDashboard.php");
                exit();
            }
            if ($role === 'Faculty' || $role === 'Program Head') {
                $faculty_id = $userName;
                $stmt2 = $conn->prepare("INSERT INTO faculty (user_id, faculty_id, first_name, middle_name, last_name, department_id, program_id, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt2->bind_param(
                    'isssssis',
                    $user_id,
                    $faculty_id,
                    $first_name,
                    $middle_name,
                    $last_name,
                    $department_id,
                    $program_id,
                    $role
                );
                $stmt2->execute();
                $stmt2->close();
            }
            if ($role === 'Staff' || $role === 'Head Staff' || $role === 'HR') {
                $staff_id = $userName;
                $stmt3 = $conn->prepare("INSERT INTO staff (user_id, staff_id, first_name, middle_name, last_name, role, department_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt3->bind_param(
                    'isssssi',
                    $user_id,
                    $staff_id,
                    $first_name,
                    $middle_name,
                    $last_name,
                    $role,
                    $department_id
                );
                $stmt3->execute();
                $stmt3->close();
            }
        } else {
            $feedback = "Error adding user: {$stmt->error}";
        }
        $stmt->close();
    }
}

// Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $id = intval($_POST['edit_id']);
    $first_name = trim($_POST['edit_first_name']);
    $middle_name = trim($_POST['edit_middle_name'] ?? '');
    $last_name = trim($_POST['edit_last_name']);
    $userName = trim($_POST['edit_userName']);
    $userType = $_POST['edit_userType'];
    $role = $_POST['edit_role'];

    // Get current role from users table
    $stmt = $conn->prepare("SELECT role FROM users WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($currentRole);
    $stmt->fetch();
    $stmt->close();

    // Update users table
    $stmt = $conn->prepare("UPDATE users SET userName=?, first_name=?, middle_name=?, last_name=?, userType=?, role=? WHERE id=?");
    $stmt->bind_param('ssssssi', $userName, $first_name, $middle_name, $last_name, $userType, $role, $id);
    $success = $stmt->execute();
    $stmt->close();

    // Remove from faculty/staff if role changed
    if ($currentRole !== $role) {
        $conn->query("DELETE FROM faculty WHERE user_id=$id");
        $conn->query("DELETE FROM staff WHERE user_id=$id");
    }

    // Insert/update faculty if role is Faculty or Program Head
    if ($role === 'Faculty' || $role === 'Program Head') {
        $department = $_POST['edit_department'] ?? '';
        $program = $_POST['edit_program'] ?? '';

        // Only get IDs if not empty
        $department_id = null;
        if (!empty($department)) {
            $stmtDept = $conn->prepare("SELECT id FROM departments WHERE department_name = ?");
            $stmtDept->bind_param('s', $department);
            $stmtDept->execute();
            $stmtDept->bind_result($department_id);
            $stmtDept->fetch();
            $stmtDept->close();
        }

        $program_id = null;
        if (!empty($program)) {
            $stmtProg = $conn->prepare("SELECT program_id FROM programs WHERE program_name = ?");
            $stmtProg->bind_param('s', $program);
            $stmtProg->execute();
            $stmtProg->bind_result($program_id);
            $stmtProg->fetch();
            $stmtProg->close();
        }

        // Check if faculty row exists
        $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM faculty WHERE user_id=?");
        $stmtCheck->bind_param('i', $id);
        $stmtCheck->execute();
        $stmtCheck->bind_result($facultyExists);
        $stmtCheck->fetch();
        $stmtCheck->close();

        $faculty_id = $userName;
        if ($facultyExists) {
            $stmtUpdate = $conn->prepare("UPDATE faculty SET faculty_id=?, first_name=?, middle_name=?, last_name=?, department_id=?, program_id=?, role=? WHERE user_id=?");
            $stmtUpdate->bind_param('ssssiisi', $faculty_id, $first_name, $middle_name, $last_name, $department_id, $program_id, $role, $id);
            $stmtUpdate->execute();
            $stmtUpdate->close();
        } else {
            $stmtInsert = $conn->prepare("INSERT INTO faculty (user_id, faculty_id, first_name, middle_name, last_name, department_id, program_id, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtInsert->bind_param('isssssis', $id, $faculty_id, $first_name, $middle_name, $last_name, $department_id, $program_id, $role);
            $stmtInsert->execute();
            $stmtInsert->close();
        }
    }

    // Insert/update staff if role is Staff
    if ($role === 'Staff' || $role === 'Head Staff' || $role === 'HR') {
        // Check if staff row exists
        $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM staff WHERE user_id=?");
        $stmtCheck->bind_param('i', $id);
        $stmtCheck->execute();
        $stmtCheck->bind_result($staffExists);
        $stmtCheck->fetch();
        $stmtCheck->close();

        $staff_id = $userName;
        if ($staffExists) {
            $stmtUpdate = $conn->prepare("UPDATE staff SET staff_id=?, first_name=?, middle_name=?, last_name=?, role=?, department_id=? WHERE user_id=?");
            $stmtUpdate->bind_param('ssssssi', $staff_id, $first_name, $middle_name, $last_name, $role, $department_id, $id);
            $stmtUpdate->execute();
            $stmtUpdate->close();
        } else {
            $stmtInsert = $conn->prepare("INSERT INTO staff (user_id, staff_id, first_name, middle_name, last_name, role, department_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtInsert->bind_param('isssssi', $id, $staff_id, $first_name, $middle_name, $last_name, $role, $department_id);
            $stmtInsert->execute();
            $stmtInsert->close();
        }
    }

    // No insert/update for HR

    if ($success) {
        $feedback = "User updated successfully!";
        $rowHighlightId = $id;
    } else {
        $feedback = "Error updating user: " . ($stmtUpdate->error ?? $conn->error);
    }
}

// Reset Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $id = intval($_POST['reset_id']);
    $newPassword = trim($_POST['reset_new_password']);

    // Always set to default if requested or if the special value is set
    if ($newPassword === '__RESET_TO_DEFAULT__') {
        $newPassword = 'TCM2025CAP';
    }

    // Save as plain text (no hashing)
    $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
    $stmt->bind_param('si', $newPassword, $id);
    $stmt->execute();
    $stmt->close();
}

// Change Password (new feature)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $id = intval($_POST['user_id']);
    $newPassword = trim($_POST['new_password']);

    // Optional: Add validation for $newPassword here

    // Update the user's password only
    $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
    $stmt->bind_param('si', $newPassword, $id);
    if ($stmt->execute()) {
        $feedback = "Password updated successfully!";
    } else {
        $feedback = "Error updating password: " . $stmt->error;
    }
    $stmt->close();
}

// Toggle Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $id = intval($_POST['toggle_id']);
    $current_status = $_POST['toggle_current_status'];
    $userType = $_POST['toggle_userType'];
    // Determine new status
    $new_status = $current_status === 'Active' ? 'Inactive' : 'Active';
    $stmt = $conn->prepare("UPDATE users SET status=? WHERE id=?");
    $stmt->bind_param('si', $new_status, $id);
    if ($stmt->execute()) {
        $feedback = "Status updated!";
        $rowHighlightId = $id;
    } else {
        $feedback = "Error updating status: " . $stmt->error;
    }
    $stmt->close();
}

// --- Search, filter, sort, and pagination logic ---
$search = $_GET['search'] ?? '';
$type_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'desc';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where = "WHERE 1=1";
$params = [];
$types = '';
if ($search) {
    $where .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.userName LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'sss';
}
if ($type_filter && $type_filter !== 'All') {
    $where .= " AND u.role = ?";
    $params[] = $type_filter;
    $types .= 's';
}
if ($status_filter !== '') {
    $where .= " AND u.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

// Count total users
$count_sql = "SELECT COUNT(*) FROM users u $where";
$stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$stmt->bind_result($total);
$stmt->fetch();
$stmt->close();

// Fetch users
$validSort = ['first_name', 'last_name', 'userName', 'userType', 'status', 'created_at'];
if (!in_array($sort, $validSort))
    $sort = 'created_at';
$order = strtolower($order) === 'asc' ? 'asc' : 'desc';
$sql = "SELECT 
    u.id, u.userName, 
    u.userType, 
    u.first_name AS user_first_name,
    u.middle_name AS user_middle_name,
    u.last_name AS user_last_name,
    u.role,
    u.status,
    u.created_at,
    f.first_name AS faculty_first_name,
    f.middle_name AS faculty_middle_name,
    f.last_name AS faculty_last_name,
    f.department_id AS faculty_department_id,
    f.program_id AS faculty_program_id,
    d.department_name AS faculty_department,
    p.program_name AS faculty_program
FROM users u
LEFT JOIN faculty f ON u.id = f.user_id
LEFT JOIN departments d ON f.department_id = d.id
LEFT JOIN programs p ON f.program_id = p.program_id
$where ORDER BY $sort $order LIMIT ? OFFSET ?";
$bind_params = $params;
$bind_types = $types . 'ii';
$bind_params[] = $per_page;
$bind_params[] = $offset;
$stmt = $conn->prepare($sql);
$stmt->bind_param($bind_types, ...$bind_params);
$stmt->execute();
$stmt->bind_result(
    $id,
    $userName,
    $userType,
    $user_first_name,
    $user_middle_name,
    $user_last_name,
    $role,
    $status,
    $created_at,
    $faculty_first_name,
    $faculty_middle_name,
    $faculty_last_name,
    $faculty_department_id,
    $faculty_program_id,
    $faculty_department,
    $faculty_program
);

$users = [];
while ($stmt->fetch()) {
    $first_name = $faculty_first_name ?: $user_first_name ?: '';
    $middle_name = $faculty_middle_name ?: $user_middle_name ?: '';
    $last_name = $faculty_last_name ?: $user_last_name ?: '';
    if (!$first_name && !$last_name) {
        $first_name = $userName;
    }
    $users[] = [
        'id' => $id,
        'first_name' => $first_name,
        'middle_name' => $middle_name,
        'last_name' => $last_name,
        'userName' => $userName,
        'userType' => $userType,
        'role' => $role,
        'status' => $status,
        'display_status' => $status,
        'created_at' => $created_at,
        'department' => $faculty_department ?: '',
        'program' => $faculty_program ?: ''
    ];
}
$stmt->close();
$total_pages = ceil($total / $per_page);

function h($v)
{
    return htmlspecialchars($v ?? '');
}
function userTypeBadge($type)
{
    if ($type === 'Faculty')
        return 'bg-[#dbeafe] text-[#2563eb]';
    if ($type === 'Admin')
        return 'bg-gray-200 text-gray-700';
    if ($type === 'Staff')
        return 'bg-[#fef9c3] text-[#b45309]';
    return 'bg-gray-100 text-gray-500';
}
function statusBadge($type, $status)
{
    return $status === 'Active' ? 'bg-[#dbeafe] text-[#2563eb]' : 'bg-gray-200 text-gray-600';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>User Account Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <style>
        .row-success {
            background: #e6fbe6 !important;
            transition: background 1s;
        }

        .row-error {
            background: #fde2e2 !important;
            transition: background 1s;
        }

        .action-btn {
            transition: background 0.2s;
        }

        .action-btn.edit {
            background: #2563eb;
            color: #fff;
        }

        .action-btn.edit:hover {
            background: #1746a2;
        }

        .action-btn.reset {
            background: #f59e42;
            color: #fff;
        }

        .action-btn.reset:hover {
            background: #c97a13;
        }

        .action-btn.toggle-active {
            background: #10b981;
            color: #fff;
        }

        .action-btn.toggle-active:hover {
            background: #059669;
        }

        .action-btn.toggle-inactive {
            background: #9ca3af;
            color: #fff;
        }

        .action-btn.toggle-inactive:hover {
            background: #6b7280;
        }

        .modal-bg {
            background: rgba(0, 0, 0, 0.3);
        }

        .table-row-alt {
            background: #f9fafb;
        }
    </style>
</head>

<body class="bg-[#f4f6fa] min-h-screen overflow-hidden">
    <!-- Main Layout -->
    <div class="flex flex-col lg:flex-row max-w-[1800px] mx-auto py-10 px-2 gap-8 min-h-screen">
        <!-- Sidebar: Filters & Quick Actions -->
        <aside class="w-full lg:w-[340px] flex-shrink-0 mb-8 lg:mb-0">
            <div class="bg-white rounded-2xl shadow p-8 mb-8">
                <h2 class="text-xl font-bold text-[#467C4F] mb-6">Filter</h2>
                <form method="get" class="space-y-4">
                    <input type="text" name="search" value="<?= h($search) ?>" placeholder="Search by name or username"
                        class="w-full border rounded px-4 py-3 text-base" />
                    <select name="role" class="w-full border rounded px-4 py-3 text-base"
                        onchange="this.form.submit()">
                        <option value="All" <?= $type_filter === 'All' ? 'selected' : '' ?>>All Roles</option>
                        <option value="Faculty" <?= $type_filter === 'Faculty' ? 'selected' : '' ?>>Faculty</option>
                        <option value="Staff" <?= $type_filter === 'Staff' ? 'selected' : '' ?>>Staff</option>
                        <option value="Program Head" <?= $type_filter === 'Program Head' ? 'selected' : '' ?>>Program Head
                        </option>
                        <option value="HR" <?= $type_filter === 'HR Admin' ? 'selected' : '' ?>>HR Admin</option>
                    </select>
                    <select name="status" class="w-full border rounded px-4 py-3 text-base"
                        onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="Active" <?= $status_filter === 'Active' ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= $status_filter === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                    <button type="submit"
                        class="w-full bg-[#467C4F] text-white rounded px-4 py-3 mt-2 text-base font-semibold hover:bg-[#2563eb] transition">Apply</button>
                </form>
            </div>
            <div class="bg-white rounded-2xl shadow p-8">
                <h2 class="text-xl font-bold text-[#467C4F] mb-6">Quick Actions</h2>
                <button onclick="showAddModal()"
                    class="w-full bg-[#2563eb] text-white rounded px-4 py-3 text-base font-semibold hover:bg-[#1746a2] transition">
                    <i class="fa fa-user-plus mr-2"></i>Add New User
                </button>
            </div>
        </aside>

        <!-- Main Panel: User Account Management -->
        <main class="flex-1 w-full">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8 gap-2">
                <h1 class="text-3xl font-bold text-[#467C4F]">User Account Management</h1>
            </div>
            <!-- Feedback -->
            <?php if ($feedback): ?>
                <div
                    class="mb-6 px-6 py-4 rounded text-lg <?= strpos($feedback, 'success') !== false ? 'bg-[#e6fbe6] text-[#10b981]' : 'bg-[#5ec478] text-[#ffffff]' ?>">
                    <?= h($feedback) ?>
                </div>
            <?php endif; ?>
            <!-- Table -->
            <div class="bg-white rounded-2xl shadow overflow-x-auto" style="margin-left: 12px; margin-right: 12px;">
                <table class="min-w-full divide-y divide-gray-200 text-base"
                    style="border-radius: 18px; overflow: hidden;">
                    <thead class="bg-[#e6f7f1]">
                        <tr>
                            <th class="px-6 py-4 font-semibold text-[#222] text-left">Full Name</th>
                            <th class="px-6 py-4 font-semibold text-[#222] text-left">Username</th>
                            <th class="px-6 py-4 font-semibold text-[#222] text-left">User Type</th>
                            <th class="px-6 py-4 font-semibold text-[#222] text-left">Role</th>
                            <th class="px-6 py-4 font-semibold text-[#222] text-left">Status</th>
                            <th class="px-6 py-4 font-semibold text-[#222] text-left">Date Created</th>
                            <th class="px-6 py-4 font-semibold text-[#222] text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($users as $i => $u): ?>
                            <tr class="<?= $i % 2 ? 'table-row-alt' : '' ?><?= $rowHighlightId == $u['id'] ? ' row-success' : '' ?>"
                                id="row-<?= $u['id'] ?>">
                                <td class="px-6 py-4">
                                    <?= h(trim($u['first_name'] . ' ' . $u['middle_name'] . ' ' . $u['last_name'])) ?></td>
                                <td class="px-6 py-4"><?= h($u['userName']) ?></td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-block px-3 py-1 rounded-full text-xs font-semibold <?= userTypeBadge($u['userType']) ?>">
                                        <?= h($u['userType']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4"><?= h($u['role']) ?></td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-block px-3 py-1 rounded-full text-xs font-semibold <?= statusBadge($u['userType'], $u['display_status']) ?>">
                                        <?= h($u['display_status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4"><?= h(date('Y-m-d', strtotime($u['created_at']))) ?></td>
                                <td class="px-6 py-4 flex gap-2 flex-wrap">
                                    <button class="action-btn edit px-3 py-1 rounded" onclick="showEditModal(
                                        <?= $u['id'] ?>,
                                        '<?= h($u['first_name']) ?>',
                                        '<?= h($u['middle_name']) ?>',
                                        '<?= h($u['last_name']) ?>',
                                        '<?= h($u['userName']) ?>',
                                        '<?= h($u['userType']) ?>',
                                        '<?= h($u['role']) ?>',
                                        '<?= h($u['department'] ?? '') ?>',
                                        '<?= h($u['program'] ?? '') ?>',
                                        '<?= h($u['userName'] ?? '') ?>'
                                    )">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <button class="action-btn reset px-3 py-1 rounded"
                                        onclick="showResetModal(<?= $u['id'] ?>)">
                                        <i class="fa fa-key"></i>
                                    </button>
                                    <?php
                                    $toggleClass = $u['display_status'] == 'Active' ? 'toggle-active' : 'toggle-inactive';
                                    ?>
                                    <button class="action-btn <?= $toggleClass ?> px-3 py-1 rounded"
                                        onclick="showToggleModal(<?= $u['id'] ?>, '<?= h($u['display_status']) ?>', '<?= h($u['userType']) ?>')">
                                        <i
                                            class="fa <?= ($u['display_status'] == 'Active' ? 'fa-toggle-on' : 'fa-toggle-off') ?>"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-gray-400 py-8">No users found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div class="flex justify-between items-center mt-6 px-4">
                <div class="text-sm text-gray-600">
                    Showing <?= ($offset + 1) ?> to <?= min($offset + $per_page, $total) ?> of <?= $total ?> users
                </div>
                <div class="flex gap-1">
                    <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"
                            class="px-3 py-1 rounded <?= $p == $page ? 'bg-[#467C4F] text-white' : 'bg-gray-200 text-gray-700 hover:bg-[#e6f7f1]' ?>">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Add User Modal -->
    <div id="addModal" class="modal-bg fixed inset-0 flex items-start justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-lg p-8 w-full max-w-lg z-10 max-h-[90vh]">
            <h3 class="text-2xl font-bold mb-6 text-[#467C4F]">Add New User</h3>
            <form id="addUserForm" method="post" class="space-y-4" autocomplete="off">
                <input type="hidden" name="add_user" value="1">

                <!-- User Type -->
                <div>
                    <label class="block font-semibold text-sm text-gray-700 mb-1">User Type</label>
                    <select name="userType" id="add_userType" class="border rounded px-3 py-2 w-full" required autocomplete="off" onchange="toggleRoleField()">
                        <option value="">Select User Type</option>
                        <option value="Regular">Regular</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>

                <!-- Role (only for Regular) -->
                <div id="roleDiv">
                    <label class="block font-semibold text-sm text-gray-700 mb-1">Role</label>
                    <select name="role" id="add_role" class="border rounded px-3 py-2 w-full" autocomplete="off">
                        <option value="">Select Role</option>
                        <option value="Program Head">Program Head</option>
                        <option value="Faculty">Faculty</option>
                        <option value="Staff">Staff</option>
                        <option value="Head Staff">Head Staff</option>
                        <option value="HR">HR</option>
                    </select>
                </div>

                <!-- User ID -->
                <div>
                    <label class="block font-semibold text-sm text-gray-700 mb-1">User ID</label>
                    <input type="number" name="user_id" id="add_user_id" min="0" placeholder="User ID"
                        class="border rounded px-3 py-2 w-full" required autocomplete="off">
                </div>

                <!-- Name Fields (always visible) -->
                <div id="addNameFields" class="flex flex-col md:flex-row gap-3">
                    <div class="flex-1">
                        <label class="block font-semibold text-sm text-gray-700 mb-1">First Name</label>
                        <input type="text" name="first_name" placeholder="First Name"
                            class="border rounded px-3 py-2 w-full" required autocomplete="off">
                    </div>
                    <div class="flex-1">
                        <label class="block font-semibold text-sm text-gray-700 mb-1">Middle Name</label>
                        <input type="text" name="middle_name" placeholder="Middle Name"
                            class="border rounded px-3 py-2 w-full" autocomplete="off">
                    </div>
                    <div class="flex-1">
                        <label class="block font-semibold text-sm text-gray-700 mb-1">Last Name</label>
                        <input type="text" name="last_name" placeholder="Last Name"
                            class="border rounded px-3 py-2 w-full" required autocomplete="off">
                    </div>
                </div>

                <!-- Department -->
                <div id="addDepartmentDiv">
                    <label class="block font-semibold text-sm text-gray-700 mb-1">Department</label>
                    <div class="flex gap-2">
                        <select name="department" id="add_department" class="border rounded px-3 py-2 flex-1"
                            autocomplete="off">
                            <option value="" selected disabled>Select Department</option>
                            <?php
                            $prog_res = $conn->query("SELECT department_name FROM departments ORDER BY department_name ASC");
                            if ($prog_res) {
                                while ($prow = $prog_res->fetch_assoc()) {
                                    echo '<option value="' . h($prow['department_name']) . '">' . h($prow['department_name']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                        <button type="button" onclick="showAddDepartmentInput()"
                            class="px-3 py-2 rounded bg-[#2563eb] text-white hover:bg-[#1746a2] text-xs flex items-center">
                            <i class="fa fa-plus mr-1"></i>Add Department
                        </button>
                    </div>
                    <div id="addDepartmentInput" class="flex gap-2 items-center mt-2" style="display:none;">
                        <input type="text" id="new_department_name" class="border rounded px-3 py-2 flex-1"
                            placeholder="New Department Name">
                        <button type="button" onclick="addDepartmentOption()"
                            class="px-3 py-2 rounded bg-[#10b981] text-white hover:bg-[#059669] text-xs">Save</button>
                        <button type="button" onclick="hideAddDepartmentInputAndClearForm()"
                            class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300 text-xs">Cancel</button>
                    </div>
                </div>

                <!-- Program with Add Program Button -->
                <div id="addProgramDiv">
                    <label class="block font-semibold text-sm text-gray-700 mb-1">Program</label>
                    <div class="flex gap-2">
                        <select name="program" id="add_program" class="border rounded px-3 py-2 flex-1"
                            autocomplete="off">
                            <option value="" selected disabled>Select program</option>
                            <?php
                            $prog_res = $conn->query("SELECT program_name FROM programs ORDER BY program_name ASC");
                            if ($prog_res) {
                                while ($prow = $prog_res->fetch_assoc()) {
                                    echo '<option value="' . h($prow['program_name']) . '">' . h($prow['program_name']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                        <button type="button" onclick="showAddProgramInput()"
                            class="px-3 py-2 rounded bg-[#2563eb] text-white hover:bg-[#1746a2] text-xs flex items-center">
                            <i class="fa fa-plus mr-1"></i>Add Program
                        </button>
                    </div>
                    <div id="addProgramInput" class="flex gap-2 items-center mt-2" style="display:none;">
                        <input type="text" id="new_program_name" class="border rounded px-3 py-2 flex-1"
                            placeholder="New Program Name">
                        <button type="button" onclick="addProgramOption()"
                            class="px-3 py-2 rounded bg-[#10b981] text-white hover:bg-[#059669] text-xs">Save</button>
                        <button type="button" onclick="hideAddProgramInputAndClearForm()"
                            class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300 text-xs">Cancel</button>
                    </div>
                </div>

                <!-- Username is set automatically to User ID -->
                <input type="hidden" name="userName" id="add_userName" autocomplete="off">

                <!-- Username (auto-filled from User ID) -->
                <div>
                    <label class="block font-semibold text-sm text-gray-700 mb-1">Username</label>
                    <input type="text" id="display_userName" class="border rounded px-3 py-2 w-full bg-gray-100"
                        readonly autocomplete="off">
                </div>

                <!-- Password (default, readonly) -->
                <div>
                    <label class="block font-semibold text-sm text-gray-700 mb-1">Password</label>
                    <input type="text" name="password" id="add_password"
                        class="border rounded px-3 py-2 w-full bg-gray-100" value="TCM2025CAP" readonly
                        autocomplete="off">
                </div>
                <input type="hidden" name="new_program_name" id="hidden_new_program_name">
                <script>
                    // Auto-fill username field from User ID
                    document.getElementById('add_user_id').addEventListener('input', function () {
                        var userId = this.value.trim();
                        document.getElementById('add_userName').value = userId;
                        document.getElementById('display_userName').value = userId;
                    });

                    // Show/hide role field based on user type
                    function toggleRoleField() {
                        var userType = document.getElementById('add_userType').value;
                        var roleDiv = document.getElementById('roleDiv');
                        var deptDiv = document.getElementById('addDepartmentDiv');
                        var progDiv = document.getElementById('addProgramDiv');
                        if (userType === 'Admin') {
                            roleDiv.style.display = 'none';
                            deptDiv.style.display = 'none';
                            progDiv.style.display = 'none';
                            document.getElementById('add_role').required = false;
                            document.getElementById('add_department').required = false;
                            document.getElementById('add_program').required = false;
                        } else {
                            roleDiv.style.display = '';
                            deptDiv.style.display = '';
                            progDiv.style.display = '';
                            document.getElementById('add_role').required = true;
                            document.getElementById('add_department').required = true;
                            document.getElementById('add_program').required = true;
                        }
                    }
                </script>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" onclick="resetAddUserForm(); closeAddModal();"
                        class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300">Cancel</button>
                    <button type="submit"
                        class="px-3 py-2 rounded bg-[#467C4F] text-white hover:bg-[#35653E]">Add</button>
                </div>
                <script>
                    function resetAddUserForm() {
                        var form = document.getElementById('addUserForm');
                        form.reset();
                        document.getElementById('add_userName').value = '';
                        document.getElementById('display_userName').value = '';
                        hideAddProgramInput();
                    }
                </script>
            </form>
        </div>
    </div>
    <script>
        // Show/hide add program input
        function showAddProgramInput() {
            document.getElementById('addProgramInput').style.display = '';
            document.getElementById('new_program_name').focus();
        }
        function hideAddProgramInput() {
            document.getElementById('addProgramInput').style.display = 'none';
            document.getElementById('new_program_name').value = '';
        }
        function addProgramOption() {
            var newProgram = document.getElementById('new_program_name').value.trim();
            if (!newProgram) return;
            var select = document.getElementById('add_program');
            // Check if already exists
            for (var i = 0; i < select.options.length; i++) {
                if (select.options[i].value.toLowerCase() === newProgram.toLowerCase()) {
                    select.selectedIndex = i;
                    hideAddProgramInput();
                    return;
                }
            }
            var opt = document.createElement('option');
            opt.value = newProgram;
            opt.text = newProgram;
            select.add(opt);
            select.value = newProgram;
            hideAddProgramInput();
            // Set hidden input so PHP can process it
            document.getElementById('hidden_new_program_name').value = newProgram;
        }


        // Show/hide add department input
        function showAddDepartmentInput() {
            document.getElementById('addDepartmentInput').style.display = '';
            document.getElementById('new_department_name').focus();
        }
        function hideAddDepartmentInput() {
            document.getElementById('addDepartmentInput').style.display = 'none';
            document.getElementById('new_department_name').value = '';
        }
        function addDepartmentOption() {
            var newDepartment = document.getElementById('new_department_name').value.trim();
            if (!newDepartment) return;
            var select = document.getElementById('add_department');
            // Check if already exists
            for (var i = 0; i < select.options.length; i++) {
                if (select.options[i].value.toLowerCase() === newDepartment.toLowerCase()) {
                    select.selectedIndex = i;
                    hideAddDepartmentInput();
                    return;
                }
            }
            var opt = document.createElement('option');
            opt.value = newDepartment;
            opt.text = newDepartment;
            select.add(opt);
            select.value = newDepartment;
            hideAddDepartmentInput();
            // Set hidden input so PHP can process it
            document.getElementById('hidden_new_department_name').value = newDepartment;
        }
    </script>
    <!-- Edit User Modal -->
    <div id="editModal" class="fixed modal-bg inset-0 flex items-start justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-lg p-8 w-full max-h-[90vh] max-w-md z-10">
            <h3 class="text-2xl font-bold mb-6 text-[#467C4F]">Edit User</h3>
            <form id="editUserForm" method="post" class="space-y-4" autocomplete="off">
                <input type="hidden" name="edit_user" value="1">
                <input type="hidden" name="edit_id" id="edit_id">

                <!-- User Type -->
                <div>
                    <label for="edit_userType" class="block font-semibold text-sm text-gray-700 mb-1">User Type</label>
                    <select name="edit_userType" id="edit_userType" class="border rounded px-3 py-2 w-full" required>
                        <option value="">Select User Type</option>
                        <option value="Regular">Regular</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>

                <!-- Role -->
                <div>
                    <label for="edit_role" class="block font-semibold text-sm text-gray-700 mb-1">Role</label>
                    <select name="edit_role" id="edit_role" class="border rounded px-3 py-2 w-full" required>
                        <option value="">Select Role</option>
                        <option value="Faculty">Faculty</option>
                        <option value="Staff">Staff</option>
                        <option value="Program Head">Program Head</option>
                        <option value="Admin">Admin</option>
                        <option value="HR">HR</option>
                        <option value="Head Staff">Head Staff</option>
                    </select>
                </div>

                <!-- User ID -->
                <div>
                    <label for="edit_user_id" class="block font-semibold text-sm text-gray-700 mb-1">User ID</label>
                    <input type="number" name="edit_user_id" id="edit_user_id" min="0" placeholder="User ID"
                        class="border rounded px-3 py-2 w-full">
                </div>

                <!-- Name Fields -->
                <div class="flex flex-col md:flex-row gap-3">
                    <div class="flex-1">
                        <label for="edit_first_name" class="block font-semibold text-sm text-gray-700 mb-1">First
                            Name</label>
                        <input type="text" name="edit_first_name" id="edit_first_name" placeholder="First Name"
                            class="border rounded px-3 py-2 w-full" required>
                    </div>
                    <div class="flex-1">
                        <label for="edit_middle_name" class="block font-semibold text-sm text-gray-700 mb-1">Middle
                            Name</label>
                        <input type="text" name="edit_middle_name" id="edit_middle_name" placeholder="Middle Name"
                            class="border rounded px-3 py-2 w-full">
                    </div>
                    <div class="flex-1">
                        <label for="edit_last_name" class="block font-semibold text-sm text-gray-700 mb-1">Last
                            Name</label>
                        <input type="text" name="edit_last_name" id="edit_last_name" placeholder="Last Name"
                            class="border rounded px-3 py-2 w-full" required>
                    </div>
                </div>

                <!-- Department -->
                <div id="editDepartmentDiv" style="display:none;">
                    <label class="block font-semibold text-sm text-gray-700 mb-1 mt-2">Department</label>
                    <select name="edit_department" id="edit_department" class="border rounded px-3 py-2 w-full">
                        <option value="" selected disabled>Select department</option>
                        <?php
                        $dept_res = $conn->query("SELECT department_name FROM departments ORDER BY department_name ASC");
                        if ($dept_res) {
                            while ($drow = $dept_res->fetch_assoc()) {
                                echo '<option value="' . h($drow['department_name']) . '">' . h($drow['department_name']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- Program -->
                <div id="editProgramDiv" style="display: none;" >
                    <label class="block font-semibold text-sm text-gray-700 mb-1">Program</label>
                    <select name="edit_program" id="edit_program" class="border rounded px-3 py-2 w-full">
                        <option value="" selected disabled>Select program</option>
                        <?php
                        $prog_res = $conn->query("SELECT program_name FROM programs ORDER BY program_name ASC");
                        if ($prog_res) {
                            while ($prow = $prog_res->fetch_assoc()) {
                                echo '<option value="' . h($prow['program_name']) . '">' . h($prow['program_name']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- Username -->
                <div>
                    <label for="edit_userName" class="block font-semibold text-sm text-gray-700 mb-1">Username</label>
                    <input type="number" name="edit_userName" id="edit_userName" min="0" placeholder="Username"
                        class="border rounded px-3 py-2 w-full" required>
                </div>

                <div class="flex justify-end gap-2 mt-2">
                    <button type="button" onclick="closeEditModal()"
                        class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300">Cancel</button>
                    <button type="submit"
                        class="px-3 py-2 rounded bg-[#2563eb] text-white hover:bg-[#1746a2]">Save</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.getElementById('edit_role').addEventListener('change', function () {
            var role = this.value;
            var deptDiv = document.getElementById('editDepartmentDiv');
            var progDiv = document.getElementById('editProgramDiv');
            var deptSelect = document.getElementById('edit_department');
            var progSelect = document.getElementById('edit_program');
            if (role === 'Faculty' || role === 'Program Head') {
                deptDiv.style.display = '';
                progDiv.style.display = '';
                deptSelect.required = true;
                progSelect.required = true;
            } else if (role === 'Staff' || role === 'Head Staff' || role === 'HR') {
                deptDiv.style.display = '';
                progDiv.style.display = 'none';
                deptSelect.required = true;
                progSelect.required = false;
                progSelect.value = '';
            } else {
                deptDiv.style.display = 'none';
                progDiv.style.display = 'none';
                deptSelect.required = false;
                progSelect.required = false;
                deptSelect.value = '';
                progSelect.value = '';
            }
        });

        // On modal open, trigger the change event to set correct visibility/required state
        document.getElementById('edit_role').dispatchEvent(new Event('change'));
    </script>
    <!-- Reset Password Modal -->
    <div id="resetModal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
        <div class="modal-bg absolute inset-0"></div>
        <div class="bg-white rounded-xl shadow-lg p-8 w-full max-w-md z-10">
            <h3 class="text-lg font-bold mb-4 text-[#467C4F]">Reset Password</h3>
            <form id="resetPasswordForm" method="post" class="flex flex-col gap-3">
                <input type="hidden" name="reset_password" value="1">
                <input type="hidden" name="reset_id" id="reset_id">
                <div class="relative">
                    <input type="password" name="reset_new_password" id="reset_new_password" placeholder="New Password"
                        class="border rounded px-2 py-1 w-full" required>
                    <button type="button" onclick="toggleShowPassword()"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 text-xs">
                        <i id="showPassIcon" class="fa fa-eye"></i>
                    </button>
                </div>
                <div class="flex justify-between items-center">
                    <button type="button" onclick="generatePassword()"
                        class="text-[#2563eb] text-sm hover:underline">Reset to Default</button>
                    <div class="flex gap-2">
                        <button type="button" onclick="closeResetModal()"
                            class="px-3 py-1 rounded bg-gray-200 hover:bg-gray-300">Cancel</button>
                        <button type="submit"
                            class="px-3 py-1 rounded bg-[#f59e42] text-white hover:bg-[#c97a13]">Reset</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <script>
        function toggleShowPassword() {
            var input = document.getElementById('reset_new_password');
            var icon = document.getElementById('showPassIcon');
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = "password";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
    <!-- Notifications -->
    <div id="notification" class="fixed top-6 right-6 z-50 hidden px-6 py-3 rounded shadow-lg font-semibold"></div>
    <script>
        // Modal logic
        function showAddModal() { document.getElementById('addModal').classList.remove('hidden'); }
        function closeAddModal() { document.getElementById('addModal').classList.add('hidden'); }
        function showEditModal(id, first, middle, last, user, type, role, department = '', program = '', user_id = '') {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_user_id').value = user_id;
            document.getElementById('edit_first_name').value = first;
            document.getElementById('edit_middle_name').value = middle;
            document.getElementById('edit_last_name').value = last;
            document.getElementById('edit_userName').value = user;
            document.getElementById('edit_userType').value = type;
            document.getElementById('edit_role').value = role;

            // Department/Program fields
            var deptDiv = document.getElementById('editDepartmentDiv');
            var progDiv = document.getElementById('editProgramDiv');
            var deptSelect = document.getElementById('edit_department');
            var progSelect = document.getElementById('edit_program');

            if (role === 'Faculty' || role === 'Program Head') {
                deptDiv.style.display = '';
                progDiv.style.display = '';
                // Set department
                if (department) {
                    for (var i = 0; i < deptSelect.options.length; i++) {
                        if (deptSelect.options[i].value === department) {
                            deptSelect.selectedIndex = i;
                            break;
                        }
                    }
                } else {
                    deptSelect.selectedIndex = 0;
                }
                // Set program
                if (program) {
                    for (var i = 0; i < progSelect.options.length; i++) {
                        if (progSelect.options[i].value === program) {
                            progSelect.selectedIndex = i;
                            break;
                        }
                    }
                } else {
                    progSelect.selectedIndex = 0;
                }
            } else {
                deptDiv.style.display = 'none';
                progDiv.style.display = 'none';
                deptSelect.selectedIndex = 0;
                progSelect.selectedIndex = 0;
            }

            document.getElementById('editModal').classList.remove('hidden');
        }
        function closeEditModal() { document.getElementById('editModal').classList.add('hidden'); }
        function showResetModal(id) {
            document.getElementById('reset_id').value = id;
            document.getElementById('reset_new_password').value = '';
            document.getElementById('resetModal').classList.remove('hidden');
        }
        function closeResetModal() { document.getElementById('resetModal').classList.add('hidden'); }
        function showToggleModal(id, status, type) {
            document.getElementById('toggle_id').value = id;
            document.getElementById('toggle_current_status').value = status;
            document.getElementById('toggle_userType').value = type;
            document.getElementById('toggleStatusMsg').innerText =
                (status === 'Active'
                    ? "Are you sure you want to mark this user as Inactive?"
                    : "Are you sure you want to mark this user as Active?");
            document.getElementById('toggleModal').classList.remove('hidden');
        }
        function closeToggleModal() { document.getElementById('toggleModal').classList.add('hidden'); }

        // Password generator
        function generatePassword() {
            // Set to special value to trigger default password reset in PHP
            document.getElementById('reset_new_password').value = 'TCM2025CAP';
        }

        // Sorting
        function sortTable(col) {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', col);
            url.searchParams.set('order', url.searchParams.get('order') === 'asc' ? 'desc' : 'asc');
            window.location = url.toString();
        }

        // Notification
        function showNotification(msg, type = 'success') {
            const n = document.getElementById('notification');
            n.innerText = msg;
            n.className = "fixed top-6 right-6 z-50 px-6 py-3 rounded shadow-lg font-semibold " +
                (type === 'success' ? 'bg-[#d1fae5] text-[#10b981]' : 'bg-[#fde2e2] text-[#b91c1c]');
            n.style.display = 'block';
            setTimeout(() => { n.style.display = 'none'; }, 2500);
        }

        function toggleEditStatusFields() {
            var userType = document.getElementById('edit_userType').value;
            var deptDiv = document.getElementById('editDepartmentDiv');
            var progDiv = document.getElementById('editProgramDiv');
            if (userType === 'Regular' || userType === 'Admin') {
                deptDiv.style.display = '';
                progDiv.style.display = '';
            } else {
                deptDiv.style.display = 'none';
                progDiv.style.display = 'none';
            }
        }

        document.getElementById('add_role').addEventListener('change', function () {
            var role = this.value;
            var deptDiv = document.getElementById('addDepartmentDiv');
            var progDiv = document.getElementById('addProgramDiv');
            var deptSelect = document.getElementById('add_department');
            var progSelect = document.getElementById('add_program');
            if (role === 'Staff' || role === 'Head Staff' || role === 'HR') {
                deptDiv.style.display = '';
                progDiv.style.display = 'none';
                deptSelect.required = true;
                progSelect.required = false;
                progSelect.value = '';
            } else {
                deptDiv.style.display = '';
                progDiv.style.display = '';
                deptSelect.required = true;
                progSelect.required = true;
            }
        });
        document.getElementById('edit_userType').addEventListener('change', toggleEditStatusFields);
    </script>
    <div id="toggleModal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
        <div class="modal-bg absolute inset-0"></div>
        <div class="bg-white rounded-xl shadow-lg p-8 w-full max-w-md z-10">
            <h3 class="text-lg font-bold mb-4 text-[#467C4F]">Change Status</h3>
            <form method="post" class="flex flex-col gap-3">
                <input type="hidden" name="toggle_status" value="1">
                <input type="hidden" name="toggle_id" id="toggle_id">
                <input type="hidden" name="toggle_current_status" id="toggle_current_status">
                <input type="hidden" name="toggle_userType" id="toggle_userType">
                <div id="toggleStatusMsg" class="mb-4 text-gray-700"></div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeToggleModal()"
                        class="px-3 py-1 rounded bg-gray-200 hover:bg-gray-300">Cancel</button>
                    <button type="submit"
                        class="px-3 py-1 rounded bg-[#10b981] text-white hover:bg-[#059669]">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>