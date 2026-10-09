<?php

namespace App\Component\Utils;

class Password
{
    private static $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";

    public static function generate($length = 20): string
    {
        $password = "";
        srand((double)microtime()*1000000);

        for ($i=0; $i<$length; $i++) {
            $password .= substr(self::$chars, rand() % strlen(self::$chars), 1);
        }

        return $password;
    }
}
