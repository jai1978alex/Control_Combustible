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
   ------------------------------------- INICIO Jerez conciliacion.php ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

declare(strict_types=1);

/* TÍTULO: CARGAR LOS ARCHIVOS NECESARIOS Y PROTEGER LA PÁGINA */

require_once __DIR__.'/../../../backend/conexion.php';
require_once __DIR__.'/../../../backend/security.php';
// solo los administradores pueden entrar
requireAdmin();
$csrf = csrfToken();
$uid = (int)$_SESSION['usuario_id'];

/* TÍTULO: REVISAR QUE EXISTA LA TABLA DEL ESTANQUE */

$q = $conn->prepare("SELECT COUNT(*) c FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'estanque_movimientos'");
$q->execute();
$tablaOk = (int)$q->get_result()->fetch_assoc()['c'] > 0;

$error = '';
$mensaje = (string)($_GET['ok'] ?? '') === '1' ? 'Operación realizada correctamente.' : '';

/* TÍTULO: GUARDAR O ELIMINAR UN MOVIMIENTO */

if ($tablaOk && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $accion = (string)($_POST['accion'] ?? '');

    if ($accion === 'eliminar') {
        // eliminación lógica: la fila se conserva marcada como eliminada
        $id = (int)($_POST['id'] ?? 0);
        $st = $conn->prepare('UPDATE estanque_movimientos SET eliminado_at = NOW(), eliminado_por = ? WHERE id = ? AND eliminado_at IS NULL');
        $st->bind_param('ii', $uid, $id);
        $st->execute();
        if ($st->affected_rows === 1) {
            audit($conn, $uid, 'ELIMINAR_MOVIMIENTO_ESTANQUE', 'estanque_movimientos', $id, null);
            header('Location: conciliacion.php?ok=1'); exit;
        }
        $error = 'No se pudo eliminar el movimiento.';
    } elseif ($accion === 'entrada' || $accion === 'medicion') {
        $tipo = $accion === 'entrada' ? 'ENTRADA' : 'MEDICION';
        $litros = trim((string)($_POST['litros'] ?? ''));
        $referencia = trim((string)($_POST['referencia'] ?? ''));
        $observacion = trim((string)($_POST['observacion'] ?? ''));
        $fechaIn = trim((string)($_POST['fecha'] ?? ''));

        // si no se indica fecha se usa el momento actual
        $dt = $fechaIn === '' ? new DateTime() : DateTime::createFromFormat('Y-m-d\TH:i', $fechaIn);
        $ahora = new DateTime('+5 minutes');

        if (!validDecimal($litros, $tipo === 'ENTRADA' ? 0.01 : 0, 10000000)) {
            $error = 'Los litros deben ser un número válido (hasta 2 decimales, con punto).';
        } elseif (!$dt || $dt > $ahora) {
            $error = 'La fecha no es válida o está en el futuro.';
        } elseif ($referencia !== '' && !validText($referencia, 60)) {
            $error = 'La referencia no es válida (máximo 60 caracteres).';
        } elseif ($observacion !== '' && !validText($observacion, 255)) {
            $error = 'La observación no es válida (máximo 255 caracteres).';
        } else {
            $fecha = $dt->format('Y-m-d H:i:00');
            $ref = $referencia === '' ? null : $referencia;
            $obs = $observacion === '' ? null : $observacion;
            $st = $conn->prepare('INSERT INTO estanque_movimientos (tipo, fecha, litros, referencia, observacion, usuario_id) VALUES (?,?,?,?,?,?)');
            $lt = (float)$litros;
            $st->bind_param('ssdssi', $tipo, $fecha, $lt, $ref, $obs, $uid);
            $st->execute();
            audit($conn, $uid, 'REGISTRO_'.$tipo.'_ESTANQUE', 'estanque_movimientos', (int)$st->insert_id, 'litros '.$litros);
            header('Location: conciliacion.php?ok=1'); exit;
        }
    }
}

/* TÍTULO: CALCULAR SALDO TEÓRICO Y CONCILIACIÓN */

// litros despachados (cargas vigentes de las dos tablas) hasta una fecha
function salidasHasta(mysqli $conn, string $fecha): float {
    $st = $conn->prepare(
        'SELECT (SELECT COALESCE(SUM(litros),0) FROM operador WHERE eliminado_at IS NULL AND fecha_registro <= ?)
              + (SELECT COALESCE(SUM(litros),0) FROM operador_cargador WHERE eliminado_at IS NULL AND fecha_registro <= ?) AS t'
    );
    $st->bind_param('ss', $fecha, $fecha);
    $st->execute();
    return (float)$st->get_result()->fetch_assoc()['t'];
}
// litros que han entrado al estanque hasta una fecha
function entradasHasta(mysqli $conn, string $fecha): float {
    $st = $conn->prepare("SELECT COALESCE(SUM(litros),0) t FROM estanque_movimientos WHERE eliminado_at IS NULL AND tipo = 'ENTRADA' AND fecha <= ?");
    $st->bind_param('s', $fecha);
    $st->execute();
    return (float)$st->get_result()->fetch_assoc()['t'];
}

// tolerancia aceptada de merma, en % de lo despachado en el período (se cambia con CONCILIACION_TOLERANCIA_PCT en el .env)
$tolerancia = (float)(getenv('CONCILIACION_TOLERANCIA_PCT') ?: 1.0);
if ($tolerancia < 0 || $tolerancia > 100) { $tolerancia = 1.0; }

// Calcula, para cada lectura (de la más antigua a la más nueva): saldo teórico, diferencia acumulada,
// variación del período (cuánto cambió la diferencia desde la lectura anterior) y si supera la tolerancia.
function enriquecerSerie(mysqli $conn, array $lecturas, float $tolPct): array {
    $difAnterior = 0.0;
    $salidasAnterior = 0.0;
    foreach ($lecturas as $k => $f) {
        $sal = salidasHasta($conn, $f['fecha']);
        $f['teorico'] = entradasHasta($conn, $f['fecha']) - $sal;
        // diferencia acumulada: negativa = falta combustible (merma o pérdida), positiva = sobra
        $f['diferencia'] = (float)$f['litros'] - $f['teorico'];
        // lo que cambió la diferencia en este período y lo que se despachó en él
        $f['variacion'] = $f['diferencia'] - $difAnterior;
        $f['despachado_periodo'] = max(0.0, $sal - $salidasAnterior);
        $f['limite'] = $f['despachado_periodo'] * $tolPct / 100;
        // solo alerta cuando FALTA más de lo tolerado (si sobra, se muestra pero no se marca como pérdida)
        $f['alerta'] = $f['variacion'] < 0 && abs($f['variacion']) > $f['limite'] + 0.005;
        $difAnterior = $f['diferencia'];
        $salidasAnterior = $sal;
        $lecturas[$k] = $f;
    }
    return $lecturas;
}

$entradas = $mediciones = $remanentes = [];
$saldoTeorico = $totalEntradas = $totalSalidas = 0.0;
if ($tablaOk) {
    $ahoraTxt = (new DateTime())->format('Y-m-d H:i:s');
    $totalEntradas = entradasHasta($conn, $ahoraTxt);
    $totalSalidas = salidasHasta($conn, $ahoraTxt);
    $saldoTeorico = $totalEntradas - $totalSalidas;

    $r = $conn->query("SELECT m.id, m.fecha, m.litros, m.referencia, m.observacion, u.username FROM estanque_movimientos m LEFT JOIN usuarios u ON m.usuario_id = u.id WHERE m.eliminado_at IS NULL AND m.tipo = 'ENTRADA' ORDER BY m.fecha DESC LIMIT 30");
    while ($f = $r->fetch_assoc()) { $entradas[] = $f; }

    // mediciones reales (las últimas 200, se muestran las 30 más nuevas)
    $r = $conn->query("SELECT m.id, m.fecha, m.litros, m.observacion, u.username FROM estanque_movimientos m LEFT JOIN usuarios u ON m.usuario_id = u.id WHERE m.eliminado_at IS NULL AND m.tipo = 'MEDICION' ORDER BY m.fecha DESC, m.id DESC LIMIT 200");
    while ($f = $r->fetch_assoc()) { $mediciones[] = $f; }
    $mediciones = enriquecerSerie($conn, array_reverse($mediciones), $tolerancia);

    // remanente informado en las cargas de Operador Cargador (se usa como lectura automática)
    $r = $conn->query("SELECT oc.id, oc.fecha_registro AS fecha, oc.remanente AS litros, oc.codigo AS observacion, u.username FROM operador_cargador oc LEFT JOIN usuarios u ON oc.usuario_id = u.id WHERE oc.eliminado_at IS NULL AND oc.remanente > 0 ORDER BY oc.fecha_registro DESC, oc.id DESC LIMIT 200");
    while ($f = $r->fetch_assoc()) { $remanentes[] = $f; }
    $remanentes = enriquecerSerie($conn, array_reverse($remanentes), $tolerancia);

    // se muestran primero las más nuevas
    $mediciones = array_slice(array_reverse($mediciones), 0, 30);
    $remanentes = array_slice(array_reverse($remanentes), 0, 30);
}
$ultima = $mediciones[0] ?? null;
$hayAlerta = $ultima && $ultima['alerta'];

function lt(float $n): string { return number_format($n, 2, ',', '.').' L'; }

/* TÍTULO: CUERPO PRINCIPAL DE LA PÁGINA */
?><!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Saldo y conciliación</title>
    <link rel="stylesheet" href="../../../css/programa/superadmin.css">
    <script src="../../../js/programa/conciliacion.js" defer></script>
    <style>
        .form-mov { display:flex; flex-wrap:wrap; gap:10px; align-items:end; }
        .form-mov label { display:flex; flex-direction:column; font-weight:600; gap:4px; }
        .form-mov input { padding:8px; border:1px solid #cbd5e1; border-radius:8px; }
        .dif-neg { color:#b91c1c; font-weight:700; }
        .dif-pos { color:#b45309; font-weight:700; }
        .dif-ok { color:#166534; font-weight:700; }
        .resumen-card.rojo { background:linear-gradient(135deg,#dc2626,#7f1d1d); }
        .resumen-card.gris { background:linear-gradient(135deg,#475569,#1e293b); }
        .nota { font-size:.9em; color:#475569; margin:6px 0 14px; }
    </style>
</head>

<body>
<header class="admin-header">
    <div>
        <h1>Saldo y conciliación</h1>
        <p>Saldo teórico del estanque y comparación con la medición real.</p>
    </div>
    <div class="acciones"><a href="superadmin.php" class="btn btn-limpiar">⬅ Volver al panel</a></div>
</header>

<?php if(!$tablaOk): ?>
<div class="mensaje-admin error">⚠️ Falta la tabla del estanque. Respalda la base y ejecuta <strong>base_dato/07_MIGRACION_SALDO_CONCILIACION.sql</strong>.</div>
<?php else: ?>

<?php if($mensaje!==''): ?><div class="mensaje-admin success">✔ <?=e($mensaje)?></div><?php endif; ?>
<?php if($error!==''): ?><div class="mensaje-admin error">✖ <?=e($error)?></div><?php endif; ?>

<section class="resumen">
    <div class="resumen-card azul"><span>Total entradas</span><strong><?=e(lt($totalEntradas))?></strong></div>
    <div class="resumen-card verde"><span>Total despachado</span><strong><?=e(lt($totalSalidas))?></strong></div>
    <div class="resumen-card <?=$saldoTeorico<0?'rojo':'gris'?>"><span>Saldo teórico actual</span><strong><?=e(lt($saldoTeorico))?></strong></div>
    <div class="resumen-card gris"><span>Última diferencia medida</span><strong><?=$ultima?e(lt((float)$ultima['diferencia'])):'Sin mediciones'?></strong></div>
</section>
<?php if($hayAlerta): ?><div class="mensaje-admin error">🚨 Alerta de merma: en la última medición faltan <?=e(lt(abs((float)$ultima['variacion'])))?> respecto del período anterior; lo tolerado era <?=e(lt((float)$ultima['limite']))?> (<?=e(number_format($tolerancia,2,',','.'))?> % de lo despachado). Revisa posibles pérdidas o errores de registro.</div><?php endif; ?>
<?php if($saldoTeorico<0): ?><div class="mensaje-admin error">⚠️ El saldo teórico es negativo: hay más litros despachados que entradas registradas. Falta registrar una entrada (o el saldo inicial).</div><?php endif; ?>
<p class="nota">Saldo teórico = entradas registradas − litros despachados (cargas vigentes de Operador y Operador Cargador). Para partir, registra una entrada con referencia «Saldo inicial». Se marca alerta cuando faltan más litros que el <?=e(number_format($tolerancia,2,',','.'))?> % de lo despachado desde la lectura anterior.</p>

<hr>

<h2>Registrar entrada de combustible</h2>
<form method="POST" class="form-mov">
    <input type="hidden" name="csrf_token" value="<?=e($csrf)?>">
    <input type="hidden" name="accion" value="entrada">
    <label>Litros<input name="litros" required inputmode="decimal" placeholder="5000.00"></label>
    <label>Fecha y hora<input type="datetime-local" name="fecha"></label>
    <label>Guía / factura<input name="referencia" maxlength="60"></label>
    <label>Observación<input name="observacion" maxlength="255"></label>
    <button class="btn btn-crear" type="submit">➕ Registrar entrada</button>
</form>

<h2>Registrar medición real del estanque</h2>
<form method="POST" class="form-mov">
    <input type="hidden" name="csrf_token" value="<?=e($csrf)?>">
    <input type="hidden" name="accion" value="medicion">
    <label>Litros medidos<input name="litros" required inputmode="decimal" placeholder="1200.00"></label>
    <label>Fecha y hora<input type="datetime-local" name="fecha"></label>
    <label>Observación<input name="observacion" maxlength="255"></label>
    <button class="btn btn-buscar" type="submit">📏 Registrar medición</button>
</form>

<hr>

<h2>Conciliación (mediciones vs. saldo teórico)</h2>
<div class="tabla-contenedor">
<table>
    <thead><tr><th>Fecha</th><th>Medido</th><th>Saldo teórico</th><th>Diferencia acumulada</th><th>Variación del período</th><th>Estado</th><th>Observación</th><th>Registró</th><th>Acción</th></tr></thead>
    <tbody>
    <?php if(!$mediciones): ?><tr><td colspan="9">Aún no hay mediciones.</td></tr><?php endif; ?>
    <?php foreach($mediciones as $m): $d=(float)$m['diferencia']; $cl=abs($d)<0.005?'dif-ok':($d<0?'dif-neg':'dif-pos'); ?>
    <tr>
        <td><?=e($m['fecha'])?></td>
        <td><?=e(lt((float)$m['litros']))?></td>
        <td><?=e(lt((float)$m['teorico']))?></td>
        <td class="<?=$cl?>"><?=e(($d>0?'+':'').lt($d))?> <?=$d<-0.005?'(falta)':($d>0.005?'(sobra)':'')?></td>
        <td><?=e(($m['variacion']>0?'+':'').lt((float)$m['variacion']))?></td>
        <td><?=$m['alerta']?'<span class="dif-neg">🚨 Alerta</span>':'<span class="dif-ok">OK</span>'?></td>
        <td><?=e($m['observacion']??'')?></td>
        <td><?=e($m['username']??'—')?></td>
        <td><form method="POST" data-confirm="¿Eliminar esta medición?"><input type="hidden" name="csrf_token" value="<?=e($csrf)?>"><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?=(int)$m['id']?>"><button class="btn btn-delete" type="submit">🗑️</button></form></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<h2>Control por remanente (cargas del Operador Cargador)</h2>
<p class="nota">Cada carga que informa un remanente mayor que 0 se toma como una lectura del estanque a esa fecha y se compara con el saldo teórico. Se asume que el remanente son los litros que quedan en el estanque después de la carga; si en tu operación significa otra cosa, ignora esta tabla.</p>
<div class="tabla-contenedor">
<table>
    <thead><tr><th>Fecha</th><th>Código</th><th>Remanente informado</th><th>Saldo teórico</th><th>Diferencia acumulada</th><th>Variación del período</th><th>Estado</th><th>Registró</th></tr></thead>
    <tbody>
    <?php if(!$remanentes): ?><tr><td colspan="8">Aún no hay cargas con remanente.</td></tr><?php endif; ?>
    <?php foreach($remanentes as $m): $d=(float)$m['diferencia']; $cl=abs($d)<0.005?'dif-ok':($d<0?'dif-neg':'dif-pos'); ?>
    <tr>
        <td><?=e($m['fecha'])?></td>
        <td><?=e($m['observacion']??'')?></td>
        <td><?=e(lt((float)$m['litros']))?></td>
        <td><?=e(lt((float)$m['teorico']))?></td>
        <td class="<?=$cl?>"><?=e(($d>0?'+':'').lt($d))?></td>
        <td><?=e(($m['variacion']>0?'+':'').lt((float)$m['variacion']))?></td>
        <td><?=$m['alerta']?'<span class="dif-neg">🚨 Alerta</span>':'<span class="dif-ok">OK</span>'?></td>
        <td><?=e($m['username']??'—')?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<h2>Entradas registradas</h2>
<div class="tabla-contenedor">
<table>
    <thead><tr><th>Fecha</th><th>Litros</th><th>Guía / factura</th><th>Observación</th><th>Registró</th><th>Acción</th></tr></thead>
    <tbody>
    <?php if(!$entradas): ?><tr><td colspan="6">Aún no hay entradas.</td></tr><?php endif; ?>
    <?php foreach($entradas as $en): ?>
    <tr>
        <td><?=e($en['fecha'])?></td>
        <td><?=e(lt((float)$en['litros']))?></td>
        <td><?=e($en['referencia']??'')?></td>
        <td><?=e($en['observacion']??'')?></td>
        <td><?=e($en['username']??'—')?></td>
        <td><form method="POST" data-confirm="¿Eliminar esta entrada?"><input type="hidden" name="csrf_token" value="<?=e($csrf)?>"><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?=(int)$en['id']?>"><button class="btn btn-delete" type="submit">🗑️</button></form></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
</body>
</html>
<?php
/* ------------------------------------- FIN Jerez conciliacion.php ------------------------------------ */
