<?php

namespace Ninja;

class Shuffle extends Box
{
    public function update(\Ninja\Pkg $pkg)
    {
        $next = $this->find($pkg->address);
        $next->handle();
    }

    protected function prepareNext()
    {
        $this->setAddress($this->id + 1);
    }

    protected function skip()
    {
        $this->notify('skipped');
    }
}
