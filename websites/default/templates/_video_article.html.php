<?php
$poster = preparePoster($article->assets[0]->path, 'jpg');
$pp = $article->assets[0]->getArticle($article->assets[0]->id, 'page');
$videodata = prepareVideo($article->assets[0]->path, $pp);
?>
<article id="<?= $article->assets[0]->attr_id ?>">
    <video width="320" height="180" controls auto preload="metadata" poster="/<?= $poster; ?>">
        <?php include '_video_source.html.php'; ?>
    </video>
    <?= $article->mdcontent; ?>
</article>