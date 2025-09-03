<?php
session_start();
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: ./');
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
  <script src="https://static.geetest.com/static/tools/gt.js"></script>
  <style>
    html, body { height: 100%; margin: 0; }
    body {
      display: flex; flex-direction: column;
      background: #f2f2f2;
      font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
    }
    .main-content {
      flex: 1; display: flex;
      justify-content: center; align-items: center;
    }
    .demo-login-container { width: 320px; }
    .demo-login-other .layui-icon { font-size: 26px; }
    .layui-footer { text-align: center; padding: 15px 0; color: #888; font-size: 14px; }
    .trademark { font-size: 0.6em; vertical-align: super; margin-left: 2px; }

    /* 让验证码触发区与输入框等长（宽度100%），不改圆角和高度 */
    #captcha-container{ inset: 0; }      /* 取消内边距，避免变短 */
    .captcha-box{ width: 100%; }         /* 已有就保留，确保占满行 */
    .captcha-box{
      border: 0 !important;
      background: transparent !important;
      border-radius: 0 !important;
      box-shadow: none !important;
    }
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
        <input type="text" name="username" lay-verify="required" placeholder="用户名" autocomplete="off" class="layui-input">
      </div>
    </div>

    <div class="layui-form-item">
      <div class="layui-input-wrap">
        <div class="layui-input-prefix"><i class="layui-icon layui-icon-password"></i></div>
        <input type="password" name="password" lay-verify="required" placeholder="密   码" autocomplete="off" class="layui-input">
      </div>
    </div>

    <!-- Geetest 验证码 -->
    <div class="layui-form-item">
      <div class="captcha-box" id="captcha-box">
  <div id="captcha-placeholder"></div>   <!-- 占位：只在加载阶段存在 -->
  <div id="captcha-container"></div>
  <div id="captcha-loading">
    <i class="layui-icon layui-icon-loading-1" style="margin-right:6px;"></i>
    验证码加载中...
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
let GT = {obj:null, ready:false, passed:false, v:null};

// 初始化极验（使用 $.ajax）
// 初始化极验（使用 $.ajax）
function initCaptcha(){
    $("#captcha-container").empty();
    $("#captcha-loading").show().html(
      '<center><i class="layui-icon layui-icon-loading-1 layui-anim layui-anim-rotate layui-anim-loop" style="margin-right:6px;"></i> 验证码加载中...</center>'
    );
  $.ajax({
    url: "ajax.php",
    method: "GET",
    dataType: "json",
    data: { act: "get_gt_code" }
  }).done(function(cfg){
    if(!cfg || !cfg.gt || !cfg.challenge){
      $("#captcha-loading").show().text("初始化失败，点击重试");
      return;
    }
    initGeetest({
      gt: cfg.gt,
      challenge: cfg.challenge,
      offline: !cfg.success,
      new_captcha: true,
      product: "popup",
      width: "100%"
    }, function(obj){
      GT.obj = obj;
      obj.appendTo("#captcha-container");

      obj.onReady(function(){
        GT.ready = true;
        $("#captcha-loading").hide();          // 加载就绪，隐藏提示层
      });

      obj.onSuccess(function(){
        const v = obj.getValidate();
        if(v){
          GT.passed = true;
          GT.v = v;
        }
      });

      obj.onError(function(){
        GT.ready = false;
        GT.passed = false;
        $("#captcha-loading").show().text("验证码加载出错，点击重试");
      });
    });
  }).fail(function(){
    $("#captcha-loading").show().text("初始化失败，点击重试");
  });
}

// 支持点击占位框重试/触发
$(document).on('click', '#captcha-box', function(){
  // 未就绪：重试初始化；已就绪但未通过：拉起验证
  if(!GT.ready){
    initCaptcha();
  }else if(!GT.passed && GT.obj){
    GT.obj.verify();
  }
});


layui.use(['form','layer'], function(){
  var form = layui.form, layer = layui.layer;

  // 登录提交（使用 $.ajax）
  form.on('submit(demo-login)', function(data){
    if(!GT.ready){
      layer.msg('验证码未就绪');
      return false;
    }
    if(!GT.passed || !GT.v){
      if(GT.obj) GT.obj.verify();
      layer.msg('请完成人机验证');
      return false;
    }

    var formData = {
      act: 'login',
      username: data.field.username || '',
      password: data.field.password || '',
      geetest_challenge: GT.v.geetest_challenge,
      geetest_validate:  GT.v.geetest_validate,
      geetest_seccode:   GT.v.geetest_seccode
    };

    // 显示加载动画（带半透明遮罩）
    var loadingIdx = layer.load(0, { shade: 0.15 });

    $.ajax({
      url: "ajax.php",
      method: "POST",
      dataType: "json",
      data: formData
    }).done(function(res){
      if(res.code === 0){
        // 成功后也重置，避免验证码复用
        if(GT.obj) GT.obj.reset();
        GT.passed = false; GT.v = null;

        layer.msg('登录成功',{icon:1,time:1000},function(){
          location.href = '';
        });
      }else{
        layer.msg(res.msg || '登录失败',{icon:2});
        // 失败后重置验证码
        if(GT.obj) GT.obj.reset();
        GT.passed = false; GT.v = null;
      }
    }).fail(function(){
      layer.msg('请求失败',{icon:2});
      // 异常也重置
      if(GT.obj) GT.obj.reset();
      GT.passed = false; GT.v = null;
    }).always(function(){
      // 无论成功失败，都关闭加载动画
      layer.close(loadingIdx);
    });

    return false;
  });

  // 微信登录（使用 $.ajax）
  $('#wechat-login').on('click', function(){
    var l = layer.load();
    $.ajax({
      url: "ajax.php",
      method: "GET",
      dataType: "json",
      data: { act: "get_login_url" }
    }).done(function(res){
      layer.close(l);
      if(res.code === 0 && res.url){
        window.open(res.url, '_blank');
      }else{
        layer.msg(res.msg || '获取登录地址失败',{icon:2});
      }
    }).fail(function(){
      layer.close(l);
      layer.msg('请求失败',{icon:2});
    });
  });
});

$(function(){ initCaptcha(); });
</script>

</body>
</html>
