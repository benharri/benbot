<?php
namespace BenBot;
error_reporting(-1);

use BenBot\Utils;
use function Stringy\create as s;

class FontConverter {

    public static function blockText($text) {
        $ret = "";
        foreach (s($text)->toLowerCase() as $char) {
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
        return $ret;
    }

    public static function __callStatic($name, $args)
    {
        $fonts = [
            'full' => [
                'lowers' => s('ａｂｃｄｅｆｇｈｉｊｋｌｍｎｏｐｑｒｓｔｕｖｗｘｙｚ'),
                'uppers' => s('ＡＢＣＤＥＦＧＨＩＪＫＬＭＮＯＰＱＲＳＴＵＶＷＸＹＺ'),
                'nums' => s('０１２３４５６７８９'),
            ],
            'mono' => [
                'lowers' => s('𝚊𝚋𝚌𝚍𝚎𝚏𝚐𝚑𝚒𝚓𝚔𝚕𝚖𝚗𝚘𝚙𝚚𝚛𝚜𝚝𝚞𝚟𝚠𝚡𝚢𝚣'),
                'uppers' => s('𝙰𝙱𝙲𝙳𝙴𝙵𝙶𝙷𝙸𝙹𝙺𝙻𝙼𝙽𝙾𝙿𝚀𝚁𝚂𝚃𝚄𝚅𝚆𝚇𝚈𝚉'),
                'nums' => s('𝟶𝟷𝟸𝟹𝟺𝟻𝟼𝟽𝟾𝟿'),
            ],
            'flipped' => [
                'lowers' => s('ɐqɔpǝɟƃɥıɾʞןɯuodbɹsʇnʌʍxʎz'),
                'uppers' => s('ɐqɔpǝɟƃɥıɾʞןɯuodbɹsʇn𐌡ʍxʎz'),
                'nums' => s('0123456789'),
            ],
            'reversed' => [
                'lowers' => s('AdↄbɘꟻgHijklmᴎoqpᴙꙅTUvwxYz'),
                'uppers' => s('AdↃbƎꟻGHIJK⅃MᴎOꟼpᴙꙄTUVWXYZ'),
                'nums' => s('0߁23456789'),
            ],
            'cyrillic' => [
                'lowers' => s('αв¢∂єƒﻭнιנкℓмησρ۹яѕтυνωχуչ'),
                'uppers' => s('αв¢∂єƒﻭнιנкℓмησρ۹яѕтυνωχуչ'),
                'nums' => s('0123456789'),
            ],
            'slashed' => [
                'lowers' => s('Ⱥƀȼđɇfǥħɨɉꝁłmnøᵽꝗɍsŧᵾvwxɏƶ'),
                'uppers' => s('ȺɃȻĐɆFǤĦƗɈꝀŁMNØⱣꝖɌSŦᵾVWXɎƵ'),
                'nums' => s('01ƻ3456789'),
            ],
            'script' => [
                'lowers' => s('𝓪𝓫𝓬𝓭𝓮𝓯𝓰𝓱𝓲𝓳𝓴𝓵𝓶𝓷𝓸𝓹𝓺𝓻𝓼𝓽𝓾𝓿𝔀𝔁𝔂𝔃'),
                'uppers' => s('𝓐𝓑𝓒𝓓𝓔𝓕𝓖𝓗𝓘𝓙𝓚𝓛𝓜𝓝𝓞𝓟𝓠𝓡𝓢𝓣𝓤𝓥𝓦𝓧𝓨𝓩'),
                'nums' => s('𝟎𝟏𝟐𝟑𝟒𝟓𝟔𝟕𝟖𝟗'),
            ],
            'gothic' => [
                'lowers' => s('𝖆𝖇𝖈𝖉𝖊𝖋𝖌𝖍𝖎𝖏𝖐𝖑𝖒𝖓𝖔𝖕𝖖𝖗𝖘𝖙𝖚𝖛𝖜𝖝𝖞𝖟'),
                'uppers' => s('𝕬𝕭𝕮𝕯𝕰𝕱𝕲𝕳𝕴𝕵𝕶𝕷𝕸𝕹𝕺𝕻𝕼𝕽𝕾𝕿𝖀𝖁𝖂𝖃𝖄𝖅'),
                'nums' => s('𝟘𝟙𝟚𝟛𝟜𝟝𝟞𝟟𝟠𝟡'),
            ],
            'vaporwave' => [
                'lowers' => s('ａｂｃｄｅｆｇｈｉｊｋｌｍｎｏｐｑｒｓｔｕｖｗｘｙｚ'),
                'uppers' => s('ＡＢＣＤＥＦＧＨＩＪＫＬＭＮＯＰＱＲＳＴＵＶＷＸＹＺ'),
                'nums' => s('０１２３４５６７８９'),
            ],
        ];
        if (!isset($fonts[$name])) {
            $ret = "sorry that font doesn't exist. try these fonts:\n";
            $ret .= implode(", ", array_keys($fonts));
            return $ret;
        }

        $ret = "";
        foreach (s($args[0]) as $char) {
            $ord = ord($char);
            if ($ord >= ord('0') && $ord <= ord('9')) {
                $ret .= $fonts[$name]["nums"][$ord - ord('0')];
            } elseif ($ord >= ord('a') && $ord <= ord('z')) {
                $ret .= $fonts[$name]["lowers"][$ord - ord('a')];
            } elseif ($ord >= ord('A') && $ord <= ord('Z')) {
                $ret .= $fonts[$name]["uppers"][$ord - ord('A')];
            } elseif ($char == " ") {
                $ret .= " ";
            } else {
                continue;
            }
            $ret .= " ";
        }
        return $ret;
    }




}