<?php
require_once 'auth_check.php';
include('../includes/common.php');
$categories = $pdo->query("SELECT id, name FROM question_categories")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>题库导入</title>
  <?php include('head.php');?>
</head>
<body>


<div class="container" style="margin-left: 20px; margin-right: 20px;">
  <hr>
  <div class="layui-card" style="margin-right: 20px;">
    <!--<div class="layui-card-header" style="font-weight: bold;">上传设置</div>-->
      <blockquote class="layui-elem-quote">上传设置</blockquote>
    <div class="layui-card-body" style="padding: 30px 20px;">
      <form class="layui-form" id="uploadForm">
        <!-- 分类选择和新增按钮 -->
        <div class="layui-form-item" style="display: flex; align-items: center;">
          <label class="layui-form-label" style="flex-shrink: 0;">选择分类</label>
          <div style="flex-grow: 1; max-width: 300px;">
            <select name="category_id" id="category_id" required>
              <option value="">请选择分类</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="button" class="layui-btn" id="addCategoryBtn" style="margin-left: 10px;">
            新增类别
          </button>
          <button type="button" class="layui-btn layui-bg-red" id="delCategoryBtn" style="margin-left: 10px;">
            删除类别
          </button>
        </div>

        <!-- Excel 上传 -->
        <div class="layui-form-item">
          <label class="layui-form-label">Excel 文件</label>
          <div class="layui-input-block" style="max-width: 400px;">
            <button type="button" class="layui-btn" id="selectFileBtn">
              <i class="layui-icon layui-icon-upload"></i> 选择 Excel 文件
            </button>
            <span id="fileNameDisplay" style="margin-left: 10px;">未选择文件</span>
            <input type="file" name="excel_file" id="excel_file" accept=".xlsx,.xls" style="display: none;" />
          </div>
        </div>

        <!-- 提交按钮 -->
        <div class="layui-form-item">
          <div class="layui-input-block">
            <button type="button" class="layui-btn layui-btn-normal" id="uploadBtn">上传并导入</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
layui.use(['form', 'layer'], function(){
  var layer = layui.layer;
  var form = layui.form;

  // 选择文件按钮绑定隐藏 input
  $('#selectFileBtn').on('click', function() {
    $('#excel_file').click();
  });

  // 显示文件名
  $('#excel_file').on('change', function() {
    var file = this.files[0];
    if (file) {
      $('#fileNameDisplay').text(file.name);
    } else {
      $('#fileNameDisplay').text('未选择文件');
    }
  });

  // 新增分类按钮
  $('#addCategoryBtn').on('click', function(){
  layer.prompt({
    title: '请输入新分类名称',
    formType: 0,
    maxlength: 50,
    area: ['300px', '40px'],
    btn: ['确认', '取消'],
  }, function(value, index, elem){
    var newName = value.trim();
    if(newName === ''){
      layer.msg('分类名称不能为空',{icon:2});
      return;
    }
    $.post('ajax.php', {act: 'new_category', name: newName}, function(res){
      if(typeof res === 'string'){
        try{
          res = JSON.parse(res);
        }catch(e){
          layer.msg('服务器返回数据格式错误',{icon:2});
          return;
        }
      }
      if(res.success){
        layer.msg(res.message || '添加成功',{icon:1});
        layer.close(index);

        // 重新拉取分类列表
        $.getJSON('ajax.php', {act: 'get_categories'}, function(data){
          if(Array.isArray(data)){
            var $select = $('#category_id');
            $select.empty().append('<option value="">请选择分类</option>');
            data.forEach(function(item){
              $select.append('<option value="'+item.id+'">'+item.name+'</option>');
            });
            var addedOption = data.find(c => c.name === newName);
            if(addedOption){
              $select.val(addedOption.id);
            }
            layui.form.render('select');
          } else {
            layer.msg('分类列表刷新失败',{icon:2});
          }
        });
      } else {
        layer.msg(res.message || '添加失败，请重试',{icon:2});
      }
    }).fail(function(){
      layer.msg('网络请求失败，请稍后再试',{icon:2});
    });
  });
});

  // 删除分类按钮
  $('#delCategoryBtn').on('click', function() {
  const categoryId = $('#category_id').val();
  if (!categoryId) {
    layer.msg('请先选择要删除的分类',{icon:2});
    return;
  }

  layer.confirm('确定要删除该分类吗？', function(index){
    layer.close(index);
    sendDelete(categoryId, false); // 第一次请求，不强制删除
  });

  function sendDelete(id, forceDelete) {
    $.post('ajax.php', {
      act: 'delete_category',
      id: id,
      force: forceDelete ? 1 : 0
    }, function(res){
      if (res.need_confirm) {
        // 二次确认：是否连题目一起删除
        layer.confirm(res.message, {
          icon: 3,
          title: '提示'
        }, function(index2){
          layer.close(index2);
          sendDelete(id, true); // 用户确认后再强制删除
        });
      } else if (res.success) {
        layer.msg(res.message);
        // 刷新分类列表
        $.getJSON('ajax.php', {act: 'get_categories'}, function(data){
          if (Array.isArray(data)) {
            var $select = $('#category_id');
            $select.empty().append('<option value="">请选择分类</option>');
            data.forEach(function(item){
              $select.append('<option value="'+item.id+'">'+item.name+'</option>');
            });
            layui.form.render('select');
          }
        });
      } else {
        layer.alert(res.message || '删除失败');
      }
    }, 'json').fail(function(){
      layer.alert('请求失败，请检查网络');
    });
  }
});

  // 上传按钮事件
  $('#uploadBtn').on('click', function() {
    var categoryId = $('#category_id').val();
    var fileInput = $('#excel_file')[0].files[0];
    var load = layer.load();
    if (!categoryId) {
      layer.msg('请选择分类',{icon:2});
      return;
    }

    if (!fileInput) {
      layer.msg('请选择 Excel 文件',{icon:2});
      return;
    }

    var formData = new FormData();
    formData.append('act', 'handle_upload');
    formData.append('category_id', categoryId);
    formData.append('excel_file', fileInput);

    $.ajax({
      url: 'ajax.php',
      type: 'POST',
      data: formData,
      dataType: 'json',
      processData: false,
      contentType: false,
      success: function(res) {
          layer.close(load);
        if (res.success) {
          layer.alert(res.message || '上传成功',{icon:1});
          // 清空下拉框选中项
          $('#category_id').val(''); 
          layui.form.render('select');
          
          // 同时清空文件选择显示
          $('#excel_file').val('');
          $('#fileNameDisplay').text('未选择文件');
          
        } else {
          layer.alert('上传失败：' + (res.message || '未知错误'));
        }
      },
      error: function() {
          layer.close(load);
        layer.alert('上传失败，请检查网络或服务器问题');
      }
    });
  });

});
</script>

</body>
</html>
