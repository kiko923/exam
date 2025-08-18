<?php
require_once 'auth_check.php';
session_start();
if (!isset($_SESSION['admin_user']) || $_SESSION['admin_user'] !== 'admin') {
  exit('<h3 style="color:red;text-align:center;margin-top:50px;">无权限访问</h3>');
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>新建管理员用户</title>
  <?php include('head.php'); ?> <!-- 引入 layui.css 和 layui.js，含 jQuery -->
</head>
<body>
<div class="container" style="margin: 20px;">
  <blockquote class="layui-elem-quote">新建管理员用户</blockquote>

  <div class="layui-card">
    <div class="layui-card-body" style="padding: 30px 20px;">
      <form class="layui-form" id="createUserForm">

        <div class="layui-form-item">
          <label class="layui-form-label">用户名</label>
          <div class="layui-input-block" style="max-width: 300px;">
            <input type="text" name="username" required lay-verify="required" placeholder="请输入用户名" class="layui-input">
          </div>
        </div>

        <div class="layui-form-item">
          <label class="layui-form-label">密码</label>
          <div class="layui-input-block" style="max-width: 300px;">
            <input type="password" name="password" placeholder="不填默认为123456" class="layui-input">
          </div>
        </div>

        <div class="layui-form-item">
          <div class="layui-input-block" style="max-width: 300px;">
            <button class="layui-btn layui-btn-normal" lay-submit lay-filter="submitCreate">创建用户</button>
          </div>
        </div>

      </form>
    </div>
  </div>
</div>

<script>
layui.use(['form', 'layer'], function(){
  var form = layui.form,
      layer = layui.layer;

  form.on('submit(submitCreate)', function(data){
    $.ajax({
      type: 'POST',
      url: 'ajax.php?act=create_user',
      data: data.field,
      dataType: 'json',
      success: function(res){
        if(res.code === 0){
          layer.msg(res.msg, {icon:1});
          setTimeout(function(){ location.reload(); }, 1200);
        } else {
          layer.msg(res.msg, {icon:2});
        }
      },
      error: function(){
        layer.msg('请求失败', {icon:2});
      }
    });
    return false;
  });
});
</script>
</body>
</html>
