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
        $dir_iter = new DirectoryIterator(self::$bot->dir . "/fonts", $flags);
        foreach ($dir_iter as $fileinfo) {
            if ($fileinfo->isDir()) {
                echo $fileinfo->getFilename(), PHP_EOL;
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
        $text = implode(" ", $args);
        if (self::$figlet->loadfont())

        return "```" . self::$figlet->fetch($text) . "```";
    }

}