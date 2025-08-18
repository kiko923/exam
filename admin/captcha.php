<?php
session_start();
header('Content-type: image/png');

$code = substr(str_shuffle('1'), 0, 4);
$_SESSION['captcha_code'] = $code;

$im = imagecreatetruecolor(80, 30);
$bg = imagecolorallocate($im, 255, 255, 255);
$font_color = imagecolorallocate($im, 0, 0, 0);
imagefilledrectangle($im, 0, 0, 80, 30, $bg);
imagestring($im, 5, 18, 7, $code, $font_color);
imagepng($im);
imagedestroy($im);
