<?php
namespace BenBot\Commands;
error_reporting(-1);

use BenBot\Utils;

class Images {

    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;

        $img = self::$bot->registerCommand('img', [__CLASS__, 'img'], [
            'description' => 'save and retrieve images',
            'usage' => '<name of image to show|list|save|rm>',
            'registerHelp' => true,
        ]);
            $img->registerSubCommand('save', [__CLASS__, 'save'], [
                'description' => 'saves attached image',
                'usage' => '<name>',
            ]);
            $img->registerSubCommand('list', [__CLASS__, 'listImgs'], [
                'description' => 'lists all saved images',
            ]);
            $img->registerSubCommand('rm', [__CLASS__, 'delImg'], [
                'description' => 'deletes a saved image',
                'usage' => '<image name>',
                'aliases' => [
                    'del',
                    'remove',
                    'delete',
                ],
            ]);

        echo __CLASS__ . " registered", PHP_EOL;
    }



    public static function img($msg, $args)
    {
        if (isset($args[0])) {
            $imgname = strtolower($args[0]);
            if (isset(self::$bot->imgs[$imgname])) {
                Utils::sendFile(
                    $msg,
                    self::$bot->dir . "/uploaded_images/" . self::$bot->imgs[$imgname],
                    self::$bot->imgs[$imgname],
                    "$imgname\nby {$msg->author}"
                )->then(function ($result) use ($msg) {
                    Utils::deleteMessage($msg);
                });
            } else {
                return "$imgname does not exist. you can save it by attaching the image with `;img save $imgname`";
            }
        } else {
            return "type the name of the image you want. type `;img list` to see the name of all available images.";
        }
    }

    public static function save($msg, $args)
    {
        if (isset($args[0])) {
            $imgname = strtolower($args[0]);
            if (isset(self::$bot->imgs[$imgname])) {
                return "img with this name already exists. change the name or remove the current img with `;img rm $imgname` and then re-save it.";
            } else {
                if (count($msg->attachments) < 1) {
                    return "please attach an image";
                } else {
                    $ext = pathinfo($msg->attachments[0]->url, PATHINFO_EXTENSION);
                    self::$bot->imgs[$imgname] = "$imgname.$ext";
                    file_put_contents(self::$bot->dir . "/uploaded_images/$imgname.$ext", file_get_contents($msg->attachments[0]->url));
                    return "image save as $imgname";
                }
            }
        }
    }


    public static function listImgs($msg, $args)
    {
        $imgs = self::$bot->imgs->array_keys();
        sort($imgs);
        return "avilable images:\n\n" . implode(", ", $imgs);
    }


    public static function delImg($msg, $args)
    {
        if (isset($args[0])) {
            $imgname = strtolower($args[0]);
            if (isset(self::$bot->imgs[$imgname])) {
                $img = self::$bot->imgs[$imgname];
                unset(self::$bot->imgs[$imgname]);
                unlink(self::$bot->dir . "/uploaded_images/$img");
                return "$imgname deleted";
            } else {
                return "that wasn't an image...";
            }
        } {
            return "you have to tell me which image to delete";
        }
    }

}
