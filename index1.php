<?php
session_start();
include('includes/common.php');

// 获取所有考试
// $exams = $pdo->query("SELECT id, title, description FROM exams WHERE 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// 获取所有题目分类
$categories = $pdo->query("SELECT id, name FROM question_categories")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>考试系统</title>
    <?php include('admin/head.php');?>
    <style>
        body { max-width: 1000px; margin: 30px auto; padding: 0 15px; }
        .card { margin-bottom: 20px; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card-header { padding: 15px 20px; background: #f2f2f2; font-weight: bold; font-size: 16px; }
        .card-body { padding: 20px; background: #fff; }
        .mode-card { display: flex; margin-bottom: 20px; border-radius: 8px; overflow: hidden; box-shadow: 0 3px 10px rgba(0,0,0,0.08); }
        .mode-card .mode-icon { width: 100px; height: 100px; display: flex; align-items: center; justify-content: center; font-size: 40px; color: #fff; }
        .mode-card .mode-info { flex: 1; padding: 15px 20px; }
        .mode-card h3 { margin: 0 0 10px 0; }
        .mode-card p { margin: 0 0 15px 0; color: #666; }
        .mode-card .layui-btn { margin-right: 10px; }
        .exam-mode .mode-icon { background: #1e9fff; }
        .practice-mode .mode-icon { background: #5fb878; }
        .exam-list { border: 1px solid #e6e6e6; background-color: #fff; padding: 15px; border-radius: 8px; }
        .exam-item { padding: 10px 15px; border-bottom: 1px solid #eee; }
        .exam-item:last-child { border-bottom: none; }
        .exam-item .title { font-weight: bold; }
        .exam-item .desc { color: #666; font-size: 14px; margin-top: 5px; }
    </style>
</head>
<body>

<div class="layui-container">
    <blockquote class="layui-elem-quote">欢迎使用考试系统</blockquote>
    
    <!-- 模式选择 -->
    <div class="card">
        <div class="card-header">选择模式</div>
        <div class="card-body">
            <!-- 考试模式卡片 -->
            <div class="mode-card exam-mode">
                <div class="mode-icon">
                    <i class="layui-icon layui-icon-form"></i>
                </div>
                <div class="mode-info">
                    <h3>考试模式</h3>
                    <p>正式考试，有时间限制，需一次性完成所有题目并提交，提交后显示成绩</p>
                    <a href="#exam-section" class="layui-btn">查看可用考试</a>
                </div>
            </div>
            
            <!-- 练习模式卡片 -->
            <div class="mode-card practice-mode">
                <div class="mode-icon">
                    <i class="layui-icon layui-icon-read"></i>
                </div>
                <div class="mode-info">
                    <h3>练习模式</h3>
                    <p>按分类练习，单题展示，做完立即判对错，显示解析，记录正确率</p>
                    <a href="practice_mode.php" class="layui-btn layui-btn-normal">进入练习模式</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 考试列表 -->
    <div class="card" id="exam-section">
        <div class="card-header">可用考试</div>
        <div class="card-body">
            <?php if (count($exams) > 0): ?>
                <div class="exam-list">
                    <?php foreach ($exams as $exam): ?>
                        <div class="exam-item">
                            <div class="title"><?= htmlspecialchars($exam['title']) ?></div>
                            <?php if (!empty($exam['description'])): ?>
                                <div class="desc"><?= htmlspecialchars($exam['description']) ?></div>
                            <?php endif; ?>
                            <div class="actions" style="margin-top: 10px;">
                                <a href="exam.php?id=<?= $exam['id'] ?>" class="layui-btn layui-btn-xs">进入考试</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="layui-card-body">
                    <p>暂无可用考试</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
layui.use(['layer'], function(){
    var layer = layui.layer;
});
</script>
</body>
</html>