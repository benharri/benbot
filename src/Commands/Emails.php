<?php
namespace BenBot\Commands;
error_reporting(-1);

use BenBot\Utils;

class Emails {

    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;

        $emailcmd = self::$bot->registerCommand('email', [__CLASS__, 'sendMail'], [
            'description' => 'send an email',
            'usage' => '[@user] <message>',
            'aliases' => [
                'mail',
            ],
        ]);
            $emailcmd->registerSubCommand('save', [__CLASS__, 'saveEmailAddress'], [
                'description' => 'save an email address',
                'usage' => '[@user] <your_email_here@example.com>',
            ]);
            $emailcmd->registerSubCommand('show', [__CLASS__, 'showEmail'], [
                'description' => 'show a saved email address',
                'usage' => '[@user]',
            ]);
            $emailcmd->registerSubCommand('remove', [__CLASS__, 'rmEmail'], [
                'description' => 'remove your saved email',
                'aliases' => [
                    'rm',
                    'clear',
                    'stop',
                    'delete',
                    'del',
                ],
            ]);

        echo __CLASS__ . " registered", PHP_EOL;
    }



    public static function sendMail($msg, $args)
    {
        $recipients = [];
        if (count($msg->mentions) === 0) {
            $recipients[] = $msg->author->id;
        } else {
            foreach ($msg->mentions as $mention) {
                $recipients[] = $mention->id;
            }
        }

        $to = "";
        foreach ($recipients as $recipient) {
            if (isset(self::$bot->emails[$recipient])) {
                $to .= self::$bot->emails[$recipient] . ";";
            } else {
                return "no email found for <@$recipient>. you can save an email with `;email save <@user>`";
            }
        }

        $body = implode(" ", $args);
        $from = "From: {$msg->channel->guild->name} {$msg->author->username} benbot <{$msg->author->username}@{$msg->channel->guild->name}.benbot>";

        if (mail($to, 'BenBot message', $body, $from)) {
            return "message sent successfully";
        }
    }


    public static function saveEmailAddress($msg, $args)
    {
        if (count($msg->mentions) === 0) {
            $id = Utils::getUserIDFromMsg($msg);
        } elseif (count($msg->mentions) === 1) {
            foreach ($msg->mentions as $mention) {
                $id = $mention->id;
            }
            array_shift($args);
        } else {
            return "you can set the email for only one person.";
        }
        self::$bot->emails[$id] = $args[0];
        return "email for <@$id> set to {$args[0]}";
    }


    public static function showEmail($msg, $args)
    {
        if (count($msg->mentions) === 0) {
            $id = Utils::getUserIDFromMsg($msg);
            if (isset(self::$bot->emails[$id])) {
                return "your email is " . self::$bot->emails[$id];
            } else {
                return "you don't have an email set. use `;email save <your_email_here@domain.tld>`";
            }
        } else {
            $response = "";
            foreach ($msg->mentions as $mention) {
                if (isset(self::$bot->emails[$mention->id])) {
                    $response .= "{$mention}'s email is " . self::$bot->emails[$mention->id] . "\n";
                } else {
                    $response .= "no email found for {$mention}\n";
                }
            }
            return $response;
        }
    }


    public static function rmEmail($msg, $args)
    {
        if (count($msg->mentions) === 0) {
            $id = Utils::getUserIDFromMsg($msg);
            if (isset(self::$bot->emails[$id])) {
                unset(self::$bot->emails[$id]);
                return "your email has been removed";
            } else {
                return "you didn't have an email saved";
            }
        } else {
            return "you can't remove someone else's email";
        }
    }

}
