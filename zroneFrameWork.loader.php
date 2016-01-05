<?php
/**
 * Created by PhpStorm.
 *
 * @author: Zrone <xujining2008@126.com>
 * @date: 2016/1/3
 * @time: 4:04
 */

zroneFrameWorkAutoLoad();

/**
 * 设置自动加载方法
 */
function zroneFrameWorkAutoLoad()
{
    spl_autoload_register( 'zroneClassLoader' );
}

/**
 * 自动加载
 *
 * @param $className
 */
function zroneClassLoader( $className )
{
    set_include_path( str_replace( "\\", "/", ( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . "Vendor/" ) ) );
    spl_autoload_extensions( ".php, .class.php" );
    spl_autoload( $className );
}