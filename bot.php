<?php

///////////////////////////////////////////////////////////
// config
///////////////////////////////////////////////////////////

include __DIR__.'/vendor/autoload.php';
use Discord\DiscordCommandClient;
use Discord\Parts\User\Game;
use Discord\Parts\Embed\Embed;
use Carbon\Carbon;


$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

include __DIR__.'/kaomoji.php';
include __DIR__.'/serializedarray.php';
include __DIR__.'/util_fns.php';

$yomamajokes = file("yomamajokes.txt");
$jokes = explode("---", file_get_contents(__DIR__.'/miscjokes.txt'));

$starttime = Carbon::now();
$defs      = new SerializedArray(__DIR__.'/bot_data/defs.mp');
$imgs      = new SerializedArray(__DIR__.'/bot_data/img_urls.mp');
$cities    = new SerializedArray(__DIR__.'/bot_data/cities.mp');
$swearjar  = new SerializedArray(__DIR__.'/bot_data/swearjar.mp');
$help      = [];


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


$game = $discord->factory(Game::class, [
    'name' => ';help for more info',
]);


$discord->on('ready', function ($discord) use ($game, $defs, $imgs, $starttime, $swearjar) {
    $discord->updatePresence($game);

    $discord->on('message', function ($msg, $args) use ($defs, $imgs, $swearjar) {
        // for stuff that isn't a command
        $text = $msg->content;
        $gen = charIn($text);
        $first_char = $gen->current();

        if (!$msg->author->bot) {


            if ($first_char == ';') {

                for ($qu = "", $gen->next(); $gen->current() != " " && $gen->valid(); $gen->next())
                    $qu .= $gen->current();
                $qu = strtolower($qu);
                if ($defs->get($qu, true))
                    send($msg, "**$qu**: " . $defs->get($qu));
                if ($imgs->get($qu, true)) {
                    $imgfile = $imgs->get($qu);
                    sendFile($msg, __DIR__."/uploaded_images/$imgfile", $imgfile, $qu);
                }

            }

            if (isDM($msg)){
                $msg->channel->broadcastTyping();
                askCleverbot(implode(" ", $args))->then(function ($result) use ($msg) {
                    send($msg, $result->output);
                });
            }


            if ($msg->channel->guild->id === "233603102047993856") {
                // arf specific
                if (strpos(strtolower($text), 'dib') !== false) {
                    $msg->react(":dib:284335774823088129")->otherwise(function ($e) {
                        echo $e->getMessage(), PHP_EOL;
                    });
                }
            } else {
                if (checkForSwears(strtolower($text))) {
                    $id = isDM($msg) ? $msg->author->id : $msg->author->user->id;

                    $swearcount = $swearjar->get($id, true) ? $swearjar->get($id)["swear_count"] + 1 : 1;

                    $swearjar->set($msg->author->id, [
                        'swear_count' => $swearcount,
                        'latest_swear' => $msg->content,
                        'timestamp' => Carbon::now(),
                    ]);

                    $msg->react("‼")->otherwise(function ($e) {
                        echo $e->getMessage(), PHP_EOL;
                    });
                }
            }

        }
    });


    $starttime = Carbon::now();

    pingMe("bot started successfully");
});




///////////////////////////////////////////////////////////
// commands
///////////////////////////////////////////////////////////


///////////////////////////////////////////////////////////
$discord->registerCommand('deltest', function ($msg, $args) use ($discord) {
    send($msg, "test")->then(function($result) use ($discord, $msg) {
        print_r($result);
        $msgs = $discord->getRepository('MessageRepository', $msg->id, '');
        print_r($msgs);
        // $msgs->delete($msg);
    });
});


///////////////////////////////////////////////////////////
$discord->registerCommand('hi', [
    'hey',
    'hello',
    'wussup',
    'soup',
], [
    'description' => 'greeting',
    'aliases' => [
        'Hi',
        'Hello',
        'hello',
    ],
]);







$savecity = function ($msg, $args) use ($cities, $discord) {
    $api_key = getenv('OWM_API_KEY');
    $query = implode("%20", $args);
    $url = "http://api.openweathermap.org/data/2.5/weather?q={$query}&APPID=$api_key&units=metric";

    $discord->http->get($url)->then(function ($json) use ($cities, $msg, $discord) {
        $lat = $json->coord->lat;
        $lng = $json->coord->lon;
        $geonamesurl = "http://api.geonames.org/timezoneJSON?username=benharri&lat=$lat&lng=$lng";
        $discord->http->get($geonamesurl)->then(function ($geojson) use ($cities, $msg, $json) {

            if (count($msg->mentions) > 0) {
                $ret = "the preferred city for ";
                foreach ($msg->mentions as $mention) {
                    $cities->set($mention->id, [
                        'id'       => $json->id,
                        'lat'      => $json->coord->lat,
                        'lon'      => $json->coord->lon,
                        'city'     => $json->name,
                        'timezone' => $geojson->timezoneId,
                    ]);
                    $mentions[] = "<@{$mention->id}>";
                }
                $ret .= implode(", ", $mentions);
                $ret .= " has been set to {$json->name}";
                send($msg, $ret);
            } else {
                $cities->set($msg->author->id, [
                    'id'       => $json->id,
                    'lat'      => $json->coord->lat,
                    'lon'      => $json->coord->lon,
                    'city'     => $json->name,
                    'timezone' => $geojson->timezoneId,
                ]);
                $msg->reply("your preferred city has been set to {$json->name}");
            }

        });

    });
};






///////////////////////////////////////////////////////////
$time = $discord->registerCommand('time', function ($msg, $args) use ($cities, $discord) {
    $id = isDM($msg) ? $msg->author->id : $msg->author->user->id;

    if (count($args) == 0) {
        // lookup the person's time or tell them to save their time
        if ($cities->get($id, true)) {
            $ci = $cities->get($id);
            send($msg, "It's " . Carbon::now($ci["timezone"])->format('g:i A \o\n l F j, Y') . " in {$ci["city"]}.");
        } else {
            send($msg, "It's " . Carbon::now()->format('g:i A \o\n l F j, Y') . " Eastern Time (USA).\nyou can set a preferred city with `;time save city` or `;weather save.`");
        }
    } else {
        if (count($msg->mentions) > 0) {
            // if users are mentioned
            foreach ($msg->mentions as $mention) {
                if ($cities->get($mention->id, true)) {
                    $ci = $cities->get($mention->id);
                    send($msg, "It's " . Carbon::now($ci["timezone"])->format('g:i A \o\n l F j, Y') . " in {$ci["city"]}.");
                } else {
                    send($msg, "No city found for <@{$mention->id}>.\nset a preferred city with `;time save city` or `;weather save city`");
                }
            }

        } else {
            // look up the time for whatever they requested
            $msg->channel->broadcastTyping();

            $api_key = getenv('OWM_API_KEY');
            $query = implode("%20", $args);
            $url = "http://api.openweathermap.org/data/2.5/weather?q={$query}&APPID=$api_key&units=metric";

            $discord->http->get($url)->then(function ($jsoncoords) use ($discord, $msg) {
                $coord = $jsoncoords->coord;
                $url = "http://api.geonames.org/timezoneJSON?username=benharri";
                $newurl = "$url&lat={$coord->lat}&lng={$coord->lon}";
                $discord->http->get($newurl)->then(function ($json) use ($msg, $jsoncoords) {
                    send($msg, "It's " . Carbon::now($json->timezoneId)->format('g:i A \o\n l F j, Y') . " in {$jsoncoords->name}.");
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
registerHelp('time');


    $time->registerSubCommand('save', $savecity, [
        'description' => 'saves a preferred city to use with ;weather and ;time',
        'usage' => '<city>',
    ]);


///////////////////////////////////////////////////////////
$weather = $discord->registerCommand('weather', function ($msg, $args) use ($cities, $discord) {
    $id = isDM($msg) ? $msg->author->id : $msg->author->user->id;
    $api_key = getenv('OWM_API_KEY');
    $url = "http://api.openweathermap.org/data/2.5/weather?APPID=$api_key&units=metric&";
    if (count($args) == 0) {
        // look up for your saved city
        if ($cities->get($id, true)) {
            $ci = $cities->get($id);
            $url .= "id=" . $ci["id"];
            $discord->http->get($url)->then(function ($result) use ($msg, $ci) {
                send($msg, "", formatWeatherJson($result, $ci["timezone"]));
            });
        } else {
            $msg->reply("you can set your preferred city with `;weather save <city>`");
            return;
        }
    } else {
        if (count($msg->mentions) > 0) {
            // look up for another person
            foreach ($msg->mentions as $mention) {
                if ($cities->get($mention->id, true)) {
                    $ci = $cities->get($mention->id);
                    $url .= "id=" . $ci["id"];
                    $discord->http->get($url)->then(function ($result) use ($msg, $ci) {
                        send($msg, "", formatWeatherJson($result, $ci["timezone"]));
                    });
                } else {
                    // mentioned user not found
                    send($msg, "no preferred city found for <@{$mention->id}>.\nset a preferred city with `;weather save city <@{$mention->id}>`.");
                }
            }
        } else {
            // look up any city
            $query = implode("%20", $args);
            $url .= "q=$query";
            $discord->http->get($url)->then(function ($result) use($msg) {
                send($msg, "", formatWeatherJson($result));
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
registerHelp('weather');


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
registerHelp('roll');


///////////////////////////////////////////////////////////
$discord->registerCommand('text_benh', function ($msg, $args) {
    if (count($args) === 0) {
        send($msg, 'can\'t send a blank message');
        return;
    }

    $srvr = $msg->channel->guild->name;
    $user = isDM($msg) ? $msg->author->username : $msg->author->user->username;
    $from = "From: {$srvr} Discord <{$srvr}@bot.benharris.ch>";
    $msg_body = $user . ":\n\n" . implode(" ", $args);

    if (mail(getenv('PHONE_NUMBER') . "@vtext.com", "", $msg_body, $from)) {
        return "message sent to benh";
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
registerHelp('text_benh');



///////////////////////////////////////////////////////////
$discord->registerCommand('avatar', function ($msg, $args) {
    if (count($msg->mentions) === 0) {
        if (isDM($msg)) send($msg, $msg->author->avatar);
        else send($msg, $msg->author->user->avatar);
        return;
    }
    foreach ($msg->mentions as $av)
        send($msg, $av->avatar);
}, [
    'description' => 'gets the avatar for a user',
    'usage' => '<@user>',
    'aliases' => [
        'Avatar',
    ],
]);
registerHelp('avatar');



///////////////////////////////////////////////////////////
$discord->registerCommand('up', function ($msg, $args) use ($starttime) {
    send($msg, "benbot has been up for {$starttime->diffForHumans(Carbon::now(), true)}.");
}, [
    'description' => 'bot uptime',
    'aliases' => [
        'Up',
    ],
]);



///////////////////////////////////////////////////////////
$discord->registerCommand('say', function ($msg, $args) {
    $a = implode(" ", $args);
    if ((strpos($a, '@everyone') !== false) || (strpos($a, '@here') !== false)) {
        $msg->reply("sry, can't do that! :P");
        return;
    }
    send($msg, "$a\n\n**love**, {$msg->author}");
}, [
    'description' => 'repeats stuff back to you',
    'usage' => '<stuff to say>',
    'aliases' => [
        'Say',
    ],
]);





///////////////////////////////////////////////////////////
$discord->registerCommand('sing', function ($msg, $args) {
    $a = implode(" ", $args);
    if ((strpos($a, '@everyone') !== false) || (strpos($a, '@here') !== false)) {
        $msg->reply("sry, can't do that! :P");
        return;
    }
    send($msg, ":musical_note::musical_note::musical_note::musical_note::musical_note::musical_note:\n\n$a\n\n:musical_note::musical_note::musical_note::musical_note::musical_note::musical_note:, {$msg->author}");
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
$discord->registerCommand('set', function ($msg, $args) use ($defs) {
    $def = strtolower(array_shift($args));
    if ($def == "san" && $msg->author->id != 190933157430689792) {
        $msg->reply("you're not san");
        return;
    }
    $defs->set($def, implode(" ", $args));
    send($msg, $def . " set to: " . implode(" ", $args));
}, [
    'description' => 'sets this to that',
    'usage' => '<this> <that>',
    'aliases' => [
        'Set',
    ],
]);
registerHelp('set');
///////////////////////////////////////////////////////////
$discord->registerCommand('get', function ($msg, $args) use ($defs) {
    if (isset($args[0])) send($msg, "**" . $args[0] . "**: " . $defs->get(strtolower($args[0])));
    else send($msg, "can't search for nothing");
}, [
    'description' => 'gets a value from the definitions. you can also omit get (;<thing to get>)',
    'usage' => '<thing to get>',
    'aliases' => [
        'Get',
    ],
]);
registerHelp('get');
///////////////////////////////////////////////////////////
$discord->registerCommand('unset', function ($msg, $args) use ($defs) {
    $defs->unset(strtolower($args[0]));
    send($msg, $args[0] . " unset");
}, [
    'description' => 'removes a definition',
    'usage' => '<def to remove>',
    'aliases' => [
        'Unset',
    ],
]);
registerHelp('unset');


///////////////////////////////////////////////////////////
$discord->registerCommand('listdefs', function ($msg, $args) use ($defs) {
    $ret = "benbot definitions:\n\n";
    foreach ($defs->iter() as $key => $val) {
        $ret .= "**$key**: $val\n";
    }

    if (isDM($msg)) send($msg, $ret);
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
$discord->registerCommand('8ball', function ($msg, $args) use ($fortunes) {
    $ret = "Your Question: *";
    $ret .= count($args) == 0 ? "Why didn't {$msg->author} ask a question?" : implode(" ", $args);
    $ret .= "*\n\n**" . $fortunes[array_rand($fortunes)] . "**";
    send($msg, $ret);
}, [
    'description' => 'tells your fortune',
    'usage' => '<question to ask the mighty 8ball>',
    'aliases' => [
        'ask',
        'Ask',
    ],
]);
registerHelp('8ball');



///////////////////////////////////////////////////////////
$discord->registerCommand('lenny', function ($msg, $args) {
    send($msg, "( ͡° ͜ʖ ͡°)")->then(function ($result) use ($msg) {
        $msg->delete();
    });
}, [
    'description' => 'you should know what this does',
    'aliases' => [
        'Lenny',
    ],
]);
///////////////////////////////////////////////////////////
$discord->registerCommand('lennies', function ($msg, $args) use ($lennyception) {
    send($msg, $lennyception);
}, [
    'description' => '( ͡° ͜ʖ ͡°)',
    'aliases' => [
        'Lennies',
        'lennyception',
        'Lennyception',
    ],
]);
///////////////////////////////////////////////////////////
$discord->registerCommand('shrug', function ($msg, $args) {
    send($msg, "¯\\\_(ツ)\_/¯");
}, [
    'description' => 'meh',
    'aliases' => [
        'Shrug',
        'meh',
        'Meh',
    ],
]);
///////////////////////////////////////////////////////////
$discord->registerCommand('noice', function ($msg, $args) use ($bs) {
    send($msg, $bs);
}, [
    'description' => 'ayyy',
    'aliases' => [
        'Noice',
    ],
]);


///////////////////////////////////////////////////////////
$discord->registerCommand('copypasta', function ($msg, $args) {
    $copypastas = explode("---", file_get_contents(__DIR__.'/copypasta.txt'));
    send($msg, $copypastas[array_rand($copypastas)]);
}, [
    'description' => 'gets random copypasta',
    'aliases' => [
        'Copypasta',
    ],
]);


///////////////////////////////////////////////////////////
$kaomoji = $discord->registerCommand('kaomoji', function ($msg, $args) use ($kaomojis) {
    send($msg, $kaomojis[array_rand($kaomojis)]);
}, [
    'description' => 'sends random kaomoji',
    'usage' => '<sad|happy|angry|confused|surprised>',
    'aliases' => [
        'Kaomoji',
    ],
]);
registerHelp('kaomoji');


    $kaomoji->registerSubCommand('sad', function ($msg, $args) use($sad_kaomojis) {
        send($msg, $sad_kaomojis[array_rand($sad_kaomojis)]);
    }, ['description' => 'sad kaomoji']);
    $kaomoji->registerSubCommand('happy', function ($msg, $args) use($happy_kaomojis) {
        send($msg, $happy_kaomojis[array_rand($happy_kaomojis)]);
    }, ['description' => 'happy kaomoji']);
    $kaomoji->registerSubCommand('angry', function ($msg, $args) use($angry_kaomojis) {
        send($msg, $angry_kaomojis[array_rand($angry_kaomojis)]);
    }, ['description' => 'angry kaomoji']);
    $kaomoji->registerSubCommand('confused', function ($msg, $args) use($confused_kaomojis) {
        send($msg, $confused_kaomojis[array_rand($confused_kaomojis)]);
    }, ['description' => 'confused kaomoji']);
    $kaomoji->registerSubCommand('surprised', function ($msg, $args) use($surprised_kaomojis) {
        send($msg, $surprised_kaomojis[array_rand($surprised_kaomojis)]);
    }, ['description' => 'surprised kaomoji']);
    $kaomoji->registerSubCommand('embarrassed', function ($msg, $args) use($embarrassed_kaomojis) {
        send($msg, $embarrassed_kaomojis[array_rand($embarrassed_kaomojis)]);
    }, ['description' => 'embarrassed kaomoji']);




///////////////////////////////////////////////////////////
$joke = $discord->registerCommand('joke', function ($msg, $args) use ($jokes) {
    send($msg, $jokes[array_rand($jokes)]);
}, [
    'description' => 'tells a random joke',
    'usage' => '<chucknorris|yomama|dad>',
    'aliases' => [
        'Joke',
    ],
]);
registerHelp('joke');


    $joke->registerSubCommand('chucknorris', function ($msg, $args) use ($discord) {
        $url = "http://api.icndb.com/jokes/random1";
        $result = $discord->http->get($url, null, [], false)->then(function ($result) use ($msg) {
            send($msg, $result->value->joke);
        }, function ($e) use ($msg) {
            send($msg, $e->getMessage());
        });
    }, [
        'description' => 'get a random fact about chuck norris',
        'aliases' => [
            'chuck',
        ],
    ]);

    $joke->registerSubCommand('yomama', function ($msg, $args) use ($yomamajokes) {
        send($msg, $yomamajokes[array_rand($yomamajokes)]);
    }, [
        'description' => 'yo mama jokes',
        'aliases' => [
            'mom',
        ],
    ]);

    $joke->registerSubCommand('dad', function ($msg, $args) use ($discord) {
        $url = "https://icanhazdadjoke.com";
        $discord->http->get($url, null, ['Accept' => 'application/json'], false)->then(function ($result) use ($msg) {
            send($msg, $result->joke);
        }, function ($e) use ($msg) {
            send($msg, $e->getMessage());
        });
    }, [
        'description' => 'tells a dad joke',
    ]);



///////////////////////////////////////////////////////////
$discord->registerCommand('block', function ($msg, $args) {
    $ret = "";
    foreach (charIn(strtolower(implode(" ", $args))) as $char) {
        if (ctype_alpha($char)) $ret .= ":regional_indicator_" . $char . ": ";
        else if (ctype_digit($char)) {
            switch ($char) {
                case 0: $ret .= ":zero: "; break;
                case 1: $ret .= ":one: "; break;
                case 2: $ret .= ":two: "; break;
                case 3: $ret .= ":three: "; break;
                case 4: $ret .= ":four: "; break;
                case 5: $ret .= ":five: "; break;
                case 6: $ret .= ":six: "; break;
                case 7: $ret .= ":seven: "; break;
                case 8: $ret .= ":eight: "; break;
                case 9: $ret .= ":nine: "; break;
            }
        }
        else if ($char == " ") $ret .= "   ";
    }
    send($msg, $ret);
}, [
    'description' => 'turn a message into block text',
    'usage' => '<msg>',
    'aliases' => [
        'Block',
    ],
]);
registerHelp('block');



///////////////////////////////////////////////////////////
$ascii = $discord->registerCommand('ascii', function ($msg, $args) {
    $result = shell_exec("figlet " . escapeshellarg(implode(" ", $args)));
    $result = "```$result```";
    if (strlen($result) > 2000) send($msg, "oops message too large for discord");
    else send($msg, $result);
}, [
    'description' => 'ascii-ifies your message',
    'usage' => '<msg>',
    'aliases' => [
        'Ascii',
        'ASCII',
    ],
]);

    $ascii->registerSubCommand('slant', function ($msg, $args) {
        $result = shell_exec("figlet -f smslant " . escapeshellarg(implode(" ", $args)));
        $result = "```$result```";
        if (strlen($result) > 2000) send($msg, "oops message too large for discord");
        else send($msg, $result);
    }, [
        'description' => 'slant ascii',
        'usage' => '<msg>',
    ]);

    $ascii->registerSubCommand('lean', function ($msg, $args) {
        $result = shell_exec("figlet -f lean " . escapeshellarg(implode(" ", $args)) . " | tr ' _/' ' //'");
        $result = "```$result```";
        if (strlen($result) > 2000) send($msg, "oops message too large for discord");
        else send($msg, $result);
    });




///////////////////////////////////////////////////////////
$img = $discord->registerCommand('img', function ($msg, $args) use ($imgs, $discord) {
    $qu = strtolower($args[0]);
    // look for image in uploaded_images
    if ($imgs->get($qu, true)) {
        $imgfile = $imgs->get($qu);
        sendFile($msg, __DIR__."/uploaded_images/$imgfile", $imgfile, $qu);
    }
}, [
    'description' => 'image tools (;help img for more info)',
    'usage' => '<image to show>',
    'aliases' => [
        'Img',
    ],
]);
registerHelp('img');



    $img->registerSubCommand('save', function ($msg, $args) use ($imgs) {
        $qu = strtolower($args[0]);
        if ($imgs->get($qu, true)) {
            send($msg, "img with this name already exists");
            return;
        }
        if (count($msg->attachments) > 0) {
            foreach ($msg->attachments as $attachment) {
                $ext = pathinfo($attachment->url, PATHINFO_EXTENSION);
                $imgs->set($qu, "$qu.$ext");
                file_put_contents(__DIR__."/uploaded_images/$qu.$ext", file_get_contents($attachment->url));
            }

            send($msg, "image saved as $qu");
        } else send($msg, "no image to save");
    }, [
        'description' => 'saves attached image as name',
        'usage' => '<name>',
    ]);


    $img->registerSubCommand('list', function ($msg, $args) use ($imgs) {
        send($msg, "list of uploaded images:\n\n" . implode(", ", $imgs->getKeys()));
    }, [
        'description' => 'saved image list',
    ]);

    $img->registerSubCommand('rm', function ($msg, $args) use ($imgs) {
        $qu = strtolower($args[0]);
        if ($imgs->get($qu, true)) {
            $img = $imgs->get($qu);
            $imgs->unset($qu);
            unlink(__DIR__."/uploaded_images/$img");
            send($msg, "$img deleted");
        } else {
            send($msg, "$img doesn't exist. can't delete");
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
$discord->registerCommand('chat', function ($msg, $args) {
    $msg->channel->broadcastTyping();
    askCleverbot(implode(' ', $args))->then(function ($result) use ($msg) {
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
registerHelp('chat');


///////////////////////////////////////////////////////////
$discord->registerCommand('dm', function ($msg, $args) {
    if (isDM($msg)) send($msg, "you're already in a dm, silly");
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
registerHelp('dm');


///////////////////////////////////////////////////////////
$discord->registerCommand('bamboozle', function ($msg, $args) {
    $ret = "";
    if (count($msg->mentions) > 0)
        foreach ($msg->mentions as $key => $val)
            $ret .= "<@$key>";
    else $ret = $msg->author;
    $ret .= ", you've been heccin' bamboozled again!!!!!!!!!!!!!!!!!!!!";
    sendFile($msg, 'img/bamboozled.jpg', 'bamboozle.jpg', $ret);

}, [
    'description' => "bamboozles mentioned user (or you if you don't mention anyone!!)",
    'usage' => '<user>(optional)',
    'aliases' => [
        'Bamboozle',
    ],
]);

///////////////////////////////////////////////////////////
$discord->registerCommand('swearjar', function($msg, $args) use ($swearjar) {
    $ret = "";
    foreach ($swearjar->iter() as $user => $swear_info) {
        print_r($swear_info);
        $date = new Carbon($swear_info["timestamp"]["date"], $swear_info["timestamp"]["timezone"]);
        $ret .= "<@$user> said \"{$swear_info["latest_swear"]}\" " . $date->diffForHumans() . "\n";
    }
    send($msg, $ret);
}, [
    'description' => 'tattles on naughty people',
    'aliases' => [
        'Swearjar',
        'swears',
        'Swears',
        'dirtymouths',
        'Dirtymouths',
    ],
]);











///////////////////////////////////////////////////////////
// debugging commands
///////////////////////////////////////////////////////////
$discord->registerCommand('dbg', function ($msg, $args) use ($defs, $imgs, $discord) {
    $id = isDM($msg) ? $msg->author->id : $msg->author->user->id;

    if ($id == "193011352275648514") {
        print_r($msg);
        send($msg, "debugging. check logs.");
        print_r($msg->channel->guild);
        echo "args: ", implode(" ", $args), PHP_EOL;
    } else send($msg, "you're not allowed to use that command");
}, [
    'aliases' => [
        'Dbg',
    ],
]);
///////////////////////////////////////////////////////////
$discord->registerCommand('sys', function ($msg, $args) {
    $id = isDM($msg) ? $msg->author->id : $msg->author->user->id;
    if ($id == "193011352275648514") {
        send($msg, "```\n" . shell_exec(implode(" ", $args)) . "\n```");
    } else send($msg, "you're not allowed to use that command");
}, [
    'aliases' => [
        'Sys',
    ],
]);
///////////////////////////////////////////////////////////
$discord->registerCommand('status', function ($msg, $args) use ($discord, $starttime) {
    $usercount = 0;
    foreach ($discord->guilds as $guild) {
        $usercount += $guild->member_count;
    }
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
    print_r($discord->user);
    send($msg, "", $embed);
}, [
    'description' => 'bot status',
    'usage' => '',
    'aliases' => [
        'Status',
    ],
]);
///////////////////////////////////////////////////////////
$discord->registerCommand('server', function ($msg, $args) use ($discord) {
    if (isDM($msg)) {
        send($msg, "you're not in a server right now");
        return;
    }

    $verify_lvls = [
        0 => "None: must have discord account",
        1 => "Low: must have verified email",
        2 => "Medium: must have verified email for more than 5 minutes",
        3 => "(╯°□°）╯︵ ┻━┻: must have verified email, be registered on discord for more than 5 minutes, and must wait 10 minutes before speaking in any channel",
    ];
    $guild = $msg->channel->guild;
    $created_at = Carbon::createFromTimestamp(timestampFromSnowflake($guild->id));

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
    send($msg, "", $embed);
}, [
    'description' => 'server info',
    'aliases' => [
        'Server',
        'guild',
        'Guild',
    ],
]);
registerHelp('server');
///////////////////////////////////////////////////////////
$discord->registerCommand('roles', function ($msg, $args) {
    $ret = "```\nroles for {$msg->channel->guild->name}\n\n";
    foreach ($msg->channel->guild->roles as $role) {
        $ret .= "{$role->name} ({$role->id})\n";
    }
    $ret .= "```";
    send($msg, $ret);
}, [
    'description' => 'lists all roles for the server',
    'aliases' => [
        'Roles',
        'role',
        'Role',
    ],
]);
registerHelp('roles');








///////////////////////////////////////////////////////////
$discord->registerCommand('help', function ($msg, $args) use ($discord, $help) {
    $ret = "```";
    if (count($args) == 1) {
        $qu = strtolower($args[0]);
        if ($cmd = $discord->getCommand($qu, true)) {
            $ret .= $cmd->getHelp(';')["text"];
        } else {
            $ret .= "$qu not found";
        }
        send($msg, "$ret```");
    } else {

        $ret .= file_get_contents(__DIR__.'/banner.txt') . "\n - a bot made by benh. avatar by hirose.\n\n";
        $ret .= implode("", $help);
        $ret .= "\n;help <command> - get more information about a specific command\ncommands will still work if the first letter is capitalized.```";
        send($msg, $ret);
    }
}, [
    'aliases' => [
        'Help',
    ],
]);







$discord->run();

