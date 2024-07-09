<?php

namespace Ninja;

include_once 'config.php';
include_once FUNCTIONS;

class Mediator
{
    private $box;
    //do NOT set initial value for $type
    private $type;
    public function __construct(private \Ninja\Postbox $postbox)
    {
    }

    public function notify($comp, $event, $data)
    {

        if ($comp instanceof \Ninja\Pkg) {
            if ($event === 'update') {
                if (!isset($this->type)) {
                    $this->type = $comp->type;
                }
                $this->postbox->add($comp);
                $this->box->update($comp, $data);
            }
            /*
            if ($event === 'reverse') {
                if (equals(...$data)) {
                    return;
                }
                list($resident_id, $pkg_address) = $data;
                //find box by pkg-address box->update
                //box17->pkg->update(15) needs to be box15->pkg->update(17)
                //otherwise when 17 is reached we are at end of loop
                //would have to re-iterate, probably with a flag
                $box = $this->box->find($pkg_address);
                $box->getPackage()->update($resident_id);
            }
            */
        }
        if ($comp instanceof \Ninja\Box) {

            if ($event === 'add') {
                if (!$this->postbox->visited($data)) {
                    $this->postbox->add($data);
                }
            }
            if ($event === 'exit') {
                $this->postbox->exit(true);
            }
            if ($event === 'query') {
                return $this->postbox->query($data);
            }

            if ($event === 'skipped') {
                $this->postbox->exit();
            }

            if ($event === 'stored') {
                return $this->postbox->query($data);
            }

            if ($event === 'validate') {
                return $this->checktype($data);
            }
        }
    }

    public function setComponent(\Ninja\Box $box)
    {
        $this->box = $box;
    }
    public function checktype($type)
    {
        return $this->type === $type;
    }
}
