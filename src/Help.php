<?php
namespace BenBot;


class Help {

    protected $help;
    protected $discord;
    protected $utils;

    public function __construct($discord, $utils)
    {
        $help          = [];
        $this->discord = $discord;
        $this->utils   = $utils;
    }

    public function registerHelp($cmd_name)
    {
        $this->help[$cmd_name] = $this->discord->getCommand($cmd_name)->getHelp(';')["text"];
    }

    public function __toString()
    {
        return implode("", $this->help);
    }

    public function getHelpText($cmd_name)
    {
        if ($cmd = $this->discord->getCommand(strtolower($cmd_name), true)) {
            return $cmd->getHelp(';')["text"];
        } else {
            return "$cmd_name not found";
        }
    }

    public function helpFn()
    {
        $helpfn = function ($msg, $args) {
            if (count($args) == 1) {
                $this->utils->send($msg, "```" . $this->getHelpText($args[0]) . "```");
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