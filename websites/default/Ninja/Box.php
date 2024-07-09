<?php

namespace Ninja;

include_once 'config.php';
include_once FUNCTIONS;

class Box extends Cor
{
    public $pkg;
    /* IT IS ASSUMED THAT BOX KNOWS THE INTERFACE OF PKG */
    public function __construct(private \Ninja\Mediator $mediator, public int $id)
    {
        $this->id = $id;
    }

    protected function setAddress(int $id)
    {
        $this->pkg->setAddress($id);
    }

    protected function handle($prev_addr = 0)
    {
        $stored = $this->notify('query');
        $pass = $this->notify('validate', $this->getPackage('type'));
        if ($pass) {
            if ($this->id === $stored->address) {
                $this->post($stored);
            }
        } else {
            $this->skip();
        }

        if ($this->next) {
            $this->next->handle();
        } else {
            $this->exit();
        }
    }

    public function set(\Ninja\Pkg $pkg)
    {
        $this->pkg = $pkg;
        $this->setAddress($this->id);
        return $this;
    }

    public function getPackage($prop = '')
    {
        return $this->pkg->get($prop);
    }

    public function find(int $id)
    {
        if (!$this->next || $id == $this->id) {
            return $this;
        }
        return $this->next->find($id);
    }

    public function findPackage(int $id)
    {
        if ($id == $this->getPackage('address')) {
            return $this->getPackage();
        }
        return $this->next->findPackage($id);
    }

    protected function prepareNext()
    {
        $this->setAddress($this->id);
    }

    protected function skip()
    {
    }

    protected function exit()
    {
        //should never need to tear down the state (of mediator and postbox) as fresh instances are created on demand
        $this->notify($this, 'exit');
    }

    protected function notify($event, $data = null)
    {
        return $this->mediator->notify($this, $event, $data);
    }

    protected function post($comp)
    {
        $this->prepareNext();
        $this->notify('add', $this->pkg);
        $this->set($comp);
    }

    //MUST be invoked on first instance head of the chain to return all members
    public function collect($pairs = false)
    {
        $that = $this;
        $arr = [$that]; //set first in chain here
        while ($that->next) {
            $that = $that->next;
            $arr[] = $that;
        }
        if ($pairs) { //$pairs for mapping to database
            //ie  // "UPDATE `box` SET `pic_id` = $arr[1] WHERE `id` = $arr[0]";
            $arr = array_map(
                function ($box) {
                    return [$box->id, $box->getPackage('id')];
                },
                $arr
            );
        }
        return $arr;
    }

    static function from(array $ids, \Ninja\Mediator $mediator, $shuffle = false, $rev = false)
    {
        $ret = [];
        foreach ($ids as $i) {
            if ($shuffle) {
                if ($rev) {
                    $ret[] = new \Ninja\RevShuffle($mediator, $i);
                } else {
                    $ret[] = new \Ninja\Shuffle($mediator, $i);
                }
            } else {
                $ret[] = new \Ninja\Swap($mediator, $i);
            }
        }
        return $ret;
    }

    static function build($boxes, $packages)
    {
        $L = count($boxes);
        for ($i = 0; $i < $L; $i++) {
            if (isset($boxes[$i + 1])) {
                $boxes[$i]->setNext($boxes[$i + 1])->set($packages[$i]);
            } else {
                $boxes[$i]->set($packages[$i]);
            }
        }
        return $boxes;
    }
}
