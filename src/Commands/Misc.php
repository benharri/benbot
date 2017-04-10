<?php
namespace BenBot\Commands;
error_reporting(-1);

use BenBot\Utils;

class Misc {

    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;

        self::$bot->registerCommand('hi', [__CLASS__, 'hi'], [
            'description' => 'just wanted to say hi',
        ]);
        self::$bot->registerCommand('text_benh', [__CLASS__, 'textBenh'], [
            'description' => 'sends an SMS to benh',
            'usage' => '<message>',
            'aliases' => [
                'textben',
            ],
            'registerHelp' => true,
        ]);

        echo __CLASS__ . " registered", PHP_EOL;
    }



    public static function hi($msg, $args)
    {
        $greetings = [
            "hello {$msg->author}, how are you today?",
            "soup",
            "hi!",
            "henlo",
            "hallo!",
            "hola",
            "wassup",
        ];
        return $greetings[array_rand($greetings)];
    }

    public static function textBenh($msg, $args)
    {
        if (count($args) === 0) {
            return "can't send a blank message, silly";
        }

        $server = $msg->channel->guild->name;
        $user = Utils::isDM($msg) ? $msg->author->username : $msg->author->user->username;
        $from = "From: {$server} Discord <{$server}@bot.benharris.ch>";
        $msg_body = "$user:\n\n" . implode(" ", $args);

        if (mail(getenv('PHONE_NUMBER')."@vtext.com", "", $msg_body, $from)) {
            return "message sent to benh";
        }
    }
}
