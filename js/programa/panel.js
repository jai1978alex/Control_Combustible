/* ================================================= */
/* CONTROL DE UBICACION GPS                          */
/* ================================================= */

function mostrarMensaje(texto, tipo = "error") {

    let alerta = document.getElementById("mensajeSistema");

    if (!alerta) {
        alerta = document.createElement("div");
        alerta.id = "mensajeSistema";
        document.body.prepend(alerta);
    }

    alerta.className = "mensaje-sistema " + tipo;
    alerta.innerText = texto;

    window.clearTimeout(alerta._timer);
    alerta._timer = window.setTimeout(() => {
        if (alerta && alerta.parentNode) {
            alerta.remove();
        }
    }, 5000);
}

function obtenerUbicacion(idInput) {

    const input = document.getElementById(idInput);

    if (!input) {
        console.error("No existe el input GPS:", idInput);
        mostrarMensaje("No se encontró el campo de ubicación GPS", "error");
        return;
    }

    if (!window.isSecureContext && window.location.hostname !== "localhost" && window.location.hostname !== "127.0.0.1") {
        input.value = "";
        mostrarMensaje(
            "El GPS del navegador requiere HTTPS. Usa https:// o abre el sistema mediante localhost.",
            "error"
        );
        return;
    }

    if (!navigator.geolocation) {
        input.value = "";
        mostrarMensaje("Este navegador no permite obtener ubicación GPS", "error");
        return;
    }

    input.value = "Obteniendo ubicación...";

    navigator.geolocation.getCurrentPosition(
        function (position) {
            const lat = Number(position.coords.latitude);
            const lon = Number(position.coords.longitude);
            const accuracy = Number(position.coords.accuracy);

            if (!Number.isFinite(lat) || !Number.isFinite(lon)) {
                input.value = "";
                mostrarMensaje("El navegador devolvió una ubicación inválida", "error");
                return;
            }

            input.value = lat.toFixed(6) + ", " + lon.toFixed(6);
            input.dataset.latitude = lat.toFixed(6);
            input.dataset.longitude = lon.toFixed(6);
            input.dataset.accuracy = Number.isFinite(accuracy) ? Math.round(accuracy) : "";

            mostrarMensaje(
                Number.isFinite(accuracy)
                    ? "Ubicación obtenida. Precisión aproximada: " + Math.round(accuracy) + " m"
                    : "Ubicación obtenida correctamente",
                "success"
            );
        },
        function (error) {
            let mensaje;

            switch (error.code) {
                case error.PERMISSION_DENIED:
                    mensaje = "Permiso de ubicación denegado. Activa la ubicación para este sitio y vuelve a intentarlo.";
                    break;
                case error.POSITION_UNAVAILABLE:
                    mensaje = "No fue posible determinar la ubicación. Verifica que el GPS/ubicación del dispositivo esté activado.";
                    break;
                case error.TIMEOUT:
                    mensaje = "La búsqueda de ubicación tardó demasiado. Intenta nuevamente en un lugar con mejor señal GPS.";
                    break;
                default:
                    mensaje = "No fue posible obtener la ubicación GPS.";
            }

            input.value = "";
            delete input.dataset.latitude;
            delete input.dataset.longitude;
            delete input.dataset.accuracy;
            mostrarMensaje(mensaje, "error");
            console.error("Error de geolocalización:", error);
        },
        {
            enableHighAccuracy: true,
            timeout: 30000,
            maximumAge: 0
        }
    );
}



/* ================================================= */
/* RELOJ CHILE - ACTUALIZACION EN TIEMPO REAL        */
/* ================================================= */
function actualizarRelojChile() {
    const fechaEl = document.querySelector('.date-texto');
    const horaEl = document.querySelector('.time-texto');
    if (!fechaEl || !horaEl) return;

    const ahora = new Date();
    const partesFecha = new Intl.DateTimeFormat('es-CL', {
        timeZone: 'America/Santiago', day: '2-digit', month: '2-digit', year: 'numeric'
    }).formatToParts(ahora);
    const get = (tipo) => partesFecha.find(p => p.type === tipo)?.value || '';
    fechaEl.textContent = `${get('day')}/${get('month')}/${get('year')}`;
    horaEl.textContent = new Intl.DateTimeFormat('es-CL', {
        timeZone: 'America/Santiago', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
    }).format(ahora);
}

/* ================================================= */
/* QR: INTERPRETACION Y CARGA AUTOMATICA             */
/* ================================================= */
function normalizarClaveQR(clave) {
    return String(clave || '')
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .toLowerCase().replace(/[^a-z0-9]/g, '');
}

function parsearDatosQR(texto) {
    let valor = String(texto || '').trim();
    if (!valor) return {};

    try {
        const json = JSON.parse(valor);
        if (json && typeof json === 'object' && !Array.isArray(json)) return json;
    } catch (_) {}

    // Acepta query string: rut=...&nombres=...
    try {
        const params = new URLSearchParams(valor.includes('?') ? valor.split('?').pop() : valor);
        const objeto = {};
        params.forEach((v, k) => objeto[k] = v);
        if (Object.keys(objeto).length) return objeto;
    } catch (_) {}

    // Acepta líneas tipo "RUT: 12.345.678-9" o "rut=..."
    const objeto = {};
    valor.split(/[\r\n;|]+/).forEach(parte => {
        const m = parte.match(/^\s*([^:=]+?)\s*[:=]\s*(.*?)\s*$/);
        if (m) objeto[m[1].trim()] = m[2].trim();
    });
    return objeto;
}

function completarFormularioDesdeQR(texto) {
    const datos = parsearDatosQR(texto);
    const claves = {};
    Object.keys(datos).forEach(k => claves[normalizarClaveQR(k)] = datos[k]);

    const alias = {
        nombres: ['nombres','nombre','name'],
        apellidoPaterno: ['apellidopaterno','paterno','apellido1'],
        apellidoMaterno: ['apellidomaterno','materno','apellido2'],
        rut: ['rut','documento','documentoidentidad'],
        turno: ['turno'],
        codigo: ['codigo','codigocargador','codigoequipo'],
        codigoMaquinaria: ['codigomaquinaria','codigomaquina','maquinaria'],
        ubicacion: ['ubicacion','gps','coordenadas','geolocalizacion'],
        patente: ['patente','placa'],
        equipo: ['equipo','tipoequipo'],
        horometro: ['horometro','horometroactual'],
        kilometro: ['kilometro','kilometraje','km'],
        litros: ['litros','cantidadlitros'],
        observacion: ['observacion','observaciones','comentario']
    };

    const obtener = (campo) => {
        for (const k of alias[campo] || []) {
            if (Object.prototype.hasOwnProperty.call(claves, k)) return String(claves[k] ?? '').trim();
        }
        return '';
    };

    let formularios = document.querySelectorAll('.formulario-confirmar');
    const tieneMaquinaria = obtener('codigoMaquinaria') || obtener('equipo') || obtener('kilometro');
    const tieneCargador = obtener('codigo') && !tieneMaquinaria;
    let formulario = tieneMaquinaria ? document.getElementById('formOperador') : (tieneCargador ? document.getElementById('formCargador') : null);
    if (!formulario) formulario = formularios[0];

    let completados = 0;
    const asignar = (nombre, valor) => {
        if (!valor) return;
        const input = formulario.querySelector(`[name="${nombre}"]`);
        if (!input) return;
        input.value = valor;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        completados++;
    };

    asignar('nombres', obtener('nombres'));
    asignar('apellidoPaterno', obtener('apellidoPaterno'));
    asignar('apellidoMaterno', obtener('apellidoMaterno'));
    asignar('rut', obtener('rut'));
    asignar('turno', obtener('turno').toUpperCase());
    asignar('codigo', obtener('codigo'));
    asignar('codigoMaquinaria', obtener('codigoMaquinaria'));
    asignar('ubicacion', obtener('ubicacion'));
    asignar('patente', obtener('patente').toUpperCase());
    asignar('equipo', obtener('equipo'));
    asignar('horometro', obtener('horometro'));
    asignar('kilometro', obtener('kilometro'));
    asignar('litros', obtener('litros'));
    asignar('observacion', obtener('observacion'));

    if (completados === 0) {
        if (window.Toast) Toast.error('El QR fue leído, pero no contiene campos reconocibles para este formulario.');
        return;
    }

    if (window.Toast) Toast.success(`QR leído correctamente. Se completaron ${completados} campos.`);
    formulario.scrollIntoView({ behavior: 'smooth', block: 'start' });
}


/* ================================================= */
/* DOM READY                                         */
/* ================================================= */

document.addEventListener("DOMContentLoaded", () => {

    actualizarRelojChile();
    window.setInterval(actualizarRelojChile, 1000);

    /* ESCANER QR */
    const btnQR = document.getElementById('btnQRScanner');
    if (window.QRScanner) {
        QRScanner.init(completarFormularioDesdeQR);
        if (btnQR) {
            btnQR.addEventListener('click', (event) => {
                event.preventDefault();
                const menu = document.getElementById('menuDropdown');
                if (menu) menu.classList.remove('active');
                QRScanner.start();
            });
        }
    }

    /* BOTONES GPS */
    document.querySelectorAll(".btn-location[data-gps-target]").forEach(button => {
        button.addEventListener("click", () => {
            obtenerUbicacion(button.dataset.gpsTarget);
        });
    });

    /* MENSAJES DEVUELTOS POR PHP */
    const parametros = new URLSearchParams(window.location.search);

    if (parametros.has("success") || parametros.has("ok")) {
        const mensajesExito = {
            password_actualizada: "Contraseña actualizada correctamente"
        };
        const codigoExito = parametros.get("success");
        const mensajeExito = mensajesExito[codigoExito] || "Datos guardados correctamente";

        if (window.Toast && typeof Toast.success === "function") {
            Toast.success(mensajeExito);
        }
    }

    if (parametros.has("error")) {
        const mensajesError = {
            campos: "Completa todos los campos obligatorios",
            rut: "El RUT ingresado no es válido",
            acceso: "Acceso no autorizado",
            demasiados_intentos: "Demasiados registros guardados en poco tiempo. Espera unos minutos e intenta de nuevo."
        };

        const codigoError = parametros.get("error");
        const mensajeError = mensajesError[codigoError] || "No fue posible guardar el registro";

        if (window.Toast && typeof Toast.error === "function") {
            Toast.error(mensajeError);
        }
    }

    /* MENU */
    const btnMenu = document.getElementById("btnMenuToggle");
    const menu = document.getElementById("menuDropdown");

    if (btnMenu && menu) {
        btnMenu.addEventListener("click", (e) => {
            e.stopPropagation();
            menu.classList.toggle("active");
        });

        document.addEventListener("click", (e) => {
            if (!btnMenu.contains(e.target) && !menu.contains(e.target)) {
                menu.classList.remove("active");
            }
        });
    }

    /* VALIDACION RUT */
    function validarFormatoRut(rut) {
        return /^\d{1,2}\.\d{3}\.\d{3}-[\dkK]{1}$/.test(rut);
    }

    function validarRut(rut) {
        return window.ValidadorRut ? window.ValidadorRut.validate(rut) : false;
    }

    function activarValidadorRut(inputId, errorId) {
        const input = document.getElementById(inputId);
        const error = document.getElementById(errorId);

        if (!input || !error) return;

        input.addEventListener("input", () => {
            const rut = input.value.trim();

            if (rut === "") {
                error.textContent = "";
                input.style.borderColor = "";
                return;
            }

            if (!validarFormatoRut(rut)) {
                error.textContent = "Formato inválido";
                input.style.borderColor = "#dc2626";
                return;
            }

            if (!validarRut(rut)) {
                error.textContent = "RUT inválido";
                input.style.borderColor = "#dc2626";
                return;
            }

            error.textContent = "✔ RUT válido";
            error.style.color = "#16a34a";
            input.style.borderColor = "#16a34a";
        });
    }

    activarValidadorRut("rutCargador", "rutErrorCargador");
    activarValidadorRut("rutOperador", "rutErrorOperador");

    /* TOTAL LITROS ACUMULADOS */
    // El total base viene desde la base de datos mediante panel.php.
    // Mientras se escribe un nuevo registro se muestra el total proyectado
    // (total guardado + litros que aún no se han confirmado).
    function actualizarTotalAcumulado(inputSelector, totalId) {
        const input = document.querySelector(inputSelector);
        const totalElement = document.getElementById(totalId);

        if (!totalElement) return;

        const totalGuardado = parseFloat(totalElement.dataset.baseTotal) || 0;
        const litrosActuales = input ? (parseFloat(input.value) || 0) : 0;

        totalElement.innerText = (totalGuardado + litrosActuales).toFixed(2);
    }

    document.querySelectorAll(".litros-cargador").forEach(input => {
        input.addEventListener("input", () => {
            actualizarTotalAcumulado(".litros-cargador", "totalLitrosCargador");
        });
    });

    document.querySelectorAll(".litros-operador").forEach(input => {
        input.addEventListener("input", () => {
            actualizarTotalAcumulado(".litros-operador", "totalLitrosOperador");
        });
    });
});
