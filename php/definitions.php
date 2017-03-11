<?php

class Definitions {
    protected $definitions;
    protected $filepath;

    public function __construct($filepath = __DIR__.'/definitions.json') {
        $this->filepath = $filepath;
        $this->definitions = json_decode(file_get_contents($filepath));
    }

    public function __toString() {
        return print_r($this->definitions, true);
    }

    public function get($key) {
        return $this->definitions->$key ?? "**not set**";
    }

    public function set($key, $val) {
        $this->definitions->$key = $val;
        $this->save();
    }

    public function unset($key) {
        unset($this->definitions->$key);
        $this->save();
    }

    public function list_keys() {
        return array_keys((array)$this->definitions);
    }

    public function save() {
        file_put_contents($this->filepath, json_encode($this->definitions));
    }
}
