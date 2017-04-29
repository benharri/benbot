<?php
namespace BenBot\Commands;

use BenBot\Utils;

final class Poll
{

    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;

        self::$bot->registerCommand('poll', [__CLASS__, 'createPoll'], [
            'description' => 'yes/no poll. lasts 30 seconds.',
            'usage' => '<question>',
            'registerHelp' => true,
        ]);

        echo __CLASS__ . " registered", PHP_EOL;
    }


    public static function createPoll($msg, $args)
    {
        if ($args[0] == "") {
            return "you didn't ask a question...";
        }
        $question = implode(" ", $args);
        $response = "{$msg->author}'s poll:\n\n*$question*";

        Utils::send($msg, $response)->then(function ($result) use ($msg) {
            Utils::deleteMessage($msg);
            $result->react("👍");
            $result->react("👎");

            $poll_results = [
                'upboats' => 0,
                'downboats' => 0,
            ];

            self::$bot->loop->addTimer(20, function ($time) use ($result) {
                Utils::editMessage($result,
                    "{$result->content}\n\n10 seconds left to vote!"
                )->then(function ($res) {
                    self::$bot->loop->addTimer(10, function ($timer) use ($res) {
                        self::$bot->http->get(
                            "channels/{$res->channel->id}/messages/{$res->id}/reactions/👍"
                        )->then(function ($upboats) use (&$poll_results, $res) {
                            $poll_results['upboats'] = count($upboats) - 1;
                            self::$bot->http->get(
                                "channels/{$res->channel->id}/messages/{$res->id}/reactions/👎"
                            )->then(function ($downboats) use (&$poll_results, $res) {
                                $poll_results['downboats'] = count($downboats) - 1;
                                switch ($poll_results['upboats'] <=> $poll_results['downboats']) {
                                    case 1: $conclusion = "Yes!"; break;
                                    case 0: $conclusion = "It's a tie..."; break;
                                    case -1: $conclusion = "Nope"; break;
                                }
                                Utils::editMessage($res,
                                    "{$res->content}\n\n**Results**\nUpboats: {$poll_results['upboats']}\nDownboats: {$poll_results['downboats']}\n**$conclusion**"
                                );
                            });
                        });
                    });
                });
            });
        });
    }

}
