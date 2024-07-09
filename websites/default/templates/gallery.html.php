<a class="pagenav" id="gal_back" href="<?= GAL_PREV_PP . $prevpage ?>"><span></span></a>
<ul id="thumbnails" class="gallery <?= $layout ?>">
    <?php
    $myhref = '';
    $myalt = '';
    $mypath = '';
    foreach ($gallery as $gal) {
        if (isset($gal)) {
            $klas = $gal->orient === 'landscape' ? '' : 'portrait';
            $myhref = GAL_LOAD . $gal->id;
            $myalt = $gal->alt;
            $mypath = GALLERY . $gal->path;
        }
    ?>
        <li class="<?= $klas ?>"><a href="<?= $myhref ?>"><img alt="<?= $myalt ?>" src="/<?=  $mypath ?>"></a></li>
    <?php
    }
    ?>
</ul>

<a class="pagenav" id="gal_forward" href="<?= GAL_NEXT_PP . $nextpage ?>"><span></span></a>