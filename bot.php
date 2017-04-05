<?php

///////////////////////////////////////////////////////////
// config
///////////////////////////////////////////////////////////
error_reporting(E_ALL);

include __DIR__.'/vendor/autoload.php';

use Discord\DiscordCommandClient;
use Discord\Parts\User\Game;
use Discord\Parts\Embed\Embed;
use BenBot\SerializedArray;
use BenBot\Utils;
use BenBot\FontConverter;
use BenBot\Help;
use Carbon\Carbon;
use function Stringy\create as s;


$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

include __DIR__.'/kaomoji.php';

$yomamajokes = file("yomamajokes.txt");
$jokes = explode("---", file_get_contents(__DIR__.'/miscjokes.txt'));

$starttime = Carbon::now();

try {
    $defs   = new SerializedArray(__DIR__.'/bot_data/defs.mp');
    $imgs   = new SerializedArray(__DIR__.'/bot_data/img_urls.mp');
    $cities = new SerializedArray(__DIR__.'/bot_data/cities.mp');
    $emails = new SerializedArray(__DIR__.'/bot_data/emails.mp');
} catch (Exception $e) {
    echo 'Caught exception: ', $e->getMessage(), PHP_EOL;
}



$discord = new DiscordCommandClient([
    'token'              => getenv('DISCORD_TOKEN'),
    'prefix'             => ';',
    'defaultHelpCommand' => false,
    'name'               => 'benbot',
    'discordOptions'     => [
        'pmChannels'     => true,
        'loadAllMembers' => true,
    ],
]);

$utils = new Utils($discord);
$help  = new Help($discord, $utils);

$game = $discord->factory(Game::class, [
    'name' => ';help for more info',
]);


$discord->on('ready', function ($discord) use ($game, $defs, $imgs, $starttime, $utils) {
    $discord->updatePresence($game);

    $discord->on('message', function ($msg) use ($defs, $imgs, $utils) {
        // for stuff that isn't a command
        $str = s($msg->content);

        if (!$msg->author->bot) {

            if ($str->startsWith(';')) {
                // get first word to see if we have something saved
                $qu = (string) $str->removeLeft(';')->split(' ', 1)[0]->toLowerCase();

                if (isset($defs[$qu])) {
                    $utils->send($msg, "**$qu**: " . $defs[$qu]);
                }

                if (isset($imgs[$qu])) {
                    $msg->channel->broadcastTyping();
                    $utils->sendFile($msg, __DIR__."/uploaded_images/{$imgs[$qu]}", $imgs[$qu], $qu);
                }

            } elseif (Utils::isDM($msg)) {
                $msg->channel->broadcastTyping();
                $utils->askCleverbot($str)->then(function ($result) use ($msg, $utils) {
                    $utils->send($msg, $result->output);
                });
            }

            if ($msg->channel->guild->id === "233603102047993856") {
                // arf specific
                // dib
                if ($str->contains('dib', false)) {
                    $msg->react(":dib:284335774823088129")->otherwise(function ($e) {
                        echo $e->getMessage(), PHP_EOL;
                    });
                }
            }


        }
    });


    $starttime = Carbon::now();

    $utils->pingMe("bot started successfully");
});




///////////////////////////////////////////////////////////
// commands
///////////////////////////////////////////////////////////


///////////////////////////////////////////////////////////
$discord->registerCommand('hi', [
    'hey',
    'hello',
    'wussup',
    'soup',
    'how are you today?',
], [
    'description' => 'greeting',
    'aliases' => [
        'Hi',
        'Hello',
        'hello',
    ],
]);







$savecity = function ($msg, $args) use ($cities, $discord, $utils) {
    $api_key = getenv('OWM_API_KEY');
    $query = implode("%20", $args);
    $url = "http://api.openweathermap.org/data/2.5/weather?q={$query}&APPID=$api_key&units=metric";

    $discord->http->get($url)->then(function ($json) use ($cities, $msg, $discord, $utils) {
        $lat = $json->coord->lat;
        $lng = $json->coord->lon;
        $geonamesurl = "http://api.geonames.org/timezoneJSON?username=benharri&lat=$lat&lng=$lng";
        $discord->http->get($geonamesurl)->then(function ($geojson) use ($cities, $msg, $json, $utils) {

            if (count($msg->mentions) > 0) {
                $ret = "the preferred city for ";
                foreach ($msg->mentions as $mention) {
                    $cities[$mention->id] =  [
                        'id'       => $json->id,
                        'lat'      => $json->coord->lat,
                        'lon'      => $json->coord->lon,
                        'city'     => $json->name,
                        'timezone' => $geojson->timezoneId,
                    ];
                    $mentions[] = "<@{$mention->id}>";
                }
                $ret .= implode(", ", $mentions);
                $ret .= " has been set to {$json->name}";
                $utils->send($msg, $ret);
            } else {
                $cities[$msg->author->id] = [
                    'id'       => $json->id,
                    'lat'      => $json->coord->lat,
                    'lon'      => $json->coord->lon,
                    'city'     => $json->name,
                    'timezone' => $geojson->timezoneId,
                ];
                $msg->reply("your preferred city has been set to {$json->name}");
            }

        });

    });
};






///////////////////////////////////////////////////////////
$time = $discord->registerCommand('time', function ($msg, $args) use ($cities, $discord, $utils) {
    $id = Utils::isDM($msg) ? $msg->author->id : $msg->author->user->id;

    if (count($args) == 0) {
        // lookup the person's time or tell them to save their time
        if (isset($cities[$id])) {
            $ci = $cities[$id];
            $utils->send($msg, "It's " . Carbon::now($ci["timezone"])->format('g:i A \o\n l F j, Y') . " in {$ci["city"]}.");
        } else {
            $utils->send($msg, "It's " . Carbon::now()->format('g:i A \o\n l F j, Y') . " Eastern Time (USA).\nyou can set a preferred city with `;time save city` or `;weather save.`");
        }
    } else {
        if (count($msg->mentions) > 0) {
            // if users are mentioned
            foreach ($msg->mentions as $mention) {
                if (isset($cities[$mention->id])) {
                    $ci = $cities[$mention->id];
                    $utils->send($msg, "It's " . Carbon::now($ci["timezone"])->format('g:i A \o\n l F j, Y') . " in {$ci["city"]}.");
                } else {
                    $utils->send($msg, "No city found for <@{$mention->id}>.\nset a preferred city with `;time save city` or `;weather save city`");
                }
            }

        } else {
            // look up the time for whatever they requested
            $msg->channel->broadcastTyping();

            $api_key = getenv('OWM_API_KEY');
            $query = implode("%20", $args);
            $url = "http://api.openweathermap.org/data/2.5/weather?q={$query}&APPID=$api_key&units=metric";

            $discord->http->get($url)->then(function ($jsoncoords) use ($discord, $msg, $utils) {
                $coord = $jsoncoords->coord;
                $url = "http://api.geonames.org/timezoneJSON?username=benharri";
                $newurl = "$url&lat={$coord->lat}&lng={$coord->lon}";
                $discord->http->get($newurl)->then(function ($json) use ($msg, $jsoncoords, $utils) {
                    $utils->send($msg, "It's " . Carbon::now($json->timezoneId)->format('g:i A \o\n l F j, Y') . " in {$jsoncoords->name}.");
                });
            });
        }
    }
}, [
    'description' => 'looks up current time for yourself or another user',
    'usage' => '<@user>',
    'aliases' => [
        'Time',
    ],
]);
$help->registerHelp('time');


    $time->registerSubCommand('save', $savecity, [
        'description' => 'saves a preferred city to use with ;weather and ;time',
        'usage' => '<city>',
    ]);


///////////////////////////////////////////////////////////
$weather = $discord->registerCommand('weather', function ($msg, $args) use ($cities, $discord, $utils) {
    $id = Utils::isDM($msg) ? $msg->author->id : $msg->author->user->id;
    $api_key = getenv('OWM_API_KEY');
    $url = "http://api.openweathermap.org/data/2.5/weather?APPID=$api_key&units=metric&";
    if (count($args) == 0) {
        // look up for your saved city
        if (isset($cities[$id])) {
            $ci = $cities[$id];
            $url .= "id=" . $ci["id"];
            $discord->http->get($url)->then(function ($result) use ($msg, $ci, $utils) {
                $utils->send($msg, "", $utils->formatWeatherJson($result, $ci["timezone"]));
            });
        } else {
            $msg->reply("you can set your preferred city with `;weather save <city>`");
            return;
        }
    } else {
        if (count($msg->mentions) > 0) {
            // look up for another person
            foreach ($msg->mentions as $mention) {
                if (isset($cities[$mention->id])) {
                    $ci = $cities[$mention->id];
                    $url .= "id=" . $ci["id"];
                    $discord->http->get($url)->then(function ($result) use ($msg, $ci, $utils) {
                        $utils->send($msg, "", $utils->formatWeatherJson($result, $ci["timezone"]));
                    });
                } else {
                    // mentioned user not found
                    $utils->send($msg, "no preferred city found for <@{$mention->id}>.\nset a preferred city with `;weather save city <@{$mention->id}>`.");
                }
            }
        } else {
            // look up any city
            $query = implode("%20", $args);
            $url .= "q=$query";
            $discord->http->get($url)->then(function ($result) use($msg, $utils) {
                $utils->send($msg, "", $utils->formatWeatherJson($result));
            });
        }
    }
}, [
    'description' => 'looks up weather for a city, other user, or yourself',
    'usage' => '<city|@user>',
    'aliases' => [
        'Weather',
    ],
]);
$help->registerHelp('weather');


    $weather->registerSubCommand('save', $savecity, [
        'description' => 'saves your favorite city',
        'usage' => '<location>',
    ]);




///////////////////////////////////////////////////////////
$discord->registerCommand('roll', function ($msg, $args) {
    $msg->reply('you rolled a ' . rand(1, $args[0] ?? 6));
}, [
    'description' => 'rolls an n-sided die. defaults to 6.',
    'usage' => '<number of sides>',
    'aliases' => [
        'Roll',
    ],
]);
$help->registerHelp('roll');


///////////////////////////////////////////////////////////
$discord->registerCommand('text_benh', function ($msg, $args) use ($utils) {
    if (count($args) === 0) {
        $utils->send($msg, 'can\'t send a blank message');
        return;
    }

    $srvr = $msg->channel->guild->name;
    $user = Utils::isDM($msg) ? $msg->author->username : $msg->author->user->username;
    $from = "From: {$srvr} Discord <{$srvr}@bot.benharris.ch>";
    $msg_body = $user . ":\n\n" . implode(" ", $args);

    if (mail(getenv('PHONE_NUMBER') . "@vtext.com", "", $msg_body, $from)) {
        $utils->send($msg, "message sent to benh");
    }
}, [
    'description' => 'text a message to benh',
    'usage' => '<message>',
    'aliases' => [
        'Text_benh',
        'textben',
        'Textben',
    ],
]);
$help->registerHelp('text_benh');



$email = $discord->registerCommand('email', function ($msg, $args) use ($utils, $emails) {
    $id = Utils::isDM($msg) ? $msg->author->id : $msg->author->user->id;
    $to = "";
    $from = "From: {$msg->channel->guild->name} {$msg->author->username} benbot <{$msg->author->username}@{$msg->channel->guild->name}.benbot>";
    $body = implode(" ", $args);

    if (count($msg->mentions) == 0) {
        if (isset($emails[$id])) {
            $to = $emails[$id];
        } else {
            $utils->send($msg, "you can save an email with `;email save <email>`");
        }
    } else {
        foreach ($msg->mentions as $mention) {
            if (isset($emails[$mention->id])) {
                $to .= $emails[$mention->id] . ";";
            } else {
                $utils->send($msg, "you can save an email with `;email save <email> <@user>` or have them do it");
            }
        }
    }
    if (mail($to, 'BenBot Message', $body, $from)) {
        $utils->send($msg, "message sent successfully");
    }
}, [
    'description' => 'sends an email',
    'usage' => '<recipient> <msg>',
    'aliases' => [
        'Email',
        'tell',
        'Tell',
    ],
]);

    $email->registerSubCommand('save', function ($msg, $args) use ($utils, $emails) {
        $id = Utils::isDM($msg) ? $msg->author->id : $msg->author->user->id;
        if (count($msg->mentions) == 0) {
            $emails[$id] = $args[0];
        } elseif (count($msg->mentions) == 1) {
            $emails[$msg->mentions[0]->id] = $args[0];
        }
        $utils->send($msg, $args[0] . " saved.");
    }, [
        'description' => 'saves your email',
        'usage' => '<email>',
    ]);



///////////////////////////////////////////////////////////
$discord->registerCommand('avatar', function ($msg, $args) use ($utils) {
    if (count($msg->mentions) === 0) {
        if (Utils::isDM($msg)) {
            $utils->send($msg, $msg->author->avatar);
        } else {
            $utils->send($msg, $msg->author->user->avatar);
        }
        return;
    }
    foreach ($msg->mentions as $av)
        $utils->send($msg, $av->avatar);
}, [
    'description' => 'gets the avatar for a user',
    'usage' => '<@user>',
    'aliases' => [
        'Avatar',
    ],
]);
$help->registerHelp('avatar');





///////////////////////////////////////////////////////////
$discord->registerCommand('say', function ($msg, $args) use ($utils) {
    $a = s(implode(" ", $args));
    if ($a->contains('@everyone') || $a->contains('@here')) {
        $msg->reply("sry, can't do that! :P");
        return;
    }
    $utils->send($msg, "$a\n\n**love**, {$msg->author}");
}, [
    'description' => 'repeats stuff back to you',
    'usage' => '<stuff to say>',
    'aliases' => [
        'Say',
    ],
]);





///////////////////////////////////////////////////////////
$discord->registerCommand('sing', function ($msg, $args) use ($utils) {
    $a = implode(" ", $args);
    if ((strpos($a, '@everyone') !== false) || (strpos($a, '@here') !== false)) {
        $msg->reply("sry, can't do that! :P");
        return;
    }
    $utils->send($msg, ":musical_note::musical_note::musical_note::musical_note::musical_note::musical_note:\n\n$a\n\n:musical_note::musical_note::musical_note::musical_note::musical_note::musical_note:, {$msg->author}");
}, [
    'description' => 'sing sing sing',
    'usage' => '<sing>',
    'aliases' => [
        'Sing',
    ],
]);



///////////////////////////////////////////////////////////
// DEFINITIONS STUFF
///////////////////////////////////////////////////////////
$discord->registerCommand('set', function ($msg, $args) use ($defs, $utils) {
    $def = strtolower(array_shift($args));
    if ($def == "san" && $msg->author->id != 190933157430689792) {
        $msg->reply("you're not san");
        return;
    }
    $defs[$def] = implode(" ", $args);
    $utils->send($msg, $def . " set to: " . implode(" ", $args));
}, [
    'description' => 'sets this to that',
    'usage' => '<this> <that>',
    'aliases' => [
        'Set',
    ],
]);
$help->registerHelp('set');
///////////////////////////////////////////////////////////
$discord->registerCommand('get', function ($msg, $args) use ($defs, $utils) {
    if (isset($args[0])) {
        $qu = strtolower($args[0]);
        if (isset($defs[$qu])) {
            $utils->send($msg, "**" . $args[0] . "**: " . $defs[$qu]);
        } else {
            $utils->send($msg, "not found! you can set this definition with `;set $qu <thing here>`");
        }
    } else {
        $utils->send($msg, "can't search for nothing");
    }
}, [
    'description' => 'gets a value from the definitions. you can also omit get (;<thing to get>)',
    'usage' => '<thing to get>',
    'aliases' => [
        'Get',
    ],
]);
$help->registerHelp('get');
///////////////////////////////////////////////////////////
$discord->registerCommand('unset', function ($msg, $args) use ($defs, $utils) {
    $qu = strtolower($args[0]);
    unset($defs[$qu]);
    $utils->send($msg, "$qu unset");
}, [
    'description' => 'removes a definition',
    'usage' => '<def to remove>',
    'aliases' => [
        'Unset',
    ],
]);
$help->registerHelp('unset');


///////////////////////////////////////////////////////////
$discord->registerCommand('listdefs', function ($msg, $args) use ($defs, $utils) {
    $ret = "benbot definitions:\n\n";
    foreach ($defs as $key => $val) {
        $ret .= "**$key**: $val\n";
    }

    if (Utils::isDM($msg)) $utils->send($msg, $ret);
    else {
        if (strlen($ret) > 2000) {
            foreach (str_split($ret, 2000) as $split) {
                $msg->author->user->sendMessage($split);
            }
        }
        $msg->reply("check DMs!");
    }
}, [
    'description' => 'lists all defs (sends dm)',
    'aliases' => [
        'Listdefs',
    ],
]);







///////////////////////////////////////////////////////////
$discord->registerCommand('8ball', function ($msg, $args) use ($fortunes, $utils) {
    $ret = "Your Question: *";
    $ret .= count($args) == 0 ? "Why didn't {$msg->author} ask a question?" : implode(" ", $args);
    $ret .= "*\n\n**" . $fortunes[array_rand($fortunes)] . "**";
    $utils->send($msg, $ret);
}, [
    'description' => 'tells your fortune',
    'usage' => '<question to ask the mighty 8ball>',
    'aliases' => [
        'ask',
        'Ask',
    ],
]);
$help->registerHelp('8ball');



///////////////////////////////////////////////////////////
$discord->registerCommand('lenny', function ($msg, $args) use ($utils) {
    $utils->send($msg, "( ͡° ͜ʖ ͡°)")->then(function ($result) use ($msg) {
        Utils::deleteMessage($msg);
    });
}, [
    'description' => 'you should know what this does',
    'aliases' => [
        'Lenny',
    ],
]);
///////////////////////////////////////////////////////////
$discord->registerCommand('lennies', function ($msg, $args) use ($lennyception, $utils) {
    $utils->send($msg, $lennyception);
}, [
    'description' => '( ͡° ͜ʖ ͡°)',
    'aliases' => [
        'Lennies',
        'lennyception',
        'Lennyception',
    ],
]);
///////////////////////////////////////////////////////////
$discord->registerCommand('shrug', function ($msg, $args, $utils) {
    $utils->send($msg, "¯\\\_(ツ)\_/¯");
}, [
    'description' => 'meh',
    'aliases' => [
        'Shrug',
        'meh',
        'Meh',
    ],
]);
///////////////////////////////////////////////////////////
$discord->registerCommand('noice', function ($msg, $args) use ($bs, $utils) {
    $utils->send($msg, $bs);
}, [
    'description' => 'ayyy',
    'aliases' => [
        'Noice',
    ],
]);


///////////////////////////////////////////////////////////
$discord->registerCommand('copypasta', function ($msg, $args) use ($utils) {
    $copypastas = explode("---", file_get_contents(__DIR__.'/copypasta.txt'));
    $utils->send($msg, $copypastas[array_rand($copypastas)]);
}, [
    'description' => 'gets random copypasta',
    'aliases' => [
        'Copypasta',
    ],
]);


///////////////////////////////////////////////////////////
$kaomoji = $discord->registerCommand('kaomoji', function ($msg, $args) use ($kaomojis, $utils) {
    $utils->send($msg, $kaomojis[array_rand($kaomojis)]);
}, [
    'description' => 'sends random kaomoji',
    'usage' => '<sad|happy|angry|confused|surprised>',
    'aliases' => [
        'Kaomoji',
    ],
]);
$help->registerHelp('kaomoji');


    $kaomoji->registerSubCommand('sad', function ($msg, $args) use($sad_kaomojis, $utils) {
        $utils->send($msg, $sad_kaomojis[array_rand($sad_kaomojis)]);
    }, ['description' => 'sad kaomoji']);
    $kaomoji->registerSubCommand('happy', function ($msg, $args) use($happy_kaomojis, $utils) {
        $utils->send($msg, $happy_kaomojis[array_rand($happy_kaomojis)]);
    }, ['description' => 'happy kaomoji']);
    $kaomoji->registerSubCommand('angry', function ($msg, $args) use($angry_kaomojis, $utils) {
        $utils->send($msg, $angry_kaomojis[array_rand($angry_kaomojis)]);
    }, ['description' => 'angry kaomoji']);
    $kaomoji->registerSubCommand('confused', function ($msg, $args) use($confused_kaomojis, $utils) {
        $utils->send($msg, $confused_kaomojis[array_rand($confused_kaomojis)]);
    }, ['description' => 'confused kaomoji']);
    $kaomoji->registerSubCommand('surprised', function ($msg, $args) use($surprised_kaomojis, $utils) {
        $utils->send($msg, $surprised_kaomojis[array_rand($surprised_kaomojis)]);
    }, ['description' => 'surprised kaomoji']);
    $kaomoji->registerSubCommand('embarrassed', function ($msg, $args) use($embarrassed_kaomojis, $utils) {
        $utils->send($msg, $embarrassed_kaomojis[array_rand($embarrassed_kaomojis)]);
    }, ['description' => 'embarrassed kaomoji']);




///////////////////////////////////////////////////////////
$joke = $discord->registerCommand('joke', function ($msg, $args) use ($jokes, $utils) {
    $utils->send($msg, $jokes[array_rand($jokes)]);
}, [
    'description' => 'tells a random joke',
    'usage' => '<chucknorris|yomama|dad>',
    'aliases' => [
        'Joke',
    ],
]);
$help->registerHelp('joke');


    $joke->registerSubCommand('chucknorris', function ($msg, $args) use ($discord, $utils) {
        $url = "http://api.icndb.com/jokes/random1";
        $result = $discord->http->get($url, null, [], false)->then(function ($result) use ($msg, $utils) {
            $utils->send($msg, $result->value->joke);
        }, function ($e) use ($msg, $utils) {
            $utils->send($msg, $e->getMessage());
        });
    }, [
        'description' => 'get a random fact about chuck norris',
        'aliases' => [
            'chuck',
        ],
    ]);

    $joke->registerSubCommand('yomama', function ($msg, $args) use ($yomamajokes, $utils) {
        $utils->send($msg, $yomamajokes[array_rand($yomamajokes)]);
    }, [
        'description' => 'yo mama jokes',
        'aliases' => [
            'mom',
        ],
    ]);

    $joke->registerSubCommand('dad', function ($msg, $args) use ($discord, $utils) {
        $url = "https://icanhazdadjoke.com";
        $discord->http->get($url, null, ['Accept' => 'application/json'], false)->then(function ($result) use ($msg, $utils) {
            $utils->send($msg, $result->joke);
        }, function ($e) use ($msg, $utils) {
            $utils->send($msg, $e->getMessage());
        });
    }, [
        'description' => 'tells a dad joke',
    ]);



// FONTS

///////////////////////////////////////////////////////////
$discord->registerCommand('block', function ($msg, $args) use ($utils) {
    $utils->send($msg, FontConverter::blockText(implode(" ", $args)));
}, [
    'description' => 'turn a message into block text',
    'usage' => '<msg>',
    'aliases' => [
        'Block',
    ],
]);
$help->registerHelp('block');



///////////////////////////////////////////////////////////
$discord->registerCommand('script', function($msg, $args) use ($utils) {
    $utils->send($msg, FontConverter::script(implode(" ", $args)));
}, [
    'description' => 'script font',
    'usage' => '<msg>',
    'aliases' => [
        'Script',
    ],
]);


///////////////////////////////////////////////////////////
$discord->registerCommand('frak', function($msg, $args) use ($utils) {
    $utils->send($msg, FontConverter::gothic(implode(" ", $args)));
}, [
    'description' => 'gothic font',
    'usage' => '<msg>',
    'aliases' => [
        'Frak',
        'fraktur',
        'Fraktur',
        'gothic',
        'Gothic',
    ],
]);

///////////////////////////////////////////////////////////
$discord->registerCommand('text', function($msg, $args) use ($utils) {
    $font = array_shift($args);
    $utils->send($msg, FontConverter::$font(implode(" ", $args)));
}, [
    'description' => 'different fonts',
    'usage' => '<font> <message>',
    'aliases' => [
        'Text',
        'font',
        'Font',
    ],
]);


///////////////////////////////////////////////////////////
$ascii = $discord->registerCommand('ascii', function ($msg, $args) use ($utils) {
    $result = shell_exec("figlet " . escapeshellarg(implode(" ", $args)));
    $result = "```$result```";
    if (strlen($result) > 2000) {
        $utils->send($msg, "oops message too large for discord");
    } else {
        $utils->send($msg, $result);
    }
}, [
    'description' => 'ascii-ifies your message',
    'usage' => '<msg>',
    'aliases' => [
        'Ascii',
        'ASCII',
    ],
]);

    $ascii->registerSubCommand('slant', function ($msg, $args) use ($utils) {
        $result = shell_exec("figlet -f smslant " . escapeshellarg(implode(" ", $args)));
        $result = "```$result```";
        if (strlen($result) > 2000) {
            $utils->send($msg, "oops message too large for discord");
        } else {
            $utils->send($msg, $result);
        }
    }, [
        'description' => 'slant ascii',
        'usage' => '<msg>',
    ]);

    $ascii->registerSubCommand('lean', function ($msg, $args) use ($utils) {
        $result = shell_exec("figlet -f lean " . escapeshellarg(implode(" ", $args)) . " | tr ' _/' ' //'");
        $result = "```$result```";
        if (strlen($result) > 2000) {
            $utils->send($msg, "oops message too large for discord");
        } else {
            $utils->send($msg, $result);
        }
    });




///////////////////////////////////////////////////////////
$img = $discord->registerCommand('img', function ($msg, $args) use ($imgs, $discord, $utils) {
    if (count($args) < 1) {
        $utils->send($msg, "type the name of an image you'd like to see. `;img list` shows all saved images.");
        return;
    }
    $qu = strtolower($args[0]);
    // look for image in uploaded_images
    if (isset($imgs[$qu])) {
        $utils->sendFile($msg, __DIR__."/uploaded_images/{$imgs[$qu]}", $imgs[$qu], $qu);
    } else {
        $utils->send($msg, "$qu is not a saved image. you can save it by attaching the image with `;img save $qu`");
    }
}, [
    'description' => 'image tools (;help img for more info)',
    'usage' => '<image to show>',
    'aliases' => [
        'Img',
    ],
]);
$help->registerHelp('img');



    $img->registerSubCommand('save', function ($msg, $args) use ($imgs, $utils) {
        $qu = strtolower($args[0]);
        if (isset($imgs[$qu])) {
            $utils->send($msg, "img with this name already exists");
            return;
        }
        if (count($msg->attachments) > 0) {
            foreach ($msg->attachments as $attachment) {
                $ext = pathinfo($attachment->url, PATHINFO_EXTENSION);
                $imgs[$qu] = "$qu.$ext";
                file_put_contents(__DIR__."/uploaded_images/$qu.$ext", file_get_contents($attachment->url));
            }

            $utils->send($msg, "image saved as $qu");
        } else {
            $utils->send($msg, "no image to save");
        }
    }, [
        'description' => 'saves attached image as name',
        'usage' => '<name>',
    ]);


    $img->registerSubCommand('list', function ($msg, $args) use ($imgs, $utils) {
        $ret = "";
        foreach ($imgs as $name => $img) {
            $ret .= "$name, ";
        }
        $ret = rtrim($ret, ", ");
        $utils->send($msg, "list of uploaded images:\n\n$ret");
    }, [
        'description' => 'saved image list',
    ]);

    $img->registerSubCommand('rm', function ($msg, $args) use ($imgs, $utils) {
        $qu = strtolower($args[0]);
        if (isset($imgs[$qu])) {
            $img = $imgs[$qu];
            unset($imgs[$qu]);
            unlink(__DIR__."/uploaded_images/$img");
            $utils->send($msg, "$qu deleted");
        } else {
            $utils->send($msg, "$qu doesn't exist. can't delete");
        }
    }, [
        'description' => 'deletes a saved image',
        'usage' => '<image name>',
        'aliases' => [
            'del',
            'delete',
            'remove',
        ],
    ]);



///////////////////////////////////////////////////////////
// look up defs or images!
$discord->registerCommand('chat', function ($msg, $args) use ($utils) {
    $msg->channel->broadcastTyping();
    $utils->askCleverbot(implode(' ', $args))->then(function ($result) use ($msg) {
        $msg->reply($result->output);
    });
}, [
    'description' => 'talk to ben (you can do this in a DM too!)',
    'usage' => '<msg>',
    'aliases' => [
        '',
        'Chat',
        'Cleverbot',
        'cleverbot',
    ],
]);
$help->registerHelp('chat');


///////////////////////////////////////////////////////////
$discord->registerCommand('dm', function ($msg, $args) use ($utils) {
    if (Utils::isDM($msg)) {
        $utils->send($msg, "you're already in a dm, silly");
    }
    if (count($msg->mentions) == 0) {
        $msg->author->user->sendMessage("hi\ntry typing `;help` or just have a conversation with me");
    } else {
        foreach ($msg->mentions as $mention) {
            $mention->sendMessage("hi\ntry typing `;help` or just have a conversation with me");
        }
    }
}, [
    'description' => 'start a DM conversation with yourself or someone else',
    'usage' => '<@user>',
    'aliases' => [
        'Dm',
    ],
]);
$help->registerHelp('dm');


///////////////////////////////////////////////////////////
$discord->registerCommand('bamboozle', function ($msg, $args) use ($utils) {
    $ret = "";
    if (count($msg->mentions) > 0)
        foreach ($msg->mentions as $key => $val)
            $ret .= "<@$key>";
    else $ret = $msg->author;
    $ret .= ", you've been heccin' bamboozled again!!!!!!!!!!!!!!!!!!!!";
    $utils->sendFile($msg, 'img/bamboozled.jpg', 'bamboozle.jpg', $ret);

}, [
    'description' => "bamboozles mentioned user (or you if you don't mention anyone!!)",
    'usage' => '<user>(optional)',
    'aliases' => [
        'Bamboozle',
    ],
]);





///////////////////////////////////////////////////////////
// debugging commands
///////////////////////////////////////////////////////////
$discord->registerCommand('dbg', function ($msg, $args) use ($defs, $imgs, $discord, $utils) {
    $id = Utils::isDM($msg) ? $msg->author->id : $msg->author->user->id;

    if ($id == "193011352275648514") {
        print_r($msg);
        $utils->send($msg, "debugging. check logs.");
        print_r($msg->channel->guild);
        echo "args: ", implode(" ", $args), PHP_EOL;
    } else {
        $utils->send($msg, "you're not allowed to use that command");
    }
}, [
    'aliases' => [
        'Dbg',
    ],
]);
///////////////////////////////////////////////////////////
$discord->registerCommand('sys', function ($msg, $args) use ($utils) {
    $id = Utils::isDM($msg) ? $msg->author->id : $msg->author->user->id;
    if ($id == "193011352275648514") {
        $utils->send($msg, "```\n" . shell_exec(implode(" ", $args)) . "\n```");
    } else {
        $utils->send($msg, "you're not allowed to use that command");
    }
}, [
    'aliases' => [
        'Sys',
    ],
]);
///////////////////////////////////////////////////////////
$discord->registerCommand('status', function ($msg, $args) use ($discord, $starttime, $utils) {
    $usercount = 0;
    foreach ($discord->guilds as $guild) {
        $usercount += $guild->member_count;
    }

    $discord->http->get('http://test.benharris.ch/phpsysinfo/xml.php?plugin=complete&json')->then(function ($result) use ($discord, $starttime, $utils) {

        // print_r($result);
        $vitals = $result->Vitals->{"@attributes"};
        print_r($vitals);

        echo s($vitals->LoadAvg)->beforeFirst(' '), PHP_EOL;

        $embed = $discord->factory(Embed::class, [
            'title' => 'Benbot status',
            'thumbnail' => ['url' => $discord->avatar],
            'fields' => [
                ['name' => 'Server Uptime'
                ,'value' => Utils::secondsConvert($vitals->Uptime)
                ],
                ['name' => '1 Minute Load Avg'
                ,'value' => s($vitals->LoadAvg)->beforeFirst(' ')
                ,'inline' => true
                ],
                ['name' => '5 Minute Load Avg'
                ,'value' => s($vitals->LoadAvg)->afterFirst(' ')
                ,'inline' => true
                ],
                ['name' => '10 Minute Load Avg'
                ,'value' => s($vitals->LoadAvg)->afterLast(' ')
                ,'inline' => true
                ],
                ['name' => 'Bot Uptime'
                ,'value' => $starttime->diffForHumans(Carbon::now(), true) . " (since " . $starttime->format('g:i A \o\n l F j, Y') . ")"
                ],
            ],
            'timestamp' => null,
        ]);
        $utils->send($msg, "", $embed);
    });

    $embed = $discord->factory(Embed::class, [
        'title' => 'Benbot status',
        'thumbnail' => ['url' => $discord->avatar],
        'fields' => [
            ['name' => 'Uptime', 'value' => $starttime->diffForHumans(Carbon::now(), true) . " (since " . $starttime->format('g:i A \o\n l F j, Y') . ")"],
            ['name' => 'Server Count', 'value' => count($discord->guilds)],
            ['name' => 'User Count', 'value' => $usercount],
        ],
        'timestamp' => null,
    ]);
    $utils->send($msg, "", $embed);
}, [
    'description' => 'bot status',
    'usage' => '',
    'aliases' => [
        'Status',
    ],
]);
///////////////////////////////////////////////////////////
$discord->registerCommand('up', function ($msg, $args) use ($starttime, $utils) {
    $utils->send($msg, "benbot has been up for {$starttime->diffForHumans(Carbon::now(), true)}.");
}, [
    'description' => 'bot uptime',
    'aliases' => [
        'Up',
    ],
]);
///////////////////////////////////////////////////////////
$discord->registerCommand('server', function ($msg, $args) use ($discord, $utils) {
    if (Utils::isDM($msg)) {
        $utils->send($msg, "you're not in a server right now");
        return;
    }

    $verify_lvls = [
        0 => "None: must have discord account",
        1 => "Low: must have verified email",
        2 => "Medium: must have verified email for more than 5 minutes",
        3 => "(╯°□°）╯︵ ┻━┻: must have verified email, be registered on discord for more than 5 minutes, and must wait 10 minutes before speaking in any channel",
    ];
    $guild = $msg->channel->guild;
    $created_at = Carbon::createFromTimestamp($utils->timestampFromSnowflake($guild->id));

    $embed = $discord->factory(Embed::class, [
        'title' => "{$guild->name} server info",
        'thumbnail' => [
            'url' => $guild->icon,
        ],
        'fields' => [
            ['name' => 'Owner'
            ,'value' => "@{$guild->owner->username}#{$guild->owner->discriminator}"
            ,'inline' => true
            ],
            ['name' => 'Region'
            ,'value' => $guild->region
            ,'inline' => true
            ],
            ['name' => 'Member Count'
            ,'value' => $guild->member_count
            ,'inline' => true
            ],
            ['name' => 'Channel Count'
            ,'value' => count($guild->channels)
            ,'inline' => true
            ],
            ['name' => 'Server Created'
            ,'value' => $created_at->format('g:i A \o\n l F j, Y') . " (" . $created_at->diffForHumans() . ")"
            ],
            ['name' => 'Verification level'
            ,'value' => $verify_lvls[$guild->verification_level]
            ],
            ['name' => 'Server ID'
            ,'value' => $guild->id
            ],
            ['name' => 'benbot joined'
            ,'value' => $guild->joined_at->format('g:i A \o\n l F j, Y') . " (" . $guild->joined_at->diffForHumans() . ")"
            ],
        ],
        'timestamp' => null,
    ]);
    $utils->send($msg, "", $embed);
}, [
    'description' => 'server info',
    'aliases' => [
        'Server',
        'guild',
        'Guild',
    ],
]);
$help->registerHelp('server');
///////////////////////////////////////////////////////////
$discord->registerCommand('roles', function ($msg, $args) use ($utils) {
    $ret = "```\nroles for {$msg->channel->guild->name}\n\n";
    foreach ($msg->channel->guild->roles as $role) {
        $ret .= "{$role->name} ({$role->id})\n";
    }
    $ret .= "```";
    $utils->send($msg, $ret);
}, [
    'description' => 'lists all roles for the server',
    'aliases' => [
        'Roles',
        'role',
        'Role',
    ],
]);
$help->registerHelp('roles');








///////////////////////////////////////////////////////////
$discord->registerCommand('help', $help->helpFn(), [
    'aliases' => [
        'Help',
        'halp',
        'Halp',
    ],
]);







$discord->run();
