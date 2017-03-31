<?php
namespace BenBot;

use Carbon\Carbon;
use Discord\Parts\Embed\Embed;
use React\Promise\Deferred;

class Utils extends BenBot {

    protected $discord;

    public function __construct($discord)
    {
        // $this->discord = $discord;
    }

    public function mysend($msg, $txt, $embed = null)
    {
        return $msg->channel->sendMessage($txt, false, $embed)
            ->otherwise(function($e) use ($msg) {
                echo $e->getMessage(), PHP_EOL;
                $this->pingMe($e->getMessage());
                $msg->reply("sry, an error occurred. check with <@193011352275648514>.\n```{$e->getMessage()}```");
            });
    }

    public static function ssend($msg, $txt, $embed = null)
    {
        return $msg->channel->sendMessage($txt, false, $embed)
            ->otherwise(function($e) use ($msg) {
                echo $e->getMessage(), PHP_EOL;
                $msg->reply("sry, an error occurred. check with <@193011352275648514>.\n```{$e->getMessage()}```");
            });
    }

    public function sendFile($msg, $filepath, $filename, $txt)
    {
        return $msg->channel->sendFile($filepath, $filename, $txt)
            ->otherwise(function($e) use ($msg) {
                echo $e->getMessage(), PHP_EOL;
                $this->pingMe($e->getMessage());
                $msg->reply("sry, an error occurred. check with <@193011352275648514>.\n```{$e->getMessage()}```");
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

    public function formatWeatherJson($json, $timezone = null)
    {

        return $this->discord->factory(Embed::class, [
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



    public function askCleverbot($input)
    {
        $deferred = new Deferred();

        $url = "https://www.cleverbot.com/getreply";
        $key = getenv('CLEVERBOT_API_KEY');
        $input = rawurlencode($input);
        $this->discord->http->get("$url?input=$input&key=$key", null, [], false)->then(function ($apidata) use ($deferred) {
            $deferred->resolve($apidata);
        }, function ($e) {
            $deferred->reject($e);
        });

        return $deferred->promise();
    }

    public function pingMe($msg)
    {
        return $this->discord
            ->guilds->get('id', '289410862907785216')
            ->channels->get('id','289611811094003715')
            ->sendMessage("<@193011352275648514>, $msg");
    }

}
