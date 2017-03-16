<?php

include __DIR__.'/definitions.php';
$imgs = new Definitions(__DIR__.'/img_urls.json');


foreach ($imgs->iter() as $key => $val) {
    echo "$key: $val", PHP_EOL;
    file_put_contents(__DIR__."/uploaded_images/$key", file_get_contents($val));
}
