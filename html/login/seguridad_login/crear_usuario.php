<?php
declare(strict_types=1);
require_once __DIR__.'/../../../backend/conexion.php'; require_once __DIR__.'/../../../backend/security.php';
requireAdmin();
$mensaje=''; $error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 verifyCsrf();
 $username=trim((string)($_POST['username']??'')); $email=trim((string)($_POST['email']??'')); $password=(string)($_POST['password']??''); $passwordConfirm=(string)($_POST['password_confirm']??''); $rol=(string)($_POST['rol']??'operador');
 $currentPassword=(string)($_POST['current_password']??'');
 if(!preg_match('/^[A-Za-z0-9._-]{3,50}$/',$username)||($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))||!validPassword($password)||!in_array($rol,['operador','admin'],true)) $error='Usuario, correo, rol o contraseña no cumplen los requisitos de seguridad.';
 elseif($password!==$passwordConfirm) $error='La confirmación de contraseña no coincide.';
 else {
  if($rol==='admin'){
   $me=$conn->prepare('SELECT password FROM usuarios WHERE id=? LIMIT 1'); $meId=(int)$_SESSION['usuario_id']; $me->bind_param('i',$meId); $me->execute(); $meRow=$me->get_result()->fetch_assoc();
   if(!$meRow || !password_verify($currentPassword,(string)$meRow['password'])) { $error='Para crear otro administrador debes confirmar tu contraseña actual.'; }
  }
  if($error==='') { $hash=password_hash($password,PASSWORD_DEFAULT); try { $stmt=$conn->prepare('INSERT INTO usuarios(username,password,email,rol) VALUES(?,?,?,?)'); $stmt->bind_param('ssss',$username,$hash,$email,$rol); $stmt->execute(); audit($conn,(int)$_SESSION['usuario_id'],'CREAR_USUARIO','usuarios',(int)$stmt->insert_id,'username='.$username.' rol='.$rol); $mensaje='Usuario creado correctamente.'; } catch(Throwable $e){$error='No se pudo crear el usuario. El nombre o correo podría estar ya registrado.';} }
  }
}
$csrf=csrfToken();
?><!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Crear Usuario</title><link rel="stylesheet" href="../../../css/programa/crear_usuario.css"></head><body><div class="container"><div class="card"><h1>Crear Usuario</h1><?php if($mensaje): ?><div class="success"><?=e($mensaje)?></div><?php endif;?><?php if($error): ?><div class="error"><?=e($error)?></div><?php endif;?><form method="POST"><input type="hidden" name="csrf_token" value="<?=e($csrf)?>"><div class="form-group"><label>Usuario</label><input type="text" name="username" maxlength="50" required pattern="[A-Za-z0-9._-]{3,50}"></div><div class="form-group"><label>Email</label><input type="email" name="email" maxlength="100"></div><div class="form-group"><label>Contraseña</label><input type="password" id="crearUsuarioPassword" name="password" minlength="12" required autocomplete="new-password"><small>Mínimo 12 caracteres, mayúscula, minúscula, número y símbolo.</small></div><div class="form-group"><label>Confirmar contraseña</label><input type="password" id="crearUsuarioPasswordConfirm" name="password_confirm" minlength="12" required autocomplete="new-password"><small id="crearUsuarioPasswordError" class="password-error"></small></div><div class="form-group"><label>Contraseña actual (obligatoria si crea Administrador)</label><input type="password" name="current_password" maxlength="255" autocomplete="current-password"></div><div class="form-group"><label>Rol</label><select name="rol"><option value="operador">Operador</option><option value="admin">Administrador</option></select></div><button type="submit">Crear Usuario</button></form><div class="back"><a href="superadmin.php">← Volver al Panel</a></div></div></div>
<script src="../../../js/programa/crear_usuario.js" defer></script>
</body></html>
