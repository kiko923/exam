<?php
$host = 'localhost';
$user = 'exam';
$pass = 'lyz599..'; // 改为你的数据库密码
$dbname = 'exam';
$charset = 'utf8mb4';

$lockFile = __DIR__ . '/install.lock';

// 如果锁文件存在，提示已安装，阻止重复安装
if (file_exists($lockFile)) {
    echo "<h2>⚠️ 系统已安装！</h2>";
    echo "<p>若要重新安装，请删除 <code>install.lock</code> 文件后再访问此页面。</p>";
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;charset=$charset", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET $charset COLLATE ${charset}_general_ci;");
    $pdo->exec("USE `$dbname`;");

    $sql = file_get_contents(__DIR__ . '/install.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement . ';');
        }
    }

    // 写入锁文件，防止重复安装
    file_put_contents($lockFile, "installed at " . date('Y-m-d H:i:s'));

    echo "<h2>✅ 安装成功！</h2><p>默认管理员账号：admin / 123456</p><p>请删除 install 文件夹或 install.lock 文件后才能重新安装！</p>";
} catch (PDOException $e) {
    echo "<h2>❌ 安装失败：</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
