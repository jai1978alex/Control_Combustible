<?php
/*
 * Sitio Web Creado por Jerez.
 * Direccion: Miraflorez #1280
 * Quintero - Chile
 * jaime.jerez1978@gmail.com
 * https://www.
 * Creado, Programado y Diseñado por Jerez.
 * JJA
 */

/* -------------------------------------------------------------------------------------------------------------
   ------------------------------------- INICIO Jerez editar_nombre_usuario.php ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

declare(strict_types=1);

/* TÍTULO: CARGAR LOS ARCHIVOS NECESARIOS Y PROTEGER LA PÁGINA */

require_once __DIR__.'/../../../backend/conexion.php';
require_once __DIR__.'/../../../backend/security.php';
// solo un administrador, por formulario y con la llave secreta correcta
requireAdmin();
requirePost();
verifyCsrf();

/* TÍTULO: REVISAR LOS DATOS QUE LLEGAN DEL FORMULARIO */

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$nombres = trim((string)($_POST['nombres'] ?? ''));
$ap = trim((string)($_POST['apellido_paterno'] ?? ''));
$am = trim((string)($_POST['apellido_materno'] ?? ''));

if (!$id || !validText($nombres, 100) || !validText($ap, 100) || !validText($am, 100) || !columnaExiste($conn, 'usuarios', 'nombres')) {
    header('Location: superadmin.php?error=editar_nombre');
    exit;
}

/* TÍTULO: GUARDAR EL NOMBRE Y ANOTARLO EN LA BITÁCORA */

try {
    $stmt = $conn->prepare('UPDATE usuarios SET nombres=?, apellido_paterno=?, apellido_materno=? WHERE id=?');
    $stmt->bind_param('sssi', $nombres, $ap, $am, $id);
    $stmt->execute();
    audit($conn, (int)$_SESSION['usuario_id'], 'EDITAR_NOMBRE_USUARIO', 'usuarios', (int)$id, 'Nombre registrado actualizado');
    header('Location: superadmin.php?success=nombre_actualizado');
} catch (Throwable $e) {
    error_log($e->getMessage());
    header('Location: superadmin.php?error=editar_nombre');
}
exit;

/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- FIN Jerez editar_nombre_usuario.php --------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

/*
 * Sitio Web Creado por Jerez.
 * Direccion: Miraflorez #1280
 * Quintero - Chile
 * jaime.jerez1978@gmail.com
 * https://www.
 * Creado, Programado y Diseñado por Jerez.
 * JJA
 */
