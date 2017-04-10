<?php
namespace BenBot\Commands;
error_reporting(-1);

class Weather {

    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;

        $weather = self::$bot->registerCommand('weather', [__CLASS__, 'weather'], [
            'description' => 'just some greetings'
        ]);
        echo __CLASS__ . " registered", PHP_EOL;
    }

    public static function weather($msg, $args)
    {

    }
}
