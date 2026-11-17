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
   ------------------------------------- INICIO Jerez index.php ------------------------------------
   ------------------------------------------------------------------------------------------------------------- -->



<?php
ob_start();
require_once __DIR__ . '/backend/security.php';

// Si ya está logueado, redirige al panel
if (isset($_SESSION['usuario_id'])) {
    header("Location: html/programa/panel.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Control de Combustible <?= e(appName()) ?> - acceso</title>

        <link rel="stylesheet" href="css/programa/style.css">
        <link rel="stylesheet" href="css/programa/acceso.css">
    </head>

<!-- TÍTULO: CUERPO PRINCIPAL DE LA PÁGINA -->

    <body class="acceso-page">
        <!-- contenedor principal de la página -->
        <div class="acceso-container">
            <!-- targeta del formulrio de acceso -->
            <div class="acceso-card">

<!-- TÍTULO: ENCABEZADO DE LA PÁGINA -->

                <!-- Esta parte muestra el logo y los textos principales -->
                <div class="acceso-header">
                    <!-- caja donde se muestra el logo -->
                    <div class="logo-box">
                        <!-- nombre de la aplicación -->
                        <span class="logo-texto"><?= e(appName()) ?></span>
                    </div>

                    <!-- título principal -->
                    <h1 class="acceso-titulo">
                        <!-- texto que se muestra como título -->
                        Control de Combustible
                    </h1>

                    <!-- texto que explica el objetivo del formulario -->
                    <p class="acceso-subtitulo">
                        <!-- mensaje para el usuario -->
                        Ingrese sus credenciales para acceder
                    </p>
                </div>

<!-- TÍTULO: FORMULARIO PARA INICIAR SESIÓN -->
 
                <!-- esta parte permite ingresar el usuario y la contraseña -->
                <form method="POST"         
                    action="backend/login.php"      
                    class="acceso-form">

                    <!-- dato oculto para proteger el envío del formulario -->
                    <input type="hidden" name="csrf_token" value="<?= e(loginCsrfToken()) ?>">

<!-- TÍTULO: GRUPO DEL CAMPO DE USUARIO -->

                    <div class="form-group">
                        <!-- texto que indica qué dato debe ingresar -->
                        <label class="form-label">
                            <!-- nombre del campo -->
                            Usuario
                        </label>

                        <!-- campo para escribir el nombre de usuario -->
                        <input
                            type="text"
                            name="username"
                            class="form-input"
                            placeholder="Ingrese su usuario"
                            required
                        >
                    </div>

<!-- TÍTULO: GRUPO DEL CAMPO DE CONTRASEÑA -->

                    <div class="form-group">
                        <!-- texto que indica qué dato debe ingresar -->
                        <label class="form-label">
                            <!-- nombredel campo -->
                            Contraseña
                        </label>

                        <!-- campo para escribir la contraseña -->
                        <input                   
                            type="password"
                            name="password"
                            class="form-input"
                            placeholder="Ingrese su contraseña"    
                            required
                        >
                    </div>

<!-- TÍTULO: SECCIÓN PARA RECUPERAR LA CONTRASEÑA -->

                    <div class="recuperar">
                        <!-- enlace para ir a la página de recuperación -->
                        <a href="html/programa/recuperar.html">
                            <!-- texto que aparece como enlace -->
                            ¿Olvidaste tu contraseña?
                        </a>

                    </div>

<!-- TÍTULO: REVISAR SI EXISTE UN ERROR EN LA URL -->

                    <?php if (isset($_GET['error'])) :

                        // GUARDAR EL CÓDIGO DEL ERROR
                        $errCodigo = (string)$_GET['error'];
                        /*
                        * El mensaje NO distingue "usuario no existe" de
                        * "contraseña incorrecta" a propósito (evita que un
                        * atacante descubra qué usuarios existen). Sí se
                        * distinguen los casos que no son un intento de login
                        * fallido (bloqueo temporal, sesión vencida, cuenta sin
                        * acceso), porque ahí no hay riesgo de enumeración y el
                        * usuario necesita saber que su clave era correcta.
                        */
                        $mensajesError = [
                            'bloqueo' => 'Demasiados intentos fallidos. Por seguridad, los intentos quedaron bloqueados temporalmente. Espera unos minutos y vuelve a intentar.',
                            'sesion'  => 'Tu sesión expiró o tu contraseña fue actualizada. Ingresa nuevamente.',
                            'acceso'  => 'Tu cuenta no tiene acceso o fue desactivada. Contacta a un administrador.',
                        ];
                        $mensajeError = $mensajesError[$errCodigo] ?? 'Usuario o contraseña incorrectos';
                    ?>

                      
<!-- TÍTULO: MENSAJE PARA MOSTRAR EL ERROR -->

                    <!-- esta parte muestra el mensaje cuando existe un problema -->
                    <div class="error-message error-message-visible">
                        <!-- mostrar el mensaje de error -->
                        <?= e($mensajeError) ?>
                    </div>
                
                    <?php endif; ?>

<!-- TÍTULO: BOTÓN PARA INICIAR SESIÓN -->

                    <button type="submit" class="btn-acceso">
                        <!-- texto que aparece en el botón -->
                        Iniciar Sesión
                    </button>

                </form>

<!--TÍTULO: PIE DE PÁGINA DEL ACCESO -->

                <div class="acceso-footer">
                    <!-- nombre y versión del sistema -->
                    Sistema de Control de Combustible v1.0
                </div>
            </div>
        </div>

    </body>

</html>




<!-- -------------------------------------------------------------------------------------------------------------
   -------------------------------------- FIN Jerez index.php --------------------------------------
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