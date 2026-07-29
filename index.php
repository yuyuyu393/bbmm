<?php
// 【核心代理目标】
$target_url = "https://ygxian.jiuzhe.com.cn"; 
// 【刚才截图中抓到的罪魁祸首：隐藏中转域名】
$hidden_url = "https://ygxian.jiuzhe.com.cn";

$request_uri = $_SERVER['REQUEST_URI'];
$fetch_url = rtrim($target_url, '/') . $request_uri;

if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            } else if ($name == "CONTENT_TYPE") {
                $headers["Content-Type"] = $value;
            } else if ($name == "CONTENT_LENGTH") {
                $headers["Content-Length"] = $value;
            }
        }
        return $headers;
    }
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $fetch_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_ENCODING, ""); 

$method = $_SERVER['REQUEST_METHOD'];
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

if ($method === 'POST' || $method === 'PUT') {
    $post_data = file_get_contents('php://input');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
}

$headers = [];
$target_host = parse_url($target_url, PHP_URL_HOST);
foreach (getallheaders() as $name => $value) {
    $name_lower = strtolower($name);
    // 强制伪装来源
    if ($name_lower === 'host') {
        $headers[] = "Host: " . $target_host;
    } elseif ($name_lower === 'referer' || $name_lower === 'origin') {
        $headers[] = "$name: $target_url"; 
    } elseif ($name_lower === 'accept-encoding') {
        continue;
    } elseif ($name_lower !== 'content-length' && $name_lower !== 'host') { 
        $headers[] = "$name: $value";
    }
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header_str = substr($response, 0, $header_size);
$body = substr($response, $header_size);
curl_close($ch);

$header_lines = explode("\r\n", $header_str);
foreach ($header_lines as $line) {
    // 拦截后端的重定向（301/302），将其重写为我们的代理域名
    if (stripos($line, 'Location:') === 0) {
        $location = trim(substr($line, 9));
        $current_domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
        $location = str_replace([$target_url, $hidden_url], $current_domain, $location);
        header("Location: " . $location);
        continue;
    }
    if (stripos($line, 'Set-Cookie:') === 0 || stripos($line, 'Content-Type:') === 0) {
        header($line, false);
    }
}

// 【终极必杀：对网页内容和 JS 接口进行全网深度替换】
$current_domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$current_host = $_SERVER['HTTP_HOST'];

// 替换他明面上的域名
$body = str_replace($target_url, $current_domain, $body);
$body = str_replace("ygxian.jiuzhe.com.cn", $current_host, $body);

// 替换隐藏在暗处的坑人中转域名
$body = str_replace($hidden_url, $current_domain, $body);
$body = str_replace("dsfsdhg.jiuzhe.com.cn", $current_host, $body);

echo $body;
?>