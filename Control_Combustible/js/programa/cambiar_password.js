// Sitio Web Creado por ITred Spa.
// Direccion: Guido Reni #4190
// Pedro Aguirre Cerda - Santiago - Chile
// contacto@itred.cl o itred.spa@gmail.com
// https://www.itred.cl
// Creado, Programado y Diseñado por ITred Spa.
// BPPJ 

// 

/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- INICIO cambiar_password.js ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

// TITULO INICIALIZACION
// Espera a que la página cargue y activa la validación del
// formulario de cambio de contraseña.

// Ejecuta el código de abajo cuando el HTML terminó de cargarse
document.addEventListener("DOMContentLoaded", function () {

    // TITULO REFERENCIAS A LOS ELEMENTOS DE LA PAGINA
    // Obtiene los campos del formulario y el texto de error.

    // Obtiene el campo de la nueva contraseña
    const nueva = document.getElementById("newPassword");
    // Obtiene el campo de confirmación de la contraseña
    const confirmar = document.getElementById("confirmPassword");
    // Obtiene el elemento donde se muestra el mensaje de error
    const error = document.getElementById("confirmError");
    // Obtiene el primer formulario de la página
    const form = document.querySelector("form");
    // Si falta alguno de los elementos, termina para evitar errores
    if (!nueva || !confirmar || !error || !form) return;

    // TITULO FUNCION DE VALIDACION
    // Comprueba que ambas contraseñas coincidan y muestra o
    // limpia el mensaje de error. Devuelve true si son válidas.

    // Define la función que valida que las contraseñas coincidan
    function validar() {
        // Si el campo de confirmación tiene texto y es distinto de la contraseña nueva
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

    // Valida cada vez que se escribe en el campo de la contraseña nueva
    nueva.addEventListener("input", validar);
    // Valida cada vez que se escribe en el campo de confirmación
    confirmar.addEventListener("input", validar);
    // Al enviar el formulario valida otra vez y, si es inválido, cancela el envío (todo en una línea)
    form.addEventListener("submit", function (event) { if (!validar()) event.preventDefault(); });
});



/* --------------------------------------------------------------------------------------------------------------
   ---------------------------------------- FIN cambiar_password.js --------------------------------------
   -------------------------------------------------------------------------------------------------------------- */


// Sitio Web Creado por ITred Spa.
// Direccion: Guido Reni #4190
// Pedro Aguirre Cerda - Santiago - Chile
// contacto@itred.cl o itred.spa@gmail.com
// https://www.itred.cl
// Creado, Programado y Diseñado por ITred Spa.
// BPPJ
