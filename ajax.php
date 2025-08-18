<?php
include('includes/common.php');
header('Content-Type: application/json; charset=utf-8');
// session_start();
session_start();
// 检查是否需要登录验证（除了登录和验证码接口外）
// $no_auth_actions = ['login', 'captcha', 'get_login_url', 'get_wecaht_login_url'];
$action = isset($_REQUEST['act']) ? $_REQUEST['act'] : '';

// if (!in_array($action, $no_auth_actions) && $action != '') {
//     // 检查用户是否已登录
//     if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//         // header('Location: ./'); // 跳转到当前目录首页
//         echo json_encode([
//             'code' => 401,
//             'success' => false,
//             'message' => '未登录或会话已过期，请重新登录'
//         ],448);
//         exit; // 终止脚本执行，确保不会继续执行后续代码
//     }
// }

// 根据act参数执行不同的功能
switch ($action) {
    // 获取分类列表
    case 'save_answer':
        $examId = isset($_POST['examId']) ? intval($_POST['examId']) : 0;
        $qid = $_POST['qid'] ?? '';
        $answer = $_POST['answer'] ?? null;
        
        if (!$examId || !$qid) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'msg' => '参数错误'],448);
            exit;
        }
        
        // 多选题答案为JSON字符串，需解码
        if (is_string($answer) && (substr($answer, 0, 1) === '[' || substr($answer, 0, 1) === '{')) {
            $decoded = json_decode($answer, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $answer = $decoded;
            }
        }
        
        if (!isset($_SESSION['exam_answers'][$examId])) {
            $_SESSION['exam_answers'][$examId] = [];
        }
        
        $_SESSION['exam_answers'][$examId][$qid] = $answer;
        echo json_encode(['status' => 'ok'],448);
        break;
    default:
        echo json_encode([
            'code' => 0,
            'success' => false,
            'message' => '未知操作'
        ],448);
}