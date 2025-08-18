<?php
// 设置响应类型为 JSON
header('Content-Type: application/json');

// 连接数据库
include('../includes/common.php');

// 检查是否为POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '请求方法不正确']);
    exit;
}

// 获取要删除的ID
$ids = isset($_POST['ids']) ? $_POST['ids'] : '';

// 验证ID
if (empty($ids)) {
    echo json_encode(['success' => false, 'message' => '未提供要删除的记录ID']);
    exit;
}

try {
    // 将逗号分隔的ID字符串转换为数组
    $idArray = explode(',', $ids);
    
    // 过滤非法ID
    $idArray = array_filter(array_map('intval', $idArray));
    
    if (empty($idArray)) {
        echo json_encode(['success' => false, 'message' => '无有效的记录ID']);
        exit;
    }
    
    // 构建占位符
    $placeholders = implode(',', array_fill(0, count($idArray), '?'));
    
    // 执行删除
    $stmt = $pdo->prepare("DELETE FROM exams WHERE id IN ($placeholders)");
    $stmt->execute($idArray);
    
    $deletedCount = $stmt->rowCount();
    
    echo json_encode([
        'success' => true,
        'message' => "成功删除记录",
        'deleted' => $deletedCount
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '删除失败: ' . $e->getMessage()]);
} 