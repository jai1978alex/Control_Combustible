<?php
declare(strict_types=1);
require_once __DIR__.'/conexion.php';
require_once __DIR__.'/security.php';
require_once __DIR__.'/rut_helper.php';
requireAdmin(); requirePost(); verifyCsrf();

$tipo=(string)($_POST['tipo']??'');
$id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT);
$tablas=['cargador'=>'operador_cargador','operador'=>'operador'];
if(!$id || !isset($tablas[$tipo])){ header('Location: ../html/login/seguridad_login/superadmin.php?error=editar_registro'); exit; }

$nombres=trim((string)($_POST['nombres']??''));
$ap=trim((string)($_POST['apellidoPaterno']??''));
$am=trim((string)($_POST['apellidoMaterno']??''));
$rut=normalizeRut((string)($_POST['rut']??''));
$turno=strtoupper(trim((string)($_POST['turno']??'')));
$ubicacion=trim((string)($_POST['ubicacion']??''));
$patente=strtoupper(trim((string)($_POST['patente']??'')));
$horometro=trim((string)($_POST['horometro']??''));
$litros=trim((string)($_POST['litros']??''));
$observacion=trim((string)($_POST['observacion']??''));
$codigo=trim((string)($_POST['codigo']??''));
$codigoMaquinaria=trim((string)($_POST['codigoMaquinaria']??''));
$equipo=trim((string)($_POST['equipo']??''));
$kilometro=trim((string)($_POST['kilometro']??''));
$remananente=trim((string)($_POST['remanente']??'0'));

$baseOk=validText($nombres,100)&&validText($ap,100)&&validText($am,100)&&validText($rut,12)&&in_array($turno,['A','B','C','D'],true)&&validText($ubicacion,100)&&validText($patente,10)&&validDecimal($horometro,0,100000000)&&validDecimal($litros,0.01,1000000)&&mb_strlen($observacion)<=2000&&validarFormatoRut($rut)&&validarRut($rut)&&validGps($ubicacion)&&preg_match('/^[A-Z0-9 .-]{2,10}$/',$patente);
if(!$baseOk){ header('Location: ../html/login/seguridad_login/editar_registro.php?tipo='.urlencode($tipo).'&id='.$id.'&error=campos'); exit; }

$conn->begin_transaction();
try{
  $tabla=$tablas[$tipo];
  $st=$conn->prepare("SELECT rut,litros FROM {$tabla} WHERE id=? AND eliminado_at IS NULL LIMIT 1"); $st->bind_param('i',$id); $st->execute(); $actual=$st->get_result()->fetch_assoc();
  if(!$actual){ throw new RuntimeException('Registro no encontrado'); }

  if($tipo==='cargador'){
    if(!validText($codigo,50)||!validDecimal($remananente,0,100000000)) throw new RuntimeException('Campos inválidos');
    $stmt=$conn->prepare('UPDATE operador_cargador SET nombres=?,apellido_paterno=?,apellido_materno=?,rut=?,turno=?,codigo=?,ubicacion=?,patente=?,horometro=?,litros=?,remanente=?,observacion=? WHERE id=? AND eliminado_at IS NULL');
    $h=(float)$horometro;$l=(float)$litros;$r=(float)$remananente;
    $stmt->bind_param('ssssssssdddsi',$nombres,$ap,$am,$rut,$turno,$codigo,$ubicacion,$patente,$h,$l,$r,$observacion,$id);
    $stmt->execute();
    $tablaAudit='operador_cargador';
  }else{
    if(!validText($codigoMaquinaria,50)||!validText($equipo,100)||!validDecimal($kilometro,0,100000000)) throw new RuntimeException('Campos inválidos');
    $stmt=$conn->prepare('UPDATE operador SET nombres=?,apellido_paterno=?,apellido_materno=?,rut=?,turno=?,ubicacion=?,codigo_maquinaria=?,patente=?,equipo=?,horometro=?,kilometro=?,litros=?,observacion=? WHERE id=? AND eliminado_at IS NULL');
    $h=(float)$horometro;$km=(float)$kilometro;$l=(float)$litros;
    $stmt->bind_param('sssssssssdddsi',$nombres,$ap,$am,$rut,$turno,$ubicacion,$codigoMaquinaria,$patente,$equipo,$h,$km,$l,$observacion,$id);
    $stmt->execute();
    $tablaAudit='operador';
  }
  audit($conn,(int)$_SESSION['usuario_id'],'EDITAR_REGISTRO',$tablaAudit,$id,'RUT anterior '.$actual['rut'].' -> '.$rut.'; litros anteriores '.$actual['litros'].' -> '.$litros);
  $conn->commit();
  header('Location: ../html/login/seguridad_login/superadmin.php?success=registro_editado'); exit;
}catch(Throwable $e){
  $conn->rollback(); error_log('Error actualizando registro: '.$e->getMessage());
  header('Location: ../html/login/seguridad_login/editar_registro.php?tipo='.urlencode($tipo).'&id='.$id.'&error=guardar'); exit;
}
