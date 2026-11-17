// Sitio Web Creado por ITred Spa.
// Direccion: Guido Reni #4190
// Pedro Aguirre Cerda - Santiago - Chile
// contacto@itred.cl o itred.spa@gmail.com
// https://www.itred.cl
// Creado, Programado y Diseñado por ITred Spa.
// BPPJ 

// 

/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- INICIO imprimir.js ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */





/* ============================================================================
   BLOQUE 1 · INICIALIZACION
   ----------------------------------------------------------------------------
   Qué hace : Espera a que la página cargue y activa los botones de la
              vista de impresión.
   ============================================================================ */

// Ejecuta el código de abajo cuando el HTML terminó de cargarse
document.addEventListener('DOMContentLoaded', function () {

    /* ------------------------------------------------------------------------
       BLOQUE 2 · REFERENCIAS A LOS BOTONES
       ------------------------------------------------------------------------
       Qué hace : Obtiene el botón Imprimir y el botón Cerrar.
       ------------------------------------------------------------------------ */

    // Obtiene el botón que imprime la página
    const imprimir = document.getElementById('btnImprimir');
    // Obtiene el botón que cierra la ventana
    const cerrar = document.getElementById('btnCerrar');

    /* ------------------------------------------------------------------------
       BLOQUE 3 · ACCIONES DE LOS BOTONES
       ------------------------------------------------------------------------
       Qué hace : Al hacer clic, imprime o cierra la ventana (solo si el
                  botón existe en la página).
       ------------------------------------------------------------------------ */

    // Si existe el botón Imprimir, al hacer clic abre el diálogo de impresión
    if (imprimir) imprimir.addEventListener('click', function () { window.print(); });
    // Si existe el botón Cerrar, al hacer clic cierra la ventana actual
    if (cerrar) cerrar.addEventListener('click', function () { window.close(); });
// Cierra la función del DOMContentLoaded
});



/* --------------------------------------------------------------------------------------------------------------
   ---------------------------------------- FIN imprimir.js --------------------------------------
   -------------------------------------------------------------------------------------------------------------- */


// Sitio Web Creado por ITred Spa.
// Direccion: Guido Reni #4190
// Pedro Aguirre Cerda - Santiago - Chile
// contacto@itred.cl o itred.spa@gmail.com
// https://www.itred.cl
// Creado, Programado y Diseñado por ITred Spa.
// BPPJ
