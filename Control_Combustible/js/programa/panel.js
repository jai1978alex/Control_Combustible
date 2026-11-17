// Sitio Web Creado por ITred Spa.
// Direccion: Guido Reni #4190
// Pedro Aguirre Cerda - Santiago - Chile
// contacto@itred.cl o itred.spa@gmail.com
// https://www.itred.cl
// Creado, Programado y Diseñado por ITred Spa.
// BPPJ 

// 

/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- INICIO panel.js ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */





/* ============================================================================
   BLOQUE 1 · CONTROL DE UBICACION GPS
   ----------------------------------------------------------------------------
   Qué hace : Contiene mostrarMensaje (aviso flotante) y obtenerUbicacion
              (lee la ubicación GPS del navegador y la escribe en un campo).
   ============================================================================ */

/* ================================================= */
/* CONTROL DE UBICACION GPS                          */
/* ================================================= */

// ---- Función mostrarMensaje: muestra un aviso flotante temporal ----
// Define la función; "tipo" vale "error" si no se indica otro
function mostrarMensaje(texto, tipo = "error") {

    // Busca el aviso si ya existe en la página
    let alerta = document.getElementById("mensajeSistema");

    // Si todavía no existe, entra para crearlo
    if (!alerta) {
        // Crea un div nuevo
        alerta = document.createElement("div");
        // Le asigna el id para encontrarlo después
        alerta.id = "mensajeSistema";
        // Lo inserta al comienzo del body
        document.body.prepend(alerta);
    // Cierra el if de creación
    }

    // Asigna las clases CSS: la base más el tipo (error o success)
    alerta.className = "mensaje-sistema " + tipo;
    // Escribe el texto del mensaje
    alerta.innerText = texto;

    // Cancela el temporizador anterior para reiniciar la cuenta
    window.clearTimeout(alerta._timer);
    // Guarda un nuevo temporizador que ejecuta una acción a los 5 segundos
    alerta._timer = window.setTimeout(() => {
        // Verifica que el aviso exista y siga en la página
        if (alerta && alerta.parentNode) {
            // Elimina el aviso de la página
            alerta.remove();
        // Cierra el if de verificación
        }
    // Cierra la función del temporizador y fija su espera en 5000 ms
    }, 5000);
// Cierra la función mostrarMensaje
}

// ---- Función obtenerUbicacion: pide el GPS y lo escribe en el campo indicado ----
// Define la función; recibe el id del campo donde se guardará la ubicación
function obtenerUbicacion(idInput) {

    // Busca el campo de ubicación por su id
    const input = document.getElementById(idInput);

    // Si el campo no existe
    if (!input) {
        // Registra el problema en la consola
        console.error("No existe el input GPS:", idInput);
        // Avisa al usuario que no se encontró el campo
        mostrarMensaje("No se encontró el campo de ubicación GPS", "error");
        // Detiene la función
        return;
    // Cierra el if del campo inexistente
    }

    // Si la página no es segura (no HTTPS) y tampoco es localhost ni 127.0.0.1
    if (!window.isSecureContext && window.location.hostname !== "localhost" && window.location.hostname !== "127.0.0.1") {
        // Vacía el campo
        input.value = "";
        // Avisa que el GPS necesita HTTPS
        mostrarMensaje(
            "El GPS del navegador requiere HTTPS. Usa https:// o abre el sistema mediante localhost.",
            "error"
        );
        // Detiene la función
        return;
    // Cierra el if del contexto inseguro
    }

    // Si el navegador no tiene geolocalización
    if (!navigator.geolocation) {
        // Vacía el campo
        input.value = "";
        // Avisa que el navegador no permite obtener el GPS
        mostrarMensaje("Este navegador no permite obtener ubicación GPS", "error");
        // Detiene la función
        return;
    // Cierra el if de la falta de geolocalización
    }

    // Muestra un texto de espera en el campo mientras se obtiene la ubicación
    input.value = "Obteniendo ubicación...";

    // Pide la posición actual al navegador, con tres argumentos: éxito, error y opciones
    navigator.geolocation.getCurrentPosition(
        // ARGUMENTO 1: función que se ejecuta si se obtuvo la ubicación
        function (position) {
            // Guarda la latitud como número
            const lat = Number(position.coords.latitude);
            // Guarda la longitud como número
            const lon = Number(position.coords.longitude);
            // Guarda la precisión (en metros) como número
            const accuracy = Number(position.coords.accuracy);

            // Si la latitud o la longitud no son números válidos
            if (!Number.isFinite(lat) || !Number.isFinite(lon)) {
                // Vacía el campo
                input.value = "";
                // Avisa que la ubicación es inválida
                mostrarMensaje("El navegador devolvió una ubicación inválida", "error");
                // Detiene la función
                return;
            // Cierra el if de la ubicación inválida
            }

            // Escribe "latitud, longitud" con 6 decimales en el campo
            input.value = lat.toFixed(6) + ", " + lon.toFixed(6);
            // Guarda la latitud en un atributo data del campo
            input.dataset.latitude = lat.toFixed(6);
            // Guarda la longitud en un atributo data del campo
            input.dataset.longitude = lon.toFixed(6);
            // Guarda la precisión redondeada (o vacío si no es un número válido)
            input.dataset.accuracy = Number.isFinite(accuracy) ? Math.round(accuracy) : "";

            // Muestra un mensaje de éxito con dos argumentos: texto y tipo
            mostrarMensaje(
                // Si la precisión es válida
                Number.isFinite(accuracy)
                    // Texto que incluye la precisión aproximada en metros
                    ? "Ubicación obtenida. Precisión aproximada: " + Math.round(accuracy) + " m"
                    // Texto simple si no hay precisión
                    : "Ubicación obtenida correctamente",
                // Tipo del mensaje
                "success"
            // Cierra la llamada a mostrarMensaje
            );
        // Cierra la función de éxito
        },
        // ARGUMENTO 2: función que se ejecuta si ocurrió un error
        function (error) {
            // Variable donde se guardará el texto del error
            let mensaje;

            // Elige el mensaje según el código de error
            switch (error.code) {
                // Caso: el usuario negó el permiso
                case error.PERMISSION_DENIED:
                    // Mensaje de permiso denegado
                    mensaje = "Permiso de ubicación denegado. Activa la ubicación para este sitio y vuelve a intentarlo.";
                    // Sale del switch
                    break;
                // Caso: no se pudo determinar la posición
                case error.POSITION_UNAVAILABLE:
                    // Mensaje de posición no disponible
                    mensaje = "No fue posible determinar la ubicación. Verifica que el GPS/ubicación del dispositivo esté activado.";
                    // Sale del switch
                    break;
                // Caso: se agotó el tiempo de espera
                case error.TIMEOUT:
                    // Mensaje de tiempo agotado
                    mensaje = "La búsqueda de ubicación tardó demasiado. Intenta nuevamente en un lugar con mejor señal GPS.";
                    // Sale del switch
                    break;
                // Cualquier otro error
                default:
                    // Mensaje genérico
                    mensaje = "No fue posible obtener la ubicación GPS.";
            // Cierra el switch
            }

            // Vacía el campo
            input.value = "";
            // Borra la latitud guardada
            delete input.dataset.latitude;
            // Borra la longitud guardada
            delete input.dataset.longitude;
            // Borra la precisión guardada
            delete input.dataset.accuracy;
            // Muestra el mensaje de error elegido
            mostrarMensaje(mensaje, "error");
            // Registra el error técnico en la consola
            console.error("Error de geolocalización:", error);
        // Cierra la función de error
        },
        // ARGUMENTO 3: opciones de la búsqueda
        {
            // Pide la mayor precisión posible
            enableHighAccuracy: true,
            // Espera como máximo 30 segundos (30000 ms)
            timeout: 30000,
            // No acepta posiciones guardadas antiguas
            maximumAge: 0
        // Cierra las opciones
        }
    // Cierra la llamada a getCurrentPosition
    );
// Cierra la función obtenerUbicacion
}



/* ============================================================================
   BLOQUE 2 · RELOJ CHILE
   ----------------------------------------------------------------------------
   Qué hace : Escribe la fecha y la hora de Chile en pantalla.
   ============================================================================ */

/* ================================================= */
/* RELOJ CHILE - ACTUALIZACION EN TIEMPO REAL        */
/* ================================================= */
// Define la función que actualiza la fecha y la hora
function actualizarRelojChile() {
    // Busca el elemento donde se muestra la fecha
    const fechaEl = document.querySelector('.date-texto');
    // Busca el elemento donde se muestra la hora
    const horaEl = document.querySelector('.time-texto');
    // Si falta alguno de los dos, termina
    if (!fechaEl || !horaEl) return;

    // Obtiene la fecha y hora actuales
    const ahora = new Date();
    // Divide la fecha en partes (día, mes y año) con zona horaria de Santiago
    const partesFecha = new Intl.DateTimeFormat('es-CL', {
        // Opciones de formato: zona horaria, día, mes y año con 2 dígitos (año con 4)
        timeZone: 'America/Santiago', day: '2-digit', month: '2-digit', year: 'numeric'
    // Cierra las opciones y obtiene la lista de partes
    }).formatToParts(ahora);
    // Función auxiliar: devuelve el valor de una parte según su tipo (o texto vacío)
    const get = (tipo) => partesFecha.find(p => p.type === tipo)?.value || '';
    // Escribe la fecha con formato día/mes/año
    fechaEl.textContent = `${get('day')}/${get('month')}/${get('year')}`;
    // Escribe la hora con formato de 24 horas (hora:minuto:segundo)
    horaEl.textContent = new Intl.DateTimeFormat('es-CL', {
        // Opciones de formato: zona horaria, hora, minuto y segundo con 2 dígitos, sin AM/PM
        timeZone: 'America/Santiago', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
    // Cierra las opciones y aplica el formato a la hora actual
    }).format(ahora);
// Cierra la función actualizarRelojChile
}

/* ============================================================================
   BLOQUE 3 · QR: INTERPRETACION Y CARGA AUTOMATICA
   ----------------------------------------------------------------------------
   Qué hace : Interpreta el texto leído desde un QR y completa los campos
              del formulario que correspondan.
   ============================================================================ */

/* ================================================= */
/* QR: INTERPRETACION Y CARGA AUTOMATICA             */
/* ================================================= */

// ---- Función normalizarClaveQR: deja un nombre de campo en minúsculas y sin símbolos ----
// Define la función; recibe el nombre del campo
function normalizarClaveQR(clave) {
    // Convierte a texto (vacío si no hay valor)
    return String(clave || '')
        // Separa las letras de sus tildes y quita las tildes
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        // Pasa a minúsculas y quita todo lo que no sea letra o número
        .toLowerCase().replace(/[^a-z0-9]/g, '');
// Cierra la función normalizarClaveQR
}

// ---- Función parsearDatosQR: convierte el texto del QR en un objeto clave-valor ----
// Define la función; recibe el texto leído del QR
function parsearDatosQR(texto) {
    // Convierte a texto y quita espacios en los extremos
    let valor = String(texto || '').trim();
    // Si no hay texto, devuelve un objeto vacío
    if (!valor) return {};

    // Primer intento: leer el texto como JSON
    try {
        // Convierte el texto JSON en un objeto
        const json = JSON.parse(valor);
        // Si es un objeto válido (no nulo y no lista), lo devuelve
        if (json && typeof json === 'object' && !Array.isArray(json)) return json;
    // Si no era JSON, ignora el error y sigue con el siguiente intento
    } catch (_) {}

    // Acepta query string: rut=...&nombres=...
    // Segundo intento: leer el texto como parámetros de dirección (clave=valor&...)
    try {
        // Usa solo lo que está después del último "?" si lo hay
        const params = new URLSearchParams(valor.includes('?') ? valor.split('?').pop() : valor);
        // Crea un objeto vacío para guardar los pares
        const objeto = {};
        // Copia cada par clave-valor al objeto
        params.forEach((v, k) => objeto[k] = v);
        // Si el objeto tiene al menos una clave, lo devuelve
        if (Object.keys(objeto).length) return objeto;
    // Si falla, ignora el error y sigue con el siguiente intento
    } catch (_) {}

    // Acepta líneas tipo "RUT: 12.345.678-9" o "rut=..."
    // Tercer intento: leer el texto línea por línea
    const objeto = {};
    // Divide el texto por saltos de línea, punto y coma o barra vertical y recorre cada parte
    valor.split(/[\r\n;|]+/).forEach(parte => {
        // Busca el patrón "nombre: valor" o "nombre=valor"
        const m = parte.match(/^\s*([^:=]+?)\s*[:=]\s*(.*?)\s*$/);
        // Si encontró el patrón, guarda el nombre y el valor sin espacios
        if (m) objeto[m[1].trim()] = m[2].trim();
    // Cierra el recorrido de las partes
    });
    // Devuelve el objeto armado
    return objeto;
// Cierra la función parsearDatosQR
}

// ---- Función completarFormularioDesdeQR: rellena el formulario con los datos del QR ----
// Define la función; recibe el texto leído del QR
async function completarFormularioDesdeQR(texto) {
    // Antes de usar los datos, el servidor comprueba que el QR traiga un RUT chileno válido (de cualquier operador).
    const csrf = document.querySelector('input[name="csrf_token"]')?.value || '';
    if (!csrf) {
        if (window.Toast) Toast.error('No se pudo validar la sesión. Recarga la página e inténtalo nuevamente.');
        return;
    }

    try {
        const respuesta = await fetch('../../backend/validar_qr_identidad.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: new URLSearchParams({ qr: String(texto || ''), csrf_token: csrf }).toString()
        });
        const validacion = await respuesta.json();
        if (!respuesta.ok || !validacion.ok) {
            if (window.Toast) Toast.error(validacion.mensaje || 'No fue posible validar la identidad del QR.');
            return;
        }
    } catch (error) {
        if (window.Toast) Toast.error('No se pudo comprobar la identidad con el servidor. Revisa tu conexión.');
        return;
    }

    // Convierte el texto del QR en un objeto de datos
    const datos = parsearDatosQR(texto);
    // Objeto donde se guardarán los datos con nombres normalizados
    const claves = {};
    // Copia cada dato usando su nombre normalizado como clave
    Object.keys(datos).forEach(k => claves[normalizarClaveQR(k)] = datos[k]);

    // Tabla de alias: nombre del campo del formulario y nombres alternativos que acepta el QR
    const alias = {
        // Campo nombres
        nombres: ['nombres','nombre','name'],
        // Campo apellido paterno
        apellidoPaterno: ['apellidopaterno','paterno','apellido1'],
        // Campo apellido materno
        apellidoMaterno: ['apellidomaterno','materno','apellido2'],
        // Campo RUT
        rut: ['rut','documento','documentoidentidad'],
        // Campo turno
        turno: ['turno'],
        // Campo código del cargador
        codigo: ['codigo','codigocargador','codigoequipo'],
        // Campo código de la maquinaria
        codigoMaquinaria: ['codigomaquinaria','codigomaquina','maquinaria'],
        // Campo ubicación
        ubicacion: ['ubicacion','gps','coordenadas','geolocalizacion'],
        // Campo patente
        patente: ['patente','placa'],
        // Campo equipo
        equipo: ['equipo','tipoequipo'],
        // Campo horómetro
        horometro: ['horometro','horometroactual'],
        // Campo kilómetro
        kilometro: ['kilometro','kilometraje','km'],
        // Campo litros
        litros: ['litros','cantidadlitros'],
        // Campo observación
        observacion: ['observacion','observaciones','comentario']
    // Cierra la tabla de alias
    };

    // Define una función que busca el valor de un campo probando todos sus alias
    const obtener = (campo) => {
        // Recorre cada alias del campo (o una lista vacía si no existe)
        for (const k of alias[campo] || []) {
            // Si el QR contiene ese alias, devuelve su valor como texto sin espacios
            if (Object.prototype.hasOwnProperty.call(claves, k)) return String(claves[k] ?? '').trim();
        // Cierra el for
        }
        // Si no encontró ningún alias, devuelve texto vacío
        return '';
    // Cierra la función obtener
    };

    // Obtiene todos los formularios marcados como confirmables
    let formularios = document.querySelectorAll('.formulario-confirmar');
    // Indica si el QR trae datos de maquinaria (código de maquinaria, equipo o kilómetro)
    const tieneMaquinaria = obtener('codigoMaquinaria') || obtener('equipo') || obtener('kilometro');
    // Indica si el QR trae solo datos de cargador (código sin datos de maquinaria)
    const tieneCargador = obtener('codigo') && !tieneMaquinaria;
    // Elige el formulario: operador si hay maquinaria, cargador si hay cargador, o ninguno
    let formulario = tieneMaquinaria ? document.getElementById('formOperador') : (tieneCargador ? document.getElementById('formCargador') : null);
    // Si no se eligió ninguno, usa el primer formulario confirmable
    if (!formulario) formulario = formularios[0];

    // Contador de campos que se lograron completar
    let completados = 0;
    // Define una función que asigna un valor a un campo del formulario por su nombre
    const asignar = (nombre, valor) => {
        // Si no hay valor, no hace nada
        if (!valor) return;
        // Busca en el formulario el campo cuyo atributo name coincide
        const input = formulario.querySelector(`[name="${nombre}"]`);
        // Si el campo no existe, no hace nada
        if (!input) return;
        // Escribe el valor en el campo
        input.value = valor;
        // Dispara el evento "input" para que otras funciones reaccionen
        input.dispatchEvent(new Event('input', { bubbles: true }));
        // Dispara el evento "change" para que otras funciones reaccionen
        input.dispatchEvent(new Event('change', { bubbles: true }));
        // Suma uno al contador de campos completados
        completados++;
    // Cierra la función asignar
    };

    // Completa el campo nombres
    asignar('nombres', obtener('nombres'));
    // Completa el apellido paterno
    asignar('apellidoPaterno', obtener('apellidoPaterno'));
    // Completa el apellido materno
    asignar('apellidoMaterno', obtener('apellidoMaterno'));
    // Completa el RUT
    asignar('rut', obtener('rut'));
    // Completa el turno en mayúsculas
    asignar('turno', obtener('turno').toUpperCase());
    // Completa el código
    asignar('codigo', obtener('codigo'));
    // Completa el código de maquinaria
    asignar('codigoMaquinaria', obtener('codigoMaquinaria'));
    // Completa la ubicación
    asignar('ubicacion', obtener('ubicacion'));
    // Completa la patente en mayúsculas
    asignar('patente', obtener('patente').toUpperCase());
    // Completa el equipo
    asignar('equipo', obtener('equipo'));
    // Completa el horómetro
    asignar('horometro', obtener('horometro'));
    // Completa el kilómetro
    asignar('kilometro', obtener('kilometro'));
    // Completa los litros
    asignar('litros', obtener('litros'));
    // Completa la observación
    asignar('observacion', obtener('observacion'));

    // Si no se completó ningún campo
    if (completados === 0) {
        // Si existe Toast, muestra un aviso de error
        if (window.Toast) Toast.error('El QR fue leído, pero no contiene campos reconocibles para este formulario.');
        // Detiene la función
        return;
    // Cierra el if de cero campos
    }

    // Si existe Toast, muestra un aviso de éxito con la cantidad de campos completados
    if (window.Toast) Toast.success(`QR leído correctamente. Se completaron ${completados} campos.`);
    // Desplaza la pantalla suavemente hasta el formulario
    formulario.scrollIntoView({ behavior: 'smooth', block: 'start' });
// Cierra la función completarFormularioDesdeQR
}


/* ============================================================================
   BLOQUE 4 · INICIO DE LA PAGINA (DOM READY)
   ----------------------------------------------------------------------------
   Qué hace : Cuando la página termina de cargar, activa el reloj, el
              escáner QR, los botones GPS, los mensajes de PHP, el menú, la
              validación de RUT y el total de litros.
   ============================================================================ */

/* ================================================= */
/* DOM READY                                         */
/* ================================================= */

// Ejecuta el código de abajo cuando el HTML terminó de cargarse
document.addEventListener("DOMContentLoaded", () => {

    // ---- 4.1 Reloj: se muestra al cargar y se actualiza cada segundo ----
    // Muestra la fecha y hora de inmediato
    actualizarRelojChile();
    // Repite la actualización cada 1000 ms (1 segundo)
    window.setInterval(actualizarRelojChile, 1000);

    // ---- 4.2 Escáner QR: botón que abre el lector ----
    /* ESCANER QR */
    // Obtiene el botón que abre el escáner
    const btnQR = document.getElementById('btnQRScanner');
    // Si el módulo QRScanner está disponible
    if (window.QRScanner) {
        // Inicia el escáner indicando qué función recibe el texto leído
        QRScanner.init(completarFormularioDesdeQR);
        // Si el botón existe
        if (btnQR) {
            // Escucha el clic en el botón
            btnQR.addEventListener('click', (event) => {
                // Evita la acción por defecto del botón
                event.preventDefault();
                // Obtiene el menú desplegable
                const menu = document.getElementById('menuDropdown');
                // Si el menú existe, lo cierra
                if (menu) menu.classList.remove('active');
                // Abre el escáner QR
                QRScanner.start();
            // Cierra el evento click
            });
        // Cierra el if del botón
        }
    // Cierra el if de QRScanner
    }

    // ---- 4.3 Botones GPS: cada botón obtiene la ubicación en su campo ----
    /* BOTONES GPS */
    // Busca los botones .btn-location que tengan el atributo data-gps-target y recorre cada uno
    document.querySelectorAll(".btn-location[data-gps-target]").forEach(button => {
        // Escucha el clic del botón
        button.addEventListener("click", () => {
            // Pide la ubicación y la escribe en el campo indicado por data-gps-target
            obtenerUbicacion(button.dataset.gpsTarget);
        // Cierra el evento click
        });
    // Cierra el recorrido de los botones
    });

    // ---- 4.4 Mensajes de éxito y error que llegan desde PHP por la URL ----
    /* MENSAJES DEVUELTOS POR PHP */
    // Crea un lector de los parámetros de la dirección actual
    const parametros = new URLSearchParams(window.location.search);

    // Si la URL trae el parámetro success u ok
    if (parametros.has("success") || parametros.has("ok")) {
        // Tabla de mensajes de éxito según el código recibido
        const mensajesExito = {
            // Mensaje para el código password_actualizada
            password_actualizada: "Contraseña actualizada correctamente"
        // Cierra la tabla
        };
        // Lee el código de éxito de la URL
        const codigoExito = parametros.get("success");
        // Elige el mensaje del código o, si no existe, uno genérico
        const mensajeExito = mensajesExito[codigoExito] || "Datos guardados correctamente";

        // Si Toast existe y tiene la función success
        if (window.Toast && typeof Toast.success === "function") {
            // Muestra el aviso de éxito
            Toast.success(mensajeExito);
        // Cierra el if de Toast
        }
    // Cierra el if de éxito
    }

    // Si la URL trae el parámetro error
    if (parametros.has("error")) {
        // Tabla de mensajes de error según el código recibido
        const mensajesError = {
            // Mensaje para el código campos
            campos: "Completa todos los campos obligatorios",
            // Mensaje para el código rut
            rut: "El RUT ingresado no es válido",
            // Mensaje para el código acceso
            acceso: "Acceso no autorizado",
            // Mensaje para el código demasiados_intentos
            demasiados_intentos: "Demasiados registros guardados en poco tiempo. Espera unos minutos e intenta de nuevo."
        // Cierra la tabla
        };

        // Lee el código de error de la URL
        const codigoError = parametros.get("error");
        // Elige el mensaje del código o, si no existe, uno genérico
        const mensajeError = mensajesError[codigoError] || "No fue posible guardar el registro";

        // Si Toast existe y tiene la función error
        if (window.Toast && typeof Toast.error === "function") {
            // Muestra el aviso de error
            Toast.error(mensajeError);
        // Cierra el if de Toast
        }
    // Cierra el if de error
    }

    // ---- 4.5 Menú desplegable: abrir/cerrar ----
    /* MENU */
    // Obtiene el botón que abre y cierra el menú
    const btnMenu = document.getElementById("btnMenuToggle");
    // Obtiene el menú desplegable
    const menu = document.getElementById("menuDropdown");

    // Si existen ambos elementos
    if (btnMenu && menu) {
        // Escucha el clic en el botón del menú
        btnMenu.addEventListener("click", (e) => {
            // Evita que el clic llegue al documento (para que no cierre el menú enseguida)
            e.stopPropagation();
            // Abre el menú si estaba cerrado, o lo cierra si estaba abierto
            menu.classList.toggle("active");
        // Cierra el evento click del botón
        });

        // Escucha los clics en todo el documento
        document.addEventListener("click", (e) => {
            // Si el clic fue fuera del botón y fuera del menú
            if (!btnMenu.contains(e.target) && !menu.contains(e.target)) {
                // Cierra el menú
                menu.classList.remove("active");
            // Cierra el if del clic externo
            }
        // Cierra el evento click del documento
        });
    // Cierra el if del menú
    }

    // ---- 4.6 Validación de RUT en los campos del formulario ----
    /* VALIDACION RUT */
    // Función que comprueba el formato, por ejemplo 12.345.678-9 o 1.234.567-K
    function validarFormatoRut(rut) {
        // Devuelve true si cumple el patrón de puntos, guion y dígito verificador
        return /^\d{1,2}\.\d{3}\.\d{3}-[\dkK]{1}$/.test(rut);
    // Cierra validarFormatoRut
    }

    // Función que comprueba el dígito verificador usando el módulo ValidadorRut
    function validarRut(rut) {
        // Si ValidadorRut existe, valida el RUT; si no existe, devuelve false
        return window.ValidadorRut ? window.ValidadorRut.validate(rut) : false;
    // Cierra validarRut
    }

    // Función que activa la validación en un campo de RUT y su texto de error
    function activarValidadorRut(inputId, errorId) {
        // Obtiene el campo de RUT
        const input = document.getElementById(inputId);
        // Obtiene el elemento donde se muestra el mensaje
        const error = document.getElementById(errorId);

        // Si falta alguno de los dos, termina
        if (!input || !error) return;

        // Cada vez que se escribe en el campo
        input.addEventListener("input", () => {
            // Lee el RUT sin espacios en los extremos
            const rut = input.value.trim();

            // Si el campo quedó vacío
            if (rut === "") {
                // Borra el mensaje
                error.textContent = "";
                // Quita el color del borde
                input.style.borderColor = "";
                // Detiene la validación
                return;
            // Cierra el if del campo vacío
            }

            // Si el formato no es correcto
            if (!validarFormatoRut(rut)) {
                // Muestra "Formato inválido"
                error.textContent = "Formato inválido";
                // Pinta el borde de rojo
                input.style.borderColor = "#dc2626";
                // Detiene la validación
                return;
            // Cierra el if del formato
            }

            // Si el dígito verificador no es correcto
            if (!validarRut(rut)) {
                // Muestra "RUT inválido"
                error.textContent = "RUT inválido";
                // Pinta el borde de rojo
                input.style.borderColor = "#dc2626";
                // Detiene la validación
                return;
            // Cierra el if del dígito verificador
            }

            // Si todo es correcto, muestra el mensaje de RUT válido
            error.textContent = "✔ RUT válido";
            // Pinta el mensaje de verde
            error.style.color = "#16a34a";
            // Pinta el borde de verde
            input.style.borderColor = "#16a34a";
        // Cierra el evento input
        });
    // Cierra activarValidadorRut
    }

    // Activa la validación para el RUT del cargador
    activarValidadorRut("rutCargador", "rutErrorCargador");
    // Activa la validación para el RUT del operador
    activarValidadorRut("rutOperador", "rutErrorOperador");

    // ---- 4.7 Total de litros acumulados ----
    /* TOTAL LITROS ACUMULADOS */
    // El total base viene desde la base de datos mediante panel.php.
    // Mientras se escribe un nuevo registro se muestra el total proyectado
    // (total guardado + litros que aún no se han confirmado).
    // Función que recalcula y muestra el total; recibe el selector del campo de litros y el id del total
    function actualizarTotalAcumulado(inputSelector, totalId) {
        // Obtiene el campo de litros
        const input = document.querySelector(inputSelector);
        // Obtiene el elemento donde se muestra el total
        const totalElement = document.getElementById(totalId);

        // Si el elemento del total no existe, termina
        if (!totalElement) return;

        // Lee el total guardado en la base de datos (atributo data-base-total) o 0 si no es válido
        const totalGuardado = parseFloat(totalElement.dataset.baseTotal) || 0;
        // Lee los litros escritos en el campo (o 0 si está vacío o no hay campo)
        const litrosActuales = input ? (parseFloat(input.value) || 0) : 0;

        // Muestra la suma con 2 decimales
        totalElement.innerText = (totalGuardado + litrosActuales).toFixed(2);
    // Cierra actualizarTotalAcumulado
    }

    // Recorre los campos de litros del cargador
    document.querySelectorAll(".litros-cargador").forEach(input => {
        // Cada vez que se escribe en el campo
        input.addEventListener("input", () => {
            // Recalcula el total del cargador
            actualizarTotalAcumulado(".litros-cargador", "totalLitrosCargador");
        // Cierra el evento input
        });
    // Cierra el recorrido de los campos del cargador
    });

    // Recorre los campos de litros del operador
    document.querySelectorAll(".litros-operador").forEach(input => {
        // Cada vez que se escribe en el campo
        input.addEventListener("input", () => {
            // Recalcula el total del operador
            actualizarTotalAcumulado(".litros-operador", "totalLitrosOperador");
        // Cierra el evento input
        });
    // Cierra el recorrido de los campos del operador
    });
// Cierra la función del DOMContentLoaded
});



/* --------------------------------------------------------------------------------------------------------------
   ---------------------------------------- FIN panel.js --------------------------------------
   -------------------------------------------------------------------------------------------------------------- */


// Sitio Web Creado por ITred Spa.
// Direccion: Guido Reni #4190
// Pedro Aguirre Cerda - Santiago - Chile
// contacto@itred.cl o itred.spa@gmail.com
// https://www.itred.cl
// Creado, Programado y Diseñado por ITred Spa.
// BPPJ
