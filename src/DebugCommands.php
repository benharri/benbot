<?php
namespace BenBot;

use BenBot\Utils;
use Carbon\Carbon;

class DebugCommands {
    // BenBot client instance
    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;
        echo "hi from DebugCommands.php", PHP_EOL;
        self::$bot->registerCommand('up', [__CLASS__, 'up'], [
            'description' => 'shows uptime',
            'registerHelp' => true,
        ]);
    }

    public static function init(&$that)
    {
        self::$bot = $that;
        print_r(self::$bot);
        self::$bot->registerCommand('up', [__CLASS__, 'up']);
        echo "DebugCommands registered.", PHP_EOL;
    }

    public static function test()
    {
        echo "test static function in DebugCommands.php", PHP_EOL;
    }

    public static function up($msg, $args)
    {
        return "benbot has been up for " . self::$bot->start_time->diffForHumans(Carbon::now(), true)};
    }
}
