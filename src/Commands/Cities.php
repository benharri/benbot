<?php
namespace BenBot\Commands;
error_reporting(-1);

use BenBot\Utils;

class Cities {

    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;
    }

    public static function saveCity($msg, $args)
    {
        $api_key = getenv('OWM_API_KEY');
        $query = rawurlencode(implode(" ", $args));
        $url = "http://api.openweathermap.org/data/2.5/weather?q=$query&APPID=$api_key&units=metric";

        self::$bot->http->get($url)->then(function ($json) use ($msg) {

            $lat = $json->coord->lat;
            $lng = $json->coord->lon;

            $geonamesurl = "http://api.geonames.org/timezoneJSON?username=benharri&lat=$lat&lng=$lng";

            self::$bot->http->get($geonamesurl)->then(function ($geojson) use ($msg, $json) {

                if (count($msg->mentions) > 0) {
                    $response = "the preferred city for ";
                    $mentions = [];
                    foreach ($msg->mentions as $mention) {
                        self::$bot->cities[$mention->id] = [
                            'id'       => $json->id,
                            'lat'      => $json->coord->lat,
                            'lon'      => $json->coord->lon,
                            'city'     => $json->name,
                            'timezone' => $geojson->timezoneId,
                        ];
                        $mentions[] = "$mention";
                    }
                    $response .= implode(", ", $mentions);
                    $response .= " has been set to {$json->name}";
                    Utils::send($msg, $response);
                } else {
                    self::$bot->cities[$msg->author->id] = [
                        'id'       => $json->id,
                        'lat'      => $json->coord->lat,
                        'lon'      => $json->coord->lon,
                        'city'     => $json->name,
                        'timezone' => $geojson->timezoneId,
                    ];
                    Utils::send($msg, "{$msg->author}, your preferred city has been set to {$json->name}");
                }

            });
        });
    }
}