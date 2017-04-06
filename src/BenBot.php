<?php
namespace BenBot;

use Discord\Discord;
use Discord\Parts\User\Game;
use Discord\Parts\Embed\Embed;

use BenBot\SerializedArray;
use BenBot\Utils;
use BenBot\FontConverter;
use BenBot\Command;

use Carbon\Carbon;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Dotenv\Dotenv;
use function Stringy\create as s;


class BenBot extends Discord {

    protected $dir;
    protected $dotenv;
    protected $defs;
    protected $imgs;
    protected $cities;
    protected $emails;
    protected $help;
    protected $jokes;
    protected $yomamajokes;
    protected $start_time;
    protected $game;
    protected $cmds;

    public function __construct($dir = __DIR__)
    {

        $this->dotenv = new Dotenv($dir);
        $this->dotenv->load();

        parent::__construct([
            'token'              => getenv('DISCORD_TOKEN'),
            'pmChannels'     => true,
            'loadAllMembers' => true,
        ]);

        $this->dir         = $dir;
        $this->utils       = new Utils();
        $this->help        = [];
        $this->jokes       = explode("---", file_get_contents("$dir/miscjokes.txt"));
        $this->yomamajokes = file("$dir/yomamajokes.txt");

        $this->game = $this->factory(Game::class, [
            'name' => 'type ;help for info',
        ]);

        try {
            $this->defs   = new SerializedArray("$dir/bot_data/defs.mp");
            $this->imgs   = new SerializedArray("$dir/bot_data/defs.mp");
            $this->cities = new SerializedArray("$dir/bot_data/cities.mp");
            $this->emails = new SerializedArray("$dir/bot_data/emails.mp");
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), PHP_EOL;
        }

        $this->on('ready', function () {
            $this->updatePresence($this->game);

            $this->on('message', function ($msg) {
                $str = s($msg->content);
                if (!$msg->author->bot) {
                    if ($str->startsWith(';')) {
                        $args = $str->removeLeft(';')->split(' ');
                        $cmd = array_shift($args);

                        if (isset($this->defs[$cmd])) {
                            $this->utils->send($msg, "**$cmd**: " . $this->defs[$cmd]);
                        }
                        if (isset($this->imgs[$cmd])) {
                            $this->utils->sendFile($msg, "{$this->dir}/uploaded_images/{$this->imgs[$cmd]}", $this->imgs[$cmd], $cmd);
                        }

                        if (array_key_exists($cmd, $this->commands)) {
                            $command = $this->commands[$cmd];
                        } elseif (array_key_exists($cmd, $this->aliases)) {
                            $command = $this->commands[$this->aliases[$cmd]];
                        } else {
                            return;
                        }

                        $result = $command->handle($msg, $args);

                        if (is_string($result)) {
                            $msg->reply($result);
                        }
                    }

                } elseif (Utils::isDM($msg)) {
                    $msg->channel->broadcastTyping();
                    $this->utils->askCleverbot($str)->then(function ($result) use ($msg) {
                        $this->utils->send($msg, $result->output);
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
            // $this->utils->pingMe("bot started successfully");

            $this->registerCommand('help', function ($msg, $args) {
                if (count($args) > 0) {
                    $cmdstr = implode(" ", $args);
                    $command = $this->getCommand($cmdstr, true);

                    if (is_null($command)) {
                        return "The command $cmdstr does not exist";
                    }

                    $help = $command->getHelp()["text"];
                    Utils::ssend($msg, "```$help```");
                } else {
                    $banner = file_get_contents(__DIR__.'/../banner.txt');
                    $ret = "```$banner\n- a bot made by benh. avatar by hirose.\n\n";
                    $ret .= implode("", $this->help);
                    $ret .= "\n;help <command> - get more information about a specific command\ncommands will still work if the first letter is capitalized.```";
                    Utils::ssend($msg, $ret);
                }
            });

        });

    }

    public function registerCommand($command, $callable, array $options = [])
    {
        if (array_key_exists($command, $this->commands)) {
            throw new \Exception("A command with the name $command already exists.");
        }

        list($commandInstance, $options) = $this->buildCommand($command, $callable, $options);
        $this->commands[$command]        = $commandInstance;

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
        if (!array_key_exists($command, $this->commands)) {
            throw new \Exception("A command with the name $command does not exist.");
        }
        unset($this->commands[$command]);
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
        if (array_key_exists($command, $this->commands)) {
            return $this->commands[$command];
        }
        if (array_key_exists($command, $this->aliases) && $aliases) {
            return $this->commands[$this->aliases[$command]];
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
