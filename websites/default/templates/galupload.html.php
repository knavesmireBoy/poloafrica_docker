<?php
$incsubmit = true;
$assign = '';
$link = '';
$_path = '';
$_alt = '';
$_id = '';
$para = empty($orphans) ? '' : 'Remove any unassigned images <a href="' . GAL_MANAGE  . '">here</a>.';

$uploadguide = isset($_COOKIE['upload_guide']);
$imgId = $img ? $img->id : 0;
$guide = $uploadguide ? " Please restore the <a href='/gallery/upload/$imgId/true' id='restore_guide'>guide</a> for further information." : '';
$para .= $guide;

$imgroute = preg_match('/slide/', $previewklas);

if($imgId){
    $imgroute = $imgroute ? GAL_UP . $img->id : GAL_UP . $img->id  . "/$img->id";
}

if (isset($img->id)) {
    $link = GAL_ASSIGN . $img->id;
    $assign = "<p>If you prefer you can simply <a href='$link'>assign</a> a library pic to a box. $para </p>";
}

if (isset($key)) {
    include '_picvalidation.html.php';
}
if (isset($message)) { ?>
    <h5><?= $message; ?></h5>
<?php } ?>
<h3>Upload pic to gallery</h3>
<?= $assign ?>
<div class="uploadpreview gallery <?= $previewklas; ?>">
    <?php
    if (empty($omitguide) && !isset($_COOKIE['upload_guide'])) {
        include '_uploadguide.html.php';
    }
    ?>

    <form action="<?= $action; ?>" method="post" enctype="multipart/form-data" class="edit gallery upload <?= $warning; ?>">
        <fieldset>
            <label for="<?= $filename; ?>">upload</label>
            <input id="<?= $filename; ?>" type="file" name="<?= $filename; ?>" <?= $accept; ?>>
            <label for="box">box</label>
            <input type="number" name="box" id="box" step="1" min="0" max="92" value="<?= $box ?? '' ?>">
            <label for="alt">alt</label>
            <input id="alt" name="alt" />
            <?php include '_params.html.php'; ?>
            <input type="hidden" name="action" value="upload" id="upload" />
        </fieldset>
        <input type="submit" value="upload">
    </form>
    <div class="previews">
        <?php
        if(isset($img)){
            $_path = $img->path;
            //$_path = $img?->path; PHP8
            $_alt =  $img->alt;
            $_id = $img->id;
        }
       
        if (!empty($_path) && file_exists(GALLERY . $_path)) { ?>
            <figure><a href="<?= $imgroute || ''; ?>"><img alt='<?= $_alt ?>' src='/<?= GALLERY . $_path ?>' /></a>
                <?php if (!empty($info)) { ?>
                    <figcaption><?= 'ratio: ' . $info['ratio'] . '<br> max: ' . $info['max'] . 'px' ?></figcaption>
                <?php } ?>
            </figure>
        <?php
        } else { ?>
            <figure class="notfound">
                <a href="<?= ASSET_EDIT ?><?= $_id; ?>" title="file not found"></a>
                <figcaption>FILE NOT FOUND</figcaption>
            </figure>
        <?php }
        ?>
    </div>
</div>
<p class="replace"><a href="<?= GAL_REVIEW ?>" id="ret" title="back to review">back to review</a></p>