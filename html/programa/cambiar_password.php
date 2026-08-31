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
   ------------------------------------- INICIO Jerez cambiar_password.html ------------------------------------
   ------------------------------------------------------------------------------------------------------------- -->



<?php

/* INICIO Y CONFIGURACIÓN DEL ARCHIVO */

    // Activa el uso de tipos de datos estrictos en PHP.
    declare(strict_types=1);
    // Carga el archivo que permite conectarse a la base de datos.
    require_once __DIR__ . '/../../backend/conexion.php';
    // Carga el archivo que contiene las funciones de seguridad.
    require_once __DIR__ . '/../../backend/security.php';
    // Comprueba que el usuario haya iniciado sesión.
    requireLogin();

/* VARIABLES PARA LOS MENSAJES */

    // Crea una variable para guardar mensajes informativos.
    $mensaje = '';
    // Crea una variable para guardar mensajes de error.
    $error = '';
    // Comprueba si el usuario está obligado a cambiar su contraseña.
    $forzado = !empty($_SESSION['debe_cambiar_password']);

/* COMPROBAR ENVÍO DEL FORMULARIO */

    // Comprueba si el formulario fue enviado mediante POST.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Comprueba que el código de seguridad del formulario sea correcto.
        verifyCsrf();

    
/* OBTENER DATOS DEL FORMULARIO */
    
        // Obtiene la contraseña actual escrita por el usuario.
        $actual = (string)($_POST['current_password'] ?? '');
        // Obtiene la nueva contraseña escrita por el usuario.
        $nueva = (string)($_POST['new_password'] ?? '');
        // Obtiene la confirmación de la nueva contraseña.
        $confirmar = (string)($_POST['confirm_password'] ?? '');
        // Obtiene el número del usuario que tiene la sesión iniciada.
        $uid = (int)$_SESSION['usuario_id'];
    
/* COMPROBAR LA NUEVA CONTRASEÑA */

        // Comprueba que la nueva contraseña cumpla las reglas de seguridad.
        if (!validPassword($nueva)) {
            // Muestra un error si la nueva contraseña no cumple los requisitos.
            $error = 'La nueva contraseña no cumple los requisitos de seguridad (mínimo 12 caracteres, mayúscula, minúscula, número y símbolo).';
        // Comprueba que la nueva contraseña y su confirmación sean iguales.
        } elseif ($nueva !== $confirmar) {
            // Muestra un error si las dos contraseñas no coinciden.
            $error = 'La confirmación no coincide con la nueva contraseña.';
        // Si las comprobaciones anteriores son correctas, continúa.
        } else {

       
/* BUSCAR LA CONTRASEÑA ACTUAL */
        
            // Prepara una consulta para buscar la contraseña del usuario.
            $stmt = $conn->prepare('SELECT password FROM usuarios WHERE id=? LIMIT 1');
            // Envía el número del usuario a la consulta.
            $stmt->bind_param('i', $uid);
            // Ejecuta la consulta.
            $stmt->execute();
            // Obtiene los datos del usuario encontrado.
            $row = $stmt->get_result()->fetch_assoc();
        
/* COMPROBAR LA CONTRASEÑA ACTUAL */
        
            // Comprueba que exista el usuario y que la contraseña actual sea correcta.
            if (!$row || !password_verify($actual, (string)$row['password'])) {
                // Muestra un error si la contraseña actual no es correcta.
                $error = 'La contraseña actual no es correcta.';
            // Comprueba que la nueva contraseña no sea igual a la anterior.
            } elseif (password_verify($nueva, (string)$row['password'])) {
                // Muestra un error si se intenta usar la misma contraseña.
                $error = 'La nueva contraseña no puede ser igual a la actual.';
            // Si todo está correcto, continúa con el cambio de contraseña.
            } else {

            
/* CREAR LA NUEVA CONTRASEÑA */
            
                // Protege la nueva contraseña antes de guardarla.
                $hash = password_hash($nueva, PASSWORD_DEFAULT);
                // Comienza una operación para guardar los cambios de forma segura.
                $conn->begin_transaction();

                
/* ACTUALIZAR LA CONTRASEÑA */
            
                // Intenta realizar el cambio de contraseña.
                try {
                    // Prepara la consulta para actualizar la contraseña del usuario.
                    $u = $conn->prepare('UPDATE usuarios SET password=?, debe_cambiar_password=0 WHERE id=?');
                    // Envía la nueva contraseña protegida y el número del usuario.
                    $u->bind_param('si', $hash, $uid);
                    // Ejecuta la actualización.
                    $u->execute();

                    
/* OBTENER LA FECHA ACTUALIZADA */
                
                    // Prepara una consulta para obtener la fecha de actualización.
                    $r = $conn->prepare('SELECT updated_at FROM usuarios WHERE id=?');
                    // Envía el número del usuario a la consulta.
                    $r->bind_param('i', $uid);
                    // Ejecuta la consulta.
                    $r->execute();
                    // Obtiene los datos actualizados del usuario.
                    $fresh = $r->get_result()->fetch_assoc();


/* GUARDAR EL CAMBIO EN EL REGISTRO */
                
                    // Guarda un registro indicando que el usuario cambió su contraseña.
                    audit($conn, $uid, 'CAMBIO_PASSWORD_PROPIO', 'usuarios', $uid);
                    // Confirma y guarda todos los cambios realizados.
                    $conn->commit();

                
 /* ACTUALIZAR LA SESIÓN */
                
                    /* Renueva la sesión con los datos ya vigentes para no auto-invalidarse. */
                    // Crea una nueva sesión para el usuario.
                    session_regenerate_id(true);
                    // Guarda en la sesión la fecha actualizada del usuario.
                    $_SESSION['user_updated_at'] = (string)$fresh['updated_at'];
                    // Indica que el usuario ya no necesita cambiar su contraseña.
                    $_SESSION['debe_cambiar_password'] = 0;
                    // Crea un nuevo código de seguridad para la sesión.
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                
/* DEFINIR LA PÁGINA DE DESTINO */
                
                    // Define a qué página será enviado el usuario después del cambio.
                    $destino = appBasePath() . (($_SESSION['rol'] ?? '') === 'admin'
                        // Si el usuario es administrador, lo envía al panel de administrador.
                        ? '/html/login/seguridad_login/superadmin.php?success=password_actualizada'
                        // Si no es administrador, lo envía al panel principal.
                        : '/html/programa/panel.php?success=password_actualizada');
                    // Envía al usuario a la página correspondiente.
                    header('Location: ' . $destino);
                    // Detiene la ejecución del código.
                    exit;


/* MANEJAR ERRORES AL CAMBIAR LA CONTRASEÑA */
            
                // Captura cualquier problema que ocurra durante el proceso.
                } catch (Throwable $e) {
                    // Deshace los cambios realizados si ocurrió un problema.
                    $conn->rollback();
                    // Guarda el detalle del error en el registro del sistema.
                    error_log('Error cambiando contraseña propia: ' . $e->getMessage());
                    // Muestra un mensaje simple para el usuario.
                    $error = 'No fue posible actualizar la contraseña. Intenta nuevamente.';
                }
            }
        }
    }



/* CREAR DATOS PARA EL FORMULARIO */

    // Obtiene el código de seguridad para usarlo en el formulario.
    $csrf = csrfToken();
    // Comprueba el tipo de usuario para definir la página de regreso.
    $volver = ($_SESSION['rol'] ?? '') === 'admin'
        // Si es administrador, vuelve al panel de administrador.
        ? '../login/seguridad_login/superadmin.php'
        // Si no es administrador, vuelve al panel principal.
        : 'panel.php';


/* ================================================== */
/* INICIO DE LA PÁGINA HTML */
/* ================================================== */

?><!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Cambiar Contraseña</title>
        <link rel="stylesheet" href="../../css/programa/crear_usuario.css">

    </head>



<!-- CUERPO PRINCIPAL DE LA PÁGINA -->
    <body>

        
<!-- CONTENEDOR PRINCIPAL -->
            
        <div class="container">
            
<!-- TARJETA PARA CAMBIAR LA CONTRASEÑA -->
                   
            <div class="card">
                
<!-- TÍTULO DE LA PÁGINA -->
                                
                <h1>Cambiar Contraseña</h1>
                
<!-- AVISO DE CAMBIO OBLIGATORIO -->
                
                <?php if ($forzado && $error === ''): ?>
                    <!-- Muestra un aviso cuando el administrador obligó a cambiar la contraseña. -->
                    <div class="error">Un administrador reseteó tu contraseña. Debes definir una nueva antes de continuar.</div>
                <?php endif; ?>

                
<!-- MOSTRAR MENSAJE CORRECTO -->
                

                <?php if ($mensaje): ?><div class="success"><?= e($mensaje) ?></div><?php endif; ?>
                
                
<!-- MOSTRAR MENSAJE DE ERROR -->
                                
                <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
                
<!-- FORMULARIO PARA CAMBIAR LA CONTRASEÑA -->
                
                <form method="POST" autocomplete="off">

<!-- CÓDIGO DE SEGURIDAD -->
                    
                    <!-- Guarda el código de seguridad del formulario. -->
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                    
<!-- CONTRASEÑA ACTUAL -->
                    
                    <div class="form-group">
                        <!-- Muestra el nombre del campo para la contraseña actual. -->
                        <label>Contraseña actual</label>
                        <!-- Permite escribir la contraseña actual. -->
                        <input type="password" name="current_password" maxlength="255" required autocomplete="current-password">
                    </div>


                    
<!-- NUEVA CONTRASEÑA -->
                    
                    <div class="form-group">
                        <!-- Muestra el nombre del campo para la nueva contraseña. -->
                        <label>Nueva contraseña</label>
                        <!-- Permite escribir la nueva contraseña. -->
                        <input type="password" name="new_password" id="newPassword" minlength="12" required autocomplete="new-password">
                        <!-- Indica las reglas que debe cumplir la nueva contraseña. -->
                        <small>Mínimo 12 caracteres, mayúscula, minúscula, número y símbolo.</small>
                    </div>
                    
<!-- CONFIRMAR NUEVA CONTRASEÑA -->
                    
                    <div class="form-group">
                        <!-- Muestra el nombre del campo para confirmar la contraseña. -->
                        <label>Confirmar nueva contraseña</label>
                        <!-- Permite volver a escribir la nueva contraseña. -->
                        <input type="password" name="confirm_password" id="confirmPassword" minlength="12" required autocomplete="new-password">
                        <!-- Espacio donde se puede mostrar un mensaje sobre la contraseña. -->
                        <small id="confirmError" class="password-error"></small>
                    </div>
                    
<!-- BOTÓN PARA ACTUALIZAR -->
                    
                    <!-- Botón que permite guardar la nueva contraseña. -->
                    <button type="submit">Actualizar contraseña</button>
                </form>

<!-- BOTÓN PARA VOLVER -->
                                
                <?php if (!$forzado): ?>
                    <!-- Muestra el enlace para volver si el cambio no era obligatorio. -->
                    <div class="back"><a href="<?= e($volver) ?>">← Volver</a></div>
                <?php endif; ?>
            </div>
        </div>
       
        <!-- Carga el archivo JavaScript que controla el cambio de contraseña. -->
        <script src="../../js/programa/cambiar_password.js" defer></script>
    </body>

</html>




<!-- -------------------------------------------------------------------------------------------------------------
   -------------------------------------- FIN Jerez cambiar_password.html --------------------------------------
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