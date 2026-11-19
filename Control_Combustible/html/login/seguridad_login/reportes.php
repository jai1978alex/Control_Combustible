<?php
declare(strict_types=1);
require_once __DIR__.'/../../../backend/conexion.php'; require_once __DIR__.'/../../../backend/security.php';
requireAdmin();

/*
 * Reportes del administrador.
 * Lee tres vistas de la base de datos (solo lectura):
 *   v_litros_por_mes, v_litros_por_maquinaria, v_resumen_por_usuario
 * Los gráficos son SVG generados aquí en el servidor: no necesitan librerías
 * externas ni internet.
 */

// Comprueba qué vistas existen (si falta alguna, se avisa cómo crearla)
$vistasNecesarias = ['v_litros_por_mes','v_litros_por_maquinaria','v_resumen_por_usuario'];
$existentes = [];
$q = $conn->query("SELECT TABLE_NAME FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE()");
if ($q) { while ($f = $q->fetch_assoc()) { $existentes[] = $f['TABLE_NAME']; } }
$faltantes = array_values(array_diff($vistasNecesarias, $existentes));

$meses = $maquinarias = $usuarios = []; $totalMaquinarias = 0;
if (!$faltantes) {
    // Litros por mes y origen (cargador/operador)
    $r = $conn->query("SELECT mes, origen, cantidad_registros, litros_total, litros_promedio FROM v_litros_por_mes ORDER BY mes DESC, origen");
    while ($f = $r->fetch_assoc()) { $meses[] = $f; }
    $totalMaquinarias = (int)$conn->query('SELECT COUNT(*) c FROM v_litros_por_maquinaria')->fetch_assoc()['c'];
    // Litros por maquinaria (los 50 con más litros)
    $r = $conn->query("SELECT patente, codigo_maquinaria, cantidad_registros, litros_total, primer_registro, ultimo_registro, horometro_maximo FROM v_litros_por_maquinaria ORDER BY litros_total DESC LIMIT 50");
    while ($f = $r->fetch_assoc()) { $maquinarias[] = $f; }
    // Resumen por usuario
    $r = $conn->query("SELECT usuario_id, username, rol, estado, cantidad_registros, litros_total, ultima_actividad FROM v_resumen_por_usuario ORDER BY litros_total DESC, username");
    while ($f = $r->fetch_assoc()) { $usuarios[] = $f; }
}

function fl(mixed $v): string { return number_format((float)$v, 2, ',', '.'); }
function fi(mixed $v): string { return number_format((int)$v, 0, ',', '.'); }

/* ---- Datos del gráfico mensual: últimos 12 meses con datos, de más antiguo a más reciente ---- */
$porMes = [];
foreach ($meses as $m) { $porMes[$m['mes']][$m['origen']] = (float)$m['litros_total']; }
krsort($porMes);
$porMes = array_reverse(array_slice($porMes, 0, 12, true), true);

/* ---- KPI ---- */
$totalLitros = 0.0; $totalRegistros = 0;
foreach ($meses as $m) { $totalLitros += (float)$m['litros_total']; $totalRegistros += (int)$m['cantidad_registros']; }
$usuariosActivos = count(array_filter($usuarios, fn($u) => (int)$u['cantidad_registros'] > 0));

/* ---- Gráfico de barras agrupadas por mes (SVG) ---- */
function graficoMeses(array $porMes): string {
    if (!$porMes) return '';
    $w = 760; $h = 280; $ml = 56; $mb = 34; $mt = 16; $mr = 10;
    $max = 0.0; foreach ($porMes as $o) { $max = max($max, $o['cargador'] ?? 0, $o['operador'] ?? 0); }
    $max = $max > 0 ? $max : 1;
    // Escala "bonita": redondea hacia arriba a 1, 2 o 5 × 10^n
    $pot = 10 ** floor(log10($max)); $f = $max / $pot; $tope = ($f <= 1 ? 1 : ($f <= 2 ? 2 : ($f <= 5 ? 5 : 10))) * $pot;
    $ph = $h - $mt - $mb; $pw = $w - $ml - $mr; $n = count($porMes); $paso = $pw / $n; $bw = max(6, min(26, $paso / 2 - 4));
    $s = '<svg class="rep-grafico" viewBox="0 0 '.$w.' '.$h.'" role="img" aria-label="Litros por mes, cargador y operador">';
    for ($i = 0; $i <= 4; $i++) {
        $y = $mt + $ph - $ph * $i / 4; $val = $tope * $i / 4;
        $s .= '<line class="rep-eje" x1="'.$ml.'" x2="'.($w-$mr).'" y1="'.$y.'" y2="'.$y.'"/><text x="'.($ml-6).'" y="'.($y+4).'" text-anchor="end">'.e(number_format($val, 0, ',', '.')).'</text>';
    }
    $i = 0;
    foreach ($porMes as $mes => $o) {
        $cx = $ml + $paso * $i + $paso / 2; $i++;
        foreach (['cargador' => ['#2563eb', -$bw - 1], 'operador' => ['#16a34a', 1]] as $orig => [$color, $dx]) {
            $v = $o[$orig] ?? 0; $bh = $ph * $v / $tope; $y = $mt + $ph - $bh;
            $s .= '<rect x="'.($cx+$dx).'" y="'.$y.'" width="'.$bw.'" height="'.max($bh, $v > 0 ? 1 : 0).'" rx="2" fill="'.$color.'"><title>'.e($mes.' · '.ucfirst($orig).': '.fl($v).' L').'</title></rect>';
        }
        [$a, $mm] = explode('-', $mes) + [1 => ''];
        $s .= '<text x="'.$cx.'" y="'.($h-12).'" text-anchor="middle">'.e($mm.'/'.substr($a, 2)).'</text>';
    }
    return $s.'</svg>';
}

/* ---- Gráfico de barras horizontales (SVG): filas = [etiqueta, valor] ---- */
function graficoBarras(array $filas, string $color): string {
    if (!$filas) return '';
    $max = max(array_column($filas, 1)); $max = $max > 0 ? $max : 1;
    $w = 760; $fh = 28; $ml = 190; $mr = 110; $h = $fh * count($filas) + 8;
    $s = '<svg class="rep-grafico" viewBox="0 0 '.$w.' '.$h.'" role="img">';
    foreach (array_values($filas) as $i => [$et, $v]) {
        $y = 4 + $i * $fh; $bw = ($w - $ml - $mr) * $v / $max;
        $et = mb_strlen($et) > 24 ? mb_substr($et, 0, 23).'…' : $et;
        $s .= '<text x="'.($ml-8).'" y="'.($y+17).'" text-anchor="end">'.e($et).'</text>'
            .'<rect x="'.$ml.'" y="'.($y+4).'" width="'.max($bw, $v > 0 ? 1 : 0).'" height="18" rx="2" fill="'.$color.'"/>'
            .'<text class="rep-valor" x="'.($ml+$bw+6).'" y="'.($y+17).'">'.e(fl($v)).' L</text>';
    }
    return $s.'</svg>';
}
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Reportes de combustible</title><link rel="stylesheet" href="../../../css/programa/superadmin.css"><link rel="stylesheet" href="../../../css/programa/reportes.css"></head><body>
<header class="admin-header"><div><h1>Reportes de combustible</h1><p>Litros por mes, por maquinaria y por usuario (solo registros vigentes).</p></div><div class="acciones"><a href="superadmin.php" class="btn btn-limpiar">⬅️ Volver al panel</a></div></header>

<?php if ($faltantes): ?>
<div class="mensaje-admin error">⚠️ Faltan vistas en la base de datos: <strong><?=e(implode(', ', $faltantes))?></strong>. Ejecuta <strong>base_dato/05_MIGRACION_VISTAS_REPORTES.sql</strong> en phpMyAdmin (es seguro repetirlo) y recarga esta página.</div>
<?php else: ?>

<section class="resumen">
  <div class="resumen-card azul"><span>Total litros registrados</span><strong><?=fl($totalLitros)?> L</strong></div>
  <div class="resumen-card verde"><span>Registros vigentes</span><strong><?=fi($totalRegistros)?></strong></div>
  <div class="resumen-card azul"><span>Maquinarias con cargas</span><strong><?=fi($totalMaquinarias)?></strong></div>
  <div class="resumen-card verde"><span>Usuarios con actividad</span><strong><?=fi($usuariosActivos)?></strong></div>
</section>

<section class="rep-seccion">
  <h2>Litros por mes</h2>
  <p class="rep-nota">Últimos 12 meses con registros. Fuente: vista <code>v_litros_por_mes</code>.</p>
  <?php if (!$porMes): ?><p class="rep-vacio">Aún no hay registros.</p><?php else: ?>
  <div class="rep-leyenda"><span><i class="rep-azul"></i>Operador Cargador</span><span><i class="rep-verde"></i>Operador</span></div>
  <?=graficoMeses($porMes)?>
  <div class="rep-tabla-wrap"><table class="tabla-registros"><thead><tr><th>Mes</th><th>Origen</th><th class="num">Registros</th><th class="num">Litros</th><th class="num">Promedio por carga</th></tr></thead><tbody>
  <?php foreach ($meses as $m): ?><tr><td><?=e($m['mes'])?></td><td><?=e($m['origen'] === 'cargador' ? 'Cargador' : 'Operador')?></td><td class="num"><?=fi($m['cantidad_registros'])?></td><td class="num"><?=fl($m['litros_total'])?> L</td><td class="num"><?=fl($m['litros_promedio'])?> L</td></tr><?php endforeach; ?>
  </tbody></table></div>
  <?php endif; ?>
</section>

<section class="rep-seccion">
  <h2>Litros por maquinaria</h2>
  <p class="rep-nota">Las 10 con más litros en el gráfico; hasta 50 en la tabla. Fuente: vista <code>v_litros_por_maquinaria</code>.</p>
  <?php if (!$maquinarias): ?><p class="rep-vacio">Aún no hay registros.</p><?php else: ?>
  <?=graficoBarras(array_map(fn($m) => [$m['codigo_maquinaria'].' · '.$m['patente'], (float)$m['litros_total']], array_slice($maquinarias, 0, 10)), '#2563eb')?>
  <div class="rep-tabla-wrap"><table class="tabla-registros"><thead><tr><th>Código</th><th>Patente</th><th class="num">Registros</th><th class="num">Litros</th><th class="num">Horómetro máx.</th><th>Primer registro</th><th>Último registro</th></tr></thead><tbody>
  <?php foreach ($maquinarias as $m): ?><tr><td><?=e($m['codigo_maquinaria'])?></td><td><?=e($m['patente'])?></td><td class="num"><?=fi($m['cantidad_registros'])?></td><td class="num"><?=fl($m['litros_total'])?> L</td><td class="num"><?=fl($m['horometro_maximo'])?></td><td><?=e($m['primer_registro'])?></td><td><?=e($m['ultimo_registro'])?></td></tr><?php endforeach; ?>
  </tbody></table></div>
  <?php endif; ?>
</section>

<section class="rep-seccion">
  <h2>Resumen por usuario</h2>
  <p class="rep-nota">Litros y actividad de cada cuenta. Fuente: vista <code>v_resumen_por_usuario</code>.</p>
  <?php $conLitros = array_filter($usuarios, fn($u) => (float)$u['litros_total'] > 0); ?>
  <?php if ($conLitros): ?><?=graficoBarras(array_map(fn($u) => [$u['username'], (float)$u['litros_total']], array_slice(array_values($conLitros), 0, 10)), '#16a34a')?><?php endif; ?>
  <div class="rep-tabla-wrap"><table class="tabla-registros"><thead><tr><th>Usuario</th><th>Rol</th><th>Estado</th><th class="num">Registros</th><th class="num">Litros</th><th>Última actividad</th></tr></thead><tbody>
  <?php foreach ($usuarios as $u): ?><tr><td><strong><?=e($u['username'])?></strong></td><td><?=e($u['rol'])?></td><td><?=e($u['estado'])?></td><td class="num"><?=fi($u['cantidad_registros'])?></td><td class="num"><?=fl($u['litros_total'])?> L</td><td><?=e($u['ultima_actividad'] ?? '—')?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>
<?php endif; ?>
</body></html>
