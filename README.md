# testcord

[Add phpbot to your server](https://discordapp.com/api/oauth2/authorize?client_id=288416337057939456&scope=bot&permissions=0)

This is a discord bot I've been working on. Under heavy development. Don't add to your server if you're expecting something reliable.

This repo contains two bots. One is PHP, the other is Python. PHP is developed by [Ben Harris](https://github.com/benharri). Python is developed by [Micaiah Parker](https://github.com/micaiahparker).

Feel free to clone this project and try your hand at making a bot. 

## Set up: PHP

1 `git clone https://github.com/benharri/testcord`
1 `cd testcord/php`
1 Install dependencies `composer install`
1 Get your Discord API Key and save it as `/php/token` (no file extension)
1 Run the bot `php bot.php`

* If you want to keep the bot running in the background, run it as `nohup php bot.php`
