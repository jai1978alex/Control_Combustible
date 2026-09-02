<!--
Sitio Web Creado por Jerez.
Direccion: Miraflorez #1280
Quintero - Chile
jaime.jerez1978@gmail.com
https://www.
Creado, Programado y Diseñado por Jerez.
JJA 
-->

<!-- -------------------------------------------------------------------------------------------------------------
   ------------------------------------- INICIO Jerez panel.php ------------------------------------
   ------------------------------------------------------------------------------------------------------------- -->



<?php
    // Define la zona horaria de Chile.
    date_default_timezone_set('America/Santiago');
    // Carga el archivo que necesita esta página.
    require_once("../../backend/check_user.php");
    // Guarda el código de seguridad del formulario.
    $csrf = csrfToken();
    // Carga el archivo que necesita esta página.
    require_once("../../backend/conexion.php");
    // Prepara el total de litros del cargador.
    $totalLitrosCargador = 0.00;
    // Prepara el total de litros del operador.
    $totalLitrosOperador = 0.00;
    // Define qué registros se consideran para el total del cargador.
    $whereCargadorTotal = columnaExiste($conn, 'operador_cargador', 'eliminado_at') ? 'WHERE eliminado_at IS NULL' : '';
    // Define qué registros se consideran para el total del operador.
    $whereOperadorTotal = columnaExiste($conn, 'operador', 'eliminado_at') ? 'WHERE eliminado_at IS NULL' : '';
    // Define qué registros se consideran para el total del cargador.
    $resultadoCargador = $conn->query("SELECT COALESCE(SUM(litros), 0) AS total FROM operador_cargador {$whereCargadorTotal}");
    // Busca la suma de litros del cargador.
    if ($resultadoCargador) {
        // Busca la suma de litros del cargador.
        $filaCargador = $resultadoCargador->fetch_assoc();
        // Prepara el total de litros del cargador.
        $totalLitrosCargador = (float)($filaCargador["total"] ?? 0);
    // Cierra este bloque.
    }

    // Define qué registros se consideran para el total del operador.
    $resultadoOperador = $conn->query("SELECT COALESCE(SUM(litros), 0) AS total FROM operador {$whereOperadorTotal}");
    // Busca la suma de litros del operador.
    if ($resultadoOperador) {
        // Busca la suma de litros del operador.
        $filaOperador = $resultadoOperador->fetch_assoc();
        // Prepara el total de litros del operador.
        $totalLitrosOperador = (float)($filaOperador["total"] ?? 0);
    // Cierra este bloque.
    }
?>


<!-- TÍTULO: INICIO DE LA PÁGINA HTML -->


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Combustible <?= e(appName()) ?></title>    
    <link rel="stylesheet" href="../../css/programa/style.css">    
    <link rel="stylesheet" href="../../css/programa/panel.css">    
    <link rel="stylesheet" href="../../css/programa/formulario.css">    
    <link rel="stylesheet" href="../../css/programa/modal.css">
</head>

<!-- Inicia el contenido visible de la página. -->
    <body>
        <!-- Deja un espacio para mostrar avisos. -->
        <div id="toastContainer"></div>
        <!-- Inicia la parte de configuración de la página. -->
        <header class="app-header">
            <!-- Contenedor principal del encabezado. -->
            <div class="header-container">
                <!-- LOGO + MENU -->
                <!-- Agrupa el logo y el menú. -->
                <div class="header-logo-group">
                    <!-- Muestra el área del logo. -->
                    <div class="header-logo">
                        <!-- Caja donde se muestra el nombre del sistema. -->
                        <div class="logo-box">
                            <!-- Define esta parte de la página. -->
                            <span class="logo-texto">
                                <!-- Muestra el nombre del sistema. -->
                                <?= e(appName()) ?>
                            <!-- Define esta parte de la página. -->
                            </span>
                        </div>
                    </div>

<!-- TÍTULO: MENU -->

                    <!-- Contiene el menú de opciones. -->
                    <div class="menu-hamburguesa">
                        <!-- Botón para abrir o cerrar el menú. -->
                        <button id="btnMenuToggle" class="btn-menu">
                            <!-- Define esta parte de la página. -->
                            ☰
                        <!-- Termina el botón. -->
                        </button>
                        <!-- Lista de opciones del menú. -->
                        <div id="menuDropdown" class="menu-dropdown">
                            <!-- Botón para abrir el lector QR. -->
                            <button id="btnQRScanner" class="menu-item">
                                <!-- Define esta parte de la página. -->
                                📷 Escanear QR
                            
                            </button>

                            <!-- Enlace para cambiar la contraseña. -->
                            <a href="cambiar_password.php" class="menu-item">
                                <!-- Define esta parte de la página. -->
                                🔑 Cambiar contraseña
                            </a>

                            <!-- Formulario para cerrar la sesión. -->
                            <form method="POST" action="../../backend/logout.php" class="logout-form">
                                <!-- Envía el código de seguridad. -->
                                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                <!-- Crea un botón. -->
                                <button type="submit" class="menu-item logout-link">🚪 Cerrar Sesión</button>
                            </form>
                        </div>
                    </div>
                </div>

<!-- TÍTULO: TITULO PRINCIPAL -->

                <!-- Muestra el título principal. -->
                <div class="header-titulo">
                    <!-- Muestra el título de la página. -->
                    <h1>
                        <!-- Define esta parte de la página. -->
                        CONTROL DE COMBUSTIBLE
                    </h1>
                </div>

<!--TÍTULO: FECHA -->

                <!-- Contiene las acciones del encabezado. -->
                <div class="header-actions">
                    <!-- Muestra la fecha y la hora. -->
                    <div class="datetime-display">
                        <!-- Inicia un bloque de contenido. -->
                        <div class="date-texto">
                            <?php echo date("d/m/Y"); ?>
                        </div>

                        <!-- Inicia un bloque de contenido. -->
                        <div class="time-texto">
                            <?php echo date("H:i"); ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>


<!-- TÍTULO: MAIN -->
        
        <!-- Inicia el contenido principal. -->
        <main class="main-content">

            
<!-- TÍTULO: FORMULARIO CARGADOR -->
            
            <!-- Inicia un formulario. -->
            <form method="POST"
                action="../../backend/guardar_cargador.php"
                class="form-card formulario-confirmar"
                id="formCargador">
                <!-- Envía el código de seguridad. -->
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <!-- Muestra el título de esta sección. -->
                <h2 class="form-titulo form-titulo-blue">
                    <!-- Define esta parte de la página. -->
                    Operador Cargador
                <!-- Define esta parte de la página. -->
                </h2>

<!-- TÍTULO: TOTAL -->

                <!-- Muestra el total de litros. -->
                <div class="total-box total-blue">
                    <!-- Define esta parte de la página. -->
                    Total Litros Cargador:
                    <!-- Muestra el total de litros del cargador. -->
                    <span id="totalLitrosCargador" data-base-total="<?php echo number_format($totalLitrosCargador, 2, ".", ""); ?>"><?php echo number_format($totalLitrosCargador, 2, ".", ""); ?></span> L
                <!-- Termina el bloque de contenido. -->
                </div>

<!-- TÍTULO: NOMBRES -->

                <!-- Inicia un bloque de contenido. -->
                <div class="form-grid grid-3">
                    <!-- Inicia un bloque de contenido. -->
                    <div class="form-group">
                        <!-- Muestra el nombre del campo. -->
                        <label class="form-label">Nombres *</label>
                        <!-- Crea un campo para ingresar datos. -->
                        <input
                            type="text"
                            name="nombres"
                            class="form-input"
                            required>
                        </input>    
                    </div>

                    <!-- Inicia un bloque de contenido. -->
                    <div class="form-group">
                        <!-- Muestra el nombre del campo. -->
                        <label class="form-label">Apellido Paterno *</label>
                        <!-- Crea un campo para ingresar datos. -->
                        <input
                            type="text"
                            name="apellidoPaterno"
                            class="form-input"
                            required>
                        </input>    
                    </div>

                    <!-- Inicia un bloque de contenido. -->
                    <div class="form-group">
                        <!-- Muestra el nombre del campo. -->
                        <label class="form-label">Apellido Materno *</label>
                        <!-- Crea un campo para ingresar datos. -->
                        <input
                            type="text"
                            name="apellidoMaterno"
                            class="form-input"
                            required>
                        </input>    
                    </div>
                </div>


<!-- TÍTULO: RUT -->

                <!-- Inicia un bloque de contenido. -->
                <div class="form-grid grid-2">
                    <!-- Inicia un bloque de contenido. -->
                    <div class="form-group">
                        <!-- Muestra el nombre del campo. -->
                        <label class="form-label">
                            <!-- Define esta parte de la página. -->
                            RUT *
                        </label>

                        <!-- Crea un campo para ingresar datos. -->
                        <input
                            type="text"
                            name="rut"
                            id="rutCargador"
                            class="form-input"
                            placeholder="12.345.678-9"
                            maxlength="12"
                            required>
                        </input>    

                        <!-- Define esta parte de la página. -->
                        <small id="rutErrorCargador"
                            class="error-texto">
                        </small>
                    </div>

                    <!-- Inicia un bloque de contenido. -->
                    <div class="form-group">
                        <!-- Muestra el nombre del campo. -->
                        <label class="form-label">
                            <!-- Define esta parte de la página. -->
                            Turno
                        </label>

                        <!-- Crea una lista para elegir una opción. -->
                        <select
                            name="turno"
                            class="form-input"
                            required>
                            <!-- Agrega una opción para elegir. -->
                            <option value="">Seleccione</option>
                            <!-- Agrega una opción para elegir. -->
                            <option>A</option>
                            <!-- Agrega una opción para elegir. -->
                            <option>B</option>
                            <!-- Agrega una opción para elegir. -->
                            <option>C</option>
                            <!-- Agrega una opción para elegir. -->
                            <option>D</option>
                        </select>
                    </div>
                </div>


<!-- TÍTULO: CODIGO -->

                <!-- Inicia un bloque de contenido. -->
                <div class="form-grid grid-2">
                    <!-- Inicia un bloque de contenido. -->
                    <div class="form-group">
                        <!-- Muestra el nombre del campo. -->
                        <label class="form-label">
                            <!-- Define esta parte de la página. -->
                            Código
                        </label>

                        <!-- Crea un campo para ingresar datos. -->
                        <input
                            type="text"
                            name="codigo"
                            class="form-input"
                            required>
                        </input>    
                    </div>

                    <!-- Inicia un bloque de contenido. -->
                    <div class="form-group">
                        <!-- Muestra el nombre del campo. -->
                        <label class="form-label">
                            <!-- Define esta parte de la página. -->
                            Ubicación GPS
                        </label>

                        <!-- Inicia un bloque de contenido. -->
                        <div class="input-with-button">
                            <!-- Crea un campo para ingresar datos. -->
                            <input
                                type="text"
                                name="ubicacion"
                                id="ubicacionCargador"
                                class="form-input"
                                placeholder="Obtener ubicación"
                                readonly
                                required>
                            </input>    

                            <!-- Crea un botón. -->
                            <button
                                type="button"
                                class="btn-location btn-blue"
                                data-gps-target="ubicacionCargador"
                                aria-label="Obtener ubicación GPS del cargador"
                                title="Obtener ubicación GPS">
                                <!-- Define esta parte de la página. -->
                                📍
                            </button>
                        </div>
                    </div>
                </div>


<!-- TÏTULO: EQUIPO -->

                <!-- Inicia un bloque de contenido. -->
                <div class="form-grid grid-3">
                    <!-- Inicia un bloque de contenido. -->
                    <div class="form-group">
                        <!-- Muestra el nombre del campo. -->
                        <label class="form-label">
                            <!-- Define esta parte de la página. -->
                            Patente
                        </label>

                        <!-- Crea un campo para ingresar datos. -->
                        <input
                            type="text"
                            name="patente"
                            class="form-input"
                            required>
                        </input>    
                    </div>

                    <!-- Inicia un bloque de contenido. -->
                    <div class="form-group">
                        <!-- Muestra el nombre del campo. -->
                        <label class="form-label">
                            <!-- Define esta parte de la página. -->
                            Horómetro
                        </label>

                        <!-- Crea un campo para ingresar datos. -->
                        <input
                            type="number"
                            step="0.01"
                            name="horometro"
                            class="form-input"
                            required>
                        </input>    
                    </div>

                    <!-- Inicia un bloque de contenido. -->
                    <div class="form-group">
                        <!-- Muestra el nombre del campo. -->
                        <label class="form-label">
                            <!-- Define esta parte de la página. -->
                            Litros
                        <!-- Define esta parte de la página. -->
                        </label>

                        <!-- Crea un campo para ingresar datos. -->
                        <input
                            type="number"
                            step="0.01"
                            name="litros"
                            class="form-input litros-cargador"
                            required>
                        </input>    
                    </div>
                </div>



<!-- TITULO: OBSERVACIÓN -->

                <!-- Inicia un bloque de contenido. -->
                <div class="form-group">
                    <!-- Muestra el nombre del campo. -->
                    <label class="form-label">
                        <!-- Define esta parte de la página. -->
                        Observación
                    </label>

                    <!-- Crea un espacio para escribir una observación. -->
                    <textarea
                        name="observacion"
                        class="form-textarea"
                        placeholder="Ingrese observación">
                    </textarea>
                </div>


<!-- TITULO: BOTON -->

                <!-- Inicia un bloque de contenido. -->
                <div class="form-actions">
                    <!-- Crea un botón. -->
                    <button
                        type="button"
                        class="btn-submit btn-submit-blue btnAbrirModal">
                        Guardar Registro
                    </button>
                </div>
            </form>

            
<!-- TITULO: FORMULARIO OPERADOR -->
            
            <!-- Inicia un formulario. -->
            <form method="POST"
                action="../../backend/guardar_operador.php"
                class="form-card formulario-confirmar"
                id="formOperador">
                <!-- Envía el código de seguridad. -->
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <!-- Muestra el título de esta sección. -->
                <h2 class="form-titulo form-titulo-green">
                    <!-- Define esta parte de la página. -->
                    Datos de Operador
                </h2>

                <!-- Muestra el total de litros. -->
                <div class="total-box total-green">
                    <!-- Define esta parte de la página. -->
                    Total Litros Operador:
                    <!-- Muestra el total de litros del operador. -->
                    <span id="totalLitrosOperador" data-base-total="<?php echo number_format($totalLitrosOperador, 2, ".", ""); ?>"><?php echo number_format($totalLitrosOperador, 2, ".", ""); ?></span> L
                </div>


<!-- TITULO: NOMBRES -->

                <!-- Inicia un bloque de contenido. -->
                <div class="form-grid grid-3">
                    <!-- Inicia un bloque de contenido. -->
                    <div class="form-group">
                        <!-- Muestra el nombre del campo. -->
                        <label class="form-label">
                            <!-- Define esta parte de la página. -->
                            Nombres
                        </label>

                        <!-- Crea un campo para ingresar datos. -->
                        <input
                            type="text"
                            name="nombres"
                            class="form-input"
                            required>
                        </input>    
                    </div>

                    <!-- Inicia un bloque de contenido. -->
                    <div class="form-group">
                        <!-- Muestra el nombre del campo. -->
                        <label class="form-label">
                            <!-- Define esta parte de la página. -->
                            Apellido Paterno
                        <!-- Define esta parte de la página. -->
                        </label>

                        <!-- Crea un campo para ingresar datos. -->
                        <input
                            type="text"
                            name="apellidoPaterno"
                            class="form-input"
                            required>
                        </input>    
                    </div>

                    <!-- Inicia un bloque de contenido. -->
                    <div class="form-group">
                        <!-- Muestra el nombre del campo. -->
                        <label class="form-label">
                            <!-- Define esta parte de la página. -->
                            Apellido Materno
                        </label>

                        <!-- Crea un campo para ingresar datos. -->
                        <input
                            type="text"
                            name="apellidoMaterno"
                            class="form-input"
                            required>
                        </input>    
                    </div>
                </div>

<!-- TITULO: RUT -->

                <!-- Inicia un bloque de contenido. -->
                <div class="form-grid grid-2">
                    <!-- Inicia un bloque de contenido. -->
                    <div class="form-group">
                        <!-- Muestra el nombre del campo. -->
                        <label class="form-label">
                            <!-- Define esta parte de la página. -->
                            RUT *
                        </label>

                        <!-- Crea un campo para ingresar datos. -->
                        <input
                            type="text"
                            name="rut"
                            id="rutOperador"
                            class="form-input"
                            placeholder="12.345.678-9"
                            maxlength="12"
                            required>
                        </input>
                        <!-- Define esta parte de la página. -->
                        <small id="rutErrorOperador"
                            class="error-texto">
                        </small>
                    </div>

                    <!-- Inicia un bloque de contenido. -->
                    <div class="form-group">
                        <!-- Muestra el nombre del campo. -->
                        <label class="form-label">
                            <!-- Define esta parte de la página. -->
                            Turno
                        </label>

                        <!-- Crea una lista para elegir una opción. -->
                        <select
                            name="turno"
                            class="form-input"
                            required>
                            <!-- Agrega una opción para elegir. -->
                            <option value="">Seleccione</option>
                            <!-- Agrega una opción para elegir. -->
                            <option>A</option>
                            <!-- Agrega una opción para elegir. -->
                            <option>B</option>
                            <!-- Agrega una opción para elegir. -->
                            <option>C</option>
                            <!-- Agrega una opción para elegir. -->
                            <option>D</option>
                        </select>
                    </div>
                </div>


<!-- TITULO: GPS -->

                <!-- Inicia un bloque de contenido. -->
                <div class="form-group">
                    <!-- Muestra el nombre del campo. -->
                    <label class="form-label">
                        <!-- Define esta parte de la página. -->
                        Ubicación GPS
                    </label>

                    <!-- Inicia un bloque de contenido. -->
                    <div class="input-with-button">
                        <!-- Crea un campo para ingresar datos. -->
                        <input
                            type="text"
                            name="ubicacion"
                            id="ubicacionOperador"
                            class="form-input"
                            placeholder="Obtener ubicación"
                            readonly
                            required>
                        </input>
                        <!-- Crea un botón. -->
                        <button
                            type="button"
                            class="btn-location btn-green"
                            data-gps-target="ubicacionOperador"
                            aria-label="Obtener ubicación GPS del operador"
                            title="Obtener ubicación GPS">
                            <!-- Define esta parte de la página. -->
                            📍
                        </button>
                    </div>
                </div>



<!-- TITULO: EQUIPO -->

                <!-- Inicia un bloque de contenido. -->
                <div class="form-grid grid-3">
                    <!-- Inicia un bloque de contenido. -->
                    <div class="form-group">
                        <!-- Muestra el nombre del campo. -->
                        <label class="form-label">
                            <!-- Define esta parte de la página. -->
                            Código Maquinaria
                        </label>

                        <!-- Crea un campo para ingresar datos. -->
                        <input
                            type="text"
                            name="codigoMaquinaria"
                            class="form-input"
                            required>
                        </input>    
                    </div>

                    <!-- Inicia un bloque de contenido. -->
                    <div class="form-group">
                        <!-- Muestra el nombre del campo. -->
                        <label class="form-label">
                            <!-- Define esta parte de la página. -->
                            Patente
                        </label>

                        <!-- Crea un campo para ingresar datos. -->
                        <input
                            type="text"
                            name="patente"
                            class="form-input"
                            required>
                        </input>    
                    </div>

                    <!-- Inicia un bloque de contenido. -->
                    <div class="form-group">
                        <!-- Muestra el nombre del campo. -->
                        <label class="form-label">
                            <!-- Define esta parte de la página. -->
                            Equipo
                        </label>

                        <!-- Crea un campo para ingresar datos. -->
                        <input
                            type="text"
                            name="equipo"
                            class="form-input"
                            required>
                        </input>    
                    </div>
                </div>


<!-- TITULO: DATOS -->
                <!-- Inicia un bloque de contenido. -->
                <div class="form-grid grid-3">
                    <!-- Inicia un bloque de contenido. -->
                    <div class="form-group">
                        <!-- Muestra el nombre del campo. -->
                        <label class="form-label">
                            <!-- Define esta parte de la página. -->
                            Horómetro
                        </label>

                        <!-- Crea un campo para ingresar datos. -->
                        <input
                            type="number"
                            name="horometro"
                            class="form-input">
                        </input>    
                    </div>

                    <!-- Inicia un bloque de contenido. -->
                    <div class="form-group">
                        <!-- Muestra el nombre del campo. -->
                        <label class="form-label">
                            <!-- Define esta parte de la página. -->
                            Kilómetro
                        </label>

                        <!-- Crea un campo para ingresar datos. -->
                        <input
                            type="number"
                            name="kilometro"
                            class="form-input">
                        </input>    
                    </div>

                    <!-- Inicia un bloque de contenido. -->
                    <div class="form-group">
                        <!-- Muestra el nombre del campo. -->
                        <label class="form-label">
                            <!-- Define esta parte de la página. -->
                            Litros
                        </label>

                        <!-- Crea un campo para ingresar datos. -->
                        <input
                            type="number"
                            step="0.01"
                            name="litros"
                            class="form-input litros-operador"
                            required>
                        </input>
                    </div>
                </div>


<!-- TITULO: OBSERVACIÓN -->

                <!-- Inicia un bloque de contenido. -->
                <div class="form-group">
                    <!-- Muestra el nombre del campo. -->
                    <label class="form-label">
                        <!-- Define esta parte de la página. -->
                        Observación
                    </label>

                    <!-- Crea un espacio para escribir una observación. -->
                    <textarea                        
                        name="observacion"                        
                        class="form-textarea"
                        placeholder="Ingrese observación">
                    </textarea>
                </div>


<!-- TITULO: BOTON -->

                <!-- Inicia un bloque de contenido. -->
                <div class="form-actions">
                    <!-- Crea un botón. -->
                    <button
                        type="button"
                        class="btn-submit btn-submit-green btnAbrirModal">
                        Guardar Registro                        
                    </button>
                </div>
            </form>
        </main>



            
        
<!-- TÍTULO: MODAL ESCANEO QR -->
        
        <!-- Inicia un bloque de contenido. -->
        <div class="qr-scanner-modal" id="qrScannerModal" aria-hidden="true">
            <!-- Inicia un bloque de contenido. -->
            <div class="qr-scanner-box">
                <!-- Inicia un bloque de contenido. -->
                <div class="qr-scanner-header">
                    <!-- Muestra el título de esta sección. -->
                    <h2>Escanear código QR</h2>
                    <!-- Crea un botón. -->
                    <button type="button" id="qrModalClose" class="qr-close" aria-label="Cerrar">&times;</button>
                </div>

                <!-- Define esta parte de la página. -->
                <p id="qrScannerStatus" class="qr-scanner-status">Apunta la cámara al código QR.</p>
                <!-- Inicia un bloque de contenido. -->
                <div class="qr-video-wrap">
                    <!-- Inicia un bloque de contenido. -->
                    <div id="qr-reader"></div>
                </div>

                <!-- Prepara el espacio usado por el lector QR. -->
                <canvas id="qr-canvas" hidden></canvas>
                <!-- Crea un campo para ingresar datos. -->
                <input type="file" id="qrImageInput" accept="image/*" capture="environment" class="qr-image-input">
                <!-- Define esta parte de la página. -->
                <p class="qr-help">El QR debe contener los datos del trabajador. Puedes usar JSON, por ejemplo: <code>{"rut":"12.345.678-9","nombres":"Juan"}</code>.</p>
            </div>
        </div>

        
<!-- TÍTULO: MODAL CONFIRMACION -->
        
        <!-- Inicia un bloque de contenido. -->
        <div class="modal-confirmacion" id="modalConfirmacion">
            <!-- Inicia un bloque de contenido. -->
            <div class="modal-box">
                <!-- Muestra el título de esta sección. -->
                <h2>
                    <!-- Define esta parte de la página. -->
                    Confirmar Registro
                </h2>

                <!-- Inicia un bloque de contenido. -->
                <div id="contenidoConfirmacion"></div>
                <!-- Inicia un bloque de contenido. -->
                <div class="modal-actions">

                    <!-- Crea un botón. -->
                    <button
                        type="button"
                        id="btnEditar"
                        class="btn-modal btn-cancelar">
                        Editar
                    </button>

                    <!-- Crea un botón. -->
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
        <!-- Carga un archivo JavaScript. -->
        <script src="../../js/programa/toast.js"></script>
        <!-- Carga un archivo JavaScript. -->
        <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
        <!-- Carga un archivo JavaScript. -->
        <script src="../../js/programa/qr-scanner.js?v=6"></script>
        <!-- Carga un archivo JavaScript. -->
        <script src="../../js/programa/validador_rut.js"></script>
        <!-- Carga un archivo JavaScript. -->
        <script src="../../js/programa/modal-confirmacion.js"></script>
        <!-- Carga un archivo JavaScript. -->
        <script src="../../js/programa/panel.js?v=3"></script>
    
    </body>

</html>


<!-- -------------------------------------------------------------------------------------------------------------
   -------------------------------------- FIN Jerez panel.php --------------------------------------
   ------------------------------------------------------------------------------------------------------------- -->

<!-- 
Sitio Web Creado por Jerez.
Direccion: Miraflorez #1280
Quintero - Chile
jaime.jerez1978@gmail.com
https://www.
Creado, Programado y Diseñado por Jerez.
JJA
--