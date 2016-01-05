<?php

namespace Vendor\JDBundle;

use Vendor\ClientIPBundle\ClientIP;
use Vendor\JDBundle\libraries\LogUtil;
use Vendor\JDBundle\libraries\RSAUtils;
use Vendor\JDBundle\libraries\SignUtil;
use Vendor\JDBundle\libraries\DesUtils;
use Vendor\JDBundle\libraries\TDESUtil;
use Vendor\OrderSeniorBundle\OrderSenior;
use Vendor\RequestBundle\Request;
use Vendor\JDBundle\libraries\HttpUtils;

/**
 * Class JD 京东支付
 *
 * @author ZRONE <xujining2008@126.com>
 * @date   2016-01-01 19:32:26
 */
class JD
{
	public static $_merchant; // 商户号
	public static $_DES = "ta4E/aspLA3lgFGKmNDNRYU92RkZ4w2t";
	public static $_cert_type = "MD5"; // 证书类型
	public static $_MD5Key = "test";

	public static $_pay_syn_url = ""; // 支付异步回调URL
	public static $_pay_asyn_url = ""; // 支付成功同步通知URL
	public static $_refund_asyn_url = ""; // 退款异步回调URL
	public static $_fail_pay_syn_url = ""; // 支付失败通知URL

	public static $_serverPayUrl = 'https://plus.jdpay.com/nPay.htm'; // 支付提交URL
	public static $_serverRefundUrl = 'https://m.jdpay.com/wepay/refund'; //申请退款URL

	public function __construct( $config = array() )
	{
		self::$_merchant = trim( isset( $config[ 'merchant' ] ) ? $config[ 'merchant' ] : "" );
		self::$_DES = trim( isset( $config[ 'des' ] ) ? $config[ 'des' ] : "" );
		self::$_cert_type = strtoupper( trim( isset( $config[ 'certType' ] ) ? $config[ 'certType' ] : "" ) );
		self::$_MD5Key = trim( isset( $config[ 'md5_key' ] ) ? $config[ 'md5_key' ] : "" );
		self::$_pay_syn_url = trim( trim( isset( $config[ 'asynchronous' ] ) ? $config[ 'asynchronous' ] : "" ) );
		self::$_fail_pay_syn_url = trim( trim( isset( $config[ 'fsynchronous' ] ) ? $config[ 'fsynchronous' ] : "" ) );
		self::$_pay_asyn_url = trim( trim( isset( $config[ 'synchronous' ] ) ? $config[ 'synchronous' ] : "" ) );
		self::$_refund_asyn_url = trim( trim( isset( $config[ 'rsynchronous' ] ) ? $config[ 'synchronous' ] : "" ) );
	}

	/**
	 * 支付
	 *
	 * @param array $formData
	 *
	 * @return string
	 */
	public static function payHandle( array $formData )
	{
		$data = self::filterPayFormData( $formData );

		return self::formPaySubmit( $data );
	}

	/**
	 * 退款
	 *
	 * @param array $formRefundData
	 *
	 * @return string
	 */
	public static function refundHandle( array $formRefundData )
	{
		$data = self::filterRefundFormData( $formRefundData );

		return self::formRefundSumit( $data );
	}

	/**
	 * @zrone\NAME   异步支付
	 * @zrone\DETAIL 京东支付成功异步通知地址
	 *
	 * @return bool|string
	 */
	public static function payAsynNotify()
	{
		// 读取异步通知数据
		//		$result = file_get_contents( "php://input" );
		$XMLString = base64_decode( $_POST[ 'resp' ] );
		if( !empty( $XMLString ) ) {
			// 记录日志
			LogUtil::log( $XMLString . PHP_EOL, LogUtil::NOTIFY );
			$firstData = simplexml_load_string( $XMLString );

			$sign = md5( $firstData->VERSION . $firstData->MERCHANT . $firstData->TERMINAL . $firstData->DATA . self::$_MD5Key );

			if( $sign == $firstData->SIGN ) {
				$du = new DesUtils();
				$entityData = $du->decrypt( $firstData->DATA, self::$_DES );

				LogUtil::log( date( "Y-m-d H:i:s", $_SERVER[ 'REQUEST_TIME' ] ) . PHP_EOL . $entityData . PHP_EOL, LogUtil::SUCCESS );

				$xmlData = simplexml_load_string( $entityData );
				$trade = $xmlData->TRADE;

				$tradeData = array(
					'CARDHOLDERNAME'   => $trade->CARDHOLDERNAME,
					'CARDHOLDERMOBILE' => $trade->CARDHOLDERMOBILE,
					'CARDHOLDERID'     => $trade->CARDHOLDERID,
					'CARDNO'           => $trade->CARDNO,
					'CARDTYPE'         => $trade->CARDTYPE,
					'BANKCODE'         => $trade->BANKCODE,
					'PAYAMOUNT'        => $trade->PAYAMOUNT,
					'REMARK'           => $trade->REMARK,
					'TYPE'             => $trade->TYPE,
					'ID'               => $trade->ID,
					'AMOUNT'           => $trade->AMOUNT,
					'CURRENCY'         => $trade->CURRENCY,
					'DATE'             => $trade->DATE,
					'TIME'             => $trade->TIME,
					'NOTE'             => $trade->NOTE,
					'STATUS'           => $trade->STATUS
				);

				$return = $xmlData->RETURN;

				$returnData = array(
					'CODE' => $return->CODE,
					'DESC' => $return->DESC
				);

			} else {
				LogUtil::log( date( "Y-m-d H:i:s", $_SERVER[ 'REQUEST_TIME' ] ) . PHP_EOL . "异步通知失败, 验签失败!", LogUtil::ERROR );
			}

			echo 'success';

			return;
		}

		LogUtil::log( date( "Y-m-d H:i:s", $_SERVER[ 'REQUEST_TIME' ] ) . PHP_EOL . "异步通知失败!", LogUtil::ERROR );
	}

	/**
	 * 支付成功同步返回通知
	 *
	 * @return array|bool
	 */
	public static function paySynNotify()
	{
		$token = Request::get( 'token' ); // 用户身份表示
		$tradeNum = Request::get( 'tradeNum' ); // 交易流水号

		$data = array(
			'token'    => $token,
			'tradeNum' => $tradeNum
		);

		LogUtil::log( PHP_EOL . date( "Y-m-d H:i:s", $_SERVER[ 'REQUEST_TIME' ] ) . PHP_EOL . var_export( $data, TRUE ) . PHP_EOL, LogUtil::SUCCESS );

		return $data;
	}

	/**
	 * 支付失败同步返回通知
	 */
	public static function failPaySynNotify()
	{
		LogUtil::log( PHP_EOL . date( "Y-m-d H:i:s", $_SERVER[ 'REQUEST_TIME' ] ) . PHP_EOL . "支付失败", LogUtil::ERROR );
		echo "支付失败";
	}

	/**
	 * 封装退款form表单
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public static function formRefundSumit( array $data )
	{
		list( $returnCode, $returnContent ) = HttpUtils::http_post_data( self::$_serverRefundUrl, json_encode( $data ) );

		LogUtil::log( date( "Y-m-d H:i:s", $_SERVER[ 'REQUEST_TIME' ] ) . PHP_EOL . $returnContent, LogUtil::REFUND );
		$returnContent = json_decode( $returnContent, TRUE );

		//执行状态 成功
		if( $returnContent[ 'resultCode' ] == 0 ) {
			$mapResult = $returnContent [ 'resultData' ];

			//有返回数据
			if( NULL != $mapResult ) {
				$sign = $mapResult[ "sign" ];
				//1.解密签名内容
				$decryptStr = RSAUtils::decryptByPublicKey( $sign );

				//2.对data进行sha256摘要加密
				$sha256SourceSignString = hash( "sha256", $mapResult[ "data" ] );

				//3.比对结果
				if( $decryptStr == $sha256SourceSignString ) {
					//解密data
					$decrypData = TDESUtil::decrypt4HexStr( base64_decode( self::$_DES ), $mapResult[ "data" ] );

					//退款结果实体
					$resultData = json_decode( $decrypData, TRUE );

					//错误消息
					if( NULL == $resultData ) {
						LogUtil::log( date( "Y-m-d H:i:s", $_SERVER[ 'REQUEST_TIME' ] ) . PHP_EOL . $decrypData, LogUtil::ERROR );
					} else {
						LogUtil::log( date( "Y-m-d H:i:s", $_SERVER[ 'REQUEST_TIME' ] ) . PHP_EOL . var_export( $resultData, TRUE ), LogUtil::SUCCESS );
					}
				} else {
					LogUtil::log( date( "Y-m-d H:i:s", $_SERVER[ 'REQUEST_TIME' ] ) . PHP_EOL . "签名失败", LogUtil::ERROR );
				}
			}
		} else {
			//执行退款 失败
			LogUtil::log( date( "Y-m-d H:i:s", $_SERVER[ 'REQUEST_TIME' ] ) . PHP_EOL . $returnContent[ 'resultMsg' ], LogUtil::ERROR );
		}
	}

	/**
	 * 封装支付form表单
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public static function formPaySubmit( array $data )
	{
		$data[ 'serverPayUrl' ] = self::$_serverPayUrl;

		$formHtml = <<<eof
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF - 8">
    <title>JD支付</title>
</head>
<body>
    <form method='post' action='{$data['serverPayUrl']}' id='payForm'>
        <input type='hidden' name='version' value="{$data['version']}" />
        <input type='hidden' name='token' value='{$data['token']}' />
        <input type='hidden' name='merchantSign' value="{$data['merchantSign']}" />
        <input type='hidden' name='merchantNum' value="{$data['merchantNum']}" />
        <input type='hidden' name='merchantRemark' value="{$data['merchantRemark']}" />
        <input type='hidden' name='tradeNum' value="{$data['tradeNum']}" />
        <input type='hidden' name='tradeName' value="{$data['tradeName']}" />
        <input type='hidden' name='tradeDescription' value="{$data['tradeDescription']}" />
        <input type='hidden' name='tradeTime' value="{$data['tradeTime']}" />
        <input type='hidden' name='tradeAmount' value="{$data['tradeAmount']}" />
        <input type='hidden' name='currency' value="{$data['currency']}" />
        <input type='hidden' name='notifyUrl' value="{$data['notifyUrl']}" />
        <input type='hidden' name='successCallbackUrl' value="{$data['successCallbackUrl']}" />
        <input type='hidden' name='ip' value="{$data['ip']}" />
eof;

		if( isset( $data[ 'specifyInfoJson' ] ) ) {
			$formHtml .= <<<eof
        <input type='hidden' name='specifyInfoJson' value='{$data['specifyInfoJson']}' />
eof;
		}

		$formHtml .= <<<eof

    </form>
    <script type="text/javascript">
        document.getElementById('payForm').submit();
    </script>
</body>
</html>
eof;

		return $formHtml;
	}

	/**
	 * 封装支付表单参数
	 *
	 * @param array $formData
	 *
	 * @return array
	 */
	public static function filterPayFormData( array $formData )
	{
		$param = array();

		$param[ "currency" ] = 'CNY'; // 交易币种
		$param[ "ip" ] = ClientIP::getIp();
		$param[ 'merchantNum' ] = self::$_merchant; // 商户号
		$param[ "merchantRemark" ] = isset( $formData[ "merchantRemark" ] ) ? $formData[ "merchantRemark" ] : ""; // 请输入商户备注
		$param[ "notifyUrl" ] = self::$_pay_syn_url;
		$param[ "successCallbackUrl" ] = self::$_pay_asyn_url;
		$param[ "tradeAmount" ] = isset( $formData[ "tradeAmount" ] ) ? $formData[ "tradeAmount" ] : 0; // 交易金额
		$param[ "tradeDescription" ] = isset( $formData[ "tradeDescription" ] ) ? $formData[ "tradeDescription" ] : ""; // 交易描述
		$param[ "tradeName" ] = isset( $formData[ "tradeName" ] ) ? $formData[ "tradeName" ] : ""; // 商品名称
		$param[ "tradeNum" ] = isset( $formData[ "tradeNum" ] ) ? $formData[ "tradeNum" ] : ""; // 用户订单号
		$param[ "tradeTime" ] = date( 'Y-m-d H:i:s', $_SERVER[ 'REQUEST_TIME' ] );
		$param[ "version" ] = '1.1.5';
		$param[ "token" ] = isset( $formData[ "token" ] ) ? $formData[ "token" ] : ""; // 用户交易令牌 记录用户身份的标识

		if( isset( $formData[ "specBankCardNo" ] ) && !empty( $formData[ "specBankCardNo" ] ) && isset( $formData[ "specIdCard" ] ) && !empty( $formData[ "specIdCard" ] ) && isset( $formData[ "specName" ] ) && !empty( $formData[ "specName" ] ) ) {
			// 卡前置不为空
			$specialJson = array();

			$specialJson[ "specBankCardNo" ] = isset( $formData[ "specBankCardNo" ] ) ? $formData[ "specBankCardNo" ] : ""; // 卡号
			$specialJson[ "specIdCard" ] = isset( $formData[ "specIdCard" ] ) ? $formData[ "specIdCard" ] : ""; // 身份证号
			$specialJson[ "specName" ] = isset( $formData[ "specName" ] ) ? $formData[ "specName" ] : ""; // 姓名

			$param[ "specifyInfoJson" ] = json_encode( $specialJson );
		}

		$param[ "merchantSign" ] = SignUtil::signWithoutToHex( $param );

		return $param;
	}

	/**
	 * @zrone\NAME   封装退款表单参数
	 * @zrone\DETAIL 订单里需要加字段验证
	 *
	 * @param array $formData
	 *
	 * @return array
	 */
	public static function filterRefundFormData( array $formData )
	{
		$param = array();

		$param[ 'merchantNum' ] = self::$_merchant; // 商户号
		$param[ "version" ] = '1.0'; // 退款版本号

		$data = array(
			'tradeNum'      => isset( $formData[ "tradeNum" ] ) ? $formData[ "tradeNum" ] : "", // 用户订单号
			'oTradeNum'     => isset( $formData[ "oTradeNum" ] ) ? $formData[ "oTradeNum" ] : "", // 原始交易号
			'tradeAmount'   => isset( $formData[ "tradeAmount" ] ) ? $formData[ "tradeAmount" ] : 0, // 订单金额
			'tradeCurrency' => 'CNY', // 交易币种
			'tradeDate'     => date( "Ymd", $_SERVER[ 'REQUEST_TIME' ] ), //yyyyMMdd
			'tradeTime'     => date( "His", $_SERVER[ 'REQUEST_TIME' ] ), // HHmmss
			'tradeNotice'   => self::$_serverRefundUrl, // 交易通知地址(异步, 为空，查询交易是否成功时，进行操作)
			'tradeNote'     => isset( $formData[ "tradeNote" ] ) ? $formData[ "tradeNote" ] : "" // 交易备注
		);

		$param[ 'data' ] = TDESUtil::encrypt2HexStr( base64_decode( self::$_DES ), json_encode( $data ) );
		$tradeData = hash( "sha256", $param[ 'data' ] );
		$param[ "merchantSign" ] = RSAUtils::encryptByPrivateKey( $tradeData );

		return $param;
	}
}
