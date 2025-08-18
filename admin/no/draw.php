<?php
// 数据库连接
// $pdo = new PDO('mysql:host=localhost;dbname=exam;charset=utf8mb4', 'exam', 'lyz599..');
include('../includes/common.php');
// 获取所有分类
$categories = $pdo->query("SELECT id, name FROM question_categories")->fetchAll(PDO::FETCH_ASSOC);

// 抽题逻辑
$questions = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<p>已提交表单，准备抽题...</p>"; // 测试语句
    $catId = intval($_POST['category_id']);
    $limit = intval($_POST['limit']);

    $stmt = $pdo->prepare("SELECT * FROM questions WHERE category_id = ? ORDER BY RAND() LIMIT ?");
    $stmt->bindValue(1, $catId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();

    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>抽题系统</title>
</head>
<body>
<h2>题库抽题系统</h2>

<form method="POST">
    <label>选择题库分类：</label>
    <select name="category_id" required>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <label>抽取题目数量：</label>
    <input type="number" name="limit" value="10" min="1" required><br><br>

    <button type="submit">开始抽题</button>
</form>

<?php if (!empty($questions)): ?>
    <hr>
    <h3>抽取结果：</h3>
    <ol>
        <?php foreach ($questions as $q): ?>
            <li>
                <strong>[<?= $q['type'] ?>]</strong> <?= htmlspecialchars($q['question']) ?><br>
                <?php if ($q['type'] !== '填空题'): ?>
                    A. <?= htmlspecialchars($q['option_a']) ?><br>
                    B. <?= htmlspecialchars($q['option_b']) ?><br>
                    C. <?= htmlspecialchars($q['option_c']) ?><br>
                    D. <?= htmlspecialchars($q['option_d']) ?><br>
                <?php endif; ?>
                <em>正确答案：</em> <?= htmlspecialchars($q['answer']) ?><br>
                <em>解析：</em> <?= htmlspecialchars($q['explanation']) ?><br><br>
            </li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>
</body>
</html>
