<?php
function getDatabaseConnection() {
    $host = '127.0.0.1';
    $port = '3307';
    $dbname = 'bdpension';
    $username = 'root';
    $password = '';

    try {
        $db = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        die("Erreur de connexion : " . $e->getMessage());
    }
}
?>