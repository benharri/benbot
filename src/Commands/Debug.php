<?php
namespace BenBot\Commands;

use BenBot\BenBot;
use BenBot\Utils;
use Discord\Parts\Embed\Embed;

class Debug extends BenBot {

    public function __construct() {}

    public function register()
    {
        $this->registerCommand('up', [$this, 'up'], [
            'description' => 'shows uptime for the bot',
        ]);
        $this->help->registerHelp('up');

        $this->registerCommand('dbg', [$this, 'dbg']);

        $this->registerCommand('sys', [$this, 'sys']);

        $this->registerCommand('status', [$this, 'status']);
        $this->help->registerHelp('status');

        $this->registerCommand('roles', [$this, 'roles']);
        $this->help->registerHelp('roles');
    }

    public function up($msg, $args)
    {
        Utils::ssend($msg, "benbot has been up for {$this->start_time->diffForHumans(Carbon::now(), true)}.");
    }

    public function dbg($msg, $args)
    {
        $id = Utils::isDM($msg) ? $msg->author->id : $msg->author->user->id;

        if ($id == "193011352275648514") {
            print_r($msg);
            Utils::ssend($msg, "debugging. check logs.");
            print_r($msg->channel->guild);
            echo "args: ", implode(" ", $args), PHP_EOL;
        } else {
            Utils::ssend($msg, "you're not allowed to use that command");
        }
    }


    public function sys($msg, $args)
    {
        $id = Utils::isDM($msg) ? $msg->author->id : $msg->author->user->id;
        if ($id == "193011352275648514") {
            Utils::ssend($msg, "```\n" . shell_exec(implode(" ", $args)) . "\n```");
        } else {
            $msg->reply("you're not allowed to use that command");
        }
    }

    public function status($msg, $args)
    {
        $usercount = 0;
        foreach ($this->bot->guilds as $guild) {
            $usercount += $guild->member_count;
        }
        $embed = $this->bot->factory(Embed::class, [
            'title' => 'Benbot status',
            'thumbnail' => ['url' => $this->bot->avatar],
            'fields' => [
                ['name' => 'Uptime', 'value' => $this->bot->start_time->diffForHumans(Carbon::now(), true) . " (since " . $starttime->format('g:i A \o\n l F j, Y') . ")"],
                ['name' => 'Server Count', 'value' => count($this->bot->guilds)],
                ['name' => 'User Count', 'value' => $usercount],
            ],
            'timestamp' => null,
        ]);
        Utils::ssend($msg, "", $embed);
    }

    public function roles($msg, $args)
    {
        $ret = "```\nroles for {$msg->channel->guild->name}\n\n";
        foreach ($msg->channel->guild->roles as $role) {
            $ret .= "{$role->name} ({$role->id})\n";
        }
        $ret .= "```";
        Utils::ssend($msg, $ret);
    }

}
