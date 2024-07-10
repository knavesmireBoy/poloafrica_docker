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


foreach ($article->assets as $k => $myasset) {
    $incfile = validate_extension(trim($myasset->path), VIDEO_EXT) ? 'video' : 'image';
    $finish = !isset($article->assets[$k+1]);
    if ($incfile === 'video') {
        include '_videos.html.php';
    } else {
        include doInclude($article->content);
    }
    $ran = true;
}
