<?php
require_once __DIR__ . '/../config/constants.php';

function secure_session_start(): void {
    if (session_status() === PHP_SESSION_NONE) {
        // Cookies seguras si hay HTTPS
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
}

function csrf_token(): string {
    secure_session_start();
    if (empty($_SESSION[CSRF_TOKEN_KEY])) {
        $_SESSION[CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_KEY];
}

function verify_csrf_token(?string $token): bool {
    secure_session_start();
    return isset($_SESSION[CSRF_TOKEN_KEY]) && is_string($token) && hash_equals($_SESSION[CSRF_TOKEN_KEY], $token);
}

function require_csrf(): void {
    $token = $_POST[CSRF_TOKEN_KEY] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (!verify_csrf_token($token)) {
        http_response_code(419);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'CSRF token inválido']);
        exit;
    }
}

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function sanitize_html_basic(string $html): string {
    // Filtro básico con soporte para imágenes y tablas.
    // Nota: Para máxima robustez use HTML Purifier. Aquí aplicamos un enfoque whitelist simple.
    $allowed = '<p><b><strong><i><em><u><ul><ol><li><br><hr><h1><h2><h3><h4><h5><h6><span><a><blockquote><code>'
             . '<img><figure><figcaption>'
             . '<table><thead><tbody><tfoot><tr><td><th><caption><col><colgroup>';

    // 1) Eliminar tags no permitidos
    $clean = strip_tags($html, $allowed);

    // 2) Eliminar atributos on* (onerror, onclick, etc.) y javascript: en href/src
    //    Esto es una defensa básica adicional al whitelist de tags.
    //    a) Quitar atributos on*
    $clean = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean);

    //    b) Normalizar espacios para facilitar regex sobre atributos
    $clean = preg_replace('/\s+/', ' ', $clean);

    //    c) Filtrar href y src peligrosos (javascript:, data: no-imagen, vbscript:, etc.)
    $clean = preg_replace_callback('/\s(href|src)\s*=\s*("|\')(.*?)\2/i', function ($m) {
        $attr = strtolower($m[1]);
        $val = trim($m[3]);
        $valLower = strtolower($val);
        $isRelative = preg_match('#^(\/|\.|\#|\?)#', $val) === 1;
        $isHttp = preg_match('#^(https?:)?\/\/[^\s]+#i', $val) === 1;
        // Permitir data:image sólo para formatos raster seguros (no SVG)
        $isDataImg = preg_match('#^data:image\/(png|jpe?g|gif|webp);base64,#i', $val) === 1;
        if ($attr === 'href') {
            // Permitimos http(s), mailto, tel y relativos
            $isMailTel = preg_match('#^(mailto:|tel:)#i', $val) === 1;
            if ($isRelative || $isHttp || $isMailTel) return ' ' . $attr . '="' . $val . '"';
            return '';
        } else { // src
            if ($isRelative || $isHttp || $isDataImg) return ' ' . $attr . '="' . $val . '"';
            return '';
        }
    }, $clean);

    // 3) Opcional: limitar atributos permitidos por tag eliminando los demás (simple)
    //    Eliminamos style extremadamente peligroso (expression, url(javascript:)) si existe
    $clean = preg_replace_callback('/\sstyle\s*=\s*("|\')(.*?)\1/i', function ($m) {
        $style = $m[2];
        $style = preg_replace('/expression\s*\(/i', '', $style);
        $style = preg_replace('/url\s*\(\s*[\"\']?javascript:[^\)]+\)/i', '', $style);
        return $style ? ' style="' . $style . '"' : '';
    }, $clean);

    return $clean;
}