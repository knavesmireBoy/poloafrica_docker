<?php

namespace PoloAfrica\Controllers;

include_once 'config.php';

use \Ninja\DatabaseTable;
use \Ninja\Loader\ImageLoader;

class Gallery extends Uploader
{
    protected $loader;
    public function __construct(protected DatabaseTable $table, protected DatabaseTable $slotTable, private $pp, private $accept, protected array $loaderArgs)
    {
        // parent::__construct($table);
        $this->loader = new ImageLoader($this, ...$loaderArgs['image']);
        $this->slotTable = $slotTable;
        $this->pp = $pp; //pagination
    }

    private function fixOrientation($orient)
    {
        $res = $this->fetch('slotTable', 'orient', $orient, null, 1);
        $res = $this->fetch('table', 'id', $res->id, null, 1);
        return $this->getOrientation($res->path);
    }

    private function findByPath($path)
    {
        $img = $this->fetch('table', 'path', $path);
        $id = $img ? $img->id : null;
        $picid = true;
        return [$id, $picid];
    }
    private function getEntity($id)
    {
        $data = $this->table->find('id', intval($id), null, 1, 0, \PDO::FETCH_ASSOC);
        if (!empty($data)) {
            return $this->table->save($data[0]);
        } else {
            retour();
        }
    }

    private function getBoxId($id)
    {
        $img = $this->getEntity($id);
        return [$img, $img->getSlot(true)->id];
    }

    private function doLoadPic($id)
    {
        $pic = $this->fetch('table', 'id', $id);
        if ($pic) {
            return $this->loadpic($pic->id);
        }
    }

    private function getLimit($i)
    {
        return $this->pp[$i][0] ??  $this->pp[0][0];
    }
    private function getOffset($i)
    {
        return $this->pp[$i][1] ??  $this->pp[0][1];
    }

    private function resolvePath($path)
    {
        $pass = true;
        $subset = array_map(fn ($o) => $o->id, $this->table->find('path', $path));
        $slotset = array_map(fn ($o) => $o->pic_id, $this->slotTable->findAll());
        foreach ($subset as $sub) {
            $pass = $pass && !in_array($sub, $slotset);
        }
        return $pass;
    }

    protected function getOrphans($col = 'id', $mode = \PDO::FETCH_CLASS, $flag = true)
    {
        $cb = function ($a) {
            return $a['id'] . '/' . $a['path'];
        };
        $live = $this->table->findAll(null, 0, 0, \PDO::FETCH_ASSOC);
        $mylist = $this->getList(null, 0, 0);
        $list = toObject($mylist, true);
        $inter = array_diff(array_map($cb, $live), array_map($cb, $list));
        $orphans = [];
        foreach ($inter as $k => $v) {
            $arr = explode('/', $v);
            $tmp = [];
            $tmp['id'] = $arr[0];
            $tmp['path'] = $arr[1];
            $orphans[] = (object) $tmp;
        }
        return $orphans;
    }

    protected function deleteFiles($id = 0, $backup = false)
    {
        $file = $this->fetch('table', 'id', $id);
        $pp = '';
        if ($file) {
            $path = $file->path;
            $this->loader->doUnlink($path, $pp, $backup);
            return true;
        }
        return false;
    }
    private function getList($orderby = null, $limit = 14, $offset = 0, $mode = \PDO::FETCH_CLASS)
    {
        $gallery = $this->slotTable->findAll($orderby, $limit, $offset, $mode);
        $output = [];
        foreach ($gallery as $gal) {
            $res = $this->fetch('table', 'id', $gal->pic_id);
            //$gal->pic_id maybe null
            if ($res) {
                $res->box = $gal->id;
                $res->orient = $gal->orient;
            }
            $output[] = $res;
        }
        return $output;
    }

    protected function doDelete($id = 0, $flag = false)
    {
        $this->table->delete('id', $id);
        //if called in a loop (from removing multiple archived files) defer header to looper
        $this->complete(GAL_REVIEW, $flag);
    }

    protected function checkRatio($orient, $ratio)
    {
        return round($ratio, 1) === 1.5;
    }

    private function validatePicCookiePath($path)
    {
        return preg_match('/\.jpe?g$/', $path);
    }
    private function getLoadPicCookie($id)
    {
        if (isset($_COOKIE['loadpic'])) {
            list($duff, $name, $action, $id, $path) = explode('/', $_COOKIE['loadpic']);
            if ($this->validatePicCookiePath($path)) {
                unset($_COOKIE['loadpic']);
                setcookie('loadpic', '', -1, '/');
                return $this->findByPath($path);
            }
        }
        return [$id, false];
    }

    protected function reroute($id, $slotid, $key)
    {
        $route = isset($_POST['list']) ? GAL_EDIT : GAL_ASSIGN;
        $route = isset($_POST['floats']) ? GAL_UP : $route;
        reLocate($route . "$id/$slotid/$key", '../../');
    }
    protected function save($payload, $arg = 'upload')
    {
        $myorient = null;
        $orient = '';
        $record = $this->loader->handleAsset($payload);
        if (!empty($record['path'])) {
            $img = $this->fetch('table', 'path', $record['path']);
            $slot = $this->fetch('slotTable', 'id', $_POST['box']);
            $picId = $slot->pic_id ?? 0;
            $path = $img->path ?? $record['path'];
            list($orient, $max, $ratio) = $this->getOrientation($path);
            $myorient = $picId && ($orient === $slot->orient);
            $myratio = $this->checkRatio($orient, $ratio);
            if ($myorient && $myratio) {
                $record['id'] = $img->id ?? '';
            } elseif ($picId && !$myorient) {
                list($key) = $this->fixOrientation($orient);
                $slotid = $_POST['box'];
                $id = $record['id'] ?? $slotid;
                $this->reroute($id, $slotid, $key);
            } elseif ($picId && !$myratio) {
                $slotid = $_POST['box'];
                $id = $record['id'] ?? $slotid;
                $this->reroute($id, $slotid, 'ratio');
            }
            if (!isset($record['id']) && $img && ($img->path === $record['path'])) {
                $key = 'name';
                reLocate(GAL_ASSIGN . "$img->id/$slot->id/$key", '../../');
            }
            //save to gallery
            $img = $this->table->save($record);
            $active = $img->getSlot($img->id);
            if ($active && !empty($_POST['box']) && $picId) {
                $img->reAssign($_POST['box'], $orient, isset($_POST['shuffle']));
            } else if (!$active) {
                //guard against further inserts... on upload, unless we need to update pic_id
                if (isset($slot)) {
                    $slot->pic_id = $img->id ?? null;
                    if ($slot->id && $slot->pic_id) { //UPDATE ONLY
                        //save to slot
                        $this->slotTable->save(toObject($slot, true));
                    }
                }
            }
            return $img;
        } else {
            $slotid = $_POST['box'];
            $key = 'exist';
            if (!empty($_POST['list'])) {
                $key = 'existed';
                $this->delete($_POST['list'], true);
            }
            reLocate(GAL_ASSIGN . "0/$slotid/$key", '../../');
        }
    }
    /// PUBLIC ////// PUBLIC ////// PUBLIC ////// PUBLIC ////// PUBLIC ////// PUBLIC ////// PUBLIC ///

    public function prepareValues($fileName, $arg = 'upload')
    {
        if ($arg === 'upload' && !empty($fileName)) {
            $alt = $this->setAlt($_POST['data']['alt']);
            $values = ['path' => $fileName, 'alt' => $alt, 'date' => date('Y-m-d')];
            $img = $this->save($values, 'upload');
            if ($img) {
                $this->id = $img->id;
                reLocate(GAL_RELOAD . $img->id, '../../' );
            }
        }
    }

    public function nextpage($int = 1)
    {
        $i = is_numeric($int) ?  $int+=1 : 0;
        return $this->display($i);
    }

    public function prevpage($int = 0)
    {
        $i = is_numeric($int) ?  $int-=1 : 0;
        if ($i < 0) {
            $i = count($this->pp) - 1;
        }
        return $this->display($i);
    }

    public function display($int = 0)
    {
        if (is_numeric($int)) {
            $int = (abs($int) % count($this->pp));
            $limit = $this->getLimit($int);
            $offset = $this->getOffset($int);
            $output = $this->getList(null, intval($limit), intval($offset));
            return [
                'template' => 'gallery.html.php',
                'variables' => [
                    'gallery' => $output,
                    'prevpage' => $int,
                    'nextpage' => $int,
                    'layout' => $limit == 12 ? 'alt' : ''
                ]
            ];
        } else {
            retour();
        }
    }
    public function loadpic($id = 0, $path = '')
    {
        $img = $this->fetch('table', 'id',  $id);
        if (!$img) {
            reLocate(GAL_LIST, '../../');
        }
        $pics = $this->getList(null, 0);
        $getDetails = fn ($o) => $o->path . ',' . $o->orient . ',' . (!empty($o->alt) ? $o->alt : substr($o->path, 0, 3));

        $mapped = implode(';', array_map($getDetails, $pics));
        $pic = $this->fetch('slotTable', 'pic_id',  $id);
        $orient = $pic->orient === 'portrait' ? $pic->orient : '';
        return [
            'template' => 'galcontrols.html.php',
            'variables' => [
                'img' => $img,
                'action' => GAL_NEXT,
                'paths' => $mapped,
                'klas' => $orient
            ]
        ];
    }

    public function next($id = 1)
    {
        list($id, $picid) = $this->getLoadPicCookie($id);
        list($img, $slotid) = $this->getBoxId($id);
        $picid = empty($picid) ? $img->getNext($slotid) : $img->getCurrent($slotid);
        return $this->doLoadPic($picid);
    }

    public function prev($id = 1)
    {
        list($id, $picid) = $this->getLoadPicCookie($id);
        list($img, $slotid) = $this->getBoxId($id);
        list($img, $slotid) = $this->getBoxId($id);
        $picid = empty($picid) ? $img->getPrev($slotid) : $img->getCurrent($slotid);
        return $this->doLoadPic($picid);
    }

    //flag from reload to indicate post-uploaded state 
    public function upload($id = 0, $flag = null, $key = '')
    {
        $pic = $id ? $this->fetch('table', 'id', $id) : null;
        $slot = $pic ? $this->fetch('slotTable', 'pic_id', $id) : null;
        $slotid = $slot ? $slot->id : (is_numeric($flag) ? $flag : 0);
        //allows for messaging on validation failure (orientation etc..)
        if (!$pic && !$slot && $slotid) {
            $slot = $this->fetch('slotTable', 'id', $slotid);
            if ($slot) {
                $pic = $this->fetch('table', 'id', $slot->pic_id);
            }
        }
        $orphans = $this->getOrphans();
        $exit_guide = '';
        $message = empty($this->message) ? $key : $this->message;
        $message = is_bool($flag) ? 'reloaded' : $message;
        $myfloat = floatval($this->getRatio());
        if (!is_bool($flag)) {
            $myfloat = is_float($flag) ? floatval($flag) : $myfloat;
        }
        if($pic){
            $info = array_map(function ($pic) {
                list(, $max, $ratio) = $this->getOrientation($pic->path);
                return ['ratio' => $ratio, 'max' => $max];
            }, [$pic]);
            $exit_guide = GAL_UP . $pic->id . '/omitguide';
        }
        $previewklas = isset($pic->path) ? 'pic' : '';
        $reloaded = is_bool($flag) ? ' reloaded' : '';
        $omit = ($flag === 'omitguide') ? ' sansguide' : '';
        $previewklas .= $reloaded;
        $previewklas .= $omit;
        $slide = (($id === $flag) && empty($key)) ? ' slide' : '';
        $previewklas .= $slide;

        return [
            'template' => 'galupload.html.php',
            'variables' => [
                'action' => GAL_ON_UPLOAD . $id,
                'filename' => 'uploadfile',
                'warning' => '',
                'key' => $message,
                'img' => $pic,
                'orphans' => $orphans,
                'disabled' => false,
                'ratio' => $myfloat,
                'box' => $slotid,
                'alt' => 'ALT',
                'orient' => NULL,
                'files' => [],
                'routes' => ['route' => 'upload'],
                'accept' => $this->accept,
                'max' => isset($max) ? $max : 0,
                'info' => isset($info) ? $info[0] : null,
                'omitguide' => $omit,
                'previewklas' => $previewklas,
                'exit_guide' => $exit_guide ? $exit_guide : '/gallery/upload/0/omitguide',
                'page' => '',
                'submit' => 'submit'
            ]
        ];
    }

    public function reload($picid = 0)
    {
        $file = $this->fetch('table', 'id', $picid);
        if ($file) {
            return $this->upload($file->id, true);
        } else {
            reLocate(GAL_REVIEW, '../../');
        }
    }
    public function review($key = '')
    {
        $output = $this->getList(null, 0, 0);
        return [
            'template' => 'galedit.html.php',
            'variables' => [
                'gallery' => $output,
                'routes' => [],
                'key' => is_numeric($key) ? '' : $key
            ]
        ];
    }

    //$boxid: pass $boxid from dropdown version of image edit form to freetext version
    public function assign($id = 0, $slotid = 0, $key = '')
    {
        if ($id) {
            $img = $this->fetch('table', 'id', $id);
            $slot = isset($img) ? $this->fetch('slotTable', 'pic_id', $id) : null;
        }
        $mybox = isset($slot) ? $slot->id : $slotid ?? '';
        $myid = $id ?? '';
        $link = $myid ? GAL_UP . $myid : GAL_UP;
        $edit = $myid ? GAL_EDIT . $myid : GAL_EDIT;
        //dump($myid);
        return [
            'template' => 'galimage.html.php',
            'variables' => [
                'action' => GAL_ASSIGN,
                'box' => $mybox,
                'orient' => null,
                'key' => $key,
                'klas' => 'preload',
                'disabled' => false,
                //'submit' => 'Assign',
                'submit' => 'submit',
                'routes' => ['route' => 'assign'],
                'img' => isset($img->id) ? $img : null,
                'para' =>  '<p>We strongly recommend <a href="' . $link . '">uploading</a> a file again, not least as this is the only way to enforce the correct <strong>aspect ratio</strong> required by the gallery. Uploading  also provides the opportunity to rename or resize the file.</p><p>You may simply assign a *<strong>library</strong> image to a placeholder (box). However, the file must conform to a 1.5 aspect ratio (be it portrait or landscape) the path must be spelt exactly, and the file must obviously exist - and with the correct permissions - in the target directory.</p> <p>A better option is to use a <a href="' . $edit . '">dropdown menu</a> to assign a currently †<strong>archived</strong> image to the selected location.</p><p><dfn>*the file resides in a folder but is not referenced in the database.</dfn><dfn>†the file is referenced in the database but not assigned to a slot.</dfn></p>'
            ]
        ];
    }
    public function edit($id = 0, $slotid = 0, $key = '')
    {
        $locate = !$id ? '../' : '../../';
        
        $data = $this->fetch('table', 'id', $id, null, 0, 0, \PDO::FETCH_ASSOC);
        //$slots = $this->fetch('slotTable', 'id', $id, null, 0, 0, \PDO::FETCH_ASSOC);
        if (!$data) {
            //returns to the review page sans warning: silly buggers
            reLocate(GAL_REVIEW, $locate);
        }
        $img = $this->table->save($data);
        $slot = $img ? $img->getSlot(true) : null;

        if ($slot) {
            $img->box = $img->getSlot(1)->id;
            $img->orient = $img->getSlot(1)->orient;
        }
        $myboxid = $img->box ?? $slotid;
        $myorient = $img->orient ?? '';
        $data = $this->table->findAll();

        $active = array_map(
            function ($o) {
                $ret = [];
                $ret['id'] = $o->id;
                $ret['alt'] = $o->alt;
                $ret['path'] = $o->path;
                return (object) $ret;
            },
            array_filter($data, fn ($o) => $o->getStatus(true))
        );
        $archived = array_map(
            function ($o) {
                $ret = [];
                $ret['id'] = $o->id;
                $ret['alt'] = $o->alt;
                $ret['path'] = $o->path;
                return (object) $ret;
            },
            array_filter($data, fn ($o) => $o->getStatus(false))
        );
        $active = $img->orderById($active);
        $archived = $img->orderById($archived);
        $assign = GAL_ASSIGN . "$id/$myboxid";
        $target = empty($key) ? $id : $slotid;
        $myid = $id ?? '';
        $link = $myid ? GAL_UP . $myid : GAL_UP;
        return [
            'template' => 'galimage.html.php',
            'variables' => [
                'action' => GAL_EDIT,
                'img' => $img,
                'orient' => $myorient,
                'box' => $myboxid,
                'klas' => '',
                'disabled' => false,
                'select' => ['target' => $target, 'identity' => 'list', 'options' => $active ?? [], 'orphans' => $archived ?? [], 'optval' => 'path'],
               //'submit' => 'Edit',
                'submit' => 'submit',
                'key' => $key,
                'routes' => ['route' => 'edit'],
                'slotid' => $slotid,
                'para' => '<p>In this form you can assign a new NUMERICAL location to your SELECTED image using the BOX drop-down or REPLACE your selected picture at the CURRENT location using the PATH drop-down (far left). By default the two pictures will be swapped. Checking the checkbox will SHUFFLE pictures forward/backward. Portrait and Landscape pictures will not be swapped.</p>
                <p>Click <a href="' . $assign . '">here</a> to use free text input as opposed to the dropdown menu provided, finally you may also <a href="' . $link . '">upload</a> a new file to replace your selection.</p><p>You may of course ignore all of the above and simply update the alt attribute.</p>'
            ]
        ];
    }
    public function assignSubmit($id = 0)
    {
        if (isset($_POST['alt'])) {
            $alt = trim($_POST['alt']);
            $payload = ['alt' => $alt];
            $payload['path'] = trimToLower($_POST['path']);
            $img = $this->fetch('table', 'path', $payload['path']);
            if($img){
                $payload['alt'] = $img->alt != $alt ? $alt : $img->alt;
            }
            $date = !empty($_POST['date']) ? $_POST['date'] :  date('Y-m-d');
            $payload['date'] =  $this->checkdate($date) ? NULL : $date;
            $this->save($payload);
        }
        reLocate(GAL_REVIEW, '../../');
    }

    public function editSubmit()
    {
        if (isset($_POST['pk'])) {
            $date = !empty($_POST['date']) ? $_POST['date'] :  date('Y-m-d');
            $alt = trim($_POST['alt']);
            $payload['date'] =  $this->checkdate($date) ? NULL : $date;
            $payload['id'] = $_POST['pk'];
            $payload['alt'] = $alt;
            //would be set on NORMAL execution
            if (!empty($_POST['list'])) {
                $payload['id'] = $_POST['list'];
                $img = $this->fetch('table', 'id', $payload['id']);
                $payload['path'] = $img->path;
                $payload['alt'] = $img->alt;
                if ($_POST['pk'] == $_POST['list']) { //editing 
                    $payload['alt'] = $img->alt != $alt ? $alt : $img->alt;
                }
            }
            $this->save($payload);
        } 
        reLocate(GAL_REVIEW, '../../');
    }

    public function manage($col = null)
    {
        return [
            'template' => 'orphans.html.php',
            'variables' => [
                'group' => $this->getOrphans(),
                'id' => $col,
                'action' =>  $this->getClassName('/'),
                'dir' => 'resources/images/gallery/fullsize/',
                'exit' => ['href' => GAL_REVIEW, 'txt' => 'Back To Gallery']
            ]
        ];
    }

    public function manageSubmit()
    {
        //if javascript is enabled you can easily check all boxes at once
        // if not we need to get an ARRAY of orphan objects with path and id properties
        if (!empty($_POST)) {
            $backup = isset($_POST['backup']);
            if (isset($_POST['all']) && !isset($_POST['pics'])) {
                $picIds = array_map(fn ($a) => $a->id, $this->getOrphans());
            } else {
                $picIds = $_POST['pics'];
            }

            foreach ($picIds as $k) {
                $pic = $this->fetch('table', 'id', $k);
                if ($pic && isset($pic->path)) {
                    if ($this->resolvePath($pic->path)) {
                        $this->destroy($k, $backup, true);
                    } else {
                        $this->delete($k, true);
                    }
                }
            }
        }
        reLocate(GAL_REVIEW, '../../');
    }
}
