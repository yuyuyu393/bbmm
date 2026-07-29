<?php
// 把下面双引号里的网址改成红域名，比如 "https://anvna.cn"
$target_url = "https://anv.anvna.cn/"; 

$request_uri = $_SERVER['REQUEST_URI'];
$fetch_url = rtrim($target_url, '/') . $request_uri;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $fetch_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
if(isset($_SERVER['HTTP_USER_AGENT'])) {
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
}

$response = curl_exec($ch);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($content_type) {
    header("Content-Type: $content_type");
}

$current_domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$response = str_replace($target_url, $current_domain, $response);

echo $response;
?>