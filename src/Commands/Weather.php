<?php
namespace BenBot\Commands;

use BenBot\Utils;
use BenBot\Cities;

use Discord\Parts\Embed\Embed;
use Carbon\Carbon;


final class Weather
{

    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;

        $weather = self::$bot->registerCommand('weather', [__CLASS__, 'weather'], [
            'description' => 'get current weather',
            'usage' => '[@user|city search]',
            'registerHelp' => true,
        ]);
            $weather->registerSubCommand('save', ['BenBot\Commands\Cities', 'saveCity'], [
                'description' => "saves a city for a user (or yourself if you don't mention anyone)",
                'usage' => '<city search> [@user]',
            ]);

        echo __CLASS__ . " registered", PHP_EOL;
    }



    public static function weather($msg, $args)
    {
        $id = Utils::getUserIDFromMsg($msg);
        $api_key = getenv('OWM_API_KEY');
        $url = "http://api.openweathermap.org/data/2.5/weather?APPID=$api_key&units=metric&";

        if (count($args) <= 1 && $args[0] == "") {
            echo "looking up weather for {$msg->author} $id";
            if (isset(self::$bot->cities[$id])) {
                $city = self::$bot->cities[$id];
                $url .= "id={$city["id"]}";
                self::$bot->http->get($url)->then(function ($result) use ($msg, $city) {
                    Utils::send($msg, "", self::formatWeatherJson($result, $city["timezone"]));
                });
            } else {
                return "you can set your city with `;weather save <city>`";
            }
        } else {
            if (count($msg->mentions) > 0) {
                foreach ($msg->mentions as $mention) {
                    if (isset(self::$bot->cities[$mention->id])) {
                        $city = self::$bot->cities[$mention->id];
                        $url .= "id={$city["id"]}";
                        self::$bot->http->get($url)->then(function ($result) use ($msg, $city) {
                            Utils::send($msg, "", self::formatWeatherJson($result, $city["timezone"]));
                        });
                    } else {
                        return "no city saved for $mention.\nset a preferred city with `;weather save <city> $mention`";
                    }
                }
            } else {
                $query = rawurlencode(implode(" ", $args));
                $url .= "q=$query";
                self::$bot->http->get($url)->then(function ($result) use ($msg) {
                    Utils::send($msg, "", self::formatWeatherJson($result));
                });
            }
        }
    }


    ////////////////////////////////////////////////////
    // util fns
    ////////////////////////////////////////////////////
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
