<?php
namespace BenBot;

use Discord\Discord;
use Discord\Parts\User\Game;
use Discord\Parts\Embed\Embed;
use Discord\WebSockets\Event;

use BenBot\Utils;
use BenBot\PersistentArray;
use BenBot\Command;
use BenBot\Commands;
use BenBot\FontConverter;

use Carbon\Carbon;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Dotenv\Dotenv;
use function Stringy\create as s;


class BenBot extends Discord
{

    public $start_time;
    public $dir;
    public $defs;
    public $imgs;
    public $cities;
    public $emails;
    public $jokes;
    public $yomamajokes;
    public $copypastas;
    public $devbot;
    public $game;

    protected $help;
    protected $banner;
    protected $cmds    = [];
    protected $aliases = [];

    public function __construct($dir)
    {

        (new Dotenv($dir))->load();

        parent::__construct([
            'token'          => getenv('DISCORD_TOKEN'),
            'pmChannels'     => true,
            'loadAllMembers' => true,
        ]);

        $this->dir         = $dir;
        $this->help        = [];
        $this->jokes       = explode("---", file_get_contents("$dir/miscjokes.txt"));
        $this->copypastas  = explode("---", file_get_contents("$dir/copypasta.txt"));
        $this->yomamajokes = file("$dir/yomamajokes.txt");
        $this->banner      = file_get_contents("{$this->dir}/banner.txt");
        $this->game        = [];

        try {
            $this->defs   = new PersistentArray("$dir/bot_data/defs.mp");
            $this->imgs   = new PersistentArray("$dir/bot_data/img_urls.mp");
            $this->cities = new PersistentArray("$dir/bot_data/cities.mp");
            $this->emails = new PersistentArray("$dir/bot_data/emails.mp");
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), PHP_EOL;
        }

///////////////////////////////////////////////////////////////////////////////////////////////////
        $this->on('ready', function () {

            Utils::init($this);
            FontConverter::init();
            $this->updatePresence($this->factory(Game::class, [
                'name' => ';help for info',
            ]));
            $this->devbot = $this->client->user->id == 296304785018322947;

            $this->registerAllCommands();


            $msghandler = function ($msg) {
                $str = s($msg->content);
                if (!$msg->author->bot) {


                    // handle game move for players
                    if (Commands\TicTacToe::isActive($msg)) {
                        Commands\TicTacToe::handleMove($msg);
                        return;
                    }


                    if ($str->startsWith(';')) {
                        // is command!
                        $args = $str->removeLeft(';')->split(' ');
                        $cmd = (string) array_shift($args)->toLowerCase();

                        // look up definition
                        if (isset($this->defs[$cmd])) {
                            Utils::send($msg, "**$cmd**: " . $this->defs[$cmd]);
                        }
                        // look up image
                        if (isset($this->imgs[$cmd])) {
                            $msg->channel->broadcastTyping();
                            Utils::sendFile($msg,
                                "{$this->dir}/uploaded_images/{$this->imgs[$cmd]}",
                                $this->imgs[$cmd],
                                "$cmd\nby {$msg->author}"
                            )->then(function ($result) use ($msg) {
                                Utils::deleteMessage($msg);
                            });
                        }


                        // make sure stringys are strings
                        foreach ($args as $key => $arg) {
                            $args[$key] = (string) $arg;
                        }

                        // do the font stuff!
                        if (array_key_exists($cmd, FontConverter::$fonts)) {
                            Utils::send($msg, FontConverter::$cmd(implode(" ", $args)) . "\n--{$msg->author}")->then(function ($result) use ($msg) {
                                Utils::deleteMessage($msg);
                            });
                            return;
                        }


                        // look up command
                        if (array_key_exists($cmd, $this->cmds)) {
                            $command = $this->cmds[$cmd];
                        } elseif (array_key_exists($cmd, $this->aliases)) {
                            $command = $this->cmds[$this->aliases[$cmd]];
                        } else {
                            return;
                        }

                        // do the command
                        $result = $command->handle($msg, $args);

                        // respond if the command handler returned a string
                        if (is_string($result)) {
                            Utils::send($msg, $result);
                        }

                    } elseif (Utils::isDM($msg)) {
                        return call_user_func_array(["BenBot\Commands\CleverBot", "chat"], [$msg, explode(' ', $msg->content)]);
                    }

                }


                if (!Utils::isDM($msg) && $msg->channel->guild->id === "233603102047993856") {
                    if ($str->contains('dib', false)) {
                        $msg->react(":dib:284335774823088129")->otherwise(function ($e) {
                            echo $e->getMessage(), PHP_EOL;
                        });
                    }
                }

            }; // --onmsg

            $this->on('message', $msghandler);
            $this->on(Event::MESSAGE_UPDATE, $msghandler);

            $this->start_time = Carbon::now();

            // register help function
            $this->registerCommand('help', function ($msg, $args) {
                if (count($args) > 0 && $args[0] != "") {
                    $cmdstr = implode(" ", $args);
                    $command = $this->getCommand($cmdstr, true);

                    if (is_null($command)) {
                        return "The command `;$cmdstr` does not exist";
                    }
                    $help = $command->getHelp()["text"];
                    return "```$help```";
                } else {
                    $response = "```{$this->banner}\n- a bot made by benh. avatar by hirose.\n\n";
                    sort($this->help);
                    $response .= implode("", $this->help);
                    $response .= "\n-------------------------------------------------------------\n;help [command] - get more information about a specific command\ncommands are case-insensitive.\n\n[] denotes an optional argument.\n<> denotes a required argument.\n|  denotes available options.```";
                    return $response;
                }
            }, [
                'description' => 'shows help text',
                'usage' => '<command>',
            ]);



            Utils::ping("bot started successfully");
            echo PHP_EOL, "BOT STARTED SUCCESSFULLY", PHP_EOL, PHP_EOL;

        });

    }



    ///////////////////////////////////////////////////////////////////////////////////////////////////
    public function registerAllCommands()
    {
        Commands\AsciiArt::register($this);
        Commands\Cities::register($this);
        Commands\CleverBot::register($this);
        Commands\Debug::register($this);
        Commands\Definitions::register($this);
        Commands\Emails::register($this);
        Commands\Fonts::register($this);
        Commands\Fun::register($this);
        Commands\Images::register($this);
        Commands\Jokes::register($this);
        Commands\Misc::register($this);
        Commands\Music::register($this);
        Commands\Poll::register($this);
        Commands\TicTacToe::register($this);
        Commands\Time::register($this);
        Commands\Weather::register($this);
    }


    ///////////////////////////////////////////////////////////////////////////////////////////////////
    public function registerCommand($command, $callable, array $options = [])
    {
        if (array_key_exists($command, $this->cmds)) {
            throw new \Exception("A command with the name $command already exists.");
        }

        [$commandInstance, $options] = $this->buildCommand($command, $callable, $options);
        $this->cmds[$command] = $commandInstance;

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
        $allowed = ['cmds', 'aliases'];
        if (array_search($name, $allowed) !== false) {
            return $this->$name;
        }
        return parent::__get($name);
    }

}
