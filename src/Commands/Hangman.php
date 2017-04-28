<?php
namespace BenBot\Commands;

use BenBot\Utils;

class Hangman
{
    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;

        echo __CLASS__ . " registered", PHP_EOL;
    }


    public static function startGame($msg, $args)
    {

    }

}