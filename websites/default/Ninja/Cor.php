<?php

namespace Ninja;

abstract class Cor
{
    public $next;
    public function setNext($next)
    {
        $this->next = $next;
        return $this;
    }
    abstract protected function find(int $id);
    abstract protected function handle();
}
