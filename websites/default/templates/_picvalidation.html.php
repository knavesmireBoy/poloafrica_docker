<?php
$sid = $slotid ?? 0;
/*! gallery::upload expects EITHER a numerical or boolean as 2nd arg. true implies post upload success (reload) we don't want that here as we have an orientation failure and we need to send the slotid as the 1st arg and something that doesn't resolve to a boolean as the 2nd; we're sending the ratio required to correct the orientation issue
*/
$up1 = "<a href='gallery/upload/$sid/0.666'>UPLOADING</a> the file with the ratio set to approximately 0.666 <em>will</em> produce a file with the correct ratio, albeit with some serious cropping.";
$up2 = 'You can <strong>RE-UPLOAD</strong> the file with the ratio set to approximately 0.666<br>Some experimentation may be required and you may also need to adjust the offset<br>See the <strong>GUIDE</strong> for details';
$up = '';
if (isset($routes)) {
    $up = $routes['route'] === 'upload' ? $up2 : $up1;
    $up = $routes['route'] === 'edit' ? '' : $up;
}
$myaccept = 'Upload failed. The file type may not be acceptable.';
$types = ['webp'];
$max = preg_replace('/M$/', 'mb', ini_get('post_max_size'));

$k = explode('_', $key);
if(isset($k[1])){
    $key = $k[0];
    $arg = $k[1];
}

$lookup = [
    'accept' => 'Upload failed. The file type may not be acceptable.',
    'access' =>  'An error occurred while uploading the file to the destination directory. Ensure that the web server has access to write in the path directory.',
    'allowed' => 'Additional assets are not allowed in this section. Please contact the System Administrator.',
    'article' => "The selected image cannot be uploaded as it is use by another article.",
    'article_other' => "The asset details cannot be edited as they are in use by another article, please select the correct asset for editing",
    'article_self' => "The asset details cannot be edited as they are already in use by this article, please select the correct asset for editing",
    'attack' => "Possible file upload attack",
    'baddpage' => "That page name is already taken!",
    'choose' => "Did you forget to choose a file for upload?",
    'copy' =>  'The file uploaded to the remote location but failed to copy to the destination directory. Ensure that the web server has access to write in the path directory.',
    'exist' => "The file does not exist in the target folder - please check the spelling",
    'exceeds' => "You attempted to upload a file of <span>$arg</span> which exceeds the maximum upload limit of <span>$max</span>",
    'existed' => "The file does not exist in the target folder; the reference to it has been removed from the database.",
    'ext' => "Asset cannot be assigned as the replacement file is the wrong type",
    'landscape' => "You are attempting to load a landscape image into a portrait slot<br> $up",
    'missing' => "File could not be uploaded",
    'name' => 'You are attempting to insert a record that already exists in the database.</br>To UPDATE the record please enter a number into the box field.',
    'orphan' => 'A file with this name exists in the target folder. To preserve that file rename your upload file or select a candidate for replacing from the dropdown menu',
    'pdf' => 'Please provide the link copy in the meta_data field',
    'portrait' => "You are attempting to load a portrait image into a landscape slot<br>$up",
    'prelink' => "Aborted upload as that link already exists",
    'ratio' => "The orientation is correct but the only permitted ratio must round to 1.5 ",
    'reloaded' => "Your file was successfully uploaded.",
    'replace' => 'Only currently archived files are replacement candidates. Use the form to edit or assign as normal.',
    'sibling' => 'Upload aborted. Can only update the same named file, or can only replace an existing asset with a new or archived file.'
];
/*
$vids = [
    'naledi' => 'naledi_nkopane.jpg',
    'news24' => 'poloafricadevelopmenttrust.jpg',
    'sport1' => 'poloafricasüdafrika.jpg',
];
*/
if (in_array($key, $types)) {
    $message = "Upload failed. The file type '$key' is not supported";
}

if (empty($message) && isset($lookup[$key])) {
    $message = $lookup[$key];
}
