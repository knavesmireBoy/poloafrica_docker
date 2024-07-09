<?php

namespace Ninja\Composite;

use \Ninja\Component;

include_once 'config.php';
include_once FUNCTIONS;

class Composite implements Component
{
    protected $myItems = [];

    public function __construct()
    {
    }

    public function addItem(Component $com)
    {
        if (!in_array($com, $this->myItems)) {
            array_push($this->myItems, $com);
        }
    }

    public function removeItem(Component $com)
    {
        //Reserved for code to remove component
    }

    public function getItem($i = 0)
    {
        if (!empty($i)) {
            return $this->myItems[$i];
        }
        return $this->myItems;
    }
}
