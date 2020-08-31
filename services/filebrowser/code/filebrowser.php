<?php

if (!empty($_GET['filename'])) {

    if (!preg_match("/^[.a-z0-9_-]+$/i", $_GET['filename'])) {
        throw new InvalidArgumentException("Malformed file name");
    }

    //stream file content
    header("content-type: ".mime_content_type("/data-kerberosio-capture/".$_GET['filename'].""));
    readfile("/data-kerberosio-capture/".$_GET['filename']);


} else {

    //show all files
    echo "
        <html>
        <head>
            <title>Filebrowser (" . htmlspecialchars(getenv("KD_SYSTEM_NAME")) . ")</title>
        </head>
        <body>
    ";
    $filesList = glob("/data-kerberosio-capture/*");
    sort($filesList);
    foreach ($filesList as $filename) {
        echo "<a href='?filename=".htmlspecialchars(basename($filename))."'>".basename($filename)."</a><br>";
    }
    echo "
        </body>
        </html>
    ";

}
?>
