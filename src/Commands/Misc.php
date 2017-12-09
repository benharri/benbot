<?php

namespace BenBot\Commands;

use BenBot\Utils;
use function Stringy\create as s;

final class Misc
{
    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;

        self::$bot->registerCommand('hi', [__CLASS__, 'hi'], [
            'description' => 'just wanted to say hi',
        ]);
        self::$bot->registerCommand('text_benh', [__CLASS__, 'textBenh'], [
            'description' => 'sends an SMS to benh',
            'usage'       => '[message]',
            'aliases'     => [
                'textben',
            ],
            'registerHelp' => true,
        ]);
        self::$bot->registerCommand('say', [__CLASS__, 'say'], [
            'description' => 'says stuff back to you',
            'usage'       => '[stuff to say]',
        ]);
        self::$bot->registerCommand('dm', [__CLASS__, 'dm'], [
            'description'  => 'sends a dm',
            'usage'        => '[@user] [message]',
            'registerHelp' => true,
            'aliases'      => [
                'pm',
            ],
        ]);
        self::$bot->registerCommand('avatar', [__CLASS__, 'avatar'], [
            'description' => 'gets avatar for a user (gets your own if you don\'t mention anyone)',
            'usage'       => '<@user>',
            'aliases'     => [
                'profilepic',
                'pic',
                'userpic',
            ],
        ]);

        echo __CLASS__.' registered', PHP_EOL;
    }

    public static function hi($msg, $args)
    {
        $greetings = [
            "hello {$msg->author}, how are you today?",
            'soup',
            'hi!',
            'henlo',
            'hallo!',
            'hola',
            'wassup',
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
        $msg_body = "$user:\n\n".implode(' ', $args);

        if (mail(getenv('PHONE_NUMBER').'@vtext.com', '', $msg_body, $from)) {
            return 'message sent to benh';
        }
    }

    public static function say($msg, $args)
    {
        $a = s(implode(' ', $args));
        if ($a->contains('@everyone') || $a->contains('@here')) {
            return "sorry can't do that! :P";
        }
        Utils::send($msg, "$a\n\n**love**, {$msg->author}")->then(function ($result) use ($msg) {
            Utils::deleteMessage($msg);
        });
    }

    public static function sing($msg, $args)
    {
        $a = s(implode(' ', $args));
        if ($a->contains('@everyone') || $a->contains('@here')) {
            return "sorry can't do that! :P";
        }
        Utils::send($msg, ":musical_note::musical_note::musical_note::musical_note::musical_note::musical_note:\n\n$a\n\n:musical_note::musical_note::musical_note::musical_note::musical_note::musical_note:, {$msg->author}")->then(function ($result) use ($msg) {
            Utils::deleteMessage($msg);
        });
    }

    public static function dm($msg, $args)
    {
        if (Utils::isDM($msg)) {
            return "you're already in a DM, silly";
        }
        if (count($msg->mentions) == 0) {
            $msg->author->user->sendMessage("hi, {$msg->author} said:\n".implode(' ', $args));
        } else {
            foreach ($msg->mentions as $mention) {
                $mention->sendMessage("hi!\n{$msg->author} said:\n\n".implode(' ', $args));
            }
        }
        Utils::send($msg, 'sent!')->then(function ($result) use ($msg) {
            Utils::deleteMessage($msg);
            self::$bot->loop->addTimer(3, function ($timer) use ($msg, $result) {
                Utils::deleteMessage($result);
            });
        });
    }

    public static function avatar($msg, $args)
    {
        if (count($msg->mentions) === 0) {
            if (Utils::isDM($msg)) {
                Utils::send($msg, $msg->author->avatar);
            } else {
                Utils::send($msg, $msg->author->user->avatar);
            }
        } else {
            foreach ($msg->mentions as $mention) {
                Utils::send($msg, $mention->avatar);
            }
        }
    }
}
