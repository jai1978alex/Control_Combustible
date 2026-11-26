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
   ------------------------------------- INICIO Jerez superadmin.php ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

declare(strict_types=1);

/* TÍTULO: CARGAR LOS ARCHIVOS NECESARIOS Y PROTEGER LA PÁGINA */

// esta parte carga la conexión, la seguridad y las funciones de consumo
require_once __DIR__.'/../../../backend/conexion.php';
require_once __DIR__.'/../../../backend/security.php';
require_once __DIR__.'/../../../backend/consumo_helper.php';
// solo los administradores pueden entrar, y se crea el dato oculto para proteger los formularios
requireAdmin();
$csrf=csrfToken();

/* TÍTULO: REVISAR SI HAY UNA CONTRASEÑA TEMPORAL PARA MOSTRAR */

// GUARDAR LA CONTRASEÑA TEMPORAL (si no existe, queda vacía)
$passwordTemporal = null;
$passwordTemporalUsuario = null;
// si existe una clave temporal, se lee y se borra de la sesión para que se muestre una sola vez
if (!empty($_SESSION['flash_temp_password'])) {
    $passwordTemporal = (string)$_SESSION['flash_temp_password'];
    $passwordTemporalUsuario = (string)($_SESSION['flash_temp_password_user'] ?? '');
    unset($_SESSION['flash_temp_password'], $_SESSION['flash_temp_password_user']);
}

/* TÍTULO: BUSCAR POR RUT */

// GUARDAR EL RUT QUE ESCRIBIÓ EL ADMINISTRADOR (si no escribió nada, queda vacío)
$rutBusqueda=trim((string)($_GET['rut']??''));

/* TÍTULO: REVISAR SI LA BASE DE DATOS ES DE UNA VERSIÓN ANTIGUA */

/*
 * Las bases antiguas no tienen la columna eliminado_at.
 * Si se consulta una columna que no existe, la página se cae con error 500.
 * Por eso primero se revisa si la columna existe, sin modificar la base de datos.
 * Si existe, se ocultan los registros eliminados; si no existe, se muestran todos (1=1).
 */
// revisar si cada tabla tiene la columna eliminado_at
$eliminacionCargador=columnaExiste($conn,'operador_cargador','eliminado_at');
$eliminacionOperador=columnaExiste($conn,'operador','eliminado_at');
// condiciones con alias (para las consultas con JOIN)
$whereCargador=$eliminacionCargador ? 'oc.eliminado_at IS NULL' : '1=1';
// condiciones sin alias (para contar y sumar)
$whereCargadorSimple=$eliminacionCargador ? 'eliminado_at IS NULL' : '1=1';
$whereOperador=$eliminacionOperador ? 'o.eliminado_at IS NULL' : '1=1';
$whereOperadorSimple=$eliminacionOperador ? 'eliminado_at IS NULL' : '1=1';

/* TÍTULO: LISTA DE USUARIOS */

// esta parte trae todas las cuentas, de la más nueva a la más antigua
// si la base aún no tiene las columnas del nombre (falta la migración 06), se muestran vacías para no romper la página
$colNombre=columnaExiste($conn,'usuarios','nombres');
$colRutUsuario=columnaExiste($conn,'usuarios','rut');
$selectNombre=$colNombre
    ? "nombres,apellido_paterno,apellido_materno"
    : "'' AS nombres,'' AS apellido_paterno,'' AS apellido_materno";
$selectRut=$colRutUsuario ? "rut" : "'' AS rut";
$usuarios=$conn->query("SELECT id,username,$selectNombre,$selectRut,email,rol,estado,created_at FROM usuarios ORDER BY id DESC");

/* TÍTULO: PÁGINAS DE LAS TABLAS */

// cantidad de filas que se muestran en cada página
const FILAS_POR_PAGINA = 25;
$limiteFilas = FILAS_POR_PAGINA;
// GUARDAR LA PÁGINA ACTUAL DE CADA TABLA (mínimo la página 1)
$pgCargador = max(1, (int)($_GET['pgc'] ?? 1));
$pgOperador = max(1, (int)($_GET['pgo'] ?? 1));
// calcular desde qué fila empieza cada página
$offCargador = ($pgCargador - 1) * FILAS_POR_PAGINA;
$offOperador = ($pgOperador - 1) * FILAS_POR_PAGINA;

/* TÍTULO: CONSULTAS DE LOS REGISTROS */

// si el administrador escribió un RUT, se buscan solo los registros que coincidan
if($rutBusqueda!==''){
    $like='%'.$rutBusqueda.'%';
    // registros de operador cargador con su usuario
    $st=$conn->prepare("SELECT oc.*,u.username FROM operador_cargador oc LEFT JOIN usuarios u ON oc.usuario_id=u.id WHERE {$whereCargador} AND oc.rut LIKE ? ORDER BY oc.fecha_registro DESC LIMIT ? OFFSET ?");
    $st->bind_param('sii',$like,$limiteFilas,$offCargador);
    $st->execute();
    $cargador=$st->get_result();
    // total de coincidencias de operador cargador
    $st=$conn->prepare("SELECT COUNT(*) c FROM operador_cargador WHERE {$whereCargadorSimple} AND rut LIKE ?");
    $st->bind_param('s',$like);
    $st->execute();
    $totalFilasCargador=(int)$st->get_result()->fetch_assoc()['c'];
    // registros de operador con su usuario
    $st=$conn->prepare("SELECT o.*,u.username FROM operador o LEFT JOIN usuarios u ON o.usuario_id=u.id WHERE {$whereOperador} AND o.rut LIKE ? ORDER BY o.fecha_registro DESC LIMIT ? OFFSET ?");
    $st->bind_param('sii',$like,$limiteFilas,$offOperador);
    $st->execute();
    $operador=$st->get_result();
    // total de coincidencias de operador
    $st=$conn->prepare("SELECT COUNT(*) c FROM operador WHERE {$whereOperadorSimple} AND rut LIKE ?");
    $st->bind_param('s',$like);
    $st->execute();
    $totalFilasOperador=(int)$st->get_result()->fetch_assoc()['c'];
}else{
    // si no hay RUT, registros de operador cargador sin filtro
    $st=$conn->prepare("SELECT oc.*,u.username FROM operador_cargador oc LEFT JOIN usuarios u ON oc.usuario_id=u.id WHERE {$whereCargador} ORDER BY oc.fecha_registro DESC LIMIT ? OFFSET ?");
    $st->bind_param('ii',$limiteFilas,$offCargador);
    $st->execute();
    $cargador=$st->get_result();
    // total de registros de operador cargador
    $totalFilasCargador=(int)$conn->query("SELECT COUNT(*) c FROM operador_cargador WHERE {$whereCargadorSimple}")->fetch_assoc()['c'];
    // registros de operador sin filtro
    $st=$conn->prepare("SELECT o.*,u.username FROM operador o LEFT JOIN usuarios u ON o.usuario_id=u.id WHERE {$whereOperador} ORDER BY o.fecha_registro DESC LIMIT ? OFFSET ?");
    $st->bind_param('ii',$limiteFilas,$offOperador);
    $st->execute();
    $operador=$st->get_result();
    // total de registros de operador
    $totalFilasOperador=(int)$conn->query("SELECT COUNT(*) c FROM operador WHERE {$whereOperadorSimple}")->fetch_assoc()['c'];
}
// calcular cuántas páginas tiene cada tabla (mínimo 1)
$totalPagCargador=max(1,(int)ceil($totalFilasCargador/FILAS_POR_PAGINA));
$totalPagOperador=max(1,(int)ceil($totalFilasOperador/FILAS_POR_PAGINA));

/* TÍTULO: TOTALES DE LITROS PARA LAS TARJETAS */

// esta parte suma los litros de todos los registros, sin importar la página ni el RUT buscado
$totalCargador=(float)$conn->query("SELECT COALESCE(SUM(litros),0) t FROM operador_cargador WHERE {$whereCargadorSimple}")->fetch_assoc()['t'];
$totalOperador=(float)$conn->query("SELECT COALESCE(SUM(litros),0) t FROM operador WHERE {$whereOperadorSimple}")->fetch_assoc()['t'];

/* TÍTULO: ENLACES PARA CAMBIAR DE PÁGINA */

// GUARDAR EL RUT BUSCADO PARA NO PERDERLO AL CAMBIAR DE PÁGINA
$qsBase = $rutBusqueda!=='' ? ('rut='.urlencode($rutBusqueda)) : '';
// esta función arma el enlace de cada página (superadmin.php?rut=...&pgc=2)
$armarEnlacePagina = static function(string $param, int $pagina) use ($qsBase): string {
    // se quita el RUT vacío para que no sobre ningún símbolo "&"
    $partes = array_filter([$qsBase, $param.'='.$pagina]);
    return 'superadmin.php?'.implode('&', $partes);
};

/* TÍTULO: CUERPO PRINCIPAL DE LA PÁGINA */

// desde aquí se muestra el documento HTML; el código PHP intercalado solo lee lo calculado arriba
?><!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Panel Administrador</title>
    <link rel="stylesheet" href="../../../css/programa/superadmin.css">
    <script src="../../../js/programa/admin.js" defer></script>
</head>

<body>
<?php
/* TÍTULO: MENSAJES DE ÉXITO Y ERROR */

// esta parte guarda los textos de los mensajes que vienen en la URL (los códigos desconocidos se ignoran)
$mensajesExito = [
    'registro_eliminado' => '✔ Registro eliminado correctamente.',
    'usuario' => '✔ Usuario eliminado correctamente.',
    'password_actualizada' => '✔ Contraseña actualizada correctamente.',
    'password_reseteada' => '✔ Contraseña reseteada. Comunica la clave temporal al usuario de forma segura.',
    'nombre_actualizado' => '✔ Nombre del usuario actualizado correctamente.',
    'rut_actualizado' => '✔ RUT del usuario actualizado correctamente.',
];
$mensajesError = [
    'eliminar_registro' => '✖ No fue posible eliminar el registro.',
    'eliminar' => '✖ No fue posible eliminar el usuario.',
    'ultimo_admin' => '✖ No puedes eliminar al último administrador activo.',
    'reset_password' => '✖ No fue posible resetear la contraseña del usuario.',
    'editar_nombre' => '✖ No fue posible actualizar el nombre. Revisa que los tres campos estén completos.',
    'editar_rut' => '✖ No fue posible actualizar el RUT. Revisa que sea válido y que no esté asignado a otro usuario.',
];
// GUARDAR EL CÓDIGO DEL MENSAJE QUE VIENE EN LA URL
$successCode = (string)($_GET['success'] ?? '');
$errorCode = (string)($_GET['error'] ?? '');
?>

<!-- TÍTULO: AVISO DE BASE DE DATOS ANTIGUA -->

<?php?>
<!-- esta parte avisa si falta la columna eliminado_at en alguna de las dos tablas -->
<?php?>
<?php if(!$eliminacionCargador || !$eliminacionOperador): ?>
<div class="mensaje-admin error">⚠️ La base de datos corresponde a una versión anterior. El panel se muestra en modo compatible, pero debes ejecutar <strong>base_dato/migracion_produccion_100.sql</strong> para habilitar completamente la eliminación lógica, edición y auditoría de registros.</div>
<?php endif; ?>

 <!-- TÍTULO: MENSAJE PARA MOSTRAR EL ÉXITO -->

<?php?>
<!-- esta parte muestra el mensaje cuando la acción salió bien -->
<?php?>
<?php if($successCode!=='' && isset($mensajesExito[$successCode])): ?><div class="mensaje-admin success"><?=e($mensajesExito[$successCode])?></div><?php endif; ?>

 <!-- TÍTULO: MENSAJE PARA MOSTRAR EL ERROR -->

<?php?>
<!-- esta parte muestra el mensaje cuando existe un problema -->
<?php?>
<?php if($errorCode!=='' && isset($mensajesError[$errorCode])): ?><div class="mensaje-admin error"><?=e($mensajesError[$errorCode])?></div><?php endif; ?>

 <!-- TÍTULO: CONTRASEÑA TEMPORAL DEL USUARIO -->

<?php?>
<!-- esta parte muestra la clave temporal una sola vez, después de resetear una contraseña -->
<?php?>
<?php if($passwordTemporal!==null): ?>
<div class="mensaje-admin success temp-password-flash">
    🔑 Contraseña temporal para <strong><?=e($passwordTemporalUsuario)?></strong>:
    <code><?=e($passwordTemporal)?></code>
    <br><small>Este mensaje solo se muestra una vez. Compártela por un canal seguro; el usuario deberá cambiarla al iniciar sesión.</small>
</div>
<?php endif; ?>
<?php?>

<!-- TÍTULO: ENCABEZADO DEL PANEL -->

<header class="admin-header">
    <div>
        <h1>Panel Administrador</h1>
        <p>Consulta completa y segura de registros.</p>
    </div>
    <div class="acciones">
        <a href="crear_usuario.php" class="btn btn-crear">➕ Crear Usuario</a>
        <a href="reportes.php" class="btn btn-buscar">📊 Reportes</a>
        <a href="conciliacion.php" class="btn btn-buscar">⛽ Saldo y conciliación</a>
        <a href="auditoria.php" class="btn btn-limpiar">🛡️ Auditoría</a>
        <a href="derechos_datos.php" class="btn btn-limpiar">🔒 Derechos de datos</a>
        <a href="../../programa/cambiar_password.php" class="btn btn-limpiar">🔑 Cambiar mi contraseña</a>
        <form method="POST" action="../../../backend/logout.php" class="logout-inline">
            <input type="hidden" name="csrf_token" value="<?=e($csrf)?>">
            <button class="btn btn-logout" type="submit">🚪 Cerrar sesión</button>
        </form>
    </div>
</header>

<!-- TÍTULO: BUSCADOR POR RUT -->

<section class="busqueda-card">
    <form method="GET" class="busqueda-form">
        <label for="rut">Buscar por RUT</label>
        <input type="text" id="rut" name="rut" maxlength="12" value="<?=e($rutBusqueda)?>" placeholder="12.345.678-9">
        <button type="submit" class="btn btn-buscar">🔎 Buscar</button>
        <?php if($rutBusqueda!==''):?>
        <a href="superadmin.php" class="btn btn-limpiar">Limpiar</a>
        <a target="_blank" rel="noopener noreferrer" href="imprimir_rut.php?rut=<?=urlencode($rutBusqueda)?>" class="btn btn-print">🖨️ Imprimir RUT</a>
        <?php endif;?>
    </form>
</section>

<!-- TÍTULO: TARJETAS DE RESUMEN DE LITROS -->

<section class="resumen">
    <div class="resumen-card azul"><span>Total litros cargador</span><strong><?=number_format($totalCargador,2,',','.')?> L</strong></div>
    <div class="resumen-card verde"><span>Total litros operador</span><strong><?=number_format($totalOperador,2,',','.')?> L</strong></div>
</section>

<hr>

<!-- TÍTULO: TABLA DE USUARIOS -->

<h2>Usuarios</h2>
<div class="tabla-contenedor">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Nombre</th>
                <th>RUT</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Creado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while($u=$usuarios->fetch_assoc()):?>
            <tr>
                <td><?=e($u['id'])?></td>
                <td><?=e($u['username'])?></td>
                <td>
                    <?php $nombreCompleto=trim(($u['nombres']??'').' '.($u['apellido_paterno']??'').' '.($u['apellido_materno']??''));?>
                    <?php if($nombreCompleto!==''):?><?=e($nombreCompleto)?><?php else:?><em class="sin-nombre">Sin nombre</em><?php endif;?>
                    <?php if($colNombre):?>
                    <details class="editar-nombre">
                        <summary>✏️ Editar nombre</summary>
                        <form method="POST" action="editar_nombre_usuario.php">
                            <input type="hidden" name="csrf_token" value="<?=e($csrf)?>">
                            <input type="hidden" name="id" value="<?=e($u['id'])?>">
                            <input type="text" name="nombres" maxlength="100" placeholder="Nombres" value="<?=e($u['nombres']??'')?>" required>
                            <input type="text" name="apellido_paterno" maxlength="100" placeholder="Apellido paterno" value="<?=e($u['apellido_paterno']??'')?>" required>
                            <input type="text" name="apellido_materno" maxlength="100" placeholder="Apellido materno" value="<?=e($u['apellido_materno']??'')?>" required>
                            <button type="submit">Guardar</button>
                        </form>
                    </details>
                    <?php endif;?>
                </td>
                <td>
                    <?=e($u['rut'] ?? '')?>
                    <?php if($colRutUsuario):?>
                    <details class="editar-nombre">
                        <summary>✏️ Editar RUT</summary>
                        <form method="POST" action="editar_rut_usuario.php">
                            <input type="hidden" name="csrf_token" value="<?=e($csrf)?>">
                            <input type="hidden" name="id" value="<?=e($u['id'])?>">
                            <input type="text" name="rut" maxlength="12" placeholder="12.345.678-9" value="<?=e($u['rut'] ?? '')?>" required>
                            <button type="submit">Guardar</button>
                        </form>
                    </details>
                    <?php endif;?>
                </td>
                <td><?=e($u['email'])?></td>
                <td><?=e($u['rol'])?></td>
                <td><?=e($u['estado'])?></td>
                <td><?=e($u['created_at'])?></td>
                <td>
                    <?php if((int)$u['id']!==(int)$_SESSION['usuario_id']):?>
                    <div class="acciones-registro">
                        <form method="POST" action="resetear_password_usuario.php">
                            <input type="hidden" name="csrf_token" value="<?=e($csrf)?>">
                            <input type="hidden" name="id" value="<?=e($u['id'])?>">
                            <button class="btn-delete-registro btn-reset-password" type="submit" title="Genera una contraseña temporal para este usuario">🔁 Resetear clave</button>
                        </form>
                        <form method="POST" action="eliminar_usuario.php">
                            <input type="hidden" name="csrf_token" value="<?=e($csrf)?>">
                            <input type="hidden" name="id" value="<?=e($u['id'])?>">
                            <button class="btn-delete" type="submit">❌ Eliminar</button>
                        </form>
                    </div>
                    <?php else:?>
                    <span class="tu-usuario">(Tú)</span>
                    <?php endif;?>
                </td>
            </tr>
            <?php endwhile;?>
        </tbody>
    </table>
</div>

<hr>

<!-- TÍTULO: TABLA DE OPERADOR CARGADOR -->

<h2 id="operador-cargador">Operador Cargador <a class="btn btn-print btn-exportar" href="../../../backend/exportar_csv.php?tipo=cargador<?=$rutBusqueda!==''?'&rut='.urlencode($rutBusqueda):''?>">⬇️ Exportar CSV</a></h2>
<div class="tabla-contenedor">
    <table class="tabla-registros">
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Nombres</th>
                <th>Apellidos</th>
                <th>RUT</th>
                <th>Turno</th>
                <th>Código</th>
                <th>Ubicación</th>
                <th>Patente</th>
                <th>Horómetro</th>
                <th>Litros</th>
                <th>Remanente</th>
                <th>Fecha</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php while($r=$cargador->fetch_assoc()):?>
            <tr>
                <td><?=e($r['username']??'Sin usuario')?></td>
                <td><?=e($r['nombres'])?></td>
                <td><?=e($r['apellido_paterno'].' '.$r['apellido_materno'])?></td>
                <td><strong><?=e($r['rut'])?></strong></td>
                <td><?=e($r['turno'])?></td>
                <td><?=e($r['codigo'])?></td>
                <td><?=e($r['ubicacion'])?></td>
                <td><?=e($r['patente'])?></td>
                <td><?=e($r['horometro'])?></td>
                <td><?=number_format((float)$r['litros'],2,',','.')?> L</td>
                <td><?=number_format((float)($r['remanente']??0),2,',','.')?> L</td>
                <td><?=e($r['fecha_registro'])?></td>
                <td><div class="acciones-registro">

<!-- TÍTULO: BOTONES DE CADA REGISTRO DE OPERADOR CARGADOR -->

<?php?>
<!-- esta parte permite imprimir por RUT, editar o eliminar el registro -->
<?php?>
<a target="_blank" rel="noopener noreferrer" class="btn-print" href="imprimir_rut.php?rut=<?=urlencode($r['rut'])?>">🖨️ Imprimir</a>
<a class="btn btn-edit" href="editar_registro.php?tipo=cargador&amp;id=<?=e($r['id'])?>">✏️ Editar</a>
<form method="POST" action="../../../backend/eliminar_registro.php" class="form-eliminar-registro">
<input type="hidden" name="csrf_token" value="<?=e($csrf)?>">
<input type="hidden" name="id" value="<?=e($r['id'])?>">
<input type="hidden" name="tipo" value="cargador">
<button class="btn-delete-registro" type="submit">🗑️ Eliminar</button>
</form></div></td>
            </tr>
            <?php endwhile;?>
        </tbody>
    </table>
</div>

<!-- TÍTULO: CAMBIO DE PÁGINA DE OPERADOR CARGADOR -->

<?php?>
<!-- esta parte solo aparece si la tabla tiene más de una página -->
<?php?>
<?php if($totalPagCargador>1): ?>
<nav class="paginador">
<?php if($pgCargador>1): ?><a class="btn btn-limpiar" href="<?=e($armarEnlacePagina('pgc',$pgCargador-1))?>#operador-cargador">« Anterior</a><?php endif; ?>
<span class="paginador-info">Página <?=$pgCargador?> de <?=$totalPagCargador?> (<?=$totalFilasCargador?> registros)</span>
<?php if($pgCargador<$totalPagCargador): ?><a class="btn btn-limpiar" href="<?=e($armarEnlacePagina('pgc',$pgCargador+1))?>#operador-cargador">Siguiente »</a><?php endif; ?>
</nav>
<?php endif; ?>
<?php ?> 

<!-- TÍTULO: TABLA DE OPERADOR
     muestra el título Operador con su botón Exportar CSV
     muestra la tabla de registros, con la unidad de consumo según cada equipo -->

<hr>
<h2 id="operador">Operador <a class="btn btn-print btn-exportar" href="../../../backend/exportar_csv.php?tipo=operador<?=$rutBusqueda!==''?'&rut='.urlencode($rutBusqueda):''?>">⬇️ Exportar CSV</a></h2>
<div class="tabla-contenedor">
    <table class="tabla-registros">
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Nombres</th>
                <th>Apellidos</th>
                <th>RUT</th>
                <th>Turno</th>
                <th>Ubicación</th>
                <th>Código</th>
                <th>Patente</th>
                <th>Equipo</th>
                <th>Horómetro</th>
                <th>Kilómetro</th>
                <th>Litros</th>
                <th>Consumo</th>
                <th>Fecha</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php while($r=$operador->fetch_assoc()):?>
            <tr>
                <td><?=e($r['username']??'Sin usuario')?></td>
                <td><?=e($r['nombres'])?></td>
                <td><?=e($r['apellido_paterno'].' '.$r['apellido_materno'])?></td>
                <td><strong><?=e($r['rut'])?></strong></td>
                <td><?=e($r['turno'])?></td>
                <td><?=e($r['ubicacion'])?></td>
                <td><?=e($r['codigo_maquinaria'])?></td>
                <td><?=e($r['patente'])?></td>
                <td><?=e($r['equipo'])?></td>
                <td><?=e($r['horometro'])?></td>
                <td><?=e($r['kilometro'])?></td>
                <td><?=number_format((float)$r['litros'],2,',','.')?> L</td>
                <td><?=number_format((float)($r['consumo']??0),2,',','.')?> <?=e(unidadConsumo($r))?></td>
                <td><?=e($r['fecha_registro'])?></td>
                <td><div class="acciones-registro">

<!-- TÍTULO: BOTONES DE CADA REGISTRO DE OPERADOR -->

<?php?>
<!-- esta parte permite imprimir por RUT, editar o eliminar el registro -->
<?php?>
<a target="_blank" rel="noopener noreferrer" class="btn-print" href="imprimir_rut.php?rut=<?=urlencode($r['rut'])?>">🖨️ Imprimir</a>
<a class="btn btn-edit" href="editar_registro.php?tipo=operador&amp;id=<?=e($r['id'])?>">✏️ Editar</a>
<form method="POST" action="../../../backend/eliminar_registro.php" class="form-eliminar-registro">
<input type="hidden" name="csrf_token" value="<?=e($csrf)?>">
<input type="hidden" name="id" value="<?=e($r['id'])?>">
<input type="hidden" name="tipo" value="operador">
<button class="btn-delete-registro" type="submit">🗑️ Eliminar</button>
</form></div></td>
            </tr>
            <?php endwhile;?>
        </tbody>
    </table>
</div>

<!-- TÍTULO: CAMBIO DE PÁGINA DE OPERADOR -->

<?php?>
<!-- esta parte solo aparece si la tabla tiene más de una página -->
<?php?>
<?php if($totalPagOperador>1): ?>
<nav class="paginador">
<?php if($pgOperador>1): ?><a class="btn btn-limpiar" href="<?=e($armarEnlacePagina('pgo',$pgOperador-1))?>#operador">« Anterior</a><?php endif; ?>
<span class="paginador-info">Página <?=$pgOperador?> de <?=$totalPagOperador?> (<?=$totalFilasOperador?> registros)</span>
<?php if($pgOperador<$totalPagOperador): ?><a class="btn btn-limpiar" href="<?=e($armarEnlacePagina('pgo',$pgOperador+1))?>#operador">Siguiente »</a><?php endif; ?>
</nav>
<?php endif; ?>
</body>

</html>
<?php



/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- FIN Jerez superadmin.php --------------------------------------
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
