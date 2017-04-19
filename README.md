# benbot

[Add benbot to your server](https://discordapp.com/api/oauth2/authorize?client_id=288416337057939456&scope=bot&permissions=0)

This is a Discord bot I've been working on. Under heavy development. It's my senior project.

Feel free to clone this project and try your hand at making a bot. 

## Set up

1. `git clone https://git.benharris.ch/ben/benbot && cd benbot`
1. Install DiscordPHP and dependencies: `composer install`
1. Get your Discord API Key and save it in `.env` (copy or rename `.env.example` and paste your keys in)
2. --optional save your API keys in `.env` for openweathermap.org, cleverbot.com, and a phone number for the text command
1. Run the bot `./bot start`

>If you want to keep the bot running in the background, run it as `./bot nohup`

>If bash isn't available, you can run the bot with just `php run.php` (and `nohup php run.php &` to run in the background)


## Commands

```
    //////                        //////                //
   //    //    ////    //////    //    //    ////    ////////
  //////    ////////  //    //  //////    //    //    //
 //    //  //        //    //  //    //  //    //    //
//////      //////  //    //  //////      ////        ////
-------------------------------------------------------------
- a bot made by benh. avatar by hirose.

;8ball <your question to ask here> - ask the mighty 8-ball
;ascii [font] <words> - creates ascii word art
;chat <what you want to say> - talk to benbot
;dm [@user] [message] - sends a dm
;fonts - change your message to another font
;get <thing to find> - retrieve a definition
;img <name of image to show|list|save|rm> - save and retrieve images
;joke [chucknorris|yomama|dad] - tells a random joke
;kaomoji [sad|happy|angry|confused|surprised] - shows a cool japanese emoji face thing
;poll <question> - yes/no poll. lasts 30 seconds.
;roll [number of sides] - rolls an n-sided die (defaults to 6-sided)
;server - displays information about the server
;set <this> <that> - sets this to that
;text_benh [message] - sends an SMS to benh
;time [@user] - looks up times in different time zones. you can save a preferred city.
;unset <thing to remove> - remove a definition
;weather [@user|city search] - get current weather

-------------------------------------------------------------
;help [command] - get more information about a specific command
commands are case-insensitive.

[] denotes an optional argument.
<> denotes a required argument.
|  denotes available options.
```
