<?php
namespace BenBot;

mb_internal_encoding("UTF-8");

class UnicodeFontConverter {

    public static function __callStatic($name, $args)
    {
        $fonts = [
            'full' => [
                'lowers' => 'ａｂｃｄｅｆｇｈｉｊｋｌｍｎｏｐｑｒｓｔｕｖｗｘｙｚ',
                'uppers' => 'ＡＢＣＤＥＦＧＨＩＪＫＬＭＮＯＰＱＲＳＴＵＶＷＸＹＺ',
                'nums' => '０１２３４５６７８９',
            ],
            'mono' => [
                'lowers' => '𝚊𝚋𝚌𝚍𝚎𝚏𝚐𝚑𝚒𝚓𝚔𝚕𝚖𝚗𝚘𝚙𝚚𝚛𝚜𝚝𝚞𝚟𝚠𝚡𝚢𝚣',
                'uppers' => '𝙰𝙱𝙲𝙳𝙴𝙵𝙶𝙷𝙸𝙹𝙺𝙻𝙼𝙽𝙾𝙿𝚀𝚁𝚂𝚃𝚄𝚅𝚆𝚇𝚈𝚉',
                'nums' => '𝟶𝟷𝟸𝟹𝟺𝟻𝟼𝟽𝟾𝟿',
            ],
            'flipped' => [
                'lowers' => 'ɐqɔpǝɟƃɥıɾʞןɯuodbɹsʇnʌʍxʎz',
                'uppers' => 'ɐqɔpǝɟƃɥıɾʞןɯuodbɹsʇn𐌡ʍxʎz',
                'nums' => '0123456789',
            ],
            'reversed' => [
                'lowers' => 'AdↄbɘꟻgHijklmᴎoqpᴙꙅTUvwxYz',
                'uppers' => 'AdↃbƎꟻGHIJK⅃MᴎOꟼpᴙꙄTUVWXYZ',
                'nums' => '0߁23456789',
            ],
            'cyrillic' => [
                'lowers' => 'αв¢∂єƒﻭнιנкℓмησρ۹яѕтυνωχуչ',
                'uppers' => 'αв¢∂єƒﻭнιנкℓмησρ۹яѕтυνωχуչ',
                'nums' => '0123456789',
            ],
            'slashed' => [
                'lowers' => 'Ⱥƀȼđɇfǥħɨɉꝁłmnøᵽꝗɍsŧᵾvwxɏƶ',
                'uppers' => 'ȺɃȻĐɆFǤĦƗɈꝀŁMNØⱣꝖɌSŦᵾVWXɎƵ',
                'nums' => '01ƻ3456789',
            ],
            'script' => [
                'lowers' => '𝓪𝓫𝓬𝓭𝓮𝓯𝓰𝓱𝓲𝓳𝓴𝓵𝓶𝓷𝓸𝓹𝓺𝓻𝓼𝓽𝓾𝓿𝔀𝔁𝔂𝔃',
                'uppers' => '𝓐𝓑𝓒𝓓𝓔𝓕𝓖𝓗𝓘𝓙𝓚𝓛𝓜𝓝𝓞𝓟𝓠𝓡𝓢𝓣𝓤𝓥𝓦𝓧𝓨𝓩',
                'nums' => '𝟎𝟏𝟐𝟑𝟒𝟓𝟔𝟕𝟖𝟗',
            ],
            'gothic' => [
                'lowers' => '𝖆𝖇𝖈𝖉𝖊𝖋𝖌𝖍𝖎𝖏𝖐𝖑𝖒𝖓𝖔𝖕𝖖𝖗𝖘𝖙𝖚𝖛𝖜𝖝𝖞𝖟',
                'uppers' => '𝕬𝕭𝕮𝕯𝕰𝕱𝕲𝕳𝕴𝕵𝕶𝕷𝕸𝕹𝕺𝕻𝕼𝕽𝕾𝕿𝖀𝖁𝖂𝖃𝖄𝖅',
                'nums' => '𝟘𝟙𝟚𝟛𝟜𝟝𝟞𝟟𝟠𝟡',
            ],
        ];
        if (!isset($fonts[$name])) {
            $ret = "sorry that font doesn't exist. try these fonts:\n";
            $ret .= implode(", ", array_keys($fonts));
            return $ret;
        }

        $string = implode(" ", $args);
        $ret = "";
        foreach (self::charIn($string) as $char) {
            $ord = ord($char);
            if ($ord >= ord('0') && $ord <= ord('9')) {
                $ret .= mb_substr($fonts[$name]["nums"], $ord - ord('0'), 1);
            } elseif ($ord >= ord('a') && $ord <= ord('z')) {
                $ret .= mb_substr($fonts[$name]["lowers"], $ord - ord('a'), 1);
            } elseif ($ord >= ord('A') && $ord <= ord('Z')) {
                $ret .= mb_substr($fonts[$name]["uppers"], $ord - ord('A'), 1);
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