<?php

include __DIR__.'/definitions.php';

$cities = new Definitions(__DIR__.'/cities.json');
$api_key = file_get_contents(__DIR__.'/weather_api_key');
$url = "http://api.openweathermap.org/data/2.5/weather?APPID=$api_key&units=metric&";


foreach ($cities->iter() as $user => $city) {
    $newurl = "{$url}id={$city}";
    $json = json_decode(file_get_contents($newurl));
    $cities->set($user, [
        'id'   => $json->id,
        'lat'  => $json->coord->lat,
        'lon'  => $json->coord->lon,
        'city' => $json->name,
    ]);
}
