<?php
include('../includes/common.php');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        echo json_encode(['success'=>false, 'message'=>'分类名称不能为空']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM question_categories WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success'=>false, 'message'=>'该分类名称已存在']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO question_categories (name) VALUES (?)");
    if ($stmt->execute([$name])) {
        echo json_encode(['success'=>true, 'message'=>'分类添加成功']);
    } else {
        echo json_encode(['success'=>false, 'message'=>'添加失败，请重试']);
    }
    exit;
}

echo json_encode(['success'=>false, 'message'=>'请求方式错误']);
