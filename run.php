<?php

ini_set('display_errors', true);
error_reporting(-1);


if (!cli_set_process_title("BenBot")) {
    die("couldn't set process title");
}

require_once __DIR__.'/vendor/autoload.php';


$benbot = new BenBot\BenBot(__DIR__);

$benbot->run();
