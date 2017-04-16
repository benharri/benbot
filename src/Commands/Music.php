<?php
namespace BenBot\Commands;

use BenBot\Utils;

class Music {

    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;

        self::$bot->registerCommand('play', [__CLASS__, 'playSong'], [
            'description' => 'plays',
        ]);

        echo __CLASS__ . " registered", PHP_EOL;
    }

    public static function playSong($msg, $args)
    {
        self::$bot->joinVoiceChannel($msg->channel)->then(function (VoiceClient $vc) {
            echo "joined voice channel", PHP_EOL;
            $vc->playFile(self::$bot->dir . "/music/mytype.m4a");
        }, function ($e) {
            echo "there was an error joining the voice channel: {$e->getMessage()}", PHP_EOL, $e->getTraceAsString(), PHP_EOL;
        });
    }

}
