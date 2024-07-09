<?php
$exists = file_exists($pdf['path']) ? '' : 'notfound';
$title = $exists ? 'file not found' : $pdf['path'];
?>
<a class="<?= $exists; ?>" title="<?= $title; ?>" class="preview" href="/<?= $pdf['path']; ?>" target="_blank"><img src="/<?= DEV . 'pdf_sq.png';  ?>" /></a>