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

/* El RUT del QR debe coincidir con el RUT maestro de la cuenta conectada. */
$usuarioId = (int)$_SESSION['usuario_id'];
$stmt = $conn->prepare("SELECT rut, estado FROM usuarios WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $usuarioId);
$stmt->execute();
$cuenta = $stmt->get_result()->fetch_assoc();

if (!$cuenta || $cuenta['estado'] !== 'activo' || empty($cuenta['rut'])) {
    responderQR(409, ['ok' => false, 'mensaje' => 'Tu cuenta no tiene un RUT maestro asignado. Solicita al administrador que lo configure antes de escanear.']);
}

$rutMaestro = normalizeRut((string)$cuenta['rut']);
if (!hash_equals($rutMaestro, $rutQR)) {
    audit($conn, $usuarioId, 'QR_IDENTIDAD_RECHAZADA', 'usuarios', $usuarioId, 'El RUT del QR no coincide con el usuario conectado.');
    responderQR(403, ['ok' => false, 'mensaje' => 'Identidad no válida: el código QR pertenece a otra persona.']);
}

audit($conn, $usuarioId, 'QR_IDENTIDAD_VALIDADA', 'usuarios', $usuarioId, 'Validación de identidad mediante QR correcta.');
responderQR(200, ['ok' => true, 'mensaje' => 'Identidad validada correctamente.', 'rut' => $rutMaestro]);
