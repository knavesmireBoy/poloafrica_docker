 <article id="<?= $myasset->attr_id ?>">
        <video width="320" height="180" controls auto preload="metadata" poster="/<?= $poster; ?>">
            <?php include '_video_source.html.php'; ?>
        </video>
        <?= $article->mdcontent; ?>
    </article>

