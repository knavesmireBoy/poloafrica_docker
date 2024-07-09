<?php
$span = empty($archived) ? '<strong>ARCHIVED</strong> files' : '<a href="' . ASSET_MANAGE . '">ARCHIVED</a> files';
$pdfs = [];
$video = false;
$uploadguide = isset($_COOKIE['upload_guide']);
$guide = $uploadguide ? "please restore the <a href='/asset/upload/$articleId' id='restore_guide'>guide</a> for further information." : 'please refer to the GUIDE for naming files using the alt field.';

$id = $select['identity'];
if (strtoupper($id) === $id) {
    $id = strtolower($id);
    $selectname = "data[$id]";
} else {
    $selectname = $id;
}

$heading = empty($mytitle) ? 'Upload Asset' : 'Upload Asset' . ': “' . $mytitle . '”';

if (isset($key)) {
    include '_picvalidation.html.php';
}
if (isset($message)) { ?>
    <h5><?= $message; ?></h5>
<?php } ?>
<div class="edit_asset">
    <h3><?= $heading ?></h3>
    <p>By default new files are <strong>ADDED</strong> to an article, you can use the dropdown menu to select a candidate for replacing, the candidate file will be archived, which means the <strong>replaced</strong> file will still exist in the target folder and a reference for it can be found in a list of <?= $span; ?> where you then have the option to remove the file entirely.</p>
    <p>Re-Uploading the EXACT SAME NAMED FILE will UPDATE your current file with the selected attributes, <?= $guide; ?></p>
    <?php

    // DECIDED TO PREVENT ACCESS TO FILE EDITING FROM THE UPLOAD FORM
    if (!empty($archived)) { ?>
        <p>Any currently archived files can be found in a <a href="<?= $routes['assign'] ?>">dropdown menu</a> for assigning to an article.</p>
    <?php }

    ?>
    <div class="uploadpreview <?= $previewklas; ?>">
        <?php
        if (empty($omitguide) && !$uploadguide) {
            include '_uploadguide.html.php';
        }
        ?>
        <form action="<?= $action; ?>" method="post" enctype="multipart/form-data" class="edit upload <?= $warning; ?>">
            <fieldset>
                <label for="<?= $filename; ?>">upload</label>
                <input id="<?= $filename; ?>" type="file" name="<?= $filename; ?>" <?= $accept; ?>>
                <?php if (!empty($select['options'])) {
                    include '_select.html.php';
                } ?>
                <label for="alt">alt</label>
                <input id="alt" name="data[alt]" value=" " />
                <label for="attr_id">meta_data</label>
                <input id="attr_id" name="data[attr_id]" value="" />
                <?php include '_params.html.php'; ?>
                <input type="hidden" name="action" value="upload" id="upload" />
                <input type="hidden" name="data[article_id]" id="article_id" value="<?= $articleId; ?>" />
            </fieldset>
            <input type="submit" value="upload">
        </form>
        <?php if (!empty($files)) {
            if ($reloaded) {
                $files = pluck($files, true);
            }
            include '_previews.html.php';
        } ?>
    </div>
    <div class="replace"><a href="<?= $exit; ?>">Exit</a>
    </div>