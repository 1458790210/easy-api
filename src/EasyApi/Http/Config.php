<?php

namespace EasyApi\Http;

class Config
{
    public static $config = [];
    public static $host = 'https://api.weixin.qq.com';

    public static function getConfig()
    {
        if (is_file('.env')) {
            $env = parse_ini_file('.env', true);

            foreach ($env as $key => $val) {
                $name = strtoupper($key);

                if (is_array($val)) {
                    foreach ($val as $k => $v) {
                        $item = $name . '_' . strtoupper($k);
                        putenv("$item=$v");
                    }
                } else {
                    putenv("$name=$val");
                }
            }
        }

        self::$config = ['apiKey' => getenv('ACCESS_KEY'), 'apiSecret' => getenv('SECRET_KEY')];

        return self::$config;
    }
}