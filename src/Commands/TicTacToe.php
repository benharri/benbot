<?php
namespace BenBot\Commands;

use BenBot\Utils;

class TicTacToe
{

    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;

        $tic = self::$bot->registerCommand('tic', [__CLASS__, 'startGame'], [
            'description' => 'play tic tac toe!',
            'usage' => '<@user>',
            'registerHelp' => true,
            'aliases' => [
                'tictactoe',
                'tictac',
                'ttt',
            ],
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
            $gameid = $msg->channel->id;
            self::$bot->tictactoe[$gameid] = [
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
                self::$bot->tictactoe[$gameid]['players'][":o:"] = $mention->id;
            }
            Utils::send($msg, self::printBoard($gameid) . "\n<@" . self::$bot->tictactoe[$gameid]['players'][self::$bot->tictactoe[$gameid]['turn']] . ">, it's your turn!")->then(function ($result) use ($gameid, $msg) {
                self::$bot->tictactoe[$gameid]['last_msg'] = $result;
                Utils::deleteMessage($msg);
            });
        } else {
            return "can't play tictactoe with more than two people!";
        }
    }


    public static function isActive($msg)
    {
        $gameid = $msg->channel->id;

        return isset(self::$bot->tictactoe[$gameid])
        && self::$bot->tictactoe[$gameid]['active']
        && $msg->author->id === self::$bot->tictactoe[$gameid]['players'][self::$bot->tictactoe[$gameid]['turn']];
    }


    public static function handleMove($msg)
    {
        $gameid = $msg->channel->id;
        $player = self::$bot->tictactoe[$gameid]['turn'];
        $text   = $msg->content;
        $move   = intval($text);

        if (strtolower($text) == "stop" || strtolower($text) == ";tic stop") {
            self::stopGame($msg, []);
            return;
        }
        if ($move > 0 && $move < 10) {
            Utils::deleteMessage(self::$bot->tictactoe[$gameid]['last_msg']);
            Utils::send($msg, self::doMove($gameid, $player, $move))->then(function ($result) use ($gameid, $msg) {
                self::$bot->tictactoe[$gameid]['last_msg'] = $result;
                Utils::deleteMessage($msg);
            });
            return;
        } else {
            Utils::send($msg, "invalid move. enter a number 1-9 or quit with `;tic stop`\n" . self::printBoard($gameid));
            return;
        }
    }


    public static function doMove($gameid, $player, $move)
    {
        if (self::placePieceAt($gameid, $move, $player)) {

            if (self::checkWin($gameid)) {
                self::$bot->tictactoe[$gameid]['active'] = false;
                $response = "<@" . self::$bot->tictactoe[$gameid]['players'][self::$bot->tictactoe[$gameid]['turn']] . "> (" . self::$bot->tictactoe[$gameid]['turn'] . ") won!";
            } elseif (isset(self::$bot->tictactoe[$gameid]['tied']) && self::$bot->tictactoe[$gameid]['tied']) {
                self::$bot->tictactoe[$gameid]['active'] = false;
                $response = "it's a tie... game over";
            } else {
                self::$bot->tictactoe[$gameid]['turn'] = self::$bot->tictactoe[$gameid]['turn'] == ":x:" ? ":o:" : ":x:";
                $response = "<@" . self::$bot->tictactoe[$gameid]['players'][self::$bot->tictactoe[$gameid]['turn']] . ">, it's your turn! (you're " . self::$bot->tictactoe[$gameid]['turn'] . "'s)";
            }

        } else {
            $response = "position $move occupied! try again.";
        }
        return self::printBoard($gameid) . "\n$response";
    }


    public static function stopGame($msg, $args)
    {
        Utils::deleteMessage($msg);
        $gameid = $msg->channel->id;
        self::$bot->tictactoe[$gameid] = [
            'active' => false,
        ];
        Utils::send($msg, "game stopped")->then(function ($result) {
            self::$bot->loop->addTimer(5, function ($timer) use ($result) {
                Utils::deleteMessage($result);
            });
        });
    }



    // internal functions
    private static function checkWin($gameid)
    {
        if ((self::getPieceAt($gameid, 1) === self::getPieceAt($gameid, 4)) && (self::getPieceAt($gameid, 4) === self::getPieceAt($gameid, 7))) {
            return true;
        } else if ((self::getPieceAt($gameid, 2) === self::getPieceAt($gameid, 5)) && (self::getPieceAt($gameid, 5) === self::getPieceAt($gameid, 8))) {
            return true;
        } else if ((self::getPieceAt($gameid, 3) === self::getPieceAt($gameid, 6)) && (self::getPieceAt($gameid, 6) === self::getPieceAt($gameid, 9))) {
            return true;
        } else if ((self::getPieceAt($gameid, 1) === self::getPieceAt($gameid, 2)) && (self::getPieceAt($gameid, 2) === self::getPieceAt($gameid, 3))) {
            return true;
        } else if ((self::getPieceAt($gameid, 4) === self::getPieceAt($gameid, 5)) && (self::getPieceAt($gameid, 5) === self::getPieceAt($gameid, 6))) {
            return true;
        } else if ((self::getPieceAt($gameid, 7) === self::getPieceAt($gameid, 8)) && (self::getPieceAt($gameid, 8) === self::getPieceAt($gameid, 9))) {
            return true;
        } else if ((self::getPieceAt($gameid, 1) === self::getPieceAt($gameid, 5)) && (self::getPieceAt($gameid, 5) === self::getPieceAt($gameid, 9))) {
            return true;
        } else if ((self::getPieceAt($gameid, 3) === self::getPieceAt($gameid, 5)) && (self::getPieceAt($gameid, 5) === self::getPieceAt($gameid, 7))) {
            return true;
        } else {
            for ($i = 1; $i <= 9; $i++) {
                if (in_array(self::getPieceAt($gameid, $i), [':o:', ':x:'])) {
                    $tmp = true;
                } else {
                    $tmp = false;
                    break;
                }
            }
            if ($tmp) {
                self::$bot->tictactoe[$gameid]['active'] = false;
                self::$bot->tictactoe[$gameid]['tied'] = true;
            }
            return false;
        }
    }


    private static function printBoard($gameid)
    {
        $response = "";
        foreach (self::$bot->tictactoe[$gameid]['board'] as $row) {
            foreach ($row as $col) {
                $response .= $col;
            }
            $response .= "\n";
        }
        return $response;
    }


    private static function getPieceAt($gameid, $i)
    {
        return self::$bot->tictactoe[$gameid]['board'][intval(($i - 1) / 3)][($i - 1) % 3];
    }

    private static function placePieceAt($gameid, $i, $piece)
    {
        if (self::getPieceAt($gameid, $i) == ":x:" || self::getPieceAt($gameid, $i) == ":o:") {
            return false;
        } else {
            self::$bot->tictactoe[$gameid]['board'][intval(($i - 1) / 3)][($i - 1) % 3] = $piece;
            return true;
        }
    }


}
