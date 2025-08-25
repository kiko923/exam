<?php
require_once 'auth_check.php';
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>考试系统管理后台</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php include('head.php');?>
  <style>
    .layui-side-scroll { overflow-y: auto; }
    iframe { width: 100%; height: 100%; border: none; }
    /* 确保左侧菜单栏背景色填充到底部 */
    .layui-side {
      position: fixed;
      top: 60px;
      bottom: 0;
      z-index: 999;
      overflow-x: hidden;
      background-color: #393D49;
    }
    .layui-side-scroll {
      width: 100%;
      height: 100%;
      overflow-x: hidden;
    }
    body {
      overflow-y: hidden;
    }
    html, body {
      height: 100%;
    }
  </style>
</head>
<body class="layui-layout-body">
<div class="layui-layout layui-layout-admin">
  <!-- 顶部 -->
  <div class="layui-header">
    <div class="layui-logo">考试系统管理后台</div>
    <ul class="layui-nav layui-layout-right">
        <li class="layui-nav-item">
          <a href="javascript:;" onclick="reloadIframe()">
            <i class="layui-icon layui-icon-refresh"></i> 刷新
          </a>
        </li>
      <li class="layui-nav-item">欢迎，<?php echo htmlspecialchars($_SESSION['admin_user']); ?></li>
      <li class="layui-nav-item"><a href="ajax.php?act=logout">退出</a></li>
    </ul>
  </div>

  <!-- 左侧菜单 -->
  <div class="layui-side">
    <div class="layui-side-scroll">
      <ul class="layui-nav layui-nav-tree" lay-shrink="all" lay-filter="side-menu">

        <li class="layui-nav-item">
          <a href="welcome.php" data-hash="welcome">
            <i class="layui-icon">&#xe68e;</i> 后台首页
          </a>
        </li>
        <li class="layui-nav-item">
          <a href="editinfo.php" data-hash="editinfo">
            <i class="layui-icon layui-icon-password"></i> 修改密码
          </a>
        </li>
        <li class="layui-nav-item">
          <a href="upload.php" data-hash="upload">
            <i class="layui-icon">&#xe681;</i> 导入题库
          </a>
        </li>
        <li class="layui-nav-item">
          <a href="questions_manager.php" data-hash="questions_manager">
            <i class="layui-icon">&#xe62d;</i> 题库管理
          </a>
        </li>
        <li class="layui-nav-item">
          <a href="generate_exam.php" data-hash="generate_exam">
            <i class="layui-icon">&#xe705;</i> 生成考试
          </a>
        </li>
        <li class="layui-nav-item">
          <a href="exam_records.php" data-hash="exam_records">
            <i class="layui-icon">&#xe62c;</i> 考试管理
          </a>
        </li>
        <li class="layui-nav-item">
          <a href="bind_wechat.php" data-hash="bind_wechat">
            <i class="layui-icon layui-icon-login-wechat"></i> 绑定微信登录
          </a>
        </li>
        <?php if (!empty($_SESSION['admin_is_admin'])) { ?>
          <!--<li class="layui-nav-item">-->
          <!--  <a href="create_user.php" data-hash="create_user">-->
          <!--    <i class="layui-icon layui-icon-add-circle"></i> 创建用户-->
          <!--  </a>-->
          <!--</li>-->
          <li class="layui-nav-item">
            <a href="user_manager.php" data-hash="list_user">
              <i class="layui-icon layui-icon-friends"></i> 用户管理
            </a>
          </li>
        <?php } ?>

        
        <!-- 其他空菜单项可去除或保留 -->
      </ul>
    </div>
  </div>

  <!-- 主体内容 -->
  <div class="layui-body">
    <iframe name="mainFrame" id="mainFrame"></iframe>
  </div>

  <!-- 底部 -->
<div class="layui-footer">
  © <?php echo date('Y'); ?> 考试系统管理后台 - Powered by 永至科技<span class="trademark">®</span>
</div>

<style>
  .trademark {
    font-size: 0.6em;       /* 小一点 */
    vertical-align: super;  /* 上标 */
    margin-left: 2px;       /* 和文字稍微隔开 */
  }
</style>


</div>

<script>
layui.use('element', function(){
  var element = layui.element;

  // hash -> iframe src 映射
  var pageMap = {
    'welcome': 'welcome.php',
    'upload': 'upload.php',
    'questions_manager': 'questions_manager.php',
    'generate_exam': 'generate_exam.php',
    'exam_records': 'exam_records.php',
    'bind_wechat': 'bind_wechat.php',
    'editinfo': 'editinfo.php',
    // 'create_user': 'create_user.php',
    'list_user': 'user_manager.php',
  };

  function loadPageByHash() {
    var hash = location.hash.slice(1); // 去掉#
    if (!hash || !pageMap[hash]) {
      hash = 'welcome'; // 默认首页
    }
    var src = pageMap[hash];
    document.getElementById('mainFrame').src = src;

    // 取消所有菜单高亮
    var lis = document.querySelectorAll('.layui-nav-item');
    lis.forEach(function(li){
      li.classList.remove('layui-this');
    });

    // 找到对应菜单高亮
    var a = document.querySelector('a[data-hash="'+hash+'"]');
    if(a) {
      var li = a.parentElement;
      if(li) li.classList.add('layui-this');
    }
  }

  // 页面初次加载执行
  loadPageByHash();

  // 监听浏览器前进后退，hash 变化
  window.addEventListener('hashchange', loadPageByHash);

  // 菜单点击修改 hash，阻止默认跳转
  document.querySelectorAll('.layui-nav-item a[data-hash]').forEach(function(a){
    a.addEventListener('click', function(e){
      e.preventDefault();
      var hash = this.getAttribute('data-hash');
      if(hash) {
        location.hash = hash;
      }
    });
  });
});

</script>
<script>
    function reloadIframe(){
  var iframe = document.getElementById('mainFrame');
  if(iframe && iframe.contentWindow){
    iframe.contentWindow.location.reload(true);
  }
}

// 捕获快捷键 F5 / Ctrl+R / Cmd+R
document.addEventListener('keydown', function(e){
  if(e.key === "F5" || ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "r")){
    e.preventDefault();
    reloadIframe();
  }
});
</script>
</body>
</html>
