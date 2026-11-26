/* -------------------------------------------------------------------------------------------------------------
   Service Worker - Control de Combustible (modo sin conexión)
   Vive en la raíz del proyecto para controlar todo el sitio, sin importar en qué carpeta esté instalado.

   Qué hace:
   - Guarda en el equipo los archivos estáticos (CSS, JS, íconos) para que el panel cargue sin señal.
   - Guarda una copia del panel (html/programa/panel.php) la última vez que se abrió con conexión.
   - Si no hay señal, abre esa copia; si nunca se abrió, muestra offline.html.
   - Al cerrar sesión borra la copia del panel (datos de la persona: nombre, RUT).
   - NO intercepta ni guarda envíos al backend: la cola de registros pendientes la maneja js/programa/offline.js.

   Al cambiar archivos estáticos importantes, sube VERSION para forzar la actualización.
   ------------------------------------------------------------------------------------------------------------- */

const VERSION = 'v1';
const STATIC_CACHE = 'emex-static-' + VERSION;
const PAGES_CACHE = 'emex-pages-' + VERSION;

// sw.js está en la raíz: BASE es la carpeta raíz del proyecto (funciona en /, /control_combustible/, un subdominio, etc.).
const BASE = new URL('./', self.location).href;
const BASE_PATH = new URL(BASE).pathname;
const abs = (ruta) => new URL(ruta, BASE).href;

const PANEL_URL = abs('html/programa/panel.php');
const OFFLINE_URL = abs('offline.html');
const ESPERA_RED_MS = 5000; // si hay copia guardada y la red tarda más que esto, se usa la copia

const PRECACHE = [
    'offline.html',
    'manifest.webmanifest',
    'icons/icon-192.png',
    'icons/icon-512.png',
    'css/programa/style.css',
    'css/programa/panel.css',
    'css/programa/formulario.css',
    'css/programa/modal.css',
    'css/programa/offline.css',
    'js/programa/toast.js',
    'js/programa/qr-scanner.js',
    'js/programa/validador_rut.js',
    'js/programa/modal-confirmacion.js',
    'js/programa/panel.js',
    'js/programa/offline.js',
    'js/vendor/html5-qrcode/html5-qrcode.min.js'
].map(abs);

self.addEventListener('install', (event) => {
    event.waitUntil((async () => {
        const cache = await caches.open(STATIC_CACHE);
        // Un archivo que falle no debe impedir instalar el resto.
        await Promise.all(PRECACHE.map((url) =>
            cache.add(new Request(url, { cache: 'reload' })).catch((err) => console.warn('[SW] No se pudo guardar', url, err))
        ));
        await self.skipWaiting();
    })());
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const vigentes = [STATIC_CACHE, PAGES_CACHE];
        const nombres = await caches.keys();
        await Promise.all(nombres.filter((n) => n.startsWith('emex-') && !vigentes.includes(n)).map((n) => caches.delete(n)));
        await self.clients.claim();
    })());
});

self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'LIMPIAR_PAGINAS') {
        event.waitUntil(caches.delete(PAGES_CACHE));
    }
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    const url = new URL(req.url);
    if (url.origin !== self.location.origin) return;

    // Cerrar sesión: antes de enviar, borra la copia guardada del panel (tiene nombre y RUT del operador).
    if (req.method === 'POST' && url.pathname.endsWith('/backend/logout.php')) {
        event.respondWith((async () => {
            await caches.delete(PAGES_CACHE);
            return fetch(req);
        })());
        return;
    }

    if (req.method !== 'GET') return;

    if (req.mode === 'navigate') {
        const esPanel = url.pathname === new URL(PANEL_URL).pathname;
        const esInicio = url.pathname === BASE_PATH || url.pathname === BASE_PATH + 'index.php';
        if (esPanel || esInicio) {
            event.respondWith(navegarConCopia(event, esPanel));
        } else {
            event.respondWith(fetch(req).catch(async () => (await caches.match(OFFLINE_URL)) || Response.error()));
        }
        return;
    }

    if (esEstatico(url)) {
        event.respondWith(estaticoConRevalidacion(event, url));
    }
    // Todo lo demás (backend/*.php, otras páginas) pasa directo a la red, sin tocar.
});

function esEstatico(url) {
    if (!url.pathname.startsWith(BASE_PATH)) return false;
    const rel = url.pathname.slice(BASE_PATH.length);
    return /^(css|js|icons)\//.test(rel) || rel === 'manifest.webmanifest' || rel === 'offline.html';
}

function conTiempoMaximo(promesa, ms) {
    if (!ms) return promesa;
    return Promise.race([promesa, new Promise((_, rechazar) => setTimeout(() => rechazar(new Error('timeout')), ms))]);
}

// Panel / inicio: la red manda; si falla (o tarda demasiado y hay copia) se usa la copia guardada.
async function navegarConCopia(event, esPanel) {
    const cache = await caches.open(PAGES_CACHE);
    const copia = await cache.match(PANEL_URL, { ignoreSearch: true });
    try {
        const resp = await conTiempoMaximo(fetch(event.request), copia ? ESPERA_RED_MS : 0);
        // Solo se guarda una respuesta 200 real del panel. Si la sesión expiró, el servidor redirige al
        // login (opaqueredirect) y eso nunca se guarda como si fuera el panel.
        if (esPanel && resp.status === 200 && resp.type === 'basic') {
            event.waitUntil(cache.put(PANEL_URL, resp.clone()));
        }
        return resp;
    } catch (_) {
        if (copia) return copia;
        return (await caches.match(OFFLINE_URL)) || Response.error();
    }
}

// Estáticos: se sirven desde el equipo y se actualizan en segundo plano.
// La clave ignora la query (?v=7) para no acumular una copia por cada versión.
async function estaticoConRevalidacion(event, url) {
    const cache = await caches.open(STATIC_CACHE);
    const clave = new Request(url.origin + url.pathname);
    const guardado = await cache.match(clave);
    const actualizacion = fetch(event.request)
        .then((resp) => {
            if (resp.ok && resp.type === 'basic') cache.put(clave, resp.clone());
            return resp;
        })
        .catch(() => null);
    if (guardado) {
        event.waitUntil(actualizacion);
        return guardado;
    }
    return (await actualizacion) || Response.error();
}
