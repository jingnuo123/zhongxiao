<?php

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}
include 'weixin/phpqrcode.php'; 
$payment_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/payment/weixin.php';

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
    $modules[$i]['code']    = basename(__FILE__, '.php');

    /* 描述对应的语言项 */
    $modules[$i]['desc']    = 'weixin_desc';

    /* 是否支持货到付款 */
    $modules[$i]['is_cod']  = '0';

    /* 是否支持在线支付 */
    $modules[$i]['is_online']  = '1';

    /* 作者 */
    $modules[$i]['author']  = '68ecshop';

    /* 网址 */
    $modules[$i]['website'] = '';

    /* 版本号 */
    $modules[$i]['version'] = '1.0.0';

    /* 配置信息 */
    $modules[$i]['config']  = array(
        array('name' => 'appId',           'type' => 'text',   'value' => ''),
        array('name' => 'appSecret',               'type' => 'text',   'value' => ''),
        array('name' => 'paySignKey',           'type' => 'text',   'value' => ''),
        array('name' => 'partnerId',       'type' => 'text', 'value' => ''),
        array('name' => 'partnerKey',    'type' => 'text', 'value' => ''),
        //array('name' => 'is_instant',               'type' => 'select', 'value' => '0')
        //array('name' => 'alipay_pay_method',        'type' => 'select', 'value' => '')
    );

    return;
}

/**
 * 类
 */
class weixin
{

    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function weixin()
    {
    }

    function __construct()
    {
        $this->weixin();
    }

    /**
     * 生成支付代码
     * @param   array   $order      订单信息
     * @param   array   $payment    支付方式信息
     */
    function get_code($order, $payment)
    {
    	define(APPID , $payment['appId']);  //appid
    	define(APPKEY ,$payment['paySignKey']); //paysign key
    	define(SIGNTYPE, "sha1"); //method
    	define(PARTNERKEY,$payment['partnerKey']);//通加密串
    	define(APPSERCERT, $payment['appSecret']);
    	if(!strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
			return $this->natpayHtml($order);
		}
		include_once("weixin/WxPayHelper.php");
    	$commonUtil = new CommonUtil();
		$wxPayHelper = new WxPayHelper();
		$url = return_url('weixin');
		$wxPayHelper->setParameter("bank_type", "WX");
		$wxPayHelper->setParameter("body", $order['order_sn']);
		$wxPayHelper->setParameter("partner", $payment['partnerId']);
		$wxPayHelper->setParameter("out_trade_no", $order['order_id']);
		$wxPayHelper->setParameter("total_fee", $order['order_amount']*100);
		$wxPayHelper->setParameter("fee_type", "1");
		$wxPayHelper->setParameter("notify_url", $url);
		$wxPayHelper->setParameter("spbill_create_ip", real_ip());
		$wxPayHelper->setParameter("input_charset", "GBK");
    	$return = '
    		<div style="text-align:center">
    			<form><input type="submit" value="微信安全支付" id="getBrandWCPayRequest"></form>
    			<script type="text/javascript">
				document.addEventListener(\'WeixinJSBridgeReady\', function onBridgeReady() {
     				WeixinJSBridge.invoke(\'getBrandWCPayRequest\','.$wxPayHelper->create_biz_package().',function(res){
				        if(res.err_msg == "get_brand_wcpay_request:ok" ) {
				            window.location.href = \''.$url.'\';
				        } else {
				            alert(res.err_msg);
				        }
				     });
				}, false)</script>
    		<div>';
    	$return .= '<script src="http://res.mail.qq.com/mmr/static/lib/js/jquery.js"></script>
				<script src="http://res.mail.qq.com/mmr/static/lib/js/lazyloadv3.js"></script>';
    	return $return;
    }

    /**
     * 响应操作
     */
    function respond(){
    	if (!empty($_POST)){
    		foreach($_POST as $key => $data){
    			$_GET[$key] = $data;
    		}
    	}
    	$array = $_GET;
    	$payment  = get_payment($_GET['code']);
    	$order_sn = intval($array['out_trade_no']);
    	$log_id = $GLOBALS['db']->getOne("SELECT log_id FROM ".$GLOBALS['ecs']->table('pay_log')."where order_id='{$order_sn}' and is_paid=0 order by log_id desc");
    	if($array['partner'] != $payment['partnerId']){
    		$this->addLog(array(),4);
    		return true;
    	}
    	/* 检查支付的金额是否相符 */
    	if (!check_money($log_id, round($array['total_fee']/100,2)))
    	{
    		$this->addLog($array,2);
    		return true;
    	}
    	order_paid($log_id, 2);
    	$this->addLog($array,1);
		include_once("Wechat.php");
    	$wechat = new Wechat();
		$post = $wechat->getXmlArray();
		$this->addLog($post,10);
		$parameter = array(
				'appid' => $payment['appId'],
				'openid' => $post['openid'], // 购买用户的 OpenId，这个已经放在最终支付结果通知的 PostData 里了
				'transid' => $array['transaction_id'], // 交易单号
				'out_trade_no' => $order_sn, // 本站订单号
				'deliver_timestamp' => mktime(), // 发货时间戳，这里指得是 linux 时间戳
				'deliver_status' => '1', // 发货状态，1 表明成功，0 表明失败，失败时需要在 deliver_msg 填上失败原因
				'deliver_msg' => 'ok' // 是发货状态信息，失败时可以填上 UTF8 编码的错误提示信息，比如“该商品已退款”
		);
		//$this->addLog($parameter,11);
		$result = $wechat->delivernotify($payment, $parameter);
    	return true;
    	//@todo 有新文档的时候在完善
    	include_once("Wechat.php");
    	$wechat = new Wechat();
    	$verifyNotify = $wechat->verifyNotify($array, $payment); // 验证通知
    	if ($verifyNotify) {
    		$array += $wechat->getXmlArray(); // 再获取xml数据
    		$order_sn = intval($array['out_trade_no']);
    		$log_id = $GLOBALS['db']->getOne("SELECT log_id FROM ".$GLOBALS['ecs']->table('pay_log')."where order_id='{$order_sn}' and is_paid=0 order by log_id desc");
    		/* 检查支付的金额是否相符 */
    		if (!check_money($log_id, $array['total_fee']))
    		{
    			$this->addLog($array,2);
    			return false;
    		}
    		order_paid($log_id, 2);
    		$this->addLog($array,1);
    		return true;
    		//发货
    		$parameter = array(
    				'appid' => $payment['appId'],
    				'openid' => $array['openid'], // 购买用户的 OpenId，这个已经放在最终支付结果通知的 PostData 里了
    				'transid' => $array['transaction_id'], // 交易单号
    				'out_trade_no' => $order_sn, // 本站订单号
    				'deliver_timestamp' => mktime(), // 发货时间戳，这里指得是 linux 时间戳
    				'deliver_status' => '1', // 发货状态，1 表明成功，0 表明失败，失败时需要在 deliver_msg 填上失败原因
    				'deliver_msg' => 'ok' // 是发货状态信息，失败时可以填上 UTF8 编码的错误提示信息，比如“该商品已退款”
    		);
    		$result = $wechat->delivernotify($payment, $parameter);
    		if ($result['errcode']) { // 判断结果

    		} else {
    			//@todo add log
    			return false;
    		}
    		return true;
    	}
    	$this->addLog($payment,3);
    	return false;
    }
    function addLog($other=array(),$type=1){
    	//$log['get'] = $_GET;
    	//$log['post'] = $_POST;
    	//$log['server'] = $_SERVER;
    	$log['other'] = $other;
    	$log = serialize($log);
    	return $GLOBALS['db']->query("INSERT INTO `weixin_paylog` (`log`,`type`) VALUES ('$log','$type')");
    }
	//生成原生支付二维码
	function natpayHtml($order){
		if(!strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
			include_once("Wechat.php");
			$wechat = new Wechat();
			$arr = array(
				'appid'=>APPID,
				'timestamp'=>mktime(),
				'noncestr'=>uniqid(),
				'productid'=>$order['order_id']
			);
			$arr['sign'] = $wechat->buildSign($arr, array('paySignKey'=>APPKEY));
			$wxurl = "weixin://wxpay/bizpayurl?";
			foreach ($arr as $k => $v) {
				$wxurl .= strtolower($k) . "=" . $v . "&";
			}
			$wxurl = rtrim($wxurl,"&");
			return scerweima(urlencode($wxurl));
		}
	}
	
}

?>