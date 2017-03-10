<?php

include __DIR__.'/../vendor/autoload.php';
$token = require __DIR__.'/../secret_token.php';


$discord = new \Discord\DiscordCommandClient([
    'token' => $token,
]);

$discord->on('ready', function($discord) {
    echo "Bot is ready.", PHP_EOL;


    // Listen for events here
    $discord->on('message', function($message) use ($discord) {


        echo "Received a message from {$message->author->username}: {$message->content}", PHP_EOL;
        // print_r($message);
        //
        //
        // if (is_cmd($message))
        //     if (!$message->author->user->bot) {
        //         parse_cmd($message);
        //         // print_r($message);
        //         // $message->channel->sendMessage("hello world");

        //     }


    });
});




$discord->registerCommand('ping', function($message) {
    return 'pong';
}, [
    'description' => 'pong!',
]);

$discord->registerCommand('hello', 'hey there');

$discord->registerCommand('time', function($message) {
    return "It's " . date('g:i a on F j, Y');
}, [
    'description' => 'time'
]);



return $discord;

