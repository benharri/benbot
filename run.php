#!/usr/bin/env php
<?php

ini_set('display_errors', true);
error_reporting(-1);

$procname = $argv[1] ?? "BenBot";

if (!cli_set_process_title($procname)) {
    die("couldn't set process title");
}

require_once __DIR__.'/vendor/autoload.php';

$benbot = new BenBot\BenBot(__DIR__);

$benbot->run();
