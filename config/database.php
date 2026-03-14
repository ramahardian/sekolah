<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "sis_sekolah";
/*
$host = "localhost";
$user = "u778324865_sekolah";
$pass = "7Iu|zc&k8";
$db = "u778324865_sekolah";
*/
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}
?>