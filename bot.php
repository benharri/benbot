<?php

///////////////////////////////////////////////////////////
// config
///////////////////////////////////////////////////////////

include __DIR__.'/vendor/autoload.php';
use Discord\DiscordCommandClient;
use Discord\Parts\User\Game;
use Discord\Parts\Channel\Embed;

include __DIR__.'/kaomoji.php';
include __DIR__.'/definitions.php';
include __DIR__.'/util_fns.php';

$starttime = new DateTime();
$defs = new Definitions();
$imgs = new Definitions(__DIR__.'/img_urls.json');

$discord = new DiscordCommandClient([
    'token' => file_get_contents(__DIR__.'/token'),
    'prefix' => ';',
    'description' => "benh's bot made with DiscordPHP. avatar by hirose.",
]);

$game = $discord->factory(Game::class, [
    'name' => ';help',
]);

$discord->on('ready', function($discord) use ($game, $defs, $imgs) {
    $discord->updatePresence($game);

    $discord->on('message', function($msg, $args) use ($defs, $imgs) {
        $text = $msg->content;
        $gen = char_in($text);
        $first_char = $gen->current();

        if ($first_char == ';') {
            for ($qu = "", $gen->next(); $gen->current() != " " && $gen->valid(); $gen->next())
                $qu .= $gen->current();
            $qu = strtolower($qu);
            if ($defs->get($qu, true))
                send($msg, "**$qu**: " . $defs->get($qu));
            if ($imgs->get($qu, true))
                send($msg, "**$qu**: " . $imgs->get($qu));

        }
    });
});



///////////////////////////////////////////////////////////
// commands
///////////////////////////////////////////////////////////


///////////////////////////////////////////////////////////
$discord->registerCommand('ping', function($msg) {
    send($msg, 'pong');
}, [
    'description' => 'ping pong',
    'usage' => '',
]);

///////////////////////////////////////////////////////////
$discord->registerCommand('ding', function($msg, $args) {
    send($msg, 'dong');
}, [
    'description' => 'dong',
    'usage' => '',
]);


///////////////////////////////////////////////////////////
$discord->registerCommand('hi', [
    'hey',
    'hello',
    'wussup'
], [
    'description' => 'greeting',
    'usage' => '',
    'aliases' => [
        'hello',
        'sup',
    ],
]);





///////////////////////////////////////////////////////////
$discord->registerCommand('time', function($msg) {
    send($msg, "It's " . date('g:i A \o\n F j, Y'));
}, [
    'description' => 'current time'
]);


///////////////////////////////////////////////////////////
$discord->registerCommand('roll', function ($msg, $args) {
    $msg->reply('you rolled a ' . rand(1, $args[0] ?? 6));
}, [
    'description' => 'rolls an n-sided die. defaults to 6.',
    'usage' => '<number of sides>',
]);


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
    'usage' => '',
]);


///////////////////////////////////////////////////////////
$discord->registerCommand('say', function($msg, $args) {
    send($msg, implode(" ", $args) . "\n\n**love**, {$msg->author}");
}, [
    'description' => 'repeats stuff back to you',
    'usage' => '<stuff to say>',
]);





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
///////////////////////////////////////////////////////////
$discord->registerCommand('get', function($msg, $args) use ($defs) {
    if (isset($args[0])) send($msg, "**" . $args[0] . "**: " . $defs->get(strtolower($args[0])));
    else send($msg, "can't search for nothing");
}, [
    'description' => 'gets a value from the definitions',
    'usage' => '<thing to get>',
]);
///////////////////////////////////////////////////////////
$discord->registerCommand('unset', function($msg, $args) use ($defs) {
    $defs->unset(strtolower($args[0]));
    send($msg, $args[0] . " unset");
}, [
    'description' => 'removes a definition',
    'usage' => '<def to remove>',
]);
///////////////////////////////////////////////////////////
$discord->registerCommand('listdefs', function($msg, $args) use ($defs) {
    send($msg, "**definitions**:\n\n" . $defs->print());
}, [
    'description' => 'lists all definitions',
    'usage' => '',
]);




///////////////////////////////////////////////////////////
$discord->registerCommand('dank', function($msg) {
    send($msg, 'memes');
});






///////////////////////////////////////////////////////////
$discord->registerCommand('weather', function($msg, $args) {
    $api_key = file_get_contents(__DIR__.'/weather_api_key');
    $query = implode("%20", $args);
    $json = json_decode(file_get_contents("http://api.openweathermap.org/data/2.5/weather?q={$query}&APPID=$api_key&units=metric"));
    print_r($json);
    $ret = "it's {$json->main->temp}°C in {$json->name}";
    $msg->reply($ret);
}, [
    'description' => 'gets weather for a location',
    'usage' => '<location>',
]);






$fortunes = [
    "It is certain",
    "It is decidedly so",
    "Without a doubt",
    "Yes definitely",
    "You may rely on it",
    "As I see it, yes",
    "Most likely",
    "Outlook good",
    "Yes",
    "Signs point to yes",
    "Reply hazy try again",
    "Ask again later",
    "Better not tell you now",
    "Cannot predict now",
    "Concentrate and ask again",
    "Don't count on it",
    "My reply is no",
    "My sources say no",
    "Outlook not so good",
    "Very doubtful",
];

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



///////////////////////////////////////////////////////////
$discord->registerCommand('lenny', function($msg, $args) {
    send($msg, "( ͡° ͜ʖ ͡°)");
    $channel = $msg->channel;
    $channel->deleteMessages([$msg]);
}, [
    'description' => 'you should know what this does',
    'usage' => '',
]);
///////////////////////////////////////////////////////////
$discord->registerCommand('lennies', function($msg, $args) use ($lennyception) {
    send($msg, $lennyception);
}, [
    'description' => '( ͡° ͜ʖ ͡°)',
    'usage' => '',
]);
///////////////////////////////////////////////////////////
$discord->registerCommand('shrug', function($msg, $args) {
    send($msg, "¯\\\_(ツ)\_/¯");
}, [
    'description' => 'meh',
    'usage' => '',
]);


///////////////////////////////////////////////////////////
$kaomoji = $discord->registerCommand('kaomoji', function($msg, $args) use ($kaomojis) {
    send($msg, $kaomojis[array_rand($kaomojis)]);
}, [
    'description' => 'sends random kaomoji',
    'usage' => '',
]);

    $kaomoji->registerSubCommand('sad', function($msg, $args) use($sad_kaomojis) {
        send($msg, $sad_kaomojis[array_rand($sad_kaomojis)]);
    }, [
        'description' => 'sends random sad kaomoji',
        'usage' => '',
    ]);
    $kaomoji->registerSubCommand('happy', function($msg, $args) use($happy_kaomojis) {
        send($msg, $happy_kaomojis[array_rand($happy_kaomojis)]);
    }, [
        'description' => 'sends random happy kaomoji',
        'usage' => '',
    ]);
    $kaomoji->registerSubCommand('angry', function($msg, $args) use($angry_kaomojis) {
        send($msg, $angry_kaomojis[array_rand($angry_kaomojis)]);
    }, [
        'description' => 'sends random angry kaomoji',
        'usage' => '',
    ]);
    $kaomoji->registerSubCommand('confused', function($msg, $args) use($confused_kaomojis) {
        send($msg, $confused_kaomojis[array_rand($confused_kaomojis)]);
    }, [
        'description' => 'sends random confused kaomoji',
        'usage' => '',
    ]);
    $kaomoji->registerSubCommand('surprised', function($msg, $args) use($surprised_kaomojis) {
        send($msg, $surprised_kaomojis[array_rand($surprised_kaomojis)]);
    }, [
        'description' => 'sends random surprised kaomoji',
        'usage' => '',
    ]);
    $kaomoji->registerSubCommand('embarrassed', function($msg, $args) use($embarrassed_kaomojis) {
        send($msg, $embarrassed_kaomojis[array_rand($embarrassed_kaomojis)]);
    }, [
        'description' => 'sends random embarrassed kaomoji',
        'usage' => '',
    ]);




///////////////////////////////////////////////////////////
$joke = $discord->registerCommand('joke', function($msg, $args) {
    $json = json_decode(file_get_contents("http://tambal.azurewebsites.net/joke/random"));
    send($msg, $json->joke);
}, [
    'description' => 'tells a random joke',
    'usage' => '',
    'aliases' => [
        'Joke',
    ],
]);

    $joke->registerSubCommand('chucknorris', function($msg, $args) {
        $json = json_decode(file_get_contents("http://api.icndb.com/jokes/random1"));
        send($msg, $json->value->joke);
    }, [
        'description' => 'get a random fact about chuck norris',
        'usage' => '',
    ]);

    $joke->registerSubCommand('yomama', function($msg, $args) {
        $json = json_decode(file_get_contents("http://api.yomomma.info/"));
        send($msg, $json->joke);
    }, [
        'description' => 'yo mama jokes',
        'usage' => '',
    ]);

    $joke->registerSubCommand('dad', function($msg, $args) {
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => 'Accept: text/plain'
            ]
        ];
        $context = stream_context_create($opts);
        send($msg, file_get_contents("https://icanhazdadjoke.com/", false, $context));
    }, [
        'description' => 'tells a dad joke',
        'usage' => '',
    ]);


///////////////////////////////////////////////////////////
$discord->registerCommand('text', function($msg, $args) {
    $pain = str_split("！゛＃＄％＆'（）＊＋、ー。／０１２３４５６７８９：；〈＝〉？＠ＡＢＣＤＥＦＧＨＩＪＫＬＭＮＯＰＱＲＳＴＵＶＷＸＹＺ［］＾＿‘ａｂｃｄｅｆｇｈｉｊｋｌｍｎｏｐｑｒｓｔｕｖｗｘｙｚ");
    $res = "";
    foreach (char_in(implode(" ", $args)) as $char) {
        $ord = ord($char);
        if ($ord > 32 && $ord < 124) $res .= $pain[$ord - 33];
        else $res .= $char;
    }
    send($msg, "$res");
}, [
    'description' => 'convert ASCII to Unicode for font effect',
    'usage' => '<text to convert>',
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
    'description' => 'block text',
    'usage' => '<msg>',
]);



///////////////////////////////////////////////////////////
$discord->registerCommand('meme', function($msg, $args) {
    send($msg, 'dank');
}, [
    'description' => 'get a meme',
    'usage' => '',
]);



///////////////////////////////////////////////////////////
$img = $discord->registerCommand('img', function($msg, $args) use ($imgs) {
    if (count($args) > 0) {
        // look for image in uploaded_images
        send($msg, $imgs->get($args[0]))->then(function($reason) { echo "link sent", PHP_EOL; });
        $msg->channel->sendFile($imgs->get($args[0]), 'img.jpg')->then(function ($reason) {
            echo $reason, PHP_EOL;
        })->otherwise(function($reason) {
            echo $reason, PHP_EOL;
        });
    } else {
        return;
    }
}, [
    'description' => 'image tools',
    'usage' => '<image to show>',
    'aliases' => [
        'Img',
        'image',
        'Image',
    ],
]);

    $img->registerSubCommand('save2', function($msg, $args) use ($imgs) {
        if (count($msg->attachments) > 0) {
            foreach ($msg->attachments as $attachment) {
                $pic = file_get_contents($attachment->url);
                $ext = pathinfo($attachment->url, PATHINFO_EXTENSION);
                $filename = __DIR__.'/uploaded_images/';
                $filename .= isset($args[0]) ? $args[0].".$ext" : $attachment->filename;
                file_put_contents($filename, $pic);
            }
        } else send($msg, "no image to save");
    }, [
        'description' => 'image tools',
        'usage' => '<save as>',
    ]);

    $img->registerSubCommand('save', function($msg, $args) use ($imgs) {
        if (count($msg->attachments) > 0) {
            $i = 0;
            foreach ($msg->attachments as $attachment)
                $imgs->set($args[$i++], $attachment->url);
            send($msg, "image saved");
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
    //     'usage' => '',
    // ]);

    $img->registerSubCommand('list', function($msg, $args) use ($imgs) {
        send($msg, "list of uploaded images:\n\n" . implode(", ", $imgs->list_keys()));
    }, [
        'description' => 'saved image list',
        'usage' => '',
    ]);

    $img->registerSubCommand('asciiart', function($msg, $args) {
        if (count($msg->attachments) > 0) {
            print_r($msg->attachments);
            $imgpath = $msg->attachments[0]->url;
        } else {
            $imgpath = $msg->author->user->avatar;
        }
        echo $imgpath, PHP_EOL;
        send($msg, "```" . ascii_from_img($imgpath) . "```");
    }, [
        'description' => 'converts image to ascii art',
        'usage' => '<image>',
    ]);


///////////////////////////////////////////////////////////
// look up defs or images!
$discord->registerCommand('', function($msg, $args) use ($defs, $imgs) {
    $qu = strtolower($args[0]);
    if ($defs->get($qu, true))
        send($msg, "**$qu**: " . $defs->get($qu));
    if ($imgs->get($qu, true))
        send($msg, "**$qu**: " . $imgs->get($qu));
}, [
    'description' => 'looks up def or img',
    'usage' => '<def or img name>',
]);


///////////////////////////////////////////////////////////
$discord->registerCommand('bamboozle', function($msg, $args) {
    if (count($msg->mentions) > 0) {
        foreach ($msg->mentions as $key => $val)
            $ret .= "<@$key>";
    }
    else $ret = $msg->author;
    // print_r($msg->mentions);
    $ret .= ", you've been heccin' bamboozled again!!!!!!!!!!!!!!!!!!!!";
    // echo $ret;
    $msg->channel->sendFile('img/bamboozled.jpg', 'bamboozle.jpg', $ret);
}, [
    'description' => "bamboozles mentioned user (or you if you don't mention anyone!!)",
    'usage' => '<user>(optional)',
]);


///////////////////////////////////////////////////////////
$discord->registerCommand('dbg', function($msg, $args) use ($defs, $imgs) {
    var_dump($msg->author->user->id);
    if ($msg->author->user->id == "193011352275648514") {
        print_r($msg);
        send($msg, "debugging. check logs.");
    } else send($msg, "you're not allowed to use that command");
}, [
    'description' => 'debugging... only benh can use',
    'usage' => '',
]);



///////////////////////////////////////////////////////////
$discord->registerCommand('sys', function($msg, $args) {
    if ($msg->author->user->id == "193011352275648514") {
        send($msg, "```\n" . shell_exec(implode(" ", $args)) . "\n```");
    } else send($msg, "you're not allowed to use that command");
}, [
    'description' => 'runs command on local shell... only benh can use',
    'usage' => '<cmd>',
]);




$discord->run();

