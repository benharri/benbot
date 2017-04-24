<?php
namespace BenBot\Commands;

use BenBot\Utils;

class TicTacToe {

    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;

        self::$bot->game['active'] = false;

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



    // 1 2 3
    // 4 5 6
    // 7 8 9
    // 0 = no piece, 1, 2 for players
    // for position i, x = intval(($i - 1) / 3), y = ($i - 1) % 3
    // $board = [
    //     [0, 0, 0],
    //     [0, 0, 0],
    //     [0, 0, 0],
    // ];


    // functions to register
    public static function startGame($msg, $args)
    {
        if (count($msg->mentions) === 0) {
            return "mention someone who you would like to play with!";
        } elseif (count($msg->mentions) === 1) {
            self::$bot->game = [
                'board' => [
                    [":one:", ":two:", ":three:"],
                    [":four:", ":five:", ":six:"],
                    [":seven:", ":eight:", ":nine:"],
                ],
                'game' => 'TicTacToe',
                'players' => [
                    ":x:" => $msg->author->id
                ],
                'turn' => ":x:",
                'active' => true,
            ];
            foreach ($msg->mentions as $mention) {
                self::$bot->game['players'][":o:"] = $mention->id;
            }
            Utils::send($msg, self::printBoard() . "\n<@" . self::$bot->game['players'][self::$bot->game['turn']] . ">, it's your turn!");
        } else {
            return "can't play tictactoe with more than two people!";
        }
    }


    public static function handleMove($player, $move)
    {
        if (self::placePieceAt($move, $player)) {
            if (self::checkWin()) {
                self::$bot->game['active'] = false;
                return "<@" . self::$bot->game['players'][self::$bot->game['turn']] . "> won";
            } else {
                self::$bot->game['turn'] = self::$bot->game['turn'] == ":x:" ? ":o:" : ":x:";
                return self::printBoard() . "\n<@" . self::$bot->game['players'][self::$bot->game['turn']] . ">, it's your turn!";
            }
        } else {
            return "position already occupied!";
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



    // internal functions
    private static function checkWin()
    {
        if ((self::getPieceAt(1) === self::getPieceAt(4)) && (self::getPieceAt(4) === self::getPieceAt(7))) {
            return self::getPieceAt(1);
        } else if ((self::getPieceAt(2) === self::getPieceAt(5)) && (self::getPieceAt(5) === self::getPieceAt(8))) {
            return self::getPieceAt(2);
        } else if ((self::getPieceAt(3) === self::getPieceAt(6)) && (self::getPieceAt(6) === self::getPieceAt(9))) {
            return self::getPieceAt(3);
        } else if ((self::getPieceAt(1) === self::getPieceAt(2)) && (self::getPieceAt(2) === self::getPieceAt(3))) {
            return self::getPieceAt(1);
        } else if ((self::getPieceAt(4) === self::getPieceAt(5)) && (self::getPieceAt(5) === self::getPieceAt(6))) {
            return self::getPieceAt(4);
        } else if ((self::getPieceAt(7) === self::getPieceAt(8)) && (self::getPieceAt(8) === self::getPieceAt(9))) {
            return self::getPieceAt(7);
        } else if ((self::getPieceAt(1) === self::getPieceAt(5)) && (self::getPieceAt(5) === self::getPieceAt(9))) {
            return self::getPieceAt(1);
        } else if ((self::getPieceAt(3) === self::getPieceAt(5)) && (self::getPieceAt(5) === self::getPieceAt(7))) {
            return self::getPieceAt(3);
        } else {
            return false;
        }
    }


    private static function printBoard()
    {
        $response = "";
        foreach (self::$bot->game['board'] as $row) {
            foreach ($row as $col) {
                $response .= $col;
            }
            $response .= "\n";
        }
        return $response;
    }


    private static function getPieceAt($i)
    {
        return self::$bot->game['board'][intval(($i - 1) / 3)][($i - 1) % 3];
    }

    private static function placePieceAt($i, $piece)
    {
        if (self::getPieceAt($i) == ":x:" || self::getPieceAt($i) == ":o:") {
            return false;
        } else {
            self::$bot->game['board'][intval(($i - 1) / 3)][($i - 1) % 3] = $piece;
            return true;
        }
    }


}
