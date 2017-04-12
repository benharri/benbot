<?php
namespace BenBot\Commands;
error_reporting(-1);

use BenBot\Utils;
use BenBot\Commands\Cities;

use Carbon\Carbon;


class Time {

    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;

        $timeCmd = self::$bot->registerCommand('time', [__CLASS__, 'getUserTime'], [
            'description' => 'looks up times in different time zones. you can save a preferred city.',
            'usage' => '<@user>',
            'registerHelp' => true,
        ]);
            $timeCmd->registerSubCommand('save', ['BenBot\Commands\Cities','saveCity'], [
                'description' => 'save a city for use with time and weather.',
                'usage' => '<city>',
            ]);

        echo __CLASS__ . " registered", PHP_EOL;
    }

    public static function getUserTime($msg, $args)
    {
        $id = Utils::getUserIDFromMsg($msg);

        if (count($args) <= 1 && $args[0] == "") {
            // look up the user's time
            if (isset(self::$bot->cities[$id])) {
                $city = self::$bot->cities[$id];
                return "It's " . Carbon::now($city["timezone"])->format('g:i A \o\n l F j, Y') . " in {$city["city"]}.";
            } else {
                return "It's " . Carbon::now()->format('g:i A \o\n l F j, Y') . " Eastern Time (USA).\nyou can set a preferred city with `;time save <city>` or `;weather save <city>`";
            }

        } else {
            if (count($msg->mentions) > 0) {

                foreach ($msg->mentions as $mention) {
                    if (isset(self::$bot->cities[$mention->id])) {
                        $city = self::$bot->cities[$mention->id];
                        return "It's " . Carbon::now($city["timezone"])->format('g:i A \o\n l F j, Y') . " in {$city["city"]}.";
                    } else {
                        return "No city found for {$mention}.\nset a preferred city with `;time save <city> <@user>`";
                    }
                }

            } else {
                $msg->channel->broadcastTyping();

                $api_key = getenv('OWM_API_KEY');
                $query = rawurlencode(implode(" ", $args));
                $url = "http://api.openweathermap.org/data/2.5/weather?q={$query}&APPID=$api_key&units=metric";

                self::$bot->http->get($url)->then(function ($weatherjson) use ($msg) {
                    $coord = $weatherjson->coord;
                    $geonamesurl = "http://api.geonames.org/timezoneJSON?username=benharri&lat={$coord->lat}&lng={$coord->lon}";

                    self::$bot->http->get($geonamesurl)->then(function ($json) use ($msg, $weatherjson) {
                        Utils::send($msg, "It's " . Carbon::now($json->timezoneId)->format('g:i A \o\n l F j, Y') . " in {$weatherjson->name}.");
                    });
                });
            }
        }
    }


}