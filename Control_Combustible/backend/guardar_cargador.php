<?php
declare(strict_types=1);
require_once __DIR__.'/conexion.php'; require_once __DIR__.'/security.php'; require_once __DIR__.'/rut_helper.php';
requireLogin(); requirePost(); verifyCsrf();
$usuarioId=(int)$_SESSION['usuario_id'];
if (!rateLimitFile('guardar_cargador', (string)$usuarioId, 300, 40)) { header('Location: ../html/programa/panel.php?error=demasiados_intentos'); exit; }
$nombres=trim((string)($_POST['nombres']??'')); $ap=trim((string)($_POST['apellidoPaterno']??'')); $am=trim((string)($_POST['apellidoMaterno']??''));
$rut=normalizeRut((string)($_POST['rut']??'')); $turno=trim((string)($_POST['turno']??'')); $codigo=trim((string)($_POST['codigo']??''));
$ubicacion=trim((string)($_POST['ubicacion']??'')); $patente=strtoupper(trim((string)($_POST['patente']??''))); $horometro=trim((string)($_POST['horometro']??''));
$litros=trim((string)($_POST['litros']??'')); $observacion=trim((string)($_POST['observacion']??''));
$fechaRegistro=date('Y-m-d H:i:s');
if (!validText($nombres,100)||!validText($ap,100)||!validText($am,100)||!validText($rut,12)||!validText($codigo,50)||!validText($ubicacion,100)||!validText($patente,10)||!in_array($turno,['A','B','C','D'],true)||!validDecimal($horometro,0,100000000)||!validDecimal($litros,0.01,1000000)||mb_strlen($observacion)>2000||!validarFormatoRut($rut)||!validarRut($rut)||!validGps($ubicacion)||!preg_match('/^[A-Z0-9 .-]{2,10}$/', $patente)) { header('Location: ../html/programa/panel.php?error=campos'); exit; }
$conn->begin_transaction();
try {
 $stmt=$conn->prepare('INSERT INTO operador_cargador (nombres,apellido_paterno,apellido_materno,rut,turno,codigo,ubicacion,patente,horometro,litros,observacion,usuario_id,fecha_registro) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
 $stmt->bind_param('ssssssssddsis',$nombres,$ap,$am,$rut,$turno,$codigo,$ubicacion,$patente,$horometro,$litros,$observacion,$usuarioId,$fechaRegistro); $stmt->execute(); $id=$stmt->insert_id;
 audit($conn,$usuarioId,'REGISTRO_CARGADOR','operador_cargador',(int)$id,'RUT '.$rut.'; litros '.$litros); $conn->commit(); header('Location: ../html/programa/panel.php?success=1'); exit;
} catch(Throwable $e){$conn->rollback(); error_log($e->getMessage()); header('Location: ../html/programa/panel.php?error=guardar'); exit;}
