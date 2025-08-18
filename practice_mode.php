<?php
session_start();
include('includes/common.php');

// 初始化练习会话数据
if (!isset($_SESSION['practice_answers'])) $_SESSION['practice_answers'] = [];
if (!isset($_SESSION['practice_stats'])) $_SESSION['practice_stats'] = [];

$categoryId = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$currentPage = isset($_GET['page']) ? intval($_GET['page']) : 0;

// 处理AJAX请求
if (isset($_POST['ajax_submit']) && $_POST['ajax_submit'] == 1) {
    $questionId = intval($_POST['question_id']);
    $answer = $_POST['answer'] ?? '';
    $categoryId = intval($_POST['category_id']);
    
    // 多选题处理
    if (isset($_POST['answer']) && is_array($_POST['answer'])) {
        $answer = $_POST['answer'];
    }
    
    // 存储用户答案
    $_SESSION['practice_answers'][$categoryId][$questionId] = $answer;
    
    // 获取当前问题
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
    $stmt->execute([$questionId]);
    $question = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($question) {
        // 检查答案是否正确
        $isCorrect = false;
        
        if ($question['type'] === '多选题') {
            // 仔细处理多选题的答案比较，确保顺序、大小写无关
            $correctAnswers = array_map(function($a) {
                return strtoupper(trim($a));
            }, explode(',', $question['answer']));
            
            if (is_array($answer)) {
                // 处理用户答案，确保大小写一致
                $userAnswers = array_map(function($a) {
                    return strtoupper(trim($a));
                }, $answer);
                
                // 排序以确保顺序无关
                sort($correctAnswers);
                sort($userAnswers);
                
                // 使用array_diff检查差异
                $isCorrect = (count($correctAnswers) === count($userAnswers)) && 
                             (count(array_diff($correctAnswers, $userAnswers)) === 0) &&
                             (count(array_diff($userAnswers, $correctAnswers)) === 0);
                
                // 调试日志
                error_log("正确答案: " . implode(',', $correctAnswers));
                error_log("用户答案: " . implode(',', $userAnswers));
                error_log("判断结果: " . ($isCorrect ? "正确" : "错误"));
            }
        } else {
            // 处理单选题答案，确保是字符串
            if (is_array($answer) && count($answer) > 0) {
                $answer = $answer[0]; // 取数组第一个元素
            }
            $isCorrect = (strcasecmp(trim((string)$answer), trim($question['answer'])) === 0);
        }
        
        // 更新统计信息
        if (!isset($_SESSION['practice_stats'][$categoryId])) {
            $_SESSION['practice_stats'][$categoryId] = [
                'total' => 0,
                'correct' => 0,
                'incorrect' => 0
            ];
        }
        
        $_SESSION['practice_stats'][$categoryId]['total']++;
        if ($isCorrect) {
            $_SESSION['practice_stats'][$categoryId]['correct']++;
        } else {
            $_SESSION['practice_stats'][$categoryId]['incorrect']++;
        }
        
        // 返回结果
        $result = [
            'success' => true,
            'isCorrect' => $isCorrect,
            'correctAnswer' => $question['answer'],
            'explanation' => $question['explanation'] ?? '',
            'stats' => $_SESSION['practice_stats'][$categoryId]
        ];
        
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '题目不存在']);
    exit;
}

// 获取所有分类
$categories = $pdo->query("SELECT id, name FROM question_categories")->fetchAll(PDO::FETCH_ASSOC);

// 如果选择了分类，加载该分类的题目
$questions = [];
$totalQuestions = 0;
$currentQuestion = null;

if ($categoryId > 0) {
    // 获取总题目数
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE category_id = ?");
    $stmt->execute([$categoryId]);
    $totalQuestions = $stmt->fetchColumn();
    
    if ($totalQuestions > 0) {
        // 如果是第一次加载，随机排序题目并存入会话
        if (!isset($_SESSION['practice_questions'][$categoryId]) || 
            (isset($_GET['reset']) && $_GET['reset'] == 1)) {
            
            $stmt = $pdo->prepare("SELECT * FROM questions WHERE category_id = ? ORDER BY RAND()");
            $stmt->execute([$categoryId]);
            $_SESSION['practice_questions'][$categoryId] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 重置该分类的练习统计
            $_SESSION['practice_stats'][$categoryId] = [
                'total' => 0,
                'correct' => 0,
                'incorrect' => 0
            ];
            
            // 重置该分类的答题记录
            $_SESSION['practice_answers'][$categoryId] = [];
            
            // 重置页码
            $currentPage = 0;
        }
        
        $questions = $_SESSION['practice_questions'][$categoryId];
        
        // 确保页码在有效范围内
        if ($currentPage < 0) $currentPage = 0;
        if ($currentPage >= count($questions)) $currentPage = count($questions) - 1;
        
        // 获取当前问题
        $currentQuestion = $questions[$currentPage];

        // 检查是否所有题目已完成
        $allQuestionsCompleted = false;
        if ($totalQuestions > 0) {
            $completedCount = 0;
            foreach ($questions as $q) {
                if (isset($_SESSION['practice_answers'][$categoryId][$q['id']])) {
                    $completedCount++;
                }
            }
            $allQuestionsCompleted = ($completedCount == count($questions));
        }
    }
}

// 处理提交的答案
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_answer'])) {
    $questionId = intval($_POST['question_id']);
    $answer = $_POST['answer'] ?? '';
    $categoryId = intval($_POST['category_id']);
    $currentPage = intval($_POST['current_page']);
    
    // 多选题处理
    if (isset($_POST['answer']) && is_array($_POST['answer'])) {
        $answer = $_POST['answer'];
    }
    
    // 存储用户答案
    $_SESSION['practice_answers'][$categoryId][$questionId] = $answer;
    
    // 自动进入下一题
    if (isset($_POST['next']) && $_POST['next'] == '1') {
        $currentPage++;
    } elseif (isset($_POST['prev']) && $_POST['prev'] == '1') {
        $currentPage--;
    }
    
    // 重定向到当前页面以避免表单重提交
    header("Location: practice_mode.php?category_id=$categoryId&page=$currentPage");
    exit;
}

// 统计数据
$stats = isset($_SESSION['practice_stats'][$categoryId]) ? $_SESSION['practice_stats'][$categoryId] : [
    'total' => 0,
    'correct' => 0,
    'incorrect' => 0
];

// 计算正确率
$correctRate = $stats['total'] > 0 ? round(($stats['correct'] / $stats['total']) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>练习模式</title>
    <?php include('admin/head.php');?>
    <style>
        body { max-width: 1000px; margin: 30px auto; padding: 0 15px; }
        .question-card { margin-bottom: 25px; padding: 20px; border: 1px solid #eee; border-radius: 8px; background: #f9f9f9; }
        .question-card h4 { margin-bottom: 10px; }
        .layui-form-label { width: auto; padding-right: 10px; }
        .layui-form-item { margin-bottom: 10px; }
        .answer-correct { color: #21b978; font-weight: bold; }
        .answer-wrong { color: #e74c3c; font-weight: bold; }
        .answer-explanation { color: #2196f3; margin-top: 10px; padding: 15px; background: #e3f2fd; border-radius: 4px; border-left: 4px solid #2196f3; display: none; }
        .stats-box { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); padding: 15px; margin-bottom: 20px; }
        .stats-box .title { font-weight: bold; margin-bottom: 10px; }
        .stats-item { display: inline-block; margin-right: 20px; }
        .stats-item strong { color: #333; }
        .correct-rate { float: right; }
        .correct-rate strong { color: #21b978; }
        .navigation-buttons { margin-top: 20px; text-align: center; }
        .page-info { text-align: center; margin-bottom: 15px; color: #666; }
        .result-icon { font-size: 18px; margin-right: 5px; }
        .header-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header-nav .title { font-size: 24px; font-weight: bold; }
        .option-item { cursor: pointer; margin-bottom: 12px; padding: 10px 15px; border: 1px solid #e2e2e2; border-radius: 4px; transition: all 0.3s; }
        .option-item:hover { background-color: #f8f8f8; }
        .option-item.selected { background-color: #ddf3ff; border-color: #1E9FFF; }
        .option-item.correct { background-color: #eaffea; border-color: #5FB878; }
        .option-item.incorrect { background-color: #fff0f0; border-color: #FF5722; }
        .option-letter { display: inline-block; width: 24px; height: 24px; line-height: 24px; text-align: center; border-radius: 50%; margin-right: 8px; font-weight: bold; }
        .option-text { display: inline-block; vertical-align: middle; }
        .correct-answer { font-weight: bold; color: #5FB878; }
        #answer-result { padding: 10px; border-radius: 4px; margin: 15px 0; }
        .answered .option-item:not(.selected):not(.correct) { opacity: 0.6; }
        .answered .correct { position: relative; }
        .answered .correct::after { content: '✓'; position: absolute; right: 15px; color: #5FB878; font-weight: bold; font-size: 18px; }
    </style>
</head>
<body>

<div class="layui-container">
    <!-- 导航栏 -->
    <div class="header-nav">
        <div class="title">练习模式</div>
        <div class="nav-links">
            <a href="index.php" class="layui-btn layui-btn-primary">
                <i class="layui-icon layui-icon-home"></i> 返回首页
            </a>
        </div>
    </div>
    
    <!-- 分类选择 -->
    <div class="layui-card">
        <div class="layui-card-header">选择练习题库</div>
        <div class="layui-card-body">
            <form class="layui-form" action="practice_mode.php" method="GET">
                <div class="layui-form-item">
                    <div class="layui-input-inline" style="width: 300px;">
                        <select name="category_id" lay-filter="category_select" lay-search>
                            <option value="">请选择分类</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($categoryId == $cat['id'] ? 'selected' : '') ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="layui-input-inline" style="width: auto;">
                        <button type="submit" class="layui-btn">开始练习</button>
                        <?php if ($categoryId > 0): ?>
                        <a href="practice_mode.php?category_id=<?= $categoryId ?>&reset=1" class="layui-btn layui-btn-primary">重新开始</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <?php if ($categoryId > 0 && $totalQuestions > 0): ?>
    
    <!-- 统计信息 -->
    <div class="stats-box">
        <div class="title">练习进度</div>
        <div class="stats-item">已做题数: <strong id="total-count"><?= $stats['total'] ?></strong></div>
        <div class="stats-item">正确题数: <strong id="correct-count"><?= $stats['correct'] ?></strong></div>
        <div class="stats-item">错误题数: <strong id="incorrect-count"><?= $stats['incorrect'] ?></strong></div>
        <div class="correct-rate">正确率: <strong id="correct-rate"><?= $correctRate ?>%</strong></div>
    </div>
    
    <!-- 当前页码信息 -->
    <div class="page-info">
        题目 <?= $currentPage + 1 ?> / <?= count($questions) ?>
    </div>
    
    <!-- 问题展示 -->
    <?php if ($currentQuestion): ?>
    <div class="question-card">
        <h4>【<?= $currentQuestion['type'] ?>】</h4>
        <p><?= nl2br(htmlspecialchars($currentQuestion['question'])) ?></p>

        <?php
        $qid = $currentQuestion['id'];
        $userAnswer = $_SESSION['practice_answers'][$categoryId][$qid] ?? null;
        $answered = isset($_SESSION['practice_answers'][$categoryId][$qid]);
        
        // 判断答案是否正确
        $isCorrect = false;
        if ($answered) {
            if ($currentQuestion['type'] === '多选题') {
                $correctAnswers = array_map('trim', explode(',', $currentQuestion['answer']));
                $correctAnswers = array_map('strtoupper', $correctAnswers);
                
                if (is_array($userAnswer)) {
                    $ua = array_map('strtoupper', $userAnswer);
                    sort($correctAnswers);
                    sort($ua);
                    $isCorrect = ($correctAnswers === $ua);
                }
            } else {
                $isCorrect = (strcasecmp(trim($userAnswer), trim($currentQuestion['answer'])) === 0);
            }
        }
        ?>

        <form id="question-form" class="layui-form" action="practice_mode.php" method="POST">
            <input type="hidden" name="question_id" value="<?= $currentQuestion['id'] ?>">
            <input type="hidden" name="category_id" value="<?= $categoryId ?>">
            <input type="hidden" name="current_page" value="<?= $currentPage ?>">
            
            <div id="answer-options">
                <?php if ($currentQuestion['type'] === '单选题'): ?>
                    <?php foreach (['option_a'=>'A', 'option_b'=>'B', 'option_c'=>'C', 'option_d'=>'D'] as $field => $label): ?>
                        <?php if (trim($currentQuestion[$field]) !== ''): ?>
                            <div class="option-item single-option <?= ($userAnswer === $label) ? 'selected' : '' ?>" data-value="<?= $label ?>">
                                <span class="option-letter"><?= $label ?></span>
                                <span class="option-text"><?= htmlspecialchars($currentQuestion[$field]) ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                
                <?php elseif ($currentQuestion['type'] === '多选题'): ?>
                    <?php if (!is_array($userAnswer)) $userAnswer = []; ?>
                    <?php foreach (['option_a'=>'A', 'option_b'=>'B', 'option_c'=>'C', 'option_d'=>'D'] as $field => $label): ?>
                        <?php if (trim($currentQuestion[$field]) !== ''): ?>
                            <div class="option-item multi-option <?= (in_array($label, $userAnswer)) ? 'selected' : '' ?>" data-value="<?= $label ?>">
                                <span class="option-letter"><?= $label ?></span>
                                <span class="option-text"><?= htmlspecialchars($currentQuestion[$field]) ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                
                <?php elseif ($currentQuestion['type'] === '判断题'): ?>
                    <div class="option-item single-option <?= ($userAnswer === '对') ? 'selected' : '' ?>" data-value="对">
                        <span class="option-letter" style="background-color: #5FB878; color: white;">√</span>
                        <span class="option-text">对</span>
                    </div>
                    <div class="option-item single-option <?= ($userAnswer === '错') ? 'selected' : '' ?>" data-value="错">
                        <span class="option-letter" style="background-color: #FF5722; color: white;">×</span>
                        <span class="option-text">错</span>
                    </div>
                
                <?php elseif ($currentQuestion['type'] === '填空题' || $currentQuestion['type'] === ''): ?>
                    <div class="layui-form-item">
                        <div class="layui-input-group">
                            <input type="text" id="fill-answer" class="layui-input" value="<?= htmlspecialchars($userAnswer ?? '') ?>" placeholder="请输入答案" <?= $answered ? 'disabled' : '' ?>>
                            <div class="layui-input-suffix">
                                <button type="button" id="submit-fill" class="layui-btn layui-btn-primary" <?= $answered ? 'disabled style="cursor:not-allowed"' : '' ?>>
                                    <i class="layui-icon layui-icon-ok"></i> 提交
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div id="answer-result" style="margin-top: 15px; display: <?= $answered ? 'block' : 'none' ?>;">
                <?php if ($answered): ?>
                    <?php if ($isCorrect): ?>
                        <div class="answer-correct">
                            <i class="layui-icon layui-icon-ok result-icon"></i>回答正确
                        </div>
                    <?php else: ?>
                        <div class="answer-wrong">
                            <i class="layui-icon layui-icon-close result-icon"></i>回答错误，正确答案：<?= htmlspecialchars($currentQuestion['answer']) ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($currentQuestion['explanation'])): ?>
                <div id="explanation" class="answer-explanation" style="display: <?= $answered ? 'block' : 'none' ?>;">
                    <strong>解析：</strong> <?= nl2br(htmlspecialchars($currentQuestion['explanation'])) ?>
                </div>
            <?php endif; ?>
            
            <div class="navigation-buttons">
                <?php if ($currentPage > 0): ?>
                    <button type="submit" name="submit_answer" value="1" class="layui-btn layui-btn-primary">
                        <input type="hidden" name="prev" value="1">
                        <i class="layui-icon layui-icon-left"></i> 上一题
                    </button>
                <?php endif; ?>
                
                <?php if ($currentPage < count($questions) - 1): ?>
                    <?php if ($currentQuestion['type'] === '填空题' && !$answered): ?>
                        <button type="button" class="layui-btn layui-btn-normal" id="next-btn-disabled" disabled>
                            请先提交答案
                        </button>
                    <?php else: ?>
                        <button type="submit" name="submit_answer" value="1" class="layui-btn layui-btn-normal">
                            <input type="hidden" name="next" value="1">
                            下一题 <i class="layui-icon layui-icon-right"></i>
                        </button>
                    <?php endif; ?>
                <?php elseif ($currentPage == count($questions) - 1 && $answered): ?>
                    <!-- 最后一题，已答题，显示查看总结按钮 -->
                    <a href="#summary" class="layui-btn layui-btn-danger">
                        <i class="layui-icon layui-icon-chart-screen"></i> 查看练习总结
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- 完成提示 -->
    <?php if (isset($allQuestionsCompleted) && $allQuestionsCompleted): ?>
    <div class="layui-card" id="summary" style="margin-top: 20px;">
        <div class="layui-card-header"><i class="layui-icon layui-icon-ok-circle"></i> 练习完成</div>
        <div class="layui-card-body">
            <p>您已完成本分类的所有题目！总体正确率: <strong class="correct-answer"><?= $correctRate ?>%</strong></p>
            <div class="layui-card" style="margin-top: 15px;">
                <div class="layui-card-header">练习数据统计</div>
                <div class="layui-card-body">
                    <table class="layui-table">
                        <thead>
                            <tr>
                                <th>已做题数</th>
                                <th>正确题数</th>
                                <th>错误题数</th>
                                <th>正确率</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?= $stats['total'] ?></td>
                                <td><span style="color:#5FB878"><?= $stats['correct'] ?></span></td>
                                <td><span style="color:#FF5722"><?= $stats['incorrect'] ?></span></td>
                                <td><span style="font-weight:bold"><?= $correctRate ?>%</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div style="margin-top: 15px;">
                <a href="practice_mode.php?category_id=<?= $categoryId ?>&reset=1" class="layui-btn">
                    <i class="layui-icon layui-icon-refresh"></i> 重新练习
                </a>
                <a href="index.php" class="layui-btn layui-btn-primary">
                    <i class="layui-icon layui-icon-home"></i> 返回首页
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
        <?php if ($categoryId > 0): ?>
            <div class="layui-card">
                <div class="layui-card-body">
                    <p>该分类下暂无题目，请选择其他分类。</p>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
layui.use(['form', 'layer', 'jquery'], function(){
    var form = layui.form;
    var layer = layui.layer;
    var $ = layui.jquery;
    
    form.render();
    
    // 当选择分类时自动提交表单
    form.on('select(category_select)', function(data){
        if (data.value) {
            window.location.href = 'practice_mode.php?category_id=' + data.value;
        }
    });
    
    // 处理单选题点击
    $('.single-option').on('click', function() {
        var $this = $(this);
        if ($this.hasClass('correct') || $this.hasClass('incorrect')) {
            return; // 已经回答过了，不做处理
        }
        
        var selectedValue = $this.data('value');
        $('.single-option').removeClass('selected');
        $this.addClass('selected');
        
        // 单选题直接提交答案，无需等待用户点击提交按钮
        submitAnswer(selectedValue);
    });
    
    // 处理多选题点击
    $('.multi-option').on('click', function() {
        var $this = $(this);
        if ($('.multi-option.correct').length > 0 || $('.multi-option.incorrect').length > 0) {
            return; // 已经回答过了，不做处理
        }
        
        if ($this.hasClass('selected')) {
            $this.removeClass('selected');
        } else {
            $this.addClass('selected');
        }
    });
    
    // 多选题提交按钮（自动添加在多选题后）
    if ($('.multi-option').length > 0 && $('#multi-submit').length === 0) {
        $('#answer-options').append('<button type="button" id="multi-submit" class="layui-btn layui-btn-sm layui-btn-normal" style="margin-top: 15px;"><i class="layui-icon layui-icon-ok"></i> 提交多选答案</button>');
        
        $('#multi-submit').on('click', function() {
            if ($(this).prop('disabled')) return;
            
            var selectedValues = [];
            $('.multi-option.selected').each(function() {
                selectedValues.push($(this).data('value'));
            });
            
            if (selectedValues.length === 0) {
                layer.msg('请至少选择一个选项');
                return;
            }
            
            submitAnswer(selectedValues);
            $(this).prop('disabled', true).addClass('layui-btn-disabled');
        });
    }
    
    // 处理填空题提交
    $('#submit-fill').on('click', function() {
        var answer = $('#fill-answer').val().trim();
        if (answer) {
            submitAnswer(answer);
        } else {
            layer.msg('请输入答案');
        }
    });
    
    // 填空题回车键提交
    $('#fill-answer').on('keypress', function(e) {
        if (e.which === 13) { // 回车键
            e.preventDefault();
            var answer = $(this).val().trim();
            if (answer) {
                submitAnswer(answer);
            } else {
                layer.msg('请输入答案');
            }
        }
    });
    
    // 提交答案到服务器
    function submitAnswer(answer) {
        var questionId = $('input[name="question_id"]').val();
        var categoryId = $('input[name="category_id"]').val();
        
        $.ajax({
            url: 'practice_mode.php',
            type: 'POST',
            data: {
                ajax_submit: 1,
                question_id: questionId,
                category_id: categoryId,
                answer: answer
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // 显示结果
                    showResult(response);
                    
                    // 更新统计数据
                    updateStats(response.stats);
                } else {
                    layer.msg(response.message || '提交失败，请重试');
                }
            },
            error: function() {
                layer.msg('网络错误，请重试');
            }
        });
    }
    
    // 显示结果
    function showResult(data) {
        var resultHtml = '';
        
        if (data.isCorrect) {
            resultHtml = '<div class="answer-correct"><i class="layui-icon layui-icon-ok result-icon"></i>回答正确</div>';
        } else {
            // 对于多选题，以更友好的方式显示正确答案
            var correctAnswer = data.correctAnswer;
            if ($('.multi-option').length > 0) {
                // 将多选题的答案格式化为更友好的方式，如 A、B、C
                correctAnswer = data.correctAnswer.split(',').map(function(item) {
                    return item.trim().toUpperCase();
                }).join('、');
                
                // 找出所有正确选项对应的文字
                var answerTexts = [];
                $('.multi-option').each(function() {
                    var $option = $(this);
                    var value = $option.data('value');
                    if (data.correctAnswer.toUpperCase().indexOf(value.toUpperCase()) !== -1) {
                        answerTexts.push(value + ': ' + $option.find('.option-text').text());
                    }
                });
                
                correctAnswer += '<br><span style="font-size:13px;color:#666">(' + answerTexts.join('; ') + ')</span>';
            }
            resultHtml = '<div class="answer-wrong"><i class="layui-icon layui-icon-close result-icon"></i>回答错误，正确答案：<span class="correct-answer">' + correctAnswer + '</span></div>';
        }
        
        // 更新答案结果
        $('#answer-result').html(resultHtml).show();
        
        // 添加已回答标记
        $('#answer-options').addClass('answered');
        
        // 显示解析，如果有的话
        if (data.explanation) {
            $('#explanation').html('<strong>解析：</strong> ' + data.explanation.replace(/\n/g, '<br>')).slideDown(200);
        } else {
            $('#explanation').hide();
        }
        
        // 高亮选项
        if ($('.single-option').length > 0) {
            // 单选题或判断题
            $('.single-option').each(function() {
                var $option = $(this);
                var value = $option.data('value');
                
                if ($option.hasClass('selected')) {
                    if (value.toUpperCase() === data.correctAnswer.toUpperCase()) {
                        $option.addClass('correct');
                    } else {
                        $option.addClass('incorrect');
                    }
                } else if (value.toUpperCase() === data.correctAnswer.toUpperCase()) {
                    $option.addClass('correct');
                }
            });
        } else if ($('.multi-option').length > 0) {
            // 多选题
            var correctAnswers = data.correctAnswer.split(',').map(function(a) {
                return a.trim().toUpperCase();
            });
            
            $('.multi-option').each(function() {
                var $option = $(this);
                var value = $option.data('value').toUpperCase();
                
                if ($option.hasClass('selected')) {
                    if (correctAnswers.includes(value)) {
                        $option.addClass('correct');
                    } else {
                        $option.addClass('incorrect');
                    }
                } else if (correctAnswers.includes(value)) {
                    $option.addClass('correct');
                }
            });
        }
        
        // 禁用提交按钮
        $('#multi-submit, #submit-fill').prop('disabled', true).addClass('layui-btn-disabled');
        
        // 自动刷新页面，显示下一题按钮
        if ($('#next-btn-disabled').length > 0) {
            var url = window.location.href;
            window.location.href = url;
        }
    }
    
    // 更新统计数据
    function updateStats(stats) {
        $('#total-count').text(stats.total);
        $('#correct-count').text(stats.correct);
        $('#incorrect-count').text(stats.incorrect);
        
        var rate = stats.total > 0 ? ((stats.correct / stats.total) * 100).toFixed(1) : '0.0';
        $('#correct-rate').text(rate + '%');
    }
});
</script>
</body>
</html> 
<?php endif; ?>