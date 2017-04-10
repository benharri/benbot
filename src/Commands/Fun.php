<?php
namespace BenBot\Commands;
error_reporting(-1);

class Fun {

    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;
        self::$bot->registerCommand('roll', [__CLASS__, 'rollDie'], [
            'description' => 'rolls an n-sided die',
            'usage' => '<number of sides>',
            'registerHelp' => true,
        ]);
        echo __CLASS__ . " registered", PHP_EOL;
    }

    public static function rollDie($msg, $args)
    {
        return "{$msg->author}, you rolled a " . rand(1, $args[0] ?? 6);
    }
}
