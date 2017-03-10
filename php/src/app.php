<?php

///////////////////////////////////////////////////////////
// config
///////////////////////////////////////////////////////////

include __DIR__.'/../vendor/autoload.php';
$token = require __DIR__.'/../secret_token.php';

$start_time = microtime(true);

$discord = new \Discord\DiscordCommandClient([
    'token' => $token,
    'prefix' => '!',
    'description' => "benh's bot made with DiscordPHP",
]);

// $discord->on('ready', function($discord) {
//     $start_time = microtime(true);
//     echo $start_time, PHP_EOL;
// });



///////////////////////////////////////////////////////////
// commands
///////////////////////////////////////////////////////////

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
]);

$discord->registerCommand('hi', [
    'hey',
    'hello',
    'wussup'
]);


$discord->registerCommand('time', function($message) {
    $message->channel->sendMessage("It's " . date('g:i A \o\n F j, Y'));
}, [
    'description' => 'current time'
]);


$discord->registerCommand('roll', function ($message, $params) {
    $message->reply('you rolled a ' . rand(1, $params[0] ?? 6));
}, [
    'description' => 'rolls an n-sided die. defaults to 6.',
    'usage' => '<number of sides>',
]);


$discord->registerCommand('text_benh', function($message, $params) {
    if (count($params) === 0) {
        $message->channel->sendMessage('missing message');
        return;
    }
    print_r($message->author);
    if (mail("9068690061@vtext.com", "", implode($params, " "), "From: {$message->author->user->username} <{$message->author->user->username}@benharri.com>")) {
        return "message sent";
    }
}, [
    'description' => 'send a message to benh off discord',
    'usage' => '<message>',
]);


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


$discord->registerCommand('up', function($message, $params) use ($start_time) {
    $message->channel->sendMessage("Up for " . gmdate('H:i:s', microtime(true) - $start_time));
}, [
    'description' => 'bot uptime',
    'usage' => '',
]);






return $discord;

