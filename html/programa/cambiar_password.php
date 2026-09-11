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
   ------------------------------------- INICIO Jerez cambiar_password.php ------------------------------------
   ------------------------------------------------------------------------------------------------------------- -->



<?php

    /* TÍTULO: INICIO Y CONFIGURACIÓN DEL ARCHIVO */

    // Carga el archivo que permite conectarse a la base de datos.
    require_once __DIR__ . '/../../backend/conexion.php';

    // Carga el archivo que contiene las funciones de seguridad.
    require_once __DIR__ . '/../../backend/security.php';

    // Comprueba que el usuario haya iniciado sesión.
    requireLogin();

    /* TÍTULO: VARIABLE PARA LOS MENSAJES DE ERROR */

    // Crea una variable para guardar mensajes de error.
    $error = '';

    // Comprueba si el usuario está obligado a cambiar su contraseña.
    $forzado = !empty($_SESSION['debe_cambiar_password']);

    /* TÍTULO: COMPROBAR ENVÍO DEL FORMULARIO */

    // Comprueba si el formulario fue enviado mediante POST.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Comprueba que el código de seguridad del formulario sea correcto.
        verifyCsrf();

        /* TÍTULO: OBTENER DATOS DEL FORMULARIO */

        // Obtiene la contraseña actual escrita por el usuario.
        $actual = (string)($_POST['current_password'] ?? '');

        // Obtiene la nueva contraseña escrita por el usuario.
        $nueva = (string)($_POST['new_password'] ?? '');

        // Obtiene la confirmación de la nueva contraseña.
        $confirmar = (string)($_POST['confirm_password'] ?? '');

        // Obtiene el número del usuario que tiene la sesión iniciada.
        $uid = (int)($_SESSION['usuario_id'] ?? 0);

        /* TÍTULO: COMPROBAR USUARIO */

        // Comprueba que el número de usuario sea válido.
        if ($uid <= 0) {

            // Guarda un mensaje de error si la sesión no es válida.
            $error = 'La sesión del usuario no es válida. Inicia sesión nuevamente.';

        /* TÍTULO: COMPROBAR LA NUEVA CONTRASEÑA */

        // Comprueba que la nueva contraseña cumpla las reglas de seguridad.
        } elseif (!validPassword($nueva)) {

            // Guarda un mensaje de error si la contraseña no cumple los requisitos.
            $error = 'La nueva contraseña no cumple los requisitos de seguridad (mínimo 12 caracteres, mayúscula, minúscula, número y símbolo).';

        // Comprueba que la nueva contraseña y su confirmación sean iguales.
        } elseif ($nueva !== $confirmar) {

            // Guarda un mensaje de error si las contraseñas no coinciden.
            $error = 'La confirmación no coincide con la nueva contraseña.';

        // Si pasó todas las validaciones anteriores, continúa el proceso.
        } else {

            /* TÍTULO: BUSCAR LA CONTRASEÑA ACTUAL */

            // Prepara una consulta para buscar la contraseña del usuario.
            $stmt = $conn->prepare(
                'SELECT password FROM usuarios WHERE id=? LIMIT 1'
            );

            // Envía el número del usuario a la consulta.
            $stmt->bind_param('i', $uid);

            // Ejecuta la consulta.
            $stmt->execute();

            // Obtiene los datos del usuario encontrado.
            $row = $stmt->get_result()->fetch_assoc();

            // Cierra la consulta.
            $stmt->close();

            /* TÍTULO: COMPROBAR LA CONTRASEÑA ACTUAL */

            // Comprueba que exista el usuario y que la contraseña actual sea correcta.
            if (!$row || !password_verify($actual, (string)$row['password'])) {

                // Guarda un mensaje de error si la contraseña actual es incorrecta.
                $error = 'La contraseña actual no es correcta.';

            // Comprueba que la nueva contraseña no sea igual a la anterior.
            } elseif (password_verify($nueva, (string)$row['password'])) {

                // Guarda un mensaje de error si la nueva contraseña es igual a la actual.
                $error = 'La nueva contraseña no puede ser igual a la actual.';

            // Si todo está correcto, continúa con el cambio de contraseña.
            } else {

                /* TÍTULO: CREAR LA NUEVA CONTRASEÑA */

                // Protege la nueva contraseña antes de guardarla.
                $hash = password_hash($nueva, PASSWORD_DEFAULT);

                // Comienza una operación para guardar los cambios de forma segura.
                $conn->begin_transaction();

                // Intenta ejecutar el proceso de actualización.
                try {

                    /* TÍTULO: ACTUALIZAR LA CONTRASEÑA */

                    // Prepara la consulta para actualizar la contraseña.
                    $u = $conn->prepare(
                        'UPDATE usuarios
                        SET password=?, debe_cambiar_password=0
                        WHERE id=?'
                    );

                    // Envía la nueva contraseña protegida y el número del usuario.
                    $u->bind_param('si', $hash, $uid);

                    // Ejecuta la actualización.
                    $u->execute();

                    // Verifica que realmente se haya encontrado el usuario.
                    if ($u->affected_rows < 1) {

                        // Puede ocurrir que el usuario exista pero MySQL no marque cambios.
                        // Por seguridad comprobamos nuevamente que exista.
                        $check = $conn->prepare(
                            'SELECT id FROM usuarios WHERE id=? LIMIT 1'
                        );

                        // Envía el número del usuario a la consulta de verificación.
                        $check->bind_param('i', $uid);

                        // Ejecuta la consulta de verificación.
                        $check->execute();

                        // Obtiene el resultado de la verificación.
                        $exists = $check->get_result()->fetch_assoc();

                        // Cierra la consulta de verificación.
                        $check->close();

                        // Si el usuario realmente no existe, lanza un error.
                        if (!$exists) {

                            // Lanza una excepción indicando que no se encontró el usuario.
                            throw new RuntimeException(
                                'No se encontró el usuario al actualizar la contraseña.'
                            );
                        }
                    }

                    // Cierra la consulta de actualización.
                    $u->close();

                    /* TÍTULO: OBTENER LA FECHA ACTUALIZADA */

                    // Prepara una consulta para obtener la fecha de actualización.
                    $r = $conn->prepare(
                        'SELECT updated_at FROM usuarios WHERE id=? LIMIT 1'
                    );

                    // Envía el número del usuario.
                    $r->bind_param('i', $uid);

                    // Ejecuta la consulta.
                    $r->execute();

                    // Obtiene los datos actualizados.
                    $fresh = $r->get_result()->fetch_assoc();

                    // Cierra la consulta.
                    $r->close();

                    /* TÍTULO: GUARDAR EL CAMBIO EN EL REGISTRO */

                    // Guarda un registro indicando que el usuario cambió su contraseña.
                    audit(
                        $conn,
                        $uid,
                        'CAMBIO_PASSWORD_PROPIO',
                        'usuarios',
                        $uid
                    );

                    // Confirma y guarda todos los cambios realizados.
                    $conn->commit();

                    /* TÍTULO: ACTUALIZAR LA SESIÓN */

                    // Renueva la sesión después del cambio de contraseña.
                    session_regenerate_id(true);

                    // Guarda la fecha actualizada del usuario.
                    $_SESSION['user_updated_at'] =
                        (string)($fresh['updated_at'] ?? '');

                    // Indica que el usuario ya no necesita cambiar su contraseña.
                    $_SESSION['debe_cambiar_password'] = 0;

                    // Crea un nuevo código de seguridad para la sesión.
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                    /* TÍTULO: DEFINIR LA PÁGINA DE DESTINO */

                    // Define a qué página será enviado el usuario después del cambio.
                    if (($_SESSION['rol'] ?? '') === 'admin') {

                        // Arma la ruta de destino para el administrador.
                        $destino = appBasePath()
                            . '/html/login/seguridad_login/superadmin.php'
                            . '?success=password_actualizada';

                    } else {

                        // Arma la ruta de destino para un usuario normal.
                        $destino = appBasePath()
                            . '/html/programa/panel.php'
                            . '?success=password_actualizada';
                    }

                    // Envía al usuario a la página correspondiente.
                    header('Location: ' . $destino);

                    // Detiene la ejecución.
                    exit;

                } catch (Throwable $e) {

                    /* TÍTULO: MANEJAR ERRORES AL CAMBIAR LA CONTRASEÑA */

                    // Deshace los cambios realizados si ocurrió un problema.
                    try {

                        // Ejecuta el rollback de la transacción.
                        $conn->rollback();

                    } catch (Throwable $rollbackError) {

                        // Evita que un error durante rollback oculte el error original.
                    }

                    // Guarda el detalle del error en el registro del sistema.
                    error_log(
                        'Error cambiando contraseña propia: '
                        . $e->getMessage()
                    );

                    // Muestra un mensaje simple para el usuario.
                    $error =
                        'No fue posible actualizar la contraseña. Intenta nuevamente.';
                }
            }
        }
    }

    /* TÍTULO: CREAR DATOS PARA EL FORMULARIO */

    // Obtiene el código de seguridad para usarlo en el formulario.
    $csrf = csrfToken();

    // Comprueba el tipo de usuario para definir la página de regreso.
    $volver = ($_SESSION['rol'] ?? '') === 'admin'
        ? '../login/seguridad_login/superadmin.php'
        : 'panel.php';



?>
<!DOCTYPE html>
<html lang="es">

    <head>

        <meta charset="UTF-8">

        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Cambiar Contraseña</title>

        <link
            rel="stylesheet"
            href="../../css/programa/crear_usuario.css"
        >

    </head>

    <body>

        <!-- Contenedor principal -->
        <div class="container">

            <!-- Tarjeta para cambiar contraseña -->
            <div class="card">

                <!-- Título de la página -->
                <h1>Cambiar Contraseña</h1>

                <!-- Aviso de cambio obligatorio -->
                <?php if ($forzado && $error === ''): ?>

                    <div class="error">
                        Un administrador reseteó tu contraseña.
                        Debes definir una nueva antes de continuar.
                    </div>

                <?php endif; ?>

                <!-- Mostrar mensaje de error -->
                <?php if ($error): ?>

                    <div class="error">
                        <?= e($error) ?>
                    </div>

                <?php endif; ?>

                <!-- FORMULARIO PARA CAMBIAR LA CONTRASEÑA -->

                <form method="POST" autocomplete="off">

                    <!-- Código de seguridad -->

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($csrf) ?>"
                    >

                    <!-- Contraseña actual -->

                    <div class="form-group">

                        <label for="currentPassword">
                            Contraseña actual
                        </label>

                        <input
                            type="password"
                            id="currentPassword"
                            name="current_password"
                            maxlength="255"
                            required
                            autocomplete="current-password"
                        >

                    </div>

                    <!-- Nueva contraseña -->

                    <div class="form-group">

                        <label for="newPassword">
                            Nueva contraseña
                        </label>

                        <input
                            type="password"
                            name="new_password"
                            id="newPassword"
                            minlength="12"
                            maxlength="255"
                            required
                            autocomplete="new-password"
                        >

                        <small>
                            Mínimo 12 caracteres, mayúscula, minúscula,
                            número y símbolo.
                        </small>

                    </div>

                    <!-- Confirmar nueva contraseña -->

                    <div class="form-group">

                        <label for="confirmPassword">
                            Confirmar nueva contraseña
                        </label>

                        <input
                            type="password"
                            name="confirm_password"
                            id="confirmPassword"
                            minlength="12"
                            maxlength="255"
                            required
                            autocomplete="new-password"
                        >

                        <small
                            id="confirmError"
                            class="password-error"
                        ></small>

                    </div>

                    <!-- Botón para actualizar -->

                    <button type="submit">
                        Actualizar contraseña
                    </button>

                </form>

                <!-- Botón para volver -->

                <?php if (!$forzado): ?>

                    <div class="back">

                        <a href="<?= e($volver) ?>">
                            ← Volver
                        </a>

                    </div>

                <?php endif; ?>

            </div>

        </div>

        <!-- JavaScript -->
        <script
            src="../../js/programa/cambiar_password.js"
            defer
        ></script>

    </body>

</html>




<!-- -------------------------------------------------------------------------------------------------------------
   -------------------------------------- FIN Jerez cambiar_password.php --------------------------------------
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