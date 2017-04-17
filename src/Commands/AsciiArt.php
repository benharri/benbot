<?php
namespace BenBot\Commands;

use BenBot\Utils;
use Discord\Helpers\Process;
// use phpclasses\phpFiglet;

class AsciiArt {

    private static $bot;
    private static $figlet;
    private static $fonts;

    public static function register(&$that)
    {
        self::$bot = $that;
        self::$figlet = new \phpFiglet();
        self::$fonts = [];

        $flags = \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS;
        $di = new \RecursiveDirectoryIterator(self::$bot->dir . "/fonts", $flags);
        $it = new \RecursiveIteratorIterator($di);
        foreach ($it as $fileinfo) {
            if (pathinfo($fileinfo, PATHINFO_EXTENSION) == "flf") {
                echo $fileinfo->getBasename(".flf"), PHP_EOL;
                self::$fonts[] = $fileinfo->getBasename(".flf");
            }
        }


        self::$bot->registerCommand('ascii', [__CLASS__, 'ascii'], [
            'description' => 'creates ascii word art',
            'usage' => '<words>',
        ]);

        self::$bot->registerCommand('ascii2', [__CLASS__, 'ascii2'], [
            'description' => 'thanks',
            'usage' => '<words>',
        ]);

        echo __CLASS__ . " registered", PHP_EOL;
    }


    public static function ascii($msg, $args)
    {
        $text = implode(" ", $args);
        $process = new Process('figlet ' . escapeshellarg($text));
        $process->start(self::$bot->loop);

        $response = "";

        $process->stdout->on('data', function ($chunk) use ($msg, &$response) {
            Utils::send($msg, "```$chunk```");
            $response .= $chunk;
            echo $chunk, PHP_EOL;
        });

    }

    public static function ascii2($msg, $args)
    {
        if (array_key_exists($args[0], self::$fonts)) {
            $filename = glob(self::$bot->dir . "/fonts/*/{$args[0]}.flf");
            echo $filename, PHP_EOL;
            if (self::$figlet->loadfont($filename)) {
                array_shift($args);
                $text = implode(" ", $args);
                return "```" . self::$figlet->fetch($text) . "```";
            } else {
                return "something borked";
            }
        } else {
            if (self::$figlet->loadfont(self::$bot->dir . "/fonts/ours/standard.flf")) {
                $text = implode(" ", $args);
                return "```" . self::$figlet->fetch($text) . "```";
            }
        }

    }

}