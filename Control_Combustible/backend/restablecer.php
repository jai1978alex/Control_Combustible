<?php
declare(strict_types=1);
require_once __DIR__.'/conexion.php';
require_once __DIR__.'/security.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'message'=>'Método no permitido']);
    exit;
}

$d = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$token = (string)($d['token'] ?? '');
$password = (string)($d['password'] ?? '');

if (!preg_match('/^[a-f0-9]{64}$/', $token) || !validPassword($password)) {
    echo json_encode(['ok'=>false,'message'=>'Datos inválidos.']);
    exit;
}

$hash = hash('sha256', $token);
$stmt = $conn->prepare('SELECT id,usuario_id FROM recuperacion_password WHERE token_hash=? AND usado=0 AND expira_en>NOW() LIMIT 1');
$stmt->bind_param('s', $hash);
$stmt->execute();
$r = $stmt->get_result()->fetch_assoc();

if (!$r) {
    echo json_encode(['ok'=>false,'message'=>'El enlace es inválido o expiró.']);
    exit;
}

$conn->begin_transaction();
try {
    $new = password_hash($password, PASSWORD_DEFAULT);
    $uid = (int)$r['usuario_id'];

    $u = $conn->prepare('UPDATE usuarios SET password=?, debe_cambiar_password=0 WHERE id=?');
    $u->bind_param('si', $new, $uid);
    $u->execute();

    /* Invalida cualquier otro enlace de recuperación pendiente. */
    $x = $conn->prepare('UPDATE recuperacion_password SET usado=1 WHERE usuario_id=?');
    $x->bind_param('i', $uid);
    $x->execute();

    audit($conn, $uid, 'CAMBIO_PASSWORD', 'usuarios', $uid);
    $conn->commit();

    echo json_encode(['ok'=>true,'message'=>'Contraseña actualizada correctamente.']);
} catch (Throwable $e) {
    $conn->rollback();
    error_log('Error restableciendo contraseña: '.$e->getMessage());
    echo json_encode(['ok'=>false,'message'=>'No fue posible cambiar la contraseña.']);
}
