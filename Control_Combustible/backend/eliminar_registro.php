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
   ------------------------------------- INICIO Jerez eliminar_registro.php ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

// TITULO ELIMINAR REGISTRO (ELIMINACION LOGICA)

// Este archivo permite a un administrador eliminar un registro de combustible.
// No borra la fila: solo la marca como eliminada (fecha y administrador) y deja constancia en auditoria.

// Activa el modo estricto de tipos para todo este archivo
declare(strict_types=1);


// TITULO CARGAR ARCHIVOS NECESARIOS

// conexion.php = conecta con la base de datos
// security.php = funciones de seguridad, sesion y auditoria

// Carga la conexion a la base de datos
require_once __DIR__.'/conexion.php';

// Carga las funciones de seguridad
require_once __DIR__.'/security.php';


// TITULO REVISAR SEGURIDAD

// requireAdmin() = permite continuar solo a un administrador
// requirePost()  = acepta unicamente datos enviados por POST
// verifyCsrf()   = comprueba que el envio del formulario sea legitimo

// Permite el paso solo a administradores
requireAdmin();

// Exige que la solicitud llegue por el metodo POST
requirePost();

// Valida el token CSRF del formulario
verifyCsrf();


// TITULO RECIBIR DATOS PRINCIPALES

// tipo = indica si el registro es de un cargador o de un operador
// id   = numero del registro que se quiere eliminar

// Lee el tipo enviado por POST y lo convierte a texto (vacio si no llega)
$tipo = (string)($_POST['tipo'] ?? '');

// Lee el id enviado por POST y lo valida como numero entero
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);


// TITULO NOMBRE DE LAS TABLAS

// Indica que tabla de la base de datos corresponde a cada tipo de registro

// Lista de tipos permitidos y su tabla correspondiente
$tablas = [
    'cargador' => 'operador_cargador',
    'operador' => 'operador'
];


// TITULO REVISAR TIPO E ID

// Si el id no es valido o el tipo no existe, se vuelve al panel con un error

// Entra al bloque si no hay id valido o si el tipo no esta en la lista
if (!$id || !isset($tablas[$tipo])) {

    header('Location: ../html/login/seguridad_login/superadmin.php?error=eliminar_registro');

    exit;
}


// TITULO INICIAR CAMBIO EN LA BASE DE DATOS

// Elige la tabla segun el tipo recibido
$tabla = $tablas[$tipo];

// Inicia una transaccion: si algo falla, los cambios se pueden cancelar
$conn->begin_transaction();

// Intenta realizar la eliminacion y captura cualquier error
try {

    // TITULO BUSCAR EL REGISTRO ACTUAL

    $stmt = $conn->prepare("SELECT rut, litros, eliminado_at FROM {$tabla} WHERE id=? LIMIT 1");

    $stmt->bind_param('i', $id);

    $stmt->execute();

    $registro = $stmt->get_result()->fetch_assoc();

    if (!$registro) {

        $conn->rollback();

        header('Location: ../html/login/seguridad_login/superadmin.php?error=eliminar_registro');

        exit;
    }

    if (!empty($registro['eliminado_at'])) {

        $conn->rollback();

        header('Location: ../html/login/seguridad_login/superadmin.php?error=eliminar_registro');

        exit;
    }


    // TITULO MARCAR EL REGISTRO COMO ELIMINADO

    $stmt = $conn->prepare("UPDATE {$tabla} SET eliminado_at=NOW(), eliminado_por=? WHERE id=? AND eliminado_at IS NULL");

    $adminId = (int)$_SESSION['usuario_id'];

    $stmt->bind_param('ii', $adminId, $id);

    $stmt->execute();


    // TITULO GUARDAR REGISTRO DE AUDITORIA

    audit(
        $conn,
        (int)$_SESSION['usuario_id'],
        'ELIMINAR_REGISTRO',
        $tabla,
        $id,
        'RUT '.$registro['rut'].'; litros '.$registro['litros'].'; eliminación lógica'
    );


    // TITULO CONFIRMAR Y VOLVER AL PANEL

    $conn->commit();

    header('Location: ../html/login/seguridad_login/superadmin.php?success=registro_eliminado');

    exit;

// Si ocurre cualquier error dentro del bloque anterior
} catch (Throwable $e) {

    // TITULO SI OCURRE UN ERROR

    $conn->rollback();

    error_log('Error eliminando registro: '.$e->getMessage());

    header('Location: ../html/login/seguridad_login/superadmin.php?error=eliminar_registro');

    exit;
}

/* -------------------------------------------------------------------------------------------------------------
   ------------------------------------- FIN Jerez eliminar_registro.php --------------------------------------
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
