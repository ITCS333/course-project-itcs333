<?php
function getDBConnection() {
    $host = "localhost";
    $db   = "main";
    $user = "root";
    $pass = "";

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("DB Connection Failed: " . $e->getMessage());
    }
}
?>
