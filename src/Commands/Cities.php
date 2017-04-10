<?php
namespace BenBot\Commands;
error_reporting(-1);

class Cities {

    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;
    }

    public static function saveCity($msg, $args)
    {

    }
}