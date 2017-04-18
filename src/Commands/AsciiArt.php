<?php
namespace BenBot\Commands;

use BenBot\Utils;
use Discord\Helpers\Process;
use function Stringy\create as s;

class AsciiArt {

    private static $bot;
    private static $figlet;
    private static $fonts;

    public static function register(&$that)
    {
        self::$bot = $that;
        self::$fonts = [];

        $flags = \FilesystemIterator::SKIP_DOTS
               | \FilesystemIterator::UNIX_PATHS;
        $di = new \RecursiveDirectoryIterator(self::$bot->dir . "/fonts", $flags);
        $it = new \RecursiveIteratorIterator($di);
        foreach ($it as $fileinfo) {
            if (pathinfo($fileinfo, PATHINFO_EXTENSION) == "flf") {
                self::$fonts[$fileinfo->getBasename(".flf")] = $fileinfo->getPathName();
            }
        }
        asort(self::$fonts);

        $ascii = self::$bot->registerCommand('ascii', [__CLASS__, 'ascii'], [
            'description' => 'creates ascii word art',
            'usage' => '[font] <words>',
            'registerHelp' => true,
        ]);
            $ascii->registerSubCommand('list', [__CLASS__, 'listFonts'], [
                'description' => 'lists available ascii fonts',
                'aliases' => [
                    'listfonts'
                ],
            ]);


        echo __CLASS__ . " registered", PHP_EOL;
    }


    public static function ascii($msg, $args)
    {
        if (array_key_exists($args[0], self::$fonts)) {
            $filepath = self::$fonts[$args[0]];
            array_shift($args);
        } else {
            $filepath = self::$fonts["standard"];
        }

        $text = implode(" ", $args);

        $process = new Process("figlet -f $filepath '" . escapeshellarg($text) . "'");
        $process->start(self::$bot->loop);

        $process->stdout->on('data', function ($chunk) use ($msg) {
            Utils::send($msg, "```$chunk```");
        });

    }

    public static function listFonts($msg, $args)
    {
        $response = "**available ASCII art fonts**:\n";
        $response .= implode(", ", array_keys(self::$fonts));
        $split = str_split($response, 2000);
        foreach ($split as $part) {
            Utils::send($msg, $part);
        }
    }


}