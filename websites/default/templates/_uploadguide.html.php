<?php

$_pdf = empty($box) ? '(or a title to a PDF)' : ''; ?>

<div id="upload_guide">
    <header>
        <h1>GUIDE</h1><a title="click to hide guide" href="<?= $exit_guide; ?>" id="exit_guide">X</a>
    </header>
    <?php if (!empty($box)) { ?>
        <label for="guide_box">BOX</label>
        <input id="guide_box" type="checkbox">
        <article>
            <p>Optionally assign the new image to an existing slot. The existing image will be retained in the target folder and can be reassigned to a new slot. Leave blank to assign the image at a later date.</p>
        </article>

    <?php } ?>
    <label for="guide_alt"> <?= $alt ?? 'ALT' ?> </label>
    <input id="guide_alt" type="checkbox">
    <article>
        <p>String: Assigns an alt description to an image <?= $_pdf ?>. Don't forget that alt is a required attribute for IMAGES (even if blank). The field can also be used to rename ANY type of file. Separate the alt/title from the file name with a forward slash: <em>a beautiful view/sunset_7</em>. The original extension will be applied eg. (targetfolder/sunset_7.jpg)</p>
    </article>
    <label for="guide_attr">META_DATA</label><input id="guide_attr" type="checkbox">
    <article>
        <p>String: This can be used to set an id and/or class on the image, not strictly a content management task, as it infers a knowledge of the <a href="https://developer.mozilla.org/en-US/docs/Web/API/Document_Object_Model" target="_blank">DOM</a>. Most useful to add classes like odd / even; landscape / portrait; mobile / desktop that can control appearance / visibility.</p>
        <div style="text-align: left">Takes three forms: <ol style="text-align: left; color: black">
                <li> "id": id only</li>
                <li>"<a href="">.</a>class": class only</li>
                </li>"id<a href="">.</a>class": id AND class</li>
            </ol>The use of the full stop is critical. Period.</div>
        <?php if (empty($box)) { ?>
            <p>One further use is when uploading a <b>PDF</b> where dom attributes have little value. You will no doubt require a link to view/download the pdf. Supplying the required portion of article text will achieve this, on the fly, rather than editing the article copy later. E.G. an input of <i>One further use</i> in the <i>meta_data</i> field will create that link: <a href='.'>One further use</a>. Where a single word is desirable, to avoid ambiguity, please supply a bit of context but surround the target word with a pair of brackets:<i> One [further] use:- </i> One <a href'.'>further</a> use.</p>
            <p>There are exceptions for LIST items, ie a (possibly single) word preceded by a HYPHEN and EXACTLY ONE SPACE in the exisitng article text. "- Target Item". Input copy can be "Target Item" or ("TargetItem") without those brackets. Furthermore a link can be APPENDED to an existing list by using "- Target Item". Do note that removing a linked file only removes the link tags not the link copy.</p>
        <?php } ?>
    </article>
    <label for="guide_ratio">RATIO</label><input id="guide_ratio" type="checkbox">
    <article>
        <p>Float: n &gt; 1 eg(1.5) increases the ratio of the larger dimension n &lt; 1 eg(.75) decreases</p>
        <p> Setting n to 1 results in a square image regardless of orientation; leave at 0 to preserve the current ratio.</p>
        <p>The ratio can also be calculated on-the-fly using a slash or space delimiter eg 16/9 (or 16 9) : 1.777</p>
        <p>To convert the orientation divide 1 by the desired final ratio.</p>
    </article>
    <label for="guide_offset">OFFSET</label><input id="guide_offset" type="checkbox">
    <article>
        <p>Float: Portrait: 0 crops from top, 1 crops from bottom. Landscape: 0 from left, 1 from right. default is .5 (from centre). </p>
    </article>
    <label for="guide_appearance">APPEARANCE</label> <input id="guide_appearance" type="checkbox">
    <article>
        <p>Int: 100 for top quality, -1 (default) for optimum (about 75)</p>
        <p>Lesser values for resampling down, eg: thumbnails but if issues with tiny images leave at 0</p>
        <p>We can also use this field for ROTATING images, by using a number that represents degrees AFTER the quality numeral, slash OR space delimited: eg 75/270 (or -1 180). </p>
        <p>To ROTATE ONLY use deg after the desired angle: 90deg</p>
    </article>
    <label for="guide_max">MAX</label><input id="guide_max" type="checkbox">
    <article>
        <p>Int: absolute dimension of largest dimension</p>
        <p>As with ratio it can be calculated on the fly. For uploaded portrait images the desired max size of the width can be calculated by dividing the width by the ratio.</p>
        <p>You may have to clear the browser's cache if repeated attempts to acheive the desired size/crop don't appear to deliver the right results.</p>
    </article>

</div>