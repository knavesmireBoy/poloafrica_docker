<?php
require_once 'config.php';
include FUNCTIONS;
//phpinfo();
$layout = 'pagelayout.html.php';
$route = fixUri();

$uri = empty($route) ? '' : implode('/', $route);

//array of "actions" which need submit adding to string for processing forms; eg assignSubmit
$posts = ['assign', 'create', 'contact', 'edit', 'login', 'manage', 'permissions', 'register', 'retire', 'unarchive', 'relocate', 'swap'];

$pp = $pages[$route[0]] ?? '';
$entryPoint = new \Ninja\EntryPoint(new \PoloAfrica\PoloAfricaWebsite($pp), $posts);
$layoutVariables = $entryPoint->run($uri, $_SERVER['REQUEST_METHOD'], 'public');
echo $entryPoint->loadTemplate($layout, $layoutVariables);