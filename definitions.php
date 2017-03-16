<?php

class Definitions {
    protected $defs;
    protected $filepath;

    public function __construct($filepath = __DIR__.'/definitions.json') {
        $this->filepath = $filepath;
        $this->defs = json_decode(file_get_contents($filepath), true);
    }

    public function __toString() {
        return print_r($this->defs, true);
    }

    public function get($key, $nullable = false) {
        return $this->defs[$key] ?? ($nullable ? false : "**not set**");
    }

    public function set($key, $val) {
        $this->defs[$key] = $val;
        $this->save();
    }

    public function unset($key) {
        unset($this->defs[$key]);
        $this->save();
    }

    public function list_keys() {
        return array_keys($this->defs);
    }

    public function save() {
        file_put_contents($this->filepath, json_encode($this->defs));
    }

    public function print() {
        $ret = [];
        foreach ($this->defs as $key => $val)
            $ret[] = $key . ": " . $val;
        return implode(", ", $ret);
    }

    public function iter() {
        foreach ($this->defs as $key => $val)
            yield $key => $val;
    }


}
