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
   ------------------------------------- INICIO Jerez crear_usuario.php ----------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

// Esta página le permite al administrador crear una cuenta nueva para una persona del equipo.

declare(strict_types=1);

// TÍTULO: ARCHIVOS QUE NECESITA ESTA PÁGINA
// Se cargan las herramientas de apoyo: la conexión a la base de datos, la seguridad y la revisión del RUT.
require_once __DIR__.'/../../../backend/conexion.php';
require_once __DIR__.'/../../../backend/security.php';
require_once __DIR__.'/../../../backend/rut_helper.php';

// TÍTULO: SOLO ADMINISTRADORES
// Si la persona no es administradora, no puede estar en esta página.
requireAdmin();

// TÍTULO: MENSAJES PARA LA PERSONA
// Aquí se guardan el aviso de éxito y el aviso de error. Al comienzo están vacíos.
$mensaje='';
$error='';

// TÍTULO: CUANDO SE ENVÍA EL FORMULARIO
// Esta parte solo trabaja cuando la persona presionó el botón "Crear Usuario".
if ($_SERVER['REQUEST_METHOD']==='POST') {

    // Se comprueba que el formulario realmente viene de esta página y no de otro lugar.
    verifyCsrf();

    // Se leen los datos que la persona escribió y se les quitan los espacios sobrantes.
    $username=trim((string)($_POST['username']??''));
    $email=trim((string)($_POST['email']??''));
    $rut=normalizeRut((string)($_POST['rut']??''));
    $password=(string)($_POST['password']??'');
    $passwordConfirm=(string)($_POST['password_confirm']??'');
    $rol=(string)($_POST['rol']??'operador');
    $currentPassword=(string)($_POST['current_password']??'');

    // TÍTULO: REVISIÓN DE LOS DATOS
    // Se verifica que el usuario, el correo, la contraseña, el rol y el RUT cumplan con lo pedido.
    if (
        !preg_match('/^[A-Za-z0-9._-]{3,50}$/',$username)
        ||($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))
        ||!validPassword($password)
        ||!in_array($rol,['operador','admin'],true)
        ||!validarFormatoRut($rut)
        ||!validarRut($rut)
    ) $error='Usuario, correo, rol o contraseña no cumplen los requisitos de seguridad.';

    // Se verifica que la contraseña y su repetición sean iguales.
    elseif($password!==$passwordConfirm) $error='La confirmación de contraseña no coincide.';

    else {

        // TÍTULO: PERMISO PARA CREAR OTRO ADMINISTRADOR
        // Para crear a otro administrador, la persona debe escribir de nuevo su propia contraseña.
        if($rol==='admin'){
            $me=$conn->prepare('SELECT password FROM usuarios WHERE id=? LIMIT 1');
            $meId=(int)$_SESSION['usuario_id'];
            $me->bind_param('i',$meId);
            $me->execute();
            $meRow=$me->get_result()->fetch_assoc();
            if(!$meRow || !password_verify($currentPassword,(string)$meRow['password'])) {
                $error='Para crear otro administrador debes confirmar tu contraseña actual.';
            }
        }

        // TÍTULO: GUARDAR EL NUEVO USUARIO
        // Si no hubo ningún problema, se protege la contraseña y se guarda la cuenta nueva.
        if($error==='') {
            $hash=password_hash($password,PASSWORD_DEFAULT);
            try {
                $stmt=$conn->prepare('INSERT INTO usuarios(username,password,email,rol,rut) VALUES(?,?,?,?,?)');
                $stmt->bind_param('sssss',$username,$hash,$email,$rol,$rut);
                $stmt->execute();
                // Se deja una anotación de quién creó la cuenta, para tener un registro.
                audit($conn,(int)$_SESSION['usuario_id'],'CREAR_USUARIO','usuarios',(int)$stmt->insert_id,'username='.$username.' rol='.$rol);
                $mensaje='Usuario creado correctamente.';
            } catch(Throwable $e){
                // Si algo falla (por ejemplo, el nombre ya existe), se avisa con un mensaje claro.
                $error='No se pudo crear el usuario. El nombre o correo podría estar ya registrado.';
            }
        }
    }
}

// Se prepara el código de seguridad que viajará escondido dentro del formulario.
$csrf=csrfToken();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Usuario</title>
    <link rel="stylesheet" href="../../../css/programa/crear_usuario.css">
</head>

<!-- TÍTULO: CUERPO PRINCIPAL DE LA PÁGINA -->

<body>
    <!-- contenedor principal de la página -->
    <div class="container">
        <!-- tarjeta que contiene el formulario -->
        <div class="card">

            <!-- título principal -->
            <h1>Crear Usuario</h1>

<!-- TÍTULO: MENSAJES PARA LA PERSONA -->

            <!-- aviso verde que aparece cuando el usuario se creó bien -->
            <?php if($mensaje): ?><div class="success"><?=e($mensaje)?></div><?php endif;?>

            <!-- aviso rojo que aparece cuando hubo un problema -->
            <?php if($error): ?><div class="error"><?=e($error)?></div><?php endif;?>

<!-- TÍTULO: FORMULARIO PARA CREAR EL USUARIO -->

            <form method="POST">

                <!-- dato oculto para proteger el envío del formulario -->
                <input type="hidden" name="csrf_token" value="<?=e($csrf)?>">

                <!-- campo: nombre de usuario -->
                <div class="form-group">
                    <label>Usuario</label><input type="text" name="username" maxlength="50" required pattern="[A-Za-z0-9._-]{3,50}">
                </div>

                <!-- campo: RUT de la persona -->
                <div class="form-group">
                    <label>RUT del trabajador</label><input type="text" name="rut" maxlength="12" placeholder="12345678-5" required><small>Debe ser el RUT real del titular. Se usará para validar su identidad mediante QR.</small>
                </div>

                <!-- campo: correo electrónico (es opcional) -->
                <div class="form-group">
                    <label>Email</label><input type="email" name="email" maxlength="100">
                </div>

                <!-- campo: contraseña nueva -->
                <div class="form-group">
                    <label>Contraseña</label><input type="password" id="crearUsuarioPassword" name="password" minlength="12" required autocomplete="new-password"><small>Mínimo 12 caracteres, mayúscula, minúscula, número y símbolo.</small>
                </div>

                <!-- campo: repetir la contraseña nueva -->
                <div class="form-group">
                    <label>Confirmar contraseña</label><input type="password" id="crearUsuarioPasswordConfirm" name="password_confirm" minlength="12" required autocomplete="new-password"><small id="crearUsuarioPasswordError" class="password-error"></small>
                </div>

                <!-- campo: contraseña de quien está creando la cuenta (solo si crea un administrador) -->
                <div class="form-group">
                    <label>Contraseña actual (obligatoria si crea Administrador)</label><input type="password" name="current_password" maxlength="255" autocomplete="current-password">
                </div>

                <!-- campo: tipo de cuenta (operador o administrador) -->
                <div class="form-group">
                    <label>Rol</label><select name="rol">
                        <option value="operador">Operador</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>

                <!-- botón para guardar el usuario nuevo -->
                <button type="submit">Crear Usuario</button>

            </form>

<!-- TÍTULO: ENLACE PARA VOLVER -->

            <!-- enlace que lleva de vuelta al panel principal -->
            <div class="back"><a href="superadmin.php">← Volver al Panel</a></div>

        </div>
    </div>

    <!-- programa de apoyo que revisa que las dos contraseñas sean iguales -->
    <script src="../../../js/programa/crear_usuario.js" defer></script>
</body>

</html>
<?php
/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- FIN Jerez crear_usuario.php ------------------------------------------
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
?>