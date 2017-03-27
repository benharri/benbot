<?php

include __DIR__.'/vendor/autoload.php';

class SerializedArray {

    protected $data;
    protected $filepath;

    public function __construct($filepath)
    {
        $this->filepath = $filepath;
        if (!is_file($this->filepath)) throw new Exception("Invalid filepath");
        $rawfiledata = file_get_contents($this->filepath);
        if (strlen($rawfiledata) > 3) {
            $this->data = (new MessagePack\Unpacker())->unpack($rawfiledata);
        }
    }

    public function __toString()
    {
        return print_r($this->data, true);
    }

    public function get($key, $nullable = false)
    {
        return $this->data[$key] ?? ($nullable ? false : "**not set**");
    }

    public function set($key, $val)
    {
        $this->data[$key] = $val;
        $this->save();
    }

    public function unset($key)
    {
        unset($this->data[$key]);
        $this->save();
    }

    public function getKeys()
    {
        return array_keys($this->data);
    }

    private function save()
    {
        file_put_contents($this->filepath, (new MessagePack\Packer())->packMap($this->data));
    }

    public function print()
    {
        $ret = [];
        foreach ($this->data as $key => $val) {
            $ret[] = $key . ": " . $val;
        }
        return implode(", ", $ret);
    }

    public function iter()
    {
        foreach ($this->data as $key => $val) {
            yield $key => $val;
        }
    }


}
