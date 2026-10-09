<?php

namespace App\Component\Utils;

class Debug
{
    private static $filename = "/tmp/debug.log";

    public static function append($message, $prepend = "\n", $append = ""): void
    {
        if (is_array($message) || is_object($message)) {
            $message = print_r($message, 1);
        }

        file_put_contents(self::$filename, $prepend . $message . $append, FILE_APPEND);
    }

    public static function clear(): void
    {
        file_put_contents(self::$filename, "");
    }
}
