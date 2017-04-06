<?php
namespace BenBot;

use Discord\Discord;
use Discord\Parts\User\Game;
use Discord\Parts\Embed\Embed;

use BenBot\Utils;
use BenBot\SerializedArray;
use BenBot\Command;
use BenBot\DebugCommands;

use Carbon\Carbon;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Dotenv\Dotenv;
use function Stringy\create as s;


class BenBot extends Discord {

    protected $dir;
    protected $defs;
    protected $imgs;
    protected $cities;
    protected $emails;
    protected $help;
    protected $jokes;
    protected $yomamajokes;
    protected $start_time;
    protected $game;
    protected $cmds    = [];
    protected $aliases = [];

    public function __construct($dir)
    {

        $dotenv = new Dotenv($dir);
        $dotenv->load();

        parent::__construct([
            'token'              => getenv('DISCORD_TOKEN'),
            'pmChannels'     => true,
            'loadAllMembers' => true,
        ]);

        $this->dir         = $dir;
        $this->help        = [];
        $this->jokes       = explode("---", file_get_contents("$dir/miscjokes.txt"));
        $this->yomamajokes = file("$dir/yomamajokes.txt");


        $this->game = $this->factory(Game::class, [
            'name' => 'type ;help for info',
        ]);

        try {
            $this->defs   = new SerializedArray("$dir/bot_data/defs.mp");
            $this->imgs   = new SerializedArray("$dir/bot_data/img_urls.mp");
            $this->cities = new SerializedArray("$dir/bot_data/cities.mp");
            $this->emails = new SerializedArray("$dir/bot_data/emails.mp");
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), PHP_EOL;
        }

        $this->on('ready', function () {
            Utils::init($this);
            $this->updatePresence($this->game);

            $this->on('message', function ($msg) {
                $str = s($msg->content);
                if (!$msg->author->bot) {
                    if ($str->startsWith(';')) {
                        $args = $str->removeLeft(';')->split(' ');
                        $cmd = array_shift($args);
                        $qu = (string) $cmd;

                        if (isset($this->defs[$qu])) {
                            Utils::send($msg, "**$qu**: " . $this->defs[$qu]);
                        }
                        if (isset($this->imgs[$qu])) {
                            Utils::sendFile($msg, "{$this->dir}/uploaded_images/{$this->imgs[$qu]}", $this->imgs[$qu], $qu);
                        }

                        if (array_key_exists($qu, $this->cmds)) {
                            $command = $this->cmds[$qu];
                        } elseif (array_key_exists($qu, $this->aliases)) {
                            $command = $this->cmds[$this->aliases[$qu]];
                        } else {
                            return;
                        }

                        foreach ($args as $key => $arg) {
                            $args[$key] = (string) $arg;
                        }
                        $result = $command->handle($msg, $args);

                        if (is_string($result)) {
                            Utils::send($msg, $result);
                        }
                    }

                } elseif (Utils::isDM($msg)) {
                    $msg->channel->broadcastTyping();
                    Utils::askCleverbot($str)->then(function ($result) use ($msg) {
                        Utils::send($msg, $result->output);
                    });
                }


                if ($msg->channel->guild->id === "233603102047993856") {
                    if ($str->contains('dib', false)) {
                        $msg->react(":dib:284335774823088129")->otherwise(function ($e) {
                            echo $e->getMessage(), PHP_EOL;
                        });
                    }
                }
            });

            $this->start_time = Carbon::now();

            $this->registerCommand('help', function ($msg, $args) {
                if (count($args) > 0) {
                    $cmdstr = implode(" ", $args);
                    $command = $this->getCommand($cmdstr, true);

                    if (is_null($command)) {
                        return "The command `;$cmdstr` does not exist";
                    }
                    $help = $command->getHelp()["text"];
                    Utils::send($msg, "```$help```");
                } else {
                    $banner = file_get_contents("{$this->dir}/banner.txt");
                    $ret = "```$banner\n- a bot made by benh. avatar by hirose.\n\n";
                    sort($this->help);
                    $ret .= implode("", $this->help);
                    $ret .= "\n;help <command> - get more information about a specific command\ncommands will still work if the first letter is capitalized.```";
                    Utils::send($msg, $ret);
                }
            }, [
                'description' => 'shows help text',
                'usage' => '<command>',
            ]);

            // $this->registerAllCommands();
            // DebugCommands::register($this);
            if (!class_exists('BenBot\DebugCommands')) {
                throw new \Exception("DebugCommands not found");
            } else {
                echo "DebugCommands found...", PHP_EOL;
            }
            DebugCommands::test();
            DebugCommands::init($this);
            Utils::ping("bot started successfully");

        });

    }



    public function registerAllCommands()
    {
        DebugCommands::register($this);
        echo "registering all commands", PHP_EOL;
    }



    public function registerCommand($command, $callable, array $options = [])
    {
        if (array_key_exists($command, $this->cmds)) {
            throw new \Exception("A command with the name $command already exists.");
        }

        list($commandInstance, $options) = $this->buildCommand($command, $callable, $options);
        $this->cmds[$command]        = $commandInstance;

        foreach ($options['aliases'] as $alias) {
            $this->registerAlias($alias, $command);
        }

        if ($options['registerHelp']) {
            $this->help[$command] = $commandInstance->getHelp()["text"];
        }

        return $commandInstance;
    }


    public function unregisterCommand($command)
    {
        if (!array_key_exists($command, $this->cmds)) {
            throw new \Exception("A command with the name $command does not exist.");
        }
        unset($this->cmds[$command]);
    }


    public function registerAlias($alias, $command)
    {
        $this->aliases[$alias] = $command;
    }


    public function unregisterAlias($alias)
    {
        if (!array_key_exist($alias, $this->aliases)) {
            throw new \Exception("A command alias with the name $alias does not exist.");
        }
        unset($this->aliases[$alias]);
    }


    public function getCommand($command, $aliases = true)
    {
        if (array_key_exists($command, $this->cmds)) {
            return $this->cmds[$command];
        }
        if (array_key_exists($command, $this->aliases) && $aliases) {
            return $this->cmds[$this->aliases[$command]];
        }
    }


    public function buildCommand($command, $callable, array $options = [])
    {
        if (!is_callable($callable)) {
            throw new \Exception("The callable has to be a callable....");
        }
        $options = $this->resolveCommandOptions($options);
        $commandInstance = new Command(
            $this, $command, $callable,
            $options['description'], $options['usage'], $options['aliases']);

        return [$commandInstance, $options];
    }


    public function resolveCommandOptions(array $options)
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setDefined([
                'description',
                'usage',
                'aliases',
                'registerHelp',
            ])
            ->setDefaults([
                'description' => 'No description provided yet',
                'usage' => '',
                'aliases' => [],
                'registerHelp' => false,
            ]);
        $options = $resolver->resolve($options);
        if (!empty($options['usage'])) {
            $options['usage'] .= ' ';
        }
        return $options;
    }


    public function __get($name)
    {
        $allowed = ['commands', 'aliases'];
        if (array_search($name, $allowed) !== false) {
            return $this->$name;
        }
        return parent::__get($name);
    }

}
