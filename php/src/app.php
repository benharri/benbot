<?php

include __DIR__.'/../vendor/autoload.php';
$token = require __DIR__.'/../secret_token.php';


$discord = new \Discord\DiscordCommandClient([
    'token' => $token,
    'prefix' => '!',
]);

$discord->on('ready', function($discord) {
    echo "Bot is ready.", PHP_EOL;
});




$discord->registerCommand('ping', function($message) {
    $message->channel->sendMessage('pong');
}, [
    'description' => 'pong!',
]);


$discord->registerCommand('hello', [
    'hey there',
    'how are you',
    'sup',
    'thanks'
], [
    'aliases' => [
        'hey',
        'hi',
        'wassup',
        'sup',
    ]
]);


$discord->registerCommand('time', function($message) {
    $message->channel->sendMessage("It's " . date('g:i A \o\n F j, Y'));
}, [
    'description' => 'current time'
]);


$roll = $discord->registerCommand('roll', function ($message, $params) {
    // $message->reply(print_r($params, true));
    $message->channel->sendMessage(rand(1, $params[0] ?? 6));
}, [
    'description' => 'rolls an n-sided die. defaults to 6.',
    'usage' => '<number of sides>',
]);







return $discord;

