<?php

///////////////////////////////////////////////////////////
// config
///////////////////////////////////////////////////////////

include __DIR__.'/vendor/autoload.php';
include __DIR__.'/definitions.php';

$start_time = microtime(true);
$definitions = new Definitions();

$discord = new \Discord\DiscordCommandClient([
    'token' => file_get_contents(__DIR__.'/token'),
    'prefix' => '!',
    'description' => "benh's bot made with DiscordPHP",
]);






///////////////////////////////////////////////////////////
// commands
///////////////////////////////////////////////////////////


///////////////////////////////////////////////////////////
$discord->registerCommand('ping', function($message) {
    $message->channel->sendMessage('pong');
}, [
    'description' => 'pong!',
]);


///////////////////////////////////////////////////////////
$discord->registerCommand('hello', [
    'hey there',
    'how are you',
    'sup',
    'thanks'
]);

///////////////////////////////////////////////////////////
$discord->registerCommand('hi', [
    'hey',
    'hello',
    'wussup'
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
$discord->registerCommand('weather', function($message, $params) {
    return ["sunny", "cloudy", "bad"];
}, [
    'description' => 'gets weather for a location',
    'usage' => '<location>',
]);


///////////////////////////////////////////////////////////
$discord->registerCommand('8ball', function($message, $params) {
    return ["no", "yes", "what the hell are you thinking"];
}, [
    'description' => 'tells your fortune',
    'usage' => '',
]);



return $discord;

