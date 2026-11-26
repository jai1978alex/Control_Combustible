// Sitio Web Creado por Jerez.
// Direccion: Miraflorez #1280
// Quintero - Chile
// jaime.jerez1978@gmail.com
// https://www.
// Creado, Programado y Diseñado por Jerez.
// JJA 

// 

/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- INICIO offline.js ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

// TITULO MODO SIN CONEXIÓN DEL PANEL DE REGISTRO

// Qué hace:
// 1. Prepara el panel para que abra aunque no haya señal (registra el archivo sw.js).
// 2. Al confirmar un registro intenta enviarlo al servidor. Si no hay señal (o el servidor no responde),
//    lo guarda en el equipo y avisa al operador.
// 3. Cuando vuelve la conexión envía los pendientes, en el mismo orden en que se hicieron.
// 4. Si el servidor rechaza un registro (horómetro menor, RUT que no calza...), NO lo borra:
//    queda en "Revisar" con el motivo, para corregirlo o descartarlo.
//
// Garantías:
// - Un registro solo se borra del equipo cuando el servidor confirma que lo guardó.
// - Cada registro lleva un número único: si un reenvío llega dos veces, el servidor lo detecta y no lo duplica.
// - Los pendientes quedan asociados a la cuenta que los creó; otra cuenta en el mismo equipo no los envía.

// Abre una función que se ejecuta sola apenas se carga el archivo (así sus variables no se mezclan con las de otros archivos)
(function () {
    // Activa el modo estricto: el navegador avisa de errores de escritura que normalmente dejaría pasar
    'use strict';

    // TITULO CONFIGURACIÓN Y VARIABLES

    // Define los valores fijos que usa el módulo (nombres, tiempos de espera) y las variables que cambian mientras funciona.

    // Nombre de la "bodega" del equipo donde se guardan los registros sin enviar
    var DB_NAME = 'emex_offline';
    // Número de versión de esa bodega
    var DB_VERSION = 1;
    // Nombre del "cajón" dentro de la bodega donde se ordenan los registros
    var STORE = 'cola';
    // Tiempo máximo de espera: si el servidor no responde en 20 segundos, se considera "sin señal"
    var TIMEOUT_MS = 20000;
    // Tiempo de espera inicial entre reintentos automáticos: 15 segundos (se duplica hasta llegar al máximo)
    var REINTENTO_INICIAL_MS = 15000;
    // Tiempo máximo entre reintentos: 5 minutos
    var REINTENTO_MAX_MS = 5 * 60 * 1000;

    // Busca en la página la etiqueta que guarda el identificador del usuario que inició sesión
    var meta = document.querySelector('meta[name="emex-usuario"]');
    // Guarda el identificador del usuario como texto (si no existe la etiqueta, queda vacío)
    var usuarioId = meta ? String(meta.getAttribute('content') || '') : '';

    // Lista de mensajes que se muestran al operador según el motivo por el que el servidor rechazó un registro
    var MENSAJES_RECHAZO = {
        // Faltan datos o tienen un formato incorrecto
        campos: 'Hay campos incompletos o con formato inválido.',
        // El horómetro ingresado es más bajo que la última lectura
        horometro_menor: 'El horómetro es menor que la última lectura registrada para esta maquinaria.',
        // El RUT no es válido
        rut: 'El RUT ingresado no es válido.',
        // El RUT no pertenece a la cuenta con la que se inició sesión
        identidad: 'El RUT no corresponde a tu cuenta.',
        // La cuenta no tiene RUT asignado
        sin_rut: 'Tu cuenta no tiene un RUT asignado.',
        // El nombre no coincide con el del RUT
        nombre: 'El nombre no coincide con el registrado para este RUT.',
        // La cuenta no tiene nombre registrado
        sin_nombre: 'Tu cuenta no tiene nombre registrado.',
        // Acceso sin permiso
        acceso: 'Acceso no autorizado.'
    };
    // Nombres legibles de cada formulario, para mostrarlos en la pantalla "Revisar"
    var ETIQUETA_TIPO = { formCargador: 'Operador Cargador', formOperador: 'Datos de Operador' };

    // Indica si hay un envío en curso desde el botón Confirmar (empieza en "no")
    var enviando = false;
    // Indica si se está enviando la lista de pendientes (empieza en "no")
    var sincronizando = false;
    // Indica si la sesión del usuario venció (empieza en "no")
    var sesionExpirada = false;
    // Tiempo que se espera antes del próximo reintento (empieza en el valor inicial)
    var espera = REINTENTO_INICIAL_MS;
    // Guarda el temporizador del próximo reintento (empieza sin ninguno)
    var timerReintento = null;
    // Cuenta cuántos registros hay pendientes y cuántos fueron rechazados
    var conteo = { pendientes: 0, rechazados: 0 };
    // Guarda la barra de estado que se muestra en pantalla (empieza sin crear)
    var barra = null;

    // TITULO UTILIDADES

    // Funciones pequeñas de ayuda: mostrar avisos, crear números únicos, cerrar ventanas y dar formato a fechas.

    // Define la función aviso, que muestra un mensaje al operador según su tipo (éxito, error o información)
    function aviso(tipo, texto) {
        // Si existe el módulo de avisos y tiene el tipo pedido, lo usa
        if (window.Toast && typeof window.Toast[tipo] === 'function') { window.Toast[tipo](texto); }
        // Si no tiene ese tipo pero sí el de éxito, muestra el aviso como éxito
        else if (window.Toast && typeof window.Toast.success === 'function') { window.Toast.success(texto); }
        // Si no existe el módulo de avisos, muestra una ventana de alerta común
        else { window.alert(texto); }
    }

    // Define la función nuevoUuid, que crea un número de identificación único para cada registro
    function nuevoUuid() {
        // Si el navegador sabe crearlo directamente, lo usa
        if (window.crypto && typeof window.crypto.randomUUID === 'function') { return window.crypto.randomUUID(); }
        // Si no, arma uno a mano: primero prepara 16 espacios vacíos
        var b = new Uint8Array(16);
        // Los llena con números al azar
        window.crypto.getRandomValues(b);
        // Ajusta un valor para que cumpla el formato de identificador (versión 4)
        b[6] = (b[6] & 0x0f) | 0x40;
        // Ajusta otro valor para que cumpla el formato de identificador (variante)
        b[8] = (b[8] & 0x3f) | 0x80;
        // Convierte los números a letras y dígitos y los une en un solo texto
        var h = Array.prototype.map.call(b, function (x) { return ('0' + x.toString(16)).slice(-2); }).join('');
        // Devuelve el texto separado en grupos con guiones, como un identificador normal
        return h.slice(0, 8) + '-' + h.slice(8, 12) + '-' + h.slice(12, 16) + '-' + h.slice(16, 20) + '-' + h.slice(20);
    }

    // Define la función que cierra la ventana de confirmación
    function cerrarModalConfirmacion() {
        // Busca la ventana de confirmación en la página
        var m = document.getElementById('modalConfirmacion');
        // Si existe, la oculta
        if (m) { m.style.display = 'none'; }
    }

    // Define la función que convierte una fecha en un texto fácil de leer
    function fechaLegible(ms) {
        // Intenta dar el formato con la hora de Chile
        try {
            // Devuelve la fecha y hora en formato corto, en español y hora de Santiago
            return new Date(ms).toLocaleString('es-CL', { timeZone: 'America/Santiago', dateStyle: 'short', timeStyle: 'short' });
        // Si el equipo no permite ese formato, usa el formato normal del equipo
        } catch (_) { return new Date(ms).toLocaleString(); }
    }

    // TITULO COLA DE REGISTROS EN EL EQUIPO

    // Maneja la "bodega" del equipo (IndexedDB) donde se guardan los registros que aún no se han enviado.

    // Variable que guarda la apertura de la bodega para no abrirla muchas veces (empieza vacía)
    var dbPromesa = null;
    // Define la función que abre la bodega del equipo
    function abrirDB() {
        // Si ya está abierta (o abriéndose), devuelve esa misma
        if (dbPromesa) { return dbPromesa; }
        // Si no, inicia la apertura
        dbPromesa = new Promise(function (resolver, rechazar) {
            // Si el equipo no tiene bodega disponible, informa el error y termina
            if (!window.indexedDB) { rechazar(new Error('IndexedDB no disponible')); return; }
            // Pide abrir la bodega con su nombre y versión
            var req = window.indexedDB.open(DB_NAME, DB_VERSION);
            // Esto se ejecuta solo la primera vez (o cuando cambia la versión): prepara la bodega
            req.onupgradeneeded = function () {
                // Toma la bodega recién creada
                var db = req.result;
                // Si todavía no existe el cajón de registros
                if (!db.objectStoreNames.contains(STORE)) {
                    // Crea el cajón, usando el número único (uuid) como identificador de cada registro
                    var s = db.createObjectStore(STORE, { keyPath: 'uuid' });
                    // Crea un índice por usuario para poder buscar los registros de cada cuenta
                    s.createIndex('usuario', 'usuario', { unique: false });
                }
            };
            // Si la apertura sale bien, entrega la bodega abierta
            req.onsuccess = function () { resolver(req.result); };
            // Si falla, olvida el intento para poder reintentar y entrega el error
            req.onerror = function () { dbPromesa = null; rechazar(req.error); };
        });
        // Devuelve la apertura de la bodega
        return dbPromesa;
    }

    // Define una función general para hacer una operación (leer o escribir) en la bodega
    function transaccion(modo, operacion) {
        // Primero abre la bodega
        return abrirDB().then(function (db) {
            // Luego prepara la operación
            return new Promise(function (resolver, rechazar) {
                // Inicia la operación sobre el cajón, en modo lectura o escritura
                var tx = db.transaction(STORE, modo);
                // Variable que guardará el resultado de la operación
                var resultado;
                // Cuando la operación termina bien, entrega el resultado
                tx.oncomplete = function () { resolver(resultado); };
                // Si ocurre un error, lo informa
                tx.onerror = function () { rechazar(tx.error); };
                // Si la operación se cancela, lo informa
                tx.onabort = function () { rechazar(tx.error || new Error('Transacción cancelada')); };
                // Ejecuta lo que se pidió hacer en el cajón
                var r = operacion(tx.objectStore(STORE));
                // Si la operación devuelve algo, guarda ese resultado cuando esté listo
                if (r) { r.onsuccess = function () { resultado = r.result; }; }
            });
        });
    }

    // Guarda (o actualiza) un registro en la bodega
    function guardarItem(item) { return transaccion('readwrite', function (s) { return s.put(item); }); }
    // Borra un registro de la bodega usando su número único
    function borrarItem(uuid) { return transaccion('readwrite', function (s) { return s.delete(uuid); }); }
    // Define la función que entrega la lista de registros guardados del usuario actual
    function listarItems() {
        // Busca en la bodega solo los registros de la cuenta que tiene la sesión abierta
        return transaccion('readonly', function (s) { return s.index('usuario').getAll(usuarioId); })
            // Cuando llega la lista
            .then(function (lista) {
                // La ordena del más antiguo al más nuevo (según cuándo se hizo cada registro)
                return (lista || []).sort(function (a, b) { return a.capturado_ms - b.capturado_ms; });
            });
    }

    // TITULO VALIDACIÓN PREVIA

    // Revisa lo que se puede comprobar sin necesidad del servidor: campos completos, ubicación GPS y RUT.

    // Define la función que revisa si el formulario está bien llenado
    function validar(form) {
        // Si hay campos obligatorios vacíos o mal escritos
        if (!form.checkValidity()) {
            // Muestra al operador cuáles son los campos con problema
            form.reportValidity();
            // Devuelve que no está correcto, con el mensaje
            return { ok: false, mensaje: 'Completa todos los campos obligatorios.' };
        }
        // Busca el campo de ubicación GPS
        var gps = form.querySelector('[name="ubicacion"]');
        // Si existe y no tiene el formato de dos coordenadas (latitud, longitud)
        if (gps && !/^\s*-?\d+(\.\d+)?\s*,\s*-?\d+(\.\d+)?\s*$/.test(gps.value)) {
            // Devuelve que no está correcto y explica cómo obtener la ubicación
            return { ok: false, mensaje: 'Falta la ubicación GPS. Presiona el botón 📍 y espera a que se obtenga.' };
        }
        // Busca el campo del RUT
        var rut = form.querySelector('[name="rut"]');
        // Si existe y el validador de RUT está disponible y dice que el RUT no es válido
        if (rut && window.ValidadorRut && typeof window.ValidadorRut.validate === 'function' && !window.ValidadorRut.validate(rut.value.trim())) {
            // Devuelve que no está correcto
            return { ok: false, mensaje: 'El RUT ingresado no es válido.' };
        }
        // Si todo está bien, devuelve que está correcto
        return { ok: true };
    }

    // Define la función que arma el registro a partir de lo que el operador escribió en el formulario
    function construirItem(form) {
        // Aquí se guardarán los datos de cada campo
        var campos = {};
        // Recorre todos los campos del formulario
        Array.prototype.forEach.call(form.elements, function (el) {
            // Se salta los que no tienen nombre, el código de seguridad y los botones
            if (!el.name || el.name === 'csrf_token' || el.type === 'button' || el.type === 'submit') { return; }
            // Guarda el valor del campo usando su nombre
            campos[el.name] = el.value;
        });
        // Devuelve el registro completo
        return {
            // Número único del registro
            uuid: nuevoUuid(),
            // Usuario que lo creó
            usuario: usuarioId,
            // Tipo de formulario (por ejemplo, cargador u operador)
            tipo: form.id,
            // Dirección del servidor a la que se enviará
            endpoint: form.action,
            // Datos escritos por el operador
            campos: campos,
            // Momento exacto en que se hizo el registro
            capturado_ms: Date.now(),
            // Estado inicial: pendiente de envío
            estado: 'pendiente',
            // Cantidad de intentos de envío fallidos (empieza en cero)
            intentos: 0,
            // Motivo de rechazo (empieza vacío)
            codigo: null
        };
    }

    // Define la función que toma el código de seguridad (token) que viene en el formulario de la página
    function tokenDePagina(form) {
        // Busca el campo oculto del código de seguridad
        var t = form.querySelector('input[name="csrf_token"]');
        // Devuelve su valor, o vacío si no existe
        return t ? t.value : '';
    }

    // TITULO COMUNICACIÓN CON EL SERVIDOR

    // Envía los registros al servidor, interpreta su respuesta y pide un código de seguridad nuevo cuando hace falta.

    // Define la función que envía un registro al servidor
    function postear(item, csrf) {
        // Arma el paquete de datos a enviar
        var cuerpo = new URLSearchParams();
        // Agrega cada dato escrito por el operador
        Object.keys(item.campos).forEach(function (k) { cuerpo.set(k, item.campos[k]); });
        // Agrega el código de seguridad
        cuerpo.set('csrf_token', csrf);
        // Agrega el número único del registro (sirve para que el servidor no lo duplique)
        cuerpo.set('uuid_cliente', item.uuid);
        // Agrega el momento exacto en que se hizo el registro
        cuerpo.set('capturado_ms', String(item.capturado_ms));

        // Prepara un control para poder cortar el envío si demora demasiado
        var ctrl = new AbortController();
        // Programa el corte si pasan 20 segundos sin respuesta
        var timer = setTimeout(function () { ctrl.abort(); }, TIMEOUT_MS);
        // Envía el registro al servidor
        return fetch(item.endpoint, {
            // Lo envía como formulario
            method: 'POST',
            // Incluye la sesión del usuario
            credentials: 'same-origin',
            // Evita usar respuestas guardadas de antes
            cache: 'no-store',
            // Indica que espera respuesta en formato JSON y que envía datos de formulario
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
            // Los datos a enviar
            body: cuerpo.toString(),
            // Permite cortar el envío si se acaba el tiempo
            signal: ctrl.signal
        // Cuando el servidor responde
        }).then(function (resp) {
            // Intenta leer la respuesta (si no se puede leer, queda vacía)
            return resp.json().catch(function () { return null; }).then(function (data) {
                // Devuelve que sí hubo comunicación, con el código de estado y la respuesta
                return { red: true, status: resp.status, data: data };
            });
        // Si no hubo comunicación (sin señal o tiempo agotado)
        }).catch(function () {
            // Devuelve que no hubo comunicación
            return { red: false };
        // En cualquier caso, cancela el temporizador y entrega el resultado
        }).then(function (r) { clearTimeout(timer); return r; });
    }

    // Traduce la respuesta del servidor en una decisión:
    //  ok | rechazado (el usuario debe corregir) | sesion | csrf | reintentar (sin señal o falla temporal)
    function clasificar(r) {
        // Si no hubo comunicación, hay que reintentar más tarde
        if (!r.red) { return 'reintentar'; }
        // Si el servidor confirmó que guardó el registro, todo salió bien
        if (r.data && r.data.ok === true) { return 'ok'; }
        // Toma el código de motivo que entregó el servidor (si lo hay)
        var codigo = r.data && r.data.codigo;
        // Si la sesión venció o hay que cambiar la contraseña
        if (r.status === 401 || codigo === 'sesion' || codigo === 'cambiar_password') { return 'sesion'; }
        // Si el código de seguridad está vencido
        if (r.status === 403 && codigo === 'csrf') { return 'csrf'; }
        // Si hay demasiados intentos seguidos, hay que esperar y reintentar
        if (r.status === 429 || codigo === 'demasiados_intentos') { return 'reintentar'; }
        // Si el servidor falló o la respuesta no es del sistema (por ejemplo, la pantalla de un wifi público), reintentar
        if (r.status >= 500 || !r.data) { return 'reintentar'; }
        // En cualquier otro caso, el servidor rechazó el registro y el operador debe corregirlo
        return 'rechazado';
    }

    // Pide un código de seguridad vigente. La página guardada puede traer el de una sesión anterior.
    function obtenerCsrfFresco() {
        // Prepara un control para cortar la consulta si demora demasiado
        var ctrl = new AbortController();
        // Programa el corte a los 20 segundos
        var timer = setTimeout(function () { ctrl.abort(); }, TIMEOUT_MS);
        // Consulta al servidor por un código nuevo
        return fetch('../../backend/csrf_token.php', {
            // Incluye la sesión, evita respuestas guardadas, espera JSON y permite cortar la consulta
            credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json' }, signal: ctrl.signal
        // Cuando el servidor responde
        }).then(function (resp) {
            // Intenta leer la respuesta
            return resp.json().catch(function () { return null; }).then(function (data) {
                // Si la sesión venció, avisa que hay que iniciar sesión de nuevo
                if (resp.status === 401 || (data && data.codigo === 'sesion')) { return { error: 'sesion' }; }
                // Si la respuesta trae un código nuevo
                if (resp.ok && data && data.ok && data.csrf_token) {
                    // Si el código pertenece a otro usuario distinto del actual, trata la sesión como vencida
                    if (usuarioId && String(data.usuario_id) !== usuarioId) { return { error: 'sesion' }; }
                    // Entrega el código nuevo
                    return { token: data.csrf_token };
                }
                // Si no se pudo obtener, se considera un problema de conexión
                return { error: 'red' };
            });
        // Si no hubo comunicación, se considera un problema de conexión
        }).catch(function () {
            return { error: 'red' };
        // En cualquier caso, cancela el temporizador y entrega el resultado
        }).then(function (r) { clearTimeout(timer); return r; });
    }

    // TITULO ENVÍO DESDE EL BOTÓN CONFIRMAR

    // Es lo que ocurre cuando el operador confirma un registro: se intenta enviar y, si no se puede, se guarda en el equipo.

    // Define la función que bloquea o desbloquea el botón Confirmar
    function bloquearConfirmar(bloquear) {
        // Busca el botón Confirmar
        var b = document.getElementById('btnConfirmar');
        // Si existe, lo deshabilita o habilita según corresponda
        if (b) { b.disabled = bloquear; }
    }

    // Define la función que deja el formulario limpio después de guardar un registro
    function limpiarFormulario(form) {
        // Vacía todos los campos
        form.reset();
        // Recorre todos los campos de escritura
        Array.prototype.forEach.call(form.querySelectorAll('input'), function (i) {
            // Quita cualquier color de borde que hayan puesto las validaciones
            i.style.borderColor = '';
            // Borra los datos de ubicación guardados en el campo (latitud, longitud y precisión)
            if (i.dataset) { delete i.dataset.latitude; delete i.dataset.longitude; delete i.dataset.accuracy; }
        });
        // Borra los mensajes de error que hubiera en pantalla
        Array.prototype.forEach.call(form.querySelectorAll('.error-texto'), function (e) { e.textContent = ''; });
        // Busca el campo de litros
        var litros = form.querySelector('[name="litros"]');
        // Si existe, avisa que cambió para que se actualicen los cálculos que dependen de él
        if (litros) { litros.dispatchEvent(new Event('input', { bubbles: true })); }
    }

    // Define la función principal que envía el formulario
    function enviar(form) {
        // Si ya hay un envío en curso, no hace nada (evita registros duplicados por doble clic)
        if (enviando) { return Promise.resolve(); }
        // Marca que hay un envío en curso
        enviando = true;
        // Bloquea el botón Confirmar mientras tanto
        bloquearConfirmar(true);

        // Revisa que el formulario esté bien llenado
        var v = validar(form);
        // Si hay un problema
        if (!v.ok) {
            // Cierra la ventana de confirmación
            cerrarModalConfirmacion();
            // Muestra el problema al operador
            aviso('error', v.mensaje);
            // Marca que ya no hay envío en curso
            enviando = false;
            // Vuelve a habilitar el botón Confirmar
            bloquearConfirmar(false);
            // Termina sin enviar
            return Promise.resolve();
        }

        // Arma el registro con los datos del formulario
        var item = construirItem(form);
        // Aquí se guardará la decisión según la respuesta del servidor
        var decision;
        // Aquí se guardará la última respuesta del servidor
        var ultima;

        // Intenta enviar el registro usando el código de seguridad de la página
        return postear(item, tokenDePagina(form)).then(function (r) {
            // Guarda la respuesta y decide qué hacer con ella
            ultima = r; decision = clasificar(r);
            // Si el código de seguridad no estaba vencido, sigue adelante
            if (decision !== 'csrf') { return; }
            // Si estaba vencido, pide uno nuevo
            return obtenerCsrfFresco().then(function (t) {
                // Si no se pudo obtener, decide si hay que iniciar sesión de nuevo o reintentar más tarde
                if (!t.token) { decision = t.error === 'sesion' ? 'sesion' : 'reintentar'; return; }
                // Si se obtuvo, vuelve a enviar el registro con el código nuevo
                return postear(item, t.token).then(function (r2) { ultima = r2; decision = clasificar(r2); });
            });
        // Ya con la decisión tomada
        }).then(function () {
            // Si el servidor guardó el registro
            if (decision === 'ok') {
                // Igual que antes: recarga el panel con el aviso de éxito y los totales actualizados.
                window.location.href = window.location.pathname + '?success=1';
                // Termina
                return;
            }
            // Si el servidor rechazó el registro
            if (decision === 'rechazado') {
                // Recarga el panel mostrando el motivo del rechazo
                window.location.href = window.location.pathname + '?error=' + encodeURIComponent((ultima.data && ultima.data.codigo) || 'guardar');
                // Termina
                return;
            }
            // Sin señal, falla temporal o sesión vencida: el registro se guarda en el equipo.
            return guardarItem(item).then(function () {
                // Deja el formulario limpio para el siguiente registro
                limpiarFormulario(form);
                // Cierra la ventana de confirmación
                cerrarModalConfirmacion();
                // Actualiza el contador de pendientes y luego avisa
                return refrescarEstado().then(function () {
                    // Si el problema fue la sesión vencida
                    if (decision === 'sesion') {
                        // Marca la sesión como vencida
                        sesionExpirada = true;
                        // Actualiza la barra de estado
                        renderEstado();
                        // Avisa al operador que el registro quedó guardado y que debe iniciar sesión de nuevo
                        aviso('error', 'Tu sesión expiró. El registro quedó guardado en este equipo; inicia sesión de nuevo para enviarlo.');
                        // Después de 3,5 segundos lo lleva a la pantalla de inicio de sesión
                        setTimeout(function () { window.location.href = '../../index.php?error=sesion'; }, 3500);
                    // Si el problema fue la falta de señal
                    } else {
                        // Avisa que el registro quedó guardado y se enviará solo
                        aviso('info', 'Sin conexión: el registro quedó guardado en este equipo y se enviará solo cuando vuelva la señal.');
                        // Programa un reintento automático
                        programarReintento();
                    }
                });
            // Si ni siquiera se pudo guardar en el equipo
            }).catch(function () {
                // No se pudo guardar en el equipo: se conserva el formulario para que no se pierda nada.
                cerrarModalConfirmacion();
                // Avisa al operador que no cierre la página y que lo intente de nuevo
                aviso('error', 'No hay conexión y no se pudo guardar en este equipo. No cierres esta página ni borres los datos; intenta de nuevo.');
            });
        // Al terminar todo, sin importar cómo salió
        }).then(function () {
            // Marca que ya no hay envío en curso
            enviando = false;
            // Vuelve a habilitar el botón Confirmar
            bloquearConfirmar(false);
        // Si ocurrió un error inesperado, hace lo mismo
        }, function () {
            enviando = false;
            bloquearConfirmar(false);
        });
    }

    // TITULO SINCRONIZACIÓN DE PENDIENTES

    // Envía los registros guardados en el equipo cuando vuelve la conexión, uno por uno y en orden.

    // Define la función que programa el próximo reintento automático
    function programarReintento() {
        // Si ya había un reintento programado, lo cancela
        if (timerReintento) { clearTimeout(timerReintento); }
        // Programa uno nuevo para cuando pase el tiempo de espera
        timerReintento = setTimeout(function () { timerReintento = null; sincronizar(); }, espera);
        // Duplica la espera para la próxima vez, sin pasar del máximo
        espera = Math.min(espera * 2, REINTENTO_MAX_MS);
    }

    // Define la función que realmente envía los pendientes
    function correrSincronizacion() {
        // Marca que se está enviando
        sincronizando = true;
        // Actualiza la barra de estado
        renderEstado();
        // Contadores de enviados y rechazados, y motivo por el que se detiene el envío (si ocurre)
        var enviados = 0, rechazados = 0, detener = null;

        // Obtiene la lista de registros guardados
        return listarItems().then(function (items) {
            // Se queda solo con los que están pendientes de envío
            var pendientes = items.filter(function (i) { return i.estado === 'pendiente'; });
            // Si no hay ninguno, termina
            if (!pendientes.length) { return; }
            // Pide un código de seguridad vigente
            return obtenerCsrfFresco().then(function (t) {
                // Si no se pudo obtener, anota el motivo y termina
                if (!t.token) { detener = t.error; return; }
                // Guarda el código de seguridad
                var csrf = t.token;
                // Prepara una cadena de pasos para enviar los registros uno después del otro
                var cadena = Promise.resolve();
                // Recorre cada registro pendiente
                pendientes.forEach(function (item) {
                    // Agrega su envío a la cadena
                    cadena = cadena.then(function () {
                        // Si ya se decidió detener el envío, no envía este
                        if (detener) { return; }
                        // Envía el registro
                        return postear(item, csrf).then(function (r) {
                            // Interpreta la respuesta
                            var d = clasificar(r);
                            // Si el código de seguridad se venció
                            if (d === 'csrf') {
                                // Pide uno nuevo
                                return obtenerCsrfFresco().then(function (t2) {
                                    // Si no se pudo, decide si hay que iniciar sesión de nuevo o reintentar
                                    if (!t2.token) { return { d: t2.error === 'sesion' ? 'sesion' : 'reintentar', r: r }; }
                                    // Guarda el código nuevo
                                    csrf = t2.token;
                                    // Vuelve a enviar el registro con el código nuevo
                                    return postear(item, csrf).then(function (r2) { return { d: clasificar(r2), r: r2 }; });
                                });
                            }
                            // Si no hubo problema con el código, entrega la decisión
                            return { d: d, r: r };
                        // Según lo que se decidió para este registro
                        }).then(function (res) {
                            // Si el servidor lo guardó: lo cuenta y lo borra del equipo
                            if (res.d === 'ok') { enviados++; return borrarItem(item.uuid); }
                            // Si el servidor lo rechazó
                            if (res.d === 'rechazado') {
                                // Lo cuenta como rechazado
                                rechazados++;
                                // Lo marca como rechazado (no se borra)
                                item.estado = 'rechazado';
                                // Guarda el motivo del rechazo
                                item.codigo = (res.r.data && res.r.data.codigo) || 'guardar';
                                // Lo deja guardado para que el operador lo revise
                                return guardarItem(item);
                            }
                            // Si hubo problema de sesión o de señal: se corta el envío y se conservan los demás
                            detener = res.d;
                            // Suma un intento fallido a este registro
                            item.intentos = (item.intentos || 0) + 1;
                            // Lo deja guardado
                            return guardarItem(item);
                        });
                    });
                });
                // Devuelve la cadena completa para esperar a que termine
                return cadena;
            });
        // Cuando termina el envío de todos
        }).then(function () {
            // Marca que ya no se está enviando
            sincronizando = false;
            // Si se envió al menos uno
            if (enviados > 0) {
                // Reinicia el tiempo de espera entre reintentos
                espera = REINTENTO_INICIAL_MS;
                // Avisa cuántos registros se enviaron
                aviso('success', enviados === 1 ? 'Se envió 1 registro pendiente.' : 'Se enviaron ' + enviados + ' registros pendientes.');
            }
            // Si el servidor rechazó alguno
            if (rechazados > 0) {
                // Avisa cuántos fueron rechazados y que debe presionar "Revisar"
                aviso('error', rechazados === 1 ? 'El servidor rechazó 1 registro. Presiona "Revisar".' : 'El servidor rechazó ' + rechazados + ' registros. Presiona "Revisar".');
            }
            // Anota si la sesión quedó vencida
            sesionExpirada = (detener === 'sesion');
            // Actualiza el contador y la barra de estado
            return refrescarEstado().then(function () {
                // Si el problema fue de señal, programa otro intento más tarde
                if (detener === 'reintentar' || detener === 'red') { programarReintento(); }
            });
        // Si ocurre un error inesperado
        }).catch(function () {
            // Marca que ya no se está enviando
            sincronizando = false;
            // Actualiza el contador y la barra de estado
            return refrescarEstado();
        });
    }

    // Define la función que inicia el envío de pendientes
    function sincronizar() {
        // Si ya se está enviando o no hay usuario, no hace nada
        if (sincronizando || !usuarioId) { return Promise.resolve(); }
        // Si el navegador permite "turnos" entre pestañas
        if (navigator.locks && typeof navigator.locks.request === 'function') {
            // Evita que dos pestañas envíen lo mismo a la vez.
            return navigator.locks.request('emex-sync', { ifAvailable: true }, function (lock) {
                // Si otra pestaña ya está enviando, no hace nada
                if (!lock) { return; }
                // Si no, envía
                return correrSincronizacion();
            });
        }
        // Si el navegador no permite turnos, envía directamente
        return correrSincronizacion();
    }

    // TITULO BARRA DE ESTADO Y PANTALLA REVISAR

    // Muestra una barra con el estado de la conexión y los pendientes, y una ventana para revisar los registros sin enviar.

    // Define la función que crea la barra de estado (solo la crea una vez)
    function crearBarra() {
        // Si ya existe, la devuelve
        if (barra) { return barra; }
        // Crea el contenedor de la barra
        barra = document.createElement('div');
        // Le pone un identificador
        barra.id = 'emexEstadoRed';
        // Le asigna su estilo
        barra.className = 'emex-estado';
        // Indica a los lectores de pantalla que es un aviso de estado
        barra.setAttribute('role', 'status');
        // Indica que los cambios se anuncian con calma
        barra.setAttribute('aria-live', 'polite');
        // La deja oculta al inicio
        barra.hidden = true;

        // Crea el espacio para el texto de la barra
        var texto = document.createElement('span');
        // Le asigna su estilo
        texto.className = 'emex-estado-texto';
        // Lo agrega a la barra
        barra.appendChild(texto);

        // Crea tres botones: Enviar ahora, Revisar e Iniciar sesión
        [['enviar', 'Enviar ahora'], ['revisar', 'Revisar'], ['login', 'Iniciar sesión']].forEach(function (par) {
            // Crea el botón
            var b = document.createElement('button');
            // Lo define como botón común (no envía formularios)
            b.type = 'button';
            // Le asigna su estilo
            b.className = 'emex-estado-btn';
            // Guarda qué acción realiza
            b.dataset.accion = par[0];
            // Escribe su texto visible
            b.textContent = par[1];
            // Lo agrega a la barra
            barra.appendChild(b);
        });

        // Escucha los clics dentro de la barra
        barra.addEventListener('click', function (e) {
            // Busca el botón que se presionó
            var b = e.target.closest('button[data-accion]');
            // Si no fue un botón, no hace nada
            if (!b) { return; }
            // Botón "Enviar ahora": reinicia la espera y envía los pendientes
            if (b.dataset.accion === 'enviar') { espera = REINTENTO_INICIAL_MS; sincronizar(); }
            // Botón "Revisar": abre la ventana con los registros sin enviar
            if (b.dataset.accion === 'revisar') { abrirRevision(); }
            // Botón "Iniciar sesión": lleva a la pantalla de inicio de sesión
            if (b.dataset.accion === 'login') { window.location.href = '../../index.php?error=sesion'; }
        });

        // Busca el encabezado de la aplicación
        var cabecera = document.querySelector('.app-header');
        // Si existe, pone la barra justo debajo
        if (cabecera && cabecera.parentNode) { cabecera.parentNode.insertBefore(barra, cabecera.nextSibling); }
        // Si no existe, pone la barra al comienzo de la página
        else { document.body.insertBefore(barra, document.body.firstChild); }
        // Devuelve la barra
        return barra;
    }

    // Define la función que actualiza lo que muestra la barra de estado
    function renderEstado() {
        // Obtiene la barra (la crea si no existe)
        var b = crearBarra();
        // Revisa si el equipo está sin conexión
        var sinRed = !navigator.onLine;
        // Toma las cantidades de pendientes (p) y rechazados (r)
        var p = conteo.pendientes, r = conteo.rechazados;

        // Si todo está normal (con conexión, sin pendientes ni rechazados ni sesión vencida), oculta la barra y termina
        if (!sinRed && p === 0 && r === 0 && !sesionExpirada) { b.hidden = true; return; }

        // Lista de frases que formarán el mensaje de la barra
        var partes = [];
        // Si no hay conexión, lo indica
        if (sinRed) { partes.push('Sin conexión: los registros se guardan en este equipo.'); }
        // Si la sesión venció y hay pendientes, lo indica
        if (sesionExpirada && p > 0) { partes.push('Sesión expirada: inicia sesión para enviar los pendientes.'); }
        // Si hay pendientes, indica cuántos son o que se están enviando
        if (p > 0) { partes.push(sincronizando ? 'Enviando ' + p + ' pendiente(s)…' : p + (p === 1 ? ' registro pendiente de envío.' : ' registros pendientes de envío.')); }
        // Si hay rechazados, indica cuántos son
        if (r > 0) { partes.push(r + (r === 1 ? ' registro rechazado.' : ' registros rechazados.')); }

        // Elige el color de la barra: rojo si hay rechazados, gris si no hay conexión o venció la sesión, y otro color si solo hay pendientes
        b.className = 'emex-estado ' + (r > 0 ? 'emex-estado--error' : (sinRed || sesionExpirada ? 'emex-estado--offline' : 'emex-estado--pendiente'));
        // Escribe el mensaje uniendo las frases
        b.querySelector('.emex-estado-texto').textContent = partes.join(' ');
        // Muestra "Enviar ahora" solo si hay pendientes, hay conexión, la sesión sigue activa y no se está enviando
        b.querySelector('[data-accion="enviar"]').hidden = !(p > 0 && !sesionExpirada && !sinRed && !sincronizando);
        // Muestra "Revisar" solo si hay pendientes o rechazados
        b.querySelector('[data-accion="revisar"]').hidden = !(p > 0 || r > 0);
        // Muestra "Iniciar sesión" solo si la sesión venció y hay pendientes
        b.querySelector('[data-accion="login"]').hidden = !(sesionExpirada && p > 0);
        // Hace visible la barra
        b.hidden = false;
    }

    // Define la función que vuelve a contar los registros y actualiza la pantalla
    function refrescarEstado() {
        // Obtiene la lista de registros guardados
        return listarItems().then(function (items) {
            // Cuenta los pendientes
            conteo.pendientes = items.filter(function (i) { return i.estado === 'pendiente'; }).length;
            // Cuenta los rechazados
            conteo.rechazados = items.filter(function (i) { return i.estado === 'rechazado'; }).length;
            // Actualiza la barra de estado
            renderEstado();
            // Busca la ventana "Revisar"
            var panel = document.getElementById('emexRevision');
            // Si está abierta, actualiza su lista
            if (panel && !panel.hidden) { dibujarRevision(items); }
        // Si falla algo, al menos actualiza la barra
        }).catch(function () { renderEstado(); });
    }

    // Define la función que arma el texto resumen de un registro (tipo, equipo y litros)
    function textoResumen(item) {
        // Toma los datos del registro
        var c = item.campos || {};
        // Busca el identificador del equipo (patente o código)
        var equipo = c.patente || c.codigo || c.codigoMaquinaria || '';
        // Devuelve, por ejemplo: "Operador Cargador · ABC123 · 50 L"
        return (ETIQUETA_TIPO[item.tipo] || 'Registro') + ' · ' + (equipo ? equipo + ' · ' : '') + (c.litros || '?') + ' L';
    }

    // Define la función que dibuja la lista de registros dentro de la ventana "Revisar"
    function dibujarRevision(items) {
        // Busca el lugar donde va la lista
        var lista = document.getElementById('emexRevisionLista');
        // Si no existe, termina
        if (!lista) { return; }
        // Vacía la lista para dibujarla de nuevo
        lista.replaceChildren();
        // Si no hay registros
        if (!items.length) {
            // Crea un mensaje
            var vacio = document.createElement('p');
            // Le asigna su estilo
            vacio.className = 'emex-rev-vacio';
            // Escribe el mensaje
            vacio.textContent = 'No hay registros pendientes.';
            // Lo agrega a la lista
            lista.appendChild(vacio);
            // Termina
            return;
        }
        // Recorre cada registro
        items.forEach(function (item) {
            // Crea la fila del registro
            var fila = document.createElement('div');
            // Le asigna su estilo (con marca de error si fue rechazado)
            fila.className = 'emex-rev-item' + (item.estado === 'rechazado' ? ' emex-rev-item--error' : '');

            // Crea el título de la fila
            var t = document.createElement('strong');
            // Escribe el resumen del registro
            t.textContent = textoResumen(item);
            // Lo agrega a la fila
            fila.appendChild(t);

            // Crea el texto con la fecha
            var f = document.createElement('small');
            // Escribe cuándo se hizo el registro
            f.textContent = 'Hecho el ' + fechaLegible(item.capturado_ms);
            // Lo agrega a la fila
            fila.appendChild(f);

            // Crea el texto del estado
            var e = document.createElement('span');
            // Si fue rechazado
            if (item.estado === 'rechazado') {
                // Escribe el motivo del rechazo
                e.textContent = 'Rechazado: ' + (MENSAJES_RECHAZO[item.codigo] || ('El servidor no lo aceptó (' + item.codigo + ').'));
            // Si está pendiente
            } else {
                // Indica que espera ser enviado
                e.textContent = 'Pendiente de envío.';
            }
            // Lo agrega a la fila
            fila.appendChild(e);

            // Crea el espacio para los botones de la fila
            var acciones = document.createElement('div');
            // Le asigna su estilo
            acciones.className = 'emex-rev-acciones';
            // Si fue rechazado, ofrece el botón "Corregir"
            if (item.estado === 'rechazado') {
                // Crea el botón
                var corregir = document.createElement('button');
                // Lo define como botón común
                corregir.type = 'button';
                // Escribe su texto
                corregir.textContent = 'Corregir';
                // Al hacer clic, vuelve a cargar el registro en el formulario
                corregir.addEventListener('click', function () { corregirItem(item); });
                // Lo agrega a los botones
                acciones.appendChild(corregir);
            }
            // Crea el botón "Descartar"
            var descartar = document.createElement('button');
            // Lo define como botón común
            descartar.type = 'button';
            // Le asigna su estilo
            descartar.className = 'emex-rev-descartar';
            // Escribe su texto
            descartar.textContent = 'Descartar';
            // Al hacer clic
            descartar.addEventListener('click', function () {
                // Pregunta si realmente quiere descartarlo
                if (window.confirm('¿Descartar este registro? Se borrará de este equipo y no se enviará.')) {
                    // Si acepta, lo borra y actualiza la pantalla
                    borrarItem(item.uuid).then(refrescarEstado);
                }
            });
            // Lo agrega a los botones
            acciones.appendChild(descartar);
            // Agrega los botones a la fila
            fila.appendChild(acciones);
            // Agrega la fila a la lista
            lista.appendChild(fila);
        });
    }

    // Define la función que abre la ventana "Revisar"
    function abrirRevision() {
        // Busca la ventana (por si ya fue creada antes)
        var panel = document.getElementById('emexRevision');
        // Si todavía no existe, la crea
        if (!panel) {
            // Crea el fondo de la ventana
            panel = document.createElement('div');
            // Le pone un identificador
            panel.id = 'emexRevision';
            // Le asigna su estilo
            panel.className = 'emex-rev';
            // La deja oculta mientras se arma
            panel.hidden = true;

            // Crea la caja central de la ventana
            var caja = document.createElement('div');
            // Le asigna su estilo
            caja.className = 'emex-rev-caja';
            // Crea el título de la ventana
            var h = document.createElement('h2');
            // Escribe el título
            h.textContent = 'Registros sin enviar';
            // Crea el espacio donde irá la lista
            var lista = document.createElement('div');
            // Le pone un identificador
            lista.id = 'emexRevisionLista';
            // Crea el botón Cerrar
            var cerrar = document.createElement('button');
            // Lo define como botón común
            cerrar.type = 'button';
            // Le asigna su estilo
            cerrar.className = 'emex-rev-cerrar';
            // Escribe su texto
            cerrar.textContent = 'Cerrar';
            // Al hacer clic, oculta la ventana
            cerrar.addEventListener('click', function () { panel.hidden = true; });
            // Agrega título, lista y botón a la caja
            caja.appendChild(h); caja.appendChild(lista); caja.appendChild(cerrar);
            // Agrega la caja a la ventana
            panel.appendChild(caja);
            // Si se hace clic en el fondo (fuera de la caja), también se cierra
            panel.addEventListener('click', function (e) { if (e.target === panel) { panel.hidden = true; } });
            // Agrega la ventana a la página
            document.body.appendChild(panel);
        }
        // Muestra la ventana
        panel.hidden = false;
        // Carga y dibuja la lista de registros
        listarItems().then(dibujarRevision);
    }

    // Carga un registro rechazado de nuevo en su formulario y lo saca de la cola para que se pueda corregir.
    function corregirItem(item) {
        // Busca el formulario al que pertenece el registro
        var form = document.getElementById(item.tipo);
        // Si no existe en esta página, termina
        if (!form) { return; }
        // Recorre cada dato guardado del registro
        Object.keys(item.campos).forEach(function (nombre) {
            // Busca el campo del formulario con ese nombre
            var el = form.querySelector('[name="' + nombre + '"]');
            // Si existe, escribe el dato y avisa que cambió
            if (el) { el.value = item.campos[nombre]; el.dispatchEvent(new Event('input', { bubbles: true })); }
        });
        // Borra el registro de la cola (para que el operador lo corrija y lo vuelva a guardar)
        borrarItem(item.uuid).then(function () {
            // Busca la ventana "Revisar"
            var panel = document.getElementById('emexRevision');
            // Si existe, la cierra
            if (panel) { panel.hidden = true; }
            // Actualiza el contador y la barra de estado
            refrescarEstado();
            // Lleva la pantalla suavemente hasta el formulario
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            // Avisa que el registro ya está en el formulario
            aviso('info', 'Registro cargado en el formulario. Corrígelo y vuelve a guardarlo.');
        });
    }

    // TITULO CIERRE DE SESIÓN PROTEGIDO

    // Avisa si hay pendientes antes de cerrar sesión y no permite cerrarla sin conexión.

    // Define la función que protege el botón de cerrar sesión
    function protegerCierreDeSesion() {
        // Recorre todos los formularios de cerrar sesión de la página
        Array.prototype.forEach.call(document.querySelectorAll('.logout-form'), function (f) {
            // Cuando se intenta cerrar sesión
            f.addEventListener('submit', function (e) {
                // Si no hay conexión
                if (!navigator.onLine) {
                    // Detiene el cierre de sesión
                    e.preventDefault();
                    // Avisa al operador
                    aviso('error', 'Sin conexión: no se puede cerrar la sesión ahora. Hazlo cuando tengas señal.');
                    // Termina
                    return;
                }
                // Si hay pendientes y todavía no lo confirmó
                if (conteo.pendientes > 0 && !f.dataset.confirmado) {
                    // Detiene el cierre de sesión por ahora
                    e.preventDefault();
                    // Toma la cantidad de pendientes
                    var n = conteo.pendientes;
                    // Pregunta si de verdad quiere cerrar sesión, explicando lo que pasará con los pendientes
                    var ok = window.confirm('Tienes ' + n + (n === 1 ? ' registro' : ' registros') + ' sin enviar guardado(s) en este equipo.\n\nSi cierras sesión no se enviarán hasta que vuelvas a entrar con tu cuenta. ¿Cerrar sesión de todos modos?');
                    // Si acepta, marca que ya confirmó y cierra la sesión
                    if (ok) { f.dataset.confirmado = '1'; f.submit(); }
                }
            });
        });
    }

    // TITULO INICIO

    // Deja todo funcionando: activa el modo sin conexión y deja programados los envíos automáticos.

    // Define la función que activa el archivo sw.js (permite que el panel abra sin señal)
    function registrarServiceWorker() {
        // Si el navegador no lo permite, termina
        if (!('serviceWorker' in navigator)) { return; }
        // Rutas relativas a la página (html/programa/panel.php): sw.js vive en la raíz del proyecto.
        navigator.serviceWorker.register('../../sw.js', { scope: '../../' }).catch(function (err) {
            // Si falla, deja un aviso técnico en la consola del navegador
            console.warn('No se pudo activar el modo sin conexión:', err);
        });
    }

    // Define la función que pone todo en marcha
    function iniciar() {
        // Activa el modo sin conexión
        registrarServiceWorker();
        // Activa la protección del cierre de sesión
        protegerCierreDeSesion();
        // Cuenta los registros guardados y muestra la barra si corresponde
        refrescarEstado();

        // Cuando vuelve la conexión: reinicia la espera, actualiza la barra y envía los pendientes
        window.addEventListener('online', function () { espera = REINTENTO_INICIAL_MS; renderEstado(); sincronizar(); });
        // Cuando se pierde la conexión: actualiza la barra
        window.addEventListener('offline', renderEstado);
        // Cuando el operador vuelve a esta pestaña
        document.addEventListener('visibilitychange', function () {
            // Si la pestaña está visible y hay conexión, envía los pendientes
            if (document.visibilityState === 'visible' && navigator.onLine) { sincronizar(); }
        });
        // Red de seguridad: revisa cada minuto si quedó algo pendiente.
        setInterval(function () { if (navigator.onLine && conteo.pendientes > 0 && !sesionExpirada) { sincronizar(); } }, 60000);
        // Primer intento al abrir el panel (espera 1,5 segundos).
        setTimeout(function () { if (navigator.onLine) { sincronizar(); } }, 1500);
    }

    // TITULO DISPONIBILIDAD GLOBAL

    // Publica las funciones principales para usarlas desde otros archivos.

    // Deja disponibles las funciones enviar, sincronizar y revisar para otros scripts
    window.OfflineQueue = { enviar: enviar, sincronizar: sincronizar, revisar: abrirRevision };

    // Si la página todavía se está cargando, espera a que termine antes de iniciar
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', iniciar); }
    // Si ya terminó de cargar, inicia de inmediato
    else { iniciar(); }
// Cierra la función y la ejecuta
})();



/* --------------------------------------------------------------------------------------------------------------
   ---------------------------------------- FIN offline.js --------------------------------------
   -------------------------------------------------------------------------------------------------------------- */


// Sitio Web Creado por Jerez.
// Direccion: Miraflorez #1280
// Quintero - Chile
// jaime.jerez1978@gmail.com
// https://www.
// Creado, Programado y Diseñado por Jerez.
// JJA
