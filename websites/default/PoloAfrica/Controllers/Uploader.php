<?php

namespace PoloAfrica\Controllers;

use \Ninja\DatabaseTable;
use \Ninja\Loader\Loader;

abstract class Uploader
{
    protected $message = '';
    protected $id = 0;
    protected $ApplicationLoader;
    protected $imageLoader;
    protected $videoLoader;
    protected $loader;

    public function __construct(protected DatabaseTable $table, private $accept)
    {
    }

    abstract protected function getOrphans();
    abstract protected function save($payload);
    abstract protected function doDelete($id = 0, $flag = false);
    abstract protected function deleteFiles($id = 0);

    protected function getRatio()
    {
        return $this->loader->getRatio();
    }

    protected function getNameOfClass()
    {
        return static::class;
    }

    protected function getExtension($path)
    {
        return strtolower(substr(strrchr($path, "."), 1));
    }

    protected function getFileExtension($path)
    {
        $fileNameCmps = explode(".", $path);
        return strtolower(end($fileNameCmps));
    }
    protected function validatePath($path = '')
    {
        return $this->loader->validatePath($path);
    }
    protected function setAlt($str = '')
    {
        if (strpos($str, '/')) {
            return explode('/', $str)[0];
        }
        return $str;
    }

    protected function getAccess($i)
    {
        //2 'Content Editors' //4 'Photo Editors' 
        $lib = [1 => 'Registered Users', 2 => 'Content Editors', 4 => 'Photo Editors'];
        return isset($lib[$i]) ? $lib[$i] : 'Account Administrators';
    }

    protected function getOrientation($path, $klas = 'landscape')
    {
        return $this->loader->getOrientation($path, $klas);
    }

    protected function checkdate($date = null)
    {
        $y = date('Y');
        $date = $date ?? $_POST['date'];
        $d = explode('-', date($date))[0];
        return $d < $y;
    }

    protected function fetch($t, $prop, $val, ...$rest)
    {
        $ret = [];
        if ($val) { //safeguard against missing values
            if (strtoupper($t) === $t) {
                $t = strtolower($t);
                $ret = $this->{$t}->find($prop, $val, null, 0, 0, \PDO::FETCH_ASSOC);
            } else {
                $ret = $this->{$t}->find($prop, $val, ...$rest);
            }
        }
        return empty($ret) ? null : $ret[0];
    }

    protected function filter($array, $cb, $flag = false)
    {
        $res = array_values(array_filter($array, $cb));
        if (isset($res[0])) {
            if ($flag) {
                return $res;
            }
            return $res[0];
        }
        return null;
    }

    protected function complete($path, $loopcallback = false)
    {
        if ($path && !$loopcallback) {
            reLocate($path, '../../');
        }
    }

    protected function period($name, $ext)
    {
        $tmp = explode('.', $name);
        if (isset($tmp[2])) {
            array_pop($tmp);
            $name = implode('_', $tmp);
            $name = "$name.$ext";
        }
        return preg_replace('/\s/', '', $name);
    }

    protected function encrypt($name, $ext)
    {
        return  md5(time() . $name) . '.' . $ext;
    }

    protected function setName($encrypt = false)
    {
        $txt = $_POST['data']['description'] ?? $_POST['data']['alt'] ?? ''; //subclass??
        $fileName = trimToLower($_FILES['uploadfile']['name']);
        $ext = $this->getFileExtension($fileName);
        if (strpos($txt, '/')) {
            $f = explode('/', $txt);
            if (!empty($f[1])) {
                $fileName = $f[1] . '.' . $ext;
            }
        }
        //$fileName = $this->period($fileName, $ext);
        $fileName = preg_replace('/\s+/', '_', $fileName);
        $newFileName = empty($encrypt) ? $fileName : $this->encrypt($fileName, $ext);
        return strtolower($newFileName);
    }

    abstract public function edit();
    abstract public function editSubmit();
    abstract public function upload($id = 0, $flag = true);
    abstract public function manage($col);
    //used by Loader
    abstract public function prepareValues($fileName, $arg = '');

    public function getClassName($prefix = '')
    {
        return $prefix . strtolower(substr(strrchr($this->getNameOfClass(), '\\'), 1));
    }

    public function destroy($id = 0, $backup = false, $flag = false)
    {
        //check if file in active array
        $this->deleteFiles($id, $backup);
        $this->doDelete($id, $flag);
    }

    public function delete($id = 0, $flag = false)
    {
        $this->doDelete($id, $flag);
    }

    public function setLoader(Loader $loader)
    {
        if ($loader) $this->loader = $loader;
    }

    public function onupload($myid = 0)
    {
        $mycontroller = $this->getClassName('/');
        if (isset($_FILES['uploadfile']) && $_FILES['uploadfile']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['uploadfile']['tmp_name'];
            if (!is_uploaded_file($fileTmpPath)) {
                $this->message = "Possible file upload attack: ";
                reLocate("$mycontroller/upload/$myid/true/attack", '../../');
            }
            $newFileName = $this->setName();
            $remotePath = FILESTORE_DIR . $newFileName;
            if (!move_uploaded_file($fileTmpPath, $remotePath)) {
                reLocate("$mycontroller/upload/$myid/true/access", '../../');
            } else {
                $this->loader->init($newFileName);
                return $this->prepareValues($newFileName, 'upload');
                exit;
            }
        } //ok
        else {
            $u = floor($_SERVER['CONTENT_LENGTH'] / 1000000) . 'mb';
            $msg = empty($_FILES) ? "exceeds_$u" : 'choose';
            reLocate("$mycontroller/upload/$myid/0/$msg/$u", '../../');
        }
    }

    public function message($str = '', $i = 0)
    {
        $str = exclaim($str);
        if ($str) {
            return [
                'template' => 'accessdenied.html.php',
                'variables' => [
                    'str' => $str,
                    'accesslevel' => $this->getAccess($i),
                    'submitted' => false
                ]
            ];
        } else {
            retour();
        }
    }
}
