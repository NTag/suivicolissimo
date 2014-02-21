<html>
<head>
    <style>
    img {
        border: 1px solid black;
    }
    </style>
</head>
<body>
<form method="post" action="">
<?php

foreach ($_POST as $k => $p) {
    $k = preg_replace('#[^0-9a-z]#', '', $k);
    if (!file_exists('letters/ref/' . $k)) {
        unlink('letters/ref/' . $k . '.png');
        file_put_contents('letters/ref/' . $k, $p);
    }
}

$listRef = scandir('letters/ref');
$listRef = array_diff($listRef, array('.','..'));

foreach ($listRef as $file) {
    if (preg_match('#\.png$#', $file)) {
        $hash = preg_replace('#^(.+)\.png$#', '$1', $file);
        ?>
<img src="letters/ref/<?php echo $file; ?>" /><br />
<input type="text" name="<?php echo $hash; ?>" /><br />
<?php
    }
}
?>
<input type="submit" />
</form>
</body>
</html>