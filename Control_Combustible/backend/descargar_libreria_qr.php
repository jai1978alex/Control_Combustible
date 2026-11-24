<?php
/*
Descarga una sola vez la librería del escáner QR (html5-qrcode 2.3.8, licencia Apache-2.0)
y la guarda en js/vendor/html5-qrcode/html5-qrcode.min.js para que el escáner funcione sin internet.

USO (solo línea de comandos, en el servidor o en el PC con XAMPP):
    php descargar_libreria_qr.php
En Windows también puedes hacer doble clic en instalar_libreria_qr.bat (carpeta raíz del proyecto).
*/

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script solo se ejecuta desde la línea de comandos.');
}

$destino = dirname(__DIR__) . '/js/vendor/html5-qrcode/html5-qrcode.min.js';
$fuentes = [
    'https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js',
    'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js',
];

function descargar(string $url): ?string {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $datos = curl_exec($ch);
        $codigo = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        return ($datos !== false && $codigo === 200) ? (string)$datos : null;
    }
    $ctx = stream_context_create(['http' => ['timeout' => 60, 'follow_location' => 1]]);
    $datos = @file_get_contents($url, false, $ctx);
    return $datos === false ? null : $datos;
}

if (is_file($destino) && filesize($destino) > 50000) {
    echo "La librería ya está instalada en:\n  $destino\nNo se hizo nada.\n";
    exit(0);
}

foreach ($fuentes as $url) {
    echo "Descargando $url ...\n";
    $datos = descargar($url);
    // Comprobación: tamaño razonable (~100 KB) y que realmente defina la clase Html5Qrcode
    if ($datos !== null && strlen($datos) > 50000 && str_contains($datos, 'Html5Qrcode')) {
        if (file_put_contents($destino, $datos) === false) {
            fwrite(STDERR, "No se pudo escribir $destino (revisa los permisos).\n");
            exit(1);
        }
        echo "Listo: " . strlen($datos) . " bytes guardados en\n  $destino\n";
        echo "SHA-256: " . hash('sha256', $datos) . "\n";
        exit(0);
    }
    echo "  No se pudo usar esta fuente.\n";
}

fwrite(STDERR, "No fue posible descargar la librería. Descárgala a mano (ver js/vendor/html5-qrcode/LEEME.txt).\n");
exit(1);
