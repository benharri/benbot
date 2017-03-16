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


$discord = new DiscordCommandClient([
    'token'              => file_get_contents(__DIR__.'/token'),
    'prefix'             => ';',
    'defaultHelpCommand' => false,
    'name'               => 'benbot',
    'discordOptions'     => [
        'pmChannels' => true,
    ],
]);

include __DIR__.'/kaomoji.php';
include __DIR__.'/definitions.php';
include __DIR__.'/util_fns.php';

$starttime = new DateTime();
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
                echo $qu, ": ", $imgfile, PHP_EOL;
                $msg->channel->sendFile(__DIR__."/uploaded_images/$imgfile", $imgfile, $qu);
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
]);
$discord->registerAlias('Hi', 'hi');
$discord->registerAlias('Hello', 'hi');
$discord->registerAlias('hello', 'hi');




///////////////////////////////////////////////////////////
$discord->registerCommand('embed', function($msg, $args) use ($discord) {
    $embed = $discord->factory(Embed::class, [
        'title'     => 'test',
        'url'       => 'http://google.com',
        'image'     => $discord->factory(Image::class, ['url' => __DIR__.'/img/bamboozled.jpg']),
        'thumbnail' => $discord->factory(Image::class, ['url' => __DIR__.'/img/bamboozled.jpg']),
        'author'    => $discord->factory(Author::class, ['name' => 'Ben']),
        'footer'    => $discord->factory(Footer::class, ['text' => 'footer']),
    ]);
    $msg->channel->sendMessage('embed', false, $embed);
}, [
    'description' => 'not working :(',
]);



///////////////////////////////////////////////////////////
$time = $discord->registerCommand('time', function($msg, $args) use ($cities) {
    $url = "http://api.geonames.org/timezoneJSON?username=benharri";
    if (count($args) == 0) {
        // lookup the person's time or tell them to save their time
        if ($cities->get($msg->author->id, true)) {
            $ci = $cities->get($msg->author->id);
            $newurl = "$url&lat={$ci["lat"]}&lng={$ci["lon"]}";

            $json = json_decode(file_get_contents($newurl));
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

        }
    }
}, [
    'description' => 'looks up current time for yourself or another user',
    'usage' => '<@user>',
]);
register_help('time');
$discord->registerAlias('Time', 'time');


    $time->registerSubCommand('save', function($msg, $args) use ($cities) {
        $api_key = file_get_contents(__DIR__.'/weather_api_key');
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
    $api_key = file_get_contents(__DIR__.'/weather_api_key');
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
]);
register_help('weather');
$discord->registerAlias('Weather', 'weather');


    $weather->registerSubCommand('save', function($msg, $args) use ($cities) {
        $api_key = file_get_contents(__DIR__.'/weather_api_key');
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
]);
register_help('roll');
$discord->registerAlias('Roll', 'roll');


///////////////////////////////////////////////////////////
$discord->registerCommand('text_benh', function($msg, $args) {
    if (count($args) === 0) {
        send($msg, 'can\'t send a blank message');
        return;
    }

    $srvr = $msg->channel->guild->name;
    $user = $msg->author->user->username;
    $from = "From: {$srvr} Discord <{$srvr}@bot.benharris.ch>";
    $msg_body = $user . ":\n\n" . implode(" ", $args);

    if (mail(file_get_contents(__DIR__.'/phone_number') . "@vtext.com", "", $msg_body, $from)) {
        return "message sent to benh";
    }
}, [
    'description' => 'text a message to benh',
    'usage' => '<message>',
]);
register_help('text_benh');
$discord->registerAlias('Text_benh', 'text_benh');



///////////////////////////////////////////////////////////
$discord->registerCommand('avatar', function($msg, $args) {
    if (count($msg->mentions) === 0) {
        send($msg, $msg->author->user->avatar);
        return;
    }
    foreach ($msg->mentions as $av)
        send($msg, $av->avatar);
}, [
    'description' => 'gets the avatar for a user',
    'usage' => '<@user>',
]);
register_help('avatar');
$discord->registerAlias('Avatar', 'avatar');



///////////////////////////////////////////////////////////
$discord->registerCommand('up', function($msg, $args) use ($starttime) {
    $diff = $starttime->diff(new DateTime());
    $ret = "Up for ";
    $ret .= $diff->format("%a") . " day" . ($diff->d == 1 ? ", " : "s, ");
    $ret .= $diff->format("%h") . " hour" . ($diff->h == 1 ? ", " : "s, ");
    $ret .= $diff->format("%i") . " minute" . ($diff->i == 1 ? ", and " : "s, and ");
    $ret .= $diff->format("%s") . " second" . ($diff->s == 1 ? "" : "s");
    send($msg, $ret);
}, [
    'description' => 'bot uptime',
]);
$discord->registerAlias('Up', 'up');



///////////////////////////////////////////////////////////
$discord->registerCommand('say', function($msg, $args) {
    send($msg, implode(" ", $args) . "\n\n**love**, {$msg->author}");
}, [
    'description' => 'repeats stuff back to you',
    'usage' => '<stuff to say>',
]);
$discord->registerAlias('Say', 'say');





///////////////////////////////////////////////////////////
// DEFINITIONS STUFF
///////////////////////////////////////////////////////////
$discord->registerCommand('set', function($msg, $args) use ($defs) {
    $def = strtolower(array_shift($args));
    $defs->set($def, implode(" ", $args));
    send($msg, $def . " set to: " . implode(" ", $args));
}, [
    'description' => 'sets this to that',
    'usage' => '<this> <that>',
]);
register_help('set');
$discord->registerAlias('Set', 'set');
///////////////////////////////////////////////////////////
$discord->registerCommand('get', function($msg, $args) use ($defs) {
    if (isset($args[0])) send($msg, "**" . $args[0] . "**: " . $defs->get(strtolower($args[0])));
    else send($msg, "can't search for nothing");
}, [
    'description' => 'gets a value from the definitions. you can also omit get (;<thing to get>)',
    'usage' => '<thing to get>',
]);
register_help('get');
$discord->registerAlias('Get', 'get');
///////////////////////////////////////////////////////////
$discord->registerCommand('unset', function($msg, $args) use ($defs) {
    $defs->unset(strtolower($args[0]));
    send($msg, $args[0] . " unset");
}, [
    'description' => 'removes a definition',
    'usage' => '<def to remove>',
]);
register_help('unset');
$discord->registerAlias('Unset', 'unset');










///////////////////////////////////////////////////////////
$discord->registerCommand('8ball', function($msg, $args) use ($fortunes) {
    $ret = "Your Question: *";
    $ret .= count($args) == 0 ? "Why didn't {$msg->author} ask a question?" : implode(" ", $args);
    $ret .= "*\n\n**" . $fortunes[array_rand($fortunes)] . "**";
    send($msg, $ret);
}, [
    'description' => 'tells your fortune',
    'usage' => '<question to ask the mighty 8ball>',
]);
register_help('8ball');



///////////////////////////////////////////////////////////
$discord->registerCommand('lenny', function($msg, $args) {
    send($msg, "( ͡° ͜ʖ ͡°)");
    $channel = $msg->channel;
    $channel->deleteMessages([$msg]);
}, [
    'description' => 'you should know what this does',
]);
$discord->registerAlias('Lenny', 'lenny');
///////////////////////////////////////////////////////////
$discord->registerCommand('lennies', function($msg, $args) use ($lennyception) {
    send($msg, $lennyception);
}, [
    'description' => '( ͡° ͜ʖ ͡°)',
]);
$discord->registerAlias('Lennies', 'lennies');
///////////////////////////////////////////////////////////
$discord->registerCommand('shrug', function($msg, $args) {
    send($msg, "¯\\\_(ツ)\_/¯");
}, [
    'description' => 'meh',
]);
$discord->registerAlias('Shrug', 'shrug');


///////////////////////////////////////////////////////////
$kaomoji = $discord->registerCommand('kaomoji', function($msg, $args) use ($kaomojis) {
    send($msg, $kaomojis[array_rand($kaomojis)]);
}, [
    'description' => 'sends random kaomoji',
    'usage' => '<sad|happy|angry|confused|surprised>',
]);
register_help('kaomoji');
$discord->registerAlias('Kaomoji', 'kaomoji');


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
    $json = json_decode(file_get_contents("http://tambal.azurewebsites.net/joke/random"));
    send($msg, $json->joke);
}, [
    'description' => 'tells a random joke',
    'usage' => '<chucknorris|yomama|dad>',
]);
register_help('joke');
$discord->registerAlias('Joke', 'joke');


    $joke->registerSubCommand('chucknorris', function($msg, $args) {
        $json = json_decode(file_get_contents("http://api.icndb.com/jokes/random1"));
        send($msg, $json->value->joke);
    }, [
        'description' => 'get a random fact about chuck norris',
    ]);

    $joke->registerSubCommand('yomama', function($msg, $args) {
        $json = json_decode(file_get_contents("http://api.yomomma.info/"));
        send($msg, $json->joke);
    }, [
        'description' => 'yo mama jokes',
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
]);
register_help('block');
$discord->registerAlias('Block', 'block');



///////////////////////////////////////////////////////////
$img = $discord->registerCommand('img', function($msg, $args) use ($imgs) {
    $qu = strtolower($args[0]);
        // look for image in uploaded_images
    if ($imgs->get($qu, true)) {
        $imgfile = $imgs->get($qu);
        echo $qu, ": ", $imgfile, PHP_EOL;
        $msg->channel->sendFile(__DIR__."/uploaded_images/$imgfile", $imgfile, $qu);
    }
}, [
    'description' => 'image tools (;help img for more info)',
    'usage' => '<image to show>',
]);
register_help('img');
$discord->registerAlias('Img', 'img');

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
$discord->registerCommand('', function($msg, $args) use ($defs, $imgs) {
    $qu = strtolower($args[0]);
    if ($defs->get($qu, true))
        send($msg, "**$qu**: " . $defs->get($qu));
    if ($imgs->get($qu, true)) {
        $imgfile = $imgs->get($qu);
        echo $qu, ": ", $imgfile, PHP_EOL;
        $msg->channel->sendFile(__DIR__."/uploaded_images/$imgfile", $imgfile, $qu);
    }
}, [
    'description' => 'looks up def or img',
    'usage' => '<def or img name>',
]);


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
]);
$discord->registerAlias('Bamboozle', 'bamboozle');









///////////////////////////////////////////////////////////
// debugging commands
///////////////////////////////////////////////////////////
$discord->registerCommand('dbg', function($msg, $args) use ($defs, $imgs) {
    var_dump($msg->author->user->id);
    if ($msg->author->user->id == "193011352275648514") {
        print_r($msg);
        send($msg, "debugging. check logs.");
    } else send($msg, "you're not allowed to use that command");
});
$discord->registerAlias('Dbg', 'dbg');
///////////////////////////////////////////////////////////
$discord->registerCommand('sys', function($msg, $args) {
    if ($msg->author->user->id == "193011352275648514") {
        send($msg, "```\n" . shell_exec(implode(" ", $args)) . "\n```");
    } else send($msg, "you're not allowed to use that command");
});
$discord->registerAlias('Sys', 'sys');









///////////////////////////////////////////////////////////
$discord->registerCommand('help', function($msg, $args) use ($discord, $help) {
    $ret = "```";
    print_r($help);
    if (count($args) == 1) {
        if ($cmd = $discord->getCommand($args[0], true)) {
            $ret .= $args[0] . " info\n\n";
            $lookup_help = $cmd->getHelp(';');
            $ret .= $lookup_help["text"];
        } else {
            $ret .= "command not found";
        }
    } else {
        $ret .= "benbot - a bot made by benh. avatar by hirose.\n\n";
        $ret .= implode("", $help);
        $ret .= "\n;help <command> - get more information about a specific command";
    }
    $ret .= "```";
    print_r($msg);
    $msg->author->user->sendMessage($ret);
    send($msg, $ret);
});
$discord->registerAlias('Help', 'help');







$discord->run();

