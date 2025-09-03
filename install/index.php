<?php
// install.php 顶部放置

// 1) 锁文件路径（放和 install.php 同目录）
define('INSTALL_LOCK', __DIR__ . '/install.lock');

/**
 * 根据访问方式输出“已安装”提示并退出
 */
function already_installed_exit(): void {
    $msg = '系统已安装。如需重新安装，请先删除 install.lock 再访问安装页面。';

    // CLI 模式
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "[INSTALL] {$msg}\n");
        exit(1);
    }

    // Ajax / JSON 请求
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    if ($isAjax || stripos($accept, 'application/json') !== false) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode(['code' => 0, 'msg' => $msg], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 普通浏览器访问（HTML）
    header('Content-Type: text/html; charset=utf-8');
    http_response_code(403);
    echo "<!doctype html><meta charset='utf-8'>
    <title>已安装</title>
    <style>body{font:16px/1.6 -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial;}
    .box{max-width:720px;margin:10vh auto;padding:24px;border:1px solid #eee;border-radius:12px}
    .tip{color:#d23;font-weight:700}</style>
    <div class='box'>
      <div class='tip'>⚠ 系统已安装</div>
      <p>{$msg}</p>
    </div>";
    exit;
}

// 2) 检测锁文件
if (is_file(INSTALL_LOCK)) {
    already_installed_exit();
}

// ===== 下面写你的安装流程 =====
// ……（校验环境、填数据库、导入 SQL 等）……
//
// 安装成功后写锁文件：
function write_install_lock(): void {
    $content = "installed_at=" . date('Y-m-d H:i:s') . "\n";
    // 也可写入版本/环境信息
    @file_put_contents(INSTALL_LOCK, $content);
}

// 示例：当安装全部成功
// write_install_lock();
// echo '安装成功';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>考试题库管理系统 - 安装向导</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.17/dist/css/layui.css">
    <style>
        body{background:#f5f5f5;}
        .install-container{max-width:800px;margin:50px auto;padding:20px;}
        .step-header{text-align:center;margin-bottom:40px;}
        .step-content{background:#fff;padding:30px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,.1);}
        .requirement-item{display:flex;align-items:center;padding:10px 0;border-bottom:1px solid #f0f0f0;}
        .requirement-name{flex:1;}
        .requirement-status{width:140px;text-align:center}
        .status-ok{color:#5FB878;}
        .status-error{color:#FF5722;}
        .status-warning{color:#FFB800;}
        /* 禁止用户点击 Tab 头手动跳步（程序仍可切换） */
        .layui-tab[lay-filter="install-tabs"] .layui-tab-title li{pointer-events:none;}
        .note{color:#999;font-size:12px;margin-top:6px}
    </style>
</head>
<body>
<div class="install-container">
    <div class="step-header">
        <h1>考试题库管理系统</h1>
        <p>安装向导</p>
    </div>

    <div class="step-content">
        <div class="layui-tab" lay-filter="install-tabs">
            <ul class="layui-tab-title">
                <li class="layui-this" lay-id="check">环境检测</li>
                <li lay-id="database">数据库配置</li>
                <li lay-id="complete">安装完成</li>
            </ul>
            <div class="layui-tab-content">

                <!-- 环境检测 -->
                <div class="layui-tab-item layui-show">
                    <h3>环境要求检测</h3>

                    <div class="requirement-item">
                        <div class="requirement-name">PHP版本 (&ge; 7.0)</div>
                        <div class="requirement-status">
                            <?php if (version_compare(PHP_VERSION, '7.0.0', '>=')): ?>
                                <span class="status-ok">✓ <?php echo PHP_VERSION; ?></span>
                            <?php else: ?>
                                <span class="status-error">✗ <?php echo PHP_VERSION; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="requirement-item">
                        <div class="requirement-name">PDO扩展</div>
                        <div class="requirement-status">
                            <?php if (extension_loaded('pdo')): ?>
                                <span class="status-ok">✓ 已安装</span>
                            <?php else: ?>
                                <span class="status-error">✗ 未安装</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="requirement-item">
                        <div class="requirement-name">PDO MySQL扩展</div>
                        <div class="requirement-status">
                            <?php if (extension_loaded('pdo_mysql')): ?>
                                <span class="status-ok">✓ 已安装</span>
                            <?php else: ?>
                                <span class="status-error">✗ 未安装</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="requirement-item">
                        <div class="requirement-name">cURL扩展</div>
                        <div class="requirement-status">
                            <?php if (extension_loaded('curl')): ?>
                                <span class="status-ok">✓ 已安装</span>
                            <?php else: ?>
                                <span class="status-error">✗ 未安装</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="requirement-item">
                        <div class="requirement-name">JSON扩展</div>
                        <div class="requirement-status">
                            <?php if (extension_loaded('json')): ?>
                                <span class="status-ok">✓ 已安装</span>
                            <?php else: ?>
                                <span class="status-error">✗ 未安装</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="requirement-item">
                        <div class="requirement-name">includes 目录写入权限</div>
                        <div class="requirement-status">
                            <?php if (is_writable('../includes/')): ?>
                                <span class="status-ok">✓ 可写</span>
                            <?php else: ?>
                                <span class="status-warning">✗ 不可写</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php
                    $can_continue = version_compare(PHP_VERSION, '7.0.0', '>=') &&
                                    extension_loaded('pdo') &&
                                    extension_loaded('pdo_mysql') &&
                                    extension_loaded('curl') &&
                                    extension_loaded('json');
                    ?>

                    <div style="margin-top:30px;text-align:center;">
                        <?php if ($can_continue): ?>
                            <button class="layui-btn" id="btnNext">下一步</button>
                            <!--<div class="note">将进入数据库配置（URL 会自动切换为 <code>?step=2</code>）</div>-->
                        <?php else: ?>
                            <button class="layui-btn layui-btn-disabled" disabled>请先解决环境问题</button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 数据库配置 -->
                <div class="layui-tab-item">
                    <h3>数据库配置</h3>
                    <form class="layui-form" id="dbForm">
                        <div class="layui-form-item">
                            <label class="layui-form-label">数据库主机</label>
                            <div class="layui-input-block">
                                <input type="text" name="host" value="localhost" required lay-verify="required" placeholder="数据库主机地址" class="layui-input">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">数据库名称</label>
                            <div class="layui-input-block">
                                <input type="text" name="dbname" value="" required lay-verify="required" placeholder="数据库名称" class="layui-input">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">用户名</label>
                            <div class="layui-input-block">
                                <input type="text" name="user" value="" required lay-verify="required" placeholder="数据库用户名" class="layui-input">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">密码</label>
                            <div class="layui-input-block">
                                <input type="password" name="password" placeholder="数据库密码" class="layui-input">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <button type="button" class="layui-btn" lay-submit lay-filter="testDb">测试连接</button>
                                <button type="button" class="layui-btn layui-btn-primary" id="btnPrev">上一步</button>
                            </div>
                        </div>
                    </form>

                    <div id="dbTestResult" style="margin-top:20px;display:none;">
                        <div class="layui-alert layui-alert-normal" id="dbResultMessage"></div>
                        <div style="text-align:center;margin-top:20px;">
                            <button class="layui-btn" id="installBtn" style="display:none;">开始安装</button>
                        </div>
                    </div>
                </div>

                <!-- 安装完成 -->
                <div class="layui-tab-item">
                    <div style="text-align:center;">
                        <i class="layui-icon layui-icon-ok-circle" style="font-size:60px;color:#5FB878;"></i>
                        <h3>安装完成！</h3>
                        <p>恭喜您，考试题库管理系统安装成功！</p>
                        <div style="margin:30px 0;">
                            <p><strong>默认管理员账号：</strong> admin</p>
                            <p><strong>默认密码：</strong> 123456</p>
                            <p style="color:#FF5722;font-size:12px;">请登录后及时修改默认密码</p>
                        </div>
                        <div>
                            <a href="../admin/" class="layui-btn">进入后台管理</a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/layui@2.8.17/dist/layui.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
layui.use(['element','form','layer'], function(){
  var element = layui.element, form = layui.form, layer = layui.layer;

  // —— 步骤控制（基于 URL + localStorage 的前端约束）——
  var qs = new URLSearchParams(location.search);
  var step = parseInt(qs.get('step') || '1', 10);
  var envOK = <?php echo $can_continue ? 'true' : 'false'; ?>;

  // 允许的最大步（根据本地记录）
  var installed = localStorage.getItem('wizard_install_ok') === '1';
  var envPassed = localStorage.getItem('wizard_env_ok') === '1';

  // 进入各步的前置条件
function guardStep(s){
  const envPassedLS = localStorage.getItem('wizard_env_ok') === '1';
  const installedLS = localStorage.getItem('wizard_install_ok') === '1';
  if (s <= 1) return 1;
  if (!envOK || !envPassedLS) return 1; // 没过环境 -> 只能到 1
  if (s >= 3 && !installedLS) return 2; // 未安装 -> 最多到 2
  return s;
}


  function goStep(s){
    s = guardStep(s);
    var id = (s===1?'check':(s===2?'database':'complete'));
    element.tabChange('install-tabs', id);
    var nqs = new URLSearchParams(location.search);
    nqs.set('step', String(s));
    history.replaceState(null, '', location.pathname + '?' + nqs.toString());
  }

  // 初始进入时校验 URL 步骤
  goStep(step);

  // 禁止用户通过点击标签跳步（CSS 已禁用指针事件，这里再兜底）
  $('.layui-tab-title li').on('click', function(e){
    e.preventDefault();
    goStep(step);
  });

  // —— 环境检测：下一步 —— //
  $('#btnNext').on('click', function(){
    if(!envOK){
      layer.msg('请先解决环境问题',{icon:2}); return;
    }
    localStorage.setItem('wizard_env_ok','1');
    step = 2;
    goStep(2);
  });

  // —— 数据库配置：上一步 —— //
  $('#btnPrev').on('click', function(){
    step = 1;
    goStep(1);
  });

  // 测试数据库连接
  form.on('submit(testDb)', function(data){
    var loadIndex = layer.msg('正在测试连接...', {icon:16, shade:.3, time:0});
    $.ajax({
      url:'install_ajax.php', type:'POST', dataType:'json',
      data:{
        action:'test_db',
        host:data.field.host, dbname:data.field.dbname,
        user:data.field.user, password:data.field.password
      }
    }).done(function(res){
      layer.close(loadIndex);
      var resultDiv = $('#dbTestResult');
      var messageDiv = $('#dbResultMessage');
      var installBtn = $('#installBtn');
      resultDiv.show();
      if(res && res.success){
        messageDiv.removeClass('layui-alert-danger').addClass('layui-alert-normal')
          .html('<i class="layui-icon layui-icon-ok-circle"></i> 数据库连接成功！');
        installBtn.show();
      }else{
        messageDiv.removeClass('layui-alert-normal').addClass('layui-alert-danger')
          .html('<i class="layui-icon layui-icon-close-fill"></i> 连接失败: ' + (res ? res.message : '未知错误'));
        installBtn.hide();
      }
    }).fail(function(xhr, status, error){
      layer.close(loadIndex);
      console.log('AJAX Error:', xhr.responseText);
      layer.msg('请求失败: ' + error, {icon:2});
    });
    return false;
  });

  // 点击“开始安装”
  $('#installBtn').on('click', function(){
    var formData = {};
    $('#dbForm').serializeArray().forEach(function(it){ formData[it.name]=it.value; });

    var loadIndex = layer.msg('正在安装数据库...', {icon:16, shade:.3, time:0});
    $.post('install_ajax.php', Object.assign({action:'install_db'}, formData), function(res){
      layer.close(loadIndex);
      if(res && res.success){
        localStorage.setItem('wizard_install_ok','1');
        layer.msg('数据库安装成功！',{icon:1});
        step = 3;
        goStep(3);
      }else{
        layer.msg('安装失败: ' + (res ? res.message : '未知错误'), {icon:2});
      }
    }, 'json');
  });

});
</script>
</body>
</html>
