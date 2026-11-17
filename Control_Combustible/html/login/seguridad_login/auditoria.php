<?php
declare(strict_types=1);
require_once __DIR__.'/../../../backend/conexion.php';
require_once __DIR__.'/../../../backend/security.php';
requireAdmin();
$csrf = csrfToken();

/* ------------------------------------------------------------------
   FILTROS (ambos son opcionales: se puede buscar por uno, por los dos
   o por ninguno)
   ------------------------------------------------------------------ */
$fechaBusqueda = trim((string)($_GET['fecha'] ?? ''));
$rutBusqueda   = trim((string)($_GET['rut'] ?? ''));
$modoImprimir  = ((string)($_GET['imprimir'] ?? '') === '1');
$avisoFiltro   = '';

/* Fecha: debe ser una fecha real con formato AAAA-MM-DD. */
$fechaInicio = $fechaFin = null;
if ($fechaBusqueda !== '') {
    $f = DateTime::createFromFormat('!Y-m-d', $fechaBusqueda);
    if ($f && $f->format('Y-m-d') === $fechaBusqueda) {
        $fechaInicio = $f->format('Y-m-d 00:00:00');
        $fechaFin    = $f->modify('+1 day')->format('Y-m-d 00:00:00');
    } else {
        $avisoFiltro  = 'La fecha ingresada no es válida; se ignoró ese filtro.';
        $fechaBusqueda = '';
    }
}

/* RUT: se aceptan con o sin puntos y guion (12.345.678-5 o 123456785). */
$rutLimpio = '';
if ($rutBusqueda !== '') {
    $rutLimpio = preg_replace('/[^0-9K]/', '', strtoupper($rutBusqueda)) ?? '';
    if ($rutLimpio === '' || strlen($rutLimpio) > 12) {
        $avisoFiltro  = 'El RUT ingresado no es válido; se ignoró ese filtro.';
        $rutBusqueda  = '';
        $rutLimpio    = '';
    }
}

/* La columna usuarios.rut puede no existir en instalaciones antiguas. */
$tieneRutCuenta = false;
$colRut = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'rut'");
if ($colRut && $colRut->num_rows > 0) { $tieneRutCuenta = true; }
$campoRutCuenta = $tieneRutCuenta ? 'u.rut' : 'NULL';

/* WHERE dinámico con parámetros (sin concatenar datos del usuario). */
$where = []; $tipos = ''; $params = [];
if ($fechaInicio !== null) {
    $where[] = 'a.fecha_registro >= ? AND a.fecha_registro < ?';
    $tipos  .= 'ss'; $params[] = $fechaInicio; $params[] = $fechaFin;
}
if ($rutLimpio !== '') {
    /* Coincide si el RUT aparece en el detalle de la acción o si la acción
       la realizó la cuenta que tiene ese RUT. Se ignoran puntos y guiones. */
    $like = '%'.$rutLimpio.'%';
    $cond = "(REPLACE(REPLACE(IFNULL(a.detalle,''),'.',''),'-','') LIKE ? AND a.detalle LIKE '%RUT%')";
    $tipos .= 's'; $params[] = $like;
    if ($tieneRutCuenta) {
        $cond .= " OR REPLACE(REPLACE(IFNULL(u.rut,''),'.',''),'-','') LIKE ?";
        $tipos .= 's'; $params[] = $like;
    }
    $where[] = '('.$cond.')';
}
$sqlWhere = $where ? ('WHERE '.implode(' AND ', $where)) : '';
$sqlFrom  = 'FROM auditoria a LEFT JOIN usuarios u ON a.usuario_id=u.id';

/* Total de registros que cumplen el filtro. */
$stc = $conn->prepare("SELECT COUNT(*) c $sqlFrom $sqlWhere");
if ($params) { $stc->bind_param($tipos, ...$params); }
$stc->execute();
$totalFilas = (int)$stc->get_result()->fetch_assoc()['c'];

/* Filas: una página en pantalla, o todas (hasta el tope) al imprimir. */
$limiteImpresion = 10000;
$limite = 50;
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$sqlSelect = "SELECT a.*, u.username, $campoRutCuenta AS rut_cuenta $sqlFrom $sqlWhere ORDER BY a.fecha_registro DESC, a.id DESC";
if ($modoImprimir) {
    $st = $conn->prepare($sqlSelect.' LIMIT ?');
    $t2 = $tipos.'i'; $p2 = array_merge($params, [$limiteImpresion]);
    $st->bind_param($t2, ...$p2);
    audit($conn, (int)$_SESSION['usuario_id'], 'IMPRIMIR_AUDITORIA', 'auditoria', null,
        'Filtros: fecha='.($fechaBusqueda ?: 'todas').'; rut='.($rutBusqueda ?: 'todos').'; filas='.min($totalFilas, $limiteImpresion));
} else {
    $totalPaginas = max(1, (int)ceil($totalFilas / $limite));
    $pagina = min($pagina, $totalPaginas);
    $offset = ($pagina - 1) * $limite;
    $st = $conn->prepare($sqlSelect.' LIMIT ? OFFSET ?');
    $t2 = $tipos.'ii'; $p2 = array_merge($params, [$limite, $offset]);
    $st->bind_param($t2, ...$p2);
}
$st->execute();
$filas = $st->get_result()->fetch_all(MYSQLI_ASSOC);

/* RUT(s) mencionados dentro del detalle de una acción. */
function rutsDelDetalle(?string $detalle): string {
    if ($detalle === null || $detalle === '') { return ''; }
    preg_match_all('/\b\d{1,2}\.?\d{3}\.?\d{3}-?[0-9kK]\b/', $detalle, $m);
    return implode(', ', array_values(array_unique($m[0] ?? [])));
}

/* Enlaces que conservan los filtros. */
$qsFiltros = array_filter([
    'fecha' => $fechaBusqueda,
    'rut'   => $rutBusqueda,
], static fn($v) => $v !== '');
$urlPagina = static function (int $p) use ($qsFiltros): string {
    return 'auditoria.php?'.http_build_query($qsFiltros + ['pagina' => $p]);
};
$urlImprimir = 'auditoria.php?'.http_build_query($qsFiltros + ['imprimir' => 1]);

$descFiltro = [];
if ($fechaBusqueda !== '') { $descFiltro[] = 'Fecha: '.date('d-m-Y', strtotime($fechaBusqueda)); }
if ($rutBusqueda !== '')   { $descFiltro[] = 'RUT: '.$rutBusqueda; }
$textoFiltro = $descFiltro ? implode(' · ', $descFiltro) : 'Sin filtros (todos los registros)';
?><!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Auditoría - <?=e(appName())?></title>
<link rel="stylesheet" href="../../../css/programa/superadmin.css">
<?php if ($modoImprimir): ?>
<link rel="stylesheet" href="../../../css/programa/auditoria_imprimir.css">
<script src="../../../js/programa/imprimir.js" defer></script>
<?php endif; ?>
</head><body>
<?php if ($modoImprimir): ?>
<main class="hoja-auditoria">
  <header class="cabecera-auditoria">
    <div>
      <h1>Auditoría del sistema · <?=e(appName())?></h1>
      <p><?=e($textoFiltro)?></p>
    </div>
    <div class="meta-auditoria">
      <div>Generado: <strong><?=e(date('d-m-Y H:i'))?></strong></div>
      <div>Por: <strong><?=e($_SESSION['username'] ?? 'Administrador')?></strong></div>
      <div>Registros: <strong><?=e(count($filas))?></strong><?php if ($totalFilas > count($filas)): ?> de <?=e($totalFilas)?><?php endif; ?></div>
    </div>
  </header>
  <?php if ($totalFilas > $limiteImpresion): ?>
    <p class="aviso-impresion">Se imprimen los <?=e($limiteImpresion)?> registros más recientes de <?=e($totalFilas)?>. Afina el filtro para ver el resto.</p>
  <?php endif; ?>
  <table class="tabla-auditoria">
    <thead><tr><th>Fecha</th><th>Usuario</th><th>RUT cuenta</th><th>Acción</th><th>Tabla</th><th>ID</th><th>RUT en detalle</th><th>Detalle</th><th>IP</th></tr></thead>
    <tbody>
    <?php if (!$filas): ?><tr><td colspan="9" class="sin-datos">No hay registros para el filtro indicado.</td></tr><?php endif; ?>
    <?php foreach ($filas as $a): ?>
      <tr>
        <td><?=e($a['fecha_registro'])?></td>
        <td><?=e($a['username'] ?? 'Sistema')?></td>
        <td><?=e($a['rut_cuenta'] ?? '')?></td>
        <td><?=e($a['accion'])?></td>
        <td><?=e($a['tabla_afectada'])?></td>
        <td><?=e($a['registro_id'])?></td>
        <td><?=e(rutsDelDetalle($a['detalle'] ?? null))?></td>
        <td><?=e($a['detalle'])?></td>
        <td><?=e($a['ip'])?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <div class="acciones-impresion">
    <button type="button" id="btnImprimir">🖨️ Imprimir</button>
    <button type="button" class="secundario" id="btnCerrar">Cerrar</button>
  </div>
</main>
<?php else: ?>
<main class="admin-container"><section class="busqueda-card">
  <div class="acciones"><a class="btn btn-limpiar" href="superadmin.php">← Volver</a></div>
  <h1>Auditoría del sistema</h1>
  <p>Registro de acciones administrativas y operacionales. Solo lectura.</p>

  <form method="GET" action="auditoria.php" class="busqueda-form auditoria-filtros">
    <div class="campo-filtro">
      <label for="fecha">Buscar por fecha</label>
      <input type="date" id="fecha" name="fecha" value="<?=e($fechaBusqueda)?>">
    </div>
    <div class="campo-filtro">
      <label for="rut">Buscar por RUT</label>
      <input type="text" id="rut" name="rut" value="<?=e($rutBusqueda)?>" placeholder="12.345.678-5" maxlength="12" autocomplete="off">
    </div>
    <button class="btn btn-crear" type="submit">🔍 Buscar</button>
    <a class="btn btn-limpiar" href="auditoria.php">Limpiar</a>
    <a class="btn btn-print" target="_blank" rel="noopener noreferrer" href="<?=e($urlImprimir)?>">🖨️ Imprimir todo</a>
  </form>
  <p class="ayuda-busqueda">Puedes buscar solo por fecha, solo por RUT o combinar ambos. El RUT se puede escribir con o sin puntos y guion. <strong><?=e($textoFiltro)?></strong> — <?=e($totalFilas)?> registro(s).</p>
  <?php if ($avisoFiltro !== ''): ?><div class="mensaje-admin error"><?=e($avisoFiltro)?></div><?php endif; ?>

  <div class="tabla-contenedor"><table>
    <thead><tr><th>Fecha</th><th>Usuario</th><th>RUT cuenta</th><th>Acción</th><th>Tabla</th><th>ID</th><th>Detalle</th><th>IP</th></tr></thead>
    <tbody>
    <?php if (!$filas): ?><tr><td colspan="8">No hay registros para el filtro indicado.</td></tr><?php endif; ?>
    <?php foreach ($filas as $a): ?>
      <tr>
        <td><?=e($a['fecha_registro'])?></td>
        <td><?=e($a['username'] ?? 'Sistema')?></td>
        <td><?=e($a['rut_cuenta'] ?? '')?></td>
        <td><?=e($a['accion'])?></td>
        <td><?=e($a['tabla_afectada'])?></td>
        <td><?=e($a['registro_id'])?></td>
        <td><?=e($a['detalle'])?></td>
        <td><?=e($a['ip'])?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>

  <?php if ($totalPaginas > 1): ?>
  <nav class="paginador">
    <?php if ($pagina > 1): ?><a class="btn btn-limpiar" href="<?=e($urlPagina($pagina - 1))?>">« Anterior</a><?php endif; ?>
    <span class="paginador-info">Página <?=$pagina?> de <?=$totalPaginas?> (<?=$totalFilas?> registros)</span>
    <?php if ($pagina < $totalPaginas): ?><a class="btn btn-limpiar" href="<?=e($urlPagina($pagina + 1))?>">Siguiente »</a><?php endif; ?>
  </nav>
  <?php endif; ?>
</section></main>
<?php endif; ?>
</body></html>
