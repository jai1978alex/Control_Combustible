// Sitio Web Creado por Jerez.
// Direccion: Miraflorez #1280
// Quintero - Chile
// jaime.jerez1978@gmail.com
// https://www.
// Creado, Programado y Diseñado por Jerez.
// JJA 

// 

/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- INICIO imprimir.js ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

// TITULO INICIALIZACION

// Espera a que la página cargue y activa los botones de la
// vista de impresión.

// Ejecuta el código de abajo cuando el HTML terminó de cargarse
document.addEventListener('DOMContentLoaded', function () {

    // TITULO REFERENCIAS A LOS BOTONES

    // Obtiene el botón Imprimir y el botón Cerrar.

    // Obtiene el botón que imprime la página
    const imprimir = document.getElementById('btnImprimir');
    // Obtiene el botón que cierra la ventana
    const cerrar = document.getElementById('btnCerrar');

    // TITULO ACCIONES DE LOS BOTONES

    // Al hacer clic, imprime o cierra la ventana (solo si el
    // botón existe en la página).

    // Si existe el botón Imprimir, al hacer clic abre el diálogo de impresión
    if (imprimir) imprimir.addEventListener('click', function () { window.print(); });
    // Si existe el botón Cerrar, al hacer clic cierra la ventana actual
    if (cerrar) cerrar.addEventListener('click', function () { window.close(); });
});



/* --------------------------------------------------------------------------------------------------------------
   ---------------------------------------- FIN imprimir.js --------------------------------------
   -------------------------------------------------------------------------------------------------------------- */


// Sitio Web Creado por Jerez.
// Direccion: Miraflorez #1280
// Quintero - Chile
// jaime.jerez1978@gmail.com
// https://www.
// Creado, Programado y Diseñado por Jerez.
// JJA
