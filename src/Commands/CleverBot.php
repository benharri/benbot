<?php
namespace BenBot\Commands;
error_reporting(-1);

use BenBot\Utils;
use React\Promise\Deferred;

class CleverBot {

    private static $bot;
    private static $cs;

    public static function register(&$that)
    {
        self::$bot = $that;
        self::$cs  = [];

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
        // self::askCleverbot(implode(" ", $args), self::$cs[$msg->channel->id] ?? "")->then(function ($result) use ($msg) {
        //     self::$cs[$msg->channel->id] = $apidata->cs;
        //     Utils::send($msg, $result->output);
        // });

        $url = "https://www.cleverbot.com/getreply";
        $key = getenv('CLEVERBOT_API_KEY');
        $input = rawurlencode(implode(" ", $args));

        $url .= "?input=$input&key=$key" . isset(self::$cs[$msg->channel->id]) ? "&cs=" . self::$cs[$msg->channel->id] : "";

        self::$bot->http->get($url, null, [], false)->then(function ($apidata) use ($msg) {
            self::$cs[$msg->channel->id] = $apidata->cs;
            Utils::send($msg, $apidata->output);
        });
    }


    public static function askCleverbot($input, $cs)
    {
        $deferred = new Deferred();

        $url = "https://www.cleverbot.com/getreply";
        $key = getenv('CLEVERBOT_API_KEY');
        $input = rawurlencode($input);

        self::$bot->http->get("$url?input=$input&key=$key" . $cs == "" ? "" : "&cs=$cs", null, [], false)->then(function ($apidata) use ($deferred) {
            $deferred->resolve($apidata);
        }, function ($e) {
            $deferred->reject($e);
        });

        return $deferred->promise();
    }
}
