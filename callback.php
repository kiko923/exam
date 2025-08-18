<?php
session_start();
include('includes/common.php'); // 数据库连接

// 第三方平台配置
$appid = '1001';
$appkey = '2e9565167d1d6898348b6866cb30b717';

// 获取参数
$type = $_GET['type'] ?? '';
$code = $_GET['code'] ?? '';
$mode = $_GET['mode'] ?? ''; // login 或 bind

if (empty($type) || empty($code) || !in_array($mode, ['login', 'bind'])) {
    exit('非法访问');
}

// 请求用户信息
$api = "https://login.yzcdn.net/connect.php?act=callback&appid={$appid}&appkey={$appkey}&type={$type}&code={$code}";
$response = file_get_contents($api);
$data = json_decode($response, true);

// 检查响应数据
if (!isset($data['code']) || $data['code'] != 0 || empty($data['social_uid'])) {
    echo('授权失败，请重试，原因：'.$data['msg']);
    echo '<br><a href="/admin/#bind_wechat">返回</a>';
    exit;
}

$social_uid = $data['social_uid'];
$username = $data['username'] ?? '';

if ($mode === 'login') {
    // === 登录流程 ===
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE social_uid = :uid LIMIT 1");
    $stmt->execute([':uid' => $social_uid]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_user'] = $admin['username'];
        header("Location: /admin"); // 跳转后台首页
        exit;
    } else {
        echo "<h3>该微信账号未绑定管理员权限</h3>";
        echo "<p>请在后台绑定后再使用微信登录功能。</p>";
        echo "<p>social_uid: " . htmlspecialchars($social_uid) . "</p>";
        echo '<br><a href="/admin">返回</a>';
        exit;
    }
} elseif ($mode === 'bind') {
    // === 绑定流程 ===
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        exit('未登录管理员，无法绑定微信');
    }

    $admin_id = $_SESSION['admin_id'] ?? null;
    if (!$admin_id) {
        exit('未登录管理员，无法绑定微信');
    }

    // 检查是否已被其他管理员绑定
    $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE social_uid = :uid AND id != :id LIMIT 1");
    $stmt->execute([':uid' => $social_uid, ':id' => $admin_id]);
    if ($stmt->fetch()) {
        exit('该微信账号已被其他管理员绑定');
    }

    // 绑定当前账号
    $stmt = $pdo->prepare("UPDATE admin_users SET social_uid = :uid WHERE id = :id");
    $res = $stmt->execute([':uid' => $social_uid, ':id' => $admin_id]);

    if ($res) {
        echo '绑定成功！<br><a href="/admin/#bind_wechat">返回绑定管理</a>';
    } else {
        echo '绑定失败！';
    }
}
?>
