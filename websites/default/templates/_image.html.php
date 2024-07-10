<?php
$attr = $myasset->attr_id ? classify($myasset->attr_id) : ''; ?>
<img src="/<?= IMAGES . $myasset->path ?? '' ?>" alt="<?= $myasset->alt ?? '' ?>" <?= $attr; ?> />
