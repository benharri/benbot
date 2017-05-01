<?php
namespace BenBot\Commands;

use BenBot\Utils;
use BenBot\Commands\Cities;

use Carbon\Carbon;


final class Time
{

    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;

        $timeCmd = self::$bot->registerCommand('time', [__CLASS__, 'getUserTime'], [
            'description' => 'looks up times in different time zones. you can save a preferred city.',
            'usage' => '[@user]',
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
                return self::sayTime($city);
            } else {
                return self::sayTime([
                    'timezone' => 'America/Detroit',
                    'city' => 'Eastern Time (USA)'
                ]) . "\n you can set a preferred city with `;time save <city>`";
            }

        } else {
            if (count($msg->mentions) > 0) {

                foreach ($msg->mentions as $mention) {
                    if (isset(self::$bot->cities[$mention->id])) {
                        $city = self::$bot->cities[$mention->id];
                        return self::sayTime($city);
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
                        Utils::send($msg, self::sayTime([
                            'timezone' => $json->timezoneId,
                            'city' => $weatherjson->name
                        ]));
                    });
                });
            }
        }
    }


    public static function sayTime($city)
    {
        $time = Carbon::now($city['timezone']);
        return "It's " . $time->format('g:i A \o\n l F j, Y') . " in {$city["city"]}. " . self::clockEmojiForTime($time);
    }


    public static function clockEmojiForTime(Carbon $emojitime)
    {
        $hour = $emojitime->hour % 12;
        $minute = $emojitime->minute >= 30 ? "30" : "";
        return ":clock$hour$minute:";
    }


}