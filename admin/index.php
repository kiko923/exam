<?php
require_once 'auth_check.php';
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>考试系统管理后台</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php include('head.php'); ?>
  <style>
    /* ====== 统一侧栏宽度（与主体 left 必须一致）====== */
    :root { --side-width: 200px; } /* 如需更宽，改成 220px，并保持下方一致 */

    /* 顶部高度（供定位参照） */
    .layui-header{ height:60px; }

    /* 覆盖 Layui：侧栏宽度、主体/底部左边距 */
    .layui-layout-admin .layui-side  { width: var(--side-width) !important; }
    .layui-layout-admin .layui-body  { left:  var(--side-width) !important; }
    .layui-layout-admin .layui-footer{ left:  var(--side-width) !important; }

    /* 侧栏基础样式（去掉边框/阴影，避免出现亮色竖条） */
    .layui-side{
      position: fixed;
      top: 60px;
      bottom: 0;
      z-index: 1002;            /* 高于遮罩，确保可点击 */
      overflow-x: hidden;
      background-color: #393D49;
      border-right: none !important;
      box-shadow: none !important;
      will-change: transform;
      transition: transform .2s ease;
    }
    .layui-side-scroll{
      width: 100%;
      height: 100%;
      overflow-y: auto;
      overflow-x: hidden;
      padding-right: 0;
    }

    /* 主体与底部跟随过渡 */
    .layui-body, .layui-footer{ transition: left .2s ease; }
    .layui-body{ position:relative; z-index:0; }
    iframe { width:100%; height:100%; border:none; }
    body { overflow-y: hidden; }
    html, body { height: 100%; }

    /* 顶部 logo 内加入收缩按钮，避免与文字重叠 */
    #topLogo{
      display:flex; align-items:center; gap:10px;
      height:60px; padding:0 15px; box-sizing:border-box;
    }
    #btn-toggle-side{
      display:inline-flex; align-items:center; justify-content:center;
      width:28px; height:28px; border-radius:6px;
      color:#c2c2c2;
    }
    #btn-toggle-side:hover{ background:rgba(255,255,255,.06); color:#fff; }
    #topLogo .logo-text{ white-space:nowrap; }

    /* 桌面折叠（记忆） */
    .side-collapsed .layui-side{ transform: translateX(-100%); }
    .side-collapsed .layui-body,
    .side-collapsed .layui-footer{ left:0 !important; }

    /* 手机：默认折叠，展开有遮罩 */
    .side-mask{ display:none; }
    @media (max-width: 992px){
      body:not(.side-expanded) .layui-side{
        transform: translateX(-100%);
      }
      body:not(.side-expanded) .layui-body,
      body:not(.side-expanded) .layui-footer{
        left:0 !important;
      }
      body.side-expanded .side-mask{
        display:block;
        position:fixed; z-index:1001;
        top:60px; left:0; right:0; bottom:0;
        background: rgba(0,0,0,.35);
      }
    }

    /* 底部商标上标 */
    .trademark {
      font-size: 0.6em; vertical-align: super; margin-left: 2px;
    }
  </style>
</head>
<body class="layui-layout-body">
<div class="layui-layout layui-layout-admin">

  <!-- 顶部 -->
  <div class="layui-header">
    <div class="layui-logo" id="topLogo">
      <!-- 收缩/展开按钮放在 logo 内部，避免与标题重叠 -->
      <a href="javascript:;" id="btn-toggle-side" aria-label="展开/收起侧栏" title="展开/收起侧栏">
        <i class="layui-icon layui-icon-spread-left"></i>
      </a>
      <span class="logo-text">考试系统管理后台</span>
    </div>

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
          <li class="layui-nav-item">
            <a href="user_manager.php" data-hash="list_user">
              <i class="layui-icon layui-icon-friends"></i> 用户管理
            </a>
          </li>
        <?php } ?>

      </ul>
    </div>
  </div>

  <!-- 遮罩（仅手机展开时显示，用于点击收起） -->
  <div class="side-mask" id="sideMask"></div>

  <!-- 主体内容 -->
  <div class="layui-body">
    <iframe name="mainFrame" id="mainFrame"></iframe>
  </div>

  <!-- 底部 -->
  <div class="layui-footer">
    © <?php echo date('Y'); ?> 考试系统管理后台 - Powered by 永至科技<span class="trademark">®</span>
  </div>

</div>

<script>
layui.use('element', function(){
  var element = layui.element;

  // 路由映射
  var pageMap = {
    'welcome': 'welcome.php',
    'upload': 'upload.php',
    'questions_manager': 'questions_manager.php',
    'generate_exam': 'generate_exam.php',
    'exam_records': 'exam_records.php',
    'bind_wechat': 'bind_wechat.php',
    'editinfo': 'editinfo.php',
    'list_user': 'user_manager.php',
  };

  function loadPageByHash() {
    var hash = location.hash.slice(1);
    if (!hash || !pageMap[hash]) hash = 'welcome';
    document.getElementById('mainFrame').src = pageMap[hash];

    // 菜单高亮
    document.querySelectorAll('.layui-nav-item').forEach(function(li){
      li.classList.remove('layui-this');
    });
    var a = document.querySelector('a[data-hash="'+hash+'"]');
    if(a && a.parentElement) a.parentElement.classList.add('layui-this');
  }

  loadPageByHash();
  window.addEventListener('hashchange', loadPageByHash);

  // 菜单点击：仅改 hash，阻止默认跳转
  document.querySelectorAll('.layui-nav-item a[data-hash]').forEach(function(a){
    a.addEventListener('click', function(e){
      e.preventDefault();
      var hash = this.getAttribute('data-hash');
      if(hash) location.hash = hash;
    });
  });
});
</script>

<script>
/* ====== 侧栏收缩/展开逻辑（桌面记忆，手机遮罩）====== */
(function(){
  const btn  = document.getElementById('btn-toggle-side');
  const mask = document.getElementById('sideMask');
  const isMobile = () => window.matchMedia('(max-width: 992px)').matches;
  const LS_KEY = 'admin_side_collapsed';

  function initSide(){
    if (isMobile()){
      document.body.classList.remove('side-collapsed');
      document.body.classList.remove('side-expanded');
    }else{
      const collapsed = localStorage.getItem(LS_KEY) === '1';
      document.body.classList.toggle('side-collapsed', collapsed);
      document.body.classList.remove('side-expanded');
    }
  }

  function toggleDesktop(){
    const nowCollapsed = !document.body.classList.contains('side-collapsed');
    document.body.classList.toggle('side-collapsed', nowCollapsed);
    localStorage.setItem(LS_KEY, nowCollapsed ? '1' : '0');
  }

  function toggleMobile(){ document.body.classList.toggle('side-expanded'); }
  function closeMobile(){  document.body.classList.remove('side-expanded'); }

  initSide();

  btn && btn.addEventListener('click', function(){
    isMobile() ? toggleMobile() : toggleDesktop();
  });
  mask && mask.addEventListener('click', closeMobile);

  // 小屏点击菜单后自动收起
  document.querySelectorAll('.layui-nav-item a[data-hash]').forEach(function(a){
    a.addEventListener('click', function(){ if(isMobile()) closeMobile(); });
  });

  // 监听窗口变化，切换模式时重置
  window.addEventListener('resize', function(){
    clearTimeout(window.__resizeSideTimer);
    window.__resizeSideTimer = setTimeout(initSide, 150);
  });
})();
</script>

<script>
/* ====== iframe 刷新（含快捷键）====== */
function reloadIframe(){
  var iframe = document.getElementById('mainFrame');
  if(iframe && iframe.contentWindow){
    iframe.contentWindow.location.reload(true);
  }
}
document.addEventListener('keydown', function(e){
  if(e.key === "F5" || ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "r")){
    e.preventDefault();
    reloadIframe();
  }
});
</script>

</body>
</html>
