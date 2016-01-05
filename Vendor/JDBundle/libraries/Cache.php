<?php
/**
 * Created by PhpStorm.
 * User: zrone
 * Date: 2016/1/3
 * Time: 3:35
 */

namespace Vendor\JDBundle\libraries;

class Cache
{
    public static function noCache()
    {
        return header("Cache-Control: no-cache, must-revalidate");
    }
}