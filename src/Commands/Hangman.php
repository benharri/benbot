<?php
namespace BenBot\Commands;

use BenBot\Utils;

use function Stringy\create as s;

final class Hangman
{
    private static $bot;
    private static $gallows;

    public static function register(&$that)
    {
        self::$bot = $that;

        self::$gallows = explode("==", file_get_contents(self::$bot->dir . "/gallows.txt"));

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
        && self::$bot->hangman['readymsg']->author->id === $msg->author->id;
    }


    public static function initGameWithWord($msg)
    {
        $gameid = self::$bot->hangman['readymsg']->channel->id;
        self::$bot->hangman[$gameid]['secret_word'] = $msg->content;
        self::$bot->hangman[$gameid]['active'] = true;
        Utils::send(self::$bot->hangman['readymsg'], self::showGameState($gameid));
        self::$bot->hangman['readymsg'] = null;
    }


    public static function handleMove($msg)
    {
        $gameid = $msg->channel->id;
        if (strlen($msg->content) === 1) {
            self::$bot->hangman[$gameid]['guessed_letters'][] = $msg->content;
            if (!in_array($msg->content, str_split(self::$bot->hangman[$gameid]['secret_word']))) {
                self::$bot->hangman[$gameid]['state']++;
            }
            Utils::send($msg, self::showGameState($gameid));
        }
    }


    public static function startGame($msg, $args)
    {
        $gameid = $msg->channel->id;
        self::$bot->hangman[$gameid] = [
            'active' => false,
            'state' => 0,
            'guessed_letters' => [],
        ];
        self::$bot->hangman['readymsg'] = $msg;
        $msg->author->user->sendMessage("enter the secret word")->otherwise(function ($e) {
            echo $e->getMessage(), PHP_EOL;
            echo $e->getTraceAsString(), PHP_EOL;
        });
        return "waiting for {$msg->author} to enter a word";
    }


    private static function showSecretWord($gameid)
    {
        $ret = "Word: ";
        foreach (s(self::$bot->hangman[$gameid]['secret_word']) as $char) {
            $ret .= in_array($char, self::$bot->hangman[$gameid]['guessed_letters']) ? $char : "_";
            $ret .= " ";
        }
        return $ret;
    }


    private static function showGameState($gameid)
    {
        return "```" . self::$gallows[self::$bot->hangman[$gameid]['state']] . "\n" .
            self::showSecretWord($gameid) . "\n" .
            "Incorrect letters: " . implode(" ", array_diff(self::$bot->hangman[$gameid]['guessed_letters'], str_split(self::$bot->hangman[$gameid]['secret_word']))) . "\n" .
            "Guessed letters: " . implode(" ", self::$bot->hangman[$gameid]['guessed_letters']) . "```";
    }

}
