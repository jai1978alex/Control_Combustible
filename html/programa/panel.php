<?php
date_default_timezone_set('America/Santiago');
require_once("../../backend/check_user.php");
$csrf = csrfToken();
require_once("../../backend/conexion.php");

$totalLitrosCargador = 0.00;
$totalLitrosOperador = 0.00;

/*
 * Los totales deben excluir los registros eliminados lógicamente (igual
 * que el panel de administración, las exportaciones y la impresión).
 * Se comprueba la columna primero para no romper instalaciones antiguas
 * que aún no ejecutaron la migración de producción.
 */
$whereCargadorTotal = columnaExiste($conn, 'operador_cargador', 'eliminado_at') ? 'WHERE eliminado_at IS NULL' : '';
$whereOperadorTotal = columnaExiste($conn, 'operador', 'eliminado_at') ? 'WHERE eliminado_at IS NULL' : '';

$resultadoCargador = $conn->query("SELECT COALESCE(SUM(litros), 0) AS total FROM operador_cargador {$whereCargadorTotal}");
if ($resultadoCargador) {
    $filaCargador = $resultadoCargador->fetch_assoc();
    $totalLitrosCargador = (float)($filaCargador["total"] ?? 0);
}

$resultadoOperador = $conn->query("SELECT COALESCE(SUM(litros), 0) AS total FROM operador {$whereOperadorTotal}");
if ($resultadoOperador) {
    $filaOperador = $resultadoOperador->fetch_assoc();
    $totalLitrosOperador = (float)($filaOperador["total"] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Control de Combustible <?= e(appName()) ?></title>

    <!-- CSS -->
    <link rel="stylesheet" href="../../css/programa/style.css">
    <link rel="stylesheet" href="../../css/programa/panel.css">
    <link rel="stylesheet" href="../../css/programa/formulario.css">
    <link rel="stylesheet" href="../../css/programa/modal.css">

</head>

<body>
<div id="toastContainer"></div>


<!-- ================================================= -->
<!-- HEADER -->
<!-- ================================================= -->

<header class="app-header">

    <div class="header-container">

        <!-- LOGO + MENU -->
        <div class="header-logo-group">

            <div class="header-logo">

                <div class="logo-box">
                    <span class="logo-texto">
                        <?= e(appName()) ?>
                    </span>
                </div>

            </div>

            <!-- MENU -->
            <div class="menu-hamburguesa">

                <button id="btnMenuToggle" class="btn-menu">
                    ☰
                </button>

                <div id="menuDropdown" class="menu-dropdown">

                    <button id="btnQRScanner" class="menu-item">
                        📷 Escanear QR
                    </button>

                    <a href="cambiar_password.php" class="menu-item">
                        🔑 Cambiar contraseña
                    </a>

                    <form method="POST" action="../../backend/logout.php" class="logout-form">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                        <button type="submit" class="menu-item logout-link">🚪 Cerrar Sesión</button>
                    </form>

                </div>

            </div>

        </div>

        <!-- TITULO -->
        <div class="header-titulo">

            <h1>
                CONTROL DE COMBUSTIBLE
            </h1>

        </div>

        <!-- FECHA -->
        <div class="header-actions">

            <div class="datetime-display">

                <div class="date-texto">
                    <?php echo date("d/m/Y"); ?>
                </div>

                <div class="time-texto">
                    <?php echo date("H:i"); ?>
                </div>

            </div>

        </div>

    </div>

</header>



<!-- ================================================= -->
<!-- MAIN -->
<!-- ================================================= -->

<main class="main-content">



    <!-- ================================================= -->
    <!-- FORMULARIO CARGADOR -->
    <!-- ================================================= -->

    <form method="POST"
          action="../../backend/guardar_cargador.php"
          class="form-card formulario-confirmar"
          id="formCargador">

        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

        <h2 class="form-titulo form-titulo-blue">
            Operador Cargador
        </h2>

        <!-- TOTAL -->
        <div class="total-box total-blue">
            Total Litros Cargador:
            <span id="totalLitrosCargador" data-base-total="<?php echo number_format($totalLitrosCargador, 2, ".", ""); ?>"><?php echo number_format($totalLitrosCargador, 2, ".", ""); ?></span> L
        </div>



        <!-- NOMBRES -->
        <div class="form-grid grid-3">

            <div class="form-group">
                <label class="form-label">Nombres *</label>

                <input
                    type="text"
                    name="nombres"
                    class="form-input"
                    required>
            </div>

            <div class="form-group">
                <label class="form-label">Apellido Paterno *</label>

                <input
                    type="text"
                    name="apellidoPaterno"
                    class="form-input"
                    required>
            </div>

            <div class="form-group">
                <label class="form-label">Apellido Materno *</label>

                <input
                    type="text"
                    name="apellidoMaterno"
                    class="form-input"
                    required>
            </div>

        </div>



        <!-- RUT -->
        <div class="form-grid grid-2">

            <div class="form-group">

                <label class="form-label">
                    RUT *
                </label>

                <input
                    type="text"
                    name="rut"
                    id="rutCargador"
                    class="form-input"
                    placeholder="12.345.678-9"
                    maxlength="12"
                    required>

                <small id="rutErrorCargador"
                       class="error-texto"></small>

            </div>

            <div class="form-group">

                <label class="form-label">
                    Turno
                </label>

                <select
                    name="turno"
                    class="form-input"
                    required>

                    <option value="">Seleccione</option>

                    <option>A</option>
                    <option>B</option>
                    <option>C</option>
                    <option>D</option>

                </select>

            </div>

        </div>



        <!-- CODIGO -->
        <div class="form-grid grid-2">

            <div class="form-group">

                <label class="form-label">
                    Código
                </label>

                <input
                    type="text"
                    name="codigo"
                    class="form-input"
                    required>

            </div>

            <div class="form-group">

                <label class="form-label">
                    Ubicación GPS
                </label>

                <div class="input-with-button">

                    <input
                        type="text"
                        name="ubicacion"
                        id="ubicacionCargador"
                        class="form-input"
                        placeholder="Obtener ubicación"
                        readonly
                        required>

                    <button
                        type="button"
                        class="btn-location btn-blue"
                        data-gps-target="ubicacionCargador"
                        aria-label="Obtener ubicación GPS del cargador"
                        title="Obtener ubicación GPS">

                        📍

                    </button>

                </div>

            </div>

        </div>



        <!-- EQUIPO -->
        <div class="form-grid grid-3">

            <div class="form-group">

                <label class="form-label">
                    Patente
                </label>

                <input
                    type="text"
                    name="patente"
                    class="form-input"
                    required>

            </div>

            <div class="form-group">

                <label class="form-label">
                    Horómetro
                </label>

                <input
                    type="number"
                    step="0.01"
                    name="horometro"
                    class="form-input"
                    required>

            </div>

            <div class="form-group">

                <label class="form-label">
                    Litros
                </label>

                <input
                    type="number"
                    step="0.01"
                    name="litros"
                    class="form-input litros-cargador"
                    required>

            </div>

        </div>



        <!-- OBS -->
        <div class="form-group">

            <label class="form-label">
                Observación
            </label>

            <textarea
                name="observacion"
                class="form-textarea"
                placeholder="Ingrese observación"></textarea>

        </div>



        <!-- BOTON -->
        <div class="form-actions">

            <button
                type="button"
                class="btn-submit btn-submit-blue btnAbrirModal">

                Guardar Registro

            </button>

        </div>

    </form>





    <!-- ================================================= -->
    <!-- FORMULARIO OPERADOR -->
    <!-- ================================================= -->

    <form method="POST"
          action="../../backend/guardar_operador.php"
          class="form-card formulario-confirmar"
          id="formOperador">

        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

        <h2 class="form-titulo form-titulo-green">
            Datos de Operador
        </h2>

        <div class="total-box total-green">
            Total Litros Operador:
            <span id="totalLitrosOperador" data-base-total="<?php echo number_format($totalLitrosOperador, 2, ".", ""); ?>"><?php echo number_format($totalLitrosOperador, 2, ".", ""); ?></span> L
        </div>



        <!-- NOMBRES -->
        <div class="form-grid grid-3">

            <div class="form-group">

                <label class="form-label">
                    Nombres
                </label>

                <input
                    type="text"
                    name="nombres"
                    class="form-input"
                    required>

            </div>

            <div class="form-group">

                <label class="form-label">
                    Apellido Paterno
                </label>

                <input
                    type="text"
                    name="apellidoPaterno"
                    class="form-input"
                    required>

            </div>

            <div class="form-group">

                <label class="form-label">
                    Apellido Materno
                </label>

                <input
                    type="text"
                    name="apellidoMaterno"
                    class="form-input"
                    required>

            </div>

        </div>



        <!-- RUT -->
        <div class="form-grid grid-2">

            <div class="form-group">

                <label class="form-label">
                    RUT *
                </label>

                <input
                    type="text"
                    name="rut"
                    id="rutOperador"
                    class="form-input"
                    placeholder="12.345.678-9"
                    maxlength="12"
                    required>

                <small id="rutErrorOperador"
                       class="error-texto"></small>

            </div>

            <div class="form-group">

                <label class="form-label">
                    Turno
                </label>

                <select
                    name="turno"
                    class="form-input"
                    required>

                    <option value="">Seleccione</option>

                    <option>A</option>
                    <option>B</option>
                    <option>C</option>
                    <option>D</option>

                </select>

            </div>

        </div>



        <!-- GPS -->
        <div class="form-group">

            <label class="form-label">
                Ubicación GPS
            </label>

            <div class="input-with-button">

                <input
                    type="text"
                    name="ubicacion"
                    id="ubicacionOperador"
                    class="form-input"
                    placeholder="Obtener ubicación"
                    readonly
                    required>

                <button
                    type="button"
                    class="btn-location btn-green"
                    data-gps-target="ubicacionOperador"
                    aria-label="Obtener ubicación GPS del operador"
                    title="Obtener ubicación GPS">

                    📍

                </button>

            </div>

        </div>



        <!-- EQUIPO -->
        <div class="form-grid grid-3">

            <div class="form-group">

                <label class="form-label">
                    Código Maquinaria
                </label>

                <input
                    type="text"
                    name="codigoMaquinaria"
                    class="form-input"
                    required>

            </div>

            <div class="form-group">

                <label class="form-label">
                    Patente
                </label>

                <input
                    type="text"
                    name="patente"
                    class="form-input"
                    required>

            </div>

            <div class="form-group">

                <label class="form-label">
                    Equipo
                </label>

                <input
                    type="text"
                    name="equipo"
                    class="form-input"
                    required>

            </div>

        </div>



        <!-- DATOS -->
        <div class="form-grid grid-3">

            <div class="form-group">

                <label class="form-label">
                    Horómetro
                </label>

                <input
                    type="number"
                    name="horometro"
                    class="form-input">

            </div>

            <div class="form-group">

                <label class="form-label">
                    Kilómetro
                </label>

                <input
                    type="number"
                    name="kilometro"
                    class="form-input">

            </div>

            <div class="form-group">

                <label class="form-label">
                    Litros
                </label>

                <input
                    type="number"
                    step="0.01"
                    name="litros"
                    class="form-input litros-operador"
                    required>

            </div>

        </div>



        <!-- OBS -->
        <div class="form-group">

            <label class="form-label">
                Observación
            </label>

            <textarea
                name="observacion"
                class="form-textarea"
                placeholder="Ingrese observación"></textarea>

        </div>



        <!-- BOTON -->
        <div class="form-actions">

            <button
               type="button"
               class="btn-submit btn-submit-green btnAbrirModal">

                Guardar Registro

            </button>

        </div>

    </form>

</main>



    
<!-- ================================================= -->
<!-- MODAL ESCANEO QR -->
<!-- ================================================= -->
<div class="qr-scanner-modal" id="qrScannerModal" aria-hidden="true">
    <div class="qr-scanner-box">
        <div class="qr-scanner-header">
            <h2>Escanear código QR</h2>
            <button type="button" id="qrModalClose" class="qr-close" aria-label="Cerrar">&times;</button>
        </div>
        <p id="qrScannerStatus" class="qr-scanner-status">Apunta la cámara al código QR.</p>
        <div class="qr-video-wrap">
            <div id="qr-reader"></div>
        </div>
        <canvas id="qr-canvas" hidden></canvas>
        <input type="file" id="qrImageInput" accept="image/*" capture="environment" class="qr-image-input">
        <p class="qr-help">El QR debe contener los datos del trabajador. Puedes usar JSON, por ejemplo: <code>{"rut":"12.345.678-9","nombres":"Juan"}</code>.</p>
    </div>
</div>

<!-- ================================================= -->
<!-- MODAL CONFIRMACION -->
<!-- ================================================= -->

<div class="modal-confirmacion" id="modalConfirmacion">

    <div class="modal-box">

        <h2>
            Confirmar Registro
        </h2>

        <div id="contenidoConfirmacion"></div>

        <div class="modal-actions">

            <button
                type="button"
                id="btnEditar"
                class="btn-modal btn-cancelar">

                Editar

            </button>

            <button
                type="button"
                id="btnConfirmar"
                class="btn-modal btn-confirmar">

                Confirmar

            </button>

        </div>

    </div>

</div>

<!-- JavaScript externo: orden de dependencias -->
<script src="../../js/programa/toast.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="../../js/programa/qr-scanner.js?v=6"></script>
<script src="../../js/programa/validador_rut.js"></script>
<script src="../../js/programa/modal-confirmacion.js"></script>
<script src="../../js/programa/panel.js?v=3"></script>

</body>
</html>