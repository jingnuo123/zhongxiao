<?php
define('IN_ECS', true);
require('../includes/init.php');
require('../includes/lib_order.php');
include_once('../includes/lib_payment.php');
error_reporting(E_ALL ^ E_NOTICE);
$oid =  intval($_GET['oid']);
$order = $db->getRow("SELECT * FROM " . $ecs->table('order_info') . " WHERE order_id = $oid");
//if($order['pay_status'] == 2) exit('is payed');
if ($order['order_amount'] > 0){
	$payment = payment_info($order['pay_id']);
	include_once('../includes/modules/payment/' . $payment['pay_code'] . '.php');
	$pay_obj    = new $payment['pay_code'];
	echo $pay_obj->get_code($order, unserialize_config($payment['pay_config']));
}else{
	echo 1;
}