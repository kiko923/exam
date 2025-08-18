<?php
session_start();
include('../includes/common.php');

$appid = '1001';
$appkey = '2e9565167d1d6898348b6866cb30b717';
$type = 'wx';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' 
            || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$redirect_uri = $protocol . $host . '/callback.php?mode=bind';
$redirect_uri_encoded = urlencode($redirect_uri);

$api = "https://login.yzcdn.net/connect.php?act=login&appid={$appid}&appkey={$appkey}&type={$type}&redirect_uri={$redirect_uri_encoded}";

$response = file_get_contents($api);
$data = json_decode($response, true);

if (!isset($data['code']) || $data['code'] != 0 || empty($data['url'])) {
    echo json_encode(['code' => 1, 'msg' => '获取登录地址失败，请稍后再试']);
    exit;
}

echo json_encode(['code' => 0, 'url' => $data['url']]);
