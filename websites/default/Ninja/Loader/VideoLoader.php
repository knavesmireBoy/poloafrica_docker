<?php

namespace Ninja\Loader;

use \PoloAfrica\Controllers\Uploader;

class VideoLoader extends Loader
{
    protected $dir = '';
    protected $filename;
    protected $extensions = ['mp4', 'ogv', 'webm'];

    public function __construct(protected Uploader $controller, protected string $local, protected string $thumbs = '', protected $ratio = 0, protected $pp = '')
    {
    }

    //deal with poster images??
    private function poster($path, $cb)
    {
        $mapper = function ($path, $cb) {
            return function ($ext) use ($path, $cb) {
                $pth = preg_replace('/\.\w+$/', '.' . $ext, $path);
                $cb($this->local . $pth);
                return preg_replace('/\.\w+$/', '.jpg', $path);
            };
        };
    }
    protected function doCopy($filename, $flag = false, $sep = '')
    {
        $pathtofile = $this->dir . $sep . $filename;
        if (file_exists($pathtofile) && $this->checkMimeType($pathtofile)) {
            // && chown($pathtofile, 'andrewjsykes')
            /*
            if (chmod($pathtofile, 0777)) {
                $this->filename = $filename; //not used
                return true;
            }
            */
            return true;
        } else if (preg_match('/upload/', $flag)) {
            if (file_exists(FILESTORE_DIR . $filename)) {
                copy(FILESTORE_DIR . $filename, $pathtofile);
                return true;
            }
        }
    }
    protected function validateLink($values, $arg)
    {
        return true;
    }

    protected function setDir()
    {
        return $this->local . $this->pp . '/';
    }
    public function handleAsset($values, $pp = '', $flag = false)
    {
        $pass = null;
        $this->pp = $pp;
        $this->dir = empty($this->pp) ? $this->local : $this->setDir();
        $ext = $this->getFileExtension($values['path']);
        //only run validatePath IF extension matches; permissions error
        if (in_array($ext, $this->extensions)) {
            //run BEFORE checkMimeType, to complete path
            $pass = $this->validatePath($values['path'], $flag);
            $pass = $pass && $this->checkMimeType($this->dir . $values['path']);
        }
        if ($pass) {
            if (!$this->validateLink($values, $flag)) {
                return ['id' => '', 'path' => '', 'ext' => $ext];
            }
            return $values;
        } else if (isset($this->next)) {
            $this->controller->setLoader($this->next);
            return $this->next->handleAsset($values, $pp, $flag);
        } else {
            return ['id' => $values['id'], 'path' => '', 'ext' => ''];
        }
    }

    public function validatePath($filename, $flag = false)
    {
        $pass = file_exists($this->dir);
        if (!$pass && !empty($this->pp)) {
            $this->dir = $this->setDir();
            $pass = mkdir($this->dir, 0777, true);
        }
        return $pass && $this->doCopy($filename, $flag);
    }
}
