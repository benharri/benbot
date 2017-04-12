<?php
namespace BenBot\Commands;
error_reporting(-1);

use BenBot\Utils;
use React\Promise\Deferred;

class CleverBot {

    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;

        self::$bot->registerCommand('chat', [__CLASS__, 'chat'], [
            'description' => 'talk to benbot',
            'usage' => '<what you want to say>',
            'registerHelp' => true,
            'aliases' => [
                '',
                'cleverbot',
            ],
        ]);

        echo __CLASS__ . " registered", PHP_EOL;
    }



    public static function chat($msg, $args)
    {
        $msg->channel->broadcastTyping();
        self::askCleverbot(implode(" ", $args))->then(function ($result) use ($msg) {
            Utils::send($msg, $result->output);
        });
    }


    public static function askCleverbot($input)
    {
        $deferred = new Deferred();

        $url = "https://www.cleverbot.com/getreply";
        $key = getenv('CLEVERBOT_API_KEY');
        $input = rawurlencode($input);
        self::$bot->http->get("$url?input=$input&key=$key", null, [], false)->then(function ($apidata) use ($deferred) {
            $deferred->resolve($apidata);
        }, function ($e) {
            $deferred->reject($e);
        });

        return $deferred->promise();
    }
}