<?php
namespace BenBot;

use Discord\DiscordCommandClient;
use Discord\Parts\User\Game;
use Discord\Parts\Embed\Embed;

use BenBot\SerializedArray;
use BenBot\Utils;
use BenBot\FontConverter;
use BenBot\Help;
use BenBot\Commands;


use Carbon\Carbon;
use Dotenv\Dotenv;
use function Stringy\create as s;

class BenBot extends DiscordCommandClient {

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
            'prefix'             => ';',
            'defaultHelpCommand' => false,
            'name'               => 'benbot',
            'discordOptions'     => [
                'pmChannels'     => true,
                'loadAllMembers' => true,
            ],
        ]);

        $this->dir = $dir;
        $this->utils = new Utils($this);
        $this->help = new Help();
        // $this->help = new Help($this, $this->utils);
        $this->jokes = explode("---", file_get_contents("$dir/miscjokes.txt"));
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
                $author = $msg->author ?? false;
                if ($author && !$msg->author->bot) {
                    if ($str->startsWith(';')) {
                        $qu = (string) $str->removeLeft(';')->split(' ', 1)[0]->toLowerCase();
                        if (isset($this->defs[$qu])) {
                            $this->utils->send($msg, "**$qu**: " . $this->defs[$qu]);
                        }
                        if (isset($this->imgs[$qu])) {
                            $this->utils->sendFile($msg, "{$this->dir}/uploaded_images/{$this->imgs[$qu]}", $this->imgs[$qu], $qu);
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
            $this->utils->pingMe("bot started successfully");
        });

        $this->registerAllCommands();
    }

    private function registerAllCommands()
    {
        $this->registerCommand('help', $this->help->helpFn(), [
            'aliases' => [
                'Help',
                'halp',
                'Halp',
            ],
        ]);
        $this->cmds["Debug"]           = new Commands\Debug();
        // $this->cmds["Definitions"]     = new Commands\Definitions($this);
        // $this->cmds["Fonts"]           = new Commands\Fonts($this);
        // $this->cmds["Images"]          = new Commands\Images($this);
        // $this->cmds["Information"]     = new Commands\Information($this);
        // $this->cmds["Jokes"]           = new Commands\Jokes($this);
        // $this->cmds["PresetResponses"] = new Commands\PresetResponses($this);

        foreach ($this->cmds as $cmd) {
            $cmd->register();
        }
    }



}
