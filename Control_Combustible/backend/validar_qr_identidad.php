<?php
declare(strict_types=1);

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/rut_helper.php';

header('Content-Type: application/json; charset=utf-8');
requireLogin();
requirePost();
verifyCsrf();

function responderQR(int $estado, array $datos): never {
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$contenido = trim((string)($_POST['qr'] ?? ''));
if ($contenido === '' || strlen($contenido) > 10000) {
    responderQR(400, ['ok' => false, 'mensaje' => 'El código QR está vacío o no es válido.']);
}

/* Leer el QR como JSON o como texto con formato campo: valor. */
$datos = json_decode($contenido, true);
if (!is_array($datos)) {
    $datos = [];
    foreach (preg_split('/[\r\n;|]+/', $contenido) as $parte) {
        if (preg_match('/^\s*([^:=]+?)\s*[:=]\s*(.*?)\s*$/u', $parte, $coincidencia)) {
            $datos[$coincidencia[1]] = $coincidencia[2];
        }
    }
}

$rutQR = '';
foreach ($datos as $clave => $valor) {
    $claveNormalizada = strtolower(preg_replace('/[^a-z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string)$clave) ?: (string)$clave));
    if (in_array($claveNormalizada, ['rut', 'documento', 'documentoidentidad'], true) && is_scalar($valor)) {
        $rutQR = normalizeRut((string)$valor);
        break;
    }
}

if ($rutQR === '' || !validarFormatoRut($rutQR) || !validarRut($rutQR)) {
    responderQR(422, ['ok' => false, 'mensaje' => 'El QR no contiene un RUT válido.']);
}

/* El RUT del QR puede ser el de cualquier operador; solo se exige formato y dígito verificador válidos (ya comprobados arriba). */
$usuarioId = (int)$_SESSION['usuario_id'];
audit($conn, $usuarioId, 'QR_IDENTIDAD_VALIDADA', 'usuarios', $usuarioId, 'QR con RUT válido leído: ' . $rutQR);
responderQR(200, ['ok' => true, 'mensaje' => 'RUT del QR validado correctamente.', 'rut' => $rutQR]);
