<?php
include('../includes/common.php');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $force = intval($_POST['force'] ?? 0); // 是否强制删除（连题目一起删）

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => '分类ID无效']);
        exit;
    }

    // 检查该分类下是否有题目
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE category_id = ?");
    $stmt->execute([$id]);
    $questionCount = $stmt->fetchColumn();

    if ($questionCount > 0 && !$force) {
        echo json_encode([
            'success' => false,
            'need_confirm' => true,
            'message' => '该分类下有题库内容，是否连同题目一起删除？'
        ]);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 如果是强制删除，则先删题目
        if ($force && $questionCount > 0) {
            $stmt = $pdo->prepare("DELETE FROM questions WHERE category_id = ?");
            $stmt->execute([$id]);
        }

        // 删除分类
        $stmt = $pdo->prepare("DELETE FROM question_categories WHERE id = ?");
        $stmt->execute([$id]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => '分类及相关题目已删除']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => '删除失败，请重试']);
    }

    exit;
}

echo json_encode(['success' => false, 'message' => '请求方式错误']);
