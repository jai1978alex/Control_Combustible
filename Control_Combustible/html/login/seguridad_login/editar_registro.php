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
   ------------------------------------ INICIO Jerez editar_registro.php ---------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

// Esta página le permite al administrador corregir un registro de combustible ya guardado.

declare(strict_types=1);

// TÍTULO: ARCHIVOS QUE NECESITA ESTA PÁGINA

// Se cargan las herramientas de apoyo: la conexión a la base de datos y la seguridad.
require_once __DIR__.'/../../../backend/conexion.php';
require_once __DIR__.'/../../../backend/security.php';

// TÍTULO: SOLO ADMINISTRADORES

// Si la persona no es administradora, no puede estar en esta página.
requireAdmin();

// TÍTULO: QUÉ REGISTRO SE QUIERE EDITAR

// Se leen desde la dirección web el tipo de registro (cargador u operador) y su número.
$tipo=(string)($_GET['tipo']??'');
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);

// Lista de los tipos de registro permitidos y el lugar donde se guarda cada uno.
$tablas=['cargador'=>'operador_cargador','operador'=>'operador'];

// Si falta el número o el tipo no es válido, se detiene todo con un aviso.
if(!$id || !isset($tablas[$tipo])){
    http_response_code(400);
    exit('Solicitud inválida.');
}
$tabla=$tablas[$tipo];

// TÍTULO: BUSCAR EL REGISTRO

// Se busca en la base de datos el registro que se quiere editar (si no fue eliminado).
$st=$conn->prepare("SELECT * FROM {$tabla} WHERE id=? AND eliminado_at IS NULL LIMIT 1");
$st->bind_param('i',$id);
$st->execute();
$r=$st->get_result()->fetch_assoc();

// Si el registro no existe, se detiene todo con un aviso.
if(!$r){
    http_response_code(404);
    exit('Registro no encontrado.');
}

// Se prepara el código de seguridad que viajará escondido dentro del formulario.
$csrf=csrfToken();

// Pequeña ayuda para mostrar los datos de forma segura dentro del formulario.
function v($x):string{
    return e($x);
}

// TÍTULO: MENSAJES DE ERROR

// Textos que se muestran cuando algo sale mal al guardar los cambios.
$mensajesError=[
    'campos' => '✖ Revisa los datos ingresados: hay campos obligatorios vacíos o con un formato inválido (RUT, ubicación GPS o patente).',
    'guardar' => '✖ No fue posible guardar los cambios. Intenta nuevamente.',
];

// Se revisa si la dirección web trae algún código de error.
$errorCode=(string)($_GET['error']??'');
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Editar registro - <?=e(appName())?></title>
    <link rel="stylesheet" href="../../../css/programa/superadmin.css">
    <link rel="stylesheet" href="../../../css/programa/formulario.css">
</head>

<!-- TÍTULO: CUERPO PRINCIPAL DE LA PÁGINA -->

<body>
    <!-- contenedor principal de la página -->
    <main class="admin-container">
        <!-- tarjeta que contiene el formulario -->
        <section class="busqueda-card">

            <!-- título principal y explicación -->
            <h1>Editar registro de combustible</h1>
            <p>Los cambios quedan registrados en la auditoría.</p>

<!-- TÍTULO: MENSAJE DE ERROR -->

            <!-- aviso rojo que aparece solo si hubo un problema al guardar -->
            <?php if($errorCode!=='' && isset($mensajesError[$errorCode])):?><div class="mensaje-admin error"><?=e($mensajesError[$errorCode])?></div><?php endif;?>

<!-- TÍTULO: FORMULARIO PARA EDITAR EL REGISTRO -->

            <form method="post" action="../../../backend/actualizar_registro.php" class="form-card">

                <!-- datos ocultos: protección del envío, tipo de registro y número del registro -->
                <input type="hidden" name="csrf_token" value="<?=v($csrf)?>">
                <input type="hidden" name="tipo" value="<?=v($tipo)?>">
                <input type="hidden" name="id" value="<?=v($id)?>">

                <!-- fila 1: nombre completo de la persona -->
                <div class="form-grid grid-3">
                    <div class="form-group">
                        <label class="form-label">Nombres *</label><input class="form-input" name="nombres" maxlength="100" required value="<?=v($r['nombres'])?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Apellido Paterno *</label><input class="form-input" name="apellidoPaterno" maxlength="100" required value="<?=v($r['apellido_paterno'])?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Apellido Materno *</label><input class="form-input" name="apellidoMaterno" maxlength="100" required value="<?=v($r['apellido_materno'])?>">
                    </div>
                </div>

                <!-- fila 2: RUT y turno de trabajo -->
                <div class="form-grid grid-2">
                    <div class="form-group">
                        <label class="form-label">RUT *</label><input class="form-input" name="rut" maxlength="12" required value="<?=v($r['rut'])?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Turno *</label><select class="form-input" name="turno" required>
                            <!-- lista de turnos; queda marcado el turno que ya tenía el registro -->
                            <?php foreach(['A','B','C','D'] as $t):?>
                            <option value="<?=$t?>" <?=$r['turno']===$t?'selected':''?>><?=$t?></option>
                            <?php endforeach;?>
                        </select>
                    </div>
                </div>

                <!-- fila 3: ubicación y patente -->
                <div class="form-grid grid-2">
                    <div class="form-group">
                        <label class="form-label">Ubicación GPS *</label><input class="form-input" name="ubicacion" required value="<?=v($r['ubicacion'])?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Patente *</label><input class="form-input" name="patente" maxlength="10" required value="<?=v($r['patente'])?>">
                    </div>
                </div>

                <!-- fila 4: los campos cambian según si es un registro de cargador o de operador -->
                <div class="form-grid grid-3">

                    <!-- si el registro es de un CARGADOR, se muestran estos campos -->
                    <?php if($tipo==='cargador'):?>
                    <div class="form-group">
                        <label class="form-label">Código *</label><input class="form-input" name="codigo" maxlength="50" required value="<?=v($r['codigo'])?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Horómetro *</label><input class="form-input" name="horometro" required value="<?=v($r['horometro'])?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Litros *</label><input class="form-input" name="litros" required value="<?=v($r['litros'])?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Remanente</label><input class="form-input" name="remanente" value="<?=v($r['remanente'])?>">
                    </div>

                    <!-- si el registro es de un OPERADOR, se muestran estos otros campos -->
                    <?php else:?>
                    <div class="form-group">
                        <label class="form-label">Código maquinaria *</label><input class="form-input" name="codigoMaquinaria" maxlength="50" required value="<?=v($r['codigo_maquinaria'])?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Equipo *</label><input class="form-input" name="equipo" maxlength="100" required value="<?=v($r['equipo'])?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Horómetro *</label><input class="form-input" name="horometro" required value="<?=v($r['horometro'])?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kilómetro *</label><input class="form-input" name="kilometro" required value="<?=v($r['kilometro'])?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Litros *</label><input class="form-input" name="litros" required value="<?=v($r['litros'])?>">
                    </div>
                    <?php endif;?>

                </div>

                <!-- campo de texto libre para escribir una observación -->
                <div class="form-group">
                    <label class="form-label">Observación</label><textarea class="form-input" name="observacion" maxlength="2000" rows="5"><?=v($r['observacion'])?></textarea>
                </div>

<!-- TÍTULO: BOTONES DE ACCIÓN -->

                <!-- botón para guardar los cambios y botón para cancelar y volver -->
                <div class="acciones-registro">
                    <button class="btn btn-buscar" type="submit">💾 Guardar cambios</button><a class="btn btn-limpiar" href="superadmin.php">Cancelar</a>
                </div>

            </form>
        </section>
    </main>
</body>

</html>
<?php



/* -------------------------------------------------------------------------------------------------------------
   ------------------------------------- FIN Jerez editar_registro.php -----------------------------------------
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