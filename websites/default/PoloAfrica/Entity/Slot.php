<?php

namespace PoloAfrica\Entity;

class Slot
{
    public $id;
    public $title;

    public function __construct(private \Ninja\DatabaseTable $table)
    {
    }

    public function setName($name)
    {
        return $this->table->setName($name);
    }

    public function getName()
    {
        return $this->table->getName();
    }

    public function findAll($arg = 'id')
    {
        return $this->table->findAll($arg);
    }

    public function find($id)
    {
        return $this->table->find('id', $id);
    }

    public function delete($pp)
    {
        return $this->table->delete('title', $pp);
    }

    public function trigger($data)
    {
        foreach ($data as $d) {
            if(!empty($d)){
                $this->table->save(['title' => $d]);
            }
        }
    }

    public function repop($data, $flag = false)
    {
        if (!empty($data) || $flag) {
            $data = array_filter($data, fn($o) => $o);
            $this->table->truncate();
            $this->trigger($data);
        }
    }

    public function swap(int $destinationID, string $label)
    {
        $places = array_map(fn ($o) => $o->title, $this->findAll('id'));
        $locationID = array_search($label, $places);
        $places[$locationID] = $places[$destinationID];
        $places[$destinationID] = $label;
        $this->repop($places);
        return true;
    }

    public function shuffle(int $destinationID, string $label)
    {
        $places = array_map(fn ($o) => $o->title, $this->findAll('id'));
        $locationID = array_search($label, $places);
        $van = [$label];
        $places[$locationID] = null;
        $res = false;
        
        if ($locationID != $destinationID) {
            $res = true;
            $swap_already = abs($locationID - $destinationID);
            //could just use $swap_already (if at least 1) but shuffling is a trifle more expensive than simply swapping
            if ($swap_already > 1) {
                if ($locationID > $destinationID) {
                    drive(false, $van, $places, $destinationID);
                } else {
                    $places = array_reverse($places);
                    $destinationID = count($places) - $destinationID - 1;
                    drive(false, $van, $places, $destinationID);
                    $places = array_reverse($places);
                }
            }
            else {
               return $this->swap($destinationID, $label);
            }
            $this->repop($places);
        }
        return $res;
    }
}
