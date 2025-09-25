<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => ['message' => 'Método no permitido']]);
    exit;
}

try {
    require_login();
    require_csrf(); // valida X-CSRF-Token o POST según tu implementación

    if (!isset($_FILES['upload']) || !is_uploaded_file($_FILES['upload']['tmp_name'])) {
        http_response_code(400);
        echo json_encode(['error' => ['message' => 'Archivo no recibido']]);
        exit;
    }

    $file = $_FILES['upload'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => ['message' => 'Error al subir el archivo (' . $file['error'] . ')']]);
        exit;
    }

    // Límite 5MB
    $maxBytes = 5 * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        http_response_code(413);
        echo json_encode(['error' => ['message' => 'La imagen supera el tamaño máximo (5MB)']]);
        exit;
    }

    // Validación MIME real
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mime])) {
        http_response_code(415);
        echo json_encode(['error' => ['message' => 'Tipo de imagen no permitido']]);
        exit;
    }

    // Directorio de subida
    $root = realpath(__DIR__ . '/..');
    $uploadsDir = $root . DIRECTORY_SEPARATOR . 'uploads';
    if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0775, true)) {
        http_response_code(500);
        echo json_encode(['error' => ['message' => 'No se pudo crear el directorio de subida']]);
        exit;
    }

    // Nombre aleatorio
    $ext = $allowed[$mime];
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest = $uploadsDir . DIRECTORY_SEPARATOR . $name;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        http_response_code(500);
        echo json_encode(['error' => ['message' => 'No se pudo guardar la imagen']]);
        exit;
    }

    // URL pública
    $url = app_url('/uploads/' . $name);

    // Respuesta para SimpleUploadAdapter
    http_response_code(200);
    echo json_encode([
        'url' => $url,
        'uploaded' => 1,
        'fileName' => $name,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => ['message' => 'Error interno']]);
}
