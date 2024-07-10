<?php

$incfile = '';
$mykey = '';
$finish = false;
$ran = false;
$myasset = $article->assets[0] ?? null;
if (isset($myasset)) {
    //ASSUMES SAME FILETYPE PER ARTICLE??
  //  $incfile = validate_extension(trim($myasset->path), VIDEO_EXT) ? 'video' : 'image';
}

include '_accordion.html.php';
foreach ($article->assets as $k => $myasset) {
    $incfile = validate_extension(trim($myasset->path), VIDEO_EXT) ? 'video' : 'image';
    $flush = !isset($article->assets[$k+1]);
}

include '_foobar.html.php';
