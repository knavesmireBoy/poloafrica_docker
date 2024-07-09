<?php

namespace Ninja\Loader;

use \PoloAfrica\Controllers\Uploader;
use \Ninja\Image;


abstract class Loader
{
    protected $message = '';
    protected $id = 0;
    protected $dir = '';
    protected $next = null;
    protected $extensions = [];
    protected $blacklist = [];

    public function __construct(protected Uploader $controller, protected string $local, protected string $thumbs = '', private $ratio = 0, private $pp = '')
    {
    }

    abstract public function handleAsset($record, $dir = '', $flag = false);
    abstract public function validatePath($path);

    public function getAsset($record, $dir = '', $flag = false) {
        $record = $this->getAsset($record, $dir, $flag);
        return [$record, $this->dir];
    }

    protected function getNameOfClass()
    {
        $name = strtolower(get_class($this));
        return substr(str_replace('loader', '', $name), 7);
    }

    public function getClassName($flag = false)
    {
        return $flag ? $this->getNameOfClass() : static::class;
    }

    protected function checkMimeType($path)
    {
        $pass = preg_match('|\/|', $path);
        if (!$pass && $this->dir) {
            $path = $this->dir . $path;
        }
        $pass = file_exists($path);
        if ($pass) {
            return $pass && explode('/', mime_content_type($path))[0] === $this->getNameOfClass();
        }
        return false;
    }

    protected function setAlt($str = '')
    {
        if (strpos($str, '/')) {
            return explode('/', $str)[0];
        }
        return $str;
    }

    protected function checkDir($filename)
    {
        $pass = file_exists($this->dir);
        if (!$pass) {
            $pass = mkdir($this->dir, 0777, true);
        }
        if ($pass) {
            copy(FILESTORE_DIR . $filename, $this->dir . '/' . $filename);
        }
        return $pass;
    }


    public function getOrientation($path, $klas = 'landscape')
    {
        if (isset($this->next)) {
            return $this->next->getOrientation($path, $klas);
        }
    }

    public function getRatio()
    {
        return $this->ratio;
    }

    public function setNext($next)
    {
        $this->next = $next;
        return $this;
    }

    public function init($filename)
    {
        if (!$this->checkMimeType(FILESTORE_DIR . $filename) && isset($this->next)) {
            return $this->next->init($filename);
        }
    }
    //this is to allow pdf records to remove the attr_id which is used to provide the link copy to the pdf; it's intended to be ephemeral 
    public function exit($id, $record, $flag = true)
    {
        if (!$this->checkMimeType($this->dir . $record['path']) && isset($this->next)) {
            return $this->next->exit($id, $record, $flag);
        }
            return null;
    }

    public function verifyMimeTypes($a, $b)
    {
        if (!empty($a) && !empty($b)) {
            return $this->checkMimeType($a) === $this->checkMimeType($b);
        }
        return false;
    }
    public function verifyExtension($ext, $newext = '')
    {
        $pass = in_array($ext, $this->extensions);
        $pass = !empty($newext) ? in_array($newext, $this->extensions) : $pass;
        if ($pass) {
            return $ext;
        } else if (isset($this->next)) {
            return $this->next->verifyExtension($ext, $newext);
        }
        return '';
    }

    public function makeLink($str, $path, $flag = false)
    {
        return [$str, 0];
    }

    public function breakLink($str, $pp, $pathtofile, $attr_id)
    {
        return $str;
    }

    public function pathToFile($path)
    {
        return !empty($this->dir) ? $this->dir . $path : '';
    }

    private function doScanDir($directory, $path)
    {
        // Extracts files and directories that match a pattern
        $items = scandir($directory);
        $items = array_filter($items, fn ($str) => preg_match('/^\w/', $str));
        foreach ($items as $item) {
            $found = "$directory$item/$path";
            if ($found && file_exists($found)) {
                break;
            };
        }
        return $found;
    }

    public function doUnlink($path, $pp = '', $backup = false)
    {
        $doRemove = doWhen('file_exists', 'unlink');
        $pathtofile = $this->local . $pp . '/' . $path;
        $pass = $this->checkMimeType($pathtofile);
        if (!$pass) { //$pp will not be available for orphans
            $pathtofile = $this->doScanDir($this->local, $path);
            $pass = $this->checkMimeType($pathtofile);
        }
        if ($pass) {
            $paths = [$pathtofile, FILESTORE_DIR . $path];
            array_map($doRemove, $paths);
        } else if (isset($this->next)) {
            return $this->next->doUnlink($path, $pp, $backup);
        }
    }

    public function prepareValues($filename, $ext = 'upload')
    {
        return $this->controller->prepareValues($filename, $ext);
    }

    public function getFileExtension($path)
    {
        $res = explode(".", $path);
        return strtolower(end($res));
    }

    public function getExtensions()
    {
        return $this->extensions;
    }

    public function generateThumbNail($path)
    {
        $img = new Image(50, 90);
        $img->thumbs($this->local . $path, $this->thumbs . $path);
    }
}
