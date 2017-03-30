<?php
namespace BenBot\Commands;

use BenBot\BenBot;
use BenBot\Utils;
use Discord\Parts\Embed\Embed;

class Debug extends Benbot {
    protected $bot;

    public function __construct(&$that)
    {
        $this->bot = $that;
    }

    public function register()
    {
        $this->bot->registerCommand('up', [$this, 'up'], [
            'description' => 'shows uptime for the bot',
        ]);
        $this->bot->help->registerHelp('up');

        $this->bot->registerCommand('dbg', [$this, 'dbg']);

        $this->bot->registerCommand('sys', [$this, 'sys']);

        $this->bot->registerCommand('status', [$this, 'status']);
        $this->bot->help->registerHelp('status');

        $this->bot->registerCommand('roles', [$this, 'roles']);
        $this->bot->help->registerHelp('roles');
    }

    public function up($msg, $args)
    {
        $this->bot->utils->send($msg, "benbot has been up for {$bot->start_time->diffForHumans(Carbon::now(), true)}.");
    }

    public function dbg($msg, $args)
    {
        $id = Utils::isDM($msg) ? $msg->author->id : $msg->author->user->id;

        if ($id == "193011352275648514") {
            print_r($msg);
            $this->bot->utils->send($msg, "debugging. check logs.");
            print_r($msg->channel->guild);
            echo "args: ", implode(" ", $args), PHP_EOL;
        } else {
            $this->bot->utils->send($msg, "you're not allowed to use that command");
        }
    }


    public function sys($msg, $args)
    {
        $id = Utils::isDM($msg) ? $msg->author->id : $msg->author->user->id;
        if ($id == "193011352275648514") {
            $this->bot->utils->send($msg, "```\n" . shell_exec(implode(" ", $args)) . "\n```");
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
        $this->bot->utils->send($msg, "", $embed);
    }

    public function roles($msg, $args)
    {
        $ret = "```\nroles for {$msg->channel->guild->name}\n\n";
        foreach ($msg->channel->guild->roles as $role) {
            $ret .= "{$role->name} ({$role->id})\n";
        }
        $ret .= "```";
        $this->bot->utils->send($msg, $ret);
    }

}
