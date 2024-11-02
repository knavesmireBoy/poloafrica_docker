<?php

namespace PoloAfrica\Controllers;

include_once 'config.php';

use \Ninja\DatabaseTable;
use \Ninja\Composite\Leaf;

class Article
{
    private $count = 0;
    private $paginate = [];
    public $mdcontent = '';

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

    protected function getAccess($i)
    {
        //2 'Content Editors' //4 'Photo Editors' 
        $lib = [1 => 'Registered Users', 2 => 'Content Editors', 4 => 'Photo Editors'];
        return isset($lib[$i]) ? $lib[$i] : 'Account Administrators';
    }

    private function doPaginate()
    {
        $p = [];
        $tmp = [];
        $inc = $this->inc;
        for ($i = 1, $s = 0; $s < $this->count; $s += $inc) {
            $tmp[] = $i;
            $tmp[] = $s;
            $p[] = $tmp;
            $tmp = [];
            $i++;
        }
        return $p;
    }

    private function getPrev($pp)
    {
        $prev = $pp - 1;
        return $prev > 0 ? $prev : null;
    }

    private function getNext($pp)
    {
        $next = $pp + 1;
        return $next <= intval($this->validateFolio()) ? $next : null;
    }

    private function uniq($array)
    {
        $uniq = array_unique($array);
        return array_filter($uniq, fn($o) => $o);
    }

    private function unsetPage($page)
    {
        $pass = is_bool($page);
        $cookie =  isset($_COOKIE['page']) ?  $page === $_COOKIE['page'] : null;
        if ($pass || $cookie) {
            unset($_COOKIE['page']);
            setcookie('page', '', -1, '/');
        }
    }

    private function getPageList($active = false)
    {
        $articles = $this->table->findAll(null, 0, 0, \PDO::FETCH_ASSOC);
        $slot = $this->table->save($articles[0]);
        $slot->setName('pp');
        $articles = array_map(fn($o) => $o['page'], $articles);
        $pages = array_map(fn($o) => $o->title, $slot->findAll('id'));
        if ($active) {
            $articles = $this->uniq($articles);
            $pp = getDiff($pages, $articles);
            $pages = empty($pp) ? $pages : $articles;
        }
        $pp = [];
        foreach ($pages as $p) {
            $tmp = [];
            $tmp['id'] = $p;
            $tmp['page'] = ucfirst($p);
            $pp[] = $tmp;
        }
        return $pp;
    }

    private function prepTemplate($files, $prev, $next, $key, $alt = false)
    {
        $archived = [];
        $pp = [];
        $offset = 0;
        if (is_numeric($key) && !empty($this->paginate)) {
            list($folio, $offset) = $this->paginate[$key];
        }
        $active = $files;
        $archived = $this->table->filterNull('page', true);
        $pp = $this->getPageList(true);
        if ($alt) {
            $active = $this->table->filterNull('page', true);
        }
        return [
            'template' => 'articles.html.php',
            'title' => 'Articles List',
            'variables' => [
                'klas' => '',
                //'action' => $alt ? ARTICLES_RESTORE : ARTICLES_CONFIRM,
                'action' => ARTICLES_CONFIRM,
                'exit' => BADMINTON,
                'target' => '',
                'files' => $active,
                'archived' => $alt ? [] : $archived,
                'prev' => $prev,
                'next' => $next,
                'paginate' => $this->paginate,
                'offset' => $offset ?? null,
                'increment' => $this->inc,
                'perform' => $alt ? 'destroy' : 'delete',
                //'perform' => 'delete',
                'page' => isset($pp[0]) ? $pp[0]['id'] : 0,
                'select' => ['target' => null, 'identity' => 'pp', 'optval' => 'page', 'options' => $alt ? [] : $pp, 'default' => 'pubDate']
            ]
        ];
    }

    private function prepEditTemplate($article, $route, $edit = 'Edit')
    {
        $pp = '';
        $page = '';
        $max = 0;
        $upload = '';
        $pp = $this->getPageList();
        $action = ARTICLES_EDIT;
        $exit = ARTICLES_LIST;
        if ($article) {
            $page = $_COOKIE['page'] ?? $article->page ?? '';
            $max = count($this->table->find('page', $page));
            $upload = isset($article) ? ASSET_UPLOAD . $article->id : '';
            $action = ARTICLES_EDIT . $article->id;
            $exit = ARTICLES_LIST . strtolower($article->title ?? '');
        }
        return [
            'template' => 'edit.html.php',
            'title' => "$edit Article",
            'variables' => [
                'action' => $action,
                'exit' => $exit,
                'article' => $article,
                'route' => $route,
                'max' => $max,
                'upload' => $upload,
                //'required' => 'page',
                'select' => ['target' => $page, 'identity' => 'page', 'optval' => 'page', 'options' => $pp, 'default' => ''],
                'submit' => 'submit'
            ]
        ];
    }

    private function validateFolio()
    {
        return ceil($this->count / $this->inc);
    }

    private function listByPage($str, $limit = 0, $offset = 0)
    {
        return $this->table->find('page', $str, null, $limit, $offset);
    }

    private function confirmAction($perform)
    {
        if ($perform === 'unarchive') {
            return ARTICLES_RESTORE;
        }
        $action = $perform === 'archive' ? ARTICLES_RETIRE : ARTICLES_DEL;
        return $perform === 'destroy' ? ARTICLES_DESTROY : $action;
    }

    private function domove($id, $title, $pages, $flag = false)
    {
        if ($flag) {
            return $this->move($id, $flag);
        }
        list($source, $target) = $pages;
        return [
            'template' => 'move.html.php',
            'variables' => [
                'action' => ARTICLES_MOVE . $id,
                'exit' => BADMINTON,
                'submit' => 'submit',
                'identity' => 'move',
                'source' => $source,
                'target' => $target,
                'id' => $id,
                'title' => $title
            ]
        ];
    }

    protected function order()
    {
        $label = $_POST['mytitle'];
        $destinationID = intval($_POST['position'] - 1);
        $shuffle = isset($_POST['shuffle']);
        $article = $this->fetch('table', 'id', $_POST['pk']);
        $article->setName($_POST['mypage']);
        $res = false;

        if ($article) {
            if ($shuffle) {
                $res = $article->shuffle($destinationID, $label);
            } else {
                $res = $article->swap($destinationID, $label);
            }
        }
        if ($res) {
            //need to reload
            $page = $article->getName();
            reLocate(RELOAD . $page);
            //retour();
        }
        reLocate(ARTICLES_LIST, '../../');
    }

    private function isSection($article)
    {
        return preg_match('/section/', $article->attr_id);
    }

    //ensure public (form action) but DON'T add submit as we need to call it two ways
    public function move($id = 0, $flag = false)
    {
        $article = $this->fetch('TABLE', 'id', $id);

        if ($article) {
            $oldpp = $article['page'];
            $source = $this->table->save($article);
            $source->setName($oldpp);
            $list = array_map(fn($o) => $o->title, $source->findAll('id'));
            $data = array_filter($list, fn($o) => $o != $article['title']);
            $source->repop($data);
            if (!$flag) { //posted by move_submit
                $article['page'] = $_POST['page'];
                $this->table->save($article);
            }
            reLocate(ARTICLES_LIST, '../../');
        }
    }

    private function pageCheck($payload)
    {
        $entity = $this->fetch('table', 'id', $payload['id']);

        if (empty($entity->page)) {
            return empty($payload['page']) ? [] : [$payload['page']]; //unarchive
        } else {
            return equals($entity->page, $payload['page']) ? [] : [$entity->page, $payload['page']];
        }
    }

    private function findFolio($arg)
    {
        //$files = $this->table->findAll('title');
        $files = $this->table->filterNull('page', false, 'title');
        $titles = array_map(fn($o) => $o->title, $files);
        if (is_numeric($arg)) {
            $article = $this->fetch('table', 'id', $arg);
        } else if ($arg) {
            $article = $this->fetch('table', 'title', $arg);
        }
        if (isset($article)) {
            $index = array_search($article->title, $titles);
            if ($index >= 0) {
                for ($k = 0; $k <= $index; $k += $this->inc) {
                }
                return $k /= $this->inc;
            }
        }
        return 0;
    }

    private function cleanRefs($content)
    {
        //https://stackoverflow.com/questions/38064731/regex-to-trim-spaces-from-the-end-of-unknown-length-strings-of-words
        $res = preg_split('|\n|', $content);
        $res = array_map('trim', $res);
        return implode("\n", $res);
    }

    private function prepPaginationBar($files, $folio)
    {
        $prev = null;
        $next = null;
        $key = 0;
        $offset = 0;
        if (is_integer($folio) && $folio >= 0) {
            $this->setCount($files);
            $this->paginate = $this->doPaginate();
            $prev = $this->getPrev($folio) ?? null;
            $next = $this->getNext($folio) ?? null;
            $key = intval($folio - 1);
            $offset = $this->paginate[$key][1] ?? 0;
        }
        return [$key, $prev, $next, $offset];
    }

    private function setCount($files)
    {
        $this->count = is_array($files) ? count($files) : 0;
    }

    private function setInc($inc = 10)
    {
        $this->inc = $inc;
    }

    public function __construct(private DatabaseTable $table,  private int $inc = 10)
    {
        $this->table = $table;
        $files = $table->findAll();
        $active = array_filter($files, fn($o) => $o->page);
        $this->setCount($active);
    }

    public function edit($id = 0, $flag = false)
    {


        if (!is_numeric($id)) {
            $article = $this->fetch('table', 'title', urldecode(strtolower($id)));
        } else {
            $article = $this->fetch('table', 'id', $id);
        }

        if (!empty($article)) {
            $route = ASSETS_EDIT . $article->id;
            return $this->prepEditTemplate($article, $route, 'Edit');
        } else {
            return $this->prepEditTemplate(null, '#', 'Add');
        }
    }

    public function editSubmit($id = 0)
    {
        if (empty($_POST)) {
            retour();
        }
        $date = date('Y-m-d');
        $id = $_POST['pk'] ?? null;
        $attr = preg_replace('/\s/', '&nbsp;', $_POST['attr_id']);
        $payload = ['id' => $id, 'title' => $_POST['title'], 'pubDate' => $date, 'page' => $_POST['page'], 'summary' => $_POST['summary'], 'content' => $_POST['content'], 'attr_id' => $attr];
        $pagestatus = $this->pageCheck($payload);


        $payload['page'] = empty($pagestatus) ? $payload['page'] : $pagestatus[0];

        $payload['content'] = $this->cleanRefs($payload['content']);
        $entity = $this->fetch('table', 'title', $payload['title']);

        if (isset($entity) && empty($id)) {
            $feedback = '/!cannot add article; article title is already in use.';
            reLocate(BADMINTON . $feedback, '../../');
        }
        //MUST be NULL not JUST empty for MYSQL; FOREIGN CONSTRAINT
        $payload['page'] = empty($payload['page']) ? NULL : $payload['page'];
        $entity = $this->table->save($payload);

        if (empty($pagestatus) && !empty($_POST['position'])) {
            return $this->order($_POST['position']);
        }
        if (empty($pagestatus) && !empty($payload['page'])) {
            reLocate(ARTICLES_LIST . $this->findFolio($id), '../../');
        }
        //adding;unarchiving
        if (!empty($pagestatus[0]) && empty($pagestatus[1])) {
            $test = $this->table->find('page', $pagestatus[0]);
            if (isset($test[1])) {
                reLocate(ARTICLES_LIST, '../../');
            } else {
                //if populating page for the FIRST time..
                unset($_SESSION['nav']);
                retour();
            }
        } else if (!empty($pagestatus[1])) {
            return $this->domove($payload['id'], $payload['title'], $pagestatus, empty($pagestatus[1]));
        }
        /*
        decide we can have a NEW article and NOT assign it to a page
        if (empty($pagestatus) && empty($payload['page'])) {
            $feedback = '!please assign a page to the article';
         reLocate(BADMINTON  . $feedback, '../../');
        }
         */
        reLocate(BADMINTON, '../../');
    }

    //accessed from below EDIT ARTICLE FORM @parm ARTICLE ID
    public function assets($id = 0, $key = '')
    {
        $article = $this->fetch('table', 'id', $id);
        $assets = isset($article) ? $article->getAssets($id, fn($item) => $item) : null;
        if (!empty($assets)) {
            return [
                'template' => 'assetlist.html.php',
                'title' => 'Article Assets',
                'variables' => [
                    'exit' => ARTICLES_EDIT . $id,
                    'files' => $assets,
                    'target' => null,
                    'articleId' => $id,
                    'page' => $article->page,
                    'routes' => ['assign' => ASSET_ASSIGN . $id, 'edit' => ASSET_EDIT, 'action' => ASSET_CONFIRM],
                    'submit' => 'submit',
                    'sale' => 'sale plus'
                ]
            ];
        } else {

            if ($article) {
                $orphans = $article->getArchived();
                $upload = ASSET_UPLOAD . $id;
                return [
                    'template' => 'asset.html.php',
                    'title' => 'Add an Asset',
                    'variables' => [
                        'action' => ASSET_EDIT,
                        'dir' => ARTICLE_IMG,
                        'exit' => ARTICLES_LIST . $article->page,
                        'articleId' => $id,
                        'title' => $article->title ?? '',
                        'routes' => ['route' => 'add', 'add' => ASSET_ADD, 'assign' => ASSET_ASSIGN, 'edit' => ASSET_EDIT, 'upload' => $upload],
                        'select' => ['options' => $orphans, 'target' => null, 'identity' => 'PATH', 'optval' => 'path'],
                        'replace' => '',
                        'key' => $key,
                        'h3' => 'Add Asset'
                    ]
                ];
            } else {
                retour();
            }
        }
    }

    public function retireSubmit($id = 0)
    {
        if (empty($_POST)) {
            retour();
        }
        if (!empty($_POST['cancel'])) {
            reLocate(ARTICLES_LIST, '../../');
        }
        $record = $this->fetch('TABLE', 'id', $id);
        $page = $record['page'];
        $record['page'] = NULL;
        $article = $this->table->save($record);
        $article->setName($page);
        $this->unsetPage($page);
        //below code required when archiving; when deleting a record the database does the work (FK's)
        if (isset($_POST['child'])) {
            $article->archiveAssets($record['id'], fn($o) => true);
        }
        $list = array_map(fn($o) => $o->title, $article->findAll('id'));
        $data = array_filter($list, fn($o) => $o != $article->title);
        $article->repop($data, empty($data));
        if (empty($data)) {
            retour();
        }
        reLocate(ARTICLES_LIST, '../../');
    }

    public function restore($id = 0)
    {
        return $this->edit($id);
    }

    public function list($folio)
    {
        $by = $_COOKIE['date'] ?? 'title';
        $pp = $_COOKIE['page'] ?? '';
        $offset = 0;
        $key = 0;
        $prev = null;
        $next = null;
        //-1 list archived files don't attempt to paginate (at this point)
        $alt = is_numeric($folio) && intval($folio) < 0;
        $files = [];
        $records = $_COOKIE['records'] ?? $this->inc;

        $this->setInc($records);
        if (isset($_POST['records'])) {
            $this->setInc(intval($_POST['records']));
            unset($_COOKIE['records']);
            setcookie('records', $this->inc, -1, '/');
        }
        if ($folio === 'clear') {
            $this->unsetPage(true);
            $folio = 1;
            $pp = null;
        }
        $range = $this->validateFolio();

        if (!$alt && isset($_POST['pp']) && $_POST['pp'] === 'pubDate') {
            $by = 'pubDate';
        }

        if (!empty($_POST['pp']) || !empty($pp)) {
            $pp = isset($_POST['pp']) && $_POST['pp'] !== 'pubDate' ? $_POST['pp'] : $pp;
            if (!empty($pp)) {
                $files = $this->listByPage($pp);
                //page may no longer have articles
                if (empty($files)) {
                    $this->unsetPage(true);
                } else {
                    setcookie('page', $pp, -1, '/');
                    $_COOKIE['page'] = $pp;
                }
                if (count($files) > $this->inc) {
                    list($key, $prev, $next, $offset) = $this->prepPaginationBar($files, $folio);
                    $files = $this->listByPage($pp, $this->inc, $offset);
                }
            } else {
                if (!isset($_COOKIE['date'])) {
                    setcookie('date', 'pubDate', time() + 10, '/');
                    $_COOKIE['date'] = 'pubDate';
                }
            }
        }
        if (empty($files)) { //not by page
            $files = $alt ? $this->table->filterNull('page', true, $by) : $this->table->filterNull('page', false, $by);
            if ($folio && !is_numeric($folio)) {
                //redirect to origin page
                $folio = $this->findFolio(ucwords(urldecode($folio)));
            }
            if (!$alt && empty($folio)) {
                $folio = 1;
            }
            $folio = ($folio > $range || $folio < 1) ? 1 : $folio;
            if (!$alt) {
                list($key, $prev, $next, $offset) = $this->prepPaginationBar($files, intval($folio));
                $files = $this->table->filterNull('page', false, $by, $this->inc, $offset);
            }
        }
        return $this->prepTemplate($files, $prev, $next, $key, $alt);
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

    public function destroy()
    {
        return $this->delete();
    }

    public function delete()
    {
        if (!empty($_POST['cancel'])) {
            reLocate(ARTICLES_LIST, '../../');
        }
        if (isset($_POST['pk'])) {
            $article = $this->fetch('TABLE', 'id', $_POST['pk']);
            if ($article) {
                if ($article['page']) {
                    $source = $this->table->save($article);
                    $this->unsetPage($article['page']);
                    $source->setName($article['page']);
                }
                $this->table->delete('id', $_POST['pk']);
                reLocate(ARTICLES_LIST, '../../');
            }
        } else {
            retour();
        }
        exit;
    }

    public function confirm($id = 0, $perform = 'archive')
    {
        $file = $this->fetch('table', 'id', $id);
        $action = $this->confirmAction($perform);

        if (isset($file)) {
            $entity = $this->table->save($this->fetch('TABLE', 'id', $id));
            $assets = $entity->getAssets($file->id, fn() => true);
            return [
                'template' => 'archive.html.php',
                'variables' => [
                    'action' => $action,
                    'exit' => BADMINTON,
                    'submit' => $perform,
                    //'submit' => 'submit',
                    'perform' => $perform,
                    'identity' => 'delete',
                    'confirm' => ARTICLES_CONFIRM,
                    'file' => $file,
                    'assets' => $perform === 'archive' && !empty($assets) ? true : false,
                    'record' => $file->title
                ]
            ];
        } else {
            retour();
        }
    }
}
