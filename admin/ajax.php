<?php
include('function.php');
/**
 * 统一API处理文件
 * 通过act参数来确定执行哪个功能
 */
// 引入PhpSpreadsheet库（用于Excel处理）
use PhpOffice\PhpSpreadsheet\IOFactory;

include('../includes/common.php');
header('Content-Type: application/json; charset=utf-8');
session_start();

// 检查是否需要登录验证（除了登录和验证码接口外）
$no_auth_actions = ['login', 'captcha', 'get_login_url', 'get_wecaht_login_url','get_gt_code'];
$action = isset($_REQUEST['act']) ? $_REQUEST['act'] : '';

if (!in_array($action, $no_auth_actions) && $action != '') {
    // 检查用户是否已登录
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        // header('Location: ./'); // 跳转到当前目录首页
        echo json_encode([
            'code' => 401,
            'success' => false,
            'message' => '未登录或会话已过期，请重新登录'
        ],448);
        exit; // 终止脚本执行，确保不会继续执行后续代码
    }
}

// 根据act参数执行不同的功能
switch ($action) {
    // 获取分类列表
    case 'get_gt_code':
        $url = 'https://demos.geetest.com/gt/register-fullpage?t=' . (time().'123');
        $referer = 'https://demos.geetest.com/fullpage.html';
        $data = get_curl($url, 0, $referer);
        $arr = json_decode($data, true);
    
        if ($arr && isset($arr['gt']) && isset($arr['challenge'])) {
            echo json_encode([
                'gt'        => $arr['gt'],
                'challenge' => $arr['challenge'],
                'success'   => 1
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            echo json_encode([
                'gt'        => '',
                'challenge' => '',
                'success'   => 0,
                'msg'       => '获取 challenge 失败'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        break;
    case 'get_categories':
        $stmt = $pdo->query("SELECT id, name FROM question_categories ORDER BY id ASC");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($categories,448);
        break;
        
    // 获取题目列表
    case 'get_questions':
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
                ],448);
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
            ],448);
        } catch (Exception $e) {
            // 发生错误时返回错误信息
            echo json_encode([
                'code' => 1,
                'msg' => $e->getMessage(),
                'count' => 0,
                'data' => []
            ],448);
        }
        break;
        
    // 删除题目
    case 'delete_questions':
        // 检查是否为POST请求
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => '请求方法不正确'],448);
            exit;
        }

        // 获取要删除的ID
        $ids = isset($_POST['ids']) ? $_POST['ids'] : '';

        // 验证ID
        if (empty($ids)) {
            echo json_encode(['success' => false, 'message' => '未提供要删除的题目ID'],448);
            exit;
        }

        try {
            // 将逗号分隔的ID字符串转换为数组
            $idArray = explode(',', $ids);
            
            // 过滤非法ID
            $idArray = array_filter(array_map('intval', $idArray));
            
            if (empty($idArray)) {
                echo json_encode(['success' => false, 'message' => '无有效的题目ID'],448);
                exit;
            }
            
            // 构建占位符
            $placeholders = implode(',', array_fill(0, count($idArray), '?'));
            
            // 执行删除
            $stmt = $pdo->prepare("DELETE FROM questions WHERE id IN ($placeholders)");
            $stmt->execute($idArray);
            
            $deletedCount = $stmt->rowCount();
            
            echo json_encode([
                'success' => true,
                'message' => "成功删除题目",
                'deleted' => $deletedCount
            ],448);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '删除失败: ' . $e->getMessage()],448);
        }
        break;
        
    // 新增分类
    case 'new_category':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                echo json_encode(['success'=>false, 'message'=>'分类名称不能为空'],448);
                exit;
            }

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM question_categories WHERE name = ?");
            $stmt->execute([$name]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['success'=>false, 'message'=>'该分类名称已存在'],448);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO question_categories (name) VALUES (?)");
            if ($stmt->execute([$name])) {
                echo json_encode(['success'=>true, 'message'=>'分类添加成功'],448);
            } else {
                echo json_encode(['success'=>false, 'message'=>'添加失败，请重试'],448);
            }
        } else {
            echo json_encode(['success'=>false, 'message'=>'请求方式错误'],448);
        }
        break;
        
    // 删除分类
    case 'delete_category':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = intval($_POST['id'] ?? 0);
            $force = intval($_POST['force'] ?? 0); // 是否强制删除（连题目一起删）

            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => '分类ID无效'],448);
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
                ],448);
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
                echo json_encode(['success' => true, 'message' => '分类及相关题目已删除'],448);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => '删除失败，请重试'],448);
            }
        } else {
            echo json_encode(['success' => false, 'message' => '请求方式错误'],448);
        }
        break;
        
    case 'login':
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $captcha  = $_POST['captcha'] ?? '';
    
        if (!isset($_SESSION['captcha_code']) || strtolower($captcha) !== strtolower($_SESSION['captcha_code'])) {
            echo json_encode(['code' => 1, 'msg' => '验证码错误'], 448);
            exit;
        }
    
        $passwordHashed = md5($password);
        // 👇 加上 is_admin
        $stmt = $pdo->prepare("SELECT id, username, password, enabled, is_admin FROM admin_users WHERE username = ? AND password = ?");
        $stmt->execute([$username, $passwordHashed]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$user) {
            echo json_encode(['code' => 1, 'msg' => '用户名或密码错误'], 448);
            exit;
        }
        if ((int)$user['enabled'] === 0) {
            echo json_encode(['code' => 403, 'msg' => '账号已被禁用，请联系管理员'], 448);
            exit;
        }
    
        // 登录成功
        // session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user']      = $user['username'];
        $_SESSION['admin_id']        = (int)$user['id'];
        $_SESSION['admin_enabled']   = (int)$user['enabled'];
        $_SESSION['admin_is_admin']  = (int)$user['is_admin'];   // 👈 关键：是否管理员
    
        echo json_encode(['code' => 0, 'msg' => '登录成功'], 448);
        break;


        
    // 退出登录
    // 退出登录
    case 'logout':
        session_destroy(); // 清除 session
        header('Location: ./'); // 跳转到当前目录首页
        exit; // 终止脚本执行，确保不会继续执行后续代码
        break;
        
        // 处理Excel上传
    case 'handle_upload':
        require '../includes/vendor/autoload.php'; // 引入 PhpSpreadsheet
    
        header('Content-Type: application/json; charset=utf-8');
    
        // 1) 校验上传
        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Excel 文件上传失败'], 448);
            break;
        }
    
        // 2) 校验分类
        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        if ($category_id <= 0) {
            echo json_encode(['success' => false, 'message' => '请选择有效的题库分类'], 448);
            break;
        }
    
        try {
            // 3) 读取 Excel
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['excel_file']['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();
    
            $inserted = 0;
            foreach ($data as $index => $row) {
                if ($index === 0) continue; // 跳过标题行
    
                // 约定列：type | question | A | B | C | D | answer | explanation | image(可选)
                // 兼容老模板：如果没有第9列，image留空
                // 使用 array_pad 统一长度到9列
                list($type, $question, $a, $b, $c, $d, $answer, $explanation, $image) = array_pad($row, 9, '');
    
                // 去除首尾空格，避免脏数据
                $type = trim((string)$type);
                $question = trim((string)$question);
                $a = trim((string)$a);
                $b = trim((string)$b);
                $c = trim((string)$c);
                $d = trim((string)$d);
                $answer = trim((string)$answer);
                $explanation = trim((string)$explanation);
                $image = trim((string)$image);
    
                // 跳过空题
                if ($question === '') continue;
    
                // 填空题不需要选项
                if (mb_strpos($type, '填空') !== false) {
                    $a = $b = $c = $d = '';
                }
    
                // 可选：简单校验 image 是否像 URL（不严格，仅防误填）
                if ($image !== '' && !preg_match('#^https?://#i', $image)) {
                    // 如果你更希望严格报错，可改为 continue / 记录错误
                    // 这里选择忽略非法URL，置空
                    $image = '';
                }
    
                // 4) 插入数据库，包含 image 字段
                $stmt = $pdo->prepare("INSERT INTO questions 
                    (type, question, option_a, option_b, option_c, option_d, answer, explanation, image, category_id) 
                    VALUES (:type, :question, :a, :b, :c, :d, :answer, :explanation, :image, :category_id)");
    
                $stmt->execute([
                    ':type' => $type,
                    ':question' => $question,
                    ':a' => $a,
                    ':b' => $b,
                    ':c' => $c,
                    ':d' => $d,
                    ':answer' => $answer,
                    ':explanation' => $explanation,
                    ':image' => $image, // 新增：图片URL
                    ':category_id' => $category_id
                ]);
    
                $inserted++;
            }
    
            echo json_encode(['success' => true, 'message' => "成功导入 {$inserted} 道题"], 448);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '导入过程中发生错误: ' . $e->getMessage()], 448);
        }
    break;
        
    // 获取考试列表
    case 'get_exams':
        // 获取分页参数
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
            $sql = "SELECT e.*, 
                           c.name as category_name, 
                           e.student_name,
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
            ],448);
        } catch (Exception $e) {
            echo json_encode([
                'code' => 1,
                'msg' => $e->getMessage(),
                'count' => 0,
                'data' => []
            ],448);
        }
        break;
        
    // 删除考试
    case 'delete_exams':
        // 检查是否为POST请求
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => '请求方法不正确'],448);
            exit;
        }

        // 获取要删除的ID
        $ids = isset($_POST['ids']) ? $_POST['ids'] : '';

        // 验证ID
        if (empty($ids)) {
            echo json_encode(['success' => false, 'message' => '未提供要删除的考试ID'],448);
            exit;
        }

        try {
            // 将逗号分隔的ID字符串转换为数组
            $idArray = explode(',', $ids);
            
            // 过滤非法ID
            $idArray = array_filter(array_map('intval', $idArray));
            
            if (empty($idArray)) {
                echo json_encode(['success' => false, 'message' => '无有效的考试ID'],448);
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
                'message' => "成功删除考试",
                'deleted' => $deletedCount
            ],448);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '删除失败: ' . $e->getMessage()],448);
        }
        break;
        
    // 生成考试
    // case 'generate_exam':
    //     if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    //         echo json_encode(['success' => false, 'message' => '请求方法不正确'],448);
    //         exit;
    //     }

    //     // 获取POST参数
    //     $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    //     $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    //     $single_choice_count = isset($_POST['single_choice_count']) ? intval($_POST['single_choice_count']) : 0;
    //     $multiple_choice_count = isset($_POST['multiple_choice_count']) ? intval($_POST['multiple_choice_count']) : 0;
    //     $fill_blank_count = isset($_POST['fill_blank_count']) ? intval($_POST['fill_blank_count']) : 0;
    //     $judge_count = isset($_POST['judge_count']) ? intval($_POST['judge_count']) : 0;
    //     $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 60;
    //     $pass_score = isset($_POST['pass_score']) ? intval($_POST['pass_score']) : 60;

    //     // 验证参数
    //     if (empty($title)) {
    //         echo json_encode(['success' => false, 'message' => '考试标题不能为空'],448);
    //         exit;
    //     }

    //     if ($category_id <= 0) {
    //         echo json_encode(['success' => false, 'message' => '请选择有效的题库分类'],448);
    //         exit;
    //     }

    //     $total_questions = $single_choice_count + $multiple_choice_count + $fill_blank_count + $judge_count;
    //     if ($total_questions <= 0) {
    //         echo json_encode(['success' => false, 'message' => '至少需要一道题目'],448);
    //         exit;
    //     }

    //     try {
    //         // 随机选择题目
    //         $questions = [];
            
    //         // 单选题
    //         if ($single_choice_count > 0) {
    //             $stmt = $pdo->prepare("SELECT * FROM questions WHERE category_id = ? AND type = '单选题' ORDER BY RAND() LIMIT ?");
    //             $stmt->bindValue(1, $category_id, PDO::PARAM_INT);
    //             $stmt->bindValue(2, $single_choice_count, PDO::PARAM_INT);
    //             $stmt->execute();
    //             $single_choice_questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //             $questions = array_merge($questions, $single_choice_questions);
    //         }
            
    //         // 多选题
    //         if ($multiple_choice_count > 0) {
    //             $stmt = $pdo->prepare("SELECT * FROM questions WHERE category_id = ? AND type = '多选题' ORDER BY RAND() LIMIT ?");
    //             $stmt->bindValue(1, $category_id, PDO::PARAM_INT);
    //             $stmt->bindValue(2, $multiple_choice_count, PDO::PARAM_INT);
    //             $stmt->execute();
    //             $multiple_choice_questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //             $questions = array_merge($questions, $multiple_choice_questions);
    //         }
            
    //         // 填空题
    //         if ($fill_blank_count > 0) {
    //             $stmt = $pdo->prepare("SELECT * FROM questions WHERE category_id = ? AND type = '填空题' ORDER BY RAND() LIMIT ?");
    //             $stmt->bindValue(1, $category_id, PDO::PARAM_INT);
    //             $stmt->bindValue(2, $fill_blank_count, PDO::PARAM_INT);
    //             $stmt->execute();
    //             $fill_blank_questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //             $questions = array_merge($questions, $fill_blank_questions);
    //         }
            
    //         // 判断题
    //         if ($judge_count > 0) {
    //             $stmt = $pdo->prepare("SELECT * FROM questions WHERE category_id = ? AND type = '判断题' ORDER BY RAND() LIMIT ?");
    //             $stmt->bindValue(1, $category_id, PDO::PARAM_INT);
    //             $stmt->bindValue(2, $judge_count, PDO::PARAM_INT);
    //             $stmt->execute();
    //             $judge_questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //             $questions = array_merge($questions, $judge_questions);
    //         }
            
    //         // 检查是否获取到足够的题目
    //         $actual_count = count($questions);
    //         if ($actual_count < $total_questions) {
    //             echo json_encode(['success' => false, 'message' => "题库中题目不足，需要{$total_questions}道题，只找到{$actual_count}道"]);
    //             exit;
    //         }
            
    //         // 保存考试信息
    //         $stmt = $pdo->prepare("INSERT INTO exams (title, category_id, questions, duration, pass_score, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    //         $stmt->execute([
    //             $title,
    //             $category_id,
    //             json_encode($questions),
    //             $duration,
    //             $pass_score
    //         ]);
            
    //         echo json_encode(['success' => true, 'message' => '考试生成成功'],448);
    //     } catch (Exception $e) {
    //         echo json_encode(['success' => false, 'message' => '生成考试失败: ' . $e->getMessage()],448);
    //     }
    //     break;
    case 'generate':
        header('Content-Type: application/json; charset=utf-8');
        try {
            // 读取参数
            $catId        = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
            $num_single   = isset($_POST['num_single'])  ? intval($_POST['num_single'])  : 0;
            $score_single = isset($_POST['score_single'])? floatval($_POST['score_single']) : 0;
            $num_multi    = isset($_POST['num_multi'])   ? intval($_POST['num_multi'])   : 0;
            $score_multi  = isset($_POST['score_multi']) ? floatval($_POST['score_multi']) : 0;
            $num_judge    = isset($_POST['num_judge'])   ? intval($_POST['num_judge'])   : 0;
            $score_judge  = isset($_POST['score_judge']) ? floatval($_POST['score_judge']) : 0;
            $num_fill     = isset($_POST['num_fill'])    ? intval($_POST['num_fill'])    : 0;
            $score_fill   = isset($_POST['score_fill'])  ? floatval($_POST['score_fill']) : 0;
    
            $student_name = isset($_POST['student_name']) ? trim($_POST['student_name']) : '';
            if ($student_name !== '') {
                $student_name = mb_substr($student_name, 0, 100, 'UTF-8');
            } else {
                $student_name = null; // 允许为 NULL
            }
    
            // 计算总分
            $totalScore = $num_single * $score_single
                        + $num_multi  * $score_multi
                        + $num_judge  * $score_judge
                        + $num_fill   * $score_fill;
    
            if (abs($totalScore - 100) > 0.001) {
                echo json_encode([
                    'success' => false,
                    'message' => "所有题目总分必须加起来等于100分，现在总分为: {$totalScore}"
                ],448);
                break;
            }
    
            // 取题函数
            $fetchQuestions = function($pdo, $category_id, $type, $limit) {
                if ($limit <= 0) return [];
                $stmt = $pdo->prepare("SELECT * FROM questions WHERE category_id = ? AND type = ? ORDER BY RAND() LIMIT ?");
                $stmt->bindValue(1, $category_id, PDO::PARAM_INT);
                $stmt->bindValue(2, $type, PDO::PARAM_STR);
                $stmt->bindValue(3, $limit, PDO::PARAM_INT);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            };
    
            // 组卷
            $exam = [];
            if ($num_single > 0) {
                foreach ($fetchQuestions($pdo, $catId, '单选题', $num_single) as $q) {
                    $q['score'] = $score_single;
                    $exam[] = $q;
                }
            }
            if ($num_multi > 0) {
                foreach ($fetchQuestions($pdo, $catId, '多选题', $num_multi) as $q) {
                    $q['score'] = $score_multi;
                    $exam[] = $q;
                }
            }
            if ($num_judge > 0) {
                foreach ($fetchQuestions($pdo, $catId, '判断题', $num_judge) as $q) {
                    $q['score'] = $score_judge;
                    $exam[] = $q;
                }
            }
            if ($num_fill > 0) {
                foreach ($fetchQuestions($pdo, $catId, '填空题', $num_fill) as $q) {
                    $q['score'] = $score_fill;
                    $exam[] = $q;
                }
            }
    
            $examJson = json_encode($exam, JSON_UNESCAPED_UNICODE);
    
            // 写入 exams（包含 student_name）
            $stmt = $pdo->prepare("INSERT INTO exams (category_id, exam_data, student_name) VALUES (?, ?, ?)");
            $stmt->execute([$catId, $examJson, $student_name]);
            $examId = $pdo->lastInsertId();
    
            // 生成链接
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $examLink = $protocol . $host . "/exam.php?id=" . $examId;
    
            echo json_encode([
                'success'       => true,
                'exam_id'       => $examId,
                'exam_link'     => $examLink,
                'student_name'  => $student_name,
                'total_score'   => $totalScore
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    break;

    // 获取考试记录
    case 'exam_records':
        // 获取分页参数
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
        $offset = ($page - 1) * $limit;

        try {
            // 查询总数
            $stmt = $pdo->query("SELECT COUNT(*) FROM exam_records");
            $total = $stmt->fetchColumn();
            
            // 查询分页数据
            $stmt = $pdo->prepare("
                SELECT r.*, e.title as exam_title 
                FROM exam_records r
                LEFT JOIN exams e ON r.exam_id = e.id
                ORDER BY r.id DESC LIMIT ? OFFSET ?
            ");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->bindValue(2, $offset, PDO::PARAM_INT);
            $stmt->execute();
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 返回符合Layui表格要求的JSON格式
            echo json_encode([
                'code' => 0,
                'msg' => '',
                'count' => $total,
                'data' => $records
            ],448);
        } catch (Exception $e) {
            // 发生错误时返回错误信息
            echo json_encode([
                'code' => 1,
                'msg' => $e->getMessage(),
                'count' => 0,
                'data' => []
            ],448);
        }
        break;
        
        // 验证码生成
    case 'captcha':
            $width = 1220;
            $height = 380;
            $image = imagecreatetruecolor($width, $height);
        
            // 开启抗锯齿
            imageantialias($image, true);
        
            // 背景和文字颜色
            $bg_color   = imagecolorallocate($image, 255, 255, 255); // 白色
            $text_color = imagecolorallocate($image, 0, 0, 0);       // 黑色
            $shadow     = imagecolorallocate($image, 120, 120, 120); // 阴影/描边
        
            // 填充背景
            imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);
        
            // 随机验证码
            $code = substr(str_shuffle("0123456789"), 0, 4);
            $_SESSION['captcha_code'] = $code;
        
            // 字体文件路径
            $font = '../assets/font/微软雅黑.ttf';//微软vista黑体.ttf
            if (!file_exists($font)) {
                die('Font file not found!');
            }
        
            // 自适应字体大小（高度的 70% 左右）
            $fontSize = intval($height * 0.9);
        
            // 计算文字总宽度（大致估算）
            $bbox = imagettfbbox($fontSize, 0, $font, $code);
            $text_width  = $bbox[2] - $bbox[0];
            $text_height = $bbox[1] - $bbox[7];
        
            // 居中计算
            $x = ($width - $text_width) / 2;
            $y = ($height + $text_height) / 2;
        
            // 加粗/描边效果（提高清晰度）
            $offsets = [[-1,0],[1,0],[0,-1],[0,1]];
            foreach ($offsets as $o) {
                imagettftext($image, $fontSize, 0, $x + $o[0], $y + $o[1], $shadow, $font, $code);
            }
        
            // 绘制文字
            imagettftext($image, $fontSize, 0, $x, $y, $text_color, $font, $code);
        
            header('Content-Type: image/png');
            imagepng($image);
            imagedestroy($image);
            exit;
        break;
        
    // 保存/更新题目
    case 'save_question':
        // 检查是否为POST请求
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => '请求方法不正确'],448);
            exit;
        }

        try {
            // 获取表单数据
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $type = isset($_POST['type']) ? trim($_POST['type']) : '';
            $question = isset($_POST['question']) ? trim($_POST['question']) : '';
            $option_a = isset($_POST['option_a']) ? trim($_POST['option_a']) : '';
            $option_b = isset($_POST['option_b']) ? trim($_POST['option_b']) : '';
            $option_c = isset($_POST['option_c']) ? trim($_POST['option_c']) : '';
            $option_d = isset($_POST['option_d']) ? trim($_POST['option_d']) : '';
            $answer = isset($_POST['answer']) ? trim($_POST['answer']) : '';
            $explanation = isset($_POST['explanation']) ? trim($_POST['explanation']) : '';
            $image = isset($_POST['image']) ? trim($_POST['image']) : '';
            // 验证必填字段
            if (empty($question) || empty($type) || empty($answer)) {
                echo json_encode(['success' => false, 'message' => '题目内容、类型和答案不能为空'],448);
                exit;
            }
            
            // 如果是填空题，清空选项
            if ($type === '填空题') {
                $option_a = $option_b = $option_c = $option_d = '';
            }
            
            // 更新数据库
            if ($id > 0) {
                // 更新现有题目
                $stmt = $pdo->prepare("UPDATE questions SET 
                    type = ?, 
                    question = ?, 
                    option_a = ?, 
                    option_b = ?, 
                    option_c = ?, 
                    option_d = ?, 
                    answer = ?, 
                    explanation = ?, 
                    image = ?
                    WHERE id = ?");
                
                $stmt->execute([
                    $type, $question, $option_a, $option_b, $option_c, $option_d, $answer, $explanation,$image ,$id
                ]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => '题目已成功更新'],448);
                } else {
                    echo json_encode(['success' => false, 'message' => '题目未更改或不存在'],448);
                }
            } else {
                // 新增题目（需要分类ID）
                $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
                
                if ($category_id <= 0) {
                    echo json_encode(['success' => false, 'message' => '请选择有效的题库分类'],448);
                    exit;
                }
                
                $stmt = $pdo->prepare("INSERT INTO questions 
                    (type, question, option_a, option_b, option_c, option_d, answer, explanation,image ,category_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $type, $question, $option_a, $option_b, $option_c, $option_d, $answer, $explanation, $image, $category_id
                ]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => '题目已成功添加', 'id' => $pdo->lastInsertId()]);
                } else {
                    echo json_encode(['success' => false, 'message' => '题目添加失败']);
                }
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '操作失败: ' . $e->getMessage()],448);
        }
        break;
        
    case 'get_bind_status':
        $admin_id = $_SESSION['admin_id'] ?? null;
        if (!$admin_id) {
            echo json_encode(['code' => 1, 'msg' => '管理员ID缺失'],448);
            exit;
        }
        
        // 查询当前管理员绑定的微信 social_uid
        $stmt = $pdo->prepare("SELECT social_uid FROM admin_users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $admin_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $social_uid = $row['social_uid'] ?? '';
            echo json_encode(['code' => 0, 'social_uid' => $social_uid],448);
        } else {
            echo json_encode(['code' => 1, 'msg' => '管理员信息不存在'],448);
        }
        break;
    case 'bind_wechat_action':
        $admin_id = $_SESSION['admin_id'];
        
        // 假设登录微信时你已经把微信的 social_uid 保存到 session 中了
        // 例如：$_SESSION['wx_social_uid']
        // 这里直接用它来绑定
        if ($_POST['action'] === 'bind') {
            $social_uid = $_SESSION['wx_social_uid'] ?? null;
            if (!$social_uid) {
                echo json_encode(['code' => 1, 'msg' => '当前微信登录信息缺失，无法绑定'],448);
                exit;
            }
        
            // 检查该微信号是否被其他管理员绑定
            $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE social_uid = :uid AND id != :id LIMIT 1");
            $stmt->execute([':uid' => $social_uid, ':id' => $admin_id]);
            if ($stmt->fetch()) {
                echo json_encode(['code' => 1, 'msg' => '该微信账号已被其他管理员绑定'],448);
                exit;
            }
        
            // 执行绑定
            $stmt = $pdo->prepare("UPDATE admin_users SET social_uid = :uid WHERE id = :id");
            $res = $stmt->execute([':uid' => $social_uid, ':id' => $admin_id]);
        
            if ($res) {
                echo json_encode(['code' => 0, 'msg' => '绑定成功'],448);
            } else {
                echo json_encode(['code' => 1, 'msg' => '绑定失败'],448);
            }
            exit;
        }
        
        if ($_POST['action'] === 'unbind') {
            $stmt = $pdo->prepare("UPDATE admin_users SET social_uid = NULL WHERE id = :id");
            $res = $stmt->execute([':id' => $admin_id]);
            if ($res) {
                echo json_encode(['code' => 0, 'msg' => '解绑成功'],448);
            } else {
                echo json_encode(['code' => 1, 'msg' => '解绑失败'],448);
            }
            exit;
        }
        
        echo json_encode(['code' => 1, 'msg' => '非法请求'],448);
        break;
    case 'get_wecaht_login_url':
        $appid = '1001';
        $appkey = '2e9565167d1d6898348b6866cb30b717';
        $type = 'wx';
        
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' 
                    || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $redirect_uri = $protocol . $host . '/callback.php?mode=bind';
        $redirect_uri_encoded = urlencode($redirect_uri);
        
        $api = "https://login.znnu.com/connect.php?act=login&appid={$appid}&appkey={$appkey}&type={$type}&redirect_uri={$redirect_uri_encoded}";
        
        $response = file_get_contents($api);
        $data = json_decode($response, true);
        
        if (!isset($data['code']) || $data['code'] != 0 || empty($data['url'])) {
            echo json_encode(['code' => 1, 'msg' => '获取登录地址失败，请稍后再试'],448);
            exit;
        }
        
        echo json_encode(['code' => 0, 'url' => $data['url']]);
        break;
    case 'get_login_url':
        // Step1：从接口获取登录跳转地址
        $appid = '1001';
        $appkey = '2e9565167d1d6898348b6866cb30b717';
        $type = 'wx'; // 微信登录
        
        // 自动获取当前协议
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' 
                    || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        
        // 当前域名（包含端口号如果有）
        $host = $_SERVER['HTTP_HOST'];
        
        // 拼接回调地址（根目录下的 social_callback.php）
        $redirect_uri = $protocol . $host . '/callback.php?mode=login';
        
        // URL编码
        $redirect_uri_encoded = urlencode($redirect_uri);
        
        // 测试输出
        // echo $redirect_uri;
        // echo $redirect_uri_encoded;
        
        
        
        $api = "https://login.znnu.com/connect.php?act=login&appid={$appid}&appkey={$appkey}&type={$type}&redirect_uri={$redirect_uri}";
        
        // 获取返回的 JSON
        $response = file_get_contents($api);
        $data = json_decode($response, true);
        // echo json_encode($data,448);exit;
        if (!isset($data['code']) || $data['code'] != 0 || empty($data['url'])) {
            exit('获取登录地址失败，请稍后再试');
        }
        
        // 跳转到返回的url字段
        // header("Location: " . $data['url']);
        echo json_encode(['code'=>0,'url' => $data['url']],448);
        exit;
        break;
    case 'change_password':

        
        if (!isset($_SESSION['admin_user'])) {
            echo json_encode(['code'=>1, 'msg'=>'未登录'],448);
            exit;
        }
        
        // 用 $_POST 获取参数
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (!$oldPassword || !$newPassword || !$confirmPassword) {
            echo json_encode(['code'=>1, 'msg'=>'请填写所有字段'],448);
            exit;
        }
        
        if ($newPassword !== $confirmPassword) {
            echo json_encode(['code'=>1, 'msg'=>'新密码和确认密码不一致'],448);
            exit;
        }
        
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        
            $stmt = $pdo->prepare("SELECT password FROM admin_users WHERE username = ?");
            $stmt->execute([$_SESSION['admin_user']]);
            $row = $stmt->fetch();
        
            if (!$row) {
                echo json_encode(['code'=>1, 'msg'=>'用户不存在'],448);
                exit;
            }
            if ($row['password'] !== md5($oldPassword)) {
                echo json_encode(['code'=>1, 'msg'=>'旧密码错误'],448);
                exit;
            }
        
            $newPwdHash = md5($newPassword);
            $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE username = ?");
            $stmt->execute([$newPwdHash, $_SESSION['admin_user']]);
        
            echo json_encode(['code'=>0, 'msg'=>'密码修改成功'],448);
        } catch (PDOException $e) {
            echo json_encode(['code'=>1, 'msg'=>'数据库错误: '.$e->getMessage()],448);
        }
        break;
    case 'create_user':
        if (!isset($_SESSION['admin_user']) || $_SESSION['admin_user'] !== 'admin') {
            echo json_encode(['code' => 1, 'msg' => '无权限操作'],448);
            exit;
        }
        
        $username = trim($_POST['username'] ?? '');
        $password = isset($_POST['password']) && trim($_POST['password']) !== '' 
            ? trim($_POST['password']) 
            : '123456';
        
        if ($username === '') {
            echo json_encode(['code' => 1, 'msg' => '用户名不能为空'],448);
            exit;
        }
        
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        
            // 检查用户名是否已存在
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['code' => 1, 'msg' => '用户名已存在'],448);
                exit;
            }
        
            // 插入用户
            $hashed = md5($password);
            $stmt = $pdo->prepare("INSERT INTO admin_users (username, password) VALUES (?, ?)");
            $stmt->execute([$username, $hashed]);
        
            echo json_encode(['code' => 0, 'msg' => '用户创建成功'],448);
        } catch (PDOException $e) {
            echo json_encode(['code' => 1, 'msg' => '数据库错误: ' . $e->getMessage()],448);
        }
        break;
    case 'update_student_name':
    header('Content-Type: application/json; charset=utf-8');
    try {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $student_name = isset($_POST['student_name']) ? trim($_POST['student_name']) : '';

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => '参数 id 无效'],448); exit;
        }

        // 限长保护（与前端一致）
        if ($student_name !== '') {
            // mb_substr 防止多字节截断
            $student_name = mb_substr($student_name, 0, 100, 'UTF-8');
        } else {
            // 允许清空为 NULL
            $student_name = null;
        }

        // 使用命名参数，兼容 NULL
        $sql = "UPDATE exams SET student_name = :name WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        if ($student_name === null) {
            $stmt->bindValue(':name', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':name', $student_name, PDO::PARAM_STR);
        }
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode(['success' => true],448);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()],448);
    }
        break;
    case 'get_admin_users':
        require_admin_priv(); // 👈 仅管理员
        try {
            $page   = max(1, (int)($_GET['page']  ?? $_POST['page']  ?? 1));
            $limit  = max(1, (int)($_GET['limit'] ?? $_POST['limit'] ?? 20));
            $offset = ($page - 1) * $limit;
    
            $username = trim((string)($_GET['username'] ?? $_POST['username'] ?? ''));
            $enabled  = $_GET['enabled'] ?? $_POST['enabled'] ?? '';
    
            $wheres = [];
            $params = [];
    
            if ($username !== '') {
                $wheres[] = 'username LIKE ?';
                $params[] = '%' . $username . '%';
            }
            if ($enabled !== '' && ($enabled === '0' || $enabled === '1')) {
                $wheres[] = 'enabled = ?';
                $params[] = (int)$enabled;
            }
    
            $whereSql = $wheres ? ('WHERE ' . implode(' AND ', $wheres)) : '';
    
            // 总数
            $cntSql = "SELECT COUNT(*) FROM admin_users {$whereSql}";
            $stmt = $pdo->prepare($cntSql);
            $stmt->execute($params);
            $total = (int)$stmt->fetchColumn();
    
            // 数据（👉 带上 is_admin）
            $listSql = "SELECT id, username, enabled, is_admin, created_at
                        FROM admin_users
                        {$whereSql}
                        ORDER BY id DESC
                        LIMIT ? OFFSET ?";
            $stmt = $pdo->prepare($listSql);
    
            $bindIndex = 1;
            foreach ($params as $p) $stmt->bindValue($bindIndex++, $p);
            $stmt->bindValue($bindIndex++, $limit, PDO::PARAM_INT);
            $stmt->bindValue($bindIndex++, $offset, PDO::PARAM_INT);
    
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            echo json_encode(['code'=>0,'msg'=>'','count'=>$total,'data'=>$rows],448);
        } catch (Exception $e) {
            echo json_encode(['code'=>0,'msg'=>'查询失败：'.$e->getMessage(),'count'=>0,'data'=>[]],448);
        }
        break;
    case 'save_admin_user':
        require_admin_priv(); // 👈 仅管理员
        header('Content-Type: application/json; charset=utf-8');
        try {
            $id       = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $enabled  = isset($_POST['enabled']) ? (int)$_POST['enabled'] : 1;
            $is_admin = isset($_POST['is_admin']) ? (int)$_POST['is_admin'] : 0;
    
            if ($username === '') { echo json_encode(['success'=>false,'message'=>'账号不能为空'],448); break; }
            if ($enabled !== 0 && $enabled !== 1) $enabled = 1;
            if ($is_admin !== 0 && $is_admin !== 1) $is_admin = 0;
    
            // 用户名唯一
            if ($id > 0) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE username = ? AND id <> ?");
                $stmt->execute([$username, $id]);
            } else {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE username = ?");
                $stmt->execute([$username]);
            }
            if ((int)$stmt->fetchColumn() > 0) {
                echo json_encode(['success'=>false,'message'=>'账号已存在'],448); break;
            }
    
            // admin 账号保护：不可禁用、不可取消管理员
            if ($id > 0) {
                $stmt = $pdo->prepare("SELECT username FROM admin_users WHERE id = ?");
                $stmt->execute([$id]);
                $old = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($old && $old['username'] === 'admin') {
                    $enabled = 1;
                    $is_admin = 1;
                }
            }
            if ($username === 'admin') {
                $enabled = 1;
                $is_admin = 1;
            }
    
            if ($id > 0) {
                // 编辑
                if ($password !== '') {
                    $hashed = md5($password);
                    $sql = "UPDATE admin_users SET username = ?, password = ?, enabled = ?, is_admin = ? WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$username, $hashed, $enabled, $is_admin, $id]);
                } else {
                    $sql = "UPDATE admin_users SET username = ?, enabled = ?, is_admin = ? WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$username, $enabled, $is_admin, $id]);
                }
            } else {
                // 新增（密码留空则默认 123456）
                $hashed = md5($password === '' ? '123456' : $password);
                $sql = "INSERT INTO admin_users (username, password, enabled, is_admin, created_at)
                        VALUES (?, ?, ?, ?, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$username, $hashed, $enabled, $is_admin]);
            }
    
            echo json_encode(['success'=>true],448);
        } catch (Exception $e) {
            echo json_encode(['success'=>false, 'message'=>$e->getMessage()],448);
        }
        break;
    case 'toggle_admin_user':
        require_admin_priv(); // 👈 仅管理员
        header('Content-Type: application/json; charset=utf-8');
        try {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $enabled = isset($_POST['enabled']) ? (int)$_POST['enabled'] : 1;
            if ($id <= 0) { echo json_encode(['success' => false, 'message' => '参数错误'],448); break; }
            if ($enabled !== 0 && $enabled !== 1) $enabled = 1;
    
            // admin 不可禁用
            $stmt = $pdo->prepare("SELECT username FROM admin_users WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) { echo json_encode(['success' => false, 'message' => '用户不存在'],448); break; }
            if ($row['username'] === 'admin' && $enabled == 0) {
                echo json_encode(['success' => false, 'message' => '系统管理员账号不可禁用'],448); break;
            }
    
            $stmt = $pdo->prepare("UPDATE admin_users SET enabled = ? WHERE id = ?");
            $stmt->execute([$enabled, $id]);
    
            echo json_encode(['success' => true],448);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()],448);
        }
        break;
    case 'delete_admin_users':
        require_admin_priv(); // 👈 仅管理员
        header('Content-Type: application/json; charset=utf-8');
        try {
            $idsStr = trim($_POST['ids'] ?? '');
            if ($idsStr === '') { echo json_encode(['success' => false, 'message' => '缺少参数 ids'],448); break; }
    
            $idArr = array_values(array_filter(array_map('intval', explode(',', $idsStr))));
            if (empty($idArr)) { echo json_encode(['success' => false, 'message' => '参数 ids 无效'],448); break; }
    
            // 过滤掉 admin
            $inPlace = implode(',', array_fill(0, count($idArr), '?'));
            $stmt = $pdo->prepare("SELECT id, username FROM admin_users WHERE id IN ($inPlace)");
            $stmt->execute($idArr);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            $deletables = [];
            foreach ($rows as $r) {
                if ($r['username'] !== 'admin') $deletables[] = (int)$r['id'];
            }
    
            $deleted = 0;
            if (!empty($deletables)) {
                $inDel = implode(',', array_fill(0, count($deletables), '?'));
                $stmt = $pdo->prepare("DELETE FROM admin_users WHERE id IN ($inDel)");
                $stmt->execute($deletables);
                $deleted = $stmt->rowCount();
            }
    
            echo json_encode(['success' => true, 'deleted' => $deleted],448);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()],448);
        }
        break;
    default:
        echo json_encode(['code' => 404, 'success' => false, 'message' => '未知操作'],448);
        break;
} 
