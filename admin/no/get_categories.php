<?php
include('../includes/common.php');
header('Content-Type: application/json; charset=utf-8');

$stmt = $pdo->query("SELECT id, name FROM question_categories ORDER BY id ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($categories);
