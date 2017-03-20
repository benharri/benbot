<?php

///////////////////////////////////////////////////////////
// config
///////////////////////////////////////////////////////////

include __DIR__.'/vendor/autoload.php';
use Discord\DiscordCommandClient;
use Discord\Parts\User\Game;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Embed\Author;
use Discord\Parts\Embed\Image;
use Discord\Parts\Embed\Footer;
use Discord\Parts\Embed\Field;
use Discord\Helpers\Collection;
use Carbon\Carbon;


include_once __DIR__.'/env_stuff.php';
$discord = new DiscordCommandClient([
    'token'              => get_thing('token'),
    'prefix'             => ';',
    'defaultHelpCommand' => false,
    'name'               => 'benbot',
    'discordOptions'     => [
        'pmChannels'  => true,
    ],
]);

include __DIR__.'/kaomoji.php';
include __DIR__.'/definitions.php';
include __DIR__.'/util_fns.php';

$starttime = new DateTime();
$start_time = Carbon::now();
$defs      = new Definitions(__DIR__.'/definitions.json');
$imgs      = new Definitions(__DIR__.'/img_urls.json');
$cities    = new Definitions(__DIR__.'/cities.json');
$help      = [];




$game = $discord->factory(Game::class, [
    'name' => ';help',
]);


$discord->on('ready', function($discord) use ($game, $defs, $imgs) {
    $discord->updatePresence($game);

    $discord->on('message', function($msg, $args) use ($defs, $imgs) {
        // for stuff that isn't a command
        $text = $msg->content;
        $gen = char_in($text);
        $first_char = $gen->current();

        if ($first_char == ';') {

            for ($qu = "", $gen->next(); $gen->current() != " " && $gen->valid(); $gen->next())
                $qu .= $gen->current();
            $qu = strtolower($qu);
            if ($defs->get($qu, true))
                send($msg, "**$qu**: " . $defs->get($qu));
            if ($imgs->get($qu, true)) {
                $imgfile = $imgs->get($qu);
                $msg->channel->sendFile(__DIR__."/uploaded_images/$imgfile", $imgfile, $qu);
            }

        } else {

            if (is_dm($msg)) {
                if (!$msg->author->bot)
                    send($msg, ask_cleverbot(implode(' ', $args)));
            }
        }
    });

    $discord
        ->guilds->get('id','289410862907785216')
        ->channels->get('id','289611811094003715')
        ->sendMessage("<@193011352275648514>, bot started successfully");
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
], [
    'description' => 'greeting',
    'aliases' => [
        'Hi',
        'Hello',
        'hello',
        'henlo',
        'Henlo',
    ],
]);




///////////////////////////////////////////////////////////
$discord->registerCommand('embed', function($msg, $args) use ($discord) {



    $msg->channel->sendMessage("", false, $discord->factory(\Discord\Parts\Embed\Embed::class, [
        'title' => 'test title',
        'description' => 'testing embed',
        'url' => 'http://discordapp.com'
    ]));


}, [
    'description' => 'fixed it!',
]);



///////////////////////////////////////////////////////////
$discord->registerCommand('http', function($msg, $args) use ($discord) {
    $url = "http://api.icndb.com/jokes/random1";
    $result = $discord->http->get($url);
    print_r($result->result);
}, [
    'description' => 'test',
    'usage' => 'd',
]);


///////////////////////////////////////////////////////////
$time = $discord->registerCommand('time', function($msg, $args) use ($cities, $discord) {
    $url = "http://api.geonames.org/timezoneJSON?username=benharri";
    if (count($args) == 0) {
        // lookup the person's time or tell them to save their time
        if ($cities->get($msg->author->id, true)) {
            $ci = $cities->get($msg->author->id);
            $newurl = "$url&lat={$ci["lat"]}&lng={$ci["lon"]}";

            $json = json_decode(file_get_contents($newurl));
            // $json = $discord->http->get($newurl);
            // print_r($json);
            $jtime = strtotime($json->time);
            send($msg, "It's " . date('g:i A \o\n l F j, Y', $jtime) . " in {$ci["city"]}.");

        } else {
            send($msg, "It's " . date('g:i A \o\n l F j, Y') . " Eastern Time (USA).\nset a preferred city with `;time save city` or `;weather save.`");
        }
    } else {
        if (count($msg->mentions) > 0) {
            // if users are mentioned
            foreach ($msg->mentions as $mention) {
                if ($cities->get($mention->id, true)) {
                    $ci = $cities->get($mention->id);
                    $newurl = "$url&lat={$ci["lat"]}&lng={$ci["lon"]}";
                    $json = json_decode(file_get_contents($newurl));
                    $jtime = strtotime($json->time);
                    send($msg, "It's " . date('g:i A \o\n l F j, Y', $jtime) . " in {$ci["city"]} (<@{$mention->id}>).");
                } else {
                    send($msg, "No city found for <@{$mention->id}>.\nset a preferred city with `;time save city` or `;weather save city`");
                }
            }
        } else {
            // look up the time for whatever they requested
            $msg->channel->broadcastTyping();

            $api_key = get_thing('weather_api_key');
            $query = implode("%20", $args);
            $jsoncoords = json_decode(file_get_contents("http://api.openweathermap.org/data/2.5/weather?q={$query}&APPID=$api_key&units=metric"));
            $coord = $jsoncoords->coord;


            $url = "http://api.geonames.org/timezoneJSON?username=benharri";
            $newurl = "$url&lat={$coord->lat}&lng={$coord->lon}";

            $json = json_decode(file_get_contents($newurl));
            $jtime = strtotime($json->time);
            send($msg, "It's " . date('g:i A \o\n l F j, Y', $jtime) . " in {$jsoncoords->name}.");

        }
    }
}, [
    'description' => 'looks up current time for yourself or another user',
    'usage' => '<@user>',
    'aliases' => [
        'Time',
    ],
]);
register_help('time');


    $time->registerSubCommand('save', function($msg, $args) use ($cities) {
        $api_key = get_thing('weather_api_key');
        $query = implode("%20", $args);
        $json = json_decode(file_get_contents("http://api.openweathermap.org/data/2.5/weather?q={$query}&APPID=$api_key&units=metric"));

        if (count($msg->mentions) > 0) {
            $ret = "the preferred city for ";
            foreach ($msg->mentions as $mention) {
                $cities->set($mention->id, [
                    'id'   => $json->id,
                    'lat'  => $json->coord->lat,
                    'lon'  => $json->coord->lon,
                    'city' => $json->name,
                ]);
                $mentions[] = "<@{$mention->id}>";
            }
            $ret .= implode(", ", $mentions);
            $ret .= " has been set to {$json->name}";
            send($msg, $ret);
        } else {
            $cities->set($msg->author->id, [
                'id'   => $json->id,
                'lat'  => $json->coord->lat,
                'lon'  => $json->coord->lon,
                'city' => $json->name,
            ]);
            $msg->reply("your preferred city has been set to {$json->name}");
        }
    }, [
        'description' => 'saves a preferred city to use with ;weather and ;time',
        'usage' => '<city>',
    ]);


///////////////////////////////////////////////////////////
$weather = $discord->registerCommand('weather', function($msg, $args) use ($cities) {
    $api_key = get_thing('weather_api_key');
    $url = "http://api.openweathermap.org/data/2.5/weather?APPID=$api_key&units=metric&";
    if (count($args) == 0) {
        // look up for your saved city
        if ($cities->get($msg->author->id, true)) {
            $url .= "id=" . $cities->get($msg->author->id)["id"];
            echo $url, PHP_EOL;
            $json = json_decode(file_get_contents($url));
            print_r($json);
            $msg->reply(format_weather($json));
        } else {
            $msg->reply("you can set your preferred city with `;weather save <city>`");
            return;
        }
    } else {
        if (count($msg->mentions) > 0) {
            // look up for another person
            foreach ($msg->mentions as $mention) {
                if ($cities->get($mention->id, true)) {
                    $url .= "id=" . $cities->get($mention->id)["id"];
                    echo $url, PHP_EOL;
                    $json = json_decode(file_get_contents($url));
                    print_r($json);
                    send($msg, format_weather($json));
                } else {
                    // mentioned user not found
                    send($msg, "no preferred city found for <@{$mention->id}>.\nset a preferred city with `;weather save city <@{$mention->id}>`.");
                }
            }
        } else {
            // look up any city
            $query = implode("%20", $args);
            $url .= "q=$query";
            $msg->reply(format_weather(json_decode(file_get_contents($url))));
        }
    }
}, [
    'description' => 'looks up weather for a city, other user, or yourself',
    'usage' => '<city|@user>',
    'aliases' => [
        'Weather',
    ],
]);
register_help('weather');


    $weather->registerSubCommand('save', function($msg, $args) use ($cities) {
        $api_key = get_thing('weather_api_key');
        $query = implode("%20", $args);
        $json = json_decode(file_get_contents("http://api.openweathermap.org/data/2.5/weather?q={$query}&APPID=$api_key&units=metric"));

        if (count($msg->mentions) > 0) {
            $ret = "the preferred city for ";
            foreach ($msg->mentions as $mention) {
                $cities->set($mention->id, [
                    'id'   => $json->id,
                    'lat'  => $json->coord->lat,
                    'lon'  => $json->coord->lon,
                    'city' => $json->name,
                ]);
                $mentions[] = "<@{$mention->id}>";
            }
            $ret .= implode(", ", $mentions);
            $ret .= " has been set to {$json->name}";
            send($msg, $ret);
        } else {
            $cities->set($msg->author->id, [
                'id'   => $json->id,
                'lat'  => $json->coord->lat,
                'lon'  => $json->coord->lon,
                'city' => $json->name,
            ]);
            $msg->reply("your preferred city has been set to {$json->name}");
        }
    }, [
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
register_help('roll');


///////////////////////////////////////////////////////////
$discord->registerCommand('text_benh', function($msg, $args) {
    if (count($args) === 0) {
        send($msg, 'can\'t send a blank message');
        return;
    }

    $srvr = $msg->channel->guild->name;
    $user = is_dm($msg) ? $msg->author->username : $msg->author->user->username;
    $from = "From: {$srvr} Discord <{$srvr}@bot.benharris.ch>";
    $msg_body = $user . ":\n\n" . implode(" ", $args);

    if (mail(get_thing('phone_number') . "@vtext.com", "", $msg_body, $from)) {
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
register_help('text_benh');



///////////////////////////////////////////////////////////
$discord->registerCommand('avatar', function($msg, $args) {
    if (count($msg->mentions) === 0) {
        if (is_dm($msg)) send($msg, $msg->author->avatar);
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
register_help('avatar');



///////////////////////////////////////////////////////////
$discord->registerCommand('up', function($msg, $args) use ($start_time) {
    send($msg, "benbot has been up for {$start_time->diffForHumans(Carbon::now(), true)}");
}, [
    'description' => 'bot uptime',
    'aliases' => [
        'Up',
    ],
]);



///////////////////////////////////////////////////////////
$discord->registerCommand('say', function($msg, $args) {
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
$discord->registerCommand('sing', function($msg, $args) {
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
$discord->registerCommand('set', function($msg, $args) use ($defs) {
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
register_help('set');
///////////////////////////////////////////////////////////
$discord->registerCommand('get', function($msg, $args) use ($defs) {
    if (isset($args[0])) send($msg, "**" . $args[0] . "**: " . $defs->get(strtolower($args[0])));
    else send($msg, "can't search for nothing");
}, [
    'description' => 'gets a value from the definitions. you can also omit get (;<thing to get>)',
    'usage' => '<thing to get>',
    'aliases' => [
        'Get',
    ],
]);
register_help('get');
///////////////////////////////////////////////////////////
$discord->registerCommand('unset', function($msg, $args) use ($defs) {
    $defs->unset(strtolower($args[0]));
    send($msg, $args[0] . " unset");
}, [
    'description' => 'removes a definition',
    'usage' => '<def to remove>',
    'aliases' => [
        'Unset',
    ],
]);
register_help('unset');


///////////////////////////////////////////////////////////
$discord->registerCommand('listdefs', function($msg, $args) use ($defs) {
    $ret = "benbot definitions:\n\n";
    foreach ($defs->iter() as $key => $val) {
        $ret .= "**$key**: $val\n";
    }

    if (is_dm($msg)) send($msg, $ret);
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
    'usage' => '',
    'aliases' => [
        'Listdefs',
    ],
]);







///////////////////////////////////////////////////////////
$discord->registerCommand('8ball', function($msg, $args) use ($fortunes) {
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
register_help('8ball');



///////////////////////////////////////////////////////////
$discord->registerCommand('lenny', function($msg, $args) {
    send($msg, "( ͡° ͜ʖ ͡°)");
    $msg->delete();
}, [
    'description' => 'you should know what this does',
    'aliases' => [
        'Lenny',
    ],
]);
///////////////////////////////////////////////////////////
$discord->registerCommand('lennies', function($msg, $args) use ($lennyception) {
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
$discord->registerCommand('shrug', function($msg, $args) {
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
$discord->registerCommand('noice', function($msg, $args) use ($bs) {
    send($msg, $bs);
}, [
    'description' => 'ayyy',
    'aliases' => [
        'Noice',
    ],
]);


///////////////////////////////////////////////////////////
$discord->registerCommand('copypasta', function($msg, $args) {
    $copypastas = explode("---", file_get_contents(__DIR__.'/copypasta.txt'));
    send($msg, $copypastas[array_rand($copypastas)]);
}, [
    'description' => 'gets random copypasta',
    'aliases' => [
        'Copypasta',
    ],
]);


///////////////////////////////////////////////////////////
$kaomoji = $discord->registerCommand('kaomoji', function($msg, $args) use ($kaomojis) {
    send($msg, $kaomojis[array_rand($kaomojis)]);
}, [
    'description' => 'sends random kaomoji',
    'usage' => '<sad|happy|angry|confused|surprised>',
    'aliases' => [
        'Kaomoji',
    ],
]);
register_help('kaomoji');


    $kaomoji->registerSubCommand('sad', function($msg, $args) use($sad_kaomojis) {
        send($msg, $sad_kaomojis[array_rand($sad_kaomojis)]);
    }, ['description' => 'sad kaomoji']);
    $kaomoji->registerSubCommand('happy', function($msg, $args) use($happy_kaomojis) {
        send($msg, $happy_kaomojis[array_rand($happy_kaomojis)]);
    }, ['description' => 'happy kaomoji']);
    $kaomoji->registerSubCommand('angry', function($msg, $args) use($angry_kaomojis) {
        send($msg, $angry_kaomojis[array_rand($angry_kaomojis)]);
    }, ['description' => 'angry kaomoji']);
    $kaomoji->registerSubCommand('confused', function($msg, $args) use($confused_kaomojis) {
        send($msg, $confused_kaomojis[array_rand($confused_kaomojis)]);
    }, ['description' => 'confused kaomoji']);
    $kaomoji->registerSubCommand('surprised', function($msg, $args) use($surprised_kaomojis) {
        send($msg, $surprised_kaomojis[array_rand($surprised_kaomojis)]);
    }, ['description' => 'surprised kaomoji']);
    $kaomoji->registerSubCommand('embarrassed', function($msg, $args) use($embarrassed_kaomojis) {
        send($msg, $embarrassed_kaomojis[array_rand($embarrassed_kaomojis)]);
    }, ['description' => 'embarrassed kaomoji']);




///////////////////////////////////////////////////////////
$joke = $discord->registerCommand('joke', function($msg, $args) {
    $joke_arr = explode("-----------------------------------------------------------------------------", file_get_contents(__DIR__.'/miscjokes.txt'));
    send($msg, $joke_arr[array_rand($joke_arr)]);
}, [
    'description' => 'tells a random joke',
    'usage' => '<chucknorris|yomama|dad>',
    'aliases' => [
        'Joke',
    ],
]);
register_help('joke');


    $joke->registerSubCommand('chucknorris', function($msg, $args) {
        $json = json_decode(file_get_contents("http://api.icndb.com/jokes/random1"));
        send($msg, $json->value->joke);
    }, [
        'description' => 'get a random fact about chuck norris',
        'aliases' => [
            'chuck',
        ],
    ]);

    $joke->registerSubCommand('yomama', function($msg, $args) {
        $jokes = file("yomamajokes.txt");
        send($msg, $jokes[array_rand($jokes)]);
    }, [
        'description' => 'yo mama jokes',
        'aliases' => [
            'mom',
        ],
    ]);

    $joke->registerSubCommand('dad', function($msg, $args) {
        send($msg, file_get_contents("https://icanhazdadjoke.com/", false, stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Accept: text/plain'
            ]
        ])));
    }, [
        'description' => 'tells a dad joke',
    ]);



///////////////////////////////////////////////////////////
$discord->registerCommand('block', function($msg, $args) {
    $ret = "";
    foreach (char_in(strtolower(implode(" ", $args))) as $char) {
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
register_help('block');



///////////////////////////////////////////////////////////
$img = $discord->registerCommand('img', function($msg, $args) use ($imgs, $discord) {
    $qu = strtolower($args[0]);
    // look for image in uploaded_images
    if ($imgs->get($qu, true)) {
        $imgfile = $imgs->get($qu);
        $msg->channel->sendFile(__DIR__."/uploaded_images/$imgfile", $imgfile, $qu)->then(function($m) use ($discord, $imgfile) {
            // echo "sent", PHP_EOL;
            $discord->logger->info("$imgfile sent");
        }, function ($e) use ($discord) {
            // echo $e->getMessage(), PHP_EOL;
            $discord->logger->info($e->getMessage());
        });
    }
}, [
    'description' => 'image tools (;help img for more info)',
    'usage' => '<image to show>',
    'aliases' => [
        'Img',
    ],
]);
register_help('img');

    // $img->registerSubCommand('save2', function($msg, $args) use ($imgs) {
    //     if (count($msg->attachments) > 0) {
    //         foreach ($msg->attachments as $attachment) {
    //             $pic = file_get_contents($attachment->url);
    //             $ext = pathinfo($attachment->url, PATHINFO_EXTENSION);
    //             $filename = __DIR__.'/uploaded_images/';
    //             $filename .= isset($args[0]) ? $args[0].".$ext" : $attachment->filename;
    //             file_put_contents($filename, $pic);
    //         }
    //     } else send($msg, "no image to save");
    // }, [
    //     'description' => 'image tools',
    //     'usage' => '<save as>',
    // ]);

    $img->registerSubCommand('save', function($msg, $args) use ($imgs) {
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

    // $img->registerSubCommand('list2', function($msg, $args) use ($imgs) {
    //     $dir = new DirectoryIterator(__DIR__.'/uploaded_images/');
    //     foreach ($dir as $fileinfo) {
    //         if (!$fileinfo->isDot()) {
    //             $ret[] = $fileinfo->getBasename(".".$dir->getExtension());
    //         }
    //     }
    //     send($msg, "list of uploaded images:\n\n" . implode(", ", $ret));
    // }, [
    //     'description' => 'saved image list',
    // ]);

    $img->registerSubCommand('list', function($msg, $args) use ($imgs) {
        send($msg, "list of uploaded images:\n\n" . implode(", ", $imgs->list_keys()));
    }, [
        'description' => 'saved image list',
    ]);

    // $img->registerSubCommand('asciiart', function($msg, $args) {
    //     if (count($msg->attachments) > 0) {
    //         print_r($msg->attachments);
    //         $imgpath = $msg->attachments[0]->url;
    //     } else {
    //         $imgpath = $msg->author->user->avatar;
    //     }
    //     echo $imgpath, PHP_EOL;
    //     send($msg, "```" . ascii_from_img($imgpath) . "```");
    // }, [
    //     'description' => 'converts image to ascii art',
    //     'usage' => '<image>',
    // ]);


///////////////////////////////////////////////////////////
// look up defs or images!
$discord->registerCommand('', function($msg, $args) {
    $msg->reply(ask_cleverbot(implode(' ', $args)));
}, [
    'description' => 'talk to ben (you can do this in a DM too!)',
    'usage' => '<msg>',
    'aliases' => [
        'chat',
        'cleverbot',
    ],
]);
register_help('');


///////////////////////////////////////////////////////////
$discord->registerCommand('dm', function($msg, $args) {
    if (is_dm($msg)) send($msg, "you're already in a dm, silly");
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
register_help('dm');


///////////////////////////////////////////////////////////
$discord->registerCommand('bamboozle', function($msg, $args) {
    if (count($msg->mentions) > 0)
        foreach ($msg->mentions as $key => $val)
            $ret .= "<@$key>";
    else $ret = $msg->author;
    $ret .= ", you've been heccin' bamboozled again!!!!!!!!!!!!!!!!!!!!";
    $msg->channel->sendFile('img/bamboozled.jpg', 'bamboozle.jpg', $ret);
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
$discord->registerCommand('dbg', function($msg, $args) use ($defs, $imgs, $discord) {
    if (is_dm($msg)) $id = $msg->author->id;
    else $id = $msg->author->user->id;

    if ($id == "193011352275648514") {
        print_r($msg);
        send($msg, "debugging. check logs.");
        print_r($discord);
    } else send($msg, "you're not allowed to use that command");
}, [
    'aliases' => [
        'Dbg',
    ],
]);
///////////////////////////////////////////////////////////
$discord->registerCommand('sys', function($msg, $args) {
    if (is_dm($msg)) $id = $msg->author->id;
    else $id = $msg->author->user->id;
    if ($id == "193011352275648514") {
        send($msg, "```\n" . shell_exec(implode(" ", $args)) . "\n```");
    } else send($msg, "you're not allowed to use that command");
}, [
    'aliases' => [
        'Sys',
    ],
]);
///////////////////////////////////////////////////////////
$discord->registerCommand('server', function($msg, $args) use ($discord) {
    $verify_lvls = [
        0 => "None: must have discord account",
        1 => "Low: must have verified email",
        2 => "Medium: must have verified email for more than 5 minutes",
        3 => "(╯°□°）╯︵ ┻━┻: must have verified email, be registered on discord for more than 5 minutes, and must wait 10 minutes before speaking in any channel",
    ];
    $guild = $msg->channel->guild;
    $embed = $discord->factory(Embed::class, [
        'title' => "{$guild->name} server info",
        'thumbnail' => $discord->factory(Image::class, [
            'url' => $guild->icon
        ]),
        'fields' => [
            $discord->factory(Field::class, [
                'name' => 'Member Count',
                'value' => $guild->member_count,
            ]),
            $discord->factory(Field::class, [
                'name' => 'Region',
                'value' => $guild->region,
            ]),
            $discord->factory(Field::class, [
                'name' => 'Owner',
                'value' => "@{$guild->owner->username}#{$guild->owner->discriminator}",
            ]),
            $discord->factory(Field::class, [
                'name' => 'Verification level',
                'value' => $verify_lvls[$guild->verification_level],
            ]),
            $discord->factory(Field::class, [
                'name' => 'Server ID',
                'value' => $guild->id,
            ]),
            $discord->factory(Field::class, [
                'name' => 'benbot joined',
                'value' => $guild->joined_at->format('g:i A \o\n l F j, Y') . "\n(" . $guild->joined_at->diffForHumans() . ")",
            ]),
        ],
        'timestamp' => date(),
    ]);
    // print_r($embed);
    // print_r($guild);
    echo "things", PHP_EOL;
    send($msg, "", $embed);
}, [
    'description' => 'server info',
    'aliases' => [
        'Server',
        'guild',
        'Guild',
    ],
]);
register_help('server');
///////////////////////////////////////////////////////////
$discord->registerCommand('roles', function($msg, $args) {
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
register_help('roles');








///////////////////////////////////////////////////////////
$discord->registerCommand('help', function($msg, $args) use ($discord, $help) {
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
        $ret .= "benbot - a bot made by benh. avatar by hirose.\n\n";
        $ret .= implode("", $help);
        $ret .= "\n;help <command> - get more information about a specific command\ncommands will still work if the first letter is capitalized.```";
        if (is_dm($msg)) send($msg, $ret);
        else {
            $msg->author->user->sendMessage($ret);
            $msg->reply("check DMs!");
        }
    }
}, [
    'aliases' => [
        'Help',
    ],
]);







$discord->run();

