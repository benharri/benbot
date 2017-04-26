<?php
namespace BenBot\Commands;

use BenBot\Utils;

class TicTacToe
{

    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;

        self::$bot->game[] = [
            'active' => false
        ];

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
            $gameid = $msg->channel->id;
            self::$bot->game[$gameid] = [
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
                self::$bot->game[$gameid]['players'][":o:"] = $mention->id;
            }
            Utils::send($msg, self::printBoard($gameid) . "\n<@" . self::$bot->game[$gameid]['players'][self::$bot->game[$gameid]['turn']] . ">, it's your turn!");
        } else {
            return "can't play tictactoe with more than two people!";
        }
    }


    public static function isActive($msg)
    {
        $gameid = $msg->channel->id;

        return isset(self::$bot->game[$gameid])
        && self::$bot->game[$gameid]['active']
        && $msg->author->id === self::$bot->game[$gameid]['players'][self::$bot->game[$gameid]['turn']];
    }


    public static function handleMove($msg)
    {
        $gameid = $msg->channel->id;
        $player = self::$bot->game[$gameid]['turn'];
        $text   = $msg->content;
        $move   = intval($text);

        if (strtolower($text) == "stop" || strtolower($text) == ";tic stop") {
            self::stopGame($msg, []);
            return;
        }
        if ($move > 0 && $move < 10) {
            Utils::send($msg, self::doMove($gameid, $player, $move));
            return;
        } else {
            Utils::send($msg, "invalid move. enter a number 1-9 or quit with `;tic stop`");
            return;
        }
    }


    public static function doMove($gameid, $player, $move)
    {
        if (self::placePieceAt($gameid, $move, $player)) {

            if (self::checkWin($gameid)) {
                self::$bot->game[$gameid]['active'] = false;
                return "<@" . self::$bot->game[$gameid]['players'][self::$bot->game[$gameid]['turn']] . "> won";
            } elseif (isset(self::$bot->game[$gameid]['tied']) && self::$bot->game[$gameid]['tied']) {
                self::$bot->game[$gameid]['active'] = false;
                return "it's a tie";
            } else {
                self::$bot->game[$gameid]['turn'] = self::$bot->game[$gameid]['turn'] == ":x:" ? ":o:" : ":x:";
                return self::printBoard($gameid) . "\n<@" . self::$bot->game[$gameid]['players'][self::$bot->game[$gameid]['turn']] . ">, it's your turn!";
            }

        } else {
            return "position already occupied!";
        }
    }


    public static function stopGame($msg, $args)
    {
        Utils::deleteMessage($msg);
        $gameid = $msg->channel->id;
        self::$bot->game[$gameid] = [
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
                self::$bot->game[$gameid]['active'] = false;
                self::$bot->game[$gameid]['tied'] = true;
            }
            return false;
        }
    }


    private static function printBoard($gameid)
    {
        $response = "";
        foreach (self::$bot->game[$gameid]['board'] as $row) {
            foreach ($row as $col) {
                $response .= $col;
            }
            $response .= "\n";
        }
        return $response;
    }


    private static function getPieceAt($gameid, $i)
    {
        return self::$bot->game[$gameid]['board'][intval(($i - 1) / 3)][($i - 1) % 3];
    }

    private static function placePieceAt($gameid, $i, $piece)
    {
        if (self::getPieceAt($gameid, $i) == ":x:" || self::getPieceAt($gameid, $i) == ":o:") {
            return false;
        } else {
            self::$bot->game[$gameid]['board'][intval(($i - 1) / 3)][($i - 1) % 3] = $piece;
            return true;
        }
    }


}
