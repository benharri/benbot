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
        echo "Utils initialized.", PHP_EOL;
    }



    public static function send($msg, $txt, $embed = null)
    {
        return $msg->channel->sendMessage($txt, false, $embed)
            ->otherwise(function($e) use ($msg) {
                echo $e->getMessage(), PHP_EOL;
                $msg->reply("sry, an error occurred. check with <@193011352275648514>.\n```{$e->getMessage()}```");
                self::ping($e->getMessage());
            });
    }


    public static function sendFile($msg, $filepath, $filename, $txt)
    {
        return $msg->channel->sendFile($filepath, $filename, $txt)
            ->otherwise(function($e) use ($msg) {
                echo $e->getMessage(), PHP_EOL;
                $msg->reply("sry, an error occurred. check with <@193011352275648514>.\n```{$e->getMessage()}```");
                self::ping($e->getMessage());
            });
    }


    public static function isDM($msg)
    {
        return $msg->channel->is_private;
    }


    public static function timestampFromSnowflake ($snowflake)
    {
        return (($snowflake / 4194304) + 1420070400000) / 1000;
    }


    public static function celsiusToFahrenheit($celsius)
    {
        return $celsius * 9 / 5 + 32;
    }

    public static function fahrenheitToCelsius($fahrenheit)
    {
        return $fahrenheit * 5 / 9 + 32;
    }

    public static function formatWeatherJson($json, $timezone = null)
    {

        return self::$bot->factory(Embed::class, [
            'title' => "Weather in {$json->name}, {$json->sys->country}",
            'thumbnail' => ['url' => "http://openweathermap.org/img/w/{$json->weather[0]->icon}.png"],
            'fields' => [
                ['name' => 'Current temperature'
                , 'value' => "{$json->main->temp}°C (".self::celsiusToFahrenheit($json->main->temp)."°F)"
                , 'inline' => true
                ],
                ['name' => 'Low/High Forecasted Temp'
                , 'value' => "{$json->main->temp_min}/{$json->main->temp_max}°C  " . self::celsiusToFahrenheit($json->main->temp_min) . "/" . self::celsiusToFahrenheit($json->main->temp_max) . "°F"
                , 'inline' => true
                ],
                ['name' => 'Current conditions'
                , 'value' => $json->weather[0]->description
                , 'inline' => true
                ],
                ['name' => 'Atmospheric Pressure'
                , 'value' => "{$json->main->pressure} hPa"
                , 'inline' => true
                ],
                ['name' => 'Humidity'
                , 'value' => "{$json->main->humidity} %"
                , 'inline' => true
                ],
                ['name' => 'Wind'
                , 'value' => "{$json->wind->speed} meters/second, {$json->wind->deg}°"
                , 'inline' => true
                ],
                ['name' => 'Sunrise'
                , 'value' => Carbon::createFromTimestamp($json->sys->sunrise, $timezone)->toTimeString()
                , 'inline' => true
                ],
                ['name' => 'Sunset'
                , 'value' => Carbon::createFromTimestamp($json->sys->sunset, $timezone)->toTimeString()
                , 'inline' => true
                ],
            ],
            'timestamp' => null,
        ]);

    }



    public static function askCleverbot($input)
    {
        $deferred = new Deferred();

        $url = "https://www.cleverbot.com/getreply";
        $key = getenv('CLEVERBOT_API_KEY');
        $input = rawurlencode($input);
        self::$bot->discord->http->get("$url?input=$input&key=$key", null, [], false)->then(function ($apidata) use ($deferred) {
            $deferred->resolve($apidata);
        }, function ($e) {
            $deferred->reject($e);
        });

        return $deferred->promise();
    }


    public static function ping($msg)
    {
        if (is_null(self::$bot)) {
            throw new \Exception("Utils class not initialized");
        }
        return self::$bot
            ->guilds->get('id', '289410862907785216')
            ->channels->get('id','297082205048668160')
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
            $return[] = $years.' '.($years > 1 ? self::$lang['years'] : substr(self::$lang['years'], 0, strlen(self::$lang['years']) - 1));
        }
        if ($days > 0) {
            $return[] = $days.' '.self::$lang['days'];
        }
        if ($hours > 0) {
            $return[] = $hours.' '.self::$lang['hours'];
        }
        if ($minutes > 0) {
            $return[] = $minutes.' '.self::$lang['minutes'];
        }
        if ($seconds > 0) {
            $return[] = $seconds.(date('m/d') == '06/03' ? ' sex' : ' '.self::$lang['seconds']);
        }
        return implode(', ', $return);
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

    public function editMessage($msg, $text)
    {
        $deferred = new Deferred();
        $this->discord->http->patch(
            "channels/{$msg->channel->id}/messages/{$msg->id}",
            [
                'content' => $text
            ]
        )->then(
            function ($response) use ($deferred) {
                $msg->fill($response);
                $deferred->resolve($msg);
            },
            \React\Partial\bind_right($msg->reject, $deferred)
        );
        return $deferred->promise();
    }

}
