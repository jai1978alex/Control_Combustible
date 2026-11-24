<?php
/*
Derechos de los titulares de datos personales (Ley 19.628 y Ley 21.719).

Pantalla solo para administradores. Permite, a partir del RUT de una persona:
  - Acceso / portabilidad: descargar todos sus datos en un archivo JSON.
  - Cancelación / supresión: anonimizar sus datos personales (los registros de combustible se
    conservan como estadística, pero ya no se pueden asociar a la persona).
Cada acción queda en la auditoría (sin guardar el RUT completo).
*/

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/conexion.php';
require_once __DIR__ . '/../../../backend/security.php';
require_once __DIR__ . '/../../../backend/rut_helper.php';

requireAdmin();

$adminId  = (int)$_SESSION['usuario_id'];
$mensaje  = '';
$error    = '';
$resumen  = null;
$rutInput = '';

// Expresión SQL que deja un RUT guardado en forma canónica (sin puntos ni guion, mayúsculas)
const SQL_RUT_CANONICO = "UPPER(REPLACE(REPLACE(rut,'.',''),'-',''))";

// Enmascara un RUT para dejarlo en auditoría sin guardarlo completo (ej.: 12******-5)
function rutEnmascarado(string $rutCanonico): string {
    $dv = substr($rutCanonico, -1);
    $cuerpo = substr($rutCanonico, 0, -1);
    return substr($cuerpo, 0, 2) . str_repeat('*', max(strlen($cuerpo) - 2, 0)) . '-' . $dv;
}

// Cuenta los datos asociados a un RUT canónico
function resumenTitular(mysqli $conn, string $rutCan): array {
    $r = ['operador' => 0, 'cargador' => 0, 'cuenta' => null, 'auditoria' => 0];

    foreach (['operador' => 'operador', 'cargador' => 'operador_cargador'] as $clave => $tabla) {
        $st = $conn->prepare('SELECT COUNT(*) c FROM `' . $tabla . '` WHERE ' . SQL_RUT_CANONICO . ' = ?');
        $st->bind_param('s', $rutCan);
        $st->execute();
        $r[$clave] = (int)$st->get_result()->fetch_assoc()['c'];
    }

    $st = $conn->prepare('SELECT id, username, email, rol, estado, created_at FROM usuarios WHERE ' . SQL_RUT_CANONICO . ' = ? LIMIT 1');
    $st->bind_param('s', $rutCan);
    $st->execute();
    $r['cuenta'] = $st->get_result()->fetch_assoc() ?: null;

    if ($r['cuenta']) {
        $uid = (int)$r['cuenta']['id'];
        $st = $conn->prepare('SELECT COUNT(*) c FROM auditoria WHERE usuario_id = ?');
        $st->bind_param('i', $uid);
        $st->execute();
        $r['auditoria'] = (int)$st->get_result()->fetch_assoc()['c'];
    }
    return $r;
}

// Entrega todos los datos del titular como archivo JSON (derecho de acceso y portabilidad)
function exportarTitular(mysqli $conn, string $rutCan, array $resumen, int $adminId): never {
    $datos = ['generado_en' => date('c'), 'rut' => $rutCan, 'cuenta' => null,
              'registros_operador' => [], 'registros_cargador' => [], 'auditoria' => []];

    foreach (['registros_operador' => 'operador', 'registros_cargador' => 'operador_cargador'] as $clave => $tabla) {
        $st = $conn->prepare('SELECT * FROM `' . $tabla . '` WHERE ' . SQL_RUT_CANONICO . ' = ? ORDER BY fecha_registro');
        $st->bind_param('s', $rutCan);
        $st->execute();
        $datos[$clave] = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    if ($resumen['cuenta']) {
        $uid = (int)$resumen['cuenta']['id'];
        // Nunca se incluye el hash de la contraseña
        $st = $conn->prepare('SELECT id, username, email, rut, rol, estado, created_at, updated_at FROM usuarios WHERE id = ?');
        $st->bind_param('i', $uid);
        $st->execute();
        $datos['cuenta'] = $st->get_result()->fetch_assoc();

        $st = $conn->prepare('SELECT accion, tabla_afectada, registro_id, detalle, ip, user_agent, fecha_registro FROM auditoria WHERE usuario_id = ? ORDER BY fecha_registro');
        $st->bind_param('i', $uid);
        $st->execute();
        $datos['auditoria'] = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    audit($conn, $adminId, 'DERECHOS_EXPORTAR', 'usuarios', null,
        'RUT ' . rutEnmascarado($rutCan) . '; operador=' . $resumen['operador'] . '; cargador=' . $resumen['cargador']);

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="datos_titular_' . date('Ymd_His') . '.json"');
    echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

// Anonimiza los datos personales del titular (derecho de cancelación / supresión)
function anonimizarTitular(mysqli $conn, string $rutCan, array $resumen, int $adminId): void {
    $variantes = [$rutCan, substr($rutCan, 0, -1) . '-' . substr($rutCan, -1)];
    $cuerpo = substr($rutCan, 0, -1);
    $variantes[] = number_format((int)$cuerpo, 0, '', '.') . '-' . substr($rutCan, -1);

    $conn->begin_transaction();
    try {
        foreach (['operador', 'operador_cargador'] as $tabla) {
            $st = $conn->prepare(
                'UPDATE `' . $tabla . "` SET nombres='ANONIMIZADO', apellido_paterno='ANONIMIZADO', apellido_materno='ANONIMIZADO',
                    rut='ANONIMIZADO', ubicacion='ANONIMIZADO', observacion=NULL WHERE " . SQL_RUT_CANONICO . ' = ?'
            );
            $st->bind_param('s', $rutCan);
            $st->execute();
        }

        if ($resumen['cuenta']) {
            $uid = (int)$resumen['cuenta']['id'];
            $username = (string)$resumen['cuenta']['username'];

            // La bitácora conserva qué se hizo, pero sin IP, navegador, RUT ni usuario de la persona
            $st = $conn->prepare("UPDATE auditoria SET ip='0.0.0.0', user_agent=NULL WHERE usuario_id = ?");
            $st->bind_param('i', $uid);
            $st->execute();

            $st = $conn->prepare("UPDATE auditoria SET detalle = REPLACE(detalle, ?, 'anonimizado') WHERE usuario_id = ? AND detalle LIKE ?");
            $like = '%' . $username . '%';
            $st->bind_param('sis', $username, $uid, $like);
            $st->execute();

            // La cuenta queda inactiva, sin RUT ni correo y con una clave inutilizable
            $nuevoNombre = 'anonimizado_' . $uid;
            $claveInutil = password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT);
            $st = $conn->prepare("UPDATE usuarios SET username=?, email=NULL, rut=NULL, password=?, estado='inactivo', debe_cambiar_password=0 WHERE id = ?");
            $st->bind_param('ssi', $nuevoNombre, $claveInutil, $uid);
            $st->execute();

            $st = $conn->prepare('DELETE FROM recuperacion_password WHERE usuario_id = ?');
            $st->bind_param('i', $uid);
            $st->execute();
        }

        // Cualquier mención del RUT en el detalle de la bitácora (cargas, QR, intentos rechazados)
        foreach (array_unique($variantes) as $v) {
            $st = $conn->prepare("UPDATE auditoria SET detalle = REPLACE(detalle, ?, 'RUT anonimizado') WHERE detalle LIKE ?");
            $like = '%' . $v . '%';
            $st->bind_param('ss', $v, $like);
            $st->execute();
        }

        audit($conn, $adminId, 'DERECHOS_ANONIMIZAR', 'usuarios', $resumen['cuenta'] ? (int)$resumen['cuenta']['id'] : null,
            'RUT ' . rutEnmascarado($rutCan) . '; operador=' . $resumen['operador'] . '; cargador=' . $resumen['cargador'] . '; cuenta=' . ($resumen['cuenta'] ? 'si' : 'no'));
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

// ---- Procesa la búsqueda o la acción pedida ----
$origen = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$rutInput = normalizeRut((string)($origen['rut'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
}

if ($rutInput !== '') {
    if (!validarFormatoRut($rutInput) || !validarRut($rutInput)) {
        $error = 'El RUT no es válido. Usa el formato 12.345.678-5.';
    } else {
        $rutCan = rutCanonico($rutInput);
        $resumen = resumenTitular($conn, $rutCan);
        $accion = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['accion'] ?? '') : '';

        if ($accion === 'exportar') {
            exportarTitular($conn, $rutCan, $resumen, $adminId);
        }

        if ($accion === 'anonimizar') {
            $confirmacion = trim((string)($_POST['confirmacion'] ?? ''));
            $clave = (string)($_POST['current_password'] ?? '');
            $yo = $conn->prepare('SELECT password FROM usuarios WHERE id = ? LIMIT 1');
            $yo->bind_param('i', $adminId);
            $yo->execute();
            $miFila = $yo->get_result()->fetch_assoc();

            if ($resumen['operador'] + $resumen['cargador'] === 0 && !$resumen['cuenta']) {
                $error = 'No hay datos asociados a ese RUT.';
            } elseif ($resumen['cuenta'] && $resumen['cuenta']['rol'] === 'admin') {
                $error = 'La cuenta de ese RUT es de administrador. Cámbiale el rol o elimínala primero desde el panel y luego anonimiza.';
            } elseif ($confirmacion !== 'ANONIMIZAR') {
                $error = 'Escribe ANONIMIZAR (en mayúsculas) para confirmar.';
            } elseif (!$miFila || !password_verify($clave, (string)$miFila['password'])) {
                $error = 'Tu contraseña actual no es correcta.';
            } else {
                try {
                    anonimizarTitular($conn, $rutCan, $resumen, $adminId);
                    $mensaje = 'Datos anonimizados correctamente.';
                    $resumen = resumenTitular($conn, $rutCan);
                } catch (Throwable $e) {
                    error_log('derechos_datos anonimizar: ' . $e->getMessage());
                    $error = 'No se pudo anonimizar. No se hizo ningún cambio.';
                }
            }
        }
    }
}

$csrf = csrfToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Derechos de datos personales</title>
    <link rel="stylesheet" href="../../../css/programa/crear_usuario.css">
    <link rel="stylesheet" href="../../../css/programa/derechos_datos.css">
</head>
<body>
<div class="container">
    <div class="card">
        <h1>Derechos de datos personales</h1>
        <p class="intro">Atiende solicitudes de acceso, portabilidad y supresión de un trabajador a partir de su RUT.</p>

        <?php if ($mensaje): ?><div class="success"><?= e($mensaje) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>

        <form method="GET">
            <div class="form-group">
                <label>RUT del titular</label>
                <input type="text" name="rut" maxlength="12" placeholder="12.345.678-5" value="<?= e($rutInput) ?>" required>
            </div>
            <button type="submit">Buscar datos</button>
        </form>

        <?php if ($resumen): ?>
        <h2>Datos encontrados</h2>
        <table class="resumen">
            <tr><th>Registros en Operador</th><td><?= (int)$resumen['operador'] ?></td></tr>
            <tr><th>Registros en Operador Cargador</th><td><?= (int)$resumen['cargador'] ?></td></tr>
            <tr><th>Cuenta de acceso</th><td><?= $resumen['cuenta'] ? e($resumen['cuenta']['username'] . ' (' . $resumen['cuenta']['rol'] . ', ' . $resumen['cuenta']['estado'] . ')') : 'No tiene' ?></td></tr>
            <tr><th>Eventos de auditoría de su cuenta</th><td><?= (int)$resumen['auditoria'] ?></td></tr>
        </table>

        <h2>Entregar sus datos (acceso y portabilidad)</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="rut" value="<?= e($rutInput) ?>">
            <input type="hidden" name="accion" value="exportar">
            <button type="submit">Descargar datos (JSON)</button>
        </form>

        <h2>Eliminar sus datos personales (supresión)</h2>
        <p class="aviso">Reemplaza nombre, apellidos, RUT, ubicación GPS y observaciones por «ANONIMIZADO», desactiva su cuenta y borra su correo, IP y navegador de la bitácora.
            Los litros y fechas se conservan sin poder asociarlos a la persona. <strong>No se puede deshacer.</strong>
            Antes de anonimizar, verifica que no exista una obligación legal de conservar esos datos (laboral, tributaria o una investigación en curso).</p>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="rut" value="<?= e($rutInput) ?>">
            <input type="hidden" name="accion" value="anonimizar">
            <div class="form-group">
                <label>Escribe ANONIMIZAR para confirmar</label>
                <input type="text" name="confirmacion" maxlength="20" autocomplete="off" required>
            </div>
            <div class="form-group">
                <label>Tu contraseña actual</label>
                <input type="password" name="current_password" maxlength="255" autocomplete="current-password" required>
            </div>
            <button type="submit" class="peligro">Anonimizar datos</button>
        </form>
        <?php endif; ?>

        <div class="back"><a href="superadmin.php">← Volver al Panel</a></div>
    </div>
</div>
</body>
</html>
