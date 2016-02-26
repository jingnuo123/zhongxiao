<?php
/**
 * Created by PhpStorm.
 * User: AAPPED
 * Date: 2016/2/25 0025
 * Time: 下午 16:28
 */


define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
include_once(ROOT_PATH . 'includes/cls_image.php');
$image = new cls_image($_CFG['bgcolor']);
$exc   = new exchange($ecs->table("ad"), $db, 'ad_id', 'ad_name');

/* act操作项的初始化 */
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'list';
}
else
{
    $_REQUEST['act'] = trim($_REQUEST['act']);
}

if($_REQUEST['act']=='list'){
    $smarty->assign("list",get_media_list());
    $smarty->display("media_list.html");
}

if($_REQUEST['act']=='add_page'){
    $smarty->display('media_add.html');
}

if($_REQUEST['act']=='add'){
    $sql="INSERT INTO ecs_media (name,img_link,title1,link1,title2,link2,title3,link3) VALUES('{$_REQUEST['name']}','{$_REQUEST['img_link']}','{$_REQUEST['title1']}','{$_REQUEST['link1']}','{$_REQUEST['title2']}','{$_REQUEST['link2']}','{$_REQUEST['title3']}','{$_REQUEST['link3']}');";
//    echo var_dump($GLOBALS['db']->query($sql));
}

if($_REQUEST['cat']=='update'){

}

if($_REQUEST['act']=='delete'){

}

function get_media_list(){
    $sql="SELECT * from ecs_media;";
    return $GLOBALS['db']->getAll($sql);
}
