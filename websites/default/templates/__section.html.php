<?php

$incfile = '';
$mykey = '';
$flush = false;
$myasset = $article->assets[0] ?? null;
$myhead = preg_match('/^<h\d>.+/', $article->summary);
include '_accordion.html.php';

if (isset($article->assets[0])) {//if article has assets;
  foreach ($article->assets as $k => $myasset) {
    $incfile = validate_extension(trim($myasset->path), VIDEO_EXT) ? 'video' : 'image';
    $flush = !isset($article->assets[$k + 1]);
    include '_article.html.php';
  }
} else {
  if (preg_match('/\w+\.html\.php$/', $article->content)) {//non database derived article
    include $article->content;
  } else {
    include '_mdarticle.html.php';
  }
}
