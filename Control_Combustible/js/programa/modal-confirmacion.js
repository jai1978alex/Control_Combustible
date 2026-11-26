// Sitio Web Creado por Jerez.
// Direccion: Miraflorez #1280
// Quintero - Chile
// jaime.jerez1978@gmail.com
// https://www.
// Creado, Programado y Diseñado por Jerez.
// JJA 

// 

/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- INICIO modal-confirmacion.js ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

// TITULO REFERENCIAS A ELEMENTOS Y ESTADO

// Obtiene los botones y las partes del modal de confirmación y
// declara la variable que recuerda qué formulario está activo.

// Obtiene todos los botones que abren el modal de confirmación
const botonesAbrir = document.querySelectorAll(".btnAbrirModal");

// Obtiene el contenedor principal del modal
const modal = document.getElementById("modalConfirmacion");

// Obtiene el espacio donde se mostrarán los datos a confirmar
const contenido = document.getElementById("contenidoConfirmacion");

// Obtiene el botón que cierra el modal para seguir editando
const btnEditar = document.getElementById("btnEditar");

// Obtiene el botón que confirma y envía el formulario
const btnConfirmar = document.getElementById("btnConfirmar");

// Variable que guarda el formulario que se está confirmando (empieza sin ninguno)
let formularioActivo = null;



// TITULO ABRIR MODAL

// Al pulsar un botón, toma los datos del formulario, los arma
// en pantalla de forma segura y muestra el modal.

// Recorre cada botón que abre el modal
botonesAbrir.forEach(btn => {

    // Escucha el clic sobre ese botón
    btn.addEventListener("click", () => {

        // Guarda el formulario al que pertenece el botón pulsado
        formularioActivo = btn.closest("form");

        // Obtiene todos los campos (input, select y textarea) de ese formulario
        const inputs = formularioActivo.querySelectorAll(
            "input, select, textarea"
        );

        // Se construye el contenido con nodos DOM (createElement + textContent)
        // en vez de innerHTML con plantillas de texto. Los valores de estos
        // campos pueden venir de un código QR escaneado o de texto libre
        // (observación), por lo que no son datos de confianza: si se insertan
        // como HTML permiten XSS. textContent nunca interpreta HTML/JS.
        // Vacía el contenido anterior del modal
        contenido.replaceChildren();

        // Recorre cada campo del formulario
        inputs.forEach(input => {

            // Solo continúa si el campo no es botón, envío ni campo oculto
            if (
                input.type !== "button" &&
                input.type !== "submit" &&
                input.type !== "hidden"
            ) {

                // Crea un div que representa una fila del resumen
                const item = document.createElement("div");
                // Le asigna la clase CSS de la fila
                item.className = "confirmacion-item";

                // Crea un elemento en negrita para el nombre del campo
                const etiqueta = document.createElement("strong");
                // Escribe el nombre del campo como texto (seguro)
                etiqueta.textContent = input.name;

                // Crea un elemento para el valor del campo
                const valor = document.createElement("span");
                // Escribe el valor ingresado como texto (seguro)
                valor.textContent = input.value;

                // Agrega el nombre a la fila
                item.appendChild(etiqueta);
                // Agrega el valor a la fila
                item.appendChild(valor);
                // Agrega la fila completa al contenido del modal
                contenido.appendChild(item);
            }
        });

        // Muestra el modal
        modal.style.display = "flex";
    });
});



// TITULO EDITAR (CERRAR EL MODAL)

// Verifica que el modal exista y, al pulsar Editar, lo oculta
// para que el usuario siga modificando el formulario.

// Si falta algún elemento del modal, avisa por consola (no detiene el programa)
if (!modal || !contenido || !btnEditar || !btnConfirmar) { console.warn("Modal de confirmación no disponible"); }

// Si existe el botón Editar, escucha su clic
if (btnEditar) btnEditar.addEventListener("click", () => {

    // Oculta el modal
    modal.style.display = "none";

});



// TITULO CONFIRMAR (ENVIAR EL FORMULARIO)

// Al pulsar Confirmar, envía el formulario que se estaba
// revisando.

// Si existe el botón Confirmar, escucha su clic
if (btnConfirmar) btnConfirmar.addEventListener("click", () => {

    // Envía el formulario activo al servidor
    // Con modo sin conexión: intenta enviar y, si no hay señal, guarda el registro en el equipo.
    // Si offline.js no cargó, se envía el formulario como siempre.
    if (window.OfflineQueue && typeof window.OfflineQueue.enviar === "function") {
        window.OfflineQueue.enviar(formularioActivo);
    } else {
        formularioActivo.submit();
    }

});



// TITULO CERRAR HACIENDO CLIC AFUERA

// Si se hace clic en el fondo oscuro fuera del cuadro, cierra
// el modal.

// Escucha los clics en toda la ventana
window.addEventListener("click", e => {

    // Si el clic fue exactamente sobre el fondo del modal (no sobre su contenido)
    if (e.target === modal) {

        // Oculta el modal
        modal.style.display = "none";
    }
});

// TITULO TIEMPO DE INACTIVIDAD

// Define el tiempo de inactividad. En este archivo no se usa.

// Guarda 15 minutos expresados en milisegundos (15 × 60 segundos × 1000 ms)
let tiempoInactivo = 15 * 60 * 1000;



/* --------------------------------------------------------------------------------------------------------------
   ---------------------------------------- FIN modal-confirmacion.js --------------------------------------
   -------------------------------------------------------------------------------------------------------------- */


// Sitio Web Creado por Jerez.
// Direccion: Miraflorez #1280
// Quintero - Chile
// jaime.jerez1978@gmail.com
// https://www.
// Creado, Programado y Diseñado por Jerez.
// JJA
