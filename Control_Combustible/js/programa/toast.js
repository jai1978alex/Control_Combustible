// Sitio Web Creado por Jerez.
// Direccion: Miraflorez #1280
// Quintero - Chile
// jaime.jerez1978@gmail.com
// https://www.
// Creado, Programado y Diseñado por Jerez.
// JJA 

// 

/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- INICIO toast.js ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

// TITULO MODULO DE NOTIFICACIONES (TOAST)
// Define el objeto Toast, que muestra avisos temporales de tipo
// éxito, error o información en una esquina de la pantalla.

// Toast Notification Module

// Declara el objeto que agrupa las funciones de notificación
const Toast = {

    // TITULO CONTENEDOR
    // Guarda y busca el elemento donde se muestran los avisos.

    // Propiedad que guardará el contenedor de avisos (empieza vacía)
    container: null,

    // Define la función init, que busca el contenedor
    init() {
        // Obtiene el elemento con id toastContainer y lo guarda en container
        this.container = document.getElementById('toastContainer');
    },

    // TITULO MOSTRAR UN AVISO
    // Crea el aviso con su ícono y mensaje, lo agrega a la
    // pantalla y lo quita solo después de 3 segundos.

    // Define la función show, que recibe el mensaje y el tipo (por defecto 'info')
    show(message, type = 'info') {
        // Si todavía no se obtuvo el contenedor
        if (!this.container) {
            // Lo busca llamando a init
            this.init();
        }

        // Crea un div que será el aviso
        const toast = document.createElement('div');
        // Le asigna las clases CSS: "toast" y "toast-" más el tipo
        toast.className = `toast toast-${type}`;

        // Icon based on type
        // Variable que guardará el ícono SVG (empieza vacía)
        let icon = '';
        // Elige el ícono según el tipo de aviso
        switch (type) {
            // Caso de aviso de éxito
            case 'success':
                // Guarda un SVG verde con un círculo y una marca de verificación
                // (las líneas siguientes hasta el cierre con ` son el contenido de ese SVG)
                icon = `<svg class="toast-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>`;
                // Sale del switch
                break;
            // Caso de aviso de error
            case 'error':
                // Guarda un SVG rojo con un círculo y una X
                // (las líneas siguientes hasta el cierre con ` son el contenido de ese SVG)
                icon = `<svg class="toast-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>`;
                // Sale del switch
                break;
            // Caso de aviso informativo
            case 'info':
                // Guarda un SVG azul con un círculo y el símbolo de información
                // (las líneas siguientes hasta el cierre con ` son el contenido de ese SVG)
                icon = `<svg class="toast-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>`;
                // Sale del switch
                break;
        }

        // Inserta el ícono dentro del aviso
        toast.innerHTML = icon;
        // Crea un div para el texto del mensaje
        const mensajeEl = document.createElement('div');
        // Le asigna la clase CSS del mensaje
        mensajeEl.className = 'toast-message';
        // Escribe el mensaje como texto (seguro, no interpreta HTML)
        mensajeEl.textContent = message;
        // Agrega el texto al aviso
        toast.appendChild(mensajeEl);

        // Agrega el aviso completo al contenedor de la pantalla
        this.container.appendChild(toast);

        // Auto remove after 3 seconds
        // Programa una acción que se ejecuta a los 3 segundos
        setTimeout(() => {
            // Vuelve el aviso transparente (se desvanece)
            toast.style.opacity = '0';
            // Programa otra acción 300 ms después, cuando termina el desvanecimiento
            setTimeout(() => {
                // Quita el aviso del contenedor
                this.container.removeChild(toast);
            }, 300);
        }, 3000);
    },

    // TITULO ATAJOS POR TIPO
    // Funciones cortas para mostrar cada tipo de aviso.

    // Define el atajo para avisos de éxito
    success(message) {
        // Llama a show con el tipo 'success'
        this.show(message, 'success');
    },

    // Define el atajo para avisos de error
    error(message) {
        // Llama a show con el tipo 'error'
        this.show(message, 'error');
    },

    // Define el atajo para avisos informativos
    info(message) {
        // Llama a show con el tipo 'info'
        this.show(message, 'info');
    }
};

// TITULO DISPONIBILIDAD GLOBAL
// Publica Toast en window para usarlo desde otros archivos.

// Make Toast available globally
// Asigna el objeto a window para que otros scripts puedan usarlo
window.Toast = Toast;



/* --------------------------------------------------------------------------------------------------------------
   ---------------------------------------- FIN toast.js --------------------------------------
   -------------------------------------------------------------------------------------------------------------- */


// Sitio Web Creado por Jerez.
// Direccion: Miraflorez #1280
// Quintero - Chile
// jaime.jerez1978@gmail.com
// https://www.
// Creado, Programado y Diseñado por Jerez.
// JJA
