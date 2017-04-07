<?php
namespace BenBot\Commands;

class Misc {

    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;
        self::$bot->registerCommand('hi', [__CLASS__, 'hi'], [
            'description' => 'just some greetings'
        ]);
        echo __CLASS__ . " registered", PHP_EOL;
    }

    public static function hi($msg, $args)
    {
        return "hello {$msg->author}, how are you today?";
    }
}
