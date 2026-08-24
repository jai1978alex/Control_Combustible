<?php
declare(strict_types=1);
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

<body class="acceso-page">

    <div class="acceso-container">
        <div class="acceso-card">

            <!-- HEADER -->
            <div class="acceso-header">
                <div class="logo-box">
                    <span class="logo-texto"><?= e(appName()) ?></span>
                </div>

                <h1 class="acceso-titulo">
                    Control de Combustible
                </h1>

                <p class="acceso-subtitulo">
                    Ingrese sus credenciales para acceder
                </p>
            </div>

            <!-- FORM LOGIN -->
            <form method="POST"
                  action="backend/login.php"
                  class="acceso-form">

                <input type="hidden" name="csrf_token" value="<?= e(loginCsrfToken()) ?>">

                <div class="form-group">
                    <label class="form-label">
                        Usuario
                    </label>

                    <input
                        type="text"
                        name="username"
                        class="form-input"
                        placeholder="Ingrese su usuario"
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label">
                        Contraseña
                    </label>

                    <input
                        type="password"
                        name="password"
                        class="form-input"
                        placeholder="Ingrese su contraseña"
                        required
                    >
                </div>

                <div class="recuperar">
                    <a href="html/programa/recuperar.html">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>

                <!-- ERROR -->
                <?php if (isset($_GET['error'])) :
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

                    <div class="error-message error-message-visible">
                        <?= e($mensajeError) ?>
                    </div>

                <?php endif; ?>

                <button type="submit" class="btn-acceso">
                    Iniciar Sesión
                </button>

            </form>

            <div class="acceso-footer">
                Sistema de Control de Combustible v1.0
            </div>

        </div>
    </div>

</body>

</html>