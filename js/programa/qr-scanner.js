/* ==========================================================
 * ESCANER QR
 * Usa html5-qrcode en Windows/Chrome y BarcodeDetector como
 * alternativa cuando el navegador lo soporte.
 * ========================================================== */
(function () {
    'use strict';

    const QRScanner = {
        scanner: null,
        detector: null,
        stream: null,
        timer: null,
        modal: null,
        status: null,
        fileInput: null,
        closeButton: null,
        onScanCallback: null,
        isScanning: false,
        libraryMode: false,
        selectedCameraId: null,

        init(onScanSuccess) {
            this.modal = document.getElementById('qrScannerModal');
            this.status = document.getElementById('qrScannerStatus');
            this.fileInput = document.getElementById('qrImageInput');
            this.closeButton = document.getElementById('qrModalClose');
            this.onScanCallback = onScanSuccess;

            if (!this.modal || !this.closeButton) {
                console.error('Elementos del escáner QR no encontrados.');
                return;
            }

            this.closeButton.addEventListener('click', () => this.stop());
            if (this.fileInput) {
                this.fileInput.addEventListener('change', (event) => {
                    const file = event.target.files && event.target.files[0];
                    if (file) this.scanImage(file);
                    event.target.value = '';
                });
            }
        },

        setStatus(text, type = 'info') {
            if (!this.status) return;
            this.status.textContent = text;
            this.status.className = 'qr-scanner-status ' + type;
        },

        loadLibraryFallback() {
            if (typeof window.Html5Qrcode === 'function') return Promise.resolve(true);
            if (this._libraryPromise) return this._libraryPromise;

            this._libraryPromise = new Promise((resolve) => {
                const script = document.createElement('script');
                script.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
                script.async = true;
                script.onload = () => resolve(typeof window.Html5Qrcode === 'function');
                script.onerror = () => resolve(false);
                document.head.appendChild(script);
            });

            return this._libraryPromise;
        },

        async start() {
            if (this.isScanning || !this.modal) return;
            this.modal.classList.add('active');
            this.modal.setAttribute('aria-hidden', 'false');
            this.setStatus('Iniciando cámara...');

            // html5-qrcode es la opción principal porque funciona en más navegadores.
            if (typeof window.Html5Qrcode !== 'function') {
                this.setStatus('Preparando lector QR...');
                await this.loadLibraryFallback();
            }

            if (typeof window.Html5Qrcode === 'function') {
                await this.startHtml5QrCode();
                return;
            }

            // Si las librerías externas no están disponibles, intentamos el lector nativo.
            await this.startNative();
        },

        async startHtml5QrCode() {
            try {
                const reader = document.getElementById('qr-reader');
                if (!reader) throw new Error('No se encontró el área del lector QR.');

                reader.innerHTML = '';
                this.scanner = new Html5Qrcode('qr-reader', { verbose: false });
                this.libraryMode = true;
                this.isScanning = true;
                this.setStatus('Buscando cámaras disponibles...');

                let cameras = [];
                try {
                    cameras = await Html5Qrcode.getCameras();
                } catch (cameraError) {
                    console.warn('No se pudo enumerar cámaras; se intentará selección automática.', cameraError);
                }

                if (Array.isArray(cameras) && cameras.length) {
                    const preferida = cameras.find(camera => {
                        const label = String(camera.label || '').toLowerCase();
                        return /back|rear|environment|trasera|posterior/.test(label);
                    }) || cameras[0];
                    this.selectedCameraId = preferida.id;
                }

                this.setStatus('Solicitando permiso para usar la cámara...');

                // En equipos Windows/Chrome suele funcionar mejor usar el ID real
                // de la cámara que depender únicamente de facingMode: environment.
                const cameraConfig = this.selectedCameraId
                    ? this.selectedCameraId
                    : { facingMode: { ideal: 'environment' } };

                await this.scanner.start(
                    cameraConfig,
                    {
                        fps: 10,
                        qrbox: function (viewfinderWidth, viewfinderHeight) {
                            const size = Math.floor(Math.min(viewfinderWidth, viewfinderHeight) * 0.65);
                            return { width: Math.max(180, size), height: Math.max(180, size) };
                        },
                        aspectRatio: 1.0,
                        disableFlip: false
                    },
                    async (decodedText) => {
                        const texto = String(decodedText || '').trim();
                        if (!texto || !this.isScanning) return;
                        this.setStatus('QR detectado. Cargando datos...', 'success');
                        if (typeof this.onScanCallback === 'function') {
                            this.onScanCallback(texto);
                        }
                        await this.stop();
                    },
                    () => {}
                );

                this.setStatus('Cámara activa. Apunta el QR dentro del recuadro.', 'success');
            } catch (error) {
                console.error('Error iniciando html5-qrcode:', error);
                await this.stop(false);

                const nombre = error && (error.name || error.message || '');
                let mensaje = 'No fue posible iniciar la cámara. Verifica que Chrome tenga permiso para usarla.';
                if (/NotAllowed|Permission/i.test(nombre)) {
                    mensaje = 'Permiso de cámara denegado. En Chrome, permite la cámara para este sitio y vuelve a intentar.';
                } else if (/NotFound|DevicesNotFound/i.test(nombre)) {
                    mensaje = 'No se encontró una cámara disponible en este equipo.';
                } else if (/NotReadable|TrackStart/i.test(nombre)) {
                    mensaje = 'La cámara está siendo usada por otro programa. Cierra Teams, Zoom, Meet u otra aplicación que la use y vuelve a intentar.';
                } else if (/secure|https/i.test(nombre)) {
                    mensaje = 'La cámara requiere un contexto seguro. En XAMPP usa http://localhost/ o configura HTTPS.';
                }
                this.setStatus(mensaje, 'error');
            }
        },

        async startNative() {
            try {
                if (!window.isSecureContext && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
                    throw new Error('La cámara requiere HTTPS o localhost.');
                }
                if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
                    throw new Error('Este navegador no permite acceder a la cámara.');
                }
                if (!('BarcodeDetector' in window)) {
                    throw new Error('El lector QR no está disponible. Comprueba que exista conexión a Internet para cargar el módulo html5-qrcode.');
                }
                const formats = await BarcodeDetector.getSupportedFormats();
                if (!formats.includes('qr_code')) throw new Error('El navegador no admite lectura QR.');

                this.detector = new BarcodeDetector({ formats: ['qr_code'] });
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } },
                    audio: false
                });

                const reader = document.getElementById('qr-reader');
                const video = document.createElement('video');
                video.id = 'qr-native-video';
                video.autoplay = true;
                video.muted = true;
                video.playsInline = true;
                reader.innerHTML = '';
                reader.appendChild(video);
                video.srcObject = this.stream;
                await video.play();
                this.isScanning = true;
                this.libraryMode = false;
                this.setStatus('Cámara activa. Apunta al código QR.');
                this.nativeLoop(video);
            } catch (error) {
                console.error('Error iniciando lector nativo:', error);
                await this.stop(false);
                this.setStatus(error.message || 'No fue posible iniciar el escáner QR.', 'error');
            }
        },

        async nativeLoop(video) {
            if (!this.isScanning || !this.detector) return;
            try {
                const codes = await this.detector.detect(video);
                if (codes && codes.length && codes[0].rawValue) {
                    const texto = codes[0].rawValue.trim();
                    if (typeof this.onScanCallback === 'function') this.onScanCallback(texto);
                    await this.stop();
                    return;
                }
            } catch (error) {
                console.debug('Lectura QR nativa:', error);
            }
            this.timer = requestAnimationFrame(() => this.nativeLoop(video));
        },

        async scanImage(file) {
            if (typeof window.Html5QrcodeScanner === 'function') {
                // No se usa el widget permanente; html5-qrcode permite escaneo
                // de archivo con una instancia normal.
            }
            try {
                if (typeof window.Html5Qrcode !== 'function') {
                    throw new Error('El módulo de lectura QR no está disponible.');
                }
                const tempId = 'qr-file-reader';
                let temp = document.getElementById(tempId);
                if (!temp) {
                    temp = document.createElement('div');
                    temp.id = tempId;
                    temp.hidden = true;
                    document.body.appendChild(temp);
                }
                const scanner = new Html5Qrcode(tempId, { verbose: false });
                const decodedText = await scanner.scanFile(file, true);
                await scanner.clear();
                if (typeof this.onScanCallback === 'function') this.onScanCallback(decodedText.trim());
                await this.stop();
            } catch (error) {
                console.error('Error leyendo imagen QR:', error);
                this.setStatus('No se encontró un QR válido en la imagen.', 'error');
            }
        },

        async stop(closeModal = true) {
            this.isScanning = false;
            if (this.timer) cancelAnimationFrame(this.timer);
            this.timer = null;

            if (this.scanner) {
                try {
                    const state = this.scanner.getState && this.scanner.getState();
                    if (state === 2 || state === 3) await this.scanner.stop();
                    await this.scanner.clear();
                } catch (error) {
                    console.debug('Cierre del lector QR:', error);
                }
            }
            this.scanner = null;
            this.selectedCameraId = null;

            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }
            this.detector = null;

            const reader = document.getElementById('qr-reader');
            if (reader) reader.innerHTML = '';

            if (closeModal && this.modal) {
                this.modal.classList.remove('active');
                this.modal.setAttribute('aria-hidden', 'true');
            }
        }
    };

    window.QRScanner = QRScanner;
})();
