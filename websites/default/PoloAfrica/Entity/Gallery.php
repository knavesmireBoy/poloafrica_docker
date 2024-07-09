<?php

namespace PoloAfrica\Entity;

class Gallery
{
    public $id;
    public $path;
    public $alt;
    public $date;
    public $orient;
    public $box;

    public function __construct(private \Ninja\DatabaseTable $boxtable)
    {
    }
    private function build($shuffle, $rev)
    {
        /*if a slot record is missing a pic_id, errors will be thrown
        we have to ASSUME that each slot has a valid resident before all the shuffling malarkey
        */
        $validatePicId = function ($last, $next) {
            if (isset($last['pic_id']) && isset($next['pic_id'])) {
                return $next;
            }
        };
        $mediator = new \Ninja\Mediator(new \Ninja\Postbox($rev));
        $data = $this->boxtable->findAll(null, 0, 0, \PDO::FETCH_ASSOC);
        $pass = array_reduce($data, $validatePicId, $data[0]);

        if ($pass) {
            $data = array_map('array_values', $data);
            $rawboxes = array_map(function ($item) {
                return $item[0];
            }, $data);
            // $rawboxes = $rev && $shuffle ? array_reverse($rawboxes) : $rawboxes;
            $boxes = \Ninja\Box::from($rawboxes, $mediator, $shuffle, $rev);
            $mediator->setComponent($boxes[0]);
            $packages = \Ninja\Pkg::from($mediator, $data);
            return \Ninja\Box::build($boxes, $packages);
        }
        return [];
    }

    public function reAssign($slotid, $orient, $shuffle = false)
    {
        if (!$slotid) {
            return;
        }
        $i = intval($slotid);
        $current = $this->getSlot(true); //pic is assigned to a slot

        if (empty($current)) {
            $this->boxtable->save(['id' => $i, 'orient' => $orient, 'pic_id' => $this->id]);
            return;
        }
        $rev = $current->id < $i;
        //default to swap if no changes
        $shuffle = $shuffle && $current->id !== $i;
        $boxes = $this->build($shuffle, $rev);
        if (!empty($boxes)) {
            $boxes[$current->id - 1]->getPackage()->update($i);
            $grp = $boxes[0]->collect(true);

            foreach ($grp as $g) {
                list($id, $pic) = $g;
                $this->boxtable->save(['id' => $id, 'pic_id' => $pic]);
            }
        }
    }
    //instead of joining boxtables
    public function orderById($data)
    {
        //return $data; //uncomment this line if problems
        $L = count($data);
        $addr = [];
        $tmp = [];
        foreach ($data as $d) {
            $found = $this->boxtable->find('pic_id', $d->id);
            $tmp['id'] = $d->id; //template expects id to be id of pic NOT box column
            $tmp['alt'] = $d->alt;
            $tmp['path'] = $d->path;
            if (!empty($found)) {
                $tmp['sorter'] = $found[0]->id;
            } else {
                $tmp['sorter'] = $L++;
            }
            $addr[] = (object) $tmp;
            $tmp = [];
        }
        //https://stackoverflow.com/questions/1597736/sort-an-array-of-associative-arrays-by-column-value
        $x = array_column($addr, 'sorter');
        array_multisort($x, SORT_ASC, $addr);
        return $addr;
    }

    public function getSlot($flag = false)
    {
        $ret = $this->boxtable->find('pic_id', $this->id);
        $flag = $flag && isset($ret[0]);
        return $flag ? $ret[0] : null;
    }
    public function getNext($id)
    {
        $ret = $this->boxtable->find('id', $id, null, 1, 0, \PDO::FETCH_CLASS, ' > :value');
        if (!isset($ret[0])) {
            $ret = $this->boxtable->find('id', 1);
        }
        return $ret[0]->pic_id;
    }
    public function getCurrent($id)
    {
        $ret = $this->boxtable->find('id', $id, null, 1, 0);
        if (!isset($ret[0])) {
            $ret = $this->boxtable->find('id', 1);
        }
        return $ret[0]->pic_id;
    }
    public function getPrev($id)
    {
        //find all less than
        $ret = $this->boxtable->find('id', $id, null, 0, 0, \PDO::FETCH_ASSOC, ' < :value');
        if (!isset($ret[0])) {
            $ret = $this->boxtable->find('id', count($this->boxtable->findAll()));
        } else {
            $ret = array_reverse($ret)[0];
            $ret = $this->boxtable->find('id', $ret['id']);
        }
        return $ret[0]->pic_id;
    }

    public function getStatus($flag = false) {
       $ids = array_map(fn($o) => $o->pic_id, $this->boxtable->findAll());
       return $flag ? in_array($this->id, $ids) : !in_array($this->id, $ids);
    }
}
