<?php

namespace PoloAfrica;

use \Ninja\Website;
use \Ninja\DatabaseTable;
use \Ninja\Authentication;
use \PoloAfrica\Controllers\Pages;

class PoloAfricaWebsite implements Website
{
    private $articleTable;
    private $userTable;
    private $assetTable;
    private $slotTable;
    private $boxTable;
    private $galleryTable;
    private $pdo;
    private $pagesTable;
    private $authentication;
    private $pages;

    public function getDefaultRoute(): string
    {
        return 'home';
    }
    public function __construct(private $pp)
    {
        $pwd = $_ENV['MYSQL_PASSWORD'];
        $user = $_ENV['MYSQL_USER'];
        $dbname = $_ENV['MYSQL_DATABASE'];
        $this->pdo = new \PDO(
            //'mysql:host=localhost;dbname=polafrica;charset=utf8mb4',
            "mysql:host=mydb;dbname=$dbname;charset=utf8mb4",
            $user,
            $pwd
        );

        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('SET NAMES "utf8"');
        $this->userTable = new DatabaseTable($this->pdo, 'user', 'id', '\PoloAfrica\Entity\User', [$this->userTable]);
        //NOTE we would (possibly) need a join table if article-asset was N:N, it is in the DB from a previous incarnation
        //$this->article_asset = new DatabaseTable($this->pdo, 'article_asset', 'article_id');
        $this->pagesTable = new DatabaseTable($this->pdo, 'pages', 'id', '\PoloAfrica\Entity\Page', [&$this->slotTable]);
        $this->slotTable = new DatabaseTable($this->pdo, $pp, 'id', '\PoloAfrica\Entity\Slot', [&$this->slotTable]);
        $this->assetTable = new DatabaseTable($this->pdo, 'assets', 'id', '\PoloAfrica\Entity\Asset', [&$this->assetTable, &$this->articleTable]);
        $this->articleTable = new DatabaseTable($this->pdo, 'articles', 'id', '\PoloAfrica\Entity\Article', [$this->assetTable, $this->slotTable]);
        $this->authentication = new Authentication($this->userTable, 'email', 'password');
        $this->boxTable = new DatabaseTable($this->pdo, 'slot', 'id');
        $this->galleryTable = new DatabaseTable($this->pdo, 'gallery', 'id', '\PoloAfrica\Entity\Gallery', [$this->boxTable]);
        $this->pages = array_map(fn ($o) => strtolower($o->name), $this->pagesTable->findAll());
    }

    private function validate($key, $array)
    {
        $k = ($key === 'logger') ? 'login' : $key;
        return in_array($k, $array) ? $k : null;
    }

    private function withTrim($uri, $str)
    {
        return equals($uri, $str) || equals($uri, ltrim($str, '/')) || equals($uri, rtrim($str, '/'));
    }

    private function queryUri($uri)
    {
        return $this->withTrim($uri, BADMINTON) || $this->withTrim($uri, USER_LIST);
    }

    private function factory(string $id, array $args)
    {
        $controllers = [
            'article',
            'asset',
            'user',
            'login',
            'gallery',
            'pages'
        ];
        //https://stackoverflow.com/questions/534159/instantiate-a-class-from-a-variable-in-php#:~:text=Put%20the%20classname%20into%20a,%24classname(%22xyz%22)%3B
        $key = $this->validate($id, $controllers);
        if ($key) {
            $klas = "PoloAfrica\\Controllers\\" . ucwords($key);
            return new $klas(...$args);
        }
    }

    private function build(string $name, array $mandatory, array $optional, array $user)
    {
        $id = array_pop($user) ?? $name;
        $id = ($id === $name) ? $id : $name;
        return $this->factory($id, [...$mandatory, ...$optional, ...$user]);
    }

    private function ensureArray($arr)
    {
        return is_array($arr) ? $arr : [];
    }

    //recur
    private function list($pagedata, $pagenames, $ret, $i, $j)
    {
        if (isset($pagedata[$i]) && isset($pagenames[$j])) {
            $tgt = $pagenames[$j];
            //iterate until you find KEY to title
            if ($pagedata[$i]['name'] === $tgt) {
                $ret[] = $pagedata[$i]['title'];
                $j += 1; //advance
                $i = 0; //reset
                return $this->list($pagedata, $pagenames, $ret, $i, $j);
            } else {
                //increment $pagedata
                return $this->list($pagedata, $pagenames, $ret, $i += 1, $j);
            }
        } else {
            return $ret;
        }
    }

    public function setNavBar(): array
    {
        $pagedata = $this->pagesTable->findAll(null, 0, 0, \PDO::FETCH_ASSOC);
        $e = $this->pagesTable->getEntity();
        $e->setName('pp');
        $pagenames = array_map(fn ($arr) => $arr['title'], $e->findAll('id', 0, 0, \PDO::FETCH_ASSOC));
        //assumes $pagedata and $pagenames are same length!; $pagenames is effectively generated by $pagedata
        $pagetitles = $this->list($pagedata, $pagenames, [], 0, 0);
        return [$pagenames, $pagetitles];
    }

    public function getController(string $name = '', array $args = [], array $user_args = []): ?object
    {
        $defaultArgs = [
            'logger' => [$this->authentication],
            'user' => [$this->userTable/*, $this->userTable*/],
            'article' => [$this->articleTable],
            'asset' => [$this->assetTable],
            'gallery' => [$this->galleryTable, $this->boxTable]
        ];

        if (isset($defaultArgs[$name])) {
            $args = $this->ensureArray($args);
            $user_args = $this->ensureArray($user_args);
            return $this->build($name, $defaultArgs[$name], $args, $user_args);
        }
        return new Pages($this->pagesTable, $this->articleTable, $this, $name);
    }

    public function getScripts($key = ''): array
    {
        //note mis-spelling scripts results in: SyntaxError: Unexpected token '<'
        /*issue with ajax and cancel &cancel=cancel, need to find a way to determine
        when form submission was cancelled Probably click handler util then we prevent ajax
        on forms with a cancel option which means we have to reload JS for some routes
        UPDATE  dispense with cancel button, but, ensure we have a back button.
        */
        $js = ['viewport', 'meta', 'utils'];
        $admin = array_merge($js, ['present', 'markup', 'admin']);
        $gallery = array_merge($js, ['iterator', 'tooltips', 'publisher',  'painter', 'slideshow', 'present', 'gallery'/*, 'cacher'*/]);
        $scripts =  [
            'user/admin' => $admin,
            'logger/reg' => $admin,
            'logger/login' => $admin,
            'logger/loginSubmit' => $admin,
            'user/list' => $admin,
            'article/list' => $admin,
            'article/edit' => $admin,
            'article/assets' => $admin,
            'pages/list' => $admin,
            'gallery/review' => $admin,
            'gallery/display' => $gallery,
            //below three gallery/x lines would only be neccesary if JS was enabled AFTER a selection was made from the gallery
            'gallery/loadpic' => $gallery,
            'gallery/next' => $gallery,
            'gallery/prev' => $gallery,
            'home/display' => [...$js, 'homealone', 'present', 'ajax']
        ];
        return $scripts[$key] ?? (strpos($key, 'display') ?  [...$js, 'present', 'ajax'] : []);
    }

    public function getControllerArgs($k): array
    {
        $gallery_map = [[14, 0], [14, 14], [14, 28], [12, 42], [12, 54], [12, 66], [14, 78]];
        $accept_asset = 'accept="image/*, video/*,application/pdf"';
        $gallery_accept = 'accept="image/*"';
        $loader_args = ['application' => [ASSETS], 'image' => [ARTICLE_IMG, ARTICLE_THUMB], 'video' => [VIDEO_PATH]];
        $gallery_args = ['image' => [GALLERY, GALLERY_THUMBS, 1.5]];

        $lib = [
            'gallery' => [$gallery_map, $gallery_accept, $gallery_args],
            'article' => [10],
            'asset' => [$accept_asset, $loader_args]
        ];
        return isset($lib[$k]) ? $lib[$k] : [];
    }

    public function getLayoutVariables($key): array
    {
        $user = $this->authentication->isLoggedIn();

        if ($key === 'login') {
            return ['title' => 'Admin', 'loggedIn' => $user, 'user' => $user->name ?? ''];
        }
        $page = explode('/', $key);
        $gal = 'gallery';

        $defs = ['klas' => '', 'user' => $user->name ?? '', 'adminpage' => ''];
        $pp = ['adminpage' => true];
        $lookup = [
            'user/register' => ['title' => 'Admin', ...$defs],
            'article/list' => ['title' => 'Admin', ...$defs, ...$pp],
            'pages/list' => ['title' => 'Admin', ...$defs, ...$pp],
            'gallery/display' => ['title' => 'photos', ...$defs, 'klas' => 'public'],
            'gallery/nextpage' => ['title' => 'photos', ...$defs],
            'gallery/prevpage' => ['title' => 'photos', ...$defs],
            'gallery/loadpic' => ['title' => 'photos', 'klas' => 'showtime'],
            'gallery/next' => ['title' => 'photos', 'klas' => 'showtime'],
            'gallery/prev' => ['title' => 'photos', 'klas' => 'showtime']
        ];

        if ($page[0] === 'gallery') {
            if (empty($lookup[$key])) {
                $gal = '';
            }
        }
        $klas = in_array($page[0], [...$this->pages, $gal]) ? 'public' : '';
        $title = $klas ? $page[0] : 'Admin';
        return isset($lookup[$key]) ? $lookup[$key] : ['title' => $title, 'klas' => $klas, 'user' => $user->name ?? '', 'adminpage' => !$klas];
    }

    public function reroute($uri, int $acceslevel, string $flag = '')
    {
        $route = explode('/', $uri);
        $name = $flag ? $flag : $route[0];
        $action = $route[1];
        //$acceslevel will determine the feedback message supplied to acccessdenied.html.php
        $args = "!$action/$acceslevel";
        //CRUCIAL set $route to lowercase otherwise it falls foul of EntryPoint::checkUri
        $route = strtolower($name . '/message/' . $args);
        reLocate("/$route", '../');
    }

    public function checkLogin(string $uri): array
    {
        $user = $this->authentication->isLoggedIn();
        $key = '';
        $reroute = partial([$this, 'reroute'], $uri);
        $browser = \PoloAfrica\Entity\User::BROWSER;
        $content = \PoloAfrica\Entity\User::CONTENT_EDITOR;
        $photo = \PoloAfrica\Entity\User::PHOTO_EDITOR;
        $chief = \PoloAfrica\Entity\User::CHIEF_EDITOR;
        $account = \PoloAfrica\Entity\User::ACCOUNT_EDITOR;
        $permit = $user ? intval($user->permissions) : 0;
        $tmp = ['user/edit' => $account,  'user/list' => $account, 'user/edit' => $account, 'gallery/manage' => $photo];
        $post_access = ['user/success' => $browser, 'user/haspermission' => $browser];
        //'user/register' => $browser,
        $actions = [
            'user/confirm' => $account, 'user/permissions' => $account, 'user/changepassword' =>  $browser, 'user/changeemail' =>  $browser, 'article/list' => $content, 'user/forgot' =>  $browser, 'article/edit' => $content, 'article/confirm' => $content, 'article/delete' => $content, 'article/move' => $content, 'article/restore' => $content, 'article/assets' => $content,
            'gallery/review' => $photo, 'gallery/add' => $photo, 'gallery/upload' => $photo, 'gallery/edit' => $photo, 'gallery/destroy' => $photo, 'gallery/reload' => $photo, 'gallery/assign' => $photo, 'gallery/manage' => $photo, 'asset/upload' => $content, 'asset/delete' => $content, 'asset/manage' => $content, 'asset/edit' => $content, 'asset/confirm' => $content, 'asset/assign' => $content, 'asset/add' => $content, 'asset/reload' => $content, 'pages/list' => $content, 'pages/edit' => $content, 'pages/delete' => $chief, 'pages/confirm' => $chief, 'pages/add' => $content, 'pages/approve' => $chief
        ];
        //browser is simply a registered user who has access to privileged PUBLIC content
        //for instance slideshow
        $gal = [
            'gallery/display' => $browser, 'gallery/loadpic' => $browser,
            'gallery/next' => $browser, 'gallery/nextpage' => $browser,
            'gallery/prev' => $browser, 'gallery/prevpage' => $browser
        ];

       // $actions = array_merge($actions, $gal);
        if (isset($post_access[$uri])) {
           // reLocate(BADMINTON, '../../');
        }

        if (!$user/* && !strpos($uri, 'display')*/) {
            if ($this->queryUri($uri) || isset($actions[$uri])) {
                isset($actions[$uri]) ? $reroute($actions[$uri]) : reLocate(REG . 'gebruiker');
            }
        } else {
            if (isset($actions[$uri]) && !$user->hasPermission($actions[$uri])) {
                $reroute($actions[$uri], 'user');
            }
        }
        $ret = $user ? [$user, $permit, $key] : [''];
        //don't send empty args
        return array_filter($ret, fn ($o) => $o);
    }

    public function create($name): void
    {
        try {
            $fk = $name . '_fk';

            $sql = "CREATE TABLE IF NOT EXISTS $name (
                `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
                `title` varchar(255) NOT NULL,
                PRIMARY KEY (`id`,`title`),
                CONSTRAINT `$fk` FOREIGN KEY (`title`) REFERENCES `articles` (`title`) ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci";
            $this->pdo->exec($sql);
            //unset($_SESSION['nav']);
        } catch (\PDOException $e) {
            $output = 'Error creating table: ' . $e->getMessage();
            exit();
        }
    }

    public function drop($name)
    {
        try {
            $sql = "DROP TABLE IF EXISTS $name";
            $this->pdo->exec($sql);
            $i = count($this->pagesTable->findAll());
            $sql = "ALTER TABLE pages AUTO_INCREMENT = $i";
            $this->pdo->exec($sql);
        } catch (\PDOException $e) {
            $output = 'Error deleting table: ' . $e->getMessage();
            exit();
        }
    }
}
