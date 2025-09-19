<?php
require_once 'auth_check.php';
include('../includes/common.php');

// 获取所有分类
$categories = $pdo->query("SELECT id, name FROM question_categories")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>题库管理</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php include('head.php');?>
  <style>
    .layui-form-item .layui-input-inline {
      width: 100%;
      max-width: 400px;
    }
    .layui-card {
      margin-bottom: 15px;
    }
    .option-label {
      display: inline-block;
      width: 30px;
      text-align: right;
      margin-right: 10px;
    }
    .layui-table-tool-temp {
      padding-right: 120px;
    }
  </style>
</head>
<body>

<div class="layui-container" style="width: 100%; padding: 15px;margin-left: 20px; margin-right: 20px;">
  <blockquote class="layui-elem-quote">题库管理</blockquote>
  
  <!-- 分类选择 -->
  <div class="layui-card">
    <div class="layui-card-header">选择题库分类</div>
    <div class="layui-card-body">
      <form class="layui-form" id="categoryForm">
        <div class="layui-form-item">
          <div class="layui-input-inline">
            <select name="category_id" id="category_id" lay-filter="category_select" lay-search>
              <option value="">请选择分类</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="layui-input-inline" style="width: auto;">
            <button type="button" class="layui-btn" id="loadQuestionsBtn">加载题目</button>
          </div>
        </div>
      </form>
    </div>
  </div>
  
  <!-- 题目列表 -->
  <div class="layui-card">
    <div class="layui-card-header">题目列表</div>
    <div class="layui-card-body">
      <table id="questionTable" lay-filter="questionTable"></table>
      
      <!-- 表格工具栏 -->
      <script type="text/html" id="tableToolbar">
        <div class="layui-btn-container">
          <button class="layui-btn layui-btn-sm layui-btn-danger" lay-event="deleteSelected">批量删除</button>
        </div>
      </script>
      
      <!-- 行工具栏 -->
      <script type="text/html" id="rowToolbar">
        <div class="layui-btn-group">
          <button class="layui-btn layui-btn-sm" lay-event="edit">编辑</button>
          <button class="layui-btn layui-btn-sm layui-btn-danger" lay-event="delete">删除</button>
        </div>
      </script>
    </div>
  </div>
</div>

<!-- 编辑题目弹窗 -->
<div id="editFormTemplate" style="display: none; padding: 20px;">
  <form class="layui-form" id="editForm" lay-filter="editForm">
    <input type="hidden" name="id" value="">
    
    <div class="layui-form-item">
      <label class="layui-form-label">题目类型</label>
      <div class="layui-input-block">
        <select name="type" lay-filter="questionType">
          <option value="单选题">单选题</option>
          <option value="多选题">多选题</option>
          <option value="判断题">判断题</option>
          <option value="填空题">填空题</option>
        </select>
      </div>
    </div>
    
    <div class="layui-form-item layui-form-text">
      <label class="layui-form-label">题目内容</label>
      <div class="layui-input-block">
        <textarea name="question" placeholder="请输入题目内容" class="layui-textarea" required></textarea>
      </div>
    </div>
    
    <!--题目图片-->
    <div class="layui-form-item">
      <label class="layui-form-label">图片URL</label>
      <div class="layui-input-block">
        <input type="text" name="image" placeholder="请输入题目图片URL" class="layui-input">
      </div>
    </div>
    <!--题目图片-->
    
    <div class="layui-form-item option-group">
      <label class="layui-form-label">选项A</label>
      <div class="layui-input-block">
        <input type="text" name="option_a" placeholder="请输入选项A" class="layui-input">
      </div>
    </div>
    
    <div class="layui-form-item option-group">
      <label class="layui-form-label">选项B</label>
      <div class="layui-input-block">
        <input type="text" name="option_b" placeholder="请输入选项B" class="layui-input">
      </div>
    </div>
    
    <div class="layui-form-item option-group">
      <label class="layui-form-label">选项C</label>
      <div class="layui-input-block">
        <input type="text" name="option_c" placeholder="请输入选项C" class="layui-input">
      </div>
    </div>
    
    <div class="layui-form-item option-group">
      <label class="layui-form-label">选项D</label>
      <div class="layui-input-block">
        <input type="text" name="option_d" placeholder="请输入选项D" class="layui-input">
      </div>
    </div>
    
    <div class="layui-form-item">
      <label class="layui-form-label">正确答案</label>
      <div class="layui-input-block">
        <input type="text" name="answer" placeholder="单选题填写A/B/C/D，多选题填写如A,B,C，判断题填写正确/错误，填空题填写答案" class="layui-input" required>
      </div>
    </div>
    
    <div class="layui-form-item layui-form-text">
      <label class="layui-form-label">解析</label>
      <div class="layui-input-block">
        <textarea name="explanation" placeholder="请输入题目解析（可选）" class="layui-textarea"></textarea>
      </div>
    </div>
    
    <div class="layui-form-item">
      <div class="layui-input-block">
        <button class="layui-btn" lay-submit lay-filter="saveQuestion">保存</button>
        <button type="reset" class="layui-btn layui-btn-primary">重置</button>
      </div>
    </div>
  </form>
</div>


<script>
layui.use(['table', 'form', 'layer'], function(){
  var table = layui.table;
  var form = layui.form;
  var layer = layui.layer;
  var $ = layui.$;
  
  // 当前选中的分类ID
  var currentCategoryId = '';
  
  // 初始化表格
    var questionTable = table.render({
      elem: '#questionTable',
      url: 'ajax.php', // 后端接口
      where: {category_id: '', act: 'get_questions'},
      toolbar: '#tableToolbar',
      defaultToolbar: ['filter', 'exports', 'print'],
      cellMinWidth: 80, // 设置最小单元格宽度以防压缩过小
      cols: [[
        {type: 'checkbox', fixed: 'left'},
        {field: 'id', title: 'ID', sort: true,width: 30},
        {field: 'type', title: '题目类型',width: 100},
        {field: 'question', title: '题目内容',width: 500},
        {field: 'image', title: '题目图片', width: 120, templet: function(d){
            if(d.image){
              return '<button class="layui-btn layui-btn-xs" onclick="previewImage(\''+ d.image +'\')">预览图片</button>';
            } else {
              return '无图片';
            }
        }},
        {field: 'answer', title: '正确答案',width: 100},
        {
          field: 'options',
          title: '选项',
          templet: function(d){
            if(d.type === '填空题') return '无选项';
            return '选项数量: ' + (
              (d.option_a ? 1 : 0) + 
              (d.option_b ? 1 : 0) + 
              (d.option_c ? 1 : 0) + 
              (d.option_d ? 1 : 0)
            );
          }
        },
        {field: 'explanation', title: '解析'},
        {title: '操作', toolbar: '#rowToolbar', fixed: 'right'}
      ]],
      page: true,
      limit: 20,
      limits: [10, 20, 50, 100],
      height: 'full-350',
      text: {
        none: '无题库信息'
      }
    });

  // 加载题目按钮点击事件
  $('#loadQuestionsBtn').on('click', function(){
    var categoryId = $('#category_id').val();
    if(!categoryId){
      layer.msg('请先选择题库分类');
      return;
    }
    
    currentCategoryId = categoryId;
    layer.msg('查询成功',{icon:1});
    // 重载表格
    questionTable.reload({
      where: {
        category_id: categoryId,
        act: 'get_questions'
      },
      page: {
        curr: 1
      }
    });
  });
  
  // 监听表格工具栏事件
  table.on('toolbar(questionTable)', function(obj){
    var checkStatus = table.checkStatus(obj.config.id);
    var data = checkStatus.data;
    
    switch(obj.event){
      case 'deleteSelected':
        if(data.length === 0){
          layer.msg('请先选择要删除的题目');
          return;
        }
        
        layer.confirm('确定要删除选中的 ' + data.length + ' 道题目吗？', function(index){
          var ids = data.map(function(item){ return item.id; });
          
          // 发送删除请求
          $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: {
              act: 'delete_questions',
              ids: ids.join(',')
            },
            success: function(res){
              if(res.success){
                layer.msg('成功删除 ' + res.deleted + ' 道题目');
                // 重载表格
                questionTable.reload();
              } else {
                layer.msg('删除失败：' + res.message);
              }
            },
            error: function(){
              layer.msg('网络错误，请稍后重试');
            }
          });
          
          layer.close(index);
        });
        break;
    }
  });
  
  // 监听行工具条事件
  table.on('tool(questionTable)', function(obj){
    var data = obj.data;
    
    switch(obj.event){
      case 'edit':
        // 打开编辑弹窗
        openEditForm(data);
        break;
      case 'delete':
        layer.confirm('确定要删除这道题目吗？', function(index){
          // 发送删除请求
          $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: {
              act: 'delete_questions',
              ids: data.id
            },
            success: function(res){
              if(res.success){
                layer.msg('删除成功');
                obj.del(); // 删除对应行
              } else {
                layer.msg('删除失败：' + res.message);
              }
            },
            error: function(){
              layer.msg('网络错误，请稍后重试');
            }
          });
          
          layer.close(index);
        });
        break;
    }
  });
  
  // 打开编辑表单
  function openEditForm(data){
    var showOptions = data.type !== '填空题';
    layer.open({
      type: 1,
      title: '编辑题目',
      area: ['700px', '650px'],
      // 使用模板 HTML 字符串，避免原节点被移出并在关闭后出现在底部
      content: $('#editFormTemplate').html(),
      success: function(layero){
        var $box = $(layero);
        // 填充表单数据（按 lay-filter 定位）
        form.val('editForm', {
          'id': data.id,
          'type': data.type,
          'question': data.question,
          'option_a': data.option_a,
          'option_b': data.option_b,
          'option_c': data.option_c,
          'option_d': data.option_d,
          'answer': data.answer,
          'explanation': data.explanation,
          'image': data.image
        });
        // 控制选项显示/隐藏（作用域限定在弹层内部）
        toggleOptions($box, showOptions);
        form.render();
      }
    });
  }
  
  // 监听题目类型切换（作用域限定在当前弹层）
  form.on('select(questionType)', function(data){
    var $layer = $(data.elem).closest('.layui-layer');
    toggleOptions($layer, data.value !== '填空题');
  });
  
  // 控制选项显示/隐藏（容器作用域）
  function toggleOptions($container, show){
    var $targets = $container.find('.option-group');
    if(show){
      $targets.show();
    } else {
      $targets.hide();
    }
  }
  
  // 监听表单提交
  form.on('submit(saveQuestion)', function(data){
    var formData = data.field;
    
    // 发送保存请求
    $.ajax({
      url: 'ajax.php?act=save_question',
      type: 'POST',
      data: formData,
      success: function(res){
        if(res.success){
          layer.closeAll('page'); // 关闭弹窗
          layer.msg('保存成功');
          // 重载表格
          questionTable.reload();
        } else {
          layer.msg('保存失败：' + res.message);
        }
      },
      error: function(){
        layer.msg('网络错误，请稍后重试');
      }
    });
    
    return false; // 阻止表单默认提交
  });
});
</script>
<script>
    function previewImage(url){
  layer.open({
    type: 1,
    title: false,
    closeBtn: 1,
    area: ['80%', '80%'], // 弹窗大小
    shadeClose: true,
    content: '<div style="text-align:center;padding:10px;"><img src="'+ url +'" style="max-width:100%;max-height:100%;"></div>'
  });
}

</script>
</body>
</html> 