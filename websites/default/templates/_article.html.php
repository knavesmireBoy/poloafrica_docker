<?php
if ($incfile === 'video') {
    $poster = $myasset->preparePoster();
    $videodata = $myasset->prepareVideo();
    if ($type === 'singular') {
        $inc = '_video.html.php';
    } else {
        echo $article->summary;
        include '_video_article.html.php';
        $inc = false;
    }
} else {
    $inc = '_image.html.php';
}

if ($inc) {
    include $inc;
    if ($flush) {
        include '_mdarticle.html.php';
    }
}
