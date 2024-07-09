<?php
include_once 'funcs.php';
?>
<div id="params">
    <label for="ratio">ratio</label>
    <input id="ratio" name="floats[ratio]" value="<?= $ratio ?? 0; ?>">
    <label for="offset">offset</label>
    <input id="offset" name="floats[offset]" value=".5">
    <label for="appearance">appearance</label>
    <input id="appearance" name="ints[appearance]" value="-1">
    <label for="maxsize">max</label>
    <input id="maxsize" name="ints[maxsize]" value="0">
</div>