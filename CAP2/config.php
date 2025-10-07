<?php 

$localhost = 'localhost';
$user = 'root';
$password = '';
$database = 'evaluation_system';

// Procedural connection
$conn = mysqli_connect($localhost, $user, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

