<?php
namespace BenBot\Commands;

use BenBot\Utils;
use function String\create as s;

final class Definitions
{

    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;

        self::$bot->registerCommand('set', [__CLASS__, 'setDef'], [
            'description'  => 'sets this to that',
            'usage'        => '<this> <that>',
            'registerHelp' => true,
            'aliases' => [
                'define',
            ],
        ]);
        self::$bot->registerCommand('get', [__CLASS__, 'getDef'], [
            'description'  => 'retrieve a definition',
            'usage'        => '<thing to find>',
            'registerHelp' => true,
        ]);
        self::$bot->registerCommand('unset', [__CLASS__, 'unsetDef'], [
            'description'  => 'remove a definition',
            'usage'        => '<thing to remove>',
            'registerHelp' => true,
        ]);
        self::$bot->registerCommand('listdefs', [__CLASS__, 'listDefs'], [
            'description' => 'sends all definitions to DM',
        ]);


        echo __CLASS__ . " registered", PHP_EOL;
    }



    public static function setDef($msg, $args)
    {
        $def = strtolower(array_shift($args));
        if ($def == "san" && $msg->author->id != 190933157430689792) {
            return "you're not san...";
        }
        self::$bot->defs[$def] = implode(" ", $args);
        Utils::send($msg, $def . " set to: " . implode(" ", $args))->then(function ($result) use ($msg) {
            Utils::deleteMessage($msg);
            self::$bot->loop->addTimer(5, function ($timer) use ($result) {
                Utils::deleteMessage($result);
            });
        });
    }

    public static function getDef($msg, $args)
    {
        if (isset($args[0])) {
            $def = strtolower($args[0]);
            if (isset(self::$bot->defs[$def])) {
                Utils::send($msg, "**$def**: " . self::$bot->defs[$def])->then(function ($result) use ($msg) {
                    Utils::deleteMessage($msg);
                });
            } else {
                Utils::send($msg, "definition not found. you can set it with `;set $def <definition here>`");
            }
        } else {
            return "tell me what definition you want! type `;listdefs` and I'll send you all current definitions in a dm";
        }
    }

    public static function unsetDef($msg, $args)
    {
        if (isset($args[0])) {
            $def = strtolower($args[0]);
            if (isset(self::$bot->defs[$def])) {
                unset(self::$bot->defs[$def]);
            } else {
                return "doesn't exist... aborting.";
            }
            return "$def removed";
        }
    }

    public static function listDefs($msg, $args)
    {
        $response = "available definitions:\n\n";

        if (isset($args[0]) && strtolower($args[0]) == "show") {
            foreach (self::$bot->defs as $name => $def) {
                $response .= "**$name**: $def\n";
            }
        } else {
            $response .= implode(", ", self::$bot->defs->array_keys()) . "\n\ntype `;listdefs show` to show all definitions";
        }

        if (strlen($response) > 2000) {
            foreach (str_split($response, 2000) as $part) {
                $msg->author->user->sendMessage($part);
            }
        } else {
            $msg->author->user->sendMessage($response);
        }

        Utils::send($msg, "{$msg->author}, check DMs!")->then(function ($result) use ($msg) {
            Utils::deleteMessage($msg);
            self::$bot->loop->addTimer(5, function ($timer) use ($result) {
                Utils::deleteMessage($result);
            });
        });
    }

}
