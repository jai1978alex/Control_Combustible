<?php
/*
 * Actualiza el RUT asociado a una cuenta desde el panel de superadministración.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../backend/conexion.php';
require_once __DIR__ . '/../../../backend/security.php';
require_once __DIR__ . '/../../../backend/rut_helper.php';

requireAdmin();
requirePost();
verifyCsrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$rut = normalizeRut((string)($_POST['rut'] ?? ''));

if (!$id || !columnaExiste($conn, 'usuarios', 'rut') ||
    !validText($rut, 12) || !validarFormatoRut($rut) || !validarRut($rut)) {
    header('Location: superadmin.php?error=editar_rut');
    exit;
}

try {
    $check = $conn->prepare('SELECT id FROM usuarios WHERE rut = ? AND id <> ? LIMIT 1');
    $check->bind_param('si', $rut, $id);
    $check->execute();
    if ($check->get_result()->fetch_assoc()) {
        header('Location: superadmin.php?error=editar_rut');
        exit;
    }

    $stmt = $conn->prepare('UPDATE usuarios SET rut = ? WHERE id = ?');
    $stmt->bind_param('si', $rut, $id);
    $stmt->execute();

    audit($conn, (int)$_SESSION['usuario_id'], 'EDITAR_RUT_USUARIO', 'usuarios', (int)$id, 'RUT de cuenta actualizado');
    header('Location: superadmin.php?success=rut_actualizado');
} catch (Throwable $e) {
    error_log($e->getMessage());
    header('Location: superadmin.php?error=editar_rut');
}
exit;
