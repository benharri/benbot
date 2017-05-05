<?php

namespace BenBot\Commands;

use BenBot\EmbedColors;
use BenBot\Utils;
use Carbon\Carbon;
use Discord\Parts\Embed\Embed;

final class Debug
{
    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;

        self::$bot->registerCommand('up', [__CLASS__, 'up'], [
            'description' => 'shows uptime',
        ]);
        self::$bot->registerCommand('dbg', [__CLASS__, 'dbg'], [
            'description' => 'logs message details',
        ]);
        self::$bot->registerCommand('sys', [__CLASS__, 'sys'], [
            'description' => 'run server command and show output',
        ]);
        self::$bot->registerCommand('eval', [__CLASS__, 'botEval'], [
            'description' => 'only for owner',
        ]);
        self::$bot->registerCommand('status', [__CLASS__, 'status'], [
            'description' => 'get status of bot and server',
        ]);
        self::$bot->registerCommand('server', [__CLASS__, 'server'], [
            'description'  => 'displays information about the server',
            'registerHelp' => true,
            'aliases'      => [
                'guild',
            ],
        ]);
        self::$bot->registerCommand('roles', [__CLASS__, 'roles'], [
            'description' => 'lists all roles in the server',
            'aliases'     => [
                'role',
            ],
        ]);
        self::$bot->registerCommand('whois', [__CLASS__, 'userInfo'], [
            'description' => 'get info about a user',
            'usage'       => '[@user]',
            'aliases'     => [
                'user',
                'info',
                'userinfo',
                'whoami',
            ],
        ]);

        self::$bot->registerCommand('edittest', [__CLASS__, 'editMsgTest']);

        echo __CLASS__.' registered', PHP_EOL;
    }

    public static function up($msg, $args)
    {
        return 'benbot has been up for '.self::$bot->start_time->diffForHumans(Carbon::now(), true);
    }

    public static function dbg($msg, $args)
    {
        if (Utils::getUserIDFromMsg($msg) == '193011352275648514') {
            print_r($msg);
            Utils::send($msg, 'check logs!')->then(function ($result) {
                self::$bot->loop->addTimer(3, function ($timer) use ($result) {
                    Utils::deleteMessage($result);
                });
            });
        } else {
            return "**you're not allowed to use that command**";
        }
    }

    public static function sys($msg, $args)
    {
        if (Utils::getUserIDFromMsg($msg) == '193011352275648514') {
            return '```'.shell_exec(implode(' ', $args)).'```';
        } else {
            return "**you're not allowed to use that command**";
        }
    }

    public static function botEval($msg, $args)
    {
        if (Utils::getUserIDFromMsg($msg) == '193011352275648514') {
            $cmd = implode(' ', $args);

            return '```'.eval("return $cmd;").'```';
        } else {
            return "**you're not allowed to use that command**";
        }
    }

    public static function status($msg, $args)
    {
        $usercount = 0;
        foreach (self::$bot->guilds as $guild) {
            $usercount += $guild->member_count;
        }

        $url = 'http://test.benharr.is/phpsysinfo/xml.php?plugin=complete&json';
        self::$bot->http->get($url, null, [], false)->then(function ($result) use ($msg, $usercount) {
            print_r($result);
            $vitals = $result->Vitals->{'@attributes'};

            $embed = self::$bot->factory(Embed::class, [
                'title'     => 'Benbot status',
                'thumbnail' => ['url' => self::$bot->avatar],
                'color'     => EmbedColors::BLUE,
                'fields'    => [
                    ['name' => 'Server Uptime', 'value' => Utils::secondsConvert($vitals->Uptime),
                    ],
                    ['name' => 'Bot Uptime', 'value' => self::$bot->start_time->diffForHumans(Carbon::now(), true),
                    ],
                    ['name' => 'Server Load Average', 'value' => $vitals->LoadAvg,
                    ],
                    ['name' => 'Bot Server Count', 'value' => count(self::$bot->guilds),
                    ],
                    ['name' => 'User Count', 'value' => $usercount,
                    ],
                    ['name' => 'Bot Memory Usage', 'value' => Utils::convertMemoryUsage(),
                    ],
                ],
                'timestamp' => null,
            ]);

            Utils::send($msg, '', $embed);
        });
    }

    public static function server($msg, $args)
    {
        if (Utils::isDM($msg)) {
            return "you're not in a server, silly";
        }

        $verification_levels = [
            0 => 'None: must have discord account',
            1 => 'Low: must have verified email',
            2 => 'Medium: must have verified email for more than 5 minutes',
            3 => '(╯°□°）╯︵ ┻━┻: must have verified email, be registered on discord for more than 5 minutes, and must wait 10 minutes before speaking in any channel',
        ];
        $guild = $msg->channel->guild;
        $created_at = Carbon::createFromTimestamp(Utils::timestampFromSnowflake($guild->id));

        $embed = self::$bot->factory(Embed::class, [
            'title'     => "{$guild->name} server info",
            'thumbnail' => [
                'url' => $guild->icon,
            ],
            'color'  => EmbedColors::BLUE,
            'fields' => [
                ['name' => 'Owner', 'value' => "$guild->owner", 'inline' => true,
                ],
                ['name' => 'Region', 'value' => $guild->region, 'inline' => true,
                ],
                ['name' => 'Member Count', 'value' => $guild->member_count, 'inline' => true,
                ],
                ['name' => 'Channel Count', 'value' => count($guild->channels), 'inline' => true,
                ],
                ['name' => 'Server Created', 'value' => $created_at->format('g:i A \o\n l F j, Y').' ('.$created_at->diffForHumans().')',
                ],
                ['name' => 'Verification level', 'value' => $verification_levels[$guild->verification_level],
                ],
                ['name' => 'Server ID', 'value' => $guild->id,
                ],
                ['name' => 'benbot joined', 'value' => $guild->joined_at->format('g:i A \o\n l F j, Y').' ('.$guild->joined_at->diffForHumans().')',
                ],
            ],
            'timestamp' => null,
        ]);
        Utils::send($msg, '', $embed);
    }

    public static function roles($msg, $args)
    {
        $guild = $msg->channel->guild;
        $response = "```roles for {$guild->name}\n\n";
        foreach ($guild->roles as $role) {
            $response .= "{$role->name} ({$role->id})\n";
        }
        $response .= '```';
        Utils::send($msg, $response);
    }

    public static function userInfo($msg, $args)
    {
        if (Utils::isDM($msg)) {
            return "you're not in a server, silly";
        }

        $users = [];
        if (count($msg->mentions) === 0) {
            $users[] = $msg->author;
        } else {
            foreach ($msg->mentions as $mention) {
                $users[] = $msg->channel->guild->members->get('id', $mention->id);
            }
        }
        foreach ($users as $user) {
            $roles = [];
            foreach ($user->roles as $role) {
                $roles[] = $role->name;
            }
            print_r($user->game);
            $embed = self::$bot->factory(Embed::class, [
                'title'       => 'User info for',
                'description' => "$user",
                'thumbnail'   => ['url' => $user->user->avatar],
                'color'       => EmbedColors::BLUE,
                'fields'      => [
                    ['name' => 'Roles', 'value' => implode(', ', $roles),
                    ],
                    ['name' => 'ID', 'value' => $user->id,
                    ],
                    ['name' => 'Status', 'value' => $user->status,
                    ],
                    ['name' => 'Game', 'value' => $user->game->name ?? 'not playing anything right now',
                    ],
                    ['name' => 'Member since', 'value' => $user->joined_at->format('g:i A \o\n l F j, Y')." ({$user->joined_at->diffForHumans()})",
                    ],
                ],
                'timestamp' => null,
            ]);
            Utils::send($msg, '', $embed);
        }
    }

    public static function editMsgTest($msg, $args)
    {
        Utils::send($msg, count($msg->channel->guild->channels))->then(function ($result) {
            $result->content = 'ben is best';
            self::$bot->loop->addTimer(3, function ($timer) use ($result) {
                $result->channel->messages->save($result)->then(function ($res) {
                    self::$bot->loop->addTimer(2, function ($timer) use ($res) {
                        $res->channel->messages->delete($res);
                    });
                }, function ($e) {
                    echo $e->getMessage(), PHP_EOL;
                    echo $e->getTraceAsString(), PHP_EOL;
                });
            });
        });
    }
}
