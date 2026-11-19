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
   ------------------------------------- INICIO Jerez resetear_password_usuario.php ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

declare(strict_types=1);

/* TÍTULO: CARGAR LOS ARCHIVOS NECESARIOS Y PROTEGER LA PÁGINA */

// esta parte carga la conexión y la seguridad
require_once __DIR__.'/../../../backend/conexion.php';
require_once __DIR__.'/../../../backend/security.php';
// solo entra un administrador, por método POST y con el dato oculto de seguridad válido
requireAdmin();
requirePost();
verifyCsrf();

/* TÍTULO: REVISAR EL USUARIO QUE LLEGA DEL FORMULARIO */

// GUARDAR EL ID DEL USUARIO AL QUE SE LE CAMBIARÁ LA CLAVE
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
// si el id falta, no es válido o es el del propio administrador, se vuelve al panel con error
if (!$id || $id === (int)$_SESSION['usuario_id']) {
    header('Location: superadmin.php?error=reset_password');
    exit;
}

/* TÍTULO: BUSCAR EL USUARIO EN LA BASE DE DATOS */

// esta parte trae el id, el nombre y el estado del usuario
$stmt = $conn->prepare('SELECT id, username, estado FROM usuarios WHERE id=? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();

// si el usuario no existe, se vuelve al panel con error
if (!$u) {
    header('Location: superadmin.php?error=reset_password');
    exit;
}

/* TÍTULO: CREAR Y GUARDAR LA CONTRASEÑA TEMPORAL */

// GUARDAR LA CLAVE TEMPORAL Y SU VERSIÓN PROTEGIDA (en la base de datos solo se guarda la protegida)
$temporal = generarPasswordTemporal();
$hash = password_hash($temporal, PASSWORD_DEFAULT);

// esta parte guarda la nueva clave y obliga al usuario a cambiarla cuando vuelva a entrar
$upd = $conn->prepare('UPDATE usuarios SET password=?, debe_cambiar_password=1 WHERE id=?');
$upd->bind_param('si', $hash, $id);

// si no se pudo guardar, se vuelve al panel con error
if (!$upd->execute()) {
    header('Location: superadmin.php?error=reset_password');
    exit;
}

/* TÍTULO: REGISTRAR LA ACCIÓN EN AUDITORÍA */

// esta parte deja anotado quién reseteó la clave y de qué usuario (la clave no se anota)
audit($conn, (int)$_SESSION['usuario_id'], 'RESETEAR_PASSWORD_USUARIO', 'usuarios', $id, 'username='.$u['username']);

/* TÍTULO: ENTREGAR LA CLAVE TEMPORAL AL ADMINISTRADOR */

/*
 * La clave se muestra una sola vez al administrador, usando la sesión.
 * No se guarda en la base de datos, ni en un registro, ni en la dirección de la página.
 * El usuario deberá cambiarla cuando vuelva a iniciar sesión.
 */
$_SESSION['flash_temp_password'] = $temporal;
$_SESSION['flash_temp_password_user'] = $u['username'];

/* TÍTULO: VOLVER AL PANEL */

// esta parte vuelve al panel con el mensaje de éxito, donde se muestra la clave una sola vez
header('Location: superadmin.php?success=password_reseteada');
exit;



/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- FIN Jerez resetear_password_usuario.php --------------------------------------
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
