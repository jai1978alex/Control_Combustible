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
   -------------------------------------- INICIO Jerez imprimir_rut.php ----------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

// Esta página muestra, lista para imprimir, toda la información de combustible de una persona según su RUT.

declare(strict_types=1);

// TÍTULO: ARCHIVOS QUE NECESITA ESTA PÁGINA

// Se cargan las herramientas de apoyo: la conexión a la base de datos, la seguridad y los cálculos de consumo.
require_once __DIR__.'/../../../backend/conexion.php';
require_once __DIR__.'/../../../backend/security.php';
require_once __DIR__.'/../../../backend/consumo_helper.php';

// TÍTULO: SOLO ADMINISTRADORES

// Si la persona no es administradora, no puede estar en esta página.
requireAdmin();

// Se pide al navegador que no guarde copia de esta página, para que siempre muestre datos actualizados.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// TÍTULO: QUÉ RUT SE QUIERE IMPRIMIR

// Se lee el RUT desde la dirección web. Si está vacío o es demasiado largo, se detiene todo con un aviso.
$rut=normalizeRut((string)($_GET['rut']??''));
if($rut===''||mb_strlen($rut)>12){
    http_response_code(400);
    exit('RUT no indicado.');
}

// TÍTULO: BUSCAR LOS REGISTROS

// Primero se buscan los registros de cargador de ese RUT, del más nuevo al más antiguo.
$stmt=$conn->prepare('SELECT oc.*,u.username FROM operador_cargador oc LEFT JOIN usuarios u ON oc.usuario_id=u.id WHERE oc.eliminado_at IS NULL AND oc.rut=? ORDER BY oc.fecha_registro DESC');
$stmt->bind_param('s',$rut);
$stmt->execute();
$cargadores=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Después se buscan los registros de operador de ese mismo RUT, también del más nuevo al más antiguo.
$stmt=$conn->prepare('SELECT o.*,u.username FROM operador o LEFT JOIN usuarios u ON o.usuario_id=u.id WHERE o.eliminado_at IS NULL AND o.rut=? ORDER BY o.fecha_registro DESC');
$stmt->bind_param('s',$rut);
$stmt->execute();
$operadores=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Si no hay ningún registro para ese RUT, se detiene todo con un aviso.
if(!$cargadores&&!$operadores){
    http_response_code(404);
    exit('No se encontró el RUT.');
}

// Se deja una anotación de quién imprimió este RUT, para tener un registro.
audit($conn,(int)$_SESSION['usuario_id'],'IMPRIMIR_RUT','',null,'RUT '.$rut);

// TÍTULO: AYUDAS PARA ARMAR LAS TABLAS

// Dibuja una fila de la tabla con un título a la izquierda y su dato a la derecha.
function fila(string $e1,mixed $v):void{
    echo '<tr><th>'.e($e1).'</th><td>'.e($v).'</td></tr>';
}

// Convierte una cantidad a un texto de litros, por ejemplo "1.250,50 L".
function n(mixed $v):string{
    return number_format((float)$v,2,',','.').' L';
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Registro RUT <?=e($rut)?></title>
    <link rel="stylesheet" href="../../../css/programa/imprimir_registro.css">
    <script src="../../../js/programa/imprimir.js" defer></script>
</head>

<!-- TÍTULO: CUERPO PRINCIPAL DE LA PÁGINA -->

<body>
    <!-- hoja que se verá al imprimir -->
    <div class="hoja">

<!-- TÍTULO: ENCABEZADO DE LA HOJA -->

        <!-- muestra el nombre del sistema y el RUT consultado -->
        <div class="cabecera">
            <div>
                <h1>Control de Combustible <?=e(appName())?></h1>
                <p>Detalle completo por RUT</p>
            </div>
            <div class="rut-principal">RUT: <strong><?=e($rut)?></strong></div>
        </div>

<!-- TÍTULO: REGISTROS DE OPERADOR CARGADOR -->

        <!-- esta parte solo aparece si la persona tiene registros como cargador -->
        <?php if($cargadores): ?>
        <section>
            <h2>Operador Cargador</h2>
            <!-- suma de todos los litros de estos registros -->
            <p><strong>Total acumulado: <?=n(array_sum(array_column($cargadores,'litros')))?></strong></p>

            <!-- por cada registro se arma una tabla con todos sus datos -->
            <?php foreach($cargadores as $r): ?>
            <table>
                <?php
                fila('Usuario que registró',$r['username']??'Sin usuario');
                fila('Nombres',$r['nombres']);
                fila('Apellido Paterno',$r['apellido_paterno']);
                fila('Apellido Materno',$r['apellido_materno']);
                fila('RUT',$r['rut']);
                fila('Turno',$r['turno']);
                fila('Código',$r['codigo']);
                fila('Ubicación GPS',$r['ubicacion']);
                fila('Patente',$r['patente']);
                fila('Horómetro',$r['horometro']);
                fila('Litros',n($r['litros']));
                fila('Remanente',n($r['remanente']));
                fila('Observación',$r['observacion']);
                fila('Fecha de registro',$r['fecha_registro']);
                ?>
            </table>
            <hr>
            <?php endforeach;?>
        </section>
        <?php endif;?>

<!-- TÍTULO: REGISTROS DE OPERADOR -->

        <!-- esta parte solo aparece si la persona tiene registros como operador -->
        <?php if($operadores): ?>
        <section>
            <h2>Operador</h2>
            <!-- suma de todos los litros de estos registros -->
            <p><strong>Total acumulado: <?=n(array_sum(array_column($operadores,'litros')))?></strong></p>

            <!-- por cada registro se arma una tabla con todos sus datos -->
            <?php foreach($operadores as $r): ?>
            <table>
                <?php
                fila('Usuario que registró',$r['username']??'Sin usuario');
                fila('Nombres',$r['nombres']);
                fila('Apellido Paterno',$r['apellido_paterno']);
                fila('Apellido Materno',$r['apellido_materno']);
                fila('RUT',$r['rut']);
                fila('Turno',$r['turno']);
                fila('Ubicación GPS',$r['ubicacion']);
                fila('Código Maquinaria',$r['codigo_maquinaria']);
                fila('Patente',$r['patente']);
                fila('Equipo',$r['equipo']);
                fila('Horómetro',$r['horometro']);
                fila('Kilómetro',$r['kilometro']);
                fila('Litros',n($r['litros']));
                fila('Consumo',number_format((float)$r['consumo'],2,',','.').' '.unidadConsumo($r));
                fila('Observación',$r['observacion']);
                fila('Fecha de registro',$r['fecha_registro']);
                ?>
            </table>
            <hr>
            <?php endforeach;?>
        </section>
        <?php endif;?>

<!-- TÍTULO: BOTONES DE IMPRESIÓN -->

        <!-- botón para imprimir la hoja y botón para cerrarla -->
        <div class="acciones-impresion"><button type="button" id="btnImprimir">🖨️ Imprimir</button><button type="button" class="secundario" id="btnCerrar">Cerrar</button></div>

    </div>
</body>

</html>
<?php



/* -------------------------------------------------------------------------------------------------------------
   --------------------------------------- FIN Jerez imprimir_rut.php ------------------------------------------
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