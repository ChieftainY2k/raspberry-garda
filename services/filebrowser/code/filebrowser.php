<?php

/**
 * @TODO this is MVP/PoC only!
 */

$baseDir = "/data-kerberosio-capture";

if (!empty($_GET['filename'])) {

    //stream file content

    if (!preg_match("/^[.a-z0-9_-]+$/i", $_GET['filename'])) {
        throw new InvalidArgumentException("Malformed file name");
    }

    header("content-type: ".mime_content_type($baseDir."/".$_GET['filename'].""));
    readfile($baseDir."/".$_GET['filename']);


} else {

    //show all files
    echo "
        <html>
        <head>
            <title>Filebrowser (".htmlspecialchars(getenv("KD_SYSTEM_NAME")).")</title>
        </head>
        <body>
    ";
    $filesList = glob($baseDir."/*");
    sort($filesList);

    $idx = 1;
    $prevDay = null;
    foreach ($filesList as $filename) {
        $fimeModTime = filemtime($baseDir."/".basename($filename));
        $day = date("d", $fimeModTime);
        if ($prevDay != $day) {
            echo "<hr>";
        }
        echo ($idx++).". [".date("Y-m-d H:i:s", $fimeModTime)."] <a href='?filename=".htmlspecialchars(basename($filename))."'>".basename($filename)."</a><br>";
        $prevDay = $day;
    }
    echo "
        </body>
        </html>
    ";

}


