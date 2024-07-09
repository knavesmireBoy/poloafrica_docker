<?php
foreach ($article->assets as $a) { 
    //$parent conditionally set in _image_article; a flag to prevent setting class on image
$attr = $a->attr_id ? classify($a->attr_id) : ''; ?>
    <img src="/<?= IMAGES . $a->path ?? '' ?>" alt="<?= $a->alt ?? '' ?>" <?= $attr; ?> />
<?php }
