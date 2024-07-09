<?php
//dump($article);
include '_classify.html.php';
//DON'T FORGET classify returns "id='myid'" OR class="myclass" OR "id='myid' class='myclass'"
$type = 'singular';

include '_section_factory.php';

if (isset($attrs[1])) {
    $id = $attrs[0];
    $kls = $attrs[1]; ?>
    <section id="<?= $id ?>" class="<?= trim($kls) ?>">
        <?php } else {
        if (preg_match('/=/', $attrs[0]) || empty($attrs[0])) { ?>
            <section <?= $attrs[0] ?>>
            <?php } else { ?>
                <section class="<?= $attrs[0]; ?>">
            <?php }
    }

    include '__section.html.php'; ?>
                </section>