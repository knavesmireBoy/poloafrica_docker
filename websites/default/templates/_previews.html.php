<div class="previews">
    <?php
    foreach ($files as $k => $file) {
        $path = $file['path'];
        if (preg_match('/\.pdf$/', $path)) {
            if ($assetId && $assetId == $file['id'] || !$assetId) {
                if (isset($page)) {
                    $path = "resources/assets/$page/$path";
                }
                $pdfs[] = ['path' => $path, 'id' => $file['id']];
            }
            continue;
        }

        if (validate_extension($path, VIDEO_EXT)) {
            $poster = preparePoster($path, 'jpg');
            $videodata = prepareVideo($path, $page);
            include '_video.html.php';
            continue;
        }
        $path =  IMAGES . $path;
        include '_previewimage.html.php';
    }
    if (!empty($pdfs)) : ?>
        <div class="pdf">
            <?php
            foreach ($pdfs as $pdf) :
                include '_previewpdf.html.php';
            endforeach;  ?>
        </div>
    <?php endif; ?>

</div>