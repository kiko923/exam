<?php
// config.php：数据库配置 & PDO连接初始化
$host = 'localhost';
$dbname = 'exam';
$user = 'exam';
$pass = 'lyz599..';
$charset = 'utf8mb4';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("数据库连接失败：" . $e->getMessage());
}