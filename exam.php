<?php
session_start();
include('includes/common.php');

$examId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$examId) {
    die("考试ID无效");
}

// 读取考试数据和完成状态
$stmt = $pdo->prepare("SELECT exam_data, is_finished FROM exams WHERE id = ?");
$stmt->execute([$examId]);
$examRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$examRow) {
    die("考试不存在或已删除");
}

$examDataJson = $examRow['exam_data'];
$isFinished = (int)$examRow['is_finished'];

$examData = json_decode($examDataJson, true);
if (!$examData) {
    die("考试数据格式错误");
}

// 初始化答题会话数据
if (!isset($_SESSION['exam_answers'])) $_SESSION['exam_answers'] = [];
if (!isset($_SESSION['exam_scores'])) $_SESSION['exam_scores'] = [];

$score = 0;
$totalScore = 0;
$showResult = false;
$resultMessage = '';
$userAnswers = $_SESSION['exam_answers'][$examId] ?? [];

// 如果考试已完成，显示结果
if ($isFinished) {
    // 从数据库中读取最后一次提交的成绩和答案
    $stmt = $pdo->prepare("SELECT score, answers FROM exam_answers WHERE exam_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$examId]);
    $latest = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($latest) {
        $score = $latest['score'];
        $userAnswers = json_decode($latest['answers'], true);
    }

    foreach ($examData as $q) {
        $totalScore += $q['score'];
    }
    $resultMessage = "<h3>考试结束！您的得分：{$score} / {$totalScore} 分</h3>";
    $showResult = true;

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 处理提交答卷
    $userAnswers = [];
    foreach ($examData as $index => $q) {
        $qid = "q{$index}";
        $userAnswers[$qid] = $_POST[$qid] ?? null;
    }

    foreach ($examData as $index => $q) {
        $qid = "q{$index}";
        $totalScore += $q['score'];
        $ua = $userAnswers[$qid];
        if ($q['type'] === '多选题') {
            $answerStr = trim($q['answer']);
            if (strpos($answerStr, ',') !== false) {
                $correctAnswers = array_map('trim', explode(',', $answerStr));
            } else {
                $correctAnswers = preg_split('//u', $answerStr, -1, PREG_SPLIT_NO_EMPTY);
            }
            $correctAnswers = array_map('strtoupper', array_map('trim', $correctAnswers));
            if (is_array($ua)) {
                $ua = array_map('strtoupper', array_map('trim', $ua));
                sort($correctAnswers);
                sort($ua);
                if ($correctAnswers === $ua) $score += $q['score'];
            }
        } else {
            if (is_string($ua) && strcasecmp(trim($ua), trim($q['answer'])) === 0) {
                $score += $q['score'];
            }
        }
    }

    $_SESSION['exam_answers'][$examId] = $userAnswers;
    $_SESSION['exam_scores'][$examId] = $score;

    // 写入答题结果数据库
    $stmt = $pdo->prepare("INSERT INTO exam_answers (exam_id, answers, score, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$examId, json_encode($userAnswers, JSON_UNESCAPED_UNICODE), $score]);

    // 标记考试为已完成
    $pdo->prepare("UPDATE exams SET is_finished = 1 WHERE id = ?")->execute([$examId]);

    $resultMessage = "<h3>考试结束！您的得分：{$score} / {$totalScore} 分</h3>";
    $showResult = true;

} else {
    // 第一次进入考试页面，计算总分
    foreach ($examData as $q) {
        $totalScore += $q['score'];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>考试页面</title>
    <?php include('admin/head.php');?>
    <style>
        body { max-width: 1000px; margin: 30px auto; }
        .question { margin-bottom: 25px; padding: 20px; border: 1px solid #eee; border-radius: 8px; background: #f9f9f9; }
        .question h4 { margin-bottom: 10px; }
        .layui-form-label { width: auto; padding-right: 10px; }
        .layui-form-item { margin-bottom: 10px; }
        .answer-correct { color: #21b978; font-weight: bold; }
        .answer-wrong { color: #e74c3c; font-weight: bold; }
        .answer-right { color: #21b978; font-weight: bold; }
        .answer-explanation { color: #2196f3; }
        .exam-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 24px; padding: 24px 28px; border: 1px solid #f0f0f0; }
        .exam-card strong { font-size: 16px; }
        .exam-card .layui-divider { margin: 12px 0; }
        .exam-card em { font-style: normal; color: #888; margin-right: 4px; }
    </style>
</head>
<body>

<?php if ($resultMessage): ?>
    <blockquote class="layui-elem-quote"><?= $resultMessage ?></blockquote>
<?php endif; ?>

<?php if ($resultMessage): ?>
<script>
layui.use(['layer'], function(){
    var layer = layui.layer;
    layer.alert("<?= addslashes($resultMessage) ?>", {
        icon: 1,
        title: '考试完成',
        shadeClose: true
    });
});
</script>
<?php endif; ?>

<?php if (!$showResult): ?>
<form method="POST" class="layui-form" lay-filter="exam-form" id="exam-form">
    <?php foreach ($examData as $index => $q): ?>
        <div class="question">
            <h4>第<?= $index + 1 ?>题（<?= $q['score'] ?>分）【<?= $q['type'] ?>】</h4>
            <p><?= nl2br(htmlspecialchars($q['question'])) ?></p>

            <?php
            $qid = "q{$index}";
            $ua = $userAnswers[$qid] ?? null;
            ?>

            <?php if ($q['type'] === '单选题'): ?>
                <?php foreach (['option_a'=>'A', 'option_b'=>'B', 'option_c'=>'C', 'option_d'=>'D'] as $field => $label): ?>
                    <input type="radio" name="<?= $qid ?>" value="<?= $label ?>" title="<?= $label ?>. <?= htmlspecialchars($q[$field]) ?>"
                    <?= ($ua === $label) ? 'checked' : '' ?> required>
                <?php endforeach; ?>

            <?php elseif ($q['type'] === '多选题'): ?>
                <?php if (!is_array($ua)) $ua = []; ?>
                <?php foreach (['option_a'=>'A', 'option_b'=>'B', 'option_c'=>'C', 'option_d'=>'D'] as $field => $label): ?>
                    <input type="checkbox" name="<?= $qid ?>[]" value="<?= $label ?>"
                        title="<?= $label ?>. <?= htmlspecialchars($q[$field]) ?>"
                        <?= in_array($label, $ua) ? 'checked' : '' ?>>
                <?php endforeach; ?>

            <?php elseif ($q['type'] === '判断题'): ?>
                <input type="radio" name="<?= $qid ?>" value="对" title="对" <?= ($ua === '对') ? 'checked' : '' ?> required>
                <input type="radio" name="<?= $qid ?>" value="错" title="错" <?= ($ua === '错') ? 'checked' : '' ?> required>

            <?php elseif ($q['type'] === '填空题'): ?>
                <input type="text" name="<?= $qid ?>" class="layui-input" value="<?= htmlspecialchars($ua ?? '') ?>" required>

            <?php else: ?>
                <input type="text" name="<?= $qid ?>" class="layui-input" value="<?= htmlspecialchars($ua ?? '') ?>" required>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="layui-form-item">
        <button type="submit" class="layui-btn layui-btn-normal">提交试卷</button>
    </div>
</form>

<script>
function saveAnswer(qid, answer) {
  $.ajax({
    url: 'ajax.php',
    type: 'POST',
    data: {
      act: 'save_answer',
      examId: <?= $examId ?>,
      qid: qid,
      answer: (typeof answer === 'object') ? JSON.stringify(answer) : answer
    },
    success: function(response) {
      // 你可以根据需要处理返回值
       console.log('保存成功', qid + ':' +answer);
    },
    error: function() {
      // 失败处理
       console.error('保存失败', qid + ':' +answer);
    }
  });
}

layui.use(['form'], function(){
    var form = layui.form;
    form.render();

    // 单选、判断
    form.on('radio()', function(data){
        var input = data.elem;
        saveAnswer(input.name, input.value);
    });

    // 多选
    form.on('checkbox()', function(data){
        var input = data.elem;
        var qid = input.name.replace(/\[\]$/, '');
        var checked = [];
        document.querySelectorAll('input[name="' + input.name + '"]:checked').forEach(function(cb) {
            checked.push(cb.value);
        });
        saveAnswer(qid, checked);
    });

    // 填空题
    document.querySelectorAll('input[type=text]').forEach(function(input) {
        input.addEventListener('blur', function() {
            saveAnswer(this.name, this.value);
        });
    });

    // 提交确认
    document.getElementById('exam-form').addEventListener('submit', function(e) {
        e.preventDefault();
        layui.layer.confirm('确定要提交试卷吗？提交后将无法更改答案。', {icon: 3, title:'确认提交'}, function(index){
            document.getElementById('exam-form').submit();
            layui.layer.close(index);
        });
    });
});
</script>

<?php else: ?>
<hr>
<h3>答题详情：</h3>
<div>
<?php foreach ($examData as $index => $q):
    $qid = "q{$index}";
    $userAns = $userAnswers[$qid] ?? null;
    if ($q['type'] === '多选题' && is_array($userAns)) {
        $userAnsStr = implode('', $userAns);
    } else {
        $userAnsStr = is_array($userAns) ? implode(", ", $userAns) : ($userAns ?? '');
    }
    $correctAnsStr = is_array($q['answer']) ? implode(", ", $q['answer']) : $q['answer'];
    // 判断对错
    $isCorrect = false;
    if ($q['type'] === '多选题') {
        $answerStr = trim($q['answer']);
        if (strpos($answerStr, ',') !== false) {
            $correctArr = array_map('trim', explode(',', $answerStr));
        } else {
            $correctArr = preg_split('//u', $answerStr, -1, PREG_SPLIT_NO_EMPTY);
        }
        $correctArr = array_map('strtoupper', array_map('trim', $correctArr));
        $userArr = is_array($userAns) ? array_map('strtoupper', array_map('trim', $userAns)) : [];
        sort($correctArr);
        sort($userArr);
        $isCorrect = ($correctArr === $userArr);
    } else {
        $isCorrect = (strcasecmp(trim($userAnsStr), trim($q['answer'])) === 0);
    }
?>
    <div class="exam-card">
        <strong>第<?= $index + 1 ?>题（<?= $q['score'] ?>分）【<?= htmlspecialchars($q['type']) ?>】</strong>
        <div class="layui-divider"></div>
        <div style="margin-bottom:10px; color:#333; font-size:15px;">
            <?= nl2br(htmlspecialchars($q['question'])) ?>
        </div>
        <?php
        // 构建选项字符串
        $optionsStr = '';
        if (isset($q['option_a'])) $optionsStr .= 'A.' . htmlspecialchars($q['option_a']) . ' ';
        if (isset($q['option_b'])) $optionsStr .= 'B.' . htmlspecialchars($q['option_b']) . ' ';
        if (isset($q['option_c'])) $optionsStr .= 'C.' . htmlspecialchars($q['option_c']) . ' ';
        if (isset($q['option_d'])) $optionsStr .= 'D.' . htmlspecialchars($q['option_d']) . ' ';
        $optionsStr = trim($optionsStr);
        if ($optionsStr) {
        ?>
        <?php if ($q['type'] !== '填空题') {?>
        <div style="margin-bottom:6px;"><em>选项：</em> <?= $optionsStr ?></div>
        <?php } ?>
        <?php } ?>
        <div style="margin-bottom:6px;"><em>您的答案：</em> <span class="<?= $isCorrect ? 'answer-right' : 'answer-wrong' ?>"><?= htmlspecialchars($userAnsStr) ?></span></div>
        <div style="margin-bottom:6px;"><em>正确答案：</em> <span class="answer-correct"><?= htmlspecialchars($q['answer']) ?></span></div>
        <div><em>解析：</em> <span class="answer-explanation"><?php echo trim($q['explanation']) !== '' ? nl2br(htmlspecialchars($q['explanation'])) : '该题目无解析'; ?></span></div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

</body>
</html>
