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

        self::$gallows = explode('==', file_get_contents(self::$bot->dir.'/gallows.txt'));

        self::$bot->registerCommand('hangman', [__CLASS__, 'startGame'], [
            'description'  => 'play hangman. everyone in the channel plays. `;hangman stop` to cancel a game in progress.',
            'usage'        => '[stop]',
            'registerHelp' => true,
        ]);

        echo __CLASS__.' registered', PHP_EOL;
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
        self::$bot->hangman[$gameid]['secret_word'] = strtolower($msg->content);
        self::$bot->hangman[$gameid]['active'] = true;
        Utils::send(self::$bot->hangman['readymsg'], self::showGameState($gameid))->then(function ($result) use ($msg, $gameid) {
            Utils::deleteMessage(self::$bot->hangman[$gameid]['last_msg']);
            self::$bot->hangman[$gameid]['last_msg'] = $result;
            Utils::deleteMessage($msg);
        });
        self::$bot->hangman['readymsg'] = null;
    }

    public static function handleMove($msg)
    {
        $gameid = $msg->channel->id;
        $text = strtolower($msg->content);
        if (strlen($text) === 1) {
            if (in_array($text, self::$bot->hangman[$gameid]['guessed_letters'])) {
                Utils::send($msg, self::showGameState($gameid)."\nalready guessed...")->then(function ($result) use ($msg, $gameid) {
                    Utils::deleteMessage(self::$bot->hangman[$gameid]['last_msg']);
                    self::$bot->hangman[$gameid]['last_msg'] = $result;
                    Utils::deleteMessage($msg);
                });

                return;
            } else {
                self::$bot->hangman[$gameid]['guessed_letters'][] = $text;
                if (!in_array($text, str_split(self::$bot->hangman[$gameid]['secret_word']))) {
                    self::$bot->hangman[$gameid]['state']++;
                    if (self::$bot->hangman[$gameid]['state'] >= 7) {
                        self::$bot->hangman[$gameid]['active'] = false;
                        self::$bot->hangman[$gameid]['guessed_letters'] = array_unique(str_split(self::$bot->hangman[$gameid]['secret_word']));
                        Utils::send($msg,
                            self::showGameState($gameid).
                            "\n**you lose**. the word was:\n".
                            self::$bot->hangman[$gameid]['secret_word']
                        )->then(function ($result) use ($msg, $gameid) {
                            Utils::deleteMessage(self::$bot->hangman[$gameid]['last_msg']);
                            self::$bot->hangman[$gameid]['last_msg'] = $result;
                            Utils::deleteMessage($msg);
                        });

                        return;
                    }
                } else {
                    $secretletters = array_unique(str_split(self::$bot->hangman[$gameid]['secret_word']));
                    if (count(array_intersect($secretletters, self::$bot->hangman[$gameid]['guessed_letters'])) === count($secretletters)) {
                        self::$bot->hangman[$gameid]['active'] = false;
                        self::$bot->hangman[$gameid]['guessed_letters'] = $secretletters;
                        Utils::send($msg, self::showGameState($gameid)."\n**you win!**")->then(function ($result) use ($msg, $gameid) {
                            Utils::deleteMessage(self::$bot->hangman[$gameid]['last_msg']);
                            self::$bot->hangman[$gameid]['last_msg'] = $result;
                            Utils::deleteMessage($msg);
                        });

                        return;
                    }
                }
            }
            Utils::send($msg, self::showGameState($gameid))->then(function ($result) use ($msg, $gameid) {
                Utils::deleteMessage(self::$bot->hangman[$gameid]['last_msg']);
                self::$bot->hangman[$gameid]['last_msg'] = $result;
                Utils::deleteMessage($msg);
            });
        } elseif ($text === strtolower(self::$bot->hangman[$gameid]['secret_word'])) {
            self::$bot->hangman[$gameid]['active'] = false;
            self::$bot->hangman[$gameid]['guessed_letters'] = array_unique(str_split(self::$bot->hangman[$gameid]['secret_word']));
            Utils::send($msg, self::showGameState($gameid)."\nyou guessed the word! **you win!!**")->then(function ($result) use ($msg, $gameid) {
                Utils::deleteMessage(self::$bot->hangman[$gameid]['last_msg']);
                self::$bot->hangman[$gameid]['last_msg'] = $result;
                Utils::deleteMessage($msg);
            });
        } elseif ($text === ';hangman stop') {
            self::$bot->hangman[$gameid]['active'] = false;
            self::$bot->hangman[$gameid]['guessed_letters'] = array_unique(str_split(self::$bot->hangman[$gameid]['secret_word']));
            Utils::send($msg,
                self::showGameState($gameid).
                "\n**game forfeited**. the word was:\n".
                self::$bot->hangman[$gameid]['secret_word']
            )->then(function ($result) use ($msg, $gameid) {
                Utils::deleteMessage(self::$bot->hangman[$gameid]['last_msg']);
                self::$bot->hangman[$gameid]['last_msg'] = $result;
                Utils::deleteMessage($msg);
            });
        }
    }

    public static function startGame($msg, $args)
    {
        $gameid = $msg->channel->id;
        self::$bot->hangman[$gameid] = [
            'active'          => false,
            'state'           => 0,
            'guessed_letters' => [' '],
        ];
        self::$bot->hangman['readymsg'] = $msg;
        $msg->author->user->sendMessage("enter the secret word")->otherwise(function ($e) use ($msg) {
            Utils::logError($e, $msg);
        });
        Utils::send($msg, "waiting for {$msg->author} to enter a word")->then(function ($result) use ($gameid) {
            self::$bot->hangman[$gameid]['last_msg'] = $result;
        });
    }

    private static function showSecretWord($gameid)
    {
        $ret = 'Word: ';
        foreach (s(self::$bot->hangman[$gameid]['secret_word']) as $char) {
            $ret .= $char == ' ' ? ' ' : in_array($char, self::$bot->hangman[$gameid]['guessed_letters']) ? $char : '_';
            $ret .= ' ';
        }

        return $ret;
    }

    private static function showGameState($gameid)
    {
        return '```'.self::$gallows[self::$bot->hangman[$gameid]['state']]."\n".
            self::showSecretWord($gameid)."\n\n".
            'Incorrect letters: '.implode(' ', array_diff(self::$bot->hangman[$gameid]['guessed_letters'], str_split(self::$bot->hangman[$gameid]['secret_word'])))."\n".
            'Guessed letters:'.implode(' ', self::$bot->hangman[$gameid]['guessed_letters']).'```';
    }
}
