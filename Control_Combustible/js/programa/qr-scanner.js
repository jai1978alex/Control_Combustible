// Sitio Web Creado por ITred Spa.
// Direccion: Guido Reni #4190
// Pedro Aguirre Cerda - Santiago - Chile
// contacto@itred.cl o itred.spa@gmail.com
// https://www.itred.cl
// Creado, Programado y Diseñado por ITred Spa.
// BPPJ 

// 

/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- INICIO qr-scanner.js ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */





/* ============================================================================
   BLOQUE 1 · DESCRIPCION GENERAL DEL ESCANER QR
   ----------------------------------------------------------------------------
   Qué hace : Define el objeto QRScanner, que lee códigos QR con la cámara o
              desde una imagen. Usa la librería html5-qrcode y, si no está
              disponible, el lector nativo BarcodeDetector del navegador.
   ============================================================================ */

/* ==========================================================
 * ESCANER QR
 * Usa html5-qrcode en Windows/Chrome y BarcodeDetector como
 * alternativa cuando el navegador lo soporte.
 * ========================================================== */
// Envuelve todo en una función que se ejecuta sola, para no ensuciar el espacio global
(function () {
    // Activa el modo estricto de JavaScript (detecta errores comunes)
    'use strict';

    /* ------------------------------------------------------------------------
       BLOQUE 2 · ESTADO DEL ESCANER
       ------------------------------------------------------------------------
       Qué hace : Declara el objeto QRScanner y sus propiedades iniciales.
       ------------------------------------------------------------------------ */

    // Declara el objeto que agrupa todo el escáner
    const QRScanner = {
        // Instancia de la librería html5-qrcode (vacía al inicio)
        scanner: null,
        // Instancia del lector nativo BarcodeDetector (vacía al inicio)
        detector: null,
        // Flujo de video de la cámara (vacío al inicio)
        stream: null,
        // Identificador de la animación del lector nativo (vacío al inicio)
        timer: null,
        // Elemento HTML del modal del escáner
        modal: null,
        // Elemento HTML donde se muestran los estados
        status: null,
        // Campo para subir una imagen con QR
        fileInput: null,
        // Botón que cierra el modal
        closeButton: null,
        // Función que recibe el texto leído del QR
        onScanCallback: null,
        // Indica si el escáner está funcionando
        isScanning: false,
        // Indica si se está usando la librería (true) o el lector nativo (false)
        libraryMode: false,
        // Identificador de la cámara elegida
        selectedCameraId: null,

        /* --------------------------------------------------------------------
           BLOQUE 3 · INICIALIZAR (init)
           --------------------------------------------------------------------
           Qué hace : Busca los elementos del modal y conecta los eventos de
                      cerrar y de subir imagen.
           -------------------------------------------------------------------- */

        // Define init; recibe la función que se llamará al leer un QR
        init(onScanSuccess) {
            // Obtiene el modal del escáner
            this.modal = document.getElementById('qrScannerModal');
            // Obtiene el texto de estado
            this.status = document.getElementById('qrScannerStatus');
            // Obtiene el campo para subir imágenes
            this.fileInput = document.getElementById('qrImageInput');
            // Obtiene el botón de cerrar
            this.closeButton = document.getElementById('qrModalClose');
            // Guarda la función que recibirá el resultado
            this.onScanCallback = onScanSuccess;

            // Si falta el modal o el botón de cerrar
            if (!this.modal || !this.closeButton) {
                // Registra el problema en la consola
                console.error('Elementos del escáner QR no encontrados.');
                // Detiene la función
                return;
            // Cierra el if de elementos faltantes
            }

            // Al pulsar el botón de cerrar, detiene el escáner
            this.closeButton.addEventListener('click', () => this.stop());
            // Si existe el campo de imagen
            if (this.fileInput) {
                // Escucha cuando se elige un archivo
                this.fileInput.addEventListener('change', (event) => {
                    // Toma el primer archivo elegido (si hay)
                    const file = event.target.files && event.target.files[0];
                    // Si hay archivo, lo escanea
                    if (file) this.scanImage(file);
                    // Limpia el campo para poder elegir el mismo archivo otra vez
                    event.target.value = '';
                // Cierra el evento change
                });
            // Cierra el if del campo de imagen
            }
        // Cierra init
        },

        /* --------------------------------------------------------------------
           BLOQUE 4 · MOSTRAR ESTADO (setStatus)
           --------------------------------------------------------------------
           Qué hace : Escribe un mensaje de estado en el modal con su tipo.
           -------------------------------------------------------------------- */

        // Define setStatus; recibe el texto y el tipo (por defecto 'info')
        setStatus(text, type = 'info') {
            // Si no existe el elemento de estado, termina
            if (!this.status) return;
            // Escribe el texto
            this.status.textContent = text;
            // Asigna las clases CSS según el tipo
            this.status.className = 'qr-scanner-status ' + type;
        // Cierra setStatus
        },

        /* --------------------------------------------------------------------
           BLOQUE 5 · CARGAR LA LIBRERIA (loadLibraryFallback)
           --------------------------------------------------------------------
           Qué hace : Si la librería html5-qrcode no está cargada, la descarga
                      desde internet. Devuelve una promesa true/false.
           -------------------------------------------------------------------- */

        // Define loadLibraryFallback
        loadLibraryFallback() {
            // Si la librería ya está cargada, devuelve true de inmediato
            if (typeof window.Html5Qrcode === 'function') return Promise.resolve(true);
            // Si ya hay una descarga en curso, devuelve esa misma
            if (this._libraryPromise) return this._libraryPromise;

            // Crea la promesa de descarga
            this._libraryPromise = new Promise((resolve) => {
                // Crea una etiqueta script
                const script = document.createElement('script');
                // Define la dirección de la librería
                script.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
                // Pide cargarla sin bloquear la página
                script.async = true;
                // Al cargar, resuelve con true si la librería quedó disponible
                script.onload = () => resolve(typeof window.Html5Qrcode === 'function');
                // Si falla la descarga, resuelve con false
                script.onerror = () => resolve(false);
                // Agrega el script a la página para iniciar la descarga
                document.head.appendChild(script);
            // Cierra la promesa
            });

            // Devuelve la promesa
            return this._libraryPromise;
        // Cierra loadLibraryFallback
        },

        /* --------------------------------------------------------------------
           BLOQUE 6 · INICIAR EL ESCANEO (start)
           --------------------------------------------------------------------
           Qué hace : Abre el modal y elige el método de lectura: primero la
                      librería y, si no está disponible, el lector nativo.
           -------------------------------------------------------------------- */

        // Define start (asíncrona, espera operaciones lentas)
        async start() {
            // Si ya está escaneando o no existe el modal, termina
            if (this.isScanning || !this.modal) return;
            // Muestra el modal
            this.modal.classList.add('active');
            // Indica a lectores de pantalla que el modal es visible
            this.modal.setAttribute('aria-hidden', 'false');
            // Muestra el estado inicial
            this.setStatus('Iniciando cámara...');

            // html5-qrcode es la opción principal porque funciona en más navegadores.
            // Si la librería todavía no está cargada
            if (typeof window.Html5Qrcode !== 'function') {
                // Muestra el estado de preparación
                this.setStatus('Preparando lector QR...');
                // Espera a que se descargue la librería
                await this.loadLibraryFallback();
            // Cierra el if de la librería
            }

            // Si la librería quedó disponible
            if (typeof window.Html5Qrcode === 'function') {
                // Inicia el escaneo con la librería
                await this.startHtml5QrCode();
                // Termina la función
                return;
            // Cierra el if de la librería disponible
            }

            // Si las librerías externas no están disponibles, intentamos el lector nativo.
            // Inicia el escaneo con el lector nativo del navegador
            await this.startNative();
        // Cierra start
        },

        /* --------------------------------------------------------------------
           BLOQUE 7 · ESCANEO CON LA LIBRERIA (startHtml5QrCode)
           --------------------------------------------------------------------
           Qué hace : Busca la cámara, pide permiso, inicia el video y
                      entrega el texto del QR cuando lo detecta. Si falla,
                      muestra un mensaje de error comprensible.
           -------------------------------------------------------------------- */

        // Define startHtml5QrCode (asíncrona)
        async startHtml5QrCode() {
            // Intenta iniciar la cámara; si algo falla, salta al catch
            try {
                // Obtiene el área donde se mostrará el video
                const reader = document.getElementById('qr-reader');
                // Si no existe, lanza un error con ese mensaje
                if (!reader) throw new Error('No se encontró el área del lector QR.');

                // Vacía el área del lector
                reader.innerHTML = '';
                // Crea el escáner de la librería sobre el área "qr-reader", sin mensajes extra
                this.scanner = new Html5Qrcode('qr-reader', { verbose: false });
                // Marca que se usa la librería
                this.libraryMode = true;
                // Marca que el escáner está funcionando
                this.isScanning = true;
                // Muestra el estado de búsqueda de cámaras
                this.setStatus('Buscando cámaras disponibles...');

                // Lista donde se guardarán las cámaras (vacía al inicio)
                let cameras = [];
                // Intenta obtener las cámaras disponibles
                try {
                    // Pide la lista de cámaras a la librería
                    cameras = await Html5Qrcode.getCameras();
                // Si falla, avisa pero sigue
                } catch (cameraError) {
                    // Registra una advertencia en la consola
                    console.warn('No se pudo enumerar cámaras; se intentará selección automática.', cameraError);
                // Cierra el catch de las cámaras
                }

                // Si la lista tiene al menos una cámara
                if (Array.isArray(cameras) && cameras.length) {
                    // Busca la cámara trasera; si no hay, usa la primera
                    const preferida = cameras.find(camera => {
                        // Lee el nombre de la cámara en minúsculas
                        const label = String(camera.label || '').toLowerCase();
                        // Devuelve true si el nombre indica cámara trasera
                        return /back|rear|environment|trasera|posterior/.test(label);
                    // Si no encontró trasera, usa la primera cámara
                    }) || cameras[0];
                    // Guarda el identificador de la cámara elegida
                    this.selectedCameraId = preferida.id;
                // Cierra el if de las cámaras
                }

                // Muestra el estado de solicitud de permiso
                this.setStatus('Solicitando permiso para usar la cámara...');

                // En equipos Windows/Chrome suele funcionar mejor usar el ID real
                // de la cámara que depender únicamente de facingMode: environment.
                // Define qué cámara usar
                const cameraConfig = this.selectedCameraId
                    // Si hay una cámara elegida, usa su identificador
                    ? this.selectedCameraId
                    // Si no, pide la cámara trasera de forma preferente
                    : { facingMode: { ideal: 'environment' } };

                // Inicia el video con tres argumentos: cámara, opciones y funciones de lectura
                await this.scanner.start(
                    // ARGUMENTO 1: la cámara a usar
                    cameraConfig,
                    // ARGUMENTO 2: opciones del escáner
                    {
                        // Analiza 10 imágenes por segundo
                        fps: 10,
                        // Define el tamaño del recuadro de lectura según el tamaño del video
                        qrbox: function (viewfinderWidth, viewfinderHeight) {
                            // Calcula el 65% del lado menor del video, redondeado hacia abajo
                            const size = Math.floor(Math.min(viewfinderWidth, viewfinderHeight) * 0.65);
                            // Devuelve un cuadrado de al menos 180 px
                            return { width: Math.max(180, size), height: Math.max(180, size) };
                        // Cierra la función qrbox
                        },
                        // Usa una proporción cuadrada (1:1)
                        aspectRatio: 1.0,
                        // Permite leer códigos espejados
                        disableFlip: false
                    // Cierra las opciones
                    },
                    // ARGUMENTO 3: función que se ejecuta al detectar un QR
                    async (decodedText) => {
                        // Convierte el texto leído y le quita espacios
                        const texto = String(decodedText || '').trim();
                        // Si el texto está vacío o el escáner ya se detuvo, no hace nada
                        if (!texto || !this.isScanning) return;
                        // Muestra el estado de QR detectado
                        this.setStatus('QR detectado. Cargando datos...', 'success');
                        // Si existe la función de resultado
                        if (typeof this.onScanCallback === 'function') {
                            // Entrega el texto leído
                            this.onScanCallback(texto);
                        // Cierra el if de la función de resultado
                        }
                        // Detiene el escáner y cierra el modal
                        await this.stop();
                    // Cierra la función de detección
                    },
                    // ARGUMENTO 4: función de error de lectura por imagen (vacía a propósito)
                    () => {}
                // Cierra la llamada a scanner.start
                );

                // Muestra que la cámara quedó activa
                this.setStatus('Cámara activa. Apunta el QR dentro del recuadro.', 'success');
            // Si algo falló en el try
            } catch (error) {
                // Registra el error en la consola
                console.error('Error iniciando html5-qrcode:', error);
                // Detiene el escáner sin cerrar el modal
                await this.stop(false);

                // Obtiene el nombre o mensaje del error
                const nombre = error && (error.name || error.message || '');
                // Mensaje por defecto
                let mensaje = 'No fue posible iniciar la cámara. Verifica que Chrome tenga permiso para usarla.';
                // Si el error indica permiso denegado
                if (/NotAllowed|Permission/i.test(nombre)) {
                    // Mensaje de permiso denegado
                    mensaje = 'Permiso de cámara denegado. En Chrome, permite la cámara para este sitio y vuelve a intentar.';
                // Si el error indica que no hay cámara
                } else if (/NotFound|DevicesNotFound/i.test(nombre)) {
                    // Mensaje de cámara no encontrada
                    mensaje = 'No se encontró una cámara disponible en este equipo.';
                // Si el error indica que la cámara está ocupada
                } else if (/NotReadable|TrackStart/i.test(nombre)) {
                    // Mensaje de cámara en uso
                    mensaje = 'La cámara está siendo usada por otro programa. Cierra Teams, Zoom, Meet u otra aplicación que la use y vuelve a intentar.';
                // Si el error indica falta de contexto seguro
                } else if (/secure|https/i.test(nombre)) {
                    // Mensaje de contexto seguro
                    mensaje = 'La cámara requiere un contexto seguro. En XAMPP usa http://localhost/ o configura HTTPS.';
                // Cierra la cadena de if
                }
                // Muestra el mensaje elegido como error
                this.setStatus(mensaje, 'error');
            // Cierra el catch
            }
        // Cierra startHtml5QrCode
        },

        /* --------------------------------------------------------------------
           BLOQUE 8 · ESCANEO CON EL LECTOR NATIVO (startNative)
           --------------------------------------------------------------------
           Qué hace : Si la librería no está disponible, usa BarcodeDetector
                      con la cámara del navegador.
           -------------------------------------------------------------------- */

        // Define startNative (asíncrona)
        async startNative() {
            // Intenta iniciar; si algo falla, salta al catch
            try {
                // Si la página no es segura y tampoco es localhost ni 127.0.0.1
                if (!window.isSecureContext && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
                    // Lanza un error de contexto seguro
                    throw new Error('La cámara requiere HTTPS o localhost.');
                // Cierra el if del contexto
                }
                // Si el navegador no permite acceder a la cámara
                if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
                    // Lanza un error de acceso a la cámara
                    throw new Error('Este navegador no permite acceder a la cámara.');
                // Cierra el if de la cámara
                }
                // Si el navegador no tiene BarcodeDetector
                if (!('BarcodeDetector' in window)) {
                    // Lanza un error indicando que no hay lector disponible
                    throw new Error('El lector QR no está disponible. Comprueba que exista conexión a Internet para cargar el módulo html5-qrcode.');
                // Cierra el if de BarcodeDetector
                }
                // Obtiene los formatos de código que soporta el navegador
                const formats = await BarcodeDetector.getSupportedFormats();
                // Si no soporta QR, lanza un error
                if (!formats.includes('qr_code')) throw new Error('El navegador no admite lectura QR.');

                // Crea el detector configurado solo para códigos QR
                this.detector = new BarcodeDetector({ formats: ['qr_code'] });
                // Pide permiso y obtiene el video de la cámara
                this.stream = await navigator.mediaDevices.getUserMedia({
                    // Prefiere la cámara trasera y una resolución de 1280x720
                    video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } },
                    // No pide audio
                    audio: false
                // Cierra las opciones de getUserMedia
                });

                // Obtiene el área donde se mostrará el video
                const reader = document.getElementById('qr-reader');
                // Crea el elemento de video
                const video = document.createElement('video');
                // Le asigna un id
                video.id = 'qr-native-video';
                // Reproduce automáticamente
                video.autoplay = true;
                // Sin sonido (necesario para la reproducción automática)
                video.muted = true;
                // Reproduce dentro de la página (no en pantalla completa en móviles)
                video.playsInline = true;
                // Vacía el área del lector
                reader.innerHTML = '';
                // Agrega el video al área
                reader.appendChild(video);
                // Conecta el video con la cámara
                video.srcObject = this.stream;
                // Inicia la reproducción y espera
                await video.play();
                // Marca que el escáner está funcionando
                this.isScanning = true;
                // Marca que se usa el lector nativo
                this.libraryMode = false;
                // Muestra el estado de cámara activa
                this.setStatus('Cámara activa. Apunta al código QR.');
                // Inicia el ciclo de lectura
                this.nativeLoop(video);
            // Si algo falló
            } catch (error) {
                // Registra el error en la consola
                console.error('Error iniciando lector nativo:', error);
                // Detiene el escáner sin cerrar el modal
                await this.stop(false);
                // Muestra el mensaje del error (o uno genérico)
                this.setStatus(error.message || 'No fue posible iniciar el escáner QR.', 'error');
            // Cierra el catch
            }
        // Cierra startNative
        },

        /* --------------------------------------------------------------------
           BLOQUE 9 · CICLO DE LECTURA NATIVA (nativeLoop)
           --------------------------------------------------------------------
           Qué hace : Analiza el video una y otra vez hasta encontrar un QR.
           -------------------------------------------------------------------- */

        // Define nativeLoop; recibe el elemento de video
        async nativeLoop(video) {
            // Si el escáner se detuvo o no hay detector, termina
            if (!this.isScanning || !this.detector) return;
            // Intenta detectar códigos en el video
            try {
                // Busca códigos QR en la imagen actual
                const codes = await this.detector.detect(video);
                // Si encontró al menos uno con contenido
                if (codes && codes.length && codes[0].rawValue) {
                    // Toma el texto del primer código sin espacios en los extremos
                    const texto = codes[0].rawValue.trim();
                    // Si existe la función de resultado, le entrega el texto
                    if (typeof this.onScanCallback === 'function') this.onScanCallback(texto);
                    // Detiene el escáner y cierra el modal
                    await this.stop();
                    // Termina el ciclo
                    return;
                // Cierra el if del código encontrado
                }
            // Si hay un error de lectura, lo ignora y sigue
            } catch (error) {
                // Registra el error solo en el nivel de depuración
                console.debug('Lectura QR nativa:', error);
            // Cierra el catch
            }
            // Programa la siguiente lectura en el próximo cuadro de animación
            this.timer = requestAnimationFrame(() => this.nativeLoop(video));
        // Cierra nativeLoop
        },

        /* --------------------------------------------------------------------
           BLOQUE 10 · ESCANEAR UNA IMAGEN (scanImage)
           --------------------------------------------------------------------
           Qué hace : Lee un código QR desde un archivo de imagen.
           -------------------------------------------------------------------- */

        // Define scanImage (asíncrona); recibe el archivo
        async scanImage(file) {
            // Si existe el widget Html5QrcodeScanner (bloque sin instrucciones, solo explicativo)
            if (typeof window.Html5QrcodeScanner === 'function') {
                // No se usa el widget permanente; html5-qrcode permite escaneo
                // de archivo con una instancia normal.
            // Cierra el if vacío
            }
            // Intenta leer la imagen; si falla, salta al catch
            try {
                // Si la librería no está disponible
                if (typeof window.Html5Qrcode !== 'function') {
                    // Lanza un error indicando que no hay módulo de lectura
                    throw new Error('El módulo de lectura QR no está disponible.');
                // Cierra el if de la librería
                }
                // Define el id del contenedor temporal
                const tempId = 'qr-file-reader';
                // Busca ese contenedor si ya existe
                let temp = document.getElementById(tempId);
                // Si no existe, entra para crearlo
                if (!temp) {
                    // Crea un div nuevo
                    temp = document.createElement('div');
                    // Le asigna el id
                    temp.id = tempId;
                    // Lo mantiene oculto
                    temp.hidden = true;
                    // Lo agrega a la página
                    document.body.appendChild(temp);
                // Cierra el if de creación
                }
                // Crea un lector de la librería sobre ese contenedor
                const scanner = new Html5Qrcode(tempId, { verbose: false });
                // Lee el QR del archivo y espera el texto
                const decodedText = await scanner.scanFile(file, true);
                // Libera el lector
                await scanner.clear();
                // Si existe la función de resultado, le entrega el texto sin espacios en los extremos
                if (typeof this.onScanCallback === 'function') this.onScanCallback(decodedText.trim());
                // Detiene el escáner y cierra el modal
                await this.stop();
            // Si algo falló
            } catch (error) {
                // Registra el error en la consola
                console.error('Error leyendo imagen QR:', error);
                // Avisa que no se encontró un QR válido
                this.setStatus('No se encontró un QR válido en la imagen.', 'error');
            // Cierra el catch
            }
        // Cierra scanImage
        },

        /* --------------------------------------------------------------------
           BLOQUE 11 · DETENER EL ESCANER (stop)
           --------------------------------------------------------------------
           Qué hace : Apaga la cámara, libera recursos y, si se indica,
                      cierra el modal.
           -------------------------------------------------------------------- */

        // Define stop (asíncrona); closeModal vale true por defecto
        async stop(closeModal = true) {
            // Marca que el escáner ya no está funcionando
            this.isScanning = false;
            // Si hay un ciclo de animación activo, lo cancela
            if (this.timer) cancelAnimationFrame(this.timer);
            // Borra el identificador del ciclo
            this.timer = null;

            // Si hay un escáner de la librería
            if (this.scanner) {
                // Intenta apagarlo
                try {
                    // Obtiene el estado del escáner (si la función existe)
                    const state = this.scanner.getState && this.scanner.getState();
                    // Si está escaneando (2) o en pausa (3), lo detiene
                    if (state === 2 || state === 3) await this.scanner.stop();
                    // Libera el escáner
                    await this.scanner.clear();
                // Si falla, lo ignora
                } catch (error) {
                    // Registra el error solo en el nivel de depuración
                    console.debug('Cierre del lector QR:', error);
                // Cierra el catch
                }
            // Cierra el if del escáner
            }
            // Borra la referencia al escáner
            this.scanner = null;
            // Borra la cámara elegida
            this.selectedCameraId = null;

            // Si hay un flujo de video de la cámara
            if (this.stream) {
                // Apaga cada pista (video/audio) del flujo
                this.stream.getTracks().forEach(track => track.stop());
                // Borra la referencia al flujo
                this.stream = null;
            // Cierra el if del flujo
            }
            // Borra la referencia al detector nativo
            this.detector = null;

            // Obtiene el área del lector
            const reader = document.getElementById('qr-reader');
            // Si existe, la vacía
            if (reader) reader.innerHTML = '';

            // Si se pidió cerrar el modal y el modal existe
            if (closeModal && this.modal) {
                // Oculta el modal
                this.modal.classList.remove('active');
                // Indica a lectores de pantalla que el modal está oculto
                this.modal.setAttribute('aria-hidden', 'true');
            // Cierra el if del modal
            }
        // Cierra stop
        }
    // Cierra el objeto QRScanner
    };

    /* ------------------------------------------------------------------------
       BLOQUE 12 · DISPONIBILIDAD GLOBAL
       ------------------------------------------------------------------------
       Qué hace : Publica QRScanner en window para usarlo desde otros archivos.
       ------------------------------------------------------------------------ */

    // Asigna el objeto a window para que otros scripts puedan usarlo
    window.QRScanner = QRScanner;
// Cierra la función que se ejecuta sola
})();



/* --------------------------------------------------------------------------------------------------------------
   ---------------------------------------- FIN qr-scanner.js --------------------------------------
   -------------------------------------------------------------------------------------------------------------- */


// Sitio Web Creado por ITred Spa.
// Direccion: Guido Reni #4190
// Pedro Aguirre Cerda - Santiago - Chile
// contacto@itred.cl o itred.spa@gmail.com
// https://www.itred.cl
// Creado, Programado y Diseñado por ITred Spa.
// BPPJ
