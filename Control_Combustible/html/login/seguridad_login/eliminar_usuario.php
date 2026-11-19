<?php
/*
Sitio Web Creado por Jerez.
Direccion: Miraflorez #1280
Quintero - Chile
jaime.jerez1978@gmail.com
https://www.
Creado, Programado y Diseñado por Jerez.
JJA
*/

/* -------------------------------------------------------------------------------------------------------------
   ------------------------------------ INICIO Jerez eliminar_usuario.php --------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

// Esta página desactiva la cuenta de un usuario. No muestra nada en pantalla: hace su trabajo y vuelve al panel.

declare(strict_types=1);

// TÍTULO: ARCHIVOS QUE NECESITA ESTA PÁGINA

// Se cargan las herramientas de apoyo: la conexión a la base de datos y la seguridad.
require_once __DIR__.'/../../../backend/conexion.php';
require_once __DIR__.'/../../../backend/security.php';

// TÍTULO: REVISIONES DE SEGURIDAD

// Solo puede entrar un administrador, solo se acepta el envío desde el formulario
// y se comprueba que ese formulario realmente viene de esta aplicación.
requireAdmin();
requirePost();
verifyCsrf();

// TÍTULO: QUÉ USUARIO SE QUIERE DESACTIVAR

// Se lee el número del usuario elegido.
$id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT);

// Si el número no es válido, o si la persona intenta desactivar su propia cuenta,
// se vuelve al panel con un aviso de error.
if(!$id || $id===$_SESSION['usuario_id']) {
    header('Location: superadmin.php?error=eliminar');
    exit;
}

// TÍTULO: BUSCAR EL USUARIO

// Se busca en la base de datos el nombre y el tipo de cuenta del usuario elegido.
$stmt=$conn->prepare('SELECT username,rol FROM usuarios WHERE id=?');
$stmt->bind_param('i',$id);
$stmt->execute();
$u=$stmt->get_result()->fetch_assoc();

// Si el usuario no existe, se vuelve al panel con un aviso de error.
if(!$u){
    header('Location: superadmin.php?error=eliminar');
    exit;
}

// TÍTULO: PROTEGER AL ÚLTIMO ADMINISTRADOR

// Si el usuario es administrador, se cuenta cuántos administradores activos hay.
// Si es el único que queda, no se puede desactivar, para que el sistema nunca se quede sin administrador.
if($u['rol']==='admin') {
    $count=(int)$conn->query("SELECT COUNT(*) c FROM usuarios WHERE rol='admin' AND estado='activo'")->fetch_assoc()['c'];
    if($count<=1){
        header('Location: superadmin.php?error=ultimo_admin');
        exit;
    }
}

// TÍTULO: DESACTIVAR LA CUENTA

// La cuenta no se borra: solo se marca como inactiva.
$stmt=$conn->prepare("UPDATE usuarios SET estado='inactivo' WHERE id=?");
$stmt->bind_param('i',$id);
$stmt->execute();

// Se deja una anotación de quién desactivó la cuenta, para tener un registro.
audit($conn,(int)$_SESSION['usuario_id'],'DESACTIVAR_USUARIO','usuarios',$id,'username='.$u['username']);

// TÍTULO: VOLVER AL PANEL

// Se regresa al panel con un aviso de que todo salió bien.
header('Location: superadmin.php?success=usuario');
exit;




/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- FIN Jerez eliminar_usuario.php ---------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

/*
Sitio Web Creado por Jerez.
Direccion: Miraflorez #1280
Quintero - Chile
jaime.jerez1978@gmail.com
https://www.
Creado, Programado y Diseñado por Jerez.
JJA
*/
