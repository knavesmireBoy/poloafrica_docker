<?php

namespace Ninja;

class Pkg
{
    public function __construct(private \Ninja\Mediator $mediator, public int $address,  public string $type, public int $id)
    {
        $this->id = $id;
        $this->address = $address;
        $this->type = $type;
    }

    private function validate($boxid)
    {
        return $boxid < $this->address;
    }

    public function update($boxid)
    {
        if ($this->address) {
            $i = intval($boxid);
            $j = $this->address;
            $this->setAddress($i);
            if ($this->validate($boxid)) {
                $this->mediator->notify($this, 'update', null);
            } else {
                $this->mediator->notify($this, 'update', $j);
            }
            return $this;
        }
        return null;
    }
    public function setAddress(int $arg)
    {
        $this->address = $arg;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function get($prop = '')
    {
        $myprop = $prop && isset($this->{$prop});
        return $myprop ? $this->{$prop} : $this;
    }

    static function from(\Ninja\Mediator $mediator, $data)
    {
        $ret = [];
        foreach ($data as $d) {
            try {
                $ret[] = new \Ninja\Pkg($mediator, ...$d);
            } catch (\Exception $e) {
            }
        }
        return $ret;
    }
}
