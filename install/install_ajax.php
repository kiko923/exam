<?php
// 错误报告
error_reporting(E_ALL);
ini_set('display_errors', 0); // 不直接显示错误，避免影响JSON输出

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
define('INSTALL_LOCK', __DIR__ . '/install.lock');
function write_install_lock(): void {
    $content = "installed_at=" . date('Y-m-d H:i:s') . "\n";
    // 也可写入版本/环境信息
    @file_put_contents(INSTALL_LOCK, $content);
}
// 检查请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '只允许POST请求'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_POST['action'] ?? '';

function success($data = null, $message = '操作成功') {
    return json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
}

function error($message = '操作失败', $data = null) {
    return json_encode([
        'success' => false,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
}

switch ($action) {
    case 'test_db':
        $host = $_POST['host'] ?? '';
        $dbname = $_POST['dbname'] ?? '';
        $user = $_POST['user'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (!$host || !$dbname || !$user) {
            echo error('请填写完整的数据库信息');
            break;
        }
        
        try {
            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // 测试查询
            $stmt = $pdo->query("SELECT VERSION() as version");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo success(['version' => $result['version']], '数据库连接成功');
        } catch (PDOException $e) {
            echo error('数据库连接失败：' . $e->getMessage());
        }
        break;
        
    case 'install_db':
        $host = $_POST['host'] ?? '';
        $dbname = $_POST['dbname'] ?? '';
        $user = $_POST['user'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (!$host || !$dbname || !$user) {
            echo error('请填写完整的数据库信息');
            break;
        }
        
        try {
            // 连接数据库
            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // 读取SQL文件
            $sql = file_get_contents(__DIR__ . '/database.sql');
            if (!$sql) {
                echo error('无法读取数据库脚本文件');
                break;
            }
            
            // 分割SQL语句
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            // 执行SQL语句
            $pdo->beginTransaction();
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $pdo->exec($statement);
                }
            }
            
            $pdo->commit();
            
            // 创建数据库配置文件
            $config_content = "<?php
// config.php：数据库配置 & PDO连接初始化
\$host = '{$host}';
\$dbname = '{$dbname}';
\$user = '{$user}';
\$pass = '{$password}';
\$charset = 'utf8mb4';
try {
    \$pdo = new PDO(\"mysql:host=\$host;dbname=\$dbname;charset=\$charset\", \$user, \$pass);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException \$e) {
    die(\"数据库连接失败：\" . \$e->getMessage());
}";
            
            file_put_contents('../includes/database.php', $config_content);
            write_install_lock();
            echo success(null, '数据库安装成功');
            
        } catch (PDOException $e) {
            if (isset($pdo)) {
                $pdo->rollback();
            }
            echo error('数据库安装失败：' . $e->getMessage());
        } catch (Exception $e) {
            echo error('安装失败：' . $e->getMessage());
        }
        break;
        
    case 'delete_install':
        try {
            // 删除安装文件
            $files_to_delete = [
                __FILE__,
                __DIR__ . '/index.php',
                __DIR__ . '/database.sql'
            ];
            
            foreach ($files_to_delete as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
            
            // 删除install目录（如果为空）
            if (is_dir(__DIR__)) {
                $files = scandir(__DIR__);
                if (count($files) <= 2) { // 只有 . 和 ..
                    rmdir(__DIR__);
                }
            }
            
            echo success(null, '安装文件删除成功');
        } catch (Exception $e) {
            echo error('删除失败：' . $e->getMessage());
        }
        break;
        
    default:
        echo error('未知操作: ' . $action);
        break;
}

// 确保脚本结束
exit;