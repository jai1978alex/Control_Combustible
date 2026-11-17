// Sitio Web Creado por Jerez.
// Direccion: Miraflorez #1280
// Quintero - Chile
// jaime.jerez1978@gmail.com
// https://www.
// Creado, Programado y Diseñado por Jerez.
// JJA 

// 

/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- INICIO restablecer.js ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

// TITULO INICIALIZACION

// Espera a que la página cargue y activa el formulario para
// restablecer la contraseña mediante un token.

// Ejecuta el código de abajo cuando el HTML terminó de cargarse
document.addEventListener('DOMContentLoaded',()=> {

    // TITULO REFERENCIAS A LOS ELEMENTOS DE LA PAGINA

    // Obtiene el formulario (f) y el espacio de mensajes (m).

    // Obtiene el formulario con id resetForm (f) y el contenedor de mensajes con id mensaje (m)
    const f=document.getElementById('resetForm'),m=document.getElementById('mensaje');
    // Si el formulario no existe en esta página, termina
    if(!f)return;

    // TITULO LECTURA DEL TOKEN DE LA URL

    // Toma el token del enlace y lo borra de la barra de
    // direcciones para que no quede visible.

    // Crea un lector de los parámetros de la dirección actual
    const params=new URLSearchParams(location.search);
    // Obtiene el parámetro token (o texto vacío si no existe) y lo guarda en t
    const t=params.get('token') || '';
    // Si hay token, reemplaza la URL por una sin parámetros, sin recargar la página
    if(t){ history.replaceState(null,'',location.pathname); }

    // TITULO ENVIO DEL FORMULARIO

    // Valida el token y la nueva contraseña, la envía al
    // servidor y redirige al inicio si todo sale bien.

    // Escucha el envío del formulario (función asíncrona porque espera al servidor)
    f.addEventListener('submit',async e=>{
        // Evita que la página se recargue al enviar
        e.preventDefault();
        // Lee la contraseña (p) y su repetición (p2)
        const p=document.getElementById('password').value,p2=document.getElementById('password2').value;
        // Comprueba en una sola condición (separada por ||) que:
        //  - el token tenga 64 caracteres hexadecimales
        //  - la contraseña tenga al menos 12 caracteres
        //  - la contraseña no pase de 255 caracteres
        //  - tenga al menos una mayúscula
        //  - tenga al menos una minúscula
        //  - tenga al menos un número
        //  - tenga al menos un símbolo
        //  - ambas contraseñas sean iguales
        // Si alguna regla falla, entra al bloque
        if(!/^[a-f0-9]{64}$/.test(t)||p.length<12||p.length>255||!/[A-Z]/.test(p)||!/[a-z]/.test(p)||!/[0-9]/.test(p)||!/[^A-Za-z0-9]/.test(p)||p!==p2){
            // Muestra el mensaje con los requisitos y termina el envío
            m.textContent='La contraseña debe tener 12 caracteres, mayúscula, minúscula, número y símbolo; ambas deben coincidir.';return;
        }
        // Intenta comunicarse con el servidor
        try{
            // Envía token y contraseña en JSON por POST al archivo PHP y guarda la respuesta en r
            const r=await fetch('../../backend/restablecer.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({token:t,password:p})});
            // Convierte la respuesta a objeto (d) y muestra el mensaje del servidor
            const d=await r.json();m.textContent=d.message;
            // Si el servidor respondió ok: limpia el formulario y, a los 2 segundos, redirige al inicio
            if(d.ok){f.reset();setTimeout(()=>location.href='/control_combustible/index.php',2000)}
        // Si falla la conexión, muestra un mensaje genérico de error
        }catch(_){m.textContent='No fue posible completar la operación.'}
    });
});



/* --------------------------------------------------------------------------------------------------------------
   ---------------------------------------- FIN restablecer.js --------------------------------------
   -------------------------------------------------------------------------------------------------------------- */


// Sitio Web Creado por Jerez.
// Direccion: Miraflorez #1280
// Quintero - Chile
// jaime.jerez1978@gmail.com
// https://www.
// Creado, Programado y Diseñado por Jerez.
// JJA
