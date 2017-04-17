<?php
namespace BenBot\Commands;

use BenBot\Utils;
use Discord\Helpers\Process;

class AsciiArt {

    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;

        self::$bot->registerCommand('ascii', [__CLASS__, 'ascii'], [
            'description' => 'creates ascii word art',
            'usage' => '<words>',
        ]);

        echo __CLASS__ . " registered", PHP_EOL;
    }


    public static function ascii($msg, $args)
    {
        $process = new Process('figlet hi');
        $process->start(self::$bot->loop);

        $response = "";

        $process->stdout->on('data', function ($chunk) use (&$response) {
            $response .= $chunk;
        });

        $process->on('exit', function ($code) use ($msg, $response) {
            Utils::send($msg, $response);
        });

    }

}