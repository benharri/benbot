<?php
namespace BenBot\Commands;

use BenBot\Utils;
use Carbon\Carbon;

class Debug {
    private static $bot;

    public static function register(&$that)
    {
        echo "hi from Debug.php", PHP_EOL;
        self::$bot = $that;
        self::$bot->registerCommand('up', ['self', 'up'], [
            'description' => 'shows uptime',
            'registerHelp' => true,
        ]);
    }

    public static function up($msg, $args)
    {
        return "benbot has been up for " . self::$bot->start_time->diffForHumans(Carbon::now(), true)};
    }
}
