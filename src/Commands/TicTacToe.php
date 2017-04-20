<?php
namespace BenBot\Commands;

use BenBot\Utils;

class TicTacToe {

    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;

        $tic = self::$bot->registerCommand('tic', [__CLASS__, 'startGame'], [
            'description' => 'play tic tac toe!',
            'usage' => '<@user>',
            'registerHelp' => true,
        ]);
            $tic->registerSubCommand('stop', [__CLASS__, 'stopGame'], [
                'description' => 'stops current game',
            ]);

        echo __CLASS__ . " registered", PHP_EOL;
    }


    public static function startGame($msg, $args)
    {
        self::$bot->game = [
            'board' => [],
            'game' => 'TicTacToe',
            'players' => [
                $msg->author->id
            ],
            'turn' => 0,
            'active' => false,
        ];
        if (count($msg->mentions) == 0) {
            self::$bot->game['pending'] = true;
            return "whom would you like to play with?";
        } elseif (count($msg->mentions) == 1) {
            self::$bot->game['players'][1] = $msg->mentions[0]->id;
            self::$bot->game['active'] = true;
            Utils::send($msg, "<@" . self::$bot->game['players'][0] . ">, it's your turn!");
        } else {
            return "can't play tictactoe with more than two people!";
        }
    }


    public static function stopGame($msg, $args)
    {
        Utils::deleteMessage($msg);
        self::$bot->game = [];
        Utils::send($msg, "game stopped")->then(function ($result) {
            self::$bot->loop->addTimer(5, function ($timer) use ($result) {
                Utils::deleteMessage($result);
            });
        });
    }

}
