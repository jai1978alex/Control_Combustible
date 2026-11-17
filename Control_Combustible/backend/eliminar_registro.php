<?php
declare(strict_types=1);
require_once __DIR__.'/conexion.php';
require_once __DIR__.'/security.php';
requireAdmin();
requirePost();
verifyCsrf();

$tipo = (string)($_POST['tipo'] ?? '');
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$tablas = [
    'cargador' => 'operador_cargador',
    'operador' => 'operador'
];

if (!$id || !isset($tablas[$tipo])) {
    header('Location: ../html/login/seguridad_login/superadmin.php?error=eliminar_registro');
    exit;
}

$tabla = $tablas[$tipo];
$conn->begin_transaction();
try {
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

    $stmt = $conn->prepare("UPDATE {$tabla} SET eliminado_at=NOW(), eliminado_por=? WHERE id=? AND eliminado_at IS NULL");
    $adminId = (int)$_SESSION['usuario_id'];
    $stmt->bind_param('ii', $adminId, $id);
    $stmt->execute();

    audit(
        $conn,
        (int)$_SESSION['usuario_id'],
        'ELIMINAR_REGISTRO',
        $tabla,
        $id,
        'RUT '.$registro['rut'].'; litros '.$registro['litros'].'; eliminación lógica'
    );

    $conn->commit();
    header('Location: ../html/login/seguridad_login/superadmin.php?success=registro_eliminado');
    exit;
} catch (Throwable $e) {
    $conn->rollback();
    error_log('Error eliminando registro: '.$e->getMessage());
    header('Location: ../html/login/seguridad_login/superadmin.php?error=eliminar_registro');
    exit;
}
