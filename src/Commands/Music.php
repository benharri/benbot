<?php
namespace BenBot\Commands;

use BenBot\Utils;

use React\Promise\Deferred;

use Discord\Helpers\Process;
use Discord\Voice\VoiceClient;
use Discord\Parts\Channel\Channel;

final class Music
{

    private static $bot;
    private static $voiceclients;

    public static function register(&$that)
    {
        self::$bot = $that;

        self::$voiceclients = [];

        self::$bot->registerCommand('play', [__CLASS__, 'playFromYouTube'], [
            'description' => 'plays',
            'usage' => '<yt ID|URL|search>',
            'aliases' => [
                'yt',
            ],
        ]);
        self::$bot->registerCommand('pause', [__CLASS__, 'pauseAudio'], [
            'description' => 'pauses the currently playing song',
        ]);
        self::$bot->registerCommand('resume', [__CLASS__, 'resumeAudio'], [
            'description' => 'resumes a paused song',
        ]);
        self::$bot->registerCommand('stop', [__CLASS__, 'stopAudio'], [
            'description' => 'stops the currently playing song',
        ]);
        self::$bot->registerCommand('mytype', [__CLASS__, 'playTest'], [
            'description' => 'ur just my type',
        ]);


        echo __CLASS__ . " registered", PHP_EOL;
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
        $channel = self::getVoiceChannel($msg);
        print_r($channel);
        if (!$channel instanceof Channel) {
            return "you're not in a voice channel, silly";
        }

        if (isset(self::$voiceclients[$msg->channel->guild->id]) && self::$voiceclients[$msg->channel->guild->id] instanceof VoiceClient) {
            self::$voiceclients[$msg->channel->guild->id]->stop();
            self::getVideoJSON($args)->then(function ($json) use ($msg) {
                Utils::send($msg, "preparing...")->then(function ($statusmsg) use ($msg, $json) {
                    self::downloadAudio($json)->then(function ($file) use ($statusmsg, $msg) {
                        $statusmsg->channel->messages->delete($statusmsg);
                        self::$voiceclients[$msg->channel->guild->id]->playFile(self::$bot->dir . "/music/$file")->then(function () {
                            self::$voiceclients[$msg->channel->guild->id]->close();
                        }, function ($e) {
                            echo $e->getMessage(), PHP_EOL;
                            echo $e->getTraceAsString(), PHP_EOL;
                        });
                    });
                });
            });
        }

        self::getVideoJSON($args)->then(function ($json) use ($channel, $msg) {
            Utils::send($msg, "preparing...")->then(function ($statusmsg) use ($channel, $msg, $json) {
                self::downloadAudio($json)->then(function ($file) use ($channel, $statusmsg, $msg) {
                    $statusmsg->channel->messages->delete($statusmsg);
                    self::$bot->joinVoiceChannel($channel)->then(function (VoiceClient $vc) use ($file, $msg) {
                        self::$voiceclients[$msg->channel->guild->id] = $vc;
                        $vc->playFile(self::$bot->dir . "/music/$file")->then(function () use ($vc) {
                            $vc->close();
                        }, function ($e) {
                            echo $e->getMessage(), PHP_EOL;
                            echo $e->getTraceAsString(), PHP_EOL;
                        });
                    });
                });
            });
        });
    }


    public static function pauseAudio($msg, $args)
    {
        if (self::$voiceclients[$msg->channel->guild->id] instanceof VoiceClient) {
            self::$voiceclients[$msg->channel->guild->id]->pause();
            return "paused";
        } else {
            return "not playing...";
        }
    }


    public static function resumeAudio($msg, $args)
    {
        if (self::$voiceclients[$msg->channel->guild->id] instanceof VoiceClient) {
            self::$voiceclients[$msg->channel->guild->id]->unpause();
            return "resuming";
        } else {
            return "not stopped...";
        }
    }


    public static function stopAudio($msg, $args)
    {
        if (self::$voiceclients[$msg->channel->guild->id] instanceof VoiceClient) {
            self::$voiceclients[$msg->channel->guild->id]->stop();
            return "stopped";
        } else {
            return "not playing...";
        }
    }


    private static function getVideoJSON($args)
    {
        $deferred = new Deferred();

        $cmd = "youtube-dl --dump-single-json ";
        if ($args[0] != "") {
            if (strlen($args[0]) === 11 || strpos($args[0], "http") !== false) {
                // is yt vid ID or URL
                $cmd .= $args[0];
            } else {
                $query = implode(" ", $args);
                $cmd .= "'ytsearch:$query'";
            }
        }
        echo $cmd, PHP_EOL;

        $process = new Process($cmd);
        $process->on('exit', function ($exitcode, $termsig) use (&$data, $deferred) {
            if (intval($exitcode) === 1) {
                $deferred->reject("invalid url");
            } else {
                $deferred->resolve(json_decode($data));
            }
            echo "$exitcode, $termsig", PHP_EOL;
        });
        self::$bot->loop->addTimer(0.001, function ($timer) use (&$data, $deferred, $process) {
            $process->start(self::$bot->loop);
            $process->stdout->on('data', function ($output) use (&$data) {
                $data .= $output;
            });
        });

        return $deferred->promise();
    }


    private static function downloadAudio($result)
    {
        $deferred = new Deferred();

        $json = $result->entries[0];
        $url = escapeshellarg($json->webpage_url);
        $filename = $json->id . '-' . md5($json->title) . '-' . $json->duration;

        foreach (scandir(self::$bot->dir . '/music') as $file) {
            // check if we've already downloaded the file!
            if (pathinfo($file, PATHINFO_FILENAME) === $filename) {
                $deferred->resolve($file);
                return $deferred->promise();
            }
        }

        $file = escapeshellarg(self::$bot->dir . "/music/$filename.%(ext)s");

        $cmd = "youtube-dl --extract-audio --audio-format mp3 --audio-quality 0 --restrict-filenames --no-check-certificate --no-warnings --source-address 0.0.0.0 -o $file $url";
        echo $cmd, PHP_EOL;

        $process = new Process($cmd);
        $process->on('exit', function ($exitcode, $termsig) use ($deferred, $filename) {
            if (intval($exitcode) !== 0) {
                $deferred->reject('error downloading video');
            } else {
                foreach (scandir(self::$bot->dir . '/music') as $file) {
                    if (pathinfo($file, PATHINFO_FILENAME) === $filename) {
                        $deferred->resolve($file);
                    }
                }
            }
        });

        self::$bot->loop->addTimer(0.001, function ($timer) use ($deferred, $process) {
            $process->start(self::$bot->loop);
        });

        return $deferred->promise();
    }


    private static function getVoiceChannel($msg)
    {
        foreach ($msg->channel->guild->channels->getAll('type', Channel::TYPE_VOICE) as $voicechannel) {
            if (!empty($voicechannel->members->get('user_id', $msg->author->id))) {
                return $voicechannel;
            }
        }
        return null;
    }

}
