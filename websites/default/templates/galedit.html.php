<?php
include_once 'funcs.php';
$myhref = '';
$mypath = '';
$orient = '';
$mytitle = 0;
$myalt = '';
?>

<h3>Select pic to edit</h3>
<?php if (!empty($msg)) {  ?>
    <p><?= urldecode($msg) ?></p>
<?php } ?>
<p>Note that the images are arranged in a convenient grid that does not correspond exactly to the order they appear in the public facing gallery. Hovering over the image will provide the number of the "slot" that the image belongs to. This may prove useful when re-allocating images.</p>
<ul id="gal_edit">
    <?php
    foreach ($gallery as $gal) {
        if (isset($gal)) :
            $orient = $gal->orient && $gal->orient === 'portrait' ? 'portrait' : '';
            $myhref = $gal->id ? GAL_EDIT . $gal->id : '';
            //$myhref = $gal->id ? GAL_ASSIGN . 0 . "/$gal->box" : '' (1);
            $mypath = $gal->path ? GALLERY_THUMBS . $gal->path : '';
            $myid = $gal->id;
            $mytitle = $gal->box; //display BOX id on hover

            if (file_exists($mypath)) { ?>
                <li class="<?= $orient ?>"><a href="<?= $myhref ?>"><img alt="<?= $myalt ?>" title="<?= $mytitle ?>" src="/<?= $mypath ?>"></a></li>
            <?php } else { ?>
                <li class="notfound">
                    <a>
                        <img alt="<?= $myalt ?>" title="file not found" />
                    </a>
                    FILE NOT FOUND
                </li>
            <?php
            }
        else : //stress test for missing files; should NEVER be required
            $id = ++$myid;
            $myhref = GAL_EDIT . $id;
            ?><li class="await <?= $orient ?>"><a href="<?= $myhref ?>"><img alt="<?= $myalt ?>" title="<?= $id ?>" src="/<?= GALLERY_THUMBS . 'await.jpg' ?>"></a></li>
    <?php
        endif;
    }
    ?>
</ul>
<p class="replace"><a href="/gallery/upload" id="upload_link" title="upload a pic">upload new pic</a><a href="<?= BADMINTON ?>" id="ret" title="back to admin">exit</a></p>

<?php
/*(1) I used this to skip the dropdown version of the edit image form and straight to the freetext version when I had cleared the gallery database table by mistake and needed to input the path to the file*/