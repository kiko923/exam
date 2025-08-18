<?php
require_once 'auth_check.php';
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title>微信账号绑定管理</title>
  <?php include('head.php'); ?> <!-- 引入 layui.css 和 layui.js，含 jQuery -->
</head>
<body>

<div class="container" style="margin-left: 20px; margin-right: 20px;">
  <hr>
  <div class="layui-card" style="margin-right: 20px;">
    <!--<div class="layui-card-header" style="font-weight: bold;">微信账号绑定管理</div>-->
      <blockquote class="layui-elem-quote">微信账号绑定管理</blockquote>
    <div class="layui-card-body" style="padding: 30px 20px;">
      <form class="layui-form">

        <!-- 当前绑定微信ID -->
        <div class="layui-form-item" style="display: flex; align-items: center;">
          <label class="layui-form-label" style="flex-shrink: 0;">绑定状态</label>
          <div style="flex-grow: 1; max-width: 300px;">
            <input type="text" id="currentWxUid" readonly value="加载中..." class="layui-input" />
          </div>
        </div>

        <!-- 操作按钮 -->
        <div class="layui-form-item" style="display: flex; align-items: center;">
          <label class="layui-form-label" style="flex-shrink: 0;">操作</label>
          <div id="btnGroup" style="flex-grow: 1; display: flex; gap: 10px; flex-wrap: wrap;"></div>
        </div>

      </form>
    </div>
  </div>
</div>

<script>
layui.use(['layer'], function () {
  var layer = layui.layer;

  function fetchBindStatus() {
    $.ajax({
      url: 'ajax.php?act=get_bind_status',
      type: 'GET',
      dataType: 'json',
      success: function (res) {
        if (res.code === 0) {
          var uid = res.social_uid || '未绑定';
          $('#currentWxUid').val(uid);

          var btnGroup = $('#btnGroup');
          btnGroup.empty();

          if (uid === '未绑定') {
            btnGroup.append('<button type="button" class="layui-btn layui-btn-normal" style="color: #ffffff !important;background-color: #27c24c;border-color: #27c24c;" id="bindBtn">立即绑定</button>');
          } else {
            btnGroup.append('<button type="button" class="layui-btn layui-btn-disabled" style="background-color: #e6f4ea; border-color: #e6f4ea; color: #999;" disabled>已绑定</button>');
            btnGroup.append('<button type="button" class="layui-btn layui-btn-danger" id="unbindBtn">解绑微信账号</button>');
          }

          bindEvents(uid);
        } else {
          layer.msg(res.msg || '获取绑定状态失败', { icon: 2 });
          $('#currentWxUid').val('加载失败');
        }
      },
      error: function () {
        layer.msg('请求失败，请检查网络', { icon: 2 });
        $('#currentWxUid').val('加载失败');
      }
    });
  }

  function bindEvents(uid) {
    if (uid === '未绑定') {
      $('#bindBtn').click(function () {
        layer.msg('正在获取登录链接...',{icon:0});
        $.ajax({
          url: 'ajax.php?act=get_wecaht_login_url',
          type: 'GET',
          dataType: 'json',
          success: function (res) {
            if (res.code === 0 && res.url) {
                layer.msg('跳转成功',{icon:1});
              window.open(res.url, '_blank');
            } else {
              layer.msg(res.msg || '获取登录地址失败', { icon: 2 });
            }
          },
          error: function () {
            layer.msg('请求失败，请检查网络', { icon: 2 });
          }
        });
      });
    }

    $('#unbindBtn').click(function () {
      layer.confirm('确定解绑当前微信账号吗？解绑后将无法使用微信一键登录。', { icon: 3, title: '确认解绑' }, function (index) {
        $.ajax({
          url: 'ajax.php?act=bind_wechat_action',
          type: 'POST',
          dataType: 'json',
          data: { action: 'unbind' },
          success: function (res) {
            if (res.code === 0) {
              fetchBindStatus();
              layer.msg('解绑成功', { icon: 1 });
            } else {
              layer.msg(res.msg || '解绑失败', { icon: 2 });
            }
          },
          error: function () {
            layer.msg('请求失败，请检查网络', { icon: 2 });
          }
        });

        layer.close(index);
      });
    });
  }

  fetchBindStatus();
});
</script>


</body>
</html>
