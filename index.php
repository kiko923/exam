<?php
session_start();

// 按你的实际情况改这两个常量
define('INSTALL_LOCK', __DIR__ . '/install/install.lock'); // 锁文件路径
define('INSTALL_URL',  '/install/');            // 安装页面地址（或 '/install/'）

// 未安装：跳到安装页面
if (!is_file(INSTALL_LOCK)) {
    header('Location: ' . INSTALL_URL, true, 302);
    exit;
}

// 已安装：按会话跳转后台
$loggedIn = !empty($_SESSION['admin_logged_in']); // true/1 都算已登录
$target   = $loggedIn ? '/admin/' : '/admin/login.php';

header('Location: ' . $target, true, 302);
exit;
