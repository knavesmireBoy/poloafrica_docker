<?php

namespace Ninja;

class RevShuffle extends Box
{
    public function update(\Ninja\Pkg $pkg, $prev_addr)
    {
        $next = $this->find($prev_addr);
        $next->handle($pkg->address);
    }

    private function validate($comp)
    {
        $stored = $comp->getPackage();
        if ($this->notify('validate', $stored->type)) {
            return $stored;
        }
        return null;
    }

    protected function handle($tgt_addr = 0)
    {
        $stored = $this->notify('query');
        $pass = $this->notify('validate', $this->getPackage('type'));

        if ($pass) {
            if ($this->id === $tgt_addr) {
                $stored = $this->notify('query', true);
                $this->set($stored);
                $this->next = null;
            } else {
                $comp = $this;
                while (!$stored = $this->validate($comp->next)) {
                    $comp = $comp->next;
                }
                $this->post($stored);
            }
        } else {
            $this->skip();
        }

        if ($this->next) {
            $this->next->handle($tgt_addr);
        } else {
            $this->exit();
        }
    }

    protected function prepareNext()
    {
        $this->setAddress($this->id + 1);
    }

    protected function skip()
    {
        $this->notify('skipped');
    }

    protected function exit()
    {
        //should never need to tear down the state (of mediator and postbox) as fresh instances are created on demand
        $this->notify($this, 'exit');
    }
}
