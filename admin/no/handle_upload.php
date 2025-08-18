<?php
require 'vendor/autoload.php'; // 引入 PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\IOFactory;

// 设置响应类型为 JSON
header('Content-Type: application/json');

// 连接数据库
include('../includes/common.php');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 判断文件是否上传
if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Excel 文件上传失败']);
    exit;
}

// 判断分类是否有效
$category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
if ($category_id <= 0) {
    echo json_encode(['success' => false, 'message' => '请选择有效的题库分类']);
    exit;
}

try {
    // 加载 Excel 文件
    $spreadsheet = IOFactory::load($_FILES['excel_file']['tmp_name']);
    $sheet = $spreadsheet->getActiveSheet();
    $data = $sheet->toArray();

    $inserted = 0;
    foreach ($data as $index => $row) {
        if ($index === 0) continue; // 跳过标题行

        // 提取每列
        list($type, $question, $a, $b, $c, $d, $answer, $explanation) = array_pad($row, 8, '');

        // 跳过空题
        if (empty(trim($question))) continue;

        // 填空题不需要选项
        if (strpos($type, '填空') !== false) {
            $a = $b = $c = $d = '';
        }

        // 插入数据库
        $stmt = $pdo->prepare("INSERT INTO questions 
            (type, question, option_a, option_b, option_c, option_d, answer, explanation, category_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([$type, $question, $a, $b, $c, $d, $answer, $explanation, $category_id]);
        $inserted++;
    }

    echo json_encode(['success' => true, 'message' => "成功导入 {$inserted} 道题"]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '导入过程中发生错误: ' . $e->getMessage()]);
}
