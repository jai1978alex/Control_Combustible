<?php
declare(strict_types=1);
require_once __DIR__.'/../../../backend/conexion.php'; require_once __DIR__.'/../../../backend/security.php';
requireAdmin(); requirePost(); verifyCsrf();
$id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT);
if(!$id || $id===$_SESSION['usuario_id']) { header('Location: superadmin.php?error=eliminar'); exit; }
$stmt=$conn->prepare('SELECT username,rol FROM usuarios WHERE id=?'); $stmt->bind_param('i',$id); $stmt->execute(); $u=$stmt->get_result()->fetch_assoc();
if(!$u){header('Location: superadmin.php?error=eliminar');exit;}
if($u['rol']==='admin') { $count=(int)$conn->query("SELECT COUNT(*) c FROM usuarios WHERE rol='admin' AND estado='activo'")->fetch_assoc()['c']; if($count<=1){header('Location: superadmin.php?error=ultimo_admin');exit;} }
$stmt=$conn->prepare("UPDATE usuarios SET estado='inactivo' WHERE id=?"); $stmt->bind_param('i',$id); $stmt->execute(); audit($conn,(int)$_SESSION['usuario_id'],'DESACTIVAR_USUARIO','usuarios',$id,'username='.$u['username']);
header('Location: superadmin.php?success=usuario'); exit;
