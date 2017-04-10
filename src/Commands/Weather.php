<?php
namespace BenBot\Commands;
error_reporting(-1);

use BenBot\Utils;
use BenBot\Cities;

class Weather {

    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;

        $weather = self::$bot->registerCommand('weather', [__CLASS__, 'weather'], [
            'description' => 'get current weather',
            'usage' => '<@user | city, country>',
            'registerHelp' => true,
        ]);
            $weather->registerSubCommand('save', "BenBot\Commands\Cities::saveCity", [
                'description' => 'saves a city for a user to look up weather and current time',
            ]);

        echo __CLASS__ . " registered", PHP_EOL;
    }

    public static function weather($msg, $args)
    {
        return "WIP";
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
}
