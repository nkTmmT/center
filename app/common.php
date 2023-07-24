<?php
// 应用公共文件
/**
 * 使用curl发送http请求
 * @param $url
 * @param $is_post
 * @param $params
 * @param $header
 * @param $time_out
 * @return bool|string
 */
function curlRequest($url, $is_post = false, $params = [], $header = false, $time_out = 60){

    $ch = curl_init();//初始化curl
    if ($is_post) {
        curl_setopt($ch, CURLOPT_POST, $is_post);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    }else{
        $url = $url.'?'.http_build_query($params);
    }
    // URL
    curl_setopt($ch, CURLOPT_URL, $url);
    // 设置是否返回response header
    curl_setopt($ch, CURLOPT_HEADER, 0);
    // 要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //当需要通过curl_getinfo来获取发出请求的header信息时，该选项需要设置为true
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $time_out);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $time_out);
    curl_setopt($ch, CURLOPT_FAILONERROR, false);
    if (1 == strpos('$'.$url, "https://")) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }

    if ($header) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}


function getLocalIp()
{
    exec("ipconfig", $out, $stats);
    $ip = '';
    if (!empty($out)) {
        foreach ($out as $k => $row) {
            if (isset($row) && (strstr($row, ' 192.') || strstr($row, ' 10.') || strstr($row, ' 172.') || strstr($row, ':10.') || strstr($row, ':172.'))) {
                $temp = ltrim($row);
                $data = explode(' ', $temp);
                $pos = strpos($data[1], ':');
                $ip = $pos ? substr($data[1], $pos + 1) : $data[1];
                break;
            }
        }
    }
    return $ip;
}
