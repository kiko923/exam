<?php
require_once 'auth_check.php';

// require_once 'auth_check.php';
include('../includes/common.php');
// 获取所有分类，用于筛选
$categories = $pdo->query("SELECT id, name FROM question_categories")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>考试记录管理</title>
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
    .layui-table-tool-temp {
      padding-right: 120px;
    }
  </style>
</head>
<body>

<div class="layui-container" style="width: 100%; padding: 15px;">
  <blockquote class="layui-elem-quote">考试记录管理</blockquote>
  
  <!-- 筛选条件 -->
  <div class="layui-card">
    <div class="layui-card-header">筛选条件</div>
    <div class="layui-card-body">
      <form class="layui-form" id="filterForm">
        <div class="layui-form-item">
          <div class="layui-inline">
            <label class="layui-form-label">分类筛选</label>
            <div class="layui-input-inline" style="width: 200px;">
              <select name="category_id" id="category_id" lay-filter="category_select">
                <option value="">全部分类</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="layui-inline">
            <label class="layui-form-label">日期范围</label>
            <div class="layui-input-inline" style="width: 300px;">
              <input type="text" name="date_range" id="date_range" placeholder="请选择日期范围" class="layui-input">
            </div>
          </div>
          <div class="layui-inline">
            <button type="button" class="layui-btn" id="searchBtn">查询</button>
            <button type="reset" class="layui-btn layui-btn-primary">重置</button>
          </div>
        </div>
      </form>
    </div>
  </div>
  
  <!-- 考试记录列表 -->
  <div class="layui-card">
    <div class="layui-card-header">考试记录列表</div>
    <div class="layui-card-body">
      <table id="examTable" lay-filter="examTable"></table>
      
      <!-- 表格工具栏 -->
      <script type="text/html" id="tableToolbar">
        <div class="layui-btn-container">
          <button class="layui-btn layui-btn-sm layui-btn-danger" lay-event="deleteSelected">批量删除</button>
        </div>
      </script>
      
      <!-- 行工具栏 -->
      <script type="text/html" id="rowToolbar">
        <div class="layui-btn-group">
          <button class="layui-btn layui-btn-sm" lay-event="view">查看</button>
          <button class="layui-btn layui-btn-sm layui-btn-danger" lay-event="delete">删除</button>
        </div>
      </script>
    </div>
  </div>
</div>


<script>
layui.use(['table', 'form', 'layer', 'laydate'], function(){
  var table = layui.table;
  var form = layui.form;
  var layer = layui.layer;
  var laydate = layui.laydate;
  var $ = layui.$;
  
  // 初始化日期范围选择器
  laydate.render({
    elem: '#date_range',
    range: true
  });
  
  // 初始化表格
var examTable = table.render({
  elem: '#examTable',
  url: 'ajax.php',
  where: {act: 'get_exams'},
  toolbar: '#tableToolbar',
  defaultToolbar: ['filter', 'exports', 'print'],
  cellMinWidth: 80, // 👈 增加这个
      cols: [[
      {type: 'checkbox', fixed: 'left'},
      {field: 'id', title: 'ID', sort: true,width: 30},
      {field: 'category_name', title: '分类'},
      {field: 'question_count', title: '题目数量'},
      {field: 'score_display', title: '得分', width: 100},
      {field: 'created_at', title: '创建时间', sort: true},
      {title: '操作', toolbar: '#rowToolbar', fixed: 'right'}
    ]],
  page: true,
  limit: 20,
  limits: [10, 20, 50, 100],
  height: 'full-350',
  text: {
    none: '暂无考试记录'
  }
});

  
  // 搜索按钮点击事件
  $('#searchBtn').on('click', function(){
    var categoryId = $('#category_id').val();
    var dateRange = $('#date_range').val();
    layer.msg('查询成功',{icon:1});
    // 重载表格
    examTable.reload({
      where: {
        act: 'get_exams',
        category_id: categoryId,
        date_range: dateRange
      },
      page: {
        curr: 1
      }
    });
  });
  
  // 监听表格工具栏事件
  table.on('toolbar(examTable)', function(obj){
    var checkStatus = table.checkStatus(obj.config.id);
    var data = checkStatus.data;
    
    switch(obj.event){
      case 'deleteSelected':
        if(data.length === 0){
          layer.msg('请先选择要删除的记录');
          return;
        }
        
        layer.confirm('确定要删除选中的 ' + data.length + ' 条记录吗？', function(index){
          var ids = data.map(function(item){ return item.id; });
          
          // 发送删除请求
          $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: {
              act: 'delete_exams',
              ids: ids.join(',')
            },
            success: function(res){
              if(res.success){
                layer.msg('成功删除 ' + res.deleted + ' 条记录');
                // 重载表格
                examTable.reload();
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
  table.on('tool(examTable)', function(obj){
    var data = obj.data;
    
    switch(obj.event){
      case 'view':
        // 查看考试详情
        window.open(data.exam_link, '_blank');
        break;
      case 'delete':
        layer.confirm('确定要删除这条记录吗？', function(index){
          // 发送删除请求
          $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: {
              act: 'delete_exams',
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
});
</script>

</body>
</html> 