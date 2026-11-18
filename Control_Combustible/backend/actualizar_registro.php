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
   ------------------------------------- INICIO Jerez actualizar_registro.php ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

// TITULO EDITAR REGISTRO DE OPERADOR

// Este archivo permite modificar los datos de un registro.
// Solo puede ser usado por un administrador.
// También revisa que los datos sean correctos y guarda
// un registro de la modificación realizada.

// Activa el modo estricto de tipos para todo este archivo
declare(strict_types=1);


// TITULO CARGAR ARCHIVOS NECESARIOS

// conexion.php   = conecta con la base de datos
// security.php   = controla la seguridad
// rut_helper.php = ayuda a revisar y ordenar el RUT

// Carga la conexion a la base de datos
require_once __DIR__ . '/conexion.php';

// Carga las funciones de seguridad
require_once __DIR__ . '/security.php';

// Carga las funciones que revisan y ordenan el RUT
require_once __DIR__ . '/rut_helper.php';


// TITULO REVISAR SEGURIDAD

// requireAdmin() = permite entrar solo al administrador
// requirePost()  = permite recibir datos enviados por POST
// verifyCsrf()   = revisa que el envío sea seguro

// Permite el paso solo a administradores
requireAdmin();

// Exige que la solicitud llegue por el metodo POST
requirePost();

// Valida el token CSRF del formulario
verifyCsrf();


// TITULO RECIBIR DATOS PRINCIPALES

// tipo = indica si el registro es de un cargador o de un operador
// id   = número del registro que se quiere editar

// Lee el tipo enviado por POST y lo convierte a texto (vacio si no llega)
$tipo = (string) ($_POST['tipo'] ?? '');

// Lee el id enviado por POST y lo valida como numero entero
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);


// TITULO NOMBRE DE LAS TABLAS

// Aquí se indica qué tabla corresponde a cada tipo de registro

// Lista de tipos permitidos y su tabla correspondiente
$tablas = [
    'cargador' => 'operador_cargador',
    'operador' => 'operador'
];


// TITULO REVISAR TIPO E ID

// Si no existe un ID válido o el tipo no corresponde, se vuelve a la página del administrador

// Entra al bloque si no hay id valido o si el tipo no esta en la lista
if (!$id || !isset($tablas[$tipo])) {

    header(
        'Location: ../html/login/seguridad_login/superadmin.php?error=editar_registro'
    );

    exit;
}


// TITULO RECIBIR LOS DATOS DEL FORMULARIO

// trim() quita espacios innecesarios
// strtoupper() convierte algunos datos a mayúsculas

// Nombres: se leen como texto y se quitan los espacios sobrantes
$nombres = trim((string) ($_POST['nombres'] ?? ''));

// Apellido paterno: se lee como texto y se quitan los espacios sobrantes
$ap = trim((string) ($_POST['apellidoPaterno'] ?? ''));

// Apellido materno: se lee como texto y se quitan los espacios sobrantes
$am = trim((string) ($_POST['apellidoMaterno'] ?? ''));

// RUT: se lee como texto y se ordena con el formato estandar
$rut = normalizeRut((string) ($_POST['rut'] ?? ''));

// Turno: se lee como texto, se quitan espacios y se pasa a mayusculas
$turno = strtoupper(
    trim((string) ($_POST['turno'] ?? ''))
);

// Ubicacion: se lee como texto y se quitan los espacios sobrantes
$ubicacion = trim((string) ($_POST['ubicacion'] ?? ''));

// Patente: se lee como texto, se quitan espacios y se pasa a mayusculas
$patente = strtoupper(
    trim((string) ($_POST['patente'] ?? ''))
);

// Horometro: se lee como texto y se quitan los espacios sobrantes
$horometro = trim((string) ($_POST['horometro'] ?? ''));

// Litros: se lee como texto y se quitan los espacios sobrantes
$litros = trim((string) ($_POST['litros'] ?? ''));

// Observacion: se lee como texto y se quitan los espacios sobrantes
$observacion = trim((string) ($_POST['observacion'] ?? ''));

// Codigo (solo cargador): se lee como texto y se quitan los espacios sobrantes
$codigo = trim((string) ($_POST['codigo'] ?? ''));

// Codigo de maquinaria (solo operador): se lee como texto y se quitan los espacios sobrantes
$codigoMaquinaria = trim(
    (string) ($_POST['codigoMaquinaria'] ?? '')
);

// Equipo (solo operador): se lee como texto y se quitan los espacios sobrantes
$equipo = trim((string) ($_POST['equipo'] ?? ''));

// Kilometro (solo operador): se lee como texto y se quitan los espacios sobrantes
$kilometro = trim((string) ($_POST['kilometro'] ?? ''));

// Remanente (solo cargador): se lee como texto (por defecto 0) y se quitan los espacios sobrantes
$remananente = trim(
    (string) ($_POST['remanente'] ?? '0')
);


// TITULO REVISAR QUE LOS DATOS SEAN CORRECTOS

// Aquí se revisan los nombres, RUT, turno, ubicación, patente, horómetro, litros y observaciones
// También se revisa que el RUT sea válido y que la patente tenga un formato permitido

// Guarda true solo si TODAS las condiciones siguientes se cumplen
$baseOk =
    validText($nombres, 100)
    && validText($ap, 100)
    && validText($am, 100)
    && validText($rut, 12)
    && in_array($turno, ['A', 'B', 'C', 'D'], true)
    && validText($ubicacion, 100)
    && validText($patente, 10)
    && validDecimal($horometro, 0, 100000000)
    && validDecimal($litros, 0.01, 1000000)
    && mb_strlen($observacion) <= 2000
    && validarFormatoRut($rut)
    && validarRut($rut)
    && validGps($ubicacion)
    && preg_match(
        '/^[A-Z0-9 .-]{2,10}$/',
        $patente
    );


// TITULO SI HAY UN DATO INCORRECTO

// Se vuelve al formulario para que los datos puedan ser revisados nuevamente

// Entra al bloque si algun dato no es correcto
if (!$baseOk) {

    header(
        'Location: ../html/login/seguridad_login/editar_registro.php?tipo='
        . urlencode($tipo)
        . '&id='
        . $id
        . '&error=campos'
    );

    exit;
}


// TITULO INICIAR CAMBIO EN LA BASE DE DATOS

// La transacción permite realizar el cambio de forma segura
// Si algo falla, los cambios se pueden cancelar

// Inicia la transaccion
$conn->begin_transaction();

// Intenta realizar la edicion y captura cualquier error
try {

    $tabla = $tablas[$tipo];


    // TITULO BUSCAR EL REGISTRO ACTUAL

    // Se obtiene el RUT y los litros que tenía antes de realizar el cambio

    $st = $conn->prepare(
        "SELECT rut,litros
          FROM {$tabla}
          WHERE id=?
          AND eliminado_at IS NULL
          LIMIT 1"
    );

    $st->bind_param('i', $id);

    $st->execute();

    $actual = $st->get_result()->fetch_assoc();

    if (!$actual) {

        throw new RuntimeException('Registro no encontrado');
    }


    // TITULO EDICION SEGUN EL TIPO DE REGISTRO

    if ($tipo === 'cargador') {

        if (
            !validText($codigo, 50)
            || !validDecimal($remananente, 0, 100000000)
        ) {

            throw new RuntimeException('Campos inválidos');
        }


        // TITULO ACTUALIZAR DATOS DEL CARGADOR

        $stmt = $conn->prepare(
            'UPDATE operador_cargador
              SET nombres=?,
                  apellido_paterno=?,
                  apellido_materno=?,
                  rut=?,
                  turno=?,
                  codigo=?,
                  ubicacion=?,
                  patente=?,
                  horometro=?,
                  litros=?,
                  remanente=?,
                  observacion=?
              WHERE id=?
              AND eliminado_at IS NULL'
        );

        $h = (float) $horometro;

        $l = (float) $litros;

        $r = (float) $remananente;

        $stmt->bind_param(
            'ssssssssdddsi',
            $nombres,
            $ap,
            $am,
            $rut,
            $turno,
            $codigo,
            $ubicacion,
            $patente,
            $h,
            $l,
            $r,
            $observacion,
            $id
        );

        $stmt->execute();

        $tablaAudit = 'operador_cargador';

    // Si el registro no es de un cargador, entonces es de un operador
    } else {

        if (
            !validText($codigoMaquinaria, 50)
            || !validText($equipo, 100)
            || !validDecimal($kilometro, 0, 100000000)
        ) {

            throw new RuntimeException('Campos inválidos');
        }


        // TITULO ACTUALIZAR DATOS DEL OPERADOR

        $stmt = $conn->prepare(
            'UPDATE operador
              SET nombres=?,
                  apellido_paterno=?,
                  apellido_materno=?,
                  rut=?,
                  turno=?,
                  ubicacion=?,
                  codigo_maquinaria=?,
                  patente=?,
                  equipo=?,
                  horometro=?,
                  kilometro=?,
                  litros=?,
                  observacion=?
              WHERE id=?
              AND eliminado_at IS NULL'
        );

        $h = (float) $horometro;

        $km = (float) $kilometro;

        $l = (float) $litros;


        // TITULO ENVIAR LOS DATOS A LA BASE DE DATOS

        $stmt->bind_param(
            'sssssssssdddsi',
            $nombres,
            $ap,
            $am,
            $rut,
            $turno,
            $ubicacion,
            $codigoMaquinaria,
            $patente,
            $equipo,
            $h,
            $km,
            $l,
            $observacion,
            $id
        );

        $stmt->execute();

        $tablaAudit = 'operador';
    }


    // TITULO GUARDAR REGISTRO DE AUDITORIA

    // Se guarda quién hizo el cambio y qué datos fueron modificados

    audit(
        $conn,
        (int) $_SESSION['usuario_id'],
        'EDITAR_REGISTRO',
        $tablaAudit,
        $id,
        'RUT anterior '
        . $actual['rut']
        . ' -> '
        . $rut
        . '; litros anteriores '
        . $actual['litros']
        . ' -> '
        . $litros
    );


    // TITULO CONFIRMAR LOS CAMBIOS

    $conn->commit();


    // TITULO VOLVER AL PANEL DEL ADMINISTRADOR

    // Se informa que el registro fue editado correctamente

    header(
        'Location: ../html/login/seguridad_login/superadmin.php?success=registro_editado'
    );

    exit;


// Si ocurre cualquier error dentro del bloque anterior
} catch (Throwable $e) {

    // TITULO SI OCURRE UN ERROR

    // Se cancelan los cambios realizados
    // El error también se guarda para poder revisarlo

    $conn->rollback();

    error_log(
        'Error actualizando registro: '
        . $e->getMessage()
    );


    // TITULO VOLVER AL FORMULARIO CON MENSAJE DE ERROR

    header(
        'Location: ../html/login/seguridad_login/editar_registro.php?tipo='
        . urlencode($tipo)
        . '&id='
        . $id
        . '&error=guardar'
    );

    exit;
}

/* -------------------------------------------------------------------------------------------------------------
   ------------------------------------- FIN Jerez actualizar_registro.php --------------------------------------
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

?>
