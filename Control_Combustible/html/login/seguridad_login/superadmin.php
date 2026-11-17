<?php
declare(strict_types=1);
require_once __DIR__.'/../../../backend/conexion.php'; require_once __DIR__.'/../../../backend/security.php';
requireAdmin(); $csrf=csrfToken();

/* Flash de un solo uso con la contraseña temporal generada al resetear un usuario. */
$passwordTemporal = null;
$passwordTemporalUsuario = null;
if (!empty($_SESSION['flash_temp_password'])) {
    $passwordTemporal = (string)$_SESSION['flash_temp_password'];
    $passwordTemporalUsuario = (string)($_SESSION['flash_temp_password_user'] ?? '');
    unset($_SESSION['flash_temp_password'], $_SESSION['flash_temp_password_user']);
}

$rutBusqueda=trim((string)($_GET['rut']??''));

/*
 * Compatibilidad con instalaciones anteriores.
 * Algunas versiones antiguas de la base no tenían todavía eliminado_at.
 * Con mysqli en modo STRICT, consultar una columna inexistente provoca una
 * excepción y Apache termina mostrando HTTP 500. El panel detecta la
 * columna antes de construir las consultas (función compartida en
 * backend/security.php), sin modificar la base de datos ni alterar el
 * resto del sistema.
 */
$eliminacionCargador=columnaExiste($conn,'operador_cargador','eliminado_at');
$eliminacionOperador=columnaExiste($conn,'operador','eliminado_at');
$whereCargador=$eliminacionCargador ? 'oc.eliminado_at IS NULL' : '1=1';
$whereCargadorSimple=$eliminacionCargador ? 'eliminado_at IS NULL' : '1=1';
$whereOperador=$eliminacionOperador ? 'o.eliminado_at IS NULL' : '1=1';
$whereOperadorSimple=$eliminacionOperador ? 'eliminado_at IS NULL' : '1=1';

$usuarios=$conn->query("SELECT id,username,email,rol,estado,created_at FROM usuarios ORDER BY id DESC");

/* Paginación: evita cargar miles de filas de una vez en tablas grandes. */
const FILAS_POR_PAGINA = 25;
$limiteFilas = FILAS_POR_PAGINA;
$pgCargador = max(1, (int)($_GET['pgc'] ?? 1));
$pgOperador = max(1, (int)($_GET['pgo'] ?? 1));
$offCargador = ($pgCargador - 1) * FILAS_POR_PAGINA;
$offOperador = ($pgOperador - 1) * FILAS_POR_PAGINA;

if($rutBusqueda!==''){
    $like='%'.$rutBusqueda.'%';
    $st=$conn->prepare("SELECT oc.*,u.username FROM operador_cargador oc LEFT JOIN usuarios u ON oc.usuario_id=u.id WHERE {$whereCargador} AND oc.rut LIKE ? ORDER BY oc.fecha_registro DESC LIMIT ? OFFSET ?");
    $st->bind_param('sii',$like,$limiteFilas,$offCargador);$st->execute();$cargador=$st->get_result();
    $st=$conn->prepare("SELECT COUNT(*) c FROM operador_cargador WHERE {$whereCargadorSimple} AND rut LIKE ?");$st->bind_param('s',$like);$st->execute();$totalFilasCargador=(int)$st->get_result()->fetch_assoc()['c'];
    $st=$conn->prepare("SELECT o.*,u.username FROM operador o LEFT JOIN usuarios u ON o.usuario_id=u.id WHERE {$whereOperador} AND o.rut LIKE ? ORDER BY o.fecha_registro DESC LIMIT ? OFFSET ?");
    $st->bind_param('sii',$like,$limiteFilas,$offOperador);$st->execute();$operador=$st->get_result();
    $st=$conn->prepare("SELECT COUNT(*) c FROM operador WHERE {$whereOperadorSimple} AND rut LIKE ?");$st->bind_param('s',$like);$st->execute();$totalFilasOperador=(int)$st->get_result()->fetch_assoc()['c'];
}else{
    $st=$conn->prepare("SELECT oc.*,u.username FROM operador_cargador oc LEFT JOIN usuarios u ON oc.usuario_id=u.id WHERE {$whereCargador} ORDER BY oc.fecha_registro DESC LIMIT ? OFFSET ?");
    $st->bind_param('ii',$limiteFilas,$offCargador);$st->execute();$cargador=$st->get_result();
    $totalFilasCargador=(int)$conn->query("SELECT COUNT(*) c FROM operador_cargador WHERE {$whereCargadorSimple}")->fetch_assoc()['c'];
    $st=$conn->prepare("SELECT o.*,u.username FROM operador o LEFT JOIN usuarios u ON o.usuario_id=u.id WHERE {$whereOperador} ORDER BY o.fecha_registro DESC LIMIT ? OFFSET ?");
    $st->bind_param('ii',$limiteFilas,$offOperador);$st->execute();$operador=$st->get_result();
    $totalFilasOperador=(int)$conn->query("SELECT COUNT(*) c FROM operador WHERE {$whereOperadorSimple}")->fetch_assoc()['c'];
}
$totalPagCargador=max(1,(int)ceil($totalFilasCargador/FILAS_POR_PAGINA));
$totalPagOperador=max(1,(int)ceil($totalFilasOperador/FILAS_POR_PAGINA));

$totalCargador=(float)$conn->query("SELECT COALESCE(SUM(litros),0) t FROM operador_cargador WHERE {$whereCargadorSimple}")->fetch_assoc()['t'];
$totalOperador=(float)$conn->query("SELECT COALESCE(SUM(litros),0) t FROM operador WHERE {$whereOperadorSimple}")->fetch_assoc()['t'];

/* Query string base para construir enlaces de paginación/exportación conservando el filtro. */
$qsBase = $rutBusqueda!=='' ? ('rut='.urlencode($rutBusqueda)) : '';
$armarEnlacePagina = static function(string $param, int $pagina) use ($qsBase): string {
    $partes = array_filter([$qsBase, $param.'='.$pagina]);
    return 'superadmin.php?'.implode('&', $partes);
};
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Panel Administrador</title><link rel="stylesheet" href="../../../css/programa/superadmin.css"><script src="../../../js/programa/admin.js" defer></script></head><body>
<?php
$mensajesExito = [
    'registro_eliminado' => '✔ Registro eliminado correctamente.',
    'usuario' => '✔ Usuario eliminado correctamente.',
    'password_actualizada' => '✔ Contraseña actualizada correctamente.',
    'password_reseteada' => '✔ Contraseña reseteada. Comunica la clave temporal al usuario de forma segura.',
];
$mensajesError = [
    'eliminar_registro' => '✖ No fue posible eliminar el registro.',
    'eliminar' => '✖ No fue posible eliminar el usuario.',
    'ultimo_admin' => '✖ No puedes eliminar al último administrador activo.',
    'reset_password' => '✖ No fue posible resetear la contraseña del usuario.',
];
$successCode = (string)($_GET['success'] ?? '');
$errorCode = (string)($_GET['error'] ?? '');
?>
<?php if(!$eliminacionCargador || !$eliminacionOperador): ?>
<div class="mensaje-admin error">⚠️ La base de datos corresponde a una versión anterior. El panel se muestra en modo compatible, pero debes ejecutar <strong>base_dato/migracion_produccion_100.sql</strong> para habilitar completamente la eliminación lógica, edición y auditoría de registros.</div>
<?php endif; ?>
<?php if($successCode!=='' && isset($mensajesExito[$successCode])): ?><div class="mensaje-admin success"><?=e($mensajesExito[$successCode])?></div><?php endif; ?>
<?php if($errorCode!=='' && isset($mensajesError[$errorCode])): ?><div class="mensaje-admin error"><?=e($mensajesError[$errorCode])?></div><?php endif; ?>
<?php if($passwordTemporal!==null): ?>
<div class="mensaje-admin success temp-password-flash">
    🔑 Contraseña temporal para <strong><?=e($passwordTemporalUsuario)?></strong>:
    <code><?=e($passwordTemporal)?></code>
    <br><small>Este mensaje solo se muestra una vez. Compártela por un canal seguro; el usuario deberá cambiarla al iniciar sesión.</small>
</div>
<?php endif; ?>
<header class="admin-header"><div><h1>Panel Administrador</h1><p>Consulta completa y segura de registros.</p></div><div class="acciones"><a href="crear_usuario.php" class="btn btn-crear">➕ Crear Usuario</a><a href="auditoria.php" class="btn btn-limpiar">🛡️ Auditoría</a><a href="../../programa/cambiar_password.php" class="btn btn-limpiar">🔑 Cambiar mi contraseña</a><form method="POST" action="../../../backend/logout.php" class="logout-inline"><input type="hidden" name="csrf_token" value="<?=e($csrf)?>"><button class="btn btn-logout" type="submit">🚪 Cerrar sesión</button></form></div></header><section class="busqueda-card"><form method="GET" class="busqueda-form"><label for="rut">Buscar por RUT</label><input type="text" id="rut" name="rut" maxlength="12" value="<?=e($rutBusqueda)?>" placeholder="12.345.678-9"><button type="submit" class="btn btn-buscar">🔎 Buscar</button><?php if($rutBusqueda!==''):?><a href="superadmin.php" class="btn btn-limpiar">Limpiar</a><a target="_blank" rel="noopener noreferrer" href="imprimir_rut.php?rut=<?=urlencode($rutBusqueda)?>" class="btn btn-print">🖨️ Imprimir RUT</a><?php endif;?></form></section><section class="resumen"><div class="resumen-card azul"><span>Total litros cargador</span><strong><?=number_format($totalCargador,2,',','.')?> L</strong></div><div class="resumen-card verde"><span>Total litros operador</span><strong><?=number_format($totalOperador,2,',','.')?> L</strong></div></section><hr><h2>Usuarios</h2><div class="tabla-contenedor"><table><thead><tr><th>ID</th><th>Usuario</th><th>Email</th><th>Rol</th><th>Estado</th><th>Creado</th><th>Acciones</th></tr></thead><tbody><?php while($u=$usuarios->fetch_assoc()):?><tr><td><?=e($u['id'])?></td><td><?=e($u['username'])?></td><td><?=e($u['email'])?></td><td><?=e($u['rol'])?></td><td><?=e($u['estado'])?></td><td><?=e($u['created_at'])?></td><td><?php if((int)$u['id']!==(int)$_SESSION['usuario_id']):?><div class="acciones-registro"><form method="POST" action="resetear_password_usuario.php"><input type="hidden" name="csrf_token" value="<?=e($csrf)?>"><input type="hidden" name="id" value="<?=e($u['id'])?>"><button class="btn-delete-registro btn-reset-password" type="submit" title="Genera una contraseña temporal para este usuario">🔁 Resetear clave</button></form><form method="POST" action="eliminar_usuario.php"><input type="hidden" name="csrf_token" value="<?=e($csrf)?>"><input type="hidden" name="id" value="<?=e($u['id'])?>"><button class="btn-delete" type="submit">❌ Eliminar</button></form></div><?php else:?><span class="tu-usuario">(Tú)</span><?php endif;?></td></tr><?php endwhile;?></tbody></table></div><hr><h2 id="operador-cargador">Operador Cargador <a class="btn btn-print btn-exportar" href="../../../backend/exportar_csv.php?tipo=cargador<?=$rutBusqueda!==''?'&rut='.urlencode($rutBusqueda):''?>">⬇️ Exportar CSV</a></h2><div class="tabla-contenedor"><table class="tabla-registros"><thead><tr><th>Usuario</th><th>Nombres</th><th>Apellidos</th><th>RUT</th><th>Turno</th><th>Código</th><th>Ubicación</th><th>Patente</th><th>Horómetro</th><th>Litros</th><th>Fecha</th><th>Acción</th></tr></thead><tbody><?php while($r=$cargador->fetch_assoc()):?><tr><td><?=e($r['username']??'Sin usuario')?></td><td><?=e($r['nombres'])?></td><td><?=e($r['apellido_paterno'].' '.$r['apellido_materno'])?></td><td><strong><?=e($r['rut'])?></strong></td><td><?=e($r['turno'])?></td><td><?=e($r['codigo'])?></td><td><?=e($r['ubicacion'])?></td><td><?=e($r['patente'])?></td><td><?=e($r['horometro'])?></td><td><?=number_format((float)$r['litros'],2,',','.')?> L</td><td><?=e($r['fecha_registro'])?></td><td><div class="acciones-registro">
<a target="_blank" rel="noopener noreferrer" class="btn-print" href="imprimir_rut.php?rut=<?=urlencode($r['rut'])?>">🖨️ Imprimir</a>
<a class="btn btn-edit" href="editar_registro.php?tipo=cargador&amp;id=<?=e($r['id'])?>">✏️ Editar</a>
<form method="POST" action="../../../backend/eliminar_registro.php" class="form-eliminar-registro">
<input type="hidden" name="csrf_token" value="<?=e($csrf)?>">
<input type="hidden" name="id" value="<?=e($r['id'])?>">
<input type="hidden" name="tipo" value="cargador">
<button class="btn-delete-registro" type="submit">🗑️ Eliminar</button>
</form></div></td></tr><?php endwhile;?></tbody></table></div>
<?php if($totalPagCargador>1): ?>
<nav class="paginador">
<?php if($pgCargador>1): ?><a class="btn btn-limpiar" href="<?=e($armarEnlacePagina('pgc',$pgCargador-1))?>#operador-cargador">« Anterior</a><?php endif; ?>
<span class="paginador-info">Página <?=$pgCargador?> de <?=$totalPagCargador?> (<?=$totalFilasCargador?> registros)</span>
<?php if($pgCargador<$totalPagCargador): ?><a class="btn btn-limpiar" href="<?=e($armarEnlacePagina('pgc',$pgCargador+1))?>#operador-cargador">Siguiente »</a><?php endif; ?>
</nav>
<?php endif; ?>
<hr><h2 id="operador">Operador <a class="btn btn-print btn-exportar" href="../../../backend/exportar_csv.php?tipo=operador<?=$rutBusqueda!==''?'&rut='.urlencode($rutBusqueda):''?>">⬇️ Exportar CSV</a></h2><div class="tabla-contenedor"><table class="tabla-registros"><thead><tr><th>Usuario</th><th>Nombres</th><th>Apellidos</th><th>RUT</th><th>Turno</th><th>Ubicación</th><th>Código</th><th>Patente</th><th>Equipo</th><th>Horómetro</th><th>Kilómetro</th><th>Litros</th><th>Fecha</th><th>Acción</th></tr></thead><tbody><?php while($r=$operador->fetch_assoc()):?><tr><td><?=e($r['username']??'Sin usuario')?></td><td><?=e($r['nombres'])?></td><td><?=e($r['apellido_paterno'].' '.$r['apellido_materno'])?></td><td><strong><?=e($r['rut'])?></strong></td><td><?=e($r['turno'])?></td><td><?=e($r['ubicacion'])?></td><td><?=e($r['codigo_maquinaria'])?></td><td><?=e($r['patente'])?></td><td><?=e($r['equipo'])?></td><td><?=e($r['horometro'])?></td><td><?=e($r['kilometro'])?></td><td><?=number_format((float)$r['litros'],2,',','.')?> L</td><td><?=e($r['fecha_registro'])?></td><td><div class="acciones-registro">
<a target="_blank" rel="noopener noreferrer" class="btn-print" href="imprimir_rut.php?rut=<?=urlencode($r['rut'])?>">🖨️ Imprimir</a>
<a class="btn btn-edit" href="editar_registro.php?tipo=operador&amp;id=<?=e($r['id'])?>">✏️ Editar</a>
<form method="POST" action="../../../backend/eliminar_registro.php" class="form-eliminar-registro">
<input type="hidden" name="csrf_token" value="<?=e($csrf)?>">
<input type="hidden" name="id" value="<?=e($r['id'])?>">
<input type="hidden" name="tipo" value="operador">
<button class="btn-delete-registro" type="submit">🗑️ Eliminar</button>
</form></div></td></tr><?php endwhile;?></tbody></table></div>
<?php if($totalPagOperador>1): ?>
<nav class="paginador">
<?php if($pgOperador>1): ?><a class="btn btn-limpiar" href="<?=e($armarEnlacePagina('pgo',$pgOperador-1))?>#operador">« Anterior</a><?php endif; ?>
<span class="paginador-info">Página <?=$pgOperador?> de <?=$totalPagOperador?> (<?=$totalFilasOperador?> registros)</span>
<?php if($pgOperador<$totalPagOperador): ?><a class="btn btn-limpiar" href="<?=e($armarEnlacePagina('pgo',$pgOperador+1))?>#operador">Siguiente »</a><?php endif; ?>
</nav>
<?php endif; ?>
</body></html>
