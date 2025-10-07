<?php
// db.php

$localhost = "localhost";
$user = "root";
$password = "";
$database = "faculty_subject_management";

try {
    $conn = new PDO("mysql:host=$localhost;dbname=$database", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>