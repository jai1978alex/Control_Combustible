<?php
declare(strict_types=1);
require_once __DIR__ . '/../../backend/conexion.php';
require_once __DIR__ . '/../../backend/security.php';
requireLogin();

$mensaje = '';
$error = '';
$forzado = !empty($_SESSION['debe_cambiar_password']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $actual = (string)($_POST['current_password'] ?? '');
    $nueva = (string)($_POST['new_password'] ?? '');
    $confirmar = (string)($_POST['confirm_password'] ?? '');
    $uid = (int)$_SESSION['usuario_id'];

    if (!validPassword($nueva)) {
        $error = 'La nueva contraseña no cumple los requisitos de seguridad (mínimo 12 caracteres, mayúscula, minúscula, número y símbolo).';
    } elseif ($nueva !== $confirmar) {
        $error = 'La confirmación no coincide con la nueva contraseña.';
    } else {
        $stmt = $conn->prepare('SELECT password FROM usuarios WHERE id=? LIMIT 1');
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!$row || !password_verify($actual, (string)$row['password'])) {
            $error = 'La contraseña actual no es correcta.';
        } elseif (password_verify($nueva, (string)$row['password'])) {
            $error = 'La nueva contraseña no puede ser igual a la actual.';
        } else {
            $hash = password_hash($nueva, PASSWORD_DEFAULT);
            $conn->begin_transaction();
            try {
                $u = $conn->prepare('UPDATE usuarios SET password=?, debe_cambiar_password=0 WHERE id=?');
                $u->bind_param('si', $hash, $uid);
                $u->execute();

                $r = $conn->prepare('SELECT updated_at FROM usuarios WHERE id=?');
                $r->bind_param('i', $uid);
                $r->execute();
                $fresh = $r->get_result()->fetch_assoc();

                audit($conn, $uid, 'CAMBIO_PASSWORD_PROPIO', 'usuarios', $uid);
                $conn->commit();

                /* Renueva la sesión con los datos ya vigentes para no auto-invalidarse. */
                session_regenerate_id(true);
                $_SESSION['user_updated_at'] = (string)$fresh['updated_at'];
                $_SESSION['debe_cambiar_password'] = 0;
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                $destino = appBasePath() . (($_SESSION['rol'] ?? '') === 'admin'
                    ? '/html/login/seguridad_login/superadmin.php?success=password_actualizada'
                    : '/html/programa/panel.php?success=password_actualizada');
                header('Location: ' . $destino);
                exit;
            } catch (Throwable $e) {
                $conn->rollback();
                error_log('Error cambiando contraseña propia: ' . $e->getMessage());
                $error = 'No fue posible actualizar la contraseña. Intenta nuevamente.';
            }
        }
    }
}

$csrf = csrfToken();
$volver = ($_SESSION['rol'] ?? '') === 'admin'
    ? '../login/seguridad_login/superadmin.php'
    : 'panel.php';
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cambiar Contraseña</title>
<link rel="stylesheet" href="../../css/programa/crear_usuario.css">
</head>
<body>
<div class="container">
    <div class="card">
        <h1>Cambiar Contraseña</h1>

        <?php if ($forzado && $error === ''): ?>
            <div class="error">Un administrador reseteó tu contraseña. Debes definir una nueva antes de continuar.</div>
        <?php endif; ?>

        <?php if ($mensaje): ?><div class="success"><?= e($mensaje) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>

        <form method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

            <div class="form-group">
                <label>Contraseña actual</label>
                <input type="password" name="current_password" maxlength="255" required autocomplete="current-password">
            </div>

            <div class="form-group">
                <label>Nueva contraseña</label>
                <input type="password" name="new_password" id="newPassword" minlength="12" required autocomplete="new-password">
                <small>Mínimo 12 caracteres, mayúscula, minúscula, número y símbolo.</small>
            </div>

            <div class="form-group">
                <label>Confirmar nueva contraseña</label>
                <input type="password" name="confirm_password" id="confirmPassword" minlength="12" required autocomplete="new-password">
                <small id="confirmError" class="password-error"></small>
            </div>

            <button type="submit">Actualizar contraseña</button>
        </form>

        <?php if (!$forzado): ?>
            <div class="back"><a href="<?= e($volver) ?>">← Volver</a></div>
        <?php endif; ?>
    </div>
</div>
<script src="../../js/programa/cambiar_password.js" defer></script>
</body>
</html>
