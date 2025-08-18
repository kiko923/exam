<?php
// 设置响应类型为 JSON
header('Content-Type: application/json');

// 连接数据库
include('../includes/common.php');

// 获取分类ID
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

// 获取分页参数
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
$offset = ($page - 1) * $limit;

try {
    // 如果没有提供分类ID，返回空结果
    if ($category_id <= 0) {
        echo json_encode([
            'code' => 0,
            'msg' => '',
            'count' => 0,
            'data' => []
        ]);
        exit;
    }
    
    // 查询总数
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE category_id = ?");
    $stmt->execute([$category_id]);
    $total = $stmt->fetchColumn();
    
    // 查询分页数据
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE category_id = ? ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $category_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 返回符合Layui表格要求的JSON格式
    echo json_encode([
        'code' => 0,
        'msg' => '',
        'count' => $total,
        'data' => $questions
    ]);
} catch (Exception $e) {
    // 发生错误时返回错误信息
    echo json_encode([
        'code' => 1,
        'msg' => $e->getMessage(),
        'count' => 0,
        'data' => []
    ]);
} 