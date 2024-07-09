<?php

namespace Ninja;

class Postbox
{
    public function __construct(private $rev = false, private $path = 'id', private array $store = [])
    {
        $this->store = $store;
    }
    public function add(\Ninja\Pkg $pkg)
    {
        $this->store[] = $pkg;
    }
    public function exit($flag = false)
    {
        if ($flag) {
            $this->store = [];
        } else {
            $last = end($this->store);
            if ($this->rev) {
                $last->address--;
            } else {
                $last->address++;
            }
        }
    }
    public function query($flag = false)
    {
        if ($flag) {
           return $this->store[0];
        }
        return  end($this->store) ?? null;
    }
    public function visited(\Ninja\Pkg $item)
    {
        $grp = array_map(function ($el) {
            return $el->{$this->path};
        }, $this->store);
        return in_array($item->get('id'), $grp);
    }
}
//covid19@krauq