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
   ------------------------------------- INICIO Jerez guardar_operador.php ----------------------------
   ------------------------------------------------------------------------------------------------------------- */


// TÍTULO: INICIO DEL PROGRAMA

// Le pide a PHP que sea estricto y no acepte datos mal escritos o mezclados
declare(strict_types=1);
// Trae el archivo que sabe conectarse a la base de datos
require_once __DIR__.'/conexion.php';
// Trae las herramientas de seguridad (control de ingreso, llaves, límites de intentos, bitácora)
require_once __DIR__.'/security.php';
// Trae las herramientas para revisar y arreglar el RUT (el número de identificación chileno)
require_once __DIR__.'/rut_helper.php';
// Trae la función que calcula el consumo (rendimiento) de la carga
require_once __DIR__.'/consumo_helper.php';
require_once __DIR__.'/horometro_helper.php';
require_once __DIR__.'/guardado_helper.php';


// TÍTULO: REVISAR QUE LA PERSONA TENGA PERMISO

// Revisa que la persona haya iniciado sesión; si no, no la deja pasar
requireLogin();
// Acepta solo datos enviados desde un formulario; si llegan por otra vía, los detiene
requirePost();
// Comprueba la llave secreta del formulario, que demuestra que el envío viene de nuestra propia página
verifyCsrf();
// Modo sin conexión: identificador único de la carga (lo genera el equipo). Si ya existe, es un reenvío y se responde "ok" sin duplicar.
$uuidCliente = uuidCliente();
if (registroYaGuardado($conn, 'operador', $uuidCliente, (int)$_SESSION['usuario_id'])) { finGuardado('ok', '', ['duplicado' => true]); }
// Guarda el número del usuario que está registrando este operador
$usuarioId=(int)$_SESSION['usuario_id'];

// Si esta persona ya guardó demasiados registros en poco tiempo (más de 40 en 5 minutos)...
if (!rateLimitFile('guardar_operador', (string)$usuarioId, 300, 40)) {
    // ...la devuelve al panel con un aviso de "demasiados intentos Y detiene todo"
    finGuardado('error','demasiados_intentos');
}


// TÍTULO: RECIBIR LOS DATOS DEL FORMULARIO

// Toma los nombres del operador y le quita los espacios de sobra
$nombres=trim((string)($_POST['nombres']??''));
// Toma el apellido paterno y le quita los espacios de sobra
$ap=trim((string)($_POST['apellidoPaterno']??''));
// Toma el apellido materno y le quita los espacios de sobra
$am=trim((string)($_POST['apellidoMaterno']??''));
// Toma el RUT y lo deja escrito siempre con el mismo formato
$rut=normalizeRut((string)($_POST['rut']??''));
// Toma el turno de trabajo (A, B, C o D)
$turno=trim((string)($_POST['turno']??''));
// Toma el lugar donde se hizo la carga
$ubicacion=trim((string)($_POST['ubicacion']??''));
// Toma el código de la máquina
$codigo=trim((string)($_POST['codigoMaquinaria']??''));
// Toma la patente del vehículo y la pasa a mayúsculas
$patente=strtoupper(trim((string)($_POST['patente']??'')));
// Toma el nombre del equipo
$equipo=trim((string)($_POST['equipo']??''));
// Toma las horas de uso de la máquina (horómetro)
$horometro=trim((string)($_POST['horometro']??''));
// Toma los kilómetros recorridos (kilometraje)
$kilometro=trim((string)($_POST['kilometro']??''));
// Toma los litros de combustible cargados
$litros=trim((string)($_POST['litros']??''));
// Toma cualquier comentario o nota adicional
$observacion=trim((string)($_POST['observacion']??''));
// Anota la fecha y hora exacta de este momento como fecha del registro
[$fechaRegistro,$detalleOffline]=fechaRegistroCliente();


// TÍTULO: REVISAR QUE TODOS LOS DATOS SEAN CORRECTOS

// Si alguno de los datos no cumple las reglas, devuelve a la persona al panel con un aviso de error
if (
    // Los nombres deben ser un texto válido de hasta 100 letras
    !validText($nombres,100)||
    // El apellido paterno debe ser un texto válido de hasta 100 letras
    !validText($ap,100)||
    // El apellido materno debe ser un texto válido de hasta 100 letras
    !validText($am,100)||
    // El RUT debe ser un texto válido de hasta 12 caracteres
    !validText($rut,12)||
    // La ubicación debe ser un texto válido de hasta 100 caracteres
    !validText($ubicacion,100)||
    // El código de la máquina debe ser un texto válido de hasta 50 caracteres
    !validText($codigo,50)||
    // La patente debe ser un texto válido de hasta 10 caracteres
    !validText($patente,10)||
    // El equipo debe ser un texto válido de hasta 100 caracteres
    !validText($equipo,100)||
    // El turno solo puede ser A, B, C o D
    !in_array($turno,['A','B','C','D'],true)||
    // Las horas de la máquina deben ser un número entre 0 y 100.000.000
    !validDecimal($horometro,0,100000000)||
    // Los kilómetros deben ser un número entre 0 y 100.000.000
    !validDecimal($kilometro,0,100000000)||
    // Los litros deben ser un número mayor a 0 y hasta 1.000.000
    !validDecimal($litros,0.01,1000000)||
    // La observación no puede pasar de 2000 letras
    mb_strlen($observacion)>2000||
    // El RUT debe estar escrito con la forma correcta
    !validarFormatoRut($rut)||
    // El RUT debe ser real (su dígito verificador debe calzar)
    !validarRut($rut)||
    // La ubicación debe ser una posición GPS válida
    !validGps($ubicacion)||
    // La patente solo puede tener letras, números, espacios, puntos o guiones (entre 2 y 10 caracteres)
    !preg_match('/^[A-Z0-9 .-]{2,10}$/', $patente)
) {
    // Devuelve a la persona al panel con el aviso de que hay campos mal llenados Y detiene todo
    finGuardado('error','campos');
}


// No permite guardar una lectura inferior a la última registrada para esta maquinaria.
if (!horometroNoRetrocede($conn, $codigo, (float)$horometro)) {
    finGuardado('error','horometro_menor');
}

// TÍTULO: GUARDAR EL REGISTRO EN LA BASE DE DATOS

// TITULO NOMBRE: EL RUT DEL OPERADOR ES EL DE LA PERSONA QUE RECIBE EL COMBUSTIBLE (puede ser distinto al de la cuenta que registra)
// Si ese RUT pertenece a una cuenta con nombre registrado, el nombre ingresado debe ser ese; no se acepta otro nombre con el mismo RUT.
$nombreOk = verificarNombreDeRut($conn, $rut, $nombres, $ap, $am);
// Si el nombre no coincide con el registrado para ese RUT...
if (!$nombreOk['ok']) {
    // ...deja el intento anotado en la bitácora
    audit($conn, $usuarioId, 'NOMBRE_NO_COINCIDE', 'operador', null, 'Motivo: '.$nombreOk['codigo'].'; RUT ingresado '.$rut);
    // ...devuelve a la persona al panel con el aviso correspondiente Y detiene todo
    finGuardado('error',$nombreOk['codigo']);
}

// Inicia un "todo o nada": o se guarda todo bien, o no se guarda nada
$conn->begin_transaction();
// Intenta guardar los datos
try {
    // Calcula el consumo (L/h o L/100 km) comparando con la carga anterior de la misma máquina y prepara la orden para insertar el nuevo registro del operador (con espacios "?" que se llenan después, para que nadie pueda colar órdenes dañinas)
    $consumo=calcularConsumoOperador($conn,$codigo,(float)$horometro,(float)$kilometro,(float)$litros,$fechaRegistro); $conUuid=($uuidCliente!=='' && tablaTieneUuid($conn,'operador')); $stmt=$conn->prepare('INSERT INTO operador (nombres,apellido_paterno,apellido_materno,rut,turno,ubicacion,codigo_maquinaria,patente,equipo,horometro,kilometro,litros,consumo,observacion,usuario_id,fecha_registro'.($conUuid?',uuid_cliente':'').') VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?'.($conUuid?',?':'').')');
    // Rellena los espacios vacíos con los datos recibidos, ejecuta la orden y guarda el número del registro recién creado
    if($conUuid){ $stmt->bind_param('sssssssssddddsiss',$nombres,$ap,$am,$rut,$turno,$ubicacion,$codigo,$patente,$equipo,$horometro,$kilometro,$litros,$consumo,$observacion,$usuarioId,$fechaRegistro,$uuidCliente); } else { $stmt->bind_param('sssssssssddddsis',$nombres,$ap,$am,$rut,$turno,$ubicacion,$codigo,$patente,$equipo,$horometro,$kilometro,$litros,$consumo,$observacion,$usuarioId,$fechaRegistro); } $stmt->execute(); $id=$stmt->insert_id;
    // Anota en la bitácora quién guardó el registro, confirma el guardado definitivo, devuelve a la persona al panel con aviso de éxito y detiene todo
    audit($conn,$usuarioId,'REGISTRO_OPERADOR','operador',(int)$id,'RUT '.$rut.'; litros '.$litros.'; consumo '.number_format($consumo,2,'.','').$detalleOffline);  $conn->commit(); finGuardado('ok');
// Si algo falló al guardar
} catch(Throwable $e){
    // deshace todo lo que se alcanzó a guardar, anota el problema en el registro interno del servidor, devuelve a la persona al panel con aviso de error y detiene todo
    $conn->rollback(); if (esErrorDuplicado($e)) { finGuardado('ok', '', ['duplicado' => true]); } error_log($e->getMessage()); finGuardado('error','guardar');
}



/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- FIN Jerez guardar_operador.php ------------------------------
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