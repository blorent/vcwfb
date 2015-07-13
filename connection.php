<?php
$servername = "mysql5-1.perso"; // voir hébergeur
$username = "lorent_db"; // vide ou "root" en local
$password = "vaSqt2FG"; // vide en local
$dbname = "lorent_db"; // nom de la BD

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 
echo "Connected successfully";
?>