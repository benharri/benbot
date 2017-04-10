<?php
namespace BenBot\Commands;
error_reporting(-1);

use Carbon\Carbon;

class Debug {

    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;
        self::$bot->registerCommand('up', [__CLASS__, 'up'], [
            'description' => 'shows uptime',
            'registerHelp' => true,
        ]);
        echo __CLASS__ . " registered", PHP_EOL;
    }

    public static function up($msg, $args)
    {
        return "benbot has been up for " . self::$bot->start_time->diffForHumans(Carbon::now(), true);
    }
}
