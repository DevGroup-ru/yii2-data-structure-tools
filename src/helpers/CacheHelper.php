<?php

namespace DevGroup\DataStructure\helpers;

use yii;

class CacheHelper
{
    /**
     * Hashes multidimensional map for using in cache key
     * @param array $map
     *
     * @return string
     */
    public static function hashMap($map)
    {
        $cache = '';
        foreach ($map as $key => $value) {
            $cache .= 0xAB01 . $key . 0xAB02;
            if (is_array($value)) {
                $cache .= 0xAB04 . static::hashMap($value);
            } else {
                $cache .= 0xAB03 . $value;
            }
        }
        return md5($cache);
    }
}
