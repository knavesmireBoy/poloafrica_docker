<?php

namespace Ninja\Loader;

use \PoloAfrica\Controllers\Uploader;
use \Ninja\Image;


class ImageLoader extends Loader
{

  public function __construct(Uploader $controller, protected string $local, protected string $thumbs = '', protected $ratio = 0, protected $pp = '')
  {
    parent::__construct($controller, $local, $thumbs, $ratio, $pp);
    $this->dir = $this->local;
  }

  protected $extensions = ['jpg', 'jpeg', 'gif', 'png'];
  protected $blacklist = ['webp'];
  protected $dir = 'resources/images/articles/fullsize/';
  protected function doRotate($data)
  {
    //$res = 

  }
  //init is only used by imageLoader on INITIAL upload and used by others AFTER handleAsset is called
  //handleAsset is the handler that calls next in chain
  public function init($filename = '')
  {
    $type = explode('/', mime_content_type(FILESTORE_DIR . $filename))[0];
    $ext = $this->verifyExtension($this->getFileExtension($filename));

    if ($ext && $type === $this->getNameOfClass()) {
      $floats = $_POST['floats'];
      $ints = $_POST['ints'];
      //splitOn returns either null or an array of two members
      $ratio = splitOn($floats['ratio']); //
      $int = splitOn($ints['maxsize']);

      if (preg_match('/deg$/', $ints['appearance'])) {
        $ints['rotate'] = intval($ints['appearance']);
      } 
      //actual division takes place here
      $floats['ratio'] = $ratio ? round($ratio[0] / $ratio[1], 2) : $floats['ratio'];
      $ints['maxsize'] = $int ? round($int[0] / $int[1]) : $ints['maxsize'];
      //or two values are obtained
      if (!isset($ints['rotate'])) {
        $appearance = splitOn($ints['appearance']);
        $ints['appearance'] = $appearance[0] ?? $appearance;
        $ints['rotate'] = $appearance[1] ?? 0;
      }
      $floats = array_map('floatval', array_values($floats));
      $ints = array_map('intval', array_values($ints));
      $img = new Image(60, 90);
      $img->setRoute($this->controller->getClassName() . '/upload', $_POST['article'] ?? 0);
      $img->build(FILESTORE_DIR . $filename, $this->local . $filename, ...$floats, ...$ints);
      $img->thumbs(FILESTORE_DIR . $filename, $this->thumbs . $filename, ...$floats, ...$ints);
    }
  }

  public function resolvePath($filename, $sep)
  {
  }

  public function process($values, $pp = '', $flag = false)
  {
  }

  public function getOrientation($path, $klas = 'landscape')
  {
    $ext = $this->getFileExtension($path);
    if (!in_array($ext, $this->extensions)) {
      return ['', 0, 0];
    }
    $path = $this->local . $path;

    if (file_exists($path)) {
      try {
        $image = new Image();
        list($w, $h) = $image->getDims($path);
        $klas = $h > $w ? 'portrait' : $klas;
      } catch (\Exception $e) {
        $klas = '';
      }
      $max = $klas === 'portrait' ? $h : $w;
      //$res = $image->doResolution($image);
      $ratio = $h > $w ? ($h / $w) : ($w / $h);
      return [$klas, $max, round($ratio, 5)];
    }
    //some sensible defaults on missing path
    return ['landscape', 300, 1.5];
  }

  public function validatePath($path = '')
  {
    $hi_res = findfile($this->local, $path, 2); //returns $path
    $lo_res = $hi_res && file_exists($this->thumbs . $hi_res);
    $mime = '';
    if ($hi_res) {
      $mime = explode('/', mime_content_type($this->local . $hi_res))[1];
    }
    return in_array($mime, $this->extensions) ? [$hi_res, $lo_res] : [null, null];
  }

  public function handleAsset($record, $dir = '', $flag = false)
  {
    $path = $record['path'] ?? trimToLower($_POST['path']);
    $ext = $this->getFileExtension($path);
    list($hi_res, $lo_res) = $this->validatePath($path);
    if ($hi_res) {
      //assign $hi-res NOT $_POST['path'] because of jpe?g
      $record['path'] = $hi_res;
      if (!$lo_res) {
        $this->generateThumbNail($record['path']);
      }
    } else {
      if (in_array($ext, $this->blacklist)) {
        return ['id' => $record['id'], 'path' => '', 'ext' => $ext];
      } else if (isset($this->next)) {
        $this->controller->setLoader($this->next);
        return $this->next->handleAsset($record, $dir, $flag);
      } else {
        return ['id' => $record['id'] ?? 0, 'path' => '', 'ext' => $ext];
      }
    }
    return $record;
  }

  public function doUnlink($path, $pp = '', $backup = false)
  {
    $doRemove = doWhen('file_exists', 'unlink');
    $pathtofile = $this->local . $path;
    $pass = $this->checkMimeType($pathtofile);
    if ($pass) {
      $paths = [$this->thumbs . $path, $this->local . $path];
      if ($backup) {
        $paths[] = FILESTORE_DIR . $path;
      }
      array_map($doRemove, $paths);
    } else if (isset($this->next)) {
      return $this->next->doUnlink($path, $pp, $backup);
    }
  }
}
