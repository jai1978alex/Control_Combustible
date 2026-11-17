// Sitio Web Creado por Jerez.
// Direccion: Miraflorez #1280
// Quintero - Chile
// jaime.jerez1978@gmail.com
// https://www.
// Creado, Programado y Diseñado por Jerez.
// JJA 

// 

/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- INICIO crear_usuario.js ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

// TITULO INICIALIZACION
// Espera a que la página cargue y activa la validación del
// formulario de creación de usuario.

// Ejecuta el código de abajo cuando el HTML terminó de cargarse
document.addEventListener("DOMContentLoaded", function () {

    // TITULO REFERENCIAS A LOS ELEMENTOS DE LA PAGINA
    // Obtiene los campos de contraseña, el texto de error y
    // el formulario.

    // Obtiene el campo de la contraseña del nuevo usuario
    const nueva = document.getElementById("crearUsuarioPassword");
    // Obtiene el campo donde se repite la contraseña
    const confirmar = document.getElementById("crearUsuarioPasswordConfirm");
    // Obtiene el elemento donde se muestra el mensaje de error
    const error = document.getElementById("crearUsuarioPasswordError");
    // Obtiene el primer formulario de la página
    const form = document.querySelector("form");
    // Si falta alguno de los elementos, termina para evitar errores
    if (!nueva || !confirmar || !error || !form) return;

    // TITULO FUNCION DE VALIDACION
    // Comprueba que ambas contraseñas coincidan y muestra o
    // limpia el mensaje de error. Devuelve true si son válidas.

    // Define la función que valida que las contraseñas coincidan
    function validar() {
        // Si el campo de confirmación tiene texto y es distinto de la contraseña
        if (confirmar.value && confirmar.value !== nueva.value) {
            // Muestra el mensaje de error
            error.textContent = "Las contraseñas no coinciden";
            // Indica que la validación falló
            return false;
        }
        // Borra cualquier mensaje de error anterior
        error.textContent = "";
        // Indica que la validación fue correcta
        return true;
    }

    // TITULO EVENTOS
    // Valida mientras se escribe y bloquea el envío del
    // formulario si las contraseñas no coinciden.

    // Valida cada vez que se escribe en el campo de contraseña
    nueva.addEventListener("input", validar);
    // Valida cada vez que se escribe en el campo de confirmación
    confirmar.addEventListener("input", validar);
    // Escucha el envío del formulario
    form.addEventListener("submit", function (event) {
        // Si la validación falla, cancela el envío
        if (!validar()) event.preventDefault();
    });
});



/* --------------------------------------------------------------------------------------------------------------
   ---------------------------------------- FIN crear_usuario.js --------------------------------------
   -------------------------------------------------------------------------------------------------------------- */


// Sitio Web Creado por Jerez.
// Direccion: Miraflorez #1280
// Quintero - Chile
// jaime.jerez1978@gmail.com
// https://www.
// Creado, Programado y Diseñado por Jerez.
// JJA
