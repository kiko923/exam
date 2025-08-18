<?php
// Step1：从接口获取登录跳转地址
$appid = '1001';
$appkey = '2e9565167d1d6898348b6866cb30b717';
$type = 'wx'; // 微信登录

// 自动获取当前协议
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' 
            || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

// 当前域名（包含端口号如果有）
$host = $_SERVER['HTTP_HOST'];

// 拼接回调地址（根目录下的 social_callback.php）
$redirect_uri = $protocol . $host . '/callback.php?mode=login';

// URL编码
$redirect_uri_encoded = urlencode($redirect_uri);

// 测试输出
// echo $redirect_uri;
// echo $redirect_uri_encoded;



$api = "https://login.yzcdn.net/connect.php?act=login&appid={$appid}&appkey={$appkey}&type={$type}&redirect_uri={$redirect_uri}";

// 获取返回的 JSON
$response = file_get_contents($api);
$data = json_decode($response, true);
// echo json_encode($data,448);exit;
if (!isset($data['code']) || $data['code'] != 0 || empty($data['url'])) {
    exit('获取登录地址失败，请稍后再试');
}

// 跳转到返回的url字段
header("Location: " . $data['url']);
exit;

