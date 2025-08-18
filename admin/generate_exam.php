<?php
require_once 'auth_check.php';
include('../includes/common.php');
$categories = $pdo->query("SELECT id, name FROM question_categories")->fetchAll(PDO::FETCH_ASSOC);

$errors = [];
$exam = [];
$totalScore = 0;
$examLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $catId = intval($_POST['category_id']);
    $num_single = intval($_POST['num_single']);
    $score_single = floatval($_POST['score_single']);
    $num_multi = intval($_POST['num_multi']);
    $score_multi = floatval($_POST['score_multi']);
    $num_judge = intval($_POST['num_judge']);
    $score_judge = floatval($_POST['score_judge']);
    $num_fill = intval($_POST['num_fill']);
    $score_fill = floatval($_POST['score_fill']);

    $totalScore = 
        $num_single * $score_single +
        $num_multi * $score_multi +
        $num_judge * $score_judge +
        $num_fill * $score_fill;

    if (abs($totalScore - 100) > 0.001) {
        $errors[] = "所有题目总分必须加起来等于100分，现在总分为: $totalScore";
    } else {
        function fetchQuestions($pdo, $category_id, $type, $limit) {
            $stmt = $pdo->prepare("SELECT * FROM questions WHERE category_id = ? AND type = ? ORDER BY RAND() LIMIT ?");
            $stmt->bindValue(1, $category_id, PDO::PARAM_INT);
            $stmt->bindValue(2, $type, PDO::PARAM_STR);
            $stmt->bindValue(3, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $exam = [];
        if ($num_single > 0) {
            $singleQuestions = fetchQuestions($pdo, $catId, '单选题', $num_single);
            foreach ($singleQuestions as $q) {
                $q['score'] = $score_single;
                $exam[] = $q;
            }
        }
        if ($num_multi > 0) {
            $multiQuestions = fetchQuestions($pdo, $catId, '多选题', $num_multi);
            foreach ($multiQuestions as $q) {
                $q['score'] = $score_multi;
                $exam[] = $q;
            }
        }
        if ($num_judge > 0) {
            $judgeQuestions = fetchQuestions($pdo, $catId, '判断题', $num_judge);
            foreach ($judgeQuestions as $q) {
                $q['score'] = $score_judge;
                $exam[] = $q;
            }
        }
        if ($num_fill > 0) {
            $fillQuestions = fetchQuestions($pdo, $catId, '填空题', $num_fill);
            foreach ($fillQuestions as $q) {
                $q['score'] = $score_fill;
                $exam[] = $q;
            }
        }

        $examJson = json_encode($exam, JSON_UNESCAPED_UNICODE);
        $stmt = $pdo->prepare("INSERT INTO exams (category_id, exam_data) VALUES (?, ?)");
        $stmt->execute([$catId, $examJson]);
        $examId = $pdo->lastInsertId();

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $examLink = $protocol . $host . "/exam.php?id=" . $examId;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>生成考试卷系统</title>
  <?php include('head.php');?>
</head>
<body>

<div class="container">
  <hr>
  <div class="layui-card"style="margin-left: 20px; margin-right: 20px;">
    <!--<div class="layui-card-header" style="margin-right: 20px;font-weight: bold;">试卷生成设置</div>-->
      <blockquote class="layui-elem-quote">试卷生成设置</blockquote>
    <div class="layui-card-body"style="margin-right: 20px;">
      <?php if (!empty($errors)): ?>
      <script>layer.msg('<?php  foreach ($errors as $err) echo htmlspecialchars($err) . "<br>"; ?>',{icon:2})</script>
        <!--<div class="layui-bg-red layui-padding layui-text-white" style="margin-bottom: 15px;margin-right: 20px;">-->
        <!--    <strong>错误：</strong><br>-->
        <!--    <?php foreach ($errors as $err) echo htmlspecialchars($err) . "<br>"; ?>-->
        <!--</div>-->
      <?php endif; ?>
      
      




      <form class="layui-form" method="POST">
        <div class="layui-form-item" style="display: flex; align-items: center;">
          <label class="layui-form-label">题库分类</label>
          <div style="flex-grow: 1; max-width: 300px;">
            <select name="category_id" required>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= (isset($catId) && $catId == $cat['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($cat['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        
        <!-- 单选题 -->
        <div class="layui-form-item">
          <label class="layui-form-label">单选题数量</label>
          <div class="layui-input-inline">
            <input type="number" name="num_single" value="<?= $_POST['num_single'] ?? 0 ?>" min="0" class="layui-input">
          </div>
          <label class="layui-form-label">每题分数</label>
          <div class="layui-input-inline">
            <input type="number" step="0.1" name="score_single" value="<?= $_POST['score_single'] ?? 0 ?>" min="0" class="layui-input">
          </div>
        </div>
        
        <!-- 多选题 -->
        <div class="layui-form-item">
          <label class="layui-form-label">多选题数量</label>
          <div class="layui-input-inline">
            <input type="number" name="num_multi" value="<?= $_POST['num_multi'] ?? 0 ?>" min="0" class="layui-input">
          </div>
          <label class="layui-form-label">每题分数</label>
          <div class="layui-input-inline">
            <input type="number" step="0.1" name="score_multi" value="<?= $_POST['score_multi'] ?? 0 ?>" min="0" class="layui-input">
          </div>
        </div>
        
        <!-- 判断题 -->
        <div class="layui-form-item">
          <label class="layui-form-label">判断题数量</label>
          <div class="layui-input-inline">
            <input type="number" name="num_judge" value="<?= $_POST['num_judge'] ?? 0 ?>" min="0" class="layui-input">
          </div>
          <label class="layui-form-label">每题分数</label>
          <div class="layui-input-inline">
            <input type="number" step="0.1" name="score_judge" value="<?= $_POST['score_judge'] ?? 0 ?>" min="0" class="layui-input">
          </div>
        </div>
        
        <!-- 填空题 -->
        <div class="layui-form-item">
          <label class="layui-form-label">填空题数量</label>
          <div class="layui-input-inline">
            <input type="number" name="num_fill" value="<?= $_POST['num_fill'] ?? 0 ?>" min="0" class="layui-input">
          </div>
          <label class="layui-form-label">每题分数</label>
          <div class="layui-input-inline">
            <input type="number" step="0.1" name="score_fill" value="<?= $_POST['score_fill'] ?? 0 ?>" min="0" class="layui-input">
          </div>
        </div>

        <!-- 总分展示 -->
        <div class="layui-form-item">
          <label class="layui-form-label">试卷总分</label>
          <div class="layui-input-inline">
            <input type="text" id="total_score" readonly class="layui-input layui-bg-gray">
          </div>
        </div>

        <div class="layui-form-item">
          <div class="layui-input-block">
            <button class="layui-btn layui-btn-normal" type="submit">生成试卷</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
layui.use(['form', 'layer'], function(){
  var form = layui.form;
  var layer = layui.layer;


  function calcTotalScore() {
    let total = 0;

    const fields = [
      {num: 'num_single', score: 'score_single'},
      {num: 'num_multi',  score: 'score_multi'},
      {num: 'num_judge',  score: 'score_judge'},
      {num: 'num_fill',   score: 'score_fill'}
    ];

    fields.forEach(field => {
      let num = parseFloat($(`input[name="${field.num}"]`).val()) || 0;
      let score = parseFloat($(`input[name="${field.score}"]`).val()) || 0;
      total += num * score;
    });

    $('#total_score').val(total.toFixed(2));
  }

  $('input').on('input blur', function(){
    calcTotalScore();
  });

  calcTotalScore(); // 页面加载后先计算一次
  form.render();
});
function copyLink(link) {
  navigator.clipboard.writeText(link).then(function() {
    layer.msg("链接已复制到剪贴板");
  }, function(err) {
    layer.msg("复制失败，请手动复制");
  });
}

</script>
<?php if ($examLink): ?>
<script>
layui.use(['layer'], function(){
  var layer = layui.layer;
  var examLink = "<?= htmlspecialchars($examLink, ENT_QUOTES) ?>";

  layer.open({
    title: '考试已生成',
    content: `
      <div id="examLinkText" style="word-break: break-all; user-select: text; margin-bottom: 10px;">${examLink}</div>
      <div style="text-align:center; margin: 20px 0;">
        <button class="layui-btn layui-btn-xs" onclick="window.open('${examLink}', '_blank')">打开考试</button>
        <button class="layui-btn layui-btn-xs" id="copyBtn">复制考试链接</button>
      </div>
      <div id="copyTip" style="color: #4caf50; font-size: 12px; text-align: center; height: 18px; margin-top: 5px;"></div>
    `,
    closeBtn: 0,
    zIndex: 19891014,
    success: function(layero){
      layer.setTop(layero);
      var copyTip = layero.find('#copyTip');

      layero.find('#copyBtn').on('click', function(event){
        event.preventDefault();
        event.stopPropagation();

        navigator.clipboard.writeText(examLink).then(function(){
          // 复制成功，显示提示文字
          copyTip.text('链接已复制到剪贴板');
        }, function(){
          // 复制失败，显示失败提示
          copyTip.text('复制失败，请手动复制');
          copyTip.css('color', '#f44336');
        });
      });
    }
  });
});


</script>
<?php endif; ?>

</body>
</html>
