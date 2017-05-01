<?php
namespace BenBot\Commands;

use BenBot\Utils;

final class Hangman
{
    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;

        self::$bot->hangman['gallows'] = "
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


    public static function isActive($msg)
    {
        $gameid = $msg->channel->id;
        return isset(self::$bot->hangman[$gameid])
        && self::$bot->hangman[$gameid]['active'];
    }


    public static function isGameOriginator($msg)
    {
        return Utils::isDM($msg)
        && self::$bot->hangman['readygame']['originator']->id === $msg->author->id;
    }


    public static function initGameWithWord($msg)
    {
        $gameid = self::$bot->hangman['readygame']['gameid'];
        self::$bot->hangman[$gameid]['secret_word'] = $msg->content;
        self::$bot->hangman[$gameid]['active'] = true;
        Utils::send(self::$bot->hangman['readygame']['origmsg'], "Game ready\n```" . self::$gallows . "```");
    }


    public static function startGame($msg, $args)
    {
        $gameid = $msg->channel->id;
        self::$bot->hangman[$gameid] = [
            'active' => false,
            'gallows' => '
           _______
          |/      |
          |
          |
          |
          |
          |
         _|___
         '
        ];
        self::$bot->hangman['readygame'] = [
            'originator' => $msg->author,
            'gameid' => $gameid,
            'origmsg' => $msg,
        ];
        $msg->author->user->sendMessage("enter the secret word")->otherwise(function ($e) {
            echo $e->getMessage(), PHP_EOL;
            echo $e->getTraceAsString(), PHP_EOL;
        });
        return "waiting for {$msg->author} to enter a word";
    }

}
