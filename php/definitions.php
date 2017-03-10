<?php

class Definitions {
    protected var $definitions;
    protected var $filepath;

    public function __construct($filepath = __DIR__.'/definitions.json') {
        $this->filepath = $filepath;
        $this->definitions = json_decode(file_get_contents($filepath));
    }

    public function __toString() {
        return print_r($this->definitions, true);
    }

    public function get($key) {
        return $this->definitions[$key];
    }

    public function set($key, $val) {
        $this->definitions[$key] = $val;
        $this->save();
    }

    public function unset($key) {
        unset($this->definitions[$key]);
        $this->save();
    }

    public function save() {
        file_put_contents($this->filepath, json_encode($this->definitions));
    }
}
