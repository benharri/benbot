<?php
namespace BenBot;
error_reporting(-1);

use Carbon\Carbon;
use Discord\Parts\Embed\Embed;
use React\Promise\Deferred;

class Utils {

    private static $bot;

    public static function init(&$that)
    {
        self::$bot = $that;
        echo PHP_EOL, "Utils initialized.", PHP_EOL;
    }



    public static function send($msg, $txt, $embed = null)
    {
        return $msg->channel->sendMessage($txt, false, $embed)
            ->otherwise(function($e) use ($msg) {
                echo $e->getMessage(), PHP_EOL;
                echo $e->getTraceAsString(), PHP_EOL;
                $msg->reply("sry, an error occurred. check with <@193011352275648514>.\n```{$e->getMessage()}```");
                self::ping($e->getMessage());
            });
    }


    public static function sendFile($msg, $filepath, $filename, $txt)
    {
        return $msg->channel->sendFile($filepath, $filename, $txt)
            ->otherwise(function($e) use ($msg) {
                echo $e->getMessage(), PHP_EOL;
                echo $e->getTraceAsString(), PHP_EOL;
                $msg->reply("sry, an error occurred. check with <@193011352275648514>.\n```{$e->getMessage()}```");
                self::ping($e->getMessage());
            });
    }


    public static function isDM($msg)
    {
        return $msg->channel->is_private;
    }


    public static function getUserIDFromMsg($msg)
    {
        return self::isDM($msg) ? $msg->author->id : $msg->author->user->id;
    }


    public static function timestampFromSnowflake ($snowflake)
    {
        return (($snowflake / 4194304) + 1420070400000) / 1000;
    }


    public static function ping($msg)
    {
        if (is_null(self::$bot)) {
            throw new \Exception("Utils class not initialized");
        }

        $channel_id = self::$bot->devbot ? '297082205048668160' : '289611811094003715';

        return self::$bot
            ->guilds->get('id', '289410862907785216')
            ->channels->get('id', $channel_id)
            ->sendMessage("<@193011352275648514>, $msg");
    }


    public static function secondsConvert($uptime)
    {
        // Method here heavily based on freebsd's uptime source
        $uptime += $uptime > 60 ? 30 : 0;
        $years = floor($uptime / 31556926);
        $uptime %= 31556926;
        $days = floor($uptime / 86400);
        $uptime %= 86400;
        $hours = floor($uptime / 3600);
        $uptime %= 3600;
        $minutes = floor($uptime / 60);
        $seconds = floor($uptime % 60);
        // Send out formatted string
        $return = array();
        if ($years > 0) {
            $return[] = $years.' '.($years > 1 ? 'years' : 'year');
        }
        if ($days > 0) {
            $return[] = $days.' days';
        }
        if ($hours > 0) {
            $return[] = $hours.' hours';
        }
        if ($minutes > 0) {
            $return[] = $minutes.' minutes';
        }
        if ($seconds > 0) {
            $return[] = $seconds.(date('m/d') == '06/03' ? ' sex' : ' seconds');
        }
        return implode(', ', $return);
    }


    public static function convertMemoryUsage($system = false)
    {
        $mem_usage = memory_get_usage($system);

        if ($mem_usage < 1024) {
            return "$mem_usage bytes";
        } elseif ($mem_usage < 1048576) {
            return round($mem_usage / 1024, 2) . " kilobytes";
        } else {
            return round($mem_usage / 1048576, 2) . " megabytes";
        }
    }


    public static function deleteMessage($msg)
    {
        $deferred = new Deferred();

        $msg->channel->messages->delete($msg)->then(
            function () use ($deferred) {
                $deferred->resolve($this);
            },
            function ($e) use ($deferred) {
                $deferred->reject($e);
            }
        );

        return $deferred->promise();
    }


    public static function editMessage($msg, $text)
    {
        $deferred = new Deferred();

        self::$bot->http->patch(
            "channels/{$msg->channel->id}/messages/{$msg->id}",
            [
                'content' => $text
            ]
        )->then(
            function ($response) use ($deferred, $msg) {
                $msg->fill($response);
                $deferred->resolve($msg);
            },
            \React\Partial\bind_right($msg->reject, $deferred)
        );
        return $deferred->promise();
    }


    public static function arrayFlatten($array)
    {
        if (!is_array($array)) {
            return false;
        }
        $result = [];
        foreach ($array as $key => $val) {
            if (is_array($val)) {
                $result = array_merge($result, self::arrayFlatten($val));
            } else {
                $result[$key] = $val;
            }
        }
        return $result;
    }

}
