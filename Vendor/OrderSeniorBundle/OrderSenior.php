<?php

/**
 * Created by PhpStorm.
 * User: zrone
 * Date: 16/1/5
 * Time: 14:39
 */
namespace Vendor\OrderSeniorBundle;

class OrderSenior
{
	public static function getOrderSn()
	{
		$year_code = array( '2010', '2011', '2012', '2013', '2014', '2015', '2016', '2017', '2018', '2019', '2020' );
		$order_sn = $year_code[ intval( date( 'Y' ) ) - 2010 ] . date( 'm' ) . date( 'd' ) . substr( time(), - 5 ) . substr( microtime(), 2, 5 ) . sprintf( '%d', rand( 0, 99 ) );

		return $order_sn;
	}
}