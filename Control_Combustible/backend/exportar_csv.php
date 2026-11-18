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
   ------------------------------------- INICIO Jerez exportar_csv.php ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

// TITULO EXPORTAR REGISTROS A CSV

// Este archivo permite a un administrador descargar los registros de combustible en un archivo CSV.
// Se puede exportar el tipo cargador u operador y, opcionalmente, filtrar por RUT.

// Activa el modo estricto de tipos para todo este archivo
declare(strict_types=1);


// TITULO CARGAR ARCHIVOS NECESARIOS

// conexion.php = conecta con la base de datos
// security.php = funciones de seguridad, sesion y auditoria

// Carga la conexion a la base de datos
require_once __DIR__ . '/conexion.php';

// Carga las funciones de seguridad
require_once __DIR__ . '/security.php';

// Permite el paso solo a administradores
requireAdmin();


// TITULO RECIBIR DATOS DE LA BUSQUEDA

// tipo = indica si se exporta cargador u operador
// rut  = texto opcional para filtrar por RUT

// Lee el tipo desde la URL y lo convierte a texto (vacio si no llega)
$tipo = (string)($_GET['tipo'] ?? '');

// Lee el RUT desde la URL, lo convierte a texto y quita espacios sobrantes
$rutBusqueda = trim((string)($_GET['rut'] ?? ''));


// TITULO REVISAR EL TIPO DE EXPORTACION

// Entra al bloque si el tipo no es ni cargador ni operador
if (!in_array($tipo, ['cargador', 'operador'], true)) {

    http_response_code(400);

    exit('Tipo de exportación no válido.');
}


// TITULO ARMAR LA CONSULTA SEGUN EL TIPO

// Entra al bloque si se exportan registros de cargadores
if ($tipo === 'cargador') {

    $sql = 'SELECT oc.nombres, oc.apellido_paterno, oc.apellido_materno, oc.rut, oc.turno, oc.codigo,
                   oc.ubicacion, oc.patente, oc.horometro, oc.litros, oc.remanente, oc.observacion,
                   u.username, oc.fecha_registro
            FROM operador_cargador oc LEFT JOIN usuarios u ON oc.usuario_id = u.id WHERE oc.eliminado_at IS NULL';

    $encabezado = ['Nombres','Apellido Paterno','Apellido Materno','RUT','Turno','Código','Ubicación','Patente','Horómetro','Litros','Remanente','Observación','Usuario','Fecha registro'];

// Si no es cargador, entonces es operador
} else {

    $sql = 'SELECT o.nombres, o.apellido_paterno, o.apellido_materno, o.rut, o.turno, o.codigo_maquinaria,
                   o.ubicacion, o.patente, o.equipo, o.horometro, o.kilometro, o.litros, o.consumo, o.observacion,
                   u.username, o.fecha_registro
            FROM operador o LEFT JOIN usuarios u ON o.usuario_id = u.id WHERE o.eliminado_at IS NULL';

    $encabezado = ['Nombres','Apellido Paterno','Apellido Materno','RUT','Turno','Código Maquinaria','Ubicación','Patente','Equipo','Horómetro','Kilómetro','Litros','Consumo','Observación','Usuario','Fecha registro'];
}


// TITULO FILTRO OPCIONAL POR RUT

// Entra al bloque si el administrador escribio un RUT para filtrar
if ($rutBusqueda !== '') {

    $sql .= ' AND ';

    $alias = $tipo === 'cargador' ? 'oc' : 'o';

    $sql .= " {$alias}.rut LIKE ?";
}

// Ordena los resultados del mas reciente al mas antiguo segun el tipo
$sql .= ($tipo === 'cargador' ? ' ORDER BY oc.fecha_registro DESC' : ' ORDER BY o.fecha_registro DESC');


// TITULO EJECUTAR LA CONSULTA

// Prepara la consulta para ejecutarla de forma segura
$stmt = $conn->prepare($sql);

// Entra al bloque si hay un RUT para filtrar
if ($rutBusqueda !== '') {

    $like = '%' . $rutBusqueda . '%';

    $stmt->bind_param('s', $like);
}

// Ejecuta la consulta
$stmt->execute();

// Obtiene el conjunto de resultados
$resultado = $stmt->get_result();


// TITULO GUARDAR REGISTRO DE AUDITORIA

// Deja constancia de que el administrador exporto registros y con que filtro
audit($conn, (int)$_SESSION['usuario_id'], 'EXPORTAR_CSV', $tipo === 'cargador' ? 'operador_cargador' : 'operador', null, $rutBusqueda !== '' ? 'rut='.$rutBusqueda : null);


// TITULO PREPARAR LA DESCARGA DEL ARCHIVO

// Arma el nombre del archivo con el tipo y la fecha y hora actual
$nombreArchivo = 'registros_' . $tipo . '_' . date('Ymd_His') . '.csv';

// Indica al navegador que el contenido es un CSV en UTF-8
header('Content-Type: text/csv; charset=utf-8');

// Indica al navegador que debe descargarlo con el nombre elegido
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');

// Evita que el navegador o proxys guarden copia del archivo
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// Compatibilidad con navegadores antiguos para no guardar copia
header('Pragma: no-cache');


// TITULO ESCRIBIR EL ENCABEZADO DEL CSV

// Abre la salida de PHP para escribir el archivo directamente en la descarga
$salida = fopen('php://output', 'w');

/* BOM UTF-8 para que Excel reconozca tildes y ñ correctamente. */
// Escribe la marca BOM al inicio del archivo
fwrite($salida, "\xEF\xBB\xBF");

// Escribe la fila de titulos usando punto y coma como separador
fputcsv($salida, $encabezado, ';');


// TITULO PROTECCION CONTRA INYECCION DE FORMULAS

/*
 * Mitigación de "CSV/Formula Injection": si un valor empieza con un
 * carácter que Excel/Sheets podría interpretar como inicio de fórmula
 * (=, +, -, @, tab, retorno de carro), se antepone un apóstrofo para que
 * se trate siempre como texto literal al abrir el archivo.
 */

// Define la funcion que limpia un valor para que sea seguro en el CSV
function csvSeguro(mixed $valor): string {

    $v = (string)($valor ?? '');

    if ($v !== '' && in_array($v[0], ['=', '+', '-', '@', "\t", "\r"], true)) {

        $v = "'" . $v;
    }

    return $v;
}


// TITULO ESCRIBIR LAS FILAS DEL CSV

// Recorre cada fila del resultado de la consulta
while ($fila = $resultado->fetch_assoc()) {

    $fila = array_map('csvSeguro', array_values($fila));

    fputcsv($salida, $fila, ';');
}


// TITULO CERRAR LA SALIDA

// Cierra la salida del archivo
fclose($salida);

// Termina la ejecucion del script
exit;

/* -------------------------------------------------------------------------------------------------------------
   ------------------------------------- FIN Jerez exportar_csv.php --------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

/* 
Sitio Web Creado por Jerez.
Direccion: Miraflorez #1280
Quintero - Chile
jaime.jerez1978@gmail.com
https://www.
Creado, Programado y Diseñado por Jerez.
JJA
*/
