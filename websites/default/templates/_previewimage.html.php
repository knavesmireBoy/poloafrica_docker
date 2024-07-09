    <?php
    
    if (file_exists($path)) { ?>
        <figure><a class="preview" href='<?= ASSET_UPLOAD . $articleId . '/' . $file['id']; ?>' title="<?= $path ?>"><img alt='<?= $file['alt'] ?>' src='/<?= $path ?>' /></a>
            <?php if (isset($info[$k])) { ?>
                <figcaption><?= 'ratio: ' . $info[$k]['ratio'] . ' / max: ' . $info[$k]['max'] . 'px' ?></figcaption>
            <?php } ?>
        </figure>
    <?php
    } else { ?>
        <figure class="notfound">
            <a href="<?= ASSET_EDIT ?><?= $file['id'] ?>" title="<?= $path; ?>"></a>
            <figcaption >FILE NOT FOUND</figcaption>
        </figure>

    <?php  }
