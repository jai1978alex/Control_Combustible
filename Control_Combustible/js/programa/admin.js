// Sitio Web Creado por Jerez.
// Direccion: Miraflorez #1280
// Quintero - Chile
// jaime.jerez1978@gmail.com
// https://www.
// Creado, Programado y Diseñado por Jerez.
// JJA 

// 

/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- INICIO admin.js ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

// TITULO INICIALIZACION DEL PANEL DE ADMINISTRACION

// Espera a que la página termine de cargar y luego activa las
// funciones del panel (confirmar eliminaciones y ocultar mensajes).

// Registra una función que se ejecuta cuando el HTML terminó de cargarse
document.addEventListener('DOMContentLoaded', function () {

    // TITULO CONFIRMACION ANTES DE ELIMINAR

    // Pide confirmación al administrador antes de eliminar un
    // usuario o un registro de combustible.

    // Busca los formularios que eliminan usuarios o registros y recorre cada uno
    document.querySelectorAll('form[action$="eliminar_usuario.php"], form[action$="eliminar_registro.php"]').forEach(function (form) {

        // Escucha el envío del formulario para poder detenerlo si hace falta
        form.addEventListener('submit', function (event) {

            // Guarda true si el formulario elimina un registro, o false si elimina un usuario
            const esRegistro = form.action.endsWith('eliminar_registro.php');

            // Elige el texto del cuadro de confirmación según lo que se va a eliminar
            const mensaje = esRegistro
                // Texto que se muestra cuando se elimina un registro de combustible
                ? '¿Quieres confirmar la eliminación de este registro de combustible?\n\nEsta acción no se puede deshacer.'
                // Texto que se muestra cuando se elimina un usuario
                : '¿Quieres confirmar la eliminación de este usuario?\n\nEsta acción no se puede deshacer.';

            // Muestra el cuadro de confirmación y entra al bloque si el usuario pulsa Cancelar
            if (!window.confirm(mensaje)) {
                // Cancela el envío del formulario, por lo que no se elimina nada
                event.preventDefault();
            }
        });
    });


    // TITULO OCULTAR MENSAJES AUTOMATICAMENTE

    // Los mensajes de confirmación o error desaparecen solos
    // después de unos segundos. La contraseña temporal se
    // excluye para que siga visible.

    // Los mensajes de confirmación/error se ocultan automáticamente después de unos segundos.
    // La contraseña temporal se excluye porque debe permanecer visible una sola vez.
    // Selecciona los .mensaje-admin que no tengan la clase .temp-password-flash y recorre cada uno
    document.querySelectorAll('.mensaje-admin:not(.temp-password-flash)').forEach(function (mensaje) {
        // Programa una acción que se ejecuta a los 3 segundos (3000 ms)
        window.setTimeout(function () {
            // Agrega la clase CSS que oculta el mensaje con una animación
            mensaje.classList.add('mensaje-admin-oculto');

            // Programa otra acción 400 ms después, cuando termina la animación
            window.setTimeout(function () {
                // Verifica que el mensaje exista y siga dentro de la página
                if (mensaje && mensaje.parentNode) {
                    // Elimina el mensaje de la página definitivamente
                    mensaje.parentNode.removeChild(mensaje);
                }
            }, 400);
        }, 3000);
    });
});



/* --------------------------------------------------------------------------------------------------------------
   ---------------------------------------- FIN admin.js --------------------------------------
   -------------------------------------------------------------------------------------------------------------- */


// Sitio Web Creado por Jerez.
// Direccion: Miraflorez #1280
// Quintero - Chile
// jaime.jerez1978@gmail.com
// https://www.
// Creado, Programado y Diseñado por Jerez.
// JJA
