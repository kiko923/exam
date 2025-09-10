<?php
session_start();
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: ./'); // 已登录跳转到首页
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>后台登录</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php include('head.php') ?>
  <style>
  html, body {height: 100%;margin: 0;}
  body {
    display: flex;flex-direction: column;
    background: #f2f2f2;
    font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
  }
  .main-content {
    flex: 1;display: flex;
    justify-content: center;align-items: center;
  }
  .demo-login-container {width: 320px;}
  .demo-login-other .layui-icon {
    position: relative;display: inline-block;
    margin: 0 2px;top: 2px;font-size: 26px;
  }
  .layui-footer {
    text-align: center;padding: 15px 0;
    color: #888;font-size: 14px;
  }
  .trademark {font-size: 0.6em;vertical-align: super;margin-left: 2px;}
  body {display: flex;flex-direction: column;min-height: 100vh;}
  .layui-footer {margin-top: auto;}
  </style>
</head>
<body>
<div class="main-content">
<form class="layui-form" id="loginForm">
  <div class="demo-login-container">
    <h2 style="text-align: center; margin-bottom: 20px;">考题后台管理系统</h2>

    <div class="layui-form-item">
      <div class="layui-input-wrap">
        <div class="layui-input-prefix"><i class="layui-icon layui-icon-username"></i></div>
        <input type="text" name="username" lay-verify="required" placeholder="用户名" autocomplete="off" class="layui-input" lay-affix="clear">
      </div>
    </div>

    <div class="layui-form-item">
      <div class="layui-input-wrap">
        <div class="layui-input-prefix"><i class="layui-icon layui-icon-password"></i></div>
        <input type="password" name="password" lay-verify="required" placeholder="密   码" autocomplete="off" class="layui-input" lay-affix="eye">
      </div>
    </div>

    <div class="layui-form-item">
      <div class="layui-row">
        <div class="layui-col-xs7">
          <div class="layui-input-wrap">
            <div class="layui-input-prefix"><i class="layui-icon layui-icon-vercode"></i></div>
            <input type="text" name="captcha" lay-verify="required" placeholder="验证码" autocomplete="off" class="layui-input" lay-affix="clear">
          </div>
        </div>
        <div class="layui-col-xs5">
          <div style="margin-left: 10px;">
            <img id="captcha_img" src="ajax.php?act=captcha" style="height:38px; width:122px; object-fit:cover; cursor:pointer;" title="点击刷新验证码" alt="验证码" />
          </div>
        </div>
      </div>
    </div>

    <div class="layui-form-item">
      <button class="layui-btn layui-btn-fluid" lay-submit lay-filter="demo-login">登录</button>
    </div>

    <div class="layui-form-item demo-login-other">
      <label>社交账号登录：</label>
      <span style="padding: 0 21px 0 6px;">
        <a href="javascript:void(0);" id="wechat-login"><i class="layui-icon layui-icon-login-wechat" style="color: #4daf29;"></i></a>
      </span>
    </div>
  </div>
</form>
</div>

<!-- 底部 -->
<div class="layui-footer">
  © <?php echo date('Y'); ?> 考试系统管理后台 - Powered by 永至科技<span class="trademark">®</span>
</div>

<script>
layui.use(['form','layer'], function(){
  var form = layui.form, layer = layui.layer;

  // 登录表单提交
  form.on('submit(demo-login)', function(data){
    var formData = data.field;
    formData.act = 'login';
    $.ajax({
      url: 'ajax.php',
      type: 'POST',
      data: formData,
      dataType: 'json',
      success: function(res){
        if(res.code === 0){
          layer.msg('登录成功', {icon: 1, time: 1000}, function(){
            // 登录成功后 AJAX 请求主页（防止直接 location.href）
            $.ajax({
              url: './',
              type: 'GET',
              success: function(){ location.reload(); },
              error: function(){ location.reload(); }
            });
          });
        } else {
          refreshCaptcha();
          if(res.code === 403){
            layer.alert(res.msg, {icon: 2});
          } else {
            layer.msg(res.msg, {time:1000,icon:2});
          }
        }
      },
      error: function(){
        layer.msg('请求失败，请稍后重试', {icon: 2});
      }
    });
    return false;
  });

  // 刷新验证码 AJAX
  function refreshCaptcha(){
    $('#captcha_img').attr('src','ajax.php?act=captcha&'+Math.random());
  }
  $('#captcha_img').on('click', refreshCaptcha);

  // 微信登录 AJAX
  $('#wechat-login').on('click', function(){
    var l = layer.load();
    $.ajax({
      url: 'ajax.php?act=get_login_url',
      type: 'GET',
      dataType: 'json',
      success: function(res){
        layer.close(l);
        if(res.code === 0 && res.url){
          // AJAX 请求后仍需打开授权页面
          window.location.href= res.url;
        } else {
          layer.msg(res.msg || '获取登录地址失败', {icon: 2});
        }
      },
      error: function(){
        layer.close(l);
        layer.msg('请求失败，请稍后重试', {icon: 2});
      }
    });
  });

});
</script>
</body>
</html>
