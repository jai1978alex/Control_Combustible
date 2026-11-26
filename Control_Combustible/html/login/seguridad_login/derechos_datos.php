<?php
/*
Derechos de los titulares de datos personales (Ley 19.628 y Ley 21.719).

Pantalla solo para administradores. Permite, a partir del RUT de una persona:
  - Acceso / portabilidad: descargar todos sus datos en un documento PDF.
La acción queda en la auditoría (sin guardar el RUT completo).
*/

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/conexion.php';
require_once __DIR__ . '/../../../backend/security.php';
require_once __DIR__ . '/../../../backend/rut_helper.php';
require_once __DIR__ . '/../../../backend/pdf_simple.php';

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

// Entrega todos los datos del titular como documento PDF (derecho de acceso y portabilidad)
// Da formato 12.345.678-5 a un RUT canónico (solo números y dígito verificador)
function rutConFormato(string $rutCan): string {
    $dv = substr($rutCan, -1);
    $cuerpo = substr($rutCan, 0, -1);
    return number_format((int)$cuerpo, 0, '', '.') . '-' . $dv;
}

// Convierte un valor de la base de datos en texto legible para el PDF
function valorPdf($valor): string {
    if ($valor === null || $valor === '') return '-';
    return (string)$valor;
}

// Convierte el nombre de una columna (por ejemplo "apellido_paterno") en una etiqueta legible ("Apellido paterno")
function etiquetaColumna(string $columna): string {
    $siglas = ['id' => 'ID', 'rut' => 'RUT', 'ip' => 'IP', 'usuario_id' => 'ID de usuario', 'registro_id' => 'ID de registro'];
    return $siglas[$columna] ?? ucfirst(str_replace('_', ' ', $columna));
}

function exportarTitular(mysqli $conn, string $rutCan, array $resumen, int $adminId): never {
    $registros = ['operador' => [], 'cargador' => []];

    foreach (['operador' => 'operador', 'cargador' => 'operador_cargador'] as $clave => $tabla) {
        $st = $conn->prepare('SELECT * FROM `' . $tabla . '` WHERE ' . SQL_RUT_CANONICO . ' = ? ORDER BY fecha_registro');
        $st->bind_param('s', $rutCan);
        $st->execute();
        $registros[$clave] = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    $cuenta = null;
    $auditoria = [];
    if ($resumen['cuenta']) {
        $uid = (int)$resumen['cuenta']['id'];
        $columnasNombre = columnaExiste($conn, 'usuarios', 'nombres') ? 'nombres, apellido_paterno, apellido_materno, ' : '';
        $st = $conn->prepare('SELECT id, username, ' . $columnasNombre . 'email, rut, rol, estado, created_at, updated_at FROM usuarios WHERE id = ?');
        $st->bind_param('i', $uid);
        $st->execute();
        $cuenta = $st->get_result()->fetch_assoc();

        $st = $conn->prepare('SELECT accion, tabla_afectada, registro_id, detalle, ip, user_agent, fecha_registro FROM auditoria WHERE usuario_id = ? ORDER BY fecha_registro');
        $st->bind_param('i', $uid);
        $st->execute();
        $auditoria = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    audit($conn, $adminId, 'DERECHOS_EXPORTAR', 'usuarios', null,
        'RUT ' . rutEnmascarado($rutCan) . '; operador=' . $resumen['operador'] . '; cargador=' . $resumen['cargador']);

    // Arma el documento PDF con todos los datos del titular
    $pdf = new PdfSimple('Datos personales del titular');
    $pdf->titulo('Datos personales del titular');
    $pdf->parrafo('Documento entregado en ejercicio de los derechos de acceso y portabilidad (Ley 19.628 y Ley 21.719).');
    $pdf->campo('RUT', rutConFormato($rutCan));
    $pdf->campo('Generado el', date('d-m-Y H:i:s'));
    $pdf->campo('Registros en Operador', (string)count($registros['operador']));
    $pdf->campo('Registros en Operador Cargador', (string)count($registros['cargador']));

    $pdf->seccion('Cuenta de acceso');
    if ($cuenta) {
        foreach ($cuenta as $columna => $valor) {
            $pdf->campo(etiquetaColumna((string)$columna), valorPdf($valor));
        }
    } else {
        $pdf->parrafo('Este RUT no tiene una cuenta de acceso asociada.');
    }

    foreach (['operador' => 'Registros como Operador', 'cargador' => 'Registros como Operador Cargador'] as $clave => $titulo) {
        $pdf->seccion($titulo . ' (' . count($registros[$clave]) . ')');
        if (!$registros[$clave]) {
            $pdf->parrafo('Sin registros.');
        }
        foreach ($registros[$clave] as $fila) {
            $pdf->subtitulo('Registro N° ' . valorPdf($fila['id'] ?? '') . ' - ' . valorPdf($fila['fecha_registro'] ?? ''));
            foreach ($fila as $columna => $valor) {
                if ($columna === 'id') continue;
                $pdf->campo(etiquetaColumna((string)$columna), valorPdf($valor));
            }
            $pdf->espacioVertical(4);
        }
    }

    $pdf->seccion('Eventos de auditoría de su cuenta (' . count($auditoria) . ')');
    if (!$auditoria) {
        $pdf->parrafo('Sin eventos.');
    }
    foreach ($auditoria as $fila) {
        $pdf->subtitulo(valorPdf($fila['fecha_registro']) . ' - ' . valorPdf($fila['accion']));
        foreach (['tabla_afectada', 'registro_id', 'detalle', 'ip', 'user_agent'] as $columna) {
            $pdf->campo(etiquetaColumna($columna), valorPdf($fila[$columna] ?? null));
        }
        $pdf->espacioVertical(4);
    }

    $contenido = $pdf->generar();
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="datos_titular_' . date('Ymd_His') . '.pdf"');
    header('Content-Length: ' . strlen($contenido));
    header('X-Content-Type-Options: nosniff');
    echo $contenido;
    exit;
}

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
        <p class="intro">Atiende solicitudes de acceso y portabilidad de un trabajador a partir de su RUT.</p>

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
            <button type="submit">Descargar datos (PDF)</button>
        </form>
        <?php endif; ?>

        <div class="back"><a href="superadmin.php">← Volver al Panel</a></div>
    </div>
</div>
</body>
</html>
