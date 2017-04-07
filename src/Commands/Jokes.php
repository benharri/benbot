<?php
namespace BenBot\Commands;

class Jokes {

    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;
        $joke = self::$bot->registerCommand('joke', [__CLASS__, 'joke'], [
            'description' => 'tells a random joke',
            'usage' => '',
            'registerHelp' => true,
        ]);

        $joke->registerSubCommand('chucknorris', [__CLASS__, 'chucknorris'], [
            'description' => 'get a fact about chuck norris',
            'aliases' => [
                'chuck',
                'norris',
            ],
        ]);

        $joke->registerSubCommand('yomama', [__CLASS__, 'yomama'], [
            'description' => 'yo mama joke',
        ]);

        echo __CLASS__ . " registered", PHP_EOL;
    }

    public static function joke($msg, $args)
    {
        return self::$bot->jokes[array_rand(self::$bot->jokes)];
    }

    public static function chucknorris($msg, $args)
    {
        $url = "http://api.icndb.com/jokes/random1";
        self::$bot->http->get($url, null, [], false)->then(function ($result) use ($msg) {
            Utils::send($msg, $result->value->joke);
        }, function ($e) use ($msg) {
            Utils::send($msg, $e->getMessage());
        });
    }

    public static function yomama($msg, $args)
    {
        return self::$bot->yomamajokes[array_rand(self::$bot->yomamajokes)];
    }

}
