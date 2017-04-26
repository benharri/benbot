<?php
namespace BenBot\Commands;

use BenBot\Utils;

class Fun
{

    private static $bot;

    public static function register(&$that)
    {
        self::$bot = $that;

        self::$bot->registerCommand('roll', [__CLASS__, 'rollDie'], [
            'description'  => 'rolls an n-sided die (defaults to 6-sided)',
            'usage'        => '[number of sides]',
            'registerHelp' => true,
        ]);
        self::$bot->registerCommand('8ball', [__CLASS__, 'ask8Ball'], [
            'description'  => 'ask the mighty 8-ball',
            'usage'        => '<your question to ask here>',
            'registerHelp' => true,
            'aliases' => [
                '8b',
                'fortune',
            ],
        ]);
        self::$bot->registerCommand('lenny', [__CLASS__, 'lennyFace'], [
            'description' => 'you should know what this does',
        ]);
        self::$bot->registerCommand('shrug', [__CLASS__, 'shrugGuy'], [
            'description' => 'meh',
            'aliases' => [
                'meh',
            ],
        ]);
        self::$bot->registerCommand('noice', [__CLASS__, 'noice'], [
            'description' => 'ayy lmao',
        ]);
        self::$bot->registerCommand('copypasta', [__CLASS__, 'copyPasta'], [
            'description' => 'get random copypasta',
        ]);
        self::$bot->registerCommand('kaomoji', [__CLASS__, 'kaomoji'], [
            'description' => 'shows a cool japanese emoji face thing',
            'usage' => '[sad|happy|angry|confused|surprised]',
            'registerHelp' => true,
        ]);
        self::$bot->registerCommand('bamboozle', [__CLASS__, 'bamboozle'], [
            'description' => 'bamboozled again',
            'usage' => '[@user]',
        ]);
        self::$bot->registerCommand('trap', [__CLASS__, 'trap'], [
            'description' => 'hmm',
        ]);

        echo __CLASS__ . " registered", PHP_EOL;
    }




    public static function rollDie($msg, $args)
    {
        return "{$msg->author}, you rolled a " . rand(1, $args[0] == "" ? 6 : $args[0]);
    }

    public static function ask8Ball($msg, $args)
    {
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

        if (count($args) > 0) {
            $response = "Your question: *" . implode(" ", $args) . "*\n\n**";
            $response .= $fortunes[array_rand($fortunes)] . "**";
            Utils::send($msg, $response)->then(function ($result) use ($msg) {
                Utils::deleteMessage($msg);
            });
        } else {
            return "{$msg->author}, you have to ask a question!";
        }
    }

    public static function lennyFace($msg, $args)
    {
        echo "lenny", PHP_EOL;
        Utils::send($msg, "( ͡° ͜ʖ ͡°)\n--{$msg->author}")->then(function ($result) use ($msg) {
            Utils::deleteMessage($msg);
        });
    }

    public static function shrugGuy($msg, $args)
    {
        echo "meh", PHP_EOL;
        Utils::send($msg, "¯\\\_(ツ)\_/¯")->then(function ($result) use ($msg) {
            Utils::deleteMessage($msg);
        });
    }

    public static function noice($msg, $args)
    {
$bs = "
  :ok_hand:　:joy:
   :ok_hand::joy:
　 :joy:
   :joy::ok_hand:
 :joy:　:ok_hand:
:joy:　　:ok_hand:
:joy:　　:ok_hand:
 :joy:　:ok_hand:
  :joy: :ok_hand:
　  :ok_hand:
　:ok_hand: :joy:
 :ok_hand:　 :joy:
:ok_hand:　　:joy:
:ok_hand:　:joy:
   :ok_hand::joy:
　 :joy:
   :joy::ok_hand:
 :joy:　:ok_hand:
:joy:　　:ok_hand:
:joy:　　:ok_hand:
 :joy:　:ok_hand:
  :joy: :ok_hand:
　  :ok_hand:";

        Utils::send($msg, $bs)->then(function ($result) use ($msg) {
            Utils::deleteMessage($msg);
        });
    }

    public static function copyPasta($msg, $args)
    {
        return self::$bot->copypastas[array_rand(self::$bot->copypastas)];
    }

    public static function kaomoji($msg, $args)
    {
        $kaomojis = [
            'sad' => ['(ノ_<。)', '(*-_-)', '(´-ω-`)', '.･ﾟﾟ･(／ω＼)･ﾟﾟ･.', '(μ_μ)', '(ﾉД`)', '(-ω-、)', '。゜゜(´Ｏ`) ゜゜。', 'o(TヘTo)', '( ; ω ; )', '(｡╯3╰｡)', '｡･ﾟﾟ*(>д<)*ﾟﾟ･｡', '( ﾟ，_ゝ｀)', '(个_个)', '(╯︵╰,)', '｡･ﾟ(ﾟ><ﾟ)ﾟ･｡', '( ╥ω╥ )', '(╯_╰)', '(╥_╥)', '.｡･ﾟﾟ･(＞_＜)･ﾟﾟ･｡.', '(／ˍ・、)', '(ノ_<、)', '(╥﹏╥)', '｡ﾟ(｡ﾉωヽ｡)ﾟ｡', '(つω`*)', '(｡T ω T｡)', '(ﾉω･､)', '･ﾟ･(｡>ω<｡)･ﾟ･', '(T_T)', '(>_<)', '(Ｔ▽Ｔ)', '｡ﾟ･ (>﹏<) ･ﾟ｡', 'o(〒﹏〒)o', '(｡•́︿•̀｡)', '(ಥ﹏ಥ)'],
            'happy' => ['(* ^ ω ^)', '(´ ∀ ` *)', '٩(◕‿◕｡)۶', '☆*:.｡.o(≧▽≦)o.｡.:*☆', '(o^▽^o)', '(⌒▽⌒)☆', '<(￣︶￣)>', 'ヽ(・∀・)ﾉ', '(´｡• ω •｡`)', '(￣ω￣)', '｀;:゛;｀;･(°ε° )', '(o･ω･o)', '(＠＾－＾)', 'ヽ(*・ω・)ﾉ', '(o_ _)ﾉ彡☆', '(^人^)', '(o´▽`o)', '(*´▽`*)', '｡ﾟ( ﾟ^∀^ﾟ)ﾟ｡', '( ´ ω ` )', '(((o(*°▽°*)o)))', '(≧◡≦)', '(o´∀`o)', '(´• ω •`)', '(＾▽＾)', '(⌒ω⌒)', '∑d(°∀°d)', '╰(▔∀▔)╯', '(─‿‿─)', '(*^‿^*)', 'ヽ(o^―^o)ﾉ', '(✯◡✯)', '(◕‿◕)', '(*≧ω≦*)', '(☆▽☆)', '(⌒‿⌒)', '＼(≧▽≦)／', '⌒(o＾▽＾o)ノ', '(*°▽°*)', '٩(｡•́‿•̀｡)۶', '(✧ω✧)', 'ヽ(*⌒▽⌒*)ﾉ', '(´｡• ᵕ •｡`)', '( ´ ▽ ` )', '(￣▽￣)', '╰(*´︶`*)╯', 'ヽ(>∀<☆)ノ', 'o(≧▽≦)o', '(☆ω☆)', '(っ˘ω˘ς )', '＼(￣▽￣)／', '(*¯︶¯*)', '＼(＾▽＾)／', '٩(◕‿◕)۶', '(o˘◡˘o)', '\(★ω★)/', '\(^ヮ^)/', '(〃＾▽＾〃)', '(╯✧▽✧)╯', 'o(>ω<)o', 'o( ❛ᴗ❛ )o', '｡ﾟ(TヮT)ﾟ｡', '( ‾́ ◡ ‾́ )', '(ﾉ´ヮ`)ﾉ*: ･ﾟ'],
            'angry' => ['(＃`Д´)', '(`皿´＃)', '( ` ω ´ )', 'ヽ( `д´*)ノ', '(・`ω´・)', '(`ー´)', 'ヽ(`⌒´メ)ノ', '凸(`△´＃)', '( `ε´ )', 'ψ( ` ∇ ´ )ψ', 'ヾ(`ヘ´)ﾉﾞ', 'ヽ(‵﹏´)ノ', '(ﾒ` ﾛ ´)', '(╬`益´)', '┌∩┐(◣_◢)┌∩┐', '凸( ` ﾛ ´ )凸', 'Σ(▼□▼メ)', '(°ㅂ°╬)', 'ψ(▼へ▼メ)～→', '(ノ°益°)ノ', '(҂ `з´ )', '(‡▼益▼)', '(҂` ﾛ ´)凸', '((╬◣﹏◢))', '٩(╬ʘ益ʘ╬)۶', '(╬ Ò﹏Ó)', '＼＼٩(๑`^´๑)۶／／', '(凸ಠ益ಠ)凸', '↑_(ΦwΦ)Ψ', '←~(Ψ▼ｰ▼)∈', '୧((#Φ益Φ#))୨', '٩(ఠ益ఠ)۶', '(ﾉಥ益ಥ)ﾉ'],
            'confused' => ['(￣ω￣;)', 'σ(￣、￣〃)', '(￣～￣;)', '(-_-;)・・・', '(・_・ヾ', '(〃￣ω￣〃ゞ', '┐(￣ヘ￣;)┌', '(・_・;)', '(￣_￣)・・・', '╮(￣ω￣;)╭', '(￣.￣;)', '(＠_＠)', '(・・;)ゞ', 'Σ(￣。￣ﾉ)', '(・・ ) ?', '(•ิ_•ิ)?', '(◎ ◎)ゞ', '(ーー;)', 'ლ(ಠ_ಠ ლ)', 'ლ(¯ロ¯"ლ)'],
            'surprised' => ['w(°ｏ°)w', 'ヽ(°〇°)ﾉ', 'Σ(O_O)', 'Σ(°ロ°)', '(⊙_⊙)', '(o_O)', '(O_O;)', '(O.O)', '(°ロ°) !', '(o_O) !', '(□_□)', 'Σ(□_□)', '∑(O_O;)', '( : ౦ ‸ ౦ : )'],
            'embarrassed' => ['(⌒_⌒;)', '(o^ ^o)', '(*/ω＼)', '(*/。＼)', '(*/_＼)', '(*ﾉωﾉ)', '(o-_-o)', '(*μ_μ)', '( ◡‿◡ *)', '(ᵔ.ᵔ)', '(//ω//)', '(ノ*°▽°*)', '(*^.^*)', '(*ﾉ▽ﾉ)', '(￣▽￣*)ゞ', '(⁄ ⁄•⁄ω⁄•⁄ ⁄)', '(*/▽＼*)', '(⁄ ⁄>⁄ ▽ ⁄<⁄ ⁄)'],
        ];

        if (isset($args[0]) && isset($kaomojis[$args[0]])) {
            Utils::send($msg, $kaomojis[$args[0]][array_rand($kaomojis[$args[0]])] . "\n\n--{$msg->author}")->then(function ($result) use ($msg) {
                Utils::deleteMessage($msg);
            });
        } else {
            $allkaomojis = Utils::arrayFlatten($kaomojis);
            Utils::send($msg, $allkaomojis[array_rand($allkaomojis)] . "\n\n--{$msg->author}")->then(function ($result) use ($msg) {
                Utils::deleteMessage($msg);
            });
        }
    }

    public static function bamboozle($msg, $args)
    {
        $response = count($msg->mentions) > 0 ? implode(", ", array_keys($msg->mentions)) : $msg->author;
        $response .= ", you've been heccin' bamboozled again!!!!!!!!!!!!!!!!!!!!!!!!";
        Utils::sendFile($msg, 'img/bamboozled.jpg', 'bamboozle.jpg', $response)->then(function ($result) use ($msg) {
            Utils::deleteMessage($msg);
        });
    }

    public static function trap($msg, $args)
    {
        Utils::deleteMessage($msg);
        Utils::send($msg, "traps are gay")->then(function ($result) {
            self::$bot->loop->addTimer(5, function ($timer) use ($result) {
                Utils::editMessage($result, "traps aren't gay");
            });
        });
    }

}
