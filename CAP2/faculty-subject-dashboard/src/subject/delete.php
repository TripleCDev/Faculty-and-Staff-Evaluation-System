<?php
require_once '../db.php';

if (isset($_GET['id'])) {
    $subject_id = $_GET['id'];

    try {
        $stmt = $conn->prepare("DELETE FROM subjects WHERE id = :id");
        $stmt->bindParam(':id', $subject_id);
        $stmt->execute();

        header("Location: ../subject/list.php?success=subject_deleted");
        exit;
    } catch (PDOException $e) {
        header("Location: ../subject/list.php?error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: ../subject/list.php?error=invalid_request");
    exit;
}
?>