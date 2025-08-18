<?php
session_start();
header('Content-Type: application/json');
include('../includes/common.php');

// 检查管理员是否登录
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['code' => 1, 'msg' => '未登录']);
    exit;
}

$admin_id = $_SESSION['admin_id'] ?? null;
if (!$admin_id) {
    echo json_encode(['code' => 1, 'msg' => '管理员ID缺失']);
    exit;
}

// 查询当前管理员绑定的微信 social_uid
$stmt = $pdo->prepare("SELECT social_uid FROM admin_users WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $admin_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $social_uid = $row['social_uid'] ?? '';
    echo json_encode(['code' => 0, 'social_uid' => $social_uid]);
} else {
    echo json_encode(['code' => 1, 'msg' => '管理员信息不存在']);
}
