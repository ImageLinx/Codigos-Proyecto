<?php
$target_dir = "uploads/";
$originalName = basename($_FILES["fileToUpload"]["name"]);
$imageFileType = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$maxFileSize = 34 * 1024 * 1024;
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$uploadOk = 1;

$uniqueID = uniqid();
$newFileName = $uniqueID . '_' . $originalName;
$target_file = $target_dir . $newFileName;

if (!in_array($imageFileType, $allowedExtensions)) {
    echo "Lo siento, solo se permiten archivos JPG, JPEG, PNG, GIF y WEBP.";
    $uploadOk = 0;
}

if ($_FILES["fileToUpload"]["size"] > $maxFileSize) {
    echo "Lo siento, tu archivo es demasiado grande. El tamaño máximo permitido es de 34 MB.";
    $uploadOk = 0;
}

if ($uploadOk == 0) {
    echo "Lo siento, tu archivo no fue cargado.";
} else {
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {

        $link = "https://" . $_SERVER['HTTP_HOST'] . "/" . $target_dir . $newFileName;

        header('Location: index.html?uploaded=true&file=' . urlencode($link));
        exit;
    } else {
        echo "Hubo un error subiendo tu archivo.";
    }
}
?>
