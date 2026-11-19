<?php
/*
 * Sitio Web Creado por Jerez.
 * Direccion: Miraflorez #1280
 * Quintero - Chile
 * jaime.jerez1978@gmail.com
 * https://www.
 * Creado, Programado y Diseñado por Jerez.
 * JJA
 */

/* -------------------------------------------------------------------------------------------------------------
   ------------------------------------- INICIO Jerez reportes.php ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

declare(strict_types=1);

/* TÍTULO: CARGAR LOS ARCHIVOS NECESARIOS Y PROTEGER LA PÁGINA */

// esta parte carga la conexión y la seguridad
require_once __DIR__.'/../../../backend/conexion.php'; require_once __DIR__.'/../../../backend/security.php';
// solo los administradores pueden entrar
requireAdmin();

/* TÍTULO: REVISAR SI EXISTEN LAS VISTAS DE LA BASE DE DATOS */

// el reporte necesita tres vistas de la base de datos; esta parte revisa cuáles existen realmente

// GUARDAR LAS VISTAS NECESARIAS, LAS QUE EXISTEN Y LAS QUE FALTAN
$vistasNecesarias = ['v_litros_por_mes','v_litros_por_maquinaria','v_resumen_por_usuario'];
$existentes = [];
$q = $conn->query("SELECT TABLE_NAME FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE()");
if ($q) { while ($f = $q->fetch_assoc()) { $existentes[] = $f['TABLE_NAME']; } }
// si falta alguna vista, no se cargan los datos y se muestra un aviso
$faltantes = array_values(array_diff($vistasNecesarias, $existentes));

/* TÍTULO: CARGAR LOS DATOS DE LAS VISTAS */

// GUARDAR LOS DATOS DE MESES, MAQUINARIAS Y USUARIOS (empiezan vacíos)
$meses = $maquinarias = $usuarios = []; $totalMaquinarias = 0;
if (!$faltantes) {
    // litros y registros por mes y origen (cargador u operador), del mes más nuevo al más antiguo
    $r = $conn->query("SELECT mes, origen, cantidad_registros, litros_total, litros_promedio FROM v_litros_por_mes ORDER BY mes DESC, origen");
    while ($f = $r->fetch_assoc()) { $meses[] = $f; }
    // total de maquinarias (sin el límite de 50 de la consulta siguiente)
    $totalMaquinarias = (int)$conn->query('SELECT COUNT(*) c FROM v_litros_por_maquinaria')->fetch_assoc()['c'];
    // las 50 maquinarias con más litros (el gráfico usa solo las 10 primeras)
    $r = $conn->query("SELECT patente, codigo_maquinaria, cantidad_registros, litros_total, primer_registro, ultimo_registro, horometro_maximo FROM v_litros_por_maquinaria ORDER BY litros_total DESC LIMIT 50");
    while ($f = $r->fetch_assoc()) { $maquinarias[] = $f; }
    // litros y actividad de cada cuenta, de la que tiene más litros a la que tiene menos
    $r = $conn->query("SELECT usuario_id, username, rol, estado, cantidad_registros, litros_total, ultima_actividad FROM v_resumen_por_usuario ORDER BY litros_total DESC, username");
    while ($f = $r->fetch_assoc()) { $usuarios[] = $f; }
}

/* TÍTULO: FORMATO DE LOS NÚMEROS */

// esta función muestra un número con 2 decimales (coma para decimales y punto para miles)
function fl(mixed $v): string { return number_format((float)$v, 2, ',', '.'); }
// esta función muestra un número entero con punto para miles
function fi(mixed $v): string { return number_format((int)$v, 0, ',', '.'); }

/* TÍTULO: DATOS PARA LOS GRÁFICOS Y LAS TARJETAS */

// ordenar los litros por mes, tomar los últimos 12 y dejarlos del más antiguo al más nuevo
$porMes = [];
foreach ($meses as $m) { $porMes[$m['mes']][$m['origen']] = (float)$m['litros_total']; }
krsort($porMes);
$porMes = array_reverse(array_slice($porMes, 0, 12, true), true);

// GUARDAR EL TOTAL DE LITROS Y DE REGISTROS (usuarios activos = cuentas con al menos un registro)
$totalLitros = 0.0; $totalRegistros = 0;
foreach ($meses as $m) { $totalLitros += (float)$m['litros_total']; $totalRegistros += (int)$m['cantidad_registros']; }
$usuariosActivos = count(array_filter($usuarios, fn($u) => (int)$u['cantidad_registros'] > 0));

/* TÍTULO: GRÁFICOS CREADOS EN EL SERVIDOR */

/*
 * Esta función dibuja las barras de litros por mes (una de cargador y una de operador).
 * Recibe los litros de cada mes y devuelve el dibujo; si no hay datos devuelve vacío.
 */
function graficoMeses(array $porMes): string {
    if (!$porMes) return '';
    // tamaño del dibujo y márgenes
    $w = 760; $h = 280; $ml = 56; $mb = 34; $mt = 16; $mr = 10;
    // buscar el valor más alto (si todo es cero se usa 1 para no dividir por cero)
    $max = 0.0; foreach ($porMes as $o) { $max = max($max, $o['cargador'] ?? 0, $o['operador'] ?? 0); }
    $max = $max > 0 ? $max : 1;
    // redondear el tope del eje vertical a 1, 2, 5 o 10 por una potencia de 10
    $pot = 10 ** floor(log10($max)); $f = $max / $pot; $tope = ($f <= 1 ? 1 : ($f <= 2 ? 2 : ($f <= 5 ? 5 : 10))) * $pot;
    // calcular el espacio útil y el ancho de cada grupo y de cada barra
    $ph = $h - $mt - $mb; $pw = $w - $ml - $mr; $n = count($porMes); $paso = $pw / $n; $bw = max(6, min(26, $paso / 2 - 4));
    // GUARDAR EL DIBUJO (empieza con la apertura del SVG)
    $s = '<svg class="rep-grafico" viewBox="0 0 '.$w.' '.$h.'" role="img" aria-label="Litros por mes, cargador y operador">';
    // líneas horizontales y números del eje vertical (5 marcas, de 0 al tope)
    for ($i = 0; $i <= 4; $i++) {
        $y = $mt + $ph - $ph * $i / 4; $val = $tope * $i / 4;
        $s .= '<line class="rep-eje" x1="'.$ml.'" x2="'.($w-$mr).'" y1="'.$y.'" y2="'.$y.'"/><text x="'.($ml-6).'" y="'.($y+4).'" text-anchor="end">'.e(number_format($val, 0, ',', '.')).'</text>';
    }
    // un grupo por mes: barra azul de cargador a la izquierda y barra verde de operador a la derecha
    $i = 0;
    foreach ($porMes as $mes => $o) {
        $cx = $ml + $paso * $i + $paso / 2; $i++;
        foreach (['cargador' => ['#2563eb', -$bw - 1], 'operador' => ['#16a34a', 1]] as $orig => [$color, $dx]) {
            // el alto de la barra depende del valor; el título muestra el dato al pasar el mouse
            $v = $o[$orig] ?? 0; $bh = $ph * $v / $tope; $y = $mt + $ph - $bh;
            $s .= '<rect x="'.($cx+$dx).'" y="'.$y.'" width="'.$bw.'" height="'.max($bh, $v > 0 ? 1 : 0).'" rx="2" fill="'.$color.'"><title>'.e($mes.' · '.ucfirst($orig).': '.fl($v).' L').'</title></rect>';
        }
        // texto del eje horizontal en formato MM/AA
        [$a, $mm] = explode('-', $mes) + [1 => ''];
        $s .= '<text x="'.$cx.'" y="'.($h-12).'" text-anchor="middle">'.e($mm.'/'.substr($a, 2)).'</text>';
    }
    // cerrar el dibujo y devolverlo
    return $s.'</svg>';
}

/*
 * Esta función dibuja barras horizontales; cada fila trae un nombre y un valor.
 * Recibe las filas y el color de la barra, y devuelve el dibujo; si no hay filas devuelve vacío.
 */
function graficoBarras(array $filas, string $color): string {
    if (!$filas) return '';
    // buscar el valor más alto (mínimo 1 para no dividir por cero)
    $max = max(array_column($filas, 1)); $max = $max > 0 ? $max : 1;
    // tamaño del dibujo: ancho, alto de cada fila y márgenes para el nombre y el valor
    $w = 760; $fh = 28; $ml = 190; $mr = 110; $h = $fh * count($filas) + 8;
    $s = '<svg class="rep-grafico" viewBox="0 0 '.$w.' '.$h.'" role="img">';
    // por cada fila: el nombre (se corta a 24 letras), la barra y el valor en litros
    foreach (array_values($filas) as $i => [$et, $v]) {
        $y = 4 + $i * $fh; $bw = ($w - $ml - $mr) * $v / $max;
        $et = mb_strlen($et) > 24 ? mb_substr($et, 0, 23).'…' : $et;
        $s .= '<text x="'.($ml-8).'" y="'.($y+17).'" text-anchor="end">'.e($et).'</text>'
            .'<rect x="'.$ml.'" y="'.($y+4).'" width="'.max($bw, $v > 0 ? 1 : 0).'" height="18" rx="2" fill="'.$color.'"/>'
            .'<text class="rep-valor" x="'.($ml+$bw+6).'" y="'.($y+17).'">'.e(fl($v)).' L</text>';
    }
    // cerrar el dibujo y devolverlo
    return $s.'</svg>';
}
/* TÍTULO: OPCIONES PARA EXPORTAR EL REPORTE EN PDF */

// esta parte revisa si la persona pidió el PDF (?imprimir=1); se abre la hoja y el navegador permite guardarla como PDF
$modoImprimir = ((string)($_GET['imprimir'] ?? '') === '1');
// el reporte solo se puede exportar si existen las tres vistas de la base de datos
if ($faltantes) { $modoImprimir = false; }
// guarda el nombre de la persona que genera el reporte y la fecha y hora de generación
$generadoPor = (string)($_SESSION['username'] ?? 'Administrador');
$generadoEn  = date('d-m-Y H:i');

/* TÍTULO: ANOTAR LA EXPORTACIÓN EN LA AUDITORÍA */

// si la persona abrió el PDF, queda anotado en la auditoría
if ($modoImprimir) {
    audit($conn, (int)$_SESSION['usuario_id'], 'EXPORTAR_REPORTE_PDF', 'reportes', null,
        'meses='.count($meses).'; maquinarias='.count($maquinarias).'; usuarios='.count($usuarios));
}

/* TÍTULO: CUERPO PRINCIPAL DE LA PÁGINA */
// desde aquí se muestra el documento HTML; el código PHP intercalado solo lee lo calculado arriba
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Reportes de combustible</title><link rel="stylesheet" href="../../../css/programa/superadmin.css"><link rel="stylesheet" href="../../../css/programa/reportes.css">
<?php /* si es la versión para imprimir, se cargan estilos y funciones extra */ ?>
<?php if ($modoImprimir): ?>
<!-- estilos especiales para la hoja impresa -->
<link rel="stylesheet" href="../../../css/programa/reportes_imprimir.css">
<!-- funciones de los botones de imprimir y cerrar -->
<script src="../../../js/programa/imprimir.js" defer></script>
<!-- abre solo el diálogo de impresión para elegir "Guardar como PDF" -->
<script src="../../../js/programa/reportes_pdf.js" defer></script>
<?php endif; ?>
</head><body<?= $modoImprimir ? ' class="reporte-impreso"' : '' ?>>
<?php /* TÍTULO: ENCABEZADO DE LA PÁGINA */ ?>
<?php /* esta parte muestra el título y el botón para volver al panel */ ?>
<?php if ($modoImprimir): ?>
<?php /* TÍTULO: CABECERA DE LA VERSIÓN PARA IMPRIMIR */ ?>
<?php /* esta parte muestra el título, la fecha y quién generó la hoja, y los botones de imprimir y cerrar */ ?>
<header class="cabecera-reporte"><div><h1>Reporte de combustible · <?=e(appName())?></h1><p>Litros por mes, por maquinaria y por usuario (solo registros vigentes).</p></div><div class="meta-reporte"><div>Generado: <strong><?=e($generadoEn)?></strong></div><div>Por: <strong><?=e($generadoPor)?></strong></div></div></header>
<div class="acciones-impresion"><button type="button" id="btnImprimir">📑 Guardar como PDF</button><button type="button" class="secundario" id="btnCerrar">Cerrar</button></div>
<?php else: ?>
<header class="admin-header"><div><h1>Reportes de combustible</h1><p>Litros por mes, por maquinaria y por usuario (solo registros vigentes).</p></div><div class="acciones">
<?php /* TÍTULO: BOTONES PARA EXPORTAR EL REPORTE */ ?>
<?php /* solo aparece si existen las vistas; abre la hoja del reporte y el navegador ofrece guardarla como PDF */ ?>
<?php if (!$faltantes): ?>
<a href="reportes.php?imprimir=1" target="_blank" rel="noopener noreferrer" class="btn btn-print">📑 Exportar PDF</a>
<?php endif; ?>
<a href="superadmin.php" class="btn btn-limpiar">⬅️ Volver al panel</a></div></header>
<?php endif; ?>

<?php /* TÍTULO: AVISO DE VISTAS QUE FALTAN */ ?>
<?php /* esta parte avisa cuando faltan vistas en la base de datos */ ?>
<?php if ($faltantes): ?>
<div class="mensaje-admin error">⚠️ Faltan vistas en la base de datos: <strong><?=e(implode(', ', $faltantes))?></strong>. Ejecuta <strong>base_dato/05_MIGRACION_VISTAS_REPORTES.sql</strong> en phpMyAdmin (es seguro repetirlo) y recarga esta página.</div>
<?php else: ?>

<?php /* TÍTULO: TARJETAS DE RESUMEN */ ?>
<?php /* esta parte muestra el total de litros, los registros, las maquinarias y los usuarios con actividad */ ?>
<section class="resumen">
  <div class="resumen-card azul"><span>Total litros registrados</span><strong><?=fl($totalLitros)?> L</strong></div>
  <div class="resumen-card verde"><span>Registros vigentes</span><strong><?=fi($totalRegistros)?></strong></div>
  <div class="resumen-card azul"><span>Maquinarias con cargas</span><strong><?=fi($totalMaquinarias)?></strong></div>
  <div class="resumen-card verde"><span>Usuarios con actividad</span><strong><?=fi($usuariosActivos)?></strong></div>
</section>

<?php /* TÍTULO: SECCIÓN LITROS POR MES */ ?>
<?php /* esta parte muestra el gráfico y la tabla de litros por mes */ ?>
<section class="rep-seccion">
  <h2>Litros por mes</h2>
  <p class="rep-nota">Últimos 12 meses con registros. Fuente: vista <code>v_litros_por_mes</code>.</p>
  <?php if (!$porMes): ?><p class="rep-vacio">Aún no hay registros.</p><?php else: ?>
<?php /* explicación de los colores del gráfico */ ?>
  <div class="rep-leyenda"><span><i class="rep-azul"></i>Operador Cargador</span><span><i class="rep-verde"></i>Operador</span></div>
<?php /* gráfico de barras por mes (últimos 12 meses con datos) */ ?>
  <?=graficoMeses($porMes)?>
<?php /* tabla con una fila por mes y origen: registros, litros y promedio por carga */ ?>
  <div class="rep-tabla-wrap"><table class="tabla-registros"><thead><tr><th>Mes</th><th>Origen</th><th class="num">Registros</th><th class="num">Litros</th><th class="num">Promedio por carga</th></tr></thead><tbody>
  <?php foreach ($meses as $m): ?><tr><td><?=e($m['mes'])?></td><td><?=e($m['origen'] === 'cargador' ? 'Cargador' : 'Operador')?></td><td class="num"><?=fi($m['cantidad_registros'])?></td><td class="num"><?=fl($m['litros_total'])?> L</td><td class="num"><?=fl($m['litros_promedio'])?> L</td></tr><?php endforeach; ?>
  </tbody></table></div>
  <?php endif; ?>
</section>

<?php /* TÍTULO: SECCIÓN LITROS POR MAQUINARIA */ ?>
<?php /* esta parte muestra el gráfico de las 10 con más litros y la tabla de hasta 50 */ ?>
<section class="rep-seccion">
  <h2>Litros por maquinaria</h2>
  <p class="rep-nota">Las 10 con más litros en el gráfico; hasta 50 en la tabla. Fuente: vista <code>v_litros_por_maquinaria</code>.</p>
  <?php if (!$maquinarias): ?><p class="rep-vacio">Aún no hay registros.</p><?php else: ?>
<?php /* gráfico de barras horizontales (nombre: código y patente) */ ?>
  <?=graficoBarras(array_map(fn($m) => [$m['codigo_maquinaria'].' · '.$m['patente'], (float)$m['litros_total']], array_slice($maquinarias, 0, 10)), '#2563eb')?>
<?php /* tabla con el detalle de cada maquinaria */ ?>
  <div class="rep-tabla-wrap"><table class="tabla-registros"><thead><tr><th>Código</th><th>Patente</th><th class="num">Registros</th><th class="num">Litros</th><th class="num">Horómetro máx.</th><th>Primer registro</th><th>Último registro</th></tr></thead><tbody>
  <?php foreach ($maquinarias as $m): ?><tr><td><?=e($m['codigo_maquinaria'])?></td><td><?=e($m['patente'])?></td><td class="num"><?=fi($m['cantidad_registros'])?></td><td class="num"><?=fl($m['litros_total'])?> L</td><td class="num"><?=fl($m['horometro_maximo'])?></td><td><?=e($m['primer_registro'])?></td><td><?=e($m['ultimo_registro'])?></td></tr><?php endforeach; ?>
  </tbody></table></div>
  <?php endif; ?>
</section>

<?php /* TÍTULO: SECCIÓN RESUMEN POR USUARIO */ ?>
<?php /* esta parte muestra el gráfico y la tabla con los litros y la actividad de cada cuenta */ ?>
<section class="rep-seccion">
  <h2>Resumen por usuario</h2>
  <p class="rep-nota">Litros y actividad de cada cuenta. Fuente: vista <code>v_resumen_por_usuario</code>.</p>
<?php /* GUARDAR LAS CUENTAS CON LITROS MAYORES A CERO (las 10 primeras van al gráfico) */ ?>
  <?php $conLitros = array_filter($usuarios, fn($u) => (float)$u['litros_total'] > 0); ?>
  <?php if ($conLitros): ?><?=graficoBarras(array_map(fn($u) => [$u['username'], (float)$u['litros_total']], array_slice(array_values($conLitros), 0, 10)), '#16a34a')?><?php endif; ?>
<?php /* tabla con todas las cuentas */ ?>
  <div class="rep-tabla-wrap"><table class="tabla-registros"><thead><tr><th>Usuario</th><th>Rol</th><th>Estado</th><th class="num">Registros</th><th class="num">Litros</th><th>Última actividad</th></tr></thead><tbody>
  <?php foreach ($usuarios as $u): ?><tr><td><strong><?=e($u['username'])?></strong></td><td><?=e($u['rol'])?></td><td><?=e($u['estado'])?></td><td class="num"><?=fi($u['cantidad_registros'])?></td><td class="num"><?=fl($u['litros_total'])?> L</td><td><?=e($u['ultima_actividad'] ?? '—')?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>
<?php /* esta parte cierra la condición: el contenido solo se muestra si existen las tres vistas */ ?>
<?php endif; ?>
</body></html>
<?php
/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- FIN Jerez reportes.php --------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

/*
 * Sitio Web Creado por Jerez.
 * Direccion: Miraflorez #1280
 * Quintero - Chile
 * jaime.jerez1978@gmail.com
 * https://www.
 * Creado, Programado y Diseñado por Jerez.
 * JJA
 */
?>
