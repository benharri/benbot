<?php
namespace BenBot;

use Carbon\Carbon;
use Discord\Parts\Embed\Embed;
use React\Promise\Deferred;

class Utils {

    protected $discord;

    public function __construct($discord)
    {
        $this->discord = $discord;
    }

    public static function charIn($str)
    {
        for ($i = 0; $i <= strlen($str); $i++) {
            yield substr($str, $i, 1);
        }
    }

    public function send($msg, $txt, $embed = null)
    {
        return $msg->channel->sendMessage($txt, false, $embed)
            ->otherwise(function($e) use ($msg) {
                echo $e->getMessage(), PHP_EOL;
                $this->pingMe($e->getMessage());
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



    public static function scriptFromAscii($string)
    {
        $ret = "";
        foreach (charin($string) as $char) {
            if (ord($char) >= ord('0') && ord($char) <= ord('9')) {
                $d = dechex(0x1d7ce + dechex(ord($char) - ord('0')));
            } elseif (ord($char) >= ord('a') && ord($char) <= ord('z')) {
                $d = dechex(0x1d4ea + dechex(ord($char) - ord('a')));
            } elseif (ord($char) >= ord('A') && ord($char) <= ord('Z')) {
                $d = dechex(0x1d4d0 + dechex(ord($char) - ord('A')));
            } else {
                continue;
            }
            $ret .= self::utf8_chr($d) . " ";
        }
        return $ret;
    }


    public static function utf8_chr( $code_point )
    {
        if( ( $i = ( int ) $code_point ) !== $code_point )
        {
            //$code_point is a string, lets extract int code point from it

            if( !( $i = ( int ) self::utf8_hex_to_int( $code_point ) ) )
            {
                return '';
            }
        }

        if( mbstring_loaded( ) /*extension_loaded( 'mbstring' )*/ )
        {
            return mb_convert_encoding( "&#$i;" , 'UTF-8' , 'HTML-ENTITIES' );
        }
        else if( version_compare( phpversion( ) , '5.0.0' ) === 1 )
        {
            //html_entity_decode did not support Multi-Byte before PHP 5.0.0
            return html_entity_decode( "&#{$i};" , ENT_QUOTES, 'UTF-8' );
        }


        //Fallback

        $bits   = ( int ) ( log( $i , 2 ) + 1 );

        if( $bits <= 7 )                //Single Byte
        {
            return chr( $i );
        }
        else if( $bits <= 11 )          //Two Bytes
        {
            return chr( ( ( $i >> 6 ) & 0x1F ) | 0xC0 ) . chr( ( $i & 0x3F ) | 0x80 );
        }
        else if( $bits <= 16 )          //Three Bytes
        {
            return chr( ( ( $i >> 12 ) & 0x0F ) | 0xE0 ) . chr( ( ( $i >> 6 ) & 0x3F ) | 0x80 ) . chr( ( $i & 0x3F ) | 0x80 );
        }
        else if( $bits <=21 )           //Four Bytes
        {
            return chr( ( ( $i >> 18 ) & 0x07 ) | 0xF0 ) . chr( ( ( $i >> 12 ) & 0x3F ) | 0x80 ) . chr( ( ( $i >> 6 ) & 0x3F ) | 0x80 ) . chr( ( $i & 0x3F ) | 0x80 );
        }
        else
        {
            return '';  //Cannot be encoded as Valid UTF-8
        }
    }

    public static function utf8_hex_to_int( $str )
    {
        if( preg_match( '/^(?:\\\u|U\+|)([a-z0-9]{4,6})$/i' , $str , $match ) )
        {
            return ( int ) hexdec( $match[1] );
        }

        return 0;
    }

}
