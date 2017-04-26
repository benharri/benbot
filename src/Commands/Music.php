<?php
namespace BenBot\Commands;

use BenBot\Utils;

class Music
{

    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;

        self::$bot->registerCommand('play', [__CLASS__, 'playTest'], [
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

    public static function playTest($msg, $args)
    {
        $guild = self::$bot->guilds->get('id', '289410862907785216');
        $channel = $guild->channels->get('id', '294208856970756106');

        self::$bot->joinVoiceChannel($channel)->then(function (\Discord\Voice\VoiceClient $vc) {
            $vc->playFile(self::$bot->dir . '/music/mytype.m4a')->then(function ($test) use ($vc){
                //Leave voice channel
                $vc->close();
            });
        }, function ($e) {
            echo $e->getMessage(), PHP_EOL;
            echo $e->getTraceAsString(), PHP_EOL;
        });
    }


    public static function playFromYouTube($msg, $args)
    {
        $cmd = "youtube-dl --extract-audio --audio-format mp3 --audio-quality 0 ";
    }

}
