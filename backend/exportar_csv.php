<?php
declare(strict_types=1);
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/security.php';
requireAdmin();

$tipo = (string)($_GET['tipo'] ?? '');
$rutBusqueda = trim((string)($_GET['rut'] ?? ''));

if (!in_array($tipo, ['cargador', 'operador'], true)) {
    http_response_code(400);
    exit('Tipo de exportación no válido.');
}

if ($tipo === 'cargador') {
    $sql = 'SELECT oc.nombres, oc.apellido_paterno, oc.apellido_materno, oc.rut, oc.turno, oc.codigo,
                   oc.ubicacion, oc.patente, oc.horometro, oc.litros, oc.remanente, oc.observacion,
                   u.username, oc.fecha_registro
            FROM operador_cargador oc LEFT JOIN usuarios u ON oc.usuario_id = u.id WHERE oc.eliminado_at IS NULL';
    $encabezado = ['Nombres','Apellido Paterno','Apellido Materno','RUT','Turno','Código','Ubicación','Patente','Horómetro','Litros','Remanente','Observación','Usuario','Fecha registro'];
} else {
    $sql = 'SELECT o.nombres, o.apellido_paterno, o.apellido_materno, o.rut, o.turno, o.codigo_maquinaria,
                   o.ubicacion, o.patente, o.equipo, o.horometro, o.kilometro, o.litros, o.consumo, o.observacion,
                   u.username, o.fecha_registro
            FROM operador o LEFT JOIN usuarios u ON o.usuario_id = u.id WHERE o.eliminado_at IS NULL';
    $encabezado = ['Nombres','Apellido Paterno','Apellido Materno','RUT','Turno','Código Maquinaria','Ubicación','Patente','Equipo','Horómetro','Kilómetro','Litros','Consumo','Observación','Usuario','Fecha registro'];
}

if ($rutBusqueda !== '') {
    $sql .= ' AND ';
    $alias = $tipo === 'cargador' ? 'oc' : 'o';
    $sql .= " {$alias}.rut LIKE ?";
}
$sql .= ($tipo === 'cargador' ? ' ORDER BY oc.fecha_registro DESC' : ' ORDER BY o.fecha_registro DESC');

$stmt = $conn->prepare($sql);
if ($rutBusqueda !== '') {
    $like = '%' . $rutBusqueda . '%';
    $stmt->bind_param('s', $like);
}
$stmt->execute();
$resultado = $stmt->get_result();

audit($conn, (int)$_SESSION['usuario_id'], 'EXPORTAR_CSV', $tipo === 'cargador' ? 'operador_cargador' : 'operador', null, $rutBusqueda !== '' ? 'rut='.$rutBusqueda : null);

$nombreArchivo = 'registros_' . $tipo . '_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$salida = fopen('php://output', 'w');
/* BOM UTF-8 para que Excel reconozca tildes y ñ correctamente. */
fwrite($salida, "\xEF\xBB\xBF");
fputcsv($salida, $encabezado, ';');

/*
 * Mitigación de "CSV/Formula Injection": si un valor empieza con un
 * carácter que Excel/Sheets podría interpretar como inicio de fórmula
 * (=, +, -, @, tab, retorno de carro), se antepone un apóstrofo para que
 * se trate siempre como texto literal al abrir el archivo.
 */
function csvSeguro(mixed $valor): string {
    $v = (string)($valor ?? '');
    if ($v !== '' && in_array($v[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
        $v = "'" . $v;
    }
    return $v;
}

while ($fila = $resultado->fetch_assoc()) {
    $fila = array_map('csvSeguro', array_values($fila));
    fputcsv($salida, $fila, ';');
}

fclose($salida);
exit;
