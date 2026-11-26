<?php
declare(strict_types=1);
date_default_timezone_set('America/Santiago');

require_once __DIR__ . '/../../../backend/check_user.php';
require_once __DIR__ . '/../../../backend/conexion.php';

// El supervisor solo puede consultar. No se permite que otro rol use esta vista.
if (($_SESSION['rol'] ?? '') !== 'supervisor') {
    http_response_code(403);
    exit('Acceso restringido. Esta vista es exclusiva para supervisores.');
}

$csrf = csrfToken();

$sqlCargador = "SELECT oc.*, u.username AS usuario_registro
                FROM operador_cargador oc
                LEFT JOIN usuarios u ON u.id = oc.usuario_id
                ORDER BY oc.fecha_registro DESC, oc.id DESC";
$sqlOperador = "SELECT o.*, u.username AS usuario_registro
                FROM operador o
                LEFT JOIN usuarios u ON u.id = o.usuario_id
                ORDER BY o.fecha_registro DESC, o.id DESC";

$resCargador = $conn->query($sqlCargador);
$resOperador = $conn->query($sqlOperador);
if (!$resCargador || !$resOperador) {
    http_response_code(500);
    exit('No fue posible cargar los registros. Verifica la estructura de la base de datos.');
}

$cargadores = $resCargador->fetch_all(MYSQLI_ASSOC);
$operadores = $resOperador->fetch_all(MYSQLI_ASSOC);

$totalCargador = array_sum(array_map(static fn($r) => (float)($r['litros'] ?? 0), $cargadores));
$totalOperador = array_sum(array_map(static fn($r) => (float)($r['litros'] ?? 0), $operadores));

function mostrarValorSupervisor(array $fila, string $campo): string {
    return e($fila[$campo] ?? '—');
}

function tablaSupervisor(array $filas, string $tipo): void {
    $campos = $tipo === 'cargador'
        ? [
            'id' => 'ID', 'nombres' => 'Nombres', 'apellido_paterno' => 'Apellido paterno',
            'apellido_materno' => 'Apellido materno', 'rut' => 'RUT', 'turno' => 'Turno',
            'codigo' => 'Código maquinaria', 'ubicacion' => 'Ubicación', 'patente' => 'Patente',
            'horometro' => 'Horómetro', 'litros' => 'Litros', 'remanente' => 'Remanente',
            'observacion' => 'Observación', 'usuario_registro' => 'Usuario', 'fecha_registro' => 'Fecha',
            'eliminado_at' => 'Eliminado el'
        ]
        : [
            'id' => 'ID', 'nombres' => 'Nombres', 'apellido_paterno' => 'Apellido paterno',
            'apellido_materno' => 'Apellido materno', 'rut' => 'RUT', 'turno' => 'Turno',
            'ubicacion' => 'Ubicación', 'codigo_maquinaria' => 'Código maquinaria', 'patente' => 'Patente',
            'equipo' => 'Equipo', 'horometro' => 'Horómetro', 'kilometro' => 'Kilómetro',
            'litros' => 'Litros', 'consumo' => 'Consumo', 'observacion' => 'Observación',
            'usuario_registro' => 'Usuario', 'fecha_registro' => 'Fecha', 'eliminado_at' => 'Eliminado el'
        ];
    ?>
    <div class="tabla-contenedor">
        <table>
            <thead><tr>
                <?php foreach ($campos as $titulo): ?><th><?= e($titulo) ?></th><?php endforeach; ?>
                <th>Estado</th>
            </tr></thead>
            <tbody>
            <?php if (!$filas): ?>
                <tr><td colspan="<?= count($campos) + 1 ?>">No existen registros.</td></tr>
            <?php else: foreach ($filas as $fila): ?>
                <tr>
                    <?php foreach ($campos as $campo => $titulo): ?>
                        <td><?= mostrarValorSupervisor($fila, $campo) ?></td>
                    <?php endforeach; ?>
                    <td>
                        <?php if (!empty($fila['eliminado_at'])): ?>
                            <span class="estado eliminado">Eliminado</span>
                        <?php else: ?>
                            <span class="estado vigente">Vigente</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vista de Supervisor - Control de Combustible</title>
    <link rel="stylesheet" href="../../../css/programa/style.css">
    <link rel="stylesheet" href="../../../css/programa/supervisor.css">
</head>
<body class="supervisor-page">
<header class="supervisor-header">
    <div>
        <h1>Vista de Supervisor</h1>
        <p>Consulta general de registros · Acceso de solo lectura</p>
    </div>
    <div class="supervisor-user">
        <span>Sesión: <?= e($_SESSION['username'] ?? 'Supervisor') ?></span>
        <form method="POST" action="../../../backend/logout.php">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <button type="submit">Cerrar sesión</button>
        </form>
    </div>
</header>

<main class="supervisor-main">
    <section class="resumen-supervisor">
        <article><span>Registros operador cargador</span><strong><?= count($cargadores) ?></strong></article>
        <article><span>Litros operador cargador</span><strong><?= number_format($totalCargador, 2, ',', '.') ?> L</strong></article>
        <article><span>Registros operador</span><strong><?= count($operadores) ?></strong></article>
        <article><span>Litros operador</span><strong><?= number_format($totalOperador, 2, ',', '.') ?> L</strong></article>
        <article><span>Total general</span><strong><?= number_format($totalCargador + $totalOperador, 2, ',', '.') ?> L</strong></article>
    </section>

    <section class="supervisor-seccion">
        <h2>Registros de operador cargador</h2>
        <p><?= count($cargadores) ?> registros encontrados. Se muestran registros vigentes y eliminados.</p>
        <?php tablaSupervisor($cargadores, 'cargador'); ?>
    </section>

    <section class="supervisor-seccion">
        <h2>Registros de operador</h2>
        <p><?= count($operadores) ?> registros encontrados. Se muestran registros vigentes y eliminados.</p>
        <?php tablaSupervisor($operadores, 'operador'); ?>
    </section>
</main>
</body>
</html>
