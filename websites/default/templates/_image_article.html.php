<?php

if (!$k) {
    include '_accordion.html.php';
    include '_image.html.php';
    if ($finish) { ?>
        <article>
            <?= $article->mdcontent; ?>
        </article>

    <?php
    }
} else {
    include '_image.html.php';
    if ($finish) { ?>
        <article>
            <?= $article->mdcontent; ?>
        </article>
<?php
    }
}
