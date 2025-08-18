<?php
require_once 'auth_check.php';
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>修改管理员密码</title>
  <?php include('head.php'); ?> <!-- 引入 layui.css 和 layui.js，含 jQuery -->
</head>
<body>
<div class="container" style="margin: 20px;">
  <blockquote class="layui-elem-quote">修改管理员密码</blockquote>

  <div class="layui-card">
    <div class="layui-card-body" style="padding: 30px 20px;">
      <form class="layui-form" id="passwordForm" lay-filter="formFilter">

        <div class="layui-form-item">
          <label class="layui-form-label">旧密码</label>
          <div class="layui-input-block" style="max-width: 300px;">
            <input type="password" name="old_password" required lay-verify="required" placeholder="请输入旧密码" class="layui-input">
          </div>
        </div>

        <div class="layui-form-item">
          <label class="layui-form-label">新密码</label>
          <div class="layui-input-block" style="max-width: 300px;">
            <input type="password" name="new_password" required lay-verify="required|passwordCheck" placeholder="请输入新密码" class="layui-input">
          </div>
        </div>

        <div class="layui-form-item">
          <label class="layui-form-label">确认密码</label>
          <div class="layui-input-block" style="max-width: 300px;">
            <input type="password" name="confirm_password" required lay-verify="required|confirmPwd" placeholder="请再次输入新密码" class="layui-input">
          </div>
        </div>

        <div class="layui-form-item">
          <div class="layui-input-block" style="max-width: 300px;">
            <button class="layui-btn layui-btn-normal" lay-submit lay-filter="submitPwd">确认修改</button>
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

  // 自定义验证规则
  form.verify({
    passwordCheck: function(value){
      if(value.length < 6){
        return '新密码不能少于6位';
      }
    },
    confirmPwd: function(value){
      var newPwd = $('input[name=new_password]').val();
      if(value !== newPwd){
        return '两次密码输入不一致';
      }
    }
  });

  // 表单提交
  form.on('submit(submitPwd)', function(data){
    $.ajax({
      type: 'POST',
      url: 'ajax.php?act=change_password',
      data: data.field,
      dataType: 'json',
      success: function(res){
        if(res.code === 0){
          layer.msg(res.msg, {icon: 1});
        } else {
          layer.msg(res.msg, {icon: 2});
        }
      },
      error: function(){
        layer.msg('请求失败，请检查网络或稍后再试', {icon: 2});
      }
    });
    return false; // 阻止表单跳转
  });
});
</script>
</body>
</html>
