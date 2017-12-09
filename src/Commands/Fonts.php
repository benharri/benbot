<?php

namespace BenBot\Commands;

use BenBot\FontConverter;
use BenBot\Utils;

final class Fonts
{
    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;

        self::$bot->registerCommand('fonts', [__CLASS__, 'fontlist'], [
            'description'  => 'change your message to another font',
            'registerHelp' => true,
            'aliases'      => [
                'listfonts',
                'font',
                'text',
                'fontlist',
            ],
        ]);
        self::$bot->registerCommand('block', [__CLASS__, 'blockText'], [
            'description' => 'block text',
            'usage'       => '<msg>',
        ]);

        echo __CLASS__.' registered', PHP_EOL;
    }

    public static function fontlist($msg, $args)
    {
        return "available fonts:\n```".implode(', ', array_keys(FontConverter::$fonts)).'```use the name of the font as a command';
    }

    public static function blockText($msg, $args)
    {
        Utils::send($msg, FontConverter::blockText(implode(' ', $args))."\n--{$msg->author}")->then(function ($result) use ($msg) {
            Utils::deleteMessage($msg);
        });
    }
}
