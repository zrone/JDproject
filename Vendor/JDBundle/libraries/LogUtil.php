<?php

namespace Vendor\JDBundle\libraries;

class LogUtil
{
	const ERROR   = 0;
	const SUCCESS = 1;
	const OTHER   = 2;
	const NOTIFY  = 3;
	const REFUND  = 4;

	public static function log( $message, $type = 1 )
	{
		$file = str_replace( "\\", "/", dirname( __DIR__ ) . "/log/" );

		if( $type == self::SUCCESS ) {
			$fp = fopen( $file . "success.log", "a" );
		} else if( $type == self::OTHER ) {
			$fp = fopen( $file . "other.log", "a" );
		} else if( $type == self::NOTIFY ) {
			$fp = fopen( $file . "notify.log", "a" );
		} else if( $type == self::REFUND ) {
			$fp = fopen( $file . "refund.log", "a" );
		} else {
			$fp = fopen( $file . "error.log", "a" );
		}

		fwrite( $fp, trim( $message ) . PHP_EOL );
		fclose( $fp );
	}
}