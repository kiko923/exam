<?php 
require_once 'auth_check.php'; 

// 数据统计
try {
    $totalQuestions = (int)$pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn();
    $totalCategories = (int)$pdo->query("SELECT COUNT(*) FROM question_categories")->fetchColumn();
    $totalExams = (int)$pdo->query("SELECT COUNT(*) FROM exams")->fetchColumn();
    $todayExams = (int)$pdo->query("SELECT COUNT(*) FROM exams WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    $totalFinishedExams = (int)$pdo->query("SELECT COUNT(*) FROM exams WHERE is_finished = 1")->fetchColumn();
    $todayFinishedExams = (int)$pdo->query("SELECT COUNT(*) FROM exam_answers WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    $totalAnswers = (int)$pdo->query("SELECT COUNT(*) FROM exam_answers")->fetchColumn();
    $uniqueStudents = (int)$pdo->query("SELECT COUNT(DISTINCT student_name) FROM exams WHERE student_name IS NOT NULL AND student_name <> ''")->fetchColumn();
    $latestExamTime = $pdo->query("SELECT MAX(created_at) FROM exams")->fetchColumn();
} catch (Exception $e) {
    $totalQuestions = $totalCategories = $totalExams = $todayExams = 0;
    $totalFinishedExams = $todayFinishedExams = $totalAnswers = $uniqueStudents = 0;
    $latestExamTime = '';
}
?>
<style>
  .stat-card { text-align: center; padding: 20px; }
  .stat-number { font-size: 32px; font-weight: bold; color: #009688; }
  .stat-label { font-size: 14px; color: #666; margin-top: 6px; }
  .welcome-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 28px; border-radius: 8px; }
  .feature-item { padding: 14px; border-left: 4px solid #009688; margin: 10px 0; background: #f8f9fa; }
  .quick-action { margin: 10px 6px; }
  .subtext { font-size: 12px; color: #999; margin-top: 6px; }
  .section-title { font-weight: 600; font-size: 16px; }
  .soft-card { box-shadow: 0 1px 3px rgba(0,0,0,.06); border: 1px solid #f0f0f0; }
  .muted { color: #9ca3af; }
  .stat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 15px; }
  .stat-card { aspect-ratio: 1 / 1; display: flex; flex-direction: column; justify-content: center; align-items: center; border-radius: 8px; }
  .quick-actions-bar { display:flex; flex-wrap:wrap; gap:10px; }
  .quick-actions-bar .layui-btn { border-radius: 10px; padding: 10px 14px; display:flex; align-items:center; gap:6px; box-shadow: 0 2px 6px rgba(0,0,0,.06); }
  .quick-actions-bar .layui-btn .layui-icon { font-size: 16px; }
  /* 电击感按钮 */
  .quick-actions-bar .btn-electric { position: relative; overflow: hidden; border: 1px solid rgba(0,0,0,0.06); color: #fff; text-shadow: 0 1px 0 rgba(0,0,0,.15); transition: transform .08s ease, box-shadow .2s ease, background .2s ease; }
  .quick-actions-bar .btn-electric:before { content: ""; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(255,255,255,.18), rgba(255,255,255,0) 40%); pointer-events: none; }
  .quick-actions-bar .btn-electric:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(0,0,0,.12), 0 0 0 3px rgba(0,150,136,.08); }
  .quick-actions-bar .btn-electric:active { transform: translateY(0); box-shadow: 0 2px 8px rgba(0,0,0,.15) inset, 0 2px 6px rgba(0,0,0,.08); }
  /* 颜色变体，贴近 layui 语义色 */
  .quick-actions-bar .btn-electric.layui-btn { background: linear-gradient(135deg, #16baaa, #0ea79a); box-shadow: 0 4px 12px rgba(22,186,170,.35); }
  .quick-actions-bar .btn-electric.layui-btn:hover { box-shadow: 0 8px 20px rgba(22,186,170,.45), 0 0 0 3px rgba(22,186,170,.18); }
  .quick-actions-bar .btn-electric.layui-btn-normal { background: linear-gradient(135deg, #1e9fff, #1188e8); box-shadow: 0 4px 12px rgba(30,159,255,.35); }
  .quick-actions-bar .btn-electric.layui-btn-normal:hover { box-shadow: 0 8px 20px rgba(30,159,255,.45), 0 0 0 3px rgba(30,159,255,.18); }
  .quick-actions-bar .btn-electric.layui-btn-warm { background: linear-gradient(135deg, #ffb800, #f59e0b); box-shadow: 0 4px 12px rgba(255,184,0,.35); color:#442e00; text-shadow:none; }
  .quick-actions-bar .btn-electric.layui-btn-warm:hover { box-shadow: 0 8px 20px rgba(255,184,0,.45), 0 0 0 3px rgba(255,184,0,.18); }
  .quick-actions-bar .btn-electric.layui-btn-danger { background: linear-gradient(135deg, #ff5722, #e64a19); box-shadow: 0 4px 12px rgba(255,87,34,.35); }
  .quick-actions-bar .btn-electric.layui-btn-danger:hover { box-shadow: 0 8px 20px rgba(255,87,34,.45), 0 0 0 3px rgba(255,87,34,.18); }
  .quick-actions-bar .btn-electric.layui-btn-primary { background: linear-gradient(135deg, #ffffff, #f5f7fa); color:#0f172a; border:1px solid #e5e7eb; box-shadow: 0 4px 12px rgba(0,0,0,.06); text-shadow:none; }
  .quick-actions-bar .btn-electric.layui-btn-primary:hover { box-shadow: 0 8px 20px rgba(59,130,246,.18), 0 0 0 3px rgba(59,130,246,.15); }
</style>

<div class="layui-fluid" style="padding: 20px;">
  <div class="layui-row">
    <div class="layui-col-md12">
      <div class="welcome-header">
        <h1 style="margin:0; font-size:24px;">欢迎使用在线考试管理系统</h1>
        <p style="margin:10px 0 0 0; font-size:14px; opacity:.9;">统一管理题库、考试生成、考生与答题数据</p>
      </div>
    </div>
  </div>

  <div class="layui-row layui-col-space15" style="margin-top: 20px;">
    <div class="layui-col-md12">
      <div class="layui-card">
        <div class="layui-card-header"><h3 class="section-title" style="margin:0;">快捷操作</h3></div>
        <div class="layui-card-body">
          <div class="quick-actions-bar">
            <button class="layui-btn btn-electric" onclick="parent.location.hash='generate_exam'"><i class="layui-icon layui-icon-add-1"></i><span>生成考试</span></button>
            <button class="layui-btn layui-btn-normal btn-electric" onclick="parent.location.hash='questions_manager'"><i class="layui-icon layui-icon-list"></i><span>管理题库</span></button>
            <button class="layui-btn layui-btn-warm btn-electric" onclick="parent.location.hash='exam_records'"><i class="layui-icon layui-icon-read"></i><span>考试记录</span></button>
            <button class="layui-btn layui-btn-danger btn-electric" onclick="parent.location.hash='list_user'"><i class="layui-icon layui-icon-user"></i><span>用户管理</span></button>
            <button class="layui-btn layui-btn-primary btn-electric" onclick="parent.location.hash='upload'"><i class="layui-icon layui-icon-upload-drag"></i><span>导入题库</span></button>
          </div>
          <p class="subtext" style="line-height:1.7;margin-top:8px;">
            提示：可在“生成考试”中配置抽题规则与时间限制
          </p>
        </div>
      </div>
    </div>
  </div>

  <div class="layui-row" style="margin-top: 20px;">
    <div class="layui-col-md12">
      <div class="stat-grid">
        <div class="layui-card soft-card stat-card">
          <div class="stat-number"><?php echo $totalQuestions; ?></div>
          <div class="stat-label">题库总量</div>
        </div>
        <div class="layui-card soft-card stat-card">
          <div class="stat-number"><?php echo $totalCategories; ?></div>
          <div class="stat-label">分类数量</div>
        </div>
        <div class="layui-card soft-card stat-card">
          <div class="stat-number"><?php echo $totalExams; ?></div>
          <div class="stat-label">考试总量</div>
        </div>
        <div class="layui-card soft-card stat-card">
          <div class="stat-number"><?php echo $todayExams; ?></div>
          <div class="stat-label">今日生成考试</div>
        </div>
        <div class="layui-card soft-card stat-card">
          <div class="stat-number"><?php echo $totalFinishedExams; ?></div>
          <div class="stat-label">已完成考试</div>
        </div>
        <div class="layui-card soft-card stat-card">
          <div class="stat-number"><?php echo $todayFinishedExams; ?></div>
          <div class="stat-label">今日完成考试</div>
        </div>
        <div class="layui-card soft-card stat-card">
          <div class="stat-number"><?php echo $totalAnswers; ?></div>
          <div class="stat-label">答题记录数</div>
        </div>
        <div class="layui-card soft-card stat-card">
          <div class="stat-number"><?php echo $uniqueStudents; ?></div>
          <div class="stat-label">累计考生数</div>
        </div>
      </div>
    </div>
  </div>

  <div class="layui-row layui-col-space15" style="margin-top: 15px;">
    <div class="layui-col-md12">
      <div class="layui-card soft-card" style="padding:18px;">
        <div class="section-title">最近一次创建考试时间</div>
        <div class="subtext" style="margin-top:6px;">
          <?php echo $latestExamTime ? htmlspecialchars($latestExamTime) : '暂无'; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="layui-col-md12">
      <div class="layui-card">
        <div class="layui-card-header"><h3 class="section-title" style="margin:0;">系统功能</h3></div>
        <div class="layui-card-body">
          <div class="feature-item">
            <h4 style="margin:0 0 6px 0;color:#333;">题库与分类</h4>
            <p class="muted" style="margin:0;">维护单选、多选、判断等题目类型，支持按分类管理与检索</p>
          </div>
          <div class="feature-item">
            <h4 style="margin:0 0 6px 0;color:#333;">考试生成</h4>
            <p class="muted" style="margin:0;">根据规则快速组卷，支持随机抽题与难度控制</p>
          </div>
          <div class="feature-item">
            <h4 style="margin:0 0 6px 0;color:#333;">考试记录</h4>
            <p class="muted" style="margin:0;">查看考试进度与成绩，统计完成情况与答题数据</p>
          </div>
          <div class="feature-item">
            <h4 style="margin:0 0 6px 0;color:#333;">数据统计</h4>
            <p class="muted" style="margin:0;">题库规模、考试次数、考生数量等核心指标一目了然</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
