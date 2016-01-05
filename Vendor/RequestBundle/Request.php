<?php
/**
 * Created by PhpStorm.
 *
 * @author: Zrone <xujining2008@126.com>
 * @date: 2016/1/3
 * @time: 21:36
 */

namespace Vendor\RequestBundle;

class Request
{
    public static function get( $key, $filter = 'trim' )
    {
        $value = isset( $_GET[ $key ] ) ? $_GET[ $key ] : NULL;
        $filter = trim( $filter );

        return self::filter( $filter, $value );
    }

    public static function post( $key, $filter = 'trim' )
    {
        $value = isset( $_POST[ $key ] ) ? $_POST[ $key ] : NULL;
        $filter = trim( $filter );

        return self::filter( $filter, $value );
    }

    private static function filter( $filter, &$value )
    {
        switch ($filter) {
            case 'trim':
                return trim( $value );
                break;

            case 'integer':
                return (int)$value;
                break;

            case 'mobile':

                if (preg_match( "/^1(3|4|5|7|8)\d{9}/", $value )) {
                    return trim( $value );
                } else {
                    return NULL;
                }

                break;

            case 'postalcode':

                if (preg_match( "/\d{6}/", $value )) {
                    return trim( $value );
                } else {
                    return NULL;
                }

                break;

            case 'urlencode':
                return urlencode( $value );
                break;

            case 'urldecode':
                return urldecode( $value );
                break;

            case 'base64encode':
                return base64_encode( $value );
                break;

            case 'base64decode':
                return base64_decode( $value );
                break;

            case 'stripcslashes':
                return stripcslashes( $value );
                break;

            default:
                return $value;
                break;
        }
    }
}