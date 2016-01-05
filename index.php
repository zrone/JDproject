<?php

/**
 * Created by PhpStorm.
 *
 * @author: zrone <xujining2008@126.com>
 * @date  : 2016/1/2
 * @time  : 21:21
 */

use Vendor\JDBundle\JD;
use Vendor\JDBundle\libraries\Cache;

require_once( "zroneFrameWork.loader.php" );

Cache::noCache();


$JD = new JD( array(
	'merchant'     => '22294531',
	'des'          => 'ta4E/aspLA3lgFGKmNDNRYU92RkZ4w2t',
	'certType'     => 'RSA',
	'md5_key'      => 'test',
	'asynchronous' => 'http://www.baidu.com',
	'synchronous'  => 'http://www.baidu.com',
	'rsynchronous' => ''
) );

$data = array(
	'merchantRemark'   => '还是哇哈哈好喝',
	'tradeAmount'      => 1,
	'tradeNum'         => \Vendor\OrderSeniorBundle\OrderSenior::getOrderSn(),
	'tradeDescription' => '我玩玩',
	'tradeName'        => '可口可乐',
	'token'            => ''
);

//$data = array(
//	'oTradeNum' => '20160105897657705335',
//	'tradeAmount' => 1,
//	'tradeNote' => '我要退款'
//);

/**
 * create table jdpay_token(
 * id int(11) not null auto_increment primary key,
 * userid int(11) not null comment '用户ID',
 * jdtoken varchar(128) default '' comment 'JD支付返回token',
 * constraint `fk_userid` foreign key(`userid`) references user(`id`) on update cascade on delete cascade
 * ) ENGINE=INNODB default charset=utf8;
 **/

// JD支付退款
//echo $JD::refundHandle( $data );

// JD支付支付
echo $JD::payHandle( $data );