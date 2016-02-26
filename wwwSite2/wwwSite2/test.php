<?php
/**
 * Created by PhpStorm.
 * User: 焦正杉
 * Date: 2015/8/24 0024
 * Time: 下午 15:21
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

$ch = curl_init();
$url = 'http://apis.baidu.com/apistore/iplookupservice/iplookup?ip='.real_ip();
$header = array(
    'apikey: abd17885a2b28b8f75d86281a4db69f2',
);
// 添加apikey到header
curl_setopt($ch, CURLOPT_HTTPHEADER  , $header);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// 执行HTTP请求
curl_setopt($ch , CURLOPT_URL , $url);
$res = curl_exec($ch);
header("Content-Type:text/html;   charset=utf-8");
//echo real_ip().'<br>';
$city=json_decode($res)->retData->city;


//echo $city;
$url='http://'.$CityCode[$city].'.10splan.com/';
//echo '<a href="'.$url.'">'.$url.'</a>';
echo json_encode(array('city'=>$city,'url'=>$url));