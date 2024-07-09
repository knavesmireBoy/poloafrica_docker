<?php
$src =  '';
$type = '';
foreach ($videodata as $video) {
    $src = $video['src'];
    $type = $video['type'];
?>
    <source src="/<?= $src ?>" type=<?= $type; ?>>
<?php }
