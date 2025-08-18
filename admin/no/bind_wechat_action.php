<?php
session_start();
include('../includes/common.php');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['code' => 1, 'msg' => '未登录']);
    exit;
}

$admin_id = $_SESSION['admin_id'];

// 假设登录微信时你已经把微信的 social_uid 保存到 session 中了
// 例如：$_SESSION['wx_social_uid']
// 这里直接用它来绑定
if ($_POST['action'] === 'bind') {
    $social_uid = $_SESSION['wx_social_uid'] ?? null;
    if (!$social_uid) {
        echo json_encode(['code' => 1, 'msg' => '当前微信登录信息缺失，无法绑定']);
        exit;
    }

    // 检查该微信号是否被其他管理员绑定
    $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE social_uid = :uid AND id != :id LIMIT 1");
    $stmt->execute([':uid' => $social_uid, ':id' => $admin_id]);
    if ($stmt->fetch()) {
        echo json_encode(['code' => 1, 'msg' => '该微信账号已被其他管理员绑定']);
        exit;
    }

    // 执行绑定
    $stmt = $pdo->prepare("UPDATE admin_users SET social_uid = :uid WHERE id = :id");
    $res = $stmt->execute([':uid' => $social_uid, ':id' => $admin_id]);

    if ($res) {
        echo json_encode(['code' => 0, 'msg' => '绑定成功']);
    } else {
        echo json_encode(['code' => 1, 'msg' => '绑定失败']);
    }
    exit;
}

if ($_POST['action'] === 'unbind') {
    $stmt = $pdo->prepare("UPDATE admin_users SET social_uid = NULL WHERE id = :id");
    $res = $stmt->execute([':id' => $admin_id]);
    if ($res) {
        echo json_encode(['code' => 0, 'msg' => '解绑成功']);
    } else {
        echo json_encode(['code' => 1, 'msg' => '解绑失败']);
    }
    exit;
}

echo json_encode(['code' => 1, 'msg' => '非法请求']);
