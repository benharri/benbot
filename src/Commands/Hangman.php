<?php
namespace BenBot\Commands;

use BenBot\Utils;

final class Hangman
{
    private static $bot;
    private static $gallows;

    public static function register(&$that)
    {
        self::$bot = $that;

        self::$gallows = "
           _______
          |/      |
          |      (_)
          |      \|/
          |       |
          |      / \
          |
         _|___
         ";

        self::$bot->registerCommand('hangman', [__CLASS__, 'startGame'], [
            'description' => 'play hangman. everyone in the channel can play',
        ]);

        echo __CLASS__ . " registered", PHP_EOL;
    }


    public static function startGame($msg, $args)
    {
        return "```" . self::$gallows . "```";
    }

}
