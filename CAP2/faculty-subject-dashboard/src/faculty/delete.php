<?php
// delete.php

require_once '../db.php';

if (isset($_GET['id'])) {
    $faculty_id = $_GET['id'];

    // Prepare and execute the delete statement
    $stmt = $conn->prepare("DELETE FROM faculty WHERE id = :id");
    $stmt->bindParam(':id', $faculty_id);

    if ($stmt->execute()) {
        header("Location: list.php?success=Faculty member deleted successfully.");
        exit;
    } else {
        header("Location: list.php?error=Failed to delete faculty member.");
        exit;
    }
} else {
    header("Location: list.php?error=Invalid request.");
    exit;
}
?>