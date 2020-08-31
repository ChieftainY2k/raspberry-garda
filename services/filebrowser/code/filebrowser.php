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
    foreach ($filesList as $filename) {
        echo ($idx++).". [".date("Y-m-d H:i:s", filemtime($baseDir."/".basename($filename)))."] <a href='?filename=".htmlspecialchars(basename($filename))."'>".basename($filename)."</a><br>";
    }
    echo "
        </body>
        </html>
    ";

}

