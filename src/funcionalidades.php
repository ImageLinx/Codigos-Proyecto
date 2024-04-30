<?php
$target_dir = "uploads/";
$originalName = basename($_FILES["fileToUpload"]["name"]);
$maxFileSize = 34 * 1024 * 1024; // 34 MB
$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];

$uploadOk = 1;
$uniqueID = uniqid();
$newFileName = $uniqueID . '_' . $originalName;
$target_file = $target_dir . $newFileName;

// Verificar el tipo MIME del archivo
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$fileMimeType = finfo_file($finfo, $_FILES["fileToUpload"]["tmp_name"]);
finfo_close($finfo);

if (!in_array($fileMimeType, $allowedMimeTypes)) {
    echo "Lo siento, solo se permiten archivos de tipo imagen: JPEG, PNG y WEBP.";
    $uploadOk = 0;
}

// Verificar el tamaño del archivo
if ($_FILES["fileToUpload"]["size"] > $maxFileSize) {
    echo "Lo siento, tu archivo es demasiado grande. El tamaño máximo permitido es de 34 MB.";
    $uploadOk = 0;
}

// Intentar subir el archivo
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
