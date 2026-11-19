<?php
/*
Sitio Web Creado por Jerez.
Direccion: Miraflorez #1280
Quintero - Chile
jaime.jerez1978@gmail.com
https://www.
Creado, Programado y Diseñado por Jerez.
JJA
*/

/* -------------------------------------------------------------------------------------------------------------
   ------------------------------------- INICIO Jerez auditoria.php ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

// TÍTULO: REGLAS DE FUNCIONAMIENTO DE LA PÁGINA
// pide que el programa sea más exigente para evitar errores con los datos
declare(strict_types=1);
// TÍTULO: ARCHIVOS QUE LA PÁGINA NECESITA PARA FUNCIONAR
// trae el archivo que permite conectarse a la base de datos
require_once __DIR__.'/../../../backend/conexion.php';
// trae el archivo con las reglas de seguridad del sistema
require_once __DIR__.'/../../../backend/security.php';
// TÍTULO: CONTROL DE ACCESO A LA PÁGINA
// solo deja entrar a las personas que son administradoras
requireAdmin();
// crea la clave de seguridad que protege los formularios
$csrf = csrfToken();


// TÍTULO: DATOS QUE LA PERSONA ESCRIBE EN LOS FILTROS
// guarda la fecha escrita en el buscador, sin espacios de más
$fechaBusqueda = trim((string)($_GET['fecha'] ?? ''));
// guarda el RUT escrito en el buscador, sin espacios de más
$rutBusqueda   = trim((string)($_GET['rut'] ?? ''));
// guarda si la persona pidió ver la versión para imprimir
$modoImprimir  = ((string)($_GET['imprimir'] ?? '') === '1');
// deja preparado el espacio para avisos sobre filtros mal escritos
$avisoFiltro   = '';


// TÍTULO: REVISIÓN DE LA FECHA ESCRITA
// deja vacío el comienzo y el final del rango de fechas
$fechaInicio = $fechaFin = null;
// revisa si la persona escribió una fecha
if ($fechaBusqueda !== '') {
    // intenta convertir el texto escrito en una fecha real
    $f = DateTime::createFromFormat('!Y-m-d', $fechaBusqueda);
    // confirma que la fecha existe y está bien escrita
    if ($f && $f->format('Y-m-d') === $fechaBusqueda) {
        // marca el comienzo del día buscado
        $fechaInicio = $f->format('Y-m-d 00:00:00');
        // marca el final del día buscado, que es el comienzo del día siguiente
        $fechaFin    = $f->modify('+1 day')->format('Y-m-d 00:00:00');
    // si la fecha no es válida
    } else {
        // prepara el aviso de que la fecha no sirve y no se usará
        $avisoFiltro  = 'La fecha ingresada no es válida; se ignoró ese filtro.';
        // borra la fecha para no usarla en la búsqueda
        $fechaBusqueda = '';
    }
}


// TÍTULO: REVISIÓN DEL RUT ESCRITO
// deja el RUT limpio vacío por ahora
$rutLimpio = '';
// revisa si la persona escribió un RUT
if ($rutBusqueda !== '') {
    // quita puntos, guion y otros símbolos para dejar solo números y la letra K
    $rutLimpio = preg_replace('/[^0-9K]/', '', strtoupper($rutBusqueda)) ?? '';
    // revisa que el RUT no quede vacío ni demasiado largo
    if ($rutLimpio === '' || strlen($rutLimpio) > 12) {
        // prepara el aviso de que el RUT no sirve y no se usará
        $avisoFiltro  = 'El RUT ingresado no es válido; se ignoró ese filtro.';
        // borra el RUT escrito
        $rutBusqueda  = '';
        // borra el RUT limpio
        $rutLimpio    = '';
    }
}


// TÍTULO: REVISIÓN DE LA CUENTA DE USUARIOS
// supone por ahora que las cuentas no tienen RUT guardado
$tieneRutCuenta = false;
// consulta si las cuentas de usuarios guardan un RUT
$colRut = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'rut'");
// si la consulta funcionó y encontró el dato, lo anota
if ($colRut && $colRut->num_rows > 0) { $tieneRutCuenta = true; }
// elige qué dato mostrar como RUT de la cuenta
$campoRutCuenta = $tieneRutCuenta ? 'u.rut' : 'NULL';


// TÍTULO: ARMADO DE LA BÚSQUEDA
// deja vacías las condiciones, los tipos de datos y los valores de la búsqueda
$where = []; $tipos = ''; $params = [];
// si hay una fecha para buscar
if ($fechaInicio !== null) {
    // agrega la condición de que el registro esté dentro de ese día
    $where[] = 'a.fecha_registro >= ? AND a.fecha_registro < ?';
    // anota los valores del día buscado
    $tipos  .= 'ss'; $params[] = $fechaInicio; $params[] = $fechaFin;
}
// si hay un RUT para buscar
if ($rutLimpio !== '') {
    
    // prepara el RUT para buscarlo en cualquier parte del texto
    $like = '%'.$rutLimpio.'%';
    // arma la condición que busca el RUT dentro del detalle de la acción
    $cond = "(REPLACE(REPLACE(IFNULL(a.detalle,''),'.',''),'-','') LIKE ? AND a.detalle LIKE '%RUT%')";
    // anota el valor del RUT buscado
    $tipos .= 's'; $params[] = $like;
    // si las cuentas guardan RUT
    if ($tieneRutCuenta) {
        // agrega la búsqueda también en el RUT de la cuenta
        $cond .= " OR REPLACE(REPLACE(IFNULL(u.rut,''),'.',''),'-','') LIKE ?";
        // anota otra vez el valor del RUT buscado
        $tipos .= 's'; $params[] = $like;
    }
    // agrega la condición completa a la búsqueda
    $where[] = '('.$cond.')';
}
// junta todas las condiciones en una sola frase de búsqueda
$sqlWhere = $where ? ('WHERE '.implode(' AND ', $where)) : '';
// indica de qué tablas se sacan los registros
$sqlFrom  = 'FROM auditoria a LEFT JOIN usuarios u ON a.usuario_id=u.id';


// TÍTULO: CONTEO DE REGISTROS ENCONTRADOS
// prepara la consulta que cuenta cuántos registros hay
$stc = $conn->prepare("SELECT COUNT(*) c $sqlFrom $sqlWhere");
// si hay filtros, los entrega a la consulta
if ($params) { $stc->bind_param($tipos, ...$params); }
// ejecuta el conteo
$stc->execute();
// guarda el total de registros encontrados
$totalFilas = (int)$stc->get_result()->fetch_assoc()['c'];

// TÍTULO: OBTENCIÓN DE LOS REGISTROS A MOSTRAR
// define el máximo de registros que se pueden imprimir
$limiteImpresion = 10000;
// define cuántos registros se ven por página en pantalla
$limite = 50;
// guarda el número de página que se quiere ver
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
// arma la consulta que trae los registros, del más reciente al más antiguo
$sqlSelect = "SELECT a.*, u.username, $campoRutCuenta AS rut_cuenta $sqlFrom $sqlWhere ORDER BY a.fecha_registro DESC, a.id DESC";
// si la persona pidió la versión para imprimir
if ($modoImprimir) {
    // prepara la consulta limitada al máximo de impresión
    $st = $conn->prepare($sqlSelect.' LIMIT ?');
    // reúne los valores de la búsqueda y el máximo de impresión
    $t2 = $tipos.'i'; $p2 = array_merge($params, [$limiteImpresion]);
    // entrega los valores a la consulta
    $st->bind_param($t2, ...$p2);
    // deja anotado en la auditoría que alguien imprimió, con los filtros usados
    audit($conn, (int)$_SESSION['usuario_id'], 'IMPRIMIR_AUDITORIA', 'auditoria', null,
        'Filtros: fecha='.($fechaBusqueda ?: 'todas').'; rut='.($rutBusqueda ?: 'todos').'; filas='.min($totalFilas, $limiteImpresion));
// si la persona ve la página en pantalla
} else {
    // calcula cuántas páginas hay en total
    $totalPaginas = max(1, (int)ceil($totalFilas / $limite));
    // evita pedir una página que no existe
    $pagina = min($pagina, $totalPaginas);
    // calcula desde qué registro empieza la página pedida
    $offset = ($pagina - 1) * $limite;
    // prepara la consulta de solo una página
    $st = $conn->prepare($sqlSelect.' LIMIT ? OFFSET ?');
    // reúne los valores de la búsqueda y de la página
    $t2 = $tipos.'ii'; $p2 = array_merge($params, [$limite, $offset]);
    // entrega los valores a la consulta
    $st->bind_param($t2, ...$p2);
}
// ejecuta la consulta de registros
$st->execute();
// guarda los registros encontrados para mostrarlos
$filas = $st->get_result()->fetch_all(MYSQLI_ASSOC);

// TÍTULO: FUNCIÓN QUE ENCUENTRA RUT DENTRO DEL DETALLE
// función que recibe el texto del detalle y devuelve los RUT que aparecen en él
function rutsDelDetalle(?string $detalle): string {
    // si no hay detalle, no devuelve nada
    if ($detalle === null || $detalle === '') { return ''; }
    // busca en el texto todo lo que tenga forma de RUT
    preg_match_all('/\b\d{1,2}\.?\d{3}\.?\d{3}-?[0-9kK]\b/', $detalle, $m);
    // devuelve los RUT encontrados, sin repetir y separados por coma
    return implode(', ', array_values(array_unique($m[0] ?? [])));
}

// TÍTULO: ENLACES QUE CONSERVAN LOS FILTROS
// guarda solo los filtros que la persona realmente usó
$qsFiltros = array_filter([
    // filtro de fecha
    'fecha' => $fechaBusqueda,
    // filtro de RUT
    'rut'   => $rutBusqueda,
// descarta los filtros que están vacíos
], static fn($v) => $v !== '');
// función que arma el enlace a una página manteniendo los filtros
$urlPagina = static function (int $p) use ($qsFiltros): string {
    // devuelve la dirección de la página pedida con sus filtros
    return 'auditoria.php?'.http_build_query($qsFiltros + ['pagina' => $p]);
};
// arma el enlace de la versión para imprimir manteniendo los filtros
$urlImprimir = 'auditoria.php?'.http_build_query($qsFiltros + ['imprimir' => 1]);

// TÍTULO: TEXTO QUE DESCRIBE LOS FILTROS USADOS
// deja vacía la lista de descripciones
$descFiltro = [];
// si hay fecha, agrega su descripción con formato día-mes-año
if ($fechaBusqueda !== '') { $descFiltro[] = 'Fecha: '.date('d-m-Y', strtotime($fechaBusqueda)); }
// si hay RUT, agrega su descripción
if ($rutBusqueda !== '')   { $descFiltro[] = 'RUT: '.$rutBusqueda; }
// une las descripciones o indica que no hay filtros
$textoFiltro = $descFiltro ? implode(' · ', $descFiltro) : 'Sin filtros (todos los registros)';
// TÍTULO: COMIENZO DE LA PÁGINA QUE VE LA PERSONA
?><!doctype html>
<!-- TÍTULO: ENCABEZADO DE LA PÁGINA -->
<!-- datos generales de la página: idioma, letras y tamaño en el celular -->
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<!-- título que se ve en la pestaña del navegador -->
<title>Auditoría - <?=e(appName())?></title>
<!-- estilos generales de la página -->
<link rel="stylesheet" href="../../../css/programa/superadmin.css">
<?php // si es la versión para imprimir, se cargan estilos y funciones extra ?>
<?php if ($modoImprimir): ?>
<!-- estilos especiales para la hoja impresa -->
<link rel="stylesheet" href="../../../css/programa/auditoria_imprimir.css">
<!-- funciones de los botones de imprimir y cerrar -->
<script src="../../../js/programa/imprimir.js" defer></script>
<?php endif; ?>
<!-- fin del encabezado e inicio del cuerpo de la página -->
</head><body>
<?php // TÍTULO: VERSIÓN PARA IMPRIMIR ?>
<?php if ($modoImprimir): ?>
<!-- hoja principal que se imprime -->
<main class="hoja-auditoria">
  <!-- parte de arriba de la hoja -->
  <header class="cabecera-auditoria">
    <!-- zona del título y de los filtros usados -->
    <div>
      <!-- título de la hoja con el nombre del sistema -->
      <h1>Auditoría del sistema · <?=e(appName())?></h1>
      <!-- texto que explica qué filtros se usaron -->
      <p><?=e($textoFiltro)?></p>
    </div>
    <!-- zona con los datos de quién y cuándo generó la hoja -->
    <div class="meta-auditoria">
      <!-- fecha y hora en que se generó la hoja -->
      <div>Generado: <strong><?=e(date('d-m-Y H:i'))?></strong></div>
      <!-- nombre de la persona que generó la hoja -->
      <div>Por: <strong><?=e($_SESSION['username'] ?? 'Administrador')?></strong></div>
      <!-- cantidad de registros que trae la hoja -->
      <div>Registros: <strong><?=e(count($filas))?></strong><?php if ($totalFilas > count($filas)): ?> de <?=e($totalFilas)?><?php endif; ?></div>
    </div>
  </header>
  <?php // si hay más registros que el máximo, avisa que solo se imprimen los más recientes ?>
  <?php if ($totalFilas > $limiteImpresion): ?>
    <!-- aviso de que la hoja no trae todos los registros -->
    <p class="aviso-impresion">Se imprimen los <?=e($limiteImpresion)?> registros más recientes de <?=e($totalFilas)?>. Afina el filtro para ver el resto.</p>
  <?php endif; ?>
  <!-- tabla con los registros de la hoja impresa -->
  <table class="tabla-auditoria">
    <!-- títulos de las columnas de la tabla -->
    <thead><tr><th>Fecha</th><th>Usuario</th><th>RUT cuenta</th><th>Acción</th><th>Tabla</th><th>ID</th><th>RUT en detalle</th><th>Detalle</th><th>IP</th></tr></thead>
    <!-- cuerpo de la tabla donde van los registros -->
    <tbody>
    <?php // si no hay registros, muestra un mensaje en la tabla ?>
    <?php if (!$filas): ?><tr><td colspan="9" class="sin-datos">No hay registros para el filtro indicado.</td></tr><?php endif; ?>
    <?php // repite una fila por cada registro encontrado ?>
    <?php foreach ($filas as $a): ?>
      <tr>
        <?php // fecha y hora del registro ?>
        <td><?=e($a['fecha_registro'])?></td>
        <?php // usuario que hizo la acción, o Sistema si fue automática ?>
        <td><?=e($a['username'] ?? 'Sistema')?></td>
        <?php // RUT de la cuenta del usuario ?>
        <td><?=e($a['rut_cuenta'] ?? '')?></td>
        <?php // acción realizada ?>
        <td><?=e($a['accion'])?></td>
        <?php // tabla donde se hizo el cambio ?>
        <td><?=e($a['tabla_afectada'])?></td>
        <?php // número del registro que cambió ?>
        <td><?=e($a['registro_id'])?></td>
        <?php // RUT que aparecen escritos dentro del detalle ?>
        <td><?=e(rutsDelDetalle($a['detalle'] ?? null))?></td>
        <?php // texto con el detalle de la acción ?>
        <td><?=e($a['detalle'])?></td>
        <?php // dirección desde donde se hizo la acción ?>
        <td><?=e($a['ip'])?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <!-- zona de los botones de la hoja impresa -->
  <div class="acciones-impresion">
    <!-- botón para mandar la hoja a la impresora -->
    <button type="button" id="btnImprimir">🖨️ Imprimir</button>
    <!-- botón para cerrar la hoja -->
    <button type="button" class="secundario" id="btnCerrar">Cerrar</button>
  </div>
</main>
<?php // TÍTULO: VERSIÓN NORMAL EN PANTALLA ?>
<?php else: ?>
<!-- contenedor principal y tarjeta de búsqueda -->
<main class="admin-container"><section class="busqueda-card">
  <!-- botón para volver al panel anterior -->
  <div class="acciones"><a class="btn btn-limpiar" href="superadmin.php">← Volver</a></div>
  <!-- título principal de la página -->
  <h1>Auditoría del sistema</h1>
  <!-- texto que explica para qué sirve la página -->
  <p>Registro de acciones administrativas y operacionales. Solo lectura.</p>

  <!-- TÍTULO: FORMULARIO DE BÚSQUEDA -->
  <!-- formulario para filtrar los registros por fecha y RUT -->
  <form method="GET" action="auditoria.php" class="busqueda-form auditoria-filtros">
    <!-- grupo del campo de fecha -->
    <div class="campo-filtro">
      <!-- texto que indica qué dato escribir -->
      <label for="fecha">Buscar por fecha</label>
      <!-- campo para elegir la fecha -->
      <input type="date" id="fecha" name="fecha" value="<?=e($fechaBusqueda)?>">
    </div>
    <!-- grupo del campo de RUT -->
    <div class="campo-filtro">
      <!-- texto que indica qué dato escribir -->
      <label for="rut">Buscar por RUT</label>
      <!-- campo para escribir el RUT -->
      <input type="text" id="rut" name="rut" value="<?=e($rutBusqueda)?>" placeholder="12.345.678-5" maxlength="12" autocomplete="off">
    </div>
    <!-- botón para realizar la búsqueda -->
    <button class="btn btn-crear" type="submit">🔍 Buscar</button>
    <!-- botón para borrar los filtros -->
    <a class="btn btn-limpiar" href="auditoria.php">Limpiar</a>
    <!-- botón para abrir la versión para imprimir en una pestaña nueva -->
    <a class="btn btn-print" target="_blank" rel="noopener noreferrer" href="<?=e($urlImprimir)?>">🖨️ Imprimir todo</a>
  </form>
  <!-- texto de ayuda con los filtros usados y la cantidad de registros -->
  <p class="ayuda-busqueda">Puedes buscar solo por fecha, solo por RUT o combinar ambos. El RUT se puede escribir con o sin puntos y guion. <strong><?=e($textoFiltro)?></strong> — <?=e($totalFilas)?> registro(s).</p>
  <?php // si hubo un filtro mal escrito, muestra el aviso ?>
  <?php if ($avisoFiltro !== ''): ?><div class="mensaje-admin error"><?=e($avisoFiltro)?></div><?php endif; ?>

  <!-- TÍTULO: TABLA DE REGISTROS -->
  <!-- caja que contiene la tabla -->
  <div class="tabla-contenedor"><table>
    <!-- títulos de las columnas de la tabla -->
    <thead><tr><th>Fecha</th><th>Usuario</th><th>RUT cuenta</th><th>Acción</th><th>Tabla</th><th>ID</th><th>Detalle</th><th>IP</th></tr></thead>
    <!-- cuerpo de la tabla donde van los registros -->
    <tbody>
    <?php // si no hay registros, muestra un mensaje en la tabla ?>
    <?php if (!$filas): ?><tr><td colspan="8">No hay registros para el filtro indicado.</td></tr><?php endif; ?>
    <?php // repite una fila por cada registro encontrado ?>
    <?php foreach ($filas as $a): ?>
      <tr>
        <?php // fecha y hora del registro ?>
        <td><?=e($a['fecha_registro'])?></td>
        <?php // usuario que hizo la acción, o Sistema si fue automática ?>
        <td><?=e($a['username'] ?? 'Sistema')?></td>
        <?php // RUT de la cuenta del usuario ?>
        <td><?=e($a['rut_cuenta'] ?? '')?></td>
        <?php // acción realizada ?>
        <td><?=e($a['accion'])?></td>
        <?php // tabla donde se hizo el cambio ?>
        <td><?=e($a['tabla_afectada'])?></td>
        <?php // número del registro que cambió ?>
        <td><?=e($a['registro_id'])?></td>
        <?php // texto con el detalle de la acción ?>
        <td><?=e($a['detalle'])?></td>
        <?php // dirección desde donde se hizo la acción ?>
        <td><?=e($a['ip'])?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>

  <?php // TÍTULO: NAVEGACIÓN ENTRE PÁGINAS ?>
  <?php // solo se muestra si hay más de una página ?>
  <?php if ($totalPaginas > 1): ?>
  <!-- barra para pasar de una página a otra -->
  <nav class="paginador">
    <?php // si no está en la primera página, muestra el botón para retroceder ?>
    <?php if ($pagina > 1): ?><a class="btn btn-limpiar" href="<?=e($urlPagina($pagina - 1))?>">« Anterior</a><?php endif; ?>
    <!-- texto que indica la página actual y el total de registros -->
    <span class="paginador-info">Página <?=$pagina?> de <?=$totalPaginas?> (<?=$totalFilas?> registros)</span>
    <?php // si no está en la última página, muestra el botón para avanzar ?>
    <?php if ($pagina < $totalPaginas): ?><a class="btn btn-limpiar" href="<?=e($urlPagina($pagina + 1))?>">Siguiente »</a><?php endif; ?>
  </nav>
  <?php endif; ?>
<!-- cierre de la tarjeta y del contenedor principal -->
</section></main>
<?php endif; ?>
<!-- cierre del cuerpo y de la página -->
</body></html>
<?php /* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- FIN Jerez auditoria.php --------------------------------------
   ------------------------------------------------------------------------------------------------------------- */ ?>
<?php /*
Sitio Web Creado por Jerez.
Direccion: Miraflorez #1280
Quintero - Chile
jaime.jerez1978@gmail.com
https://www.
Creado, Programado y Diseñado por Jerez.
JJA
*/ ?>
