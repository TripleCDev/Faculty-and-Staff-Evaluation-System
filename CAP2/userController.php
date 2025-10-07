<?php
require_once 'config.php';

function getAllUsers($conn) {
    $query = "SELECT * FROM users";
    return mysqli_query($conn, $query);
}

function createUser($conn, $username, $password, $userType) {
    $stmt = $conn->prepare("INSERT INTO users (userName, password, userType) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password, $userType);
    return $stmt->execute();
}

function updateUser($conn, $id, $username, $password, $userType) {
    $stmt = $conn->prepare("UPDATE users SET userName=?, password=?, userType=? WHERE id=?");
    $stmt->bind_param("sssi", $username, $password, $userType, $id);
    return $stmt->execute();
}

function deleteUser($conn, $id) {
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}
?>
