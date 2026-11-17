// Sitio Web Creado por ITred Spa.
// Direccion: Guido Reni #4190
// Pedro Aguirre Cerda - Santiago - Chile
// contacto@itred.cl o itred.spa@gmail.com
// https://www.itred.cl
// Creado, Programado y Diseñado por ITred Spa.
// BPPJ 

// 

/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- INICIO crear_usuario.js ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */





/* ============================================================================
   BLOQUE 1 · INICIALIZACION
   ----------------------------------------------------------------------------
   Qué hace : Espera a que la página cargue y activa la validación del
              formulario de creación de usuario.
   ============================================================================ */

// Ejecuta el código de abajo cuando el HTML terminó de cargarse
document.addEventListener("DOMContentLoaded", function () {

    /* ------------------------------------------------------------------------
       BLOQUE 2 · REFERENCIAS A LOS ELEMENTOS DE LA PAGINA
       ------------------------------------------------------------------------
       Qué hace : Obtiene los campos de contraseña, el texto de error y
                  el formulario.
       ------------------------------------------------------------------------ */

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

    /* ------------------------------------------------------------------------
       BLOQUE 3 · FUNCION DE VALIDACION
       ------------------------------------------------------------------------
       Qué hace : Comprueba que ambas contraseñas coincidan y muestra o
                  limpia el mensaje de error. Devuelve true si son válidas.
       ------------------------------------------------------------------------ */

    // Define la función que valida que las contraseñas coincidan
    function validar() {
        // Si el campo de confirmación tiene texto y es distinto de la contraseña
        if (confirmar.value && confirmar.value !== nueva.value) {
            // Muestra el mensaje de error
            error.textContent = "Las contraseñas no coinciden";
            // Indica que la validación falló
            return false;
        // Cierra el if de la comparación
        }
        // Borra cualquier mensaje de error anterior
        error.textContent = "";
        // Indica que la validación fue correcta
        return true;
    // Cierra la función validar
    }

    /* ------------------------------------------------------------------------
       BLOQUE 4 · EVENTOS
       ------------------------------------------------------------------------
       Qué hace : Valida mientras se escribe y bloquea el envío del
                  formulario si las contraseñas no coinciden.
       ------------------------------------------------------------------------ */

    // Valida cada vez que se escribe en el campo de contraseña
    nueva.addEventListener("input", validar);
    // Valida cada vez que se escribe en el campo de confirmación
    confirmar.addEventListener("input", validar);
    // Escucha el envío del formulario
    form.addEventListener("submit", function (event) {
        // Si la validación falla, cancela el envío
        if (!validar()) event.preventDefault();
    // Cierra el evento submit
    });
// Cierra la función del DOMContentLoaded
});



/* --------------------------------------------------------------------------------------------------------------
   ---------------------------------------- FIN crear_usuario.js --------------------------------------
   -------------------------------------------------------------------------------------------------------------- */


// Sitio Web Creado por ITred Spa.
// Direccion: Guido Reni #4190
// Pedro Aguirre Cerda - Santiago - Chile
// contacto@itred.cl o itred.spa@gmail.com
// https://www.itred.cl
// Creado, Programado y Diseñado por ITred Spa.
// BPPJ
