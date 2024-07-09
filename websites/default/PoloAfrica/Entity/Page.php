<?php

namespace PoloAfrica\Entity;

class Page
{
    public $id;
    public $name;
    public $title;
    public $content;
    public $description;


    public function __construct(private \Ninja\DatabaseTable $slotTable)
    {
    }
  
    public function findAll(...$args)
    {
        return $this->slotTable->findAll(...$args);
    }

    public function find($id)
    {
        $ret = $this->slotTable->find('id', $id, null, 0, 0, \PDO::FETCH_ASSOC);
        return $ret ? $ret[0] : $ret;
    }

    public function setName($name)
    {
        return $this->slotTable->setName($name);
    }

    public function getSlotEntity()
    {
        return $this->slotTable->getEntity();
    }

    public function repop($data = [], $flag = false)
    {
        $slot = $this->getSlotEntity();
        $slot->repop($data, $flag);
    }

    public function swap(...$args)
    {
        $slot = $this->getSlotEntity();
        return $slot->swap(...$args);
    }

    public function shuffle(...$args)
    {
        $slot = $this->getSlotEntity();
        return $slot->shuffle(...$args);
    }
}
