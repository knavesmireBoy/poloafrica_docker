<?php

$incfile = '';
$mykey = '';

if (isset($article->assets[0])) {
    //ASSUMES SAME FILETYPE PER ARTICLE??
    $incfile = validate_extension(trim($article->assets[0]->path), VIDEO_EXT) ? 'video' : 'image';
}
if ($incfile === 'video') {
    include '_videos.html.php';
} else {
    include doInclude($article->content);
}
