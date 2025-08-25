<?php
session_start();
if (empty($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

require_once '../includes/common.php';

$stmt = $pdo->prepare("SELECT enabled FROM admin_users WHERE username = ?");
$stmt->execute([$_SESSION['admin_user']]);
$enabled = $stmt->fetchColumn();

if ($enabled === false || (int)$enabled === 0) {
    session_destroy();
    echo "您的账号已被禁用，请联系管理员。";
    exit;
}
