<?php
namespace BenBot\Commands;

use BenBot\Utils;
use React\Promise\Deferred;

class CleverBot
{

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

        $url = "https://www.cleverbot.com/getreply";
        $key = getenv('CLEVERBOT_API_KEY');
        $input = rawurlencode(implode(" ", $args));

        $url .= "?input=$input&key=$key";
        if (isset(self::$cs[$msg->channel->id])) {
            $url .= "&cs=" . self::$cs[$msg->channel->id];
        }

        self::$bot->http->get($url, null, [], false)->then(function ($apidata) use ($msg) {
            self::$cs[$msg->channel->id] = $apidata->cs;
            Utils::send($msg, $apidata->output);
        });
    }

}
