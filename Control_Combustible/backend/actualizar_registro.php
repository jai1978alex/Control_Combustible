<!--
Sitio Web Creado por Jerez.
Direccion: Miraflorez #1280
Quintero - Chile
jaime.jerez1978@gmail.com
https://www.
Creado, Programado y Diseñado por Jerez.
JJA 
-->

<!-- -------------------------------------------------------------------------------------------------------------
   ------------------------------------- INICIO Jerez actualizar_registro.php ------------------------------------
   ------------------------------------------------------------------------------------------------------------- -->


<?php

/* TÍTULO: EDITAR REGISTRO DE OPERADOR */
    
  /* Este archivo permite modificar los datos de un registro.
  * Solo puede ser usado por un administrador.
  * También revisa que los datos sean correctos y guarda
  * un registro de la modificación realizada. */
  declare(strict_types=1);

/* TÍTULO: CARGAR ARCHIVOS NECESARIOS */
  
  /* conexion.php    = conecta con la base de datos. security.php    = controla la seguridad.
    rut_helper.php  = ayuda a revisar y ordenar el RUT.*/
  require_once __DIR__ . '/conexion.php';
  require_once __DIR__ . '/security.php';
  require_once __DIR__ . '/rut_helper.php';

/* TÍTULO: REVISAR SEGURIDAD  */

  /* requireAdmin() = permite entrar solo al administrador. requirePost() = permite recibir datos enviados 
  por POST. verifyCsrf()   = revisa que el envío sea seguro.  */
  requireAdmin();
  requirePost();
  verifyCsrf();

/* TÍTULO: RECIBIR DATOS PRINCIPALES */
  
  /* tipo = indica si el registro es de un cargador o de un operador. id = número del registro que se quiere editar. */
  $tipo = (string) ($_POST['tipo'] ?? '');
  $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

/* TÍTULO: NOMBRE DE LAS TABLAS */
  
  /* Aquí se indica qué tabla corresponde a cada tipo de registro. */
  $tablas = [
      'cargador' => 'operador_cargador',
      'operador' => 'operador'
  ];

/* TÍTULO: REVISAR TIPO E ID */
    
  /* Si no existe un ID válido o el tipo no corresponde, se vuelve a la página del administrador. */
  if (!$id || !isset($tablas[$tipo])) {
      header(
          'Location: ../html/login/seguridad_login/superadmin.php?error=editar_registro'
      );
      exit;
  }

/* TÍTULO: RECIBIR LOS DATOS DEL FORMULARIO  */

  /* trim() quita espacios innecesarios. strtoupper() convierte algunos datos a mayúsculas.  */
  $nombres = trim((string) ($_POST['nombres'] ?? ''));

  $ap = trim((string) ($_POST['apellidoPaterno'] ?? ''));

  $am = trim((string) ($_POST['apellidoMaterno'] ?? ''));

  $rut = normalizeRut((string) ($_POST['rut'] ?? ''));

  $turno = strtoupper(
      trim((string) ($_POST['turno'] ?? ''))
  );

  $ubicacion = trim((string) ($_POST['ubicacion'] ?? ''));

  $patente = strtoupper(
      trim((string) ($_POST['patente'] ?? ''))
  );

  $horometro = trim((string) ($_POST['horometro'] ?? ''));

  $litros = trim((string) ($_POST['litros'] ?? ''));

  $observacion = trim((string) ($_POST['observacion'] ?? ''));

  $codigo = trim((string) ($_POST['codigo'] ?? ''));

  $codigoMaquinaria = trim(
      (string) ($_POST['codigoMaquinaria'] ?? '')
  );

  $equipo = trim((string) ($_POST['equipo'] ?? ''));

  $kilometro = trim((string) ($_POST['kilometro'] ?? ''));

  $remananente = trim(
      (string) ($_POST['remanente'] ?? '0')
  );

/* TÍTULO: REVISAR QUE LOS DATOS SEAN CORRECTOS */
  
  /* Aquí se revisan los nombres, RUT, turno, ubicación, patente, horómetro, litros y observaciones.
      También se revisa que el RUT sea válido y que la patente tenga un formato permitido. */
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

/* TÍTULO: SI HAY UN DATO INCORRECTO */

  /* Se vuelve al formulario para que los datos puedan ser revisados nuevamente. */
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

/* TÍTULO: INICIAR CAMBIO EN LA BASE DE DATOS */
  
  /* La transacción permite realizar el cambio de forma segura. Si algo falla, los cambios se pueden cancelar. */
  $conn->begin_transaction();

  /* saber qué tabla se va a modificar */
  try {
      $tabla = $tablas[$tipo];

/* TÍTULO: BUSCAR EL REGISTRO ACTUAL */
      
      /* Se obtiene el RUT y los litros que tenía antes de realizar el cambio.  */
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

      /*  revisar si existe el registro */
      if (!$actual) {
          throw new RuntimeException('Registro no encontrado');
      }

      /* si el registro es de un cargador */
      if ($tipo === 'cargador') {

          /* revvisar codigo y remanente */
          if (
              !validText($codigo, 50)
              || !validDecimal($remananente, 0, 100000000)
          ) {
              throw new RuntimeException('Campos inválidos');
          }


/* TÍTULO: ACTUALIZAR DATOS DEL CARGADOR */
            
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

          /* convertir los valores numéricos */
          $h = (float) $horometro;
          $l = (float) $litros;
          $r = (float) $remananente;

          /* enviar datos a la base de datos */
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

          /* indicar tabla para el registro de seguridad */
          $tablaAudit = 'operador_cargador';

      /* si el registro es de un cargador */
      } else {

          /* revisar datos propios del operador */ 
          if (
              !validText($codigoMaquinaria, 50)
              || !validText($equipo, 100)
              || !validDecimal($kilometro, 0, 100000000)
          ) {
              throw new RuntimeException('Campos inválidos');
          }

/* TÍTULO: ACTUALIZAR DATOS DEL OPERADOR */ 
            
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


          /* convertir los valores numéricos */
          $h = (float) $horometro;
          $km = (float) $kilometro;
          $l = (float) $litros;


/* TÍTULO: ENVIAR LOS DATOS A LA BASE DE DATOS */
            
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

          /* indicar tabla para el registro de seguridad */
          $tablaAudit = 'operador';
      }

/* TÍTULO: VOLVER AL PANEL DEL ADMINISTRADOR */
      
      /* Se guarda quién hizo el cambio y qué datos fueron modificados. */
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


      /* confirmar los cambios  */     
      $conn->commit();

/* TÍTULO: VOLVER AL PANEL DEL ADMINISTRADOR */
      
      /* Se informa que el registro fue editado correctamente. */
      header(
          'Location: ../html/login/seguridad_login/superadmin.php?success=registro_editado'
      );

      exit;


  } catch (Throwable $e) {

/* TÍTULO: SI OCURRE UN ERROR */
      
      /* Se cancelan los cambios realizados. El error también se guarda para poder revisarlo. */
      $conn->rollback();

      error_log(
          'Error actualizando registro: '
          . $e->getMessage()
      );

/* TÍTULO: VOLVER AL FORMULARIO CON MENSAJE DE ERROR */

      header(
          'Location: ../html/login/seguridad_login/editar_registro.php?tipo='
          . urlencode($tipo)
          . '&id='
          . $id
          . '&error=guardar'
      );

      exit;
  }

?>

<!-- -------------------------------------------------------------------------------------------------------------
   -------------------------------------- FIN Jerez actualizar_registro.php --------------------------------------
   ------------------------------------------------------------------------------------------------------------- -->

<!-- 
Sitio Web Creado por Jerez.
Direccion: Miraflorez #1280
Quintero - Chile
jaime.jerez1978@gmail.com
https://www.
Creado, Programado y Diseñado por Jerez.
JJA
--