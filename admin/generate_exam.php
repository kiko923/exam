<?php
require_once 'auth_check.php';
include('../includes/common.php');
$categories = $pdo->query("SELECT id, name FROM question_categories")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>生成考试卷系统</title>
  <?php include('head.php'); ?>
</head>
<body>

<div class="container">
  <hr>
  <div class="layui-card" style="margin-left: 20px; margin-right: 20px;">
    <blockquote class="layui-elem-quote">试卷生成设置</blockquote>
    <div class="layui-card-body" style="margin-right: 20px;">

      <!-- 改为 AJAX 提交，不再用原来的 PHP 同页处理 -->
      <form class="layui-form" id="generateForm">
        <div class="layui-form-item" style="display: flex; align-items: center;">
          <label class="layui-form-label">题库分类</label>
          <div style="flex-grow: 1; max-width: 300px;">
            <select name="category_id" required>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- 新增：考生姓名（选填） -->
        <div class="layui-form-item">
          <label class="layui-form-label">考生姓名</label>
          <div class="layui-input-inline" style="width: 300px;">
            <input
              type="text"
              name="student_name"
              placeholder="选填"
              maxlength="100"
              class="layui-input">
          </div>
          <!--<div class="layui-form-mid layui-word-aux">可留空</div>-->
        </div>

        <!-- 单选题 -->
        <div class="layui-form-item">
          <label class="layui-form-label">单选题数量</label>
          <div class="layui-input-inline">
            <input type="number" name="num_single" value="0" min="0" class="layui-input">
          </div>
          <label class="layui-form-label">每题分数</label>
          <div class="layui-input-inline">
            <input type="number" step="0.1" name="score_single" value="0" min="0" class="layui-input">
          </div>
        </div>

        <!-- 多选题 -->
        <div class="layui-form-item">
          <label class="layui-form-label">多选题数量</label>
          <div class="layui-input-inline">
            <input type="number" name="num_multi" value="0" min="0" class="layui-input">
          </div>
          <label class="layui-form-label">每题分数</label>
          <div class="layui-input-inline">
            <input type="number" step="0.1" name="score_multi" value="0" min="0" class="layui-input">
          </div>
        </div>

        <!-- 判断题 -->
        <div class="layui-form-item">
          <label class="layui-form-label">判断题数量</label>
          <div class="layui-input-inline">
            <input type="number" name="num_judge" value="0" min="0" class="layui-input">
          </div>
          <label class="layui-form-label">每题分数</label>
          <div class="layui-input-inline">
            <input type="number" step="0.1" name="score_judge" value="0" min="0" class="layui-input">
          </div>
        </div>

        <!-- 填空题 -->
        <div class="layui-form-item">
          <label class="layui-form-label">填空题数量</label>
          <div class="layui-input-inline">
            <input type="number" name="num_fill" value="0" min="0" class="layui-input">
          </div>
          <label class="layui-form-label">每题分数</label>
          <div class="layui-input-inline">
            <input type="number" step="0.1" name="score_fill" value="0" min="0" class="layui-input">
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
  var $ = layui.$;

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
    return total;
  }

  // 变化时计算总分
  $('input').on('input blur', function(){
    calcTotalScore();
  });
  calcTotalScore();

  // 提交（AJAX）
  $('#generateForm').on('submit', function(e){
    e.preventDefault();

    var total = calcTotalScore();
    if (Math.abs(total - 100) > 0.001) {
      layer.msg('所有题目总分必须加起来等于100分，现在总分为：' + total.toFixed(2), {icon:2});
      return;
    }

    var formData = $(this).serializeArray();
    var data = {};
    formData.forEach(function(it){ data[it.name] = it.value; });
    // data['act'] = 'generate';

    $.ajax({
      url: 'ajax.php?act=generate',
      type: 'POST',
      data: data,
      success: function(res){
        try { if (typeof res === 'string') res = JSON.parse(res); } catch(e) {}
        if (res && res.success) {
          var examLink = res.exam_link;
          var studentName = res.student_name || '';
          var nameRow = studentName ? `<div style="margin-bottom:8px;"><b>考生：</b>${layui.util.escape(studentName)}</div>` : '';

          layer.open({
            title: '考试已生成',
            content: `
              ${nameRow}
              <div id="examLinkText" style="word-break: break-all; user-select: text; margin-bottom: 10px;">
                <b>考试链接：</b>${layui.util.escape(examLink)}
              </div>
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
                  copyTip.text('链接已复制到剪贴板');
                }, function(){
                  copyTip.text('复制失败，请手动复制');
                  copyTip.css('color', '#f44336');
                });
              });
            }
          });
        } else {
          layer.msg('生成失败：' + (res && res.message ? res.message : '未知错误'), {icon:2});
        }
      },
      error: function(){
        layer.msg('网络错误，请稍后重试', {icon:2});
      }
    });
  });

  form.render();
});
</script>

</body>
</html>
