<?php
namespace BenBot\Commands;

use BenBot\Utils;

use Discord\Helpers\Process;
use Discord\Voice\VoiceClient;
use Discord\Parts\Channel\Channel;

final class Music
{

    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;

        self::$bot->registerCommand('play', [__CLASS__, 'playFromYouTube'], [
            'description' => 'plays',
            'usage' => '<yt ID|URL|search>',
            'aliases' => [
                'yt',
            ],
        ]);

        self::$bot->registerCommand('mytype', [__CLASS__, 'playTest'], [
            'description' => 'ur just my type',
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

        self::$bot->joinVoiceChannel($channel)->then(function (VoiceClient $vc) {
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
        $channel = null;
        foreach ($msg->channel->guild->channels as $chnl) {
            if ($chnl->type != Channel::TYPE_VOICE) {
                continue;
            }
            // print_r($chnl);
            if ($chnl->members->get('id', $msg->author->id)) {
                $channel = $chnl;
                break;
            }
        }
        print_r($channel);
        if (!$channel instanceof Channel) {
            return "you're not in a voice channel, silly";
        }

        $cmd = "youtube-dl --extract-audio --audio-format mp3 --audio-quality 0 -o - ";
        if ($args[0] != "") {
            if (strlen($args[0]) === 11 || strpos($args[0], "http") !== false) {
                // is yt vid ID or URL
                $cmd .= $args[0];
            } else {
                $query = implode(" ", $args);
                $cmd .= "'ytsearch:$query'";
            }
        } else {
            return "gotta pick something to play, silly";
        }

        echo $cmd, PHP_EOL;

        self::$bot->joinVoiceChannel($channel)->then(function (VoiceClient $vc) use ($cmd) {
            $process = new Process($cmd);
            $process->start(self::$bot->loop);
            echo "process started", PHP_EOL;
            $vc->playRawStream($process->stdout)->then(function () use ($vc) {
                echo "stream done playing", PHP_EOL;
                $vc->close();
            }, function ($e) {
                echo $e->getMessage(), PHP_EOL;
                echo $e->getTraceAsString(), PHP_EOL;
            });
        }, function ($e) {
            echo $e->getMessage(), PHP_EOL;
            echo $e->getTraceAsString(), PHP_EOL;
        });
    }

}
