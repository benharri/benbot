<?php

///////////////////////////////////////////////////////////
// config
///////////////////////////////////////////////////////////

include __DIR__.'/vendor/autoload.php';
include __DIR__.'/kaomoji.php';
include __DIR__.'/definitions.php';

$start_time = microtime(true);
$definitions = new Definitions();

$discord = new \Discord\DiscordCommandClient([
    'token' => file_get_contents(__DIR__.'/token'),
    'prefix' => ';',
    'description' => "benh's bot made with DiscordPHP",
]);






///////////////////////////////////////////////////////////
// commands
///////////////////////////////////////////////////////////


///////////////////////////////////////////////////////////
$discord->registerCommand('ping', function($message) {
    $message->channel->sendMessage('pong');
}, [
    'description' => 'ping pong',
    'usage' => '',
]);

///////////////////////////////////////////////////////////
$discord->registerCommand('ding', function($message, $params) {
    $message->channel->sendMessage('dong');
}, [
    'description' => 'dong',
    'usage' => '',
]);


///////////////////////////////////////////////////////////
$discord->registerCommand('hi', [
    'hey',
    'hello',
    'wussup'
], [
    'description' => 'greeting',
    'usage' => '',
]);





///////////////////////////////////////////////////////////
$discord->registerCommand('time', function($message) {
    $message->channel->sendMessage("It's " . date('g:i A \o\n F j, Y'));
}, [
    'description' => 'current time'
]);


///////////////////////////////////////////////////////////
$discord->registerCommand('roll', function ($message, $params) {
    $message->reply('you rolled a ' . rand(1, $params[0] ?? 6));
}, [
    'description' => 'rolls an n-sided die. defaults to 6.',
    'usage' => '<number of sides>',
]);


///////////////////////////////////////////////////////////
$discord->registerCommand('text_benh', function($message, $params) {
    if (count($params) === 0) {
        $message->channel->sendMessage('missing message');
        return;
    }
    if (mail("9068690061@vtext.com", "", implode($params, " "), "From: {$message->author->user->username} <{$message->author->user->username}@benharri.com>")) {
        return "message sent";
    }
}, [
    'description' => 'send a message to benh off discord',
    'usage' => '<message>',
]);


///////////////////////////////////////////////////////////
$discord->registerCommand('avatar', function($message, $params) {
    if (count($message->mentions) === 0) {
        $message->channel->sendMessage($message->author->user->avatar);
        return;
    }
    foreach ($message->mentions as $av)
        $message->channel->sendMessage($av->avatar);
}, [
    'description' => 'gets the avatar for a user',
    'usage' => '<@user>',
]);


///////////////////////////////////////////////////////////
$discord->registerCommand('up', function($message, $params) use ($start_time) {
    $message->channel->sendMessage("Up for " . gmdate('H:i:s', microtime(true) - $start_time));
}, [
    'description' => 'bot uptime',
    'usage' => '',
]);


///////////////////////////////////////////////////////////
$discord->registerCommand('say', function($message, $params) {
    $message->channel->sendMessage(implode($params, ' '));
}, [
    'description' => 'repeats stuff back to you',
    'usage' => '<stuff to say>',
]);



///////////////////////////////////////////////////////////
$discord->registerCommand('set', function($message, $params) use ($definitions) {
    $def = array_shift($params);
    $definitions->set($def, implode($params, " "));
    $message->channel->sendMessage($def . " set to: " . implode($params, " "));
}, [
    'description' => 'sets this to that',
    'usage' => '<this> <that>',
]);
///////////////////////////////////////////////////////////
$discord->registerCommand('get', function($message, $params) use ($definitions) {
    $message->channel->sendMessage($params[0] . ": " . $definitions->get($params[0]));
}, [
    'description' => 'gets a value from the defintions',
    'usage' => '<thing to get>',
]);
///////////////////////////////////////////////////////////
$discord->registerCommand('unset', function($message, $params) use ($definitions) {
    $definitions->unset($params[0]);
    $message->channel->sendMessage($params[0] . " unset");
}, [
    'description' => 'removes a definition',
    'usage' => '<def to remove>',
]);
///////////////////////////////////////////////////////////
$discord->registerCommand('listdefs', function($message, $params) use ($definitions) {
    $message->channel->sendMessage((string)$definitions);
}, [
    'description' => 'lists all definitions',
    'usage' => '',
]);


///////////////////////////////////////////////////////////
$discord->registerCommand('dank', function($message) {
    $message->channel->sendMessage('memes');
});


///////////////////////////////////////////////////////////
$discord->registerCommand('weather', [
    "sunny", "cloudy", "bad"
], [
    'description' => 'gets weather for a location',
    'usage' => '<location>',
]);


///////////////////////////////////////////////////////////
$discord->registerCommand('8ball',  [
    "no", "yes", "what the hell are you thinking"
], [
    'description' => 'tells your fortune',
    'usage' => '',
]);


///////////////////////////////////////////////////////////
$discord->registerCommand('lenny', function($message, $params) {
    $message->channel->sendMessage("( ͡° ͜ʖ ͡°)");
    $channel = $message->channel;
    $channel->deleteMessages([$message]);
}, [
    'description' => 'lenny face ( ͡° ͜ʖ ͡°)',
    'usage' => '',
]);


///////////////////////////////////////////////////////////
$kaomoji = $discord->registerCommand('kaomoji', function($message, $params) use ($kaomojis) {
    $message->channel->sendMessage($kaomojis[array_rand($kaomojis)]);
}, [
    'description' => 'sends random kaomoji',
    'usage' => '',
]);

    $kaomoji->registerSubCommand('sad', function($message, $params) use($sad_kaomojis) {
        $message->channel->sendMessage($sad_kaomojis[array_rand($sad_kaomojis)]);
    }, [
        'description' => 'sends random sad kaomoji',
        'usage' => '',
    ]);
    $kaomoji->registerSubCommand('happy', function($message, $params) use($happy_kaomojis) {
        $message->channel->sendMessage($happy_kaomojis[array_rand($happy_kaomojis)]);
    }, [
        'description' => 'sends random happy kaomoji',
        'usage' => '',
    ]);
    $kaomoji->registerSubCommand('angry', function($message, $params) use($angry_kaomojis) {
        $message->channel->sendMessage($angry_kaomojis[array_rand($angry_kaomojis)]);
    }, [
        'description' => 'sends random angry kaomoji',
        'usage' => '',
    ]);
    $kaomoji->registerSubCommand('confused', function($message, $params) use($confused_kaomojis) {
        $message->channel->sendMessage($confused_kaomojis[array_rand($confused_kaomojis)]);
    }, [
        'description' => 'sends random confused kaomoji',
        'usage' => '',
    ]);
    $kaomoji->registerSubCommand('surprised', function($message, $params) use($surprised_kaomojis) {
        $message->channel->sendMessage($surprised_kaomojis[array_rand($surprised_kaomojis)]);
    }, [
        'description' => 'sends random surprised kaomoji',
        'usage' => '',
    ]);
    $kaomoji->registerSubCommand('embarrassed', function($message, $params) use($embarrassed_kaomojis) {
        $message->channel->sendMessage($embarrassed_kaomojis[array_rand($embarrassed_kaomojis)]);
    }, [
        'description' => 'sends random embarrassed kaomoji',
        'usage' => '',
    ]);




///////////////////////////////////////////////////////////
$joke = $discord->registerCommand('joke', function($message, $params) use ($var) {
    $json = json_decode(file_get_contents("http://tambal.azurewebsites.net/joke/random"));
    $message->channel->sendMessage($json->joke);
}, [
    'description' => 'tells a random joke',
    'usage' => '',
]);

    $joke->registerSubCommand('chucknorris', function($message, $params) {
        $json = json_decode(file_get_contents("http://api.icndb.com/jokes/random1"));
        $message->channel->sendMessage($json->value->joke);
    }, [
        'description' => 'get a random fact about chuck norris',
        'usage' => '',
    ]);

    $joke->registerSubCommand('yomama', function($message, $params) {
        $json = json_decode(file_get_contents("http://api.yomomma.info/"));
        $message->channel->sendMessage($json->joke);
    }, [
        'description' => 'yo mama jokes',
        'usage' => '',
    ]);

    $joke->registerSubCommand('dad', function($message, $params) {
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => 'Accept: text/plain'
            ]
        ];
        $context = stream_context_create($opts);
        $message->channel->sendMessage(file_get_contents("https://icanhazdadjoke.com/", false, $context));
    }, [
        'description' => 'tells a dad joke',
        'usage' => '',
    ]);


///////////////////////////////////////////////////////////
$discord->registerCommand('meme', function($message, $params) use ($memes) {

}, [
    'description' => 'get a meme',
    'usage' => '',
]);










$discord->run();

