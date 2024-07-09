<?php
$d = $description ?? '';
$c = $content ?? '';
?>

<!doctype html>
<html class="no-js">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1" name="viewport">
  <title><?= ucwords($title); ?></title>
  <meta content="<?= $d ?>" name="description">
  <meta content="<?= $c ?>" name="keywords">
  <link href="<?= CSS . 'main.css' ?>" media="all" rel="stylesheet">
  <link href="<?= CSS . 'print.css' ?>" media="print" rel="stylesheet" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Abril+Fatface&display=swap" rel="stylesheet">
  <script>
    if(screen && screen.width){
      document.cookie = 'resolution=' + Math.max(screen.width, screen.height) + '; path=/';
    }
  </script>
  <script>
        var semolina_pilchard = '<?php echo $user ? $user : 1; ?>',
      semolina_pilchard = parseFloat(semolina_pilchard);
  </script>
  <script src="<?= JS . 'modernizr.js'; ?>"></script>
</head>