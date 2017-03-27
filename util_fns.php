<?php

require __DIR__.'/vendor/autoload.php';
use React\Promise\Deferred;
use Discord\Parts\Embed\Embed;
use Carbon\Carbon;

function charIn($str)
{
    for ($i = 0; $i <= strlen($str); $i++)
        yield substr($str, $i, 1);
}


function send($msg, $txt, $embed = null)
{
    return $msg->channel->sendMessage($txt, false, $embed)
        ->otherwise(function($e) use ($msg) {
            echo $e->getMessage(), PHP_EOL;
            pingMe($e->getMessage());
            $msg->reply("sry, an error occurred. check with <@193011352275648514>.\n```{$e->getMessage()}```");
        });
}


function sendFile($msg, $filepath, $filename, $txt)
{
    return $msg->channel->sendFile($filepath, $filename, $txt)
        ->otherwise(function($e) use ($msg) {
            echo $e->getMessage(), PHP_EOL;
            pingMe($e->getMessage());
            $msg->reply("sry, an error occurred. check with <@193011352275648514>.\n```{$e->getMessage()}```");
        });
}


function isDM($msg)
{
    return $msg->channel->is_private;
}


function timestampFromSnowflake($id)
{
    return (($id / 4194304) + 1420070400000) / 1000;
}


function asciiFromImg($filepath)
{
    $ret = "";
    $img = imagecreatefromstring(file_get_contents($filepath));
    list($width, $height) = getimagesize($filepath);
    $scale = 10;
    $chars = [
        ' ', '\'', '.', ':',
        '|', 'T',  'X', '0',
        '#',
    ];
    $chars = array_reverse($chars);
    $c_count = count($chars);
    for ($y = 0; $y <= $height - $scale - 1; $y += $scale) {
        for ($x = 0; $x <= $width - ($scale / 2) - 1; $x += ($scale / 2)) {
            $rgb = imagecolorat($img, $x, $y);
            $r = (($rgb >> 16) & 0xFF);
            $g = (($rgb >> 8) & 0xFF);
            $b = ($rgb & 0xFF);
            $sat = ($r + $g + $b) / (255 * 3);
            $ret .= $chars[ (int)( $sat * ($c_count - 1) ) ];
        }
        $ret .= "\n";
    }
    return $ret;
}

function checkForSwears($text)
{
    $text = $text;
    $bad_words = file_get_contents(__DIR__.'/swearWords.txt');
    $b = '/\W' . $bad_words . '\W/i';

    if(preg_match($b, $text)){
        return true;
    } else {
        return false;
    }
}


function fahr($celsius)
{
    return $celsius * 9 / 5 + 32;
}

function cels($fahrenh)
{
    return $fahrenh * 5 / 9 - 32;
}

function formatWeatherJson($json, $timezone = null)
{
    global $discord;

    return $discord->factory(Embed::class, [
        'title' => "Weather in {$json->name}, {$json->sys->country}",
        'thumbnail' => ['url' => "http://openweathermap.org/img/w/{$json->weather[0]->icon}.png"],
        'fields' => [
            ['name' => 'Current temperature'
            , 'value' => "{$json->main->temp}°C (".fahr($json->main->temp)."°F)"
            , 'inline' => true
            ],
            ['name' => 'Low/High Forecasted Temp'
            , 'value' => "{$json->main->temp_min}/{$json->main->temp_max}°C  " . fahr($json->main->temp_min) . "/" . fahr($json->main->temp_max) . "°F"
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



function registerHelp($cmd_name)
{
    global $discord; global $help;
    $help[$cmd_name] = $discord->getCommand($cmd_name)->getHelp(';')["text"];
}


function askCleverbot($input)
{
    $deferred = new Deferred();
    global $discord;

    $url = "https://www.cleverbot.com/getreply";
    $key = getenv('CLEVERBOT_API_KEY');
    $input = rawurlencode($input);
    $discord->http->get("$url?input=$input&key=$key", null, [], false)->then(function($apidata) use ($deferred) {
        $deferred->resolve($apidata);
    }, function ($e) {
        $deferred->reject($e);
    });

    return $deferred->promise();
}

function pingMe($msg)
{
    global $discord;
    $discord
        ->guilds->get('id','289410862907785216')
        ->channels->get('id','289611811094003715')
        ->sendMessage("<@193011352275648514>, $msg");
}


