<?php
header('Content-Type: application/json');
include('../includes/common.php');

$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$date_range = isset($_GET['date_range']) ? $_GET['date_range'] : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
$offset = ($page - 1) * $limit;

try {
    $where = [];
    $params = [];

    if ($category_id > 0) {
        $where[] = "e.category_id = ?";
        $params[] = $category_id;
    }

    if (!empty($date_range)) {
        $dates = explode(' - ', $date_range);
        if (count($dates) == 2) {
            $start_date = $dates[0] . ' 00:00:00';
            $end_date = $dates[1] . ' 23:59:59';
            $where[] = "e.create_time BETWEEN ? AND ?";
            $params[] = $start_date;
            $params[] = $end_date;
        }
    }

    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    // 查询总数
    $countSql = "SELECT COUNT(*) FROM exams e $whereClause";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();

    // 查询数据
    $sql = "SELECT e.*, c.name as category_name, 
                   CONCAT('/exam.php?id=', e.id) as exam_link
            FROM exams e 
            LEFT JOIN question_categories c ON e.category_id = c.id 
            $whereClause 
            ORDER BY e.id DESC 
            LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);

    foreach ($params as $index => $value) {
        $stmt->bindValue($index + 1, $value);
    }
    $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);

    $stmt->execute();
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($exams as &$exam) {
        // 计算题目数量
        if (isset($exam['exam_data'])) {
            $examData = json_decode($exam['exam_data'], true);
            $exam['question_count'] = is_array($examData) ? count($examData) : 0;
            unset($exam['exam_data']); // 不暴露题目数据
        } else {
            $exam['question_count'] = 0;
        }

        // 查询分数
        $scoreStmt = $pdo->prepare("SELECT score FROM exam_answers WHERE exam_id = ? ORDER BY created_at DESC LIMIT 1");
        $scoreStmt->execute([$exam['id']]);
        $score = $scoreStmt->fetchColumn();

        $exam['score_display'] = ($score !== false && $score !== null) ? $score . ' 分' : '未提交';
    }

    echo json_encode([
        'code' => 0,
        'msg' => '',
        'count' => $total,
        'data' => $exams
    ]);
} catch (Exception $e) {
    echo json_encode([
        'code' => 1,
        'msg' => $e->getMessage(),
        'count' => 0,
        'data' => []
    ]);
}
