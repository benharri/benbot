<?php
namespace BenBot;
error_reporting(-1);

use BenBot\BenBot;
use Discord\Parts\Channel\Message;

class Command
{
    protected $command;
    protected $description;
    protected $usage;
    protected $subCommands       = [];
    protected $subCommandAliases = [];

    public function __construct(
        BenBot $client,
        $command,
        callable $callable,
        $description,
        $usage
    ) {
        $this->client      = $client;
        $this->command     = $command;
        $this->callable    = $callable;
        $this->description = $description;
        $this->usage       = $usage;
    }


    public function handle(Message $message, array $args)
    {
        $subCommand = strtolower(array_shift($args));

        if (array_key_exists($subCommand, $this->subCommands)) {
            return $this->subCommands[$subCommand]->handle($message, $args);
        } elseif (array_key_exists($subCommand, $this->subCommandAliases)) {
            return $this->subCommands[$this->subCommandAliases[$subCommand]]->handle($message, $args);
        }

        if (!is_null($subCommand)) {
            array_unshift($args, $subCommand);
        }

        return call_user_func_array($this->callable, [$message, $args]);
    }


    public function registerSubCommand($command, $callable, array $options = [])
    {
        if (array_key_exists($command, $this->subCommands)) {
            throw new \Exception("A subcommand with the name $command already exists.");
        }

        list($commandInstance, $options) = $this->client->buildCommand($command, $callable, $options);
        $this->subCommands[$command] = $commandInstance;

        foreach ($options['aliases'] as $alias) {
            $this->registerSubCommandAlias($alias, $command);
        }
        return $commandInstance;
    }


    public function unregisterSubCommand($command)
    {
        if (!array_key_exists($command, $this->subCommands)) {
            throw new \Exception("A subcommand with the name $command does not exist.");
        }
        unset($this->subCommands[$command]);
    }


    public function registerSubCommandAlias($alias, $command)
    {
        $this->subCommandAliases[$alias] = $command;
    }


    public function unregisterSubCommandAlias($alias)
    {
        if (!array_key_exists($alias, $this->subCommandAliases)) {
            throw new \Exception("A subcommand with the name $alias does not exist");
        }
        unset($this->subCommandAliases[$alias]);
    }


    public function getHelp()
    {
        $helpString = ";{$this->command} {$this->usage}- {$this->description}\n";

        foreach ($this->subCommands as $command) {
            $help = $command->getHelp($prefix.$this->command.' ');
            $helpString .= "    {$help['text']}\n";
        }

        return [
            'text'              => $helpString,
            'subCommandAliases' => $this->subCommandAliases,
        ];
    }


    public function __get($variable)
    {
        $allowed = ['command', 'description', 'usage'];
        if (array_search($variable, $allowed) !== false) {
            return $this->{$variable};
        }
    }

}
