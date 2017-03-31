<?php
namespace BenBot;

use BenBot\BenBot;


class Help extends BenBot {

    protected $help;
    protected $discord;
    protected $utils;

    public function __construct()
    {
        $help          = [];
        // $this->discord = $discord;
        // $this->utils   = $utils;
    }

    public function registerHelp($cmd_name)
    {
        $this->help[$cmd_name] = $this->getCommand($cmd_name)->getHelp(';')["text"];
    }

    public function __toString()
    {
        return implode("", $this->help);
    }

    public function getHelpText($cmd_name)
    {
        if ($cmd = $this->discord->getCommand($cmd_name, true)) {
            return $cmd->getHelp(';')["text"];
        } else {
            return "$cmd_name not found";
        }
    }

    public function helpFn()
    {
        $helpfn = function ($msg, $args) {
            if (count($args) == 1) {
                $qu = strtolower($args[0]);
                $ret = $this->getHelpText($qu);
                send($msg, "```$ret```");
            } else {
                $banner = file_get_contents(__DIR__.'/../banner.txt');
                $ret = "```$banner\n- a bot made by benh. avatar by hirose.\n\n";
                $ret .= implode("", $this->help);
                $ret .= "\n;help <command> - get more information about a specific command\ncommands will still work if the first letter is capitalized.```";
                $this->utils->send($msg, $ret);
            }
        };
        return $helpfn;
    }
}