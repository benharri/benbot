<?php
namespace BenBot\Commands;
error_reporting(-1);

use BenBot\Utils;
use BenBot\Cities;

class Time {

    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;


        echo __CLASS__ . " registered", PHP_EOL;
    }

}