// Sitio Web Creado por Jerez.
// Direccion: Miraflorez #1280
// Quintero - Chile
// jaime.jerez1978@gmail.com
// https://www.
// Creado, Programado y Diseñado por Jerez.
// JJA 

// 

/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- INICIO recuperar.js ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

// TITULO INICIALIZACION

// Espera a que la página cargue y activa el formulario de
// recuperación de contraseña.

// recuperar.js

// Ejecuta el código de abajo cuando el HTML terminó de cargarse
document.addEventListener("DOMContentLoaded", () => {

    // TITULO REFERENCIAS A LOS ELEMENTOS DE LA PAGINA

    // Obtiene el formulario y el campo de correo.

    // Obtiene el formulario de recuperación
    const form = document.getElementById("recuperarForm");
    // Obtiene el campo donde se escribe el correo
    const emailInput = document.getElementById("email");

    // Si el formulario no existe en esta página, termina
    if (!form) return;

    // TITULO ENVIO DEL FORMULARIO

    // Valida el correo, lo envía al servidor y muestra el
    // resultado.

    // Escucha el envío del formulario (función asíncrona porque espera al servidor)
    form.addEventListener("submit", async (e) => {
        // Evita que la página se recargue al enviar
        e.preventDefault();

        // Lee el correo y le quita espacios al inicio y al final
        const email = emailInput.value.trim();

        // Validación básica
        // Si el correo está vacío
        if (!email) {
            // Muestra un mensaje de error pidiendo el correo
            mostrarMensaje("Ingrese un correo electrónico", "error");
            // Detiene el proceso
            return;
        }

        // Si el formato del correo no es válido
        if (!validarEmail(email)) {
            // Muestra un mensaje de error de formato
            mostrarMensaje("Correo no válido", "error");
            // Detiene el proceso
            return;
        }

        // Intenta comunicarse con el servidor
        try {
            // Envía una petición al archivo PHP de recuperación y espera la respuesta
            const response = await fetch("../../backend/recuperar.php", {
                // Usa el método POST
                method: "POST",
                // Define las cabeceras de la petición
                headers: {
                    // Indica que el cuerpo se envía en formato JSON
                    "Content-Type": "application/json"
                },
                // Convierte el correo a texto JSON y lo envía como cuerpo
                body: JSON.stringify({ email })
            });

            // Convierte la respuesta del servidor de JSON a objeto
            const data = await response.json();

            // Muestra el mensaje que devolvió el servidor como éxito
            mostrarMensaje(data.message, "success");

            // Limpia el formulario
            form.reset();

        // Si ocurre un error de conexión, entra al catch
        } catch (error) {
            // Registra el error en la consola
            console.error(error);
            // Muestra un mensaje de error de conexión
            mostrarMensaje("Error al conectar con el servidor", "error");
        }
    });

    // TITULO VALIDAR CORREO

    // Comprueba que el texto tenga forma de correo electrónico.

    // Define la función que valida el formato del correo
    function validarEmail(email) {
        // Devuelve true si el texto tiene la forma algo@dominio.ext, sin espacios
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    // TITULO MOSTRAR MENSAJES

    // Muestra un mensaje bajo el formulario, en rojo si es
    // error o en verde si es éxito.

    // Define la función que muestra un mensaje con su tipo (error o success)
    function mostrarMensaje(mensaje, tipo) {
        // Busca el contenedor del mensaje si ya existe
        let div = document.getElementById("mensaje");

        // Si todavía no existe, entra para crearlo
        if (!div) {
            // Crea un elemento div nuevo
            div = document.createElement("div");
            // Le asigna el id "mensaje"
            div.id = "mensaje";
            // Lo agrega al final del formulario
            form.appendChild(div);
        }

        // Escribe el texto del mensaje
        div.textContent = mensaje;
        // Separa el mensaje 10 px del contenido de arriba
        div.style.marginTop = "10px";
        // Agrega 10 px de espacio interior
        div.style.padding = "10px";
        // Redondea las esquinas
        div.style.borderRadius = "8px";
        // Centra el texto
        div.style.textAlign = "center";

        // Si el mensaje es de tipo error
        if (tipo === "error") {
            // Fondo rojo claro
            div.style.backgroundColor = "#f8d7da";
            // Texto rojo oscuro
            div.style.color = "#721c24";
        // Si no es error, es éxito
        } else {
            // Fondo verde claro
            div.style.backgroundColor = "#d4edda";
            // Texto verde oscuro
            div.style.color = "#155724";
        }
    }

});



/* --------------------------------------------------------------------------------------------------------------
   ---------------------------------------- FIN recuperar.js --------------------------------------
   -------------------------------------------------------------------------------------------------------------- */


// Sitio Web Creado por Jerez.
// Direccion: Miraflorez #1280
// Quintero - Chile
// jaime.jerez1978@gmail.com
// https://www.
// Creado, Programado y Diseñado por Jerez.
// JJA
