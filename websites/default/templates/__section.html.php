<?php

$incfile = '';
$mykey = '';
$myasset = $article->assets[0] ?? null;
if (isset($myasset)) {
    //ASSUMES SAME FILETYPE PER ARTICLE??
    $incfile = validate_extension(trim($myasset->path), VIDEO_EXT) ? 'video' : 'image';
}
if ($incfile === 'video') {
    include '_videos.html.php';
} else {
    include doInclude($article->content);
}
