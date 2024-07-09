<?php

namespace Ninja;

class Image
{
    ///had to explicitly set $quality on imac
    private $thumbres;
    private $thumbsize;
    private $route;
    private $id;
    private $orientation;

    public function __construct($res = 2, $size = 2)
    {
        $this->thumbres = $res;
        $this->thumbsize = $size;
    }

    public function doResolution($image, int $w = 0, int $h = 0)
    {
        if (!empty($w) && !empty($h)) {
            if (is_numeric($w) && is_numeric($h)) {
                imageresolution($image, $w, $h);
            }
        }
        return imageresolution($image);
    }

    public function setRoute($r, $id)
    {
        $this->route = $r;
        $this->id = $id;
    }


  

    public function thumbs($source_image, $filepath, $ratio = 1.5,  $offset = 0.5, $quality = 75, $max = 0)
    {
        $res = $this->thumbres ? $this->thumbres : $quality;
        $size = $this->thumbsize ? $this->thumbsize : max(90, $max);
        return $this->build($source_image, $filepath, $ratio, $offset, $res, $size);
    }

    private function doChmod($path, $permissions = 0777)
    {
        $route = $this->route;
        $id = $this->id ?? 0;
        try {
            if (file_exists($path)) {
                $pass = chmod($path, $permissions);
                if (!$pass) {
                    reLocate("$route/$id/true/copy", '../../');
                }
            } else {
                reLocate("$route/$id/true/missing", '../../');
            }
        } catch (\Exception $e) {
            reLocate("$route/$id/true/access", '../../');
        }
    }


    public function getDims($img)
    {
        $type = strtolower(substr(strrchr($img, "."), 1));
        if ($type === 'jpg' || $type === 'jpeg') {
            list($width, $height) = getjpegsize($img);
        } elseif ($type === 'png') {
            list($width, $height) = getpngsize($img);
        }
        //if above functions fail...
        if (!isset($width) || intval($width) === 0) {
            try {
                list($width, $height) = getimagesize($img);
            } catch (\Exception $e) {
                echo 'Caught exception: ',  $e->getMessage(), "\n";
            }
        }
        return [$width, $height, $type];
    }

    public function build($source_image, $filepath, $ratio = 0,  $offset = 0.5, $quality = 77, $max = 0, $degrees = 0)
    {
        $ext = strtolower(substr(strrchr($source_image, "."), 1));
        
        if (!file_exists($source_image)) {
            if ($ext === 'jpeg') {
                $source_image = preg_replace('/\.jpeg$/', '.jpg',  $source_image);
            } elseif ($ext === 'jpg') {
                $source_image = preg_replace('/\.jpg$/', '.jpeg',  $source_image);
            }
        }
        $quality = preg_match('/\.jpe?g$/', $source_image) ? $quality : null;
        list($width, $height, $type) = $this->getDims($source_image);
        $portrait = $height > $width ? true : false;
        $this->orientation = $portrait ? 'portrait' : 'landscape';
        $src_x = 0;
        $src_y = 0;
        $cropper = null;

        if (empty($ratio)) {
            $ratio = $portrait ? intval(($height / $width)) : intval(($width / $height));
        }
        //a default implementation cropping from center of pic
        else {
            $o = new CropperFactory($width, $height, $ratio, $offset, $portrait);
            $cropper = $o->cropper;
            $cropper->crop();
            $src_x = intval($cropper->src_x);
            $src_y = intval($cropper->src_y);
            $width = $cropper->width;
            $height = $cropper->height;
        }
        $width = intval($width);
        $height = intval($height);
        //sets $process and $image variables
        extract($this->getResource($type, $source_image));

        $newHeight = intval($height);
        $newWidth = intval($width);

        if ($portrait) {
            if (!empty($max) && $max < $newHeight) {
                $newWidth = intval(($width / $height) * $max);
                $newHeight = intval($max);
            }
        } else if (!empty($max) && $max < $newWidth) {
            $newHeight = intval(($height / $width) * $max);
            $newWidth = intval($max);
        }
        $newResource = imagecreatetruecolor($newWidth, $newHeight);
        //https://stackoverflow.com/questions/6382448/png-transparency-resize-with-simpleimage-php-class
        if ($ext == 'png') {
            imagealphablending($newResource, false);
            imagesavealpha($newResource, true);
        }

        imagecopyresampled($newResource, $image, 0, 0, $src_x, $src_y, $newWidth, $newHeight, $width, $height);
        if (!isset($process)) {
            trigger_error("Unhandled or unknown image type ($type)", E_USER_ERROR);
        }

       // if($degrees){
        $newResource = imagerotate($newResource, $degrees, 0);
       // }
        if ($quality) {
            $process($newResource, $filepath, $quality);
        } else {
            $process($newResource, $filepath);
        }
      
        // Output
        
        /*Free the memory
        imagedestroy($source);
        imagedestroy($rotate);
        */


        $this->doChmod($filepath);
        //return imageresolution($newResource);
    }
    //found this after mostly trial and error coming up with my own version
    //https://gist.github.com/miguelfrmn/908143/840453041281b084ed0ad7d305d4b72935b1c6db


    public function getResource($type, $source_image)
    {
        //chmod($source_image, 0755);
        switch ($type) {
            case 'gif':
                $imageResource = imagecreatefromgif($source_image);
                $quality = null;
                $process = deco('imagegif');
                break;
            case 'jpg':
                $imageResource = imagecreatefromjpeg($source_image);
                $process = deco('imagejpeg');
                break;
            case 'jpeg':
                $imageResource = imagecreatefromjpeg($source_image);
                $process = deco('imagejpeg');
                break;
            case 'png':
                $imageResource = imagecreatefrompng($source_image);
                $quality = null;
                $process = deco('imagepng');
                break;
            default:
                trigger_error("Unhandled or unknown image type ($type)", E_USER_ERROR);
        }
        return ['process' => $process, 'image' => $imageResource];
    }
}
