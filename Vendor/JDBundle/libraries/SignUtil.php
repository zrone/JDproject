<?php

namespace Vendor\JDBundle\libraries;

use Vendor\JDBundle\libraries\RSAUtils;
use Vendor\JDBundle\libraries\LogUtil;

class SignUtil
{

	public static $unSignKeyList = array(
		"merchantSign",
		"version",
		"successCallbackUrl",
		"forPayLayerUrl"
	);

	public static function signWithoutToHex( $params )
	{
		ksort( $params );
		$sourceSignString = SignUtil::signString( $params, SignUtil::$unSignKeyList );

		LogUtil::log( PHP_EOL . date( "Y-m-d H:i:s", $_SERVER[ 'REQUEST_TIME' ] ) . $sourceSignString . PHP_EOL, LogUtil::OTHER );

		$sha256SourceSignString = hash( "sha256", $sourceSignString, TRUE );

		//		LogUtil::log( $sha256SourceSignString, LogUtil::OTHER );

		return RSAUtils::encryptByPrivateKey( $sha256SourceSignString );
	}

	public static function sign( $params )
	{
		ksort( $params );
		$sourceSignString = SignUtil::signString( $params, SignUtil::$unSignKeyList );

		LogUtil::log( $sourceSignString, LogUtil::OTHER );

		$sha256SourceSignString = hash( "sha256", $sourceSignString );

		//LogUtil::log( $sha256SourceSignString, LogUtil::OTHER );

		return RSAUtils::encryptByPrivateKey( $sha256SourceSignString );
	}

	public static function signString( $params, $unSignKeyList )
	{
		// 拼原String
		$sb = "";
		// 删除不需要参与签名的属性
		foreach( $params as $k => $arc ) {
			for( $i = 0; $i < count( $unSignKeyList ); $i ++ ) {
				if( $k == $unSignKeyList [ $i ] ) {
					unset ( $params [ $k ] );
				}
			}
		}

		foreach( $params as $k => $arc ) {
			$sb = $sb . $k . "=" . ( $arc == NULL ? "" : $arc ) . "&";
		}
		// 去掉最后一个&
		$sb = substr( $sb, 0, - 1 );

		return $sb;
	}
}

?>