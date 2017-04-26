<?php
namespace BenBot;

class PersistentArray implements \ArrayAccess, \Iterator
{

    protected $data;
    protected $filepath;

    public function __construct($filepath)
    {
        $this->filepath = $filepath;
        if (!is_file($this->filepath)) throw new Exception("Invalid filepath");
        $rawfiledata = file_get_contents($this->filepath);
        if (strlen($rawfiledata) > 3) {
            $this->data = msgpack_unpack($rawfiledata);
        }
    }


    public function __debugInfo()
    {
        return print_r($this->data, true);
    }


    public function __call($func, $argv)
    {
        if (!is_callable($func) || substr($func, 0, 6) !== 'array_') {
            throw new \BadMethodCallException(__CLASS__ . "->$func");
        }
        $copy = $this->data;
        return call_user_func_array($func, array_merge([$copy], $argv));
    }


    // array access methods
    public function offsetGet($offset)
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
        $this->save();
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
        $this->save();
    }

    // iterator methods
    public function rewind()
    {
        return reset($this->data);
    }
    public function current()
    {
        return current($this->data);
    }
    public function key()
    {
        return key($this->data);
    }
    public function next()
    {
        return next($this->data);
    }
    public function valid()
    {
        return key($this->data) !== null;
    }




    private function save()
    {
        file_put_contents($this->filepath, msgpack_pack($this->data));
    }


}
