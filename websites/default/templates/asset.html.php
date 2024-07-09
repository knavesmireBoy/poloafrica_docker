<?php
if (isset($link)) {
    $heading = empty($title) ? $link : $link . ': “' . $title . '”';
}
$heading = isset($heading) ? $heading : (empty($title) ? '' : $title);


$id = $select['identity'] ?? '';
if ($id && strtoupper($id) === $id) {
    $id = strtolower($id);
    $selectname = "data[$id]";
} else {
    $selectname = $id;
}
?>
<div class="edit_asset">
    <h3><?= $heading ?></h3>
    <?php if (!$replace) { ?>
        <input type="checkbox" id="asset_usage">
        <label for="asset_usage">Guide for Usage</label>
    <?php
    }
    $assetId = isset($asset) ? $asset->id : null;
    $upload = $routes['upload'];
    $freetext = $routes['add'];
    $doAssign = $assetId ? 'checked' : '';

    $doupload = "<p>You may prefer to <a href='$upload'>upload</a> a new asset, which provides the additional benefits of cropping, scaling and renaming of the file.</p>";

    $dropuse = "<p>The <strong>default</strong> intention of this form is simply to edit the <em>alt</em> and <em>meta_data</em> fields of the <strong>selected</strong> asset.</p><p>But it can be used, <strong>instead</strong>, to <strong>ADD/REINSTATE</strong> an <strong>archived</strong> asset to the article by using the <em>path</em> dropdown provided. Any
    <strong><em>changes</em></strong> to the other fields will be applied to the <strong>RETRIEVED</strong> asset.</p>";

    $freeuse = "<p>Use this form to edit the <em>alt</em> and <em>meta_data</em> fields of the <em>selected</em> file. You may also archive the file by unchecking the <em>assign</em> checkbox. You may <strong>INSTEAD</strong> reinstate a file by inputting a <strong>valid path</strong> into the path field. The subject of the form now becomes the reinstated file.<p>";

    $overrule = '<p>By default new assets are <strong>ADDED</strong> to an article, to overrule this behaviour, return to the <a href="' . ASSETS_EDIT . $articleId . '">asset list</a> click the trash button, the ensuing page will present an  option to replace the resident asset and then return you to a less verbose verson of this form, choose your replacement file from the dropdown menu, the <em>replaced</em> file will be archived. Do bear in mind that only files of the same <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/MIME_types" target="_blank" title="all about mime types">MIME type</a> may be swapped.</p>';

    $myuse = empty($select['options']) ? $freeuse : $dropuse;
    $klas = empty($select['options']) ? 'assign' : '';
    //converts key to message, unless $message is explicitly set
    if (isset($key)) {
        include '_picvalidation.html.php';
    }
    if (!empty($message)) { ?>
        <h5><?= $message; ?></h5>
    <?php } ?>
    <div id="info">
        <?php
        if (!$replace) {
            if ($myuse === $dropuse) { ?>
                <?= $dropuse; ?>
                <p>Only <strong title='files that are referenced in the database AND are not in use by another article'>ARCHIVED</strong> files will be present in the dropdown menu, however, files residing in the <a href="#" title="<?= $dir ?>">target</a> folder can be similarly REINSTATED to the database using <a href='<?= $freetext ?>'>free text input</a>.</p>
                <?= $doupload; ?>
                <?= $overrule; ?>

            <?php } else { ?>
                <?= $freeuse; ?>
                <?= $doupload; ?>

        <?php  }
        } ?>
    </div>
    <form action="<?= $action; ?>" method="post" class="<?= $klas; ?>">
        <fieldset>
            <input type="hidden" id="orphans" name="orphans" value="<?= count($select['options']); ?>">
            <?php if ($assetId) { ?>
                <input type="hidden" id="pk" name="pk" value="<?= $assetId; ?>">
            <?php
            }
            if ($articleId) { ?>
                <input type="hidden" id="article_id" name="data[article_id]" value="<?= $articleId; ?>">
            <?php
            }
            if (isset($select['options'][0])) {
            ?>
                <label for="path">path</label>
            <?php include '_select.html.php';
            } else { ?>
                <label for="path">path</label>
                <input type="text" name="<?= $selectname; ?>" id="path" placeholder="<?= $dir ?>" value="<?= $asset->path ?? '' ?>" required>

            <?php }
            //allow editing of alt and attr_id fields only
            ?>
            <label for="alt">alt</label>
            <input type="text" name="data[alt]" id="alt" value="<?= $asset->alt ?? '' ?>">
            <label for="attr_id">meta_data</label>
            <input type="text" name="data[attr_id]" id="attr_id" placeholder="attr_id" value="<?= $asset->attr_id ?? '' ?>">

            <?php if ($replace) { ?>
                <input type="hidden" id="replace" name="replace" value="<?= $replace; ?>">
            <?php
            } else if (empty($select['options'])) { ?>
                <label for="assign">assign</label>
                <input type="checkbox" name="assign" id="assign" <?= $doAssign ?>>
            <?php } ?>
        </fieldset>
        <input type="submit" value="submit" name="submit">
    </form>
    <div class="replace"><a href="<?= $exit ?>">Exit</a></div>
</div>