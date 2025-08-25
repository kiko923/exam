<?php
require_once 'auth_check.php';
if (empty($_SESSION['admin_is_admin'])) {
  exit('<h3 style="color:red;text-align:center;margin-top:50px;">无权限访问</h3>');
}

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>用户管理</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php include('head.php');?>
  <style>
    .layui-form-item .layui-input-inline { width: 100%; max-width: 400px; }
    .layui-card { margin-bottom: 15px; }
    .layui-table-tool-temp { padding-right: 120px; }
  </style>
</head>
<body>

<div class="layui-container" style="width: 100%; padding: 15px; margin-left: 20px; margin-right: 20px;">
  <blockquote class="layui-elem-quote">用户管理</blockquote>

  <!-- 筛选区 -->
  <div class="layui-card">
    <div class="layui-card-header">筛选</div>
    <div class="layui-card-body">
      <form class="layui-form" id="filterForm">
        <div class="layui-form-item">
          <div class="layui-inline">
            <label class="layui-form-label">账号</label>
            <div class="layui-input-inline" style="width: 240px;">
              <input type="text" name="username" id="username" placeholder="支持模糊查询" class="layui-input">
            </div>
          </div>
          <div class="layui-inline">
            <label class="layui-form-label">状态</label>
            <div class="layui-input-inline" style="width: 200px;">
              <select name="enabled" id="enabled">
                <option value="">全部</option>
                <option value="1">启用</option>
                <option value="0">禁用</option>
              </select>
            </div>
          </div>
          <div class="layui-inline">
            <button type="button" class="layui-btn" id="searchBtn">查询</button>
            <button type="reset" class="layui-btn layui-btn-primary" id="resetBtn">重置</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- 列表 -->
  <div class="layui-card">
    <div class="layui-card-header">用户列表</div>
    <div class="layui-card-body">
      <table id="userTable" lay-filter="userTable"></table>

      <!-- 表格工具栏 -->
      <script type="text/html" id="tableToolbar">
        <div class="layui-btn-container">
          <button class="layui-btn layui-btn-sm" lay-event="add">新增用户</button>
          <button class="layui-btn layui-btn-sm layui-btn-danger" lay-event="deleteSelected">批量删除</button>
        </div>
      </script>

      <!-- 行工具栏 -->
      <script type="text/html" id="rowToolbar">
        <div class="layui-btn-group">
          <button class="layui-btn layui-btn-xs" lay-event="edit" style="margin-right:5px;">编辑</button>
          <button class="layui-btn layui-btn-xs layui-btn-danger" lay-event="delete">删除</button>
        </div>
      </script>

      <!-- 启用开关模板 -->
      <script type="text/html" id="enabledTpl">
        <input type="checkbox"
               name="enabled"
               value="{{d.id}}"
               lay-skin="switch"
               lay-text="启用|禁用"
               lay-filter="enabledSwitch"
               {{ d.enabled == 1 ? 'checked' : '' }}>
      </script>
    </div>
  </div>
</div>

<!-- 新增/编辑弹窗 -->
<div id="editFormTpl" style="display:none; padding:20px;">
  <form class="layui-form" id="editForm" lay-filter="editForm">
    <input type="hidden" name="id" value="">
    <div class="layui-form-item">
      <label class="layui-form-label">账号</label>
      <div class="layui-input-block">
        <input type="text" name="username" required placeholder="请输入账号" autocomplete="off" class="layui-input">
      </div>
    </div>

    <div class="layui-form-item">
      <label class="layui-form-label">密码</label>
      <div class="layui-input-block">
        <input type="password" name="password" placeholder="编辑时留空则不修改" autocomplete="new-password" class="layui-input">
      </div>
    </div>

    <div class="layui-form-item">
      <label class="layui-form-label">状态</label>
      <div class="layui-input-block">
        <input type="checkbox" name="enabled" lay-skin="switch" lay-text="启用|禁用" checked>
      </div>
    </div>
    
    <div class="layui-form-item">
      <label class="layui-form-label">管理员</label>
      <div class="layui-input-block">
        <input type="checkbox" name="is_admin" lay-skin="primary" title="是否为管理员">
      </div>
    </div>

    <div class="layui-form-item">
      <div class="layui-input-block">
        <button class="layui-btn" lay-submit lay-filter="saveUser">保存</button>
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

  // 渲染表
  var userTable = table.render({
    elem: '#userTable',
    url: 'ajax.php',
    where: { act: 'get_admin_users', username:'', enabled:'' },
    toolbar: '#tableToolbar',
    defaultToolbar: ['filter', 'exports', 'print'],
    cellMinWidth: 80,
    cols: [[
      {type: 'checkbox', fixed: 'left'},
      {field: 'id', title: 'ID', sort: true, width: 50},
      {field: 'is_admin', title: '角色', width: 80, templet: function(d){
        return d.is_admin == 1
          ? '<span class="layui-badge layui-bg-blue">管理员</span>'
          : '<span class="layui-badge-rim">普通</span>';
      }},
      {field: 'username', title: '账号', minWidth: 160},
      
      {field: 'enabled', title: '状态', width: 90, templet: '#enabledTpl'},
      {field: 'created_at', title: '创建时间', width: 160, sort: true},
      {title: '操作', toolbar: '#rowToolbar', fixed: 'right', width: 180}
    ]],
    page: true,
    limit: 20,
    limits: [10, 20, 50, 100],
    height: 'full-350',
    text: { none: '暂无用户数据' }
  });

  // 查询
  $('#searchBtn').on('click', function(){
    userTable.reload({
      where: {
        act: 'get_admin_users',
        username: $('#username').val(),
        enabled: $('#enabled').val()
      },
      page: { curr: 1 }
    });
    layer.msg('查询成功',{icon:1});
  });

  // 重置
  $('#resetBtn').on('click', function(){
    setTimeout(function(){
      userTable.reload({ where: { act: 'get_admin_users', username:'', enabled:'' }, page:{ curr:1 } });
    }, 0);
  });

  // 表头工具栏
  table.on('toolbar(userTable)', function(obj){
    var checkStatus = table.checkStatus(obj.config.id);
    var rows = checkStatus.data;

    if (obj.event === 'add') {
      openEdit(); // 新增
    } else if (obj.event === 'deleteSelected') {
      if (rows.length === 0) { layer.msg('请先选择要删除的用户'); return; }
      layer.confirm('确定删除选中的 ' + rows.length + ' 个用户吗？', function(index){
        var ids = rows.map(r => r.id);
        $.post('ajax.php', { act:'delete_admin_users', ids: ids.join(',') }, function(res){
          try { if (typeof res === 'string') res = JSON.parse(res); } catch(e) {}
          if (res && res.success) {
            layer.msg('成功删除 ' + res.deleted + ' 个用户',{icon:1});
            userTable.reload();
          } else {
            layer.msg('删除失败：' + (res && res.message ? res.message : '未知错误'));
          }
        }).fail(function(){ layer.msg('网络错误，请稍后重试'); });
        layer.close(index);
      });
    }
  });

  // 行工具条
  table.on('tool(userTable)', function(obj){
    var d = obj.data;
    if (obj.event === 'edit') {
      openEdit(d); // 编辑
    } else if (obj.event === 'delete') {
      if (d.username === 'admin') { layer.msg('系统管理员账号不可删除'); return; }
      layer.confirm('确定删除该用户吗？', function(index){
        $.post('ajax.php', { act:'delete_admin_users', ids: d.id }, function(res){
          try { if (typeof res === 'string') res = JSON.parse(res); } catch(e) {}
          if (res && res.success) {
            layer.msg('删除成功',{icon:1});
            obj.del();
          } else {
            layer.msg('删除失败：' + (res && res.message ? res.message : '未知错误'));
          }
        }).fail(function(){ layer.msg('网络错误，请稍后重试'); });
        layer.close(index);
      });
    }
  });

  // 启用/禁用开关
  form.on('switch(enabledSwitch)', function(obj){
    var id = this.value;
    var enabled = obj.elem.checked ? 1 : 0;
    $.post('ajax.php', { act:'toggle_admin_user', id:id, enabled:enabled }, function(res){
      try { if (typeof res === 'string') res = JSON.parse(res); } catch(e) {}
      if(res.success){
        layer.msg('修改成功');
      }else{
        layer.msg('保存失败：' + (res && res.message ? res.message : '未知错误'));
        // 回滚 UI
        obj.elem.checked = !obj.elem.checked;
        form.render('switch');
      }
    }).fail(function(){
      layer.msg('网络错误，请稍后重试');
      obj.elem.checked = !obj.elem.checked;
      form.render('switch');
    });
  });

  // 打开新增/编辑弹窗
  function openEdit(row){
    var isEdit = !!row;
    layer.open({
      type: 1,
      title: isEdit ? '编辑用户' : '新增用户',
      area: ['520px', '420px'],
      content: $('#editFormTpl'),
      success: function(){
        // 填充表单
        form.val('editForm', {
          id: (row && row.id) || '',
          username: (row && row.username) || '',
          password: '', // 编辑时清空，留空表示不改密码
          enabled: (row ? (row.enabled == 1) : true)
        });
        // 先按行数据设置 is_admin 复选框（无 row=新增时默认不勾选）
        $('[name="is_admin"]').prop('checked', !!(row && row.is_admin == 1));
        
        // 如果是 admin 账号，强制管理员且不可改（可选的保护）
        if (row && row.username === 'admin') {
          $('[name="is_admin"]').prop('checked', true).prop('disabled', true);
        } else {
          $('[name="is_admin"]').prop('disabled', false);
        }
        
        form.render();
      }
    });
  }

  // 保存
  form.on('submit(saveUser)', function(data){
    var f = data.field;
    // 开关转换为 1/0
    f.enabled = f.enabled === 'on' ? 1 : 0;
    
    f.is_admin = f.is_admin === 'on' ? 1 : 0;
    
    $.post('ajax.php?act=save_admin_user', f, function(res){
      try { if (typeof res === 'string') res = JSON.parse(res); } catch(e) {}
      if (res && res.success) {
        layer.closeAll('page');
        layer.msg('保存成功',{icon:1});
        userTable.reload();
      } else {
        layer.msg('保存失败：' + (res && res.message ? res.message : '未知错误'));
      }
    }).fail(function(){
      layer.msg('网络错误，请稍后重试');
    });

    return false;
  });

});
</script>
</body>
</html>
