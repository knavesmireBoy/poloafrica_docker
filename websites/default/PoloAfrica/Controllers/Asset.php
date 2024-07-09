<?php

namespace PoloAfrica\Controllers;

include_once 'config.php';

use \Ninja\DatabaseTable;
use \Ninja\Loader\ImageLoader;
use \Ninja\Loader\VideoLoader;
use \Ninja\Loader\ApplicationLoader;
use PDO;

class Asset extends Uploader
{
    private $ratio;

    public function __construct(protected DatabaseTable $table, private $accept, protected array $loaderArgs = [])
    {
        parent::__construct($table, $accept);
        $this->ApplicationLoader = new ApplicationLoader($this, ...$loaderArgs['application']);
        $this->imageLoader = new ImageLoader($this, ...$loaderArgs['image']);
        $this->videoLoader = new VideoLoader($this, ...$loaderArgs['video']);
        $this->ApplicationLoader->setNext($this->imageLoader);
        $this->imageLoader->setNext($this->videoLoader);
        $this->loader = $this->ApplicationLoader;
    }

    private function getSubGroups($path)
    {
        $active = $this->table->find('article_id', null, 'path', 0, 0, 2, ' IS NOT NULL');
        $archived = $this->table->find('article_id', null, 'path', 0, 0, 2, ' IS NULL');
        if (is_bool($path)) {
            return [$active, $archived];
        }
        $paths = array_map(fn ($item) => $item['path'], $active);
        $orphans = array_map(fn ($item) => $item['path'], $archived);
        return [in_array($path, $paths), in_array($path, $orphans)];
    }

    private function getDirectory(\PoloAfrica\Entity\Asset $asset)
    {
        if (isset($asset)) {
            $video = array_map(fn ($o) => substr($o, 1), VIDEO_EXT); // '.mp4' to 'mp4'..
            $pp = $asset ? $asset->getArticle($asset->id, 'page') : '';
            $ext = $asset ? $this->getExtension($asset->path) : '';
            $dir = in_array($ext, $video);
            if (!$dir) {
                $dir = $ext === 'pdf' ? ASSETS . "$pp/" : IMAGES;
            } else {
                $dir =  VIDEO_PATH . "$pp/";
            }
            return $dir;
        }
        return IMAGES;
    }

    private function preflight($id)
    {
        $file = $this->fetch('table', 'id', $id);
        if ($file) {
            list($assigned, $archived) = $this->getSubGroups($file->path);
            if ($assigned && $archived) {
                list($assigned, $archived) = $this->getSubGroups(true);
                $players = array_filter($assigned, fn ($o) => $o['id'] == $id);
                $orphans = array_filter($archived, fn ($o) => $o['id'] == $id);
                if (!empty($players)) {
                    $files = $this->table->find('path', $file->path);
                    return count($files) > 1 ? [] : $files;
                }
                if (!empty($orphans)) {
                    foreach ($orphans as $file) {
                        return $this->delete($file['id']);
                    }
                    return [];
                }
            }
            return [$file];
        }
    }
    private function validateUpdate($record, $resident, $props, $flag = false)
    {
        foreach ($props as $p) {
            $lc = strtolower($p);
            if (!empty($_POST['data'][$lc]) && isset($resident) && $_POST['data'][$lc] !== $resident[$lc]) {
                if ($p === strtoupper($p)) {
                    $record[$lc] = trimToLower($_POST['data'][$lc]);
                }
                $record[$lc] = trim($_POST['data'][$lc]);
            } else {
                if ($flag) {
                    $record[$lc] = trimToLower($_POST['data'][$lc]);
                } else {
                    $record[$lc] = $record[$lc] ? $record[$lc] : '';
                }
            }
        }
        return $record;
    }

    private function setAttributes($data, $assign, $flag = false)
    {
        $record['alt'] =  trim($data['alt']);
        $record['attr_id'] = trimToLower($data['attr_id']);
        $record['date'] = $flag ? $data['date']  : date('Y-m-d');
        $record['path'] = trimToLower($data['path']);
        $record['article_id'] = isset($assign) ? $data['article_id'] : null;
        return $record;
    }

    private function getArticle($asset)
    {
        $asset = $asset ? $this->table->save($asset) : null;
        return $asset ? $asset->getArticle($asset->id) : null;
    }

    private function forceGetPage($values)
    {
        $file = $this->table->save($values);
        $pp = $file->getArticleDirect($values['article_id'], 'page');
        $this->table->delete('id', $file->id);
        return $pp;
    }

    private function doReplace($resident, $candidate, $route, $replace)
    {
        if ($replace) {
            $resident = toObject($resident, true);
            //siganture expects a $resident as an assoc array ['id' => 1, path' => 'my.jpg', ...];
            //$candidate can be a wrapper around a filename : ['path' => 'my.jpg'];
            list($res, $cand) = array_map(fn ($o) => $o['path'], [$resident, $candidate]);
            list($old, $neu) = array_map([$this, 'getExtension'], [$res, $cand]);
            $res = $this->archive($resident, $old, $neu);
            if (!$res) {
                reLocate($route);
            }
        }
    }

    private function getPage($values)
    {
        $pp = '';
        if (!empty($values)) {
            $file = $this->fetch('table', 'article_id', $values['article_id']);
            if ($file) {
                $pp = $file->getArticleDirect($values['article_id'], 'page');
            }
            if (!$pp) {
                return $this->forceGetPage($values);
            }
        }
        return $pp;
    }

    private function doGetPage($posted, $assetId, $prop = 'page')
    {
        $pp = '';
        if ($assetId) {
            $file = $this->fetch('table', 'id', $assetId);
            $pp = $file->getArticle($assetId, $prop);
        }
        if (!$assetId || !$pp) {
            return $this->getPage(['article_id' => $posted['article_id']]);
        }
        return $pp;
    }

    private function reinstate($posted, $resident)
    {
        $record = $this->validateUpdate($posted, $resident, ['alt', 'ATTR_ID', 'PATH']);
        $record['article_id'] = isset($posted['assign']) || isset($posted['replace']) ? $posted['article_id'] : null;
        $record['id'] = '';

        $found = $this->fetch('TABLE', 'path', $record['path']);
        if ($found) {
            $record['id'] = $record['path'] === $found['path'] ? $found['id'] : '';
        }
        return $record;
    }

    protected function getVariables($article, $asset, $doreplace)
    {
        return [
            'action' => ASSET_EDIT,
            'exit' => ARTICLES_EDIT . $article->id,
            'articleId' => $article->id,
            'asset' => $asset,
            'replace' => $doreplace,
            'h3' => $doreplace ? 'Replace Asset' : 'Add Asset',
            'title' => $article->title ?? ''
        ];
    }

    protected function complete($path, $loopcallback = false)
    {
        if ($path && !$loopcallback) {
            reLocate($path, '../../');
        }
    }
    protected function doDelete($id = 0, $flag = false)
    {
        $file = $this->fetch('table', 'id', $id);

        if (isset($file)) {
            $pp = $file->getArticle($id, 'page');
            $str = $this->breakLink($file, $pp, true);
            $file->setContent($str);
            $this->table->delete('id', $id);
            $id = $file->article_id;
            $route = $id ? ARTICLES_EDIT . $id : ARTICLES_LIST;
            //if called in a loop defer header for looper
            $this->complete($route, $flag);
        } else {
            reLocate(ASSET_LIST, '../../');
        }
    }

    protected function deleteFiles($id = 0, $backup = false)
    {
        $files = $this->preflight($id);
        if (!empty($files)) {
            foreach ($files as $f) {
                $pp = $f->getArticle($id, 'page');
                $this->loader->doUnlink($f->path, $pp, $backup);
            }
        }
    }

    protected function breakLink($file, $pp, $attr_id)
    {
        $str = $file->getArticle($file->id, 'content');
        if (isset($str)) {
            return $this->loader->breakLink($str, $pp, $file->path, $attr_id);
        }
    }

    protected function archive($record, $ext, $newext = '')
    {
        $pass = is_string($ext) ? $ext === $newext : $ext;
        if (!$pass) {
            $ext = preg_match('/^jpe?g$/i', $ext);
            $pass = $ext && preg_match('/^jpe?g$/i', $newext);
        }
        if (!empty($pass) && isset($record)) {
            $attr = !isset($_POST['data']['attr_id']) ? true : !empty($_POST['data']['attr_id']);
            $file = $this->table->save($record);
            $pp = $file->getArticle($file->id, 'page');
            //$attr is a boolean to indicate whether to replace PDF link copy
            //note must use $_POST['data']['attr_id'] not $record as $record['attr_id'] is cleared for db storage
            $str = $this->breakLink($file, $pp, $attr); //identity function for NON-PDF files
            if (isset($str)) {
                $file->setContent($str);
            }
            $record['article_id'] = NULL; //archiving right here; 
            return $this->table->save($record);
        }
        return false;
    }

    protected function save($values, $pp = '', $arg = '')
    {
        $record = $this->loader->handleAsset($values, $pp, $arg);
        $path = $_POST['data']['path'] ?? ''; //MAYBE not available on upload.html.php
        if (!empty($record['path'])) {
            $file = $this->table->save($record);
        } else {
            if ($arg === 'archived' || preg_match('/upload/', $arg)) {
                $id = $record['id'];
                if (!empty($id)) {
                    reLocate(ASSET_CONFIRM . "$id/notfound", '../../');
                } else {
                    $articleId = $_POST['data']['article_id'];
                    if (preg_match('/upload/', $arg)) {
                        $ext = $record['ext'] ?  $record['ext'] : 'accept';
                        reLocate(ASSET_UPLOAD . "$articleId/0/$ext", '../../');
                    }
                }
            }
            if ($path && !is_numeric($path)) {
                $path = trimToLower($path);
                $ext = "Cannot find the file '<span>$path</span>' in the target directory, please check the spelling.";
                return $this->add($_POST['data']['article_id'], '', $ext);
            }
        }
        if (isset($file) && $record['article_id']) {
            $articleId = $record['article_id'];
            $route = $arg === 'upload' ? ASSET_RELOAD . $file->id : ASSETS_EDIT . $record['article_id'];
            //pdf...
            $str = $file->getArticle($file->id, 'content');
            list($mystr, $key) = $this->loader->makeLink($str, $file->path, $file->alt, $record['attr_id']);
            $file->setContent($mystr);
            if (!is_numeric($key)) { //failed handleAsset
                $fId = $record['id'] ?? 0;
                $up = ASSET_UPLOAD . "$articleId/0/$key";
                $ed = ASSET_EDIT . "$fId/edit/$key";
                $route =  $arg === 'upload' ? $up : $ed;
                reLocate($route, '../../');
            }
            $record = $this->loader->exit($file->id, $record, $mystr !== $str);
            if ($record) {
                $file = $this->table->save($record);
            }
            reLocate($route, '../../');
        } else {
            reLocate(ARTICLES_LIST, '../../');
        }
    }

    protected function checkPath($path, $coll = [])
    {
        $coll = empty($coll) ? $this->table->findAll(null, 0, 0, \PDO::FETCH_ASSOC) : $coll;
        $paths = array_map(fn ($o) => $o['path'], $coll);
        return in_array($path, $paths);
    }

    protected function getOrphans($col = 'id', $mode = \PDO::FETCH_CLASS, $flag = true, $orderBy = null)
    {
        return $this->table->filterNull($col, $flag, $orderBy, 0, 0, $mode);
    }
    protected function prepTemplate($articleId, $assetId = null, $op = 'Edit', $key = '')
    {
        $archived = $this->table->find('article_id', null, 'path', 0, 0, \PDO::FETCH_ASSOC, ' IS NULL');
        $asset = $assetId ? $this->fetch('table', 'id', $assetId) : null;

        $uploadroute = ASSET_UPLOAD . $articleId;
        $title = $asset ? $asset->getArticle($asset->id, 'title') : '';
        $doreplace = preg_match('/replace/i', $op) ? 'replace' : '';
        $dir = $asset ? $this->getDirectory($asset) : IMAGES;

        $ids = isset($asset) ? "$articleId/$asset->id" :  $articleId;
        return [
            'template' => 'asset.html.php',
            'title' => "$op Asset",
            'variables' => [
                'action' => ASSET_EDIT,
                'exit' => ARTICLES_EDIT . $articleId,
                'articleId' => $articleId,
                'asset' => $asset,
                'dir' => $dir,
                'title' => $title,
                'key' => $key,
                'routes' => ['route' => 'edit', 'add' => ASSET_ADD . $ids, 'upload' => $uploadroute, 'assign' => ASSET_ASSIGN . $ids, 'edit' => ASSET_EDIT . $ids],
                'select' => ['options' => $archived, 'target' => $asset->id ?? null, 'identity' => 'PATH', 'optval' => 'path'],
                'replace' => $doreplace,
                'h3' => ucwords("$op asset")
            ]
        ];
    }
    //turn off replace directive
    protected function deActivate($filename, $id)
    {
        $article_assets = $this->table->find('article_id', $id);
        $paths = array_map(fn ($o) => $o->path, $article_assets);
        return in_array($filename, $paths) ? false : true;
    }
    public function prepareValues($fileName, $arg = 'upload')
    {
        if (empty($_POST)) {
            reLocate(REG);
        }
        $replace = false;
        $relocate = false;
        $updateId = null;
        $date = date('Y-m-d');
        $aId = intval($_POST['data']['article_id']);

        $myalt = $_POST['data']['alt'] ?? explode('.', $fileName)[0];
        $mypath = $_POST['data']['path'] ?? '';

        $alt = $this->setAlt($myalt);
        $values = array_map('trim', ['path' => $fileName, 'alt' => $alt, 'article_id' => $aId, 'attr_id' => $_POST['data']['attr_id'], 'date' => $date]);
        $upload = $this->fetch('table', 'path', $fileName);
        $article_assets = $this->table->find('article_id', $aId);

        $myarchived = $this->getOrphans('article_id', \PDO::FETCH_ASSOC, true);
        $myactive = $this->getOrphans('article_id', \PDO::FETCH_ASSOC, false);
        $active = $this->checkPath($fileName, $myactive);
        $archived = $this->checkPath($fileName, $myarchived);
        //filter out assets that belong to CURRENT article
        $active = $active && $this->deActivate($fileName, $aId);
        if ($active) {
            reLocate(ASSET_UPLOAD . "$aId/0/article", '../../');
        }
        if (isset($mypath) && is_numeric($mypath)) {
            $asset = $this->fetch('table', 'id', $mypath);
            $updateId = ($fileName == $asset->path) ? $asset->id : $updateId;
            //bail out it attempting to replace a fellow article asset of the file to be uploaded
            if (!$updateId && !$this->deActivate($fileName, $aId)) {
                reLocate(ASSET_UPLOAD . "$aId/0/sibling", '../../');
            }
            $replace = $this->deActivate($fileName, $aId);
        } else {
            $asset = $this->filter($article_assets, fn ($o) => $o->path == $fileName);
            $updateId = $asset ? $asset->id : $updateId;
        }
        if ($replace) {
            //'upload' used by pdf to enforce INITIAL link copy, not required IF replacing the target asset;
            //ignored by none pdf files
            $arg = 'uploaded';
            $relocate = !$this->insertAllowed($values, $replace);
            if (!$relocate) {
                $this->doReplace($asset, ['path' => $fileName], ASSET_UPLOAD . "$aId/0/ext", true);
            }
        }
        //if archived file has same name as upload UPDATE not INSERT
        $values['id'] = $archived && $upload ? $upload->id : $updateId;
        $pp = !empty($asset) ? $asset->getArticleDirect($aId, 'page') : '';
        if (empty($pp)) {
            $pp = $this->getPage($values);
        }
        if ($relocate || !$this->insertAllowed($values, $replace)) {
            $aId = $_POST['data']['article_id'];
            reLocate(ASSET_UPLOAD . "$aId/0/allowed", '../../');
        }
        $this->save($values, $pp, $arg);
    }

    public function destroy($id = 0, $backup = false, $flag = false)
    {
        //check if file in active array
        if (!isset($_POST['cancel'])) {
            $this->deleteFiles($id, $backup);
            $this->doDelete($id, $flag);
        } else {
            $file = $this->fetch('TABLE', 'id', $id);
            $articleId = $file['article_id'];
            reLocate(ASSETS_EDIT . $articleId, '../../');
        }
    }

    public function reload($assetId = 0)
    {
        $file = $this->fetch('table', 'id', $assetId);
        if ($file) {
            return $this->upload($file->article_id, $file->id, 'reloaded');
        } else {
            reLocate(BADMINTON);
        }
    }

    public function upload($articleId = 0, $assetId = 0, $key = '')
    {
      // dump(func_get_args());

        $files = $this->table->find('article_id', $articleId, null, 0, 0, \PDO::FETCH_ASSOC);
        $file = isset($files[0]) ? $files[0] : $this->table->find('id', $assetId);

        $file = $file ? $this->table->save($file) : $file;
        $title = $file ? $file->getArticle($file->id, 'title') : '';

        $info = array_map(function ($o) {
            list(, $max, $ratio) = $this->getOrientation($o['path']);
            return ['ratio' => $ratio, 'max' => $max];
        }, $files);
        $archived = $this->getOrphans('article_id');
        $reloaded = $key === 'reloaded';
        //force call omitguide to hide upload_guide href=asset/upload/$articleId/omitguide
        $omit = $assetId === 'omitguide' ? ' sansguide' : null;
        $omit = $assetId && is_numeric($assetId) ? ' sansguide' : $omit;
        $omit = $omit ? $omit : (isset($_COOKIE['upload_guide']) ?  ' sansguide' : '');
        $previewklas = !empty($file->article_id) ? 'pic' : '';
        $previewklas .= $omit;
        if ($reloaded) {
            $previewklas .= ' slide';
        }
        return [
            'template' => 'upload.html.php',
            'variables' => [
                'action' => ASSET_ON_UPLOAD . $articleId,
                'exit' => ARTICLES_EDIT . $articleId,
                'filename' => 'uploadfile', //id of file input
                'articleId' => $articleId,
                'previewklas' => $previewklas,
                'files' => $files,
                'info' => $info,
                'ratio' => $this->ratio,
                'warning' => '',
                'key' => $key,
                'mytitle' => $title,
                'accept' => $this->accept,
                'exit_guide' => ASSET_UPLOAD . $articleId . '/omitguide',
                'omitguide' => $omit,
                'reloaded' => $reloaded,
                'page' => $this->getPage($files[0] ?? []), //values required
                'archived' => $archived,
                'assetId' => $assetId && is_numeric($assetId) ? $assetId : null,
                'routes' => ['add' => ASSET_ADD . $articleId, 'assign' => ASSET_ASSIGN . $articleId, 'edit' => ASSET_EDIT . $articleId, 'upload' => ASSET_UPLOAD . $articleId, 'route' => 'upload'],
                'select' => ['options' => $files, 'identity' => 'PATH', 'optval' => 'path', 'target' => null, 'disabled' => false]
            ]
        ];
    }

    //called
    public function add($articleId = 0, $arg = '', $message = '')
    {
        /*
        obtain $asset in order to obtain $article info; default is the first (usually only) $asset 
        after which it can be set to null UNLESS the intention is to replace in which case it is a SIGNAL to provide the primary key in the form
        */
        $asset = $this->fetch('TABLE', 'article_id', $articleId);
        $article = $asset ? $this->getArticle($asset) : $this->table->getEntity()->getArticleDirect($articleId);
        /*
        $arg can be a string corresponding to a db modification or an asset_id
        we reach this point either by directly clicking on an edit link or redirected from the upload form in which case we cannot know which asset we are dealing with
        */

        if ($article) {
            $uploadroute = ASSET_UPLOAD . $articleId;
            $asset = is_numeric($arg) ? $this->fetch('table', 'id', $arg) : null;
            $doreplace = preg_match('/replace/i', $arg) ? 'replace' : '';
            $dir = $asset ? $this->getDirectory($asset) : IMAGES;
            $identity = is_numeric($arg) ? 'PATH' : '';
            return [
                'template' => 'asset.html.php',
                'title' => 'Add an Asset',
                'variables' => [
                    'action' => ASSET_EDIT,
                    'dir' => $dir,
                    'exit' => ARTICLES_EDIT . $articleId,
                    'articleId' => $articleId,
                    'asset' => $asset,
                    'title' => $article->title ?? '',
                    'page' => $article->page ?? '',
                    'message' => $message,
                    'key' => $message,
                    'routes' => ['route' => 'add', 'upload' => $uploadroute, 'add' => ASSET_ADD . $articleId],
                    'select' => ['options' => [], 'target' => null, 'identity' => $identity], //set options to empty to load freetext version of form
                    'replace' => $doreplace,
                    'h3' => $doreplace ? 'Replace Asset' : 'Add Asset'
                ]
            ];
        } else {
            retour();
        }
    }

    public function assign($articleId, $assetId = null, $op = "edit", $key = '')
    {
        if (!$articleId || $op === 'free') {
            return $this->add($articleId, 'add');
        }
        return $this->prepTemplate($articleId, null, $op, $key = '');
    }

    public function confirm($id = 0, $perform = 'archive')
    {
        $file = $this->fetch('table', 'id', $id);
        list($assigned, $archived) = $this->getSubGroups(true);
        $lookup = ['archive' => ASSET_RETIRE, 'delete' => ASSET_DESTROY, 'replace' => ASSET_REPLACE, 'notfound' => ASSET_DESTROY];

        if (isset($file)) {
            return [
                'template' => 'archive.html.php',
                'variables' => [
                    'action' => $lookup[$perform] ?? '',
                    'exit' => ASSETS_EDIT . $file->article_id,
                    'submit' => $perform === 'notfound' ? 'delete' : $perform,
                    'identity' => 'archive',
                    'replace' => empty($archived) ? '' : ASSET_REPLACE,
                    'confirm' => ASSET_CONFIRM,
                    'perform' => $perform,
                    'file' => $file
                ]
            ];
        } else {
            reLocate(ARTICLES_LIST, '../../');
        }
    }
    /*replace can be invoked through a one button form (see immediately above), "action=/asset/replace/id"
    we can't use "action=/asset/edit/id" else it would invoke editSubmit
    so this function is just a bridge/adpater
    */
    public function replace($id = 0)
    {
        list($assigned, $archived) = $this->getSubGroups(true);
        if (empty($archived)) {
            reLocate(ASSET_EDIT . "$id", '../../');
        }
        return $this->edit($id, 'replace');
    }

    public function edit($id = 0, $op = 'Edit', $key = 0)
    {
        $asset = $this->fetch('table', 'id', $id);
        if (!empty($asset)) {
            return $this->prepTemplate($asset->article_id, $id, $op, $key);
        } else {
            reLocate(ASSET_LIST, '../../');
        }
    }

    private function insertAllowed($subject, $replace)
    {
        return true;
        $ret = true;
        if (!empty($subject['id'])) {
            $file = $this->fetch('table', 'id', $subject['id']);
            $ret = $file->validate($subject['article_id'], $file->id, '/\.section/') && $replace;
        } else if (!$replace) {
            $file = $this->table->save($subject);
            if (!$file->validate($subject['article_id'], $file->id, '/\.section/', true)) {
                $ret = false;
            }
            $this->table->delete('id', $file->id);
        }
        return $ret;
    }

    public function editSubmit()
    {
        if (empty($_POST)) {
            reLocate(REG);
        }
        $id = $_POST['pk'] ?? null;
        $orphanCount = $_POST['orphans'] ?? 0;
        $resident = $this->fetch('TABLE', 'id', $id);
        $orphan = $this->fetch('TABLE', 'id', $_POST['data']['path']);
        $archived = $orphan ? 'archived' : 'library';
        $replace = isset($_POST['replace']) && $orphan;
        $record = null;
        $relocate = false;
        $candidate = [];

        if ($resident) {
            //free text updates
            if (!$orphan && !$orphanCount) {
                $candidate = $this->fetch('TABLE', 'path', $_POST['data']['path']);
                if (isset($candidate) && $candidate['id'] !== $resident['id']) {
                    $id = $resident['id'];
                    $key = $resident['article_id'] === $candidate['article_id'] ? 'article_self' : 'article_other';
                    if (!empty($candidate['article_id'])) {
                        reLocate(ASSET_EDIT . "$id/edit/$key", '../../');
                    }
                }
                if (!empty($candidate)) {
                    //$self is a flag that allows for CLEARING an attribute
                    $self = $candidate['id'] === $resident['id'];
                    $record = $this->validateUpdate($candidate, $resident, ['alt', 'ATTR_ID'], $self);
                    $archived = 'uploaded'; //just editing
                } else {
                    $record = $this->reinstate($_POST['data'], $resident);
                    $record['id'] = $record['id'] ?? $id;
                }
                if (isset($record)) {
                    $record['article_id'] = isset($_POST['assign']) ? $_POST['data']['article_id'] : null;
                }
            }
            if (!isset($record)) { //no activity above
                if ($orphan && $orphanCount) {
                    $orphan['article_id'] = $_POST['data']['article_id'];
                    $record = $this->validateUpdate($orphan, $resident, ['alt', 'ATTR_ID']);
                    $archived = 'uploaded';
                    if ($this->insertAllowed($resident, $replace)) {
                        $rid = $resident['id'];
                        $this->doReplace($resident, $orphan, ASSET_EDIT . "$rid/edit/ext", $replace);
                    } else {
                        $relocate = true;
                    }
                }
                if (!isset($record)) { //still no activity, just editing attributes
                    $archived = 'uploaded';
                    $record = $this->setAttributes($resident ?? $_POST['data'], $_POST['assign'] ?? null);
                    $record['id'] = $record['id'] ?? $id;
                }
            }
        } else { //not resident
            //may have been redirected from upload form where no resident, ajs??
            if ($orphan && $orphanCount) {
                $orphan['article_id'] = $_POST['data']['article_id'];
                $record = $this->validateUpdate($orphan, $resident, ['alt', 'ATTR_ID']);
            } else {
                $record = $this->reinstate($_POST['data'], $this->setAttributes($_POST['data'], $_POST['assign'] ?? null));
            }
            $relocate = $relocate ? $relocate : !$this->insertAllowed($record, $replace);
        }

        if ($relocate) {
            $id = $_POST['data']['article_id'];
            reLocate(ASSETS_EDIT . "$id/allowed", '../../');
            exit;
        }
        $record['date'] = $record['date'] ?? date('Y-m-d');
        $pp = $this->doGetPage($_POST['data'], $record['id']);
        return $this->save($record, $pp, $archived);
    }
    //not used??
    public function getAsset($id = 0)
    {
        return $this->fetch('table', 'id', $id);
    }
    //keep pic in db remove ref to article; action ("asset/retire/") passed to delete form
    public function retireSubmit($id = 0)
    {
        $asset = $this->fetch('TABLE', 'id', $id);
        if ($asset) {
            $this->archive($asset, true);
            reLocate(ARTICLES_EDIT . $asset['article_id'], '../../');
        }
        reLocate(ARTICLES_LIST, '../../');
    }

    public function manage($col = null)
    {
        return [
            'template' => 'orphans.html.php',
            'variables' => [
                'group' => $this->getOrphans($col),
                'id' => $col,
                'dir' => 'resources/images/articles/fullsize/',
                'action' =>  $this->getClassName('/')
            ]
        ];
    }

    public function manageSubmit()
    {
        if (!empty($_POST)) {
            $backup = isset($_POST['backup']);
            $archived = isset($_POST['all']) ? $this->getOrphans('article_id') : [];
            $pics = isset($_POST['pics']) ? $_POST['pics'] : array_map(fn ($o) => $o->id, $archived);
            foreach ($pics as $k) {
                $pic = $this->fetch('table', 'id', $k);
                if ($pic && isset($pic->path)) {
                    $this->destroy($k, $backup, true);
                }
            }
            reLocate(ARTICLES_LIST, '../../');
        } else {
            retour();
        }
    }
}
