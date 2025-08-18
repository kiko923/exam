<?php
session_start();
include('../includes/common.php');

$appid = '1001';
$appkey = '2e9565167d1d6898348b6866cb30b717';

// 检查登录管理员
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    exit('未登录管理员，无法绑定微信');
}

$admin_id = $_SESSION['admin_id'] ?? null;
if (!$admin_id) {
    exit('未登录管理员，无法绑定微信');
}

// 回调参数
$code = $_GET['code'] ?? '';
$type = $_GET['type'] ?? '';

if (!$code || $type !== 'wx') {
    exit('参数错误');
}

// 请求第三方接口换取用户信息
$api = "https://login.yzcdn.net/connect.php?act=callback&appid={$appid}&appkey={$appkey}&type={$type}&code={$code}";

$response = file_get_contents($api);
$data = json_decode($response, true);

if (!isset($data['code']) || $data['code'] != 0 || empty($data['social_uid'])) {
    echo json_encode($data,448);
    exit('微信登录信息获取失败');
    
}

$social_uid = $data['social_uid'];

// 判断是否被其他管理员绑定
$stmt = $pdo->prepare("SELECT id FROM admin_users WHERE social_uid = :uid AND id != :id LIMIT 1");
$stmt->execute([':uid' => $social_uid, ':id' => $admin_id]);
if ($stmt->fetch()) {
    exit('该微信账号已被其他管理员绑定');
}

// 绑定当前管理员
$stmt = $pdo->prepare("UPDATE admin_users SET social_uid = :uid WHERE id = :id");
$res = $stmt->execute([':uid' => $social_uid, ':id' => $admin_id]);

if ($res) {
    echo '绑定成功！<br><a href="bind_wechat.php">返回绑定管理</a>';
} else {
    echo '绑定失败！';
}
