<?php

require_once __DIR__.'/vendor/autoload.php';

use BenBot\BenBot;

$benbot = new BenBot(__DIR__);

$benbot->run();
