<?php
foreach ($pdfs as $pdf) :
    $path = $pdf['path'];
    $id = $pdf['id'];

    if (file_exists($path)) {
?>
        <li><a class="thumb pdf_file" href="<?= $routes['edit'] . $id ?>" title="click to edit"><img src="/<?= DEV . 'pdf_sq.png' ?> "></a>
            <a class="trash" title="delete" href="<?= $routes['action'] .  $id . '/delete'; ?>">delete</a>
        </li>
    <?php
    } else { ?>
        <li class="pdfnotfound">
            <a class="thumb pdf_file" href="<?= $routes['edit'] . $id ?>" title="file not found">
                <img src="/<?= DEV . 'pdf_sq.png' ?> ">
            </a>
        </li>

<?php }
endforeach;
