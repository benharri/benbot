<?php
namespace BenBot\Commands;

use BenBot\Utils;

class Poll {

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
        $question = implode(" ", $args);
        $response = "{$msg->author}'s poll:\n**$question**";
        echo $response, PHP_EOL;

        Utils::send($msg, $response)->then(function ($result) use ($msg) {
            print_r($result);
            Utils::deleteMessage($msg);
            $result->react("👍");
            $result->react("👎");

            self::$bot->loop->addTimer(30, function ($timer) use ($result) {
                Utils::deleteMessage($result);

            });
        });
    }

}
