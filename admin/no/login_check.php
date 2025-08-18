<?php
session_start();
include('../includes/common.php');
// echo SYSTEM_ROOT.'/config.php';


$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$captcha  = $_POST['captcha'] ?? '';

if (strtolower($captcha) !== strtolower($_SESSION['captcha_code'] ?? '')) {
    echo json_encode(['code' => 1, 'msg' => '验证码错误']);
    exit;
}

$password = md5($password);
$stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? AND password = ?");
$stmt->execute([$username, $password]);
$user = $stmt->fetch();

if ($user) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_user'] = $username;
    echo json_encode(['code' => 0, 'msg' => '登录成功']);
} else {
    echo json_encode(['code' => 1, 'msg' => '用户名或密码错误']);
}
