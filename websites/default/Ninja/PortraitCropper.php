<?php

namespace Ninja;

class PortraitCropper
{
    public $width;
    public $height;
    public $offset;
    public $src_x = 0;
    public $src_y = 0;
    protected $ratio;

    public function __construct($width, $height, $ratio, $offset)
    {
        $this->width = $width;
        $this->height = $height;
        $this->ratio = $ratio;
        $this->offset = $offset;
    }
    private function calc($old, $new, $fr = 0.5)
    {
        return ($old - $new) * $fr;
    }
    public function crop()
    {
        $res = $this->height / $this->width;
        //h too big crop top/bottom
        if (greaterThan($res, $this->ratio)) {
            $target_height = $this->width * $this->ratio;
            $this->src_y = $this->calc($this->height, $target_height, $this->offset);
            $this->height = $target_height;
        }
        //w too big crop sides
        if (lesserThan($res, $this->ratio)) {
            $target_width = $this->height / $this->ratio;
            $this->src_x = $this->calc($this->width, $target_width, $this->offset);
            $this->width = $target_width;
        }
    }
}
