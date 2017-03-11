<?php

function char_in_str($str) {
    for ($i = 0; $i <= strlen($str); $i++)
        yield substr($str, $i, 1);
}

function send($msg, $txt) {
    $msg->channel->sendMessage($txt);
}



function ascii_from_img($filepath) {
    $ret = "";
    $img = imagecreatefromstring(file_get_contents($filepath));
    list($width, $height) = getimagesize($filepath);
    $scale = 10;
    $chars = [
        ' ', '\'', '.', ':',
        '|', 'T',  'X', '0',
        '#',
    ];
    $chars = array_reverse($chars);
    $c_count = count($chars);
    for($y = 0; $y <= $height - $scale - 1; $y += $scale) {
        for($x = 0; $x <= $width - ($scale / 2) - 1; $x += ($scale / 2)) {
            $rgb = imagecolorat($img, $x, $y);
            $r = (($rgb >> 16) & 0xFF);
            $g = (($rgb >> 8) & 0xFF);
            $b = ($rgb & 0xFF);
            $sat = ($r + $g + $b) / (255 * 3);
            $ret .= $chars[ (int)( $sat * ($c_count - 1) ) ];
        }
        $ret .= "\n";
    }
    return $ret;
}





