# testcord

[Add phpbot to your server](https://discordapp.com/api/oauth2/authorize?client_id=288416337057939456&scope=bot&permissions=0)

This is a discord bot I've been working on. Under heavy development. Don't add to your server if you're expecting something reliable.

Feel free to clone this project and try your hand at making a bot. 

## Set up

1 `git clone https://github.com/benharri/testcord`
1 `cd testcord/php`
1 Install dependencies `composer install`
1 Get your Discord API Key and save it as `/php/token` (no file extension)
1 Run the bot `php bot.php`

* If you want to keep the bot running in the background, run it as `nohup php bot.php`


## Commands

`;time - current time`
`;roll <number of sides> - rolls an n-sided die. defaults to 6.`
`;text_benh <message> - send a message to benh off discord`
`;avatar <@user> - gets the avatar for a user`
`;up - bot uptime`
`;say <stuff to say> - repeats stuff back to you`
`;set <this> <that> - sets this to that`
`;get <thing to get> - gets a value from the definitions`
`;unset <def to remove> - removes a definition`
`;listdefs - lists all definitions`
`;dank - No description provided.`
`;weather <location> - gets weather for a location`
`;8ball <question to ask the mighty 8ball> - tells your fortune`
`;lenny - you should know what this does`
`;lennyception - ( ͡° ͜ʖ ͡°)`
`;kaomoji - sends random kaomoji`
    `;kaomoji sad - sends random sad kaomoji`

    `;kaomoji happy - sends random happy kaomoji`

    `;kaomoji angry - sends random angry kaomoji`

    `;kaomoji confused - sends random confused kaomoji`

    `;kaomoji surprised - sends random surprised kaomoji`

    `;kaomoji embarrassed - sends random embarrassed kaomoji`

`;joke - tells a random joke`
    `;joke chucknorris - get a random fact about chuck norris`

    `;joke yomama - yo mama jokes`

    `;joke dad - tells a dad joke`

`;block <msg> - block text`
`;meme - get a meme`
`;img <image to show> - image tools`
    `;img save <name> - saves attached image as name`

    `;img list - saved image list`

    `;img asciiart <image> - converts image to ascii art`

`; <def or img name> - looks up def or img (note the space). prefers definition if both exist.`