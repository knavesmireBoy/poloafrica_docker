
<?php
if (isset($key)) {
    //!! after running this the variable $id is created, see $ppid below so as to avoid clash FIX
    include '_picvalidation.html.php';
}
if (isset($message)) { ?>
    <h5><?= $message; ?></h5>
<?php } ?>



<div id="edit_page">
    <form action="<?= $action ?>" method="post" class="noajax">
        <fieldset>
            <label for="page">name</label>
            <input type="text" name="details[name]" id="page" required autofocus maxlength="255" value="<?= $ppname ?? '' ?>">
            <label for="title">title</label><input type="text" name="details[title]" id="title" required autofocus maxlength="255" value="<?= $pptitle ?? '' ?>">
            <div id="order">
                <label for="position">position</label>
                <input name="position" id="position" type="number" max="<?= $ppmax ?>" min="0" value="0" />
                <label for="shuffle">shuffle</label>
                <input name="shuffle" id="shuffle" type="checkbox" checked/>
            </div>
            <label for="description">description</label>
            <textarea name="details[description]" id="description" maxlength="1000" ><?= $ppdescription ?? '' ?></textarea>
            <label for="content">content</label>
            <textarea name="details[content]" id="content" placeholder="meta content" maxlength="200000"><?= $ppcontent ?? '' ?></textarea>
            <?php
            if (!empty($ppid)) { ?>
                <input type="hidden" name="pk" value="<?= $ppid; ?>">
            <?php } ?>
        </fieldset>
        <input type="submit" value="cancel" name="cancel">
        <input type="submit" name="action" value="<?= $submit ?>">
    </form>
</div>
<div class="replace">
    <a href="<?= $exit ?>">Exit</a>
</div>