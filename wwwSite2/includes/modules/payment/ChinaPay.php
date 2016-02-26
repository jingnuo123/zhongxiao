<?php



/**

* 上海银联在线插件

*/



if (!defined('IN_ECS'))

{

die('Hacking attempt');

}



$payment_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/payment/chinapay.php';



include_once(ROOT_PATH ."includes/modules/payment/chinapay/netpayclient_config.php");

include_once(ROOT_PATH ."includes/modules/payment/chinapay/netpayclient.php");



if (file_exists($payment_lang))

{

global $_LANG;



include_once($payment_lang);

}



/* 模块的基本信息 */

if (isset($set_modules) && $set_modules == TRUE)

{

$i = isset($modules) ? count($modules) : 0;



/* 代码 */

$modules[$i]['code'] = basename(__FILE__, '.php');



/* 描述对应的语言项 */

$modules[$i]['desc'] = 'chinapay_desc';



/* 是否支持货到付款 */

$modules[$i]['is_cod'] = '0';



/* 是否支持在线支付 */

$modules[$i]['is_online'] = '1';



/* 支付费用 */

$modules[$i]['pay_fee'] = '0';



/* 作者 */

$modules[$i]['author'] = 'tolys';



/* 网址 */

$modules[$i]['website'] = 'http://www.chinapay.com';



/* 版本号 */

$modules[$i]['version'] = '1.0.0';



/* 配置信息 */

$modules[$i]['config'] = array(

array('name' => 'chinapay_account', 'type' => 'text', 'value' => '商户帐号'),

array('name' => 'chinapay_merkey_file', 'type' => 'text', 'value' => 'includes/modules/payment/chinapay/私有密钥.key'),

array('name' => 'chinapay_pubkey_file', 'type' => 'text', 'value' => 'includes/modules/payment/chinapay/公共密钥.key')

);



return;

}



/**

* 类

*/

class ChinaPay
{
	function ChinaPay()
	{
	}
	function __construct()
	{
		$this->chinapay();
	}

	/**
	* 生成支付代码
	* @param array $order 订单信息
	* @param array $payment 支付方式信息
	*/
	function get_code($order, $payment)
	{
		//$MerId = trim($payment['chinapay_account']);
		//$OrdId = ecshopsn2chinapaysn($order['order_sn'], $MerId);
		//生成订单号，定长16位，任意数字组合，一天内不允许重复，本例采用当前时间戳，必填
		$OrdId = $order['order_sn'];
		$OrdId = sprintf("%016s", $OrdId);
		//订单金额，定长12位，以分为单位，不足左补0，必填
		$TransAmt = formatamount($order['order_amount']);

		//$TransTime = date('His',time());
		//货币代码，3位，境内商户固定为156，表示人民币，必填
		$CuryId = '156'; 

		//$CountryId = "0081";
		//订单日期，本例采用当前日期，必填
		$TransDate = date('Ymd',time());
		//交易类型，0001 表示支付交易，0002 表示退款交易
		$TransType = '0001'; 
		//接口版本号，境内支付为 20070129，必填
		$Version = '20070129';
		//支付网关号，4位，上线时建议留空，以跳转到银行列表页面由用户自由选择，本示例选用0001农商行网关便于测试，可选
		$GateId = '';

		//$TimeZone = "+07";

		//$DSTFlag = "1";

		//$ExtFlag = "00";

		$data_vreturnurl = return_url(basename(__FILE__, '.php'));
		//备注，最长60位，交易成功后会原样返回，可用于额外的订单跟踪等，可选
		$Priv1 = $order['log_id'];//"test priv1"; 

		$merkey_file= trim($payment['chinapay_merkey_file']);

		//导入私钥文件, 返回值即为您的商户号，长度15位

		$MerId = buildKey(ROOT_PATH . $merkey_file);

		if(!$MerId) {
			echo "导入私钥文件失败！";
			exit;
		}



		//按次序组合订单信息为待签名串

		$plain = $MerId . $OrdId . $TransAmt . $CuryId . $TransDate . $TransType . $Priv1;
		//$plain = $merid . $ordid . $transamt . $curyid . $transdate . $transtype . $priv1;
		//生成签名值，必填

		$chkvalue = sign($plain);

		if (!$chkvalue) {
			echo "签名失败！";
			exit;
		}



		$def_url = "<br /><form style='text-align:center;' method=post action='".REQ_URL_PAY."' target='_blank'>";

		$def_url .= "<input type=HIDDEN name='MerId' value='".$MerId."'/>"; 

		$def_url .= "<input type=HIDDEN name='Version' value='".$Version."'>";

		$def_url .= "<input type=HIDDEN name='OrdId' value='".$OrdId."'>";

		$def_url .= "<input type=HIDDEN name='TransAmt' value='".$TransAmt."'>";

		$def_url .= "<input type=HIDDEN name='CuryId' value='".$CuryId."'>"; 

		$def_url .= "<input type=HIDDEN name='TransDate' value='".$TransDate."'>";

		$def_url .= "<input type=HIDDEN name='TransType' value='".$TransType."'>";

		$def_url .= "<input type=HIDDEN name='BgRetUrl' value='".$data_vreturnurl."'>";

		$def_url .= "<input type=HIDDEN name='PageRetUrl' value='".$data_vreturnurl."'>";

		$def_url .= "<input type=HIDDEN name='GateId' value='".$GateId."'>";

		$def_url .= "<input type=hidden name='Priv1' value='".$Priv1."'>"; 

		$def_url .= "<input type=HIDDEN name='ChkValue' value='".$chkvalue."'>";

		$def_url .= "<input type=submit value='" .$GLOBALS['_LANG']['pay_button']. "'>";

		$def_url .= "</form>";



		return $def_url;

	}



	/**
	* 响应操作
	*/
	function respond()
	{
		$payment = get_payment(basename(__FILE__, '.php'));
		$merid = trim($_POST['merid']);

		$orderno = trim($_POST['orderno']);

		$transdate = trim($_POST['transdate']);

		$amount = trim($_POST['amount']);

		$currencycode = trim($_POST['currencycode']);

		$transtype = trim($_POST['transtype']);

		$status = trim($_POST['status']);

		$checkvalue = trim($_POST['checkvalue']);

		$v_gateid = trim($_POST['GateId']);

		$v_Priv1 = trim($_POST['Priv1']);

		//重新计算密钥的值

		$pubkey = $payment['chinapay_pubkey_file'];

		$PGID = buildKey(ROOT_PATH . $pubkey);
		if(!$PGID) {

			echo "导入私钥文件失败！";

			exit;

		}
		

		$verify = verifyTransResponse($merid, $orderno, $amount, $currencycode, $transdate, $transtype, $status, $checkvalue);
		if (!$verify) {

		echo "验证签名失败！";

		exit;

		}

		/* 检查秘钥是否正确 */

		if ($status == '1001')

		{

		//$v_ordesn = chinapaysn2ecshopsn($orderno);

		//$order_id = get_order_id_by_sn($v_ordesn);

		/* 改变订单状态 */
		order_paid($v_Priv1);

		return true;

		}

		else

		{

		return false;

		}

	}

}





/*

*本地订单号转为银联订单号

*/

function ecshopsn2chinapaysn($order_sn, $vid){

if($order_sn && $vid){

$sub_vid = substr($vid, 10, 5);

$sub_start = substr($order_sn, 2, 4);

$sub_end = substr($order_sn, 6);

$temp = $pay_id;

return $sub_start . $sub_vid . $sub_end;

}

}



/*

*银联订单号转为本地订单号

*/

function chinapaysn2ecshopsn($chinapaysn){

if($chinapaysn){ 

$year = date('Y',time());



return substr($year,0,2) . substr($chinapaysn, 0, 4) . substr($chinapaysn, 9) ;

}

}



/*

*格式化交易金额，以分位单位的12位数字。

*/

function formatamount($amount){

	if($amount){

		//if(!strstr($amount, ".")){

		//$amount = $amount.".00";

		//}
		$amount = $amount * 100;

		$amount = str_replace(".", "", $amount);

		$temp = $amount;

		for($i=0; $i< 12 - strlen($amount); $i++){

		$temp = "0" . $temp;

		}

		return $temp;

	}

}

?>
