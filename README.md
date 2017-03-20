# benbot

[Add benbot to your server](https://discordapp.com/api/oauth2/authorize?client_id=288416337057939456&scope=bot&permissions=0)

This is a discord bot I've been working on. Under heavy development.

Feel free to clone this project and try your hand at making a bot. 

## Set up

1. `git clone https://github.com/benharri/testcord && cd testcord`
1. Install DiscordPHP and dependencies: `composer install`
1. Get your Discord API Key and save it in `env_stuff.php`
2. --optional save your API keys in `env_stuff.php` for openweathermap.org, cleverbot.io, and a phone number for the text command
1. Run the bot `./bot start`

>If you want to keep the bot running in the background, run it as `./bot nohup`

>If bash isn't available, you can run the bot with just `php bot.php` (and `nohup php bot.php &` to run in the background)


## Commands

- `benbot - a bot made by benh. avatar by hirose.`


- `;time <@user> - looks up current time for yourself or another user`
- `;weather <city|@user> - looks up weather for a city, other user, or yourself`
- `;roll <number of sides> - rolls an n-sided die. defaults to 6.`
- `;text_benh <message> - text a message to benh`
- `;avatar <@user> - gets the avatar for a user`
- `;set <this> <that> - sets this to that`
`;get <thing to get> - gets a value from the definitions. you can also omit - get (;<thing to get>)`
- `;unset <def to remove> - removes a definition`
- `;8ball <question to ask the mighty 8ball> - tells your fortune`
- `;kaomoji <sad|happy|angry|confused|surprised> - sends random kaomoji`
- `;joke <chucknorris|yomama|dad> - tells a random joke`
- `;block <msg> - turn a message into block text`
- `;img <image to show> - image tools (;help img for more info)`
- `; <msg> - talk to ben (you can do this in a DM with me too!)`


- `;help <command> - get more information about a specific command`
- `commands will still work if the first letter is capitalized.`