<?php
/**
 * Created by PhpStorm.
 * User: 焦正杉
 * Date: 2015/8/24 0024
 * Time: 下午 15:21
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

if(isset($_POST['cityCode'])){
    setCookie('cityCode',$_POST['cityCode'],time()+7*24*3600);
    exit();
}

if(isset($_COOKIE['cityCode'])){
    if($_COOKIE['cityCode']!='0') {
        $url = 'http://' . $_COOKIE['cityCode'] . '.mslink.com.cn/';
        echo json_encode(array('city'=>1,'url'=>$url));
    }else{
        echo json_encode(array('city'=>0));
    }
}else {
    $ch = curl_init();
    $url = 'http://apis.baidu.com/apistore/iplookupservice/iplookup?ip=' . real_ip();
    $header = array(
        'apikey: abd17885a2b28b8f75d86281a4db69f2',
    );
// 添加apikey到header
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// 执行HTTP请求
    curl_setopt($ch, CURLOPT_URL, $url);
    $res = curl_exec($ch);
    header("Access-Control-Allow-Origin: *");
    header("Content-Type:text/html;   charset=utf-8");
//echo real_ip().'<br>';
    $city = json_decode($res)->retData->city;

    $CityCode["天津"] = "tj";
    $CityCode["北京"] = "bj";

//echo $city;
    if (isset($CityCode[$city])) {

        $url = 'http://' . $CityCode[$city] . '.mslink.com.cn/';
        echo json_encode(array('city' => $city,'cityCode'=>$CityCode[$city], 'url' => $url));
    } else {
        echo json_encode(array('city' => 0, 'url' => $url));
    }
//echo '<a href="'.$url.'">'.$url.'</a>';
}