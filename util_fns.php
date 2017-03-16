<?php

require __DIR__.'/vendor/autoload.php';

function char_in($str) {
    for ($i = 0; $i <= strlen($str); $i++)
        yield substr($str, $i, 1);
}

function send($msg, $txt, $embed = null) {
    return $msg->channel->sendMessage($txt, false, $embed);
}

function is_dm($msg) {
    return $msg->author instanceOf Discord\Parts\User\User;
}


function ascii_from_img($filepath) {
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
    for($y = 0; $y <= $height - $scale - 1; $y += $scale) {
        for($x = 0; $x <= $width - ($scale / 2) - 1; $x += ($scale / 2)) {
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


function format_weather($json) {
    $fahr = round($json->main->temp * 5 / 9 + 32);
    $ret = <<<EOD
it's {$json->main->temp}°C ({$fahr}°F) with {$json->weather[0]->description} in {$json->name}, {$json->sys->country}
EOD;
    return $ret;
}

class Cleverbot_IO {
    const API_URL = "https://cleverbot.io/1.0";
    var $user;
    var $api_key;
    var $nick;
    var $client;

    public function __construct($nick = "benbot") {
        $this->user = file_get_contents(__DIR__.'/cleverbot.io.user');
        $this->api_key = file_get_contents(__DIR__.'/cleverbot.io.api_key');
        $this->client = new GuzzleHttp\Client([
            'base_uri' => self::API_URL,
            'headers' => [
                'Accept: */*',
                'content-type: application/x-www-form-urlencoded',
                'accept-encoding: gzip, deflate',
            ],
        ]);

        $request = $client->request('POST', 'create', [
            'form_params' => [
                'user' => $this->user,
                'key'  => $this->key,
                'nick' => $this->nick,
            ],
        ]);
        $response = $request->send();
        $json = $response->json();
        if ($json->status == "success")
            $this->nick = $json->nick;
        else print_r($response);
    }

    public function ask($query) {
        $request = $client->request('POST', 'create', [
            'form_params' => [
                'user' => $this->user,
                'key'  => $this->key,
                'nick' => $this->nick,
                'text' => $query,
            ],
        ]);
        $response = $request->send();
        $json = $respons->json();
        if ($json->status == "success")
            return $json->response;
        else print_r($response);
    }
}


function create_cleverbot_instance($nick = "benbot") {
    $json = json_decode(file_get_contents("https://cleverbot.io/1.0/create", false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => [
                'Accept: */*',
                'content-type: application/x-www-form-urlencoded',
                'accept-encoding: gzip, deflate',
            ],
            'content' => http_build_query([
                'user' => file_get_contents(__DIR__.'/cleverbot.io.user'),
                'key'  => file_get_contents(__DIR__.'/cleverbot.io.api_key'),
                'nick' => $nick,
            ]),
        ]
    ])));
    if ($json->status == "success")
        return $json->nick;
    else return "```invalid response\n{$json->status}```";
}


function query_cleverbot($query, $nick = "benbot") {
    $content = http_build_query([
        'user' => file_get_contents(__DIR__.'/cleverbot.io.user'),
        'key'  => file_get_contents(__DIR__.'/cleverbot.io.api_key'),
        'nick' => $nick,
        'text' => $query,
    ]);
    print_r($content);
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => [
                'Accept: */*',
                'content-type: application/x-www-form-urlencoded',
                'accept-encoding: gzip, deflate',
            ],
            'content' => $content,
        ]
    ]);
    print_r($context);

    $json = json_decode(file_get_contents("https://cleverbot.io/1.0/ask", false, $context));

    if ($json->status == "success")
        return $json->response;
    else return "```invalid response\n{$json->status}```";
}


function register_help($cmd_name) {
    global $discord; global $help;
    $help[$cmd_name] = $discord->getCommand($cmd_name)->getHelp(';')["text"];
}



