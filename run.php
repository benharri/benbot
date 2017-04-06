<?php

if (!cli_set_process_title("BenBot")) {
    die("couldn't set process title");
}


require_once __DIR__.'/vendor/autoload.php';

use BenBot\BenBot;

$benbot = new BenBot(__DIR__);

$benbot->run();
