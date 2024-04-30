<?php
$target_dir = "uploads/";
$originalName = basename($_FILES["fileToUpload"]["name"]);
$maxFileSize = 34 * 1024 * 1024; // 34 MB
$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];

$uploadOk = 1;
$uniqueID = uniqid();
$newFileName = $uniqueID . '_' . $originalName;
$target_file = $target_dir . $newFileName;


$finfo = finfo_open(FILEINFO_MIME_TYPE);
$fileMimeType = finfo_file($finfo, $_FILES["fileToUpload"]["tmp_name"]);
finfo_close($finfo);

if (!in_array($fileMimeType, $allowedMimeTypes)) {
    $errorMessage = "Solo se permiten archivos de tipo imagen: JPEG, PNG y WEBP.";
    $uploadOk = 0;
}


if ($_FILES["fileToUpload"]["size"] > $maxFileSize) {
    $errorMessage = "Tu archivo es demasiado grande. El tamaño máximo permitido es de 34 MB.";
    $uploadOk = 0;
}


if ($uploadOk == 0) {
    header("Location: index.html?error=" . urlencode($errorMessage));
    exit;
} else {
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
        $link = "https://" . $_SERVER['HTTP_HOST'] . "/" . $target_dir . $newFileName;
        header('Location: index.html?uploaded=true&file=' . urlencode($link));
        exit;
    } else {
        $errorMessage = "Hubo un error subiendo tu archivo.";
        header("Location: index.html?error=" . urlencode($errorMessage));
        exit;
    }
}
?>
