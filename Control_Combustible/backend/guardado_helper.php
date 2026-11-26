<?php
/*
Sitio Web Creado por Jerez.
Direccion: Miraflorez #1280
Quintero - Chile
jaime.jerez1978@gmail.com
Creado, Programado y Diseñado por Jerez.
JJA
*/

/* -------------------------------------------------------------------------------------------------------------
   ------------------------------------- INICIO guardado_helper.php ---------------------------------------------
  ------------------------------------------------------------------------------------------------------------- */
  
  
  /* Funciones compartidas por guardar_cargador.php y guardar_operador.php para el modo sin conexión (PWA).
   - Responden en JSON cuando la petición viene de la cola offline (cabecera Accept: application/json).
   - Siguen redirigiendo al panel cuando la petición es un envío normal, igual que antes.
   - Evitan registros duplicados cuando el celular reenvía una carga cuya respuesta se perdió. */
   

declare(strict_types=1);

// Máximo de días hacia atrás que se acepta como "momento real de la carga" de un registro hecho sin conexión.
const OFFLINE_MAX_DIAS = 14;
// Diferencia mínima (segundos) entre la hora del equipo y la del servidor para considerar que el registro fue offline.
const OFFLINE_UMBRAL_SEGUNDOS = 120;

/**
 * Códigos de error que NO se pueden arreglar reintentando: el operador debe corregir o descartar el registro.
 * Cualquier otro código (sesion, csrf, demasiados_intentos, guardar...) se considera temporal y el registro se reintenta.
 */
function finGuardado(string $estado, string $codigo = '', array $extra = []): never
{
    if (function_exists('wantsJson') && wantsJson()) {
        $http = 200;
        if ($estado !== 'ok') {
            $http = match ($codigo) {
                'demasiados_intentos' => 429,
                'guardar'             => 500,
                default               => 422,
            };
        }
        jsonExit($http, array_merge(['ok' => $estado === 'ok', 'codigo' => $codigo], $extra));
    }

    $destino = '../html/programa/panel.php';
    if ($estado === 'ok') {
        header('Location: ' . $destino . '?success=1');
    } else {
        header('Location: ' . $destino . '?error=' . rawurlencode($codigo));
    }
    exit;
}

/** Devuelve el UUID enviado por el equipo (o '' si no viene o no es válido). */
function uuidCliente(): string
{
    $uuid = strtolower(trim((string)($_POST['uuid_cliente'] ?? '')));
    return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $uuid) ? $uuid : '';
}

/** Indica si la tabla ya tiene la columna uuid_cliente (migración 09). Sin ella el sistema funciona igual, solo sin anti-duplicados. */
function tablaTieneUuid(mysqli $conn, string $tabla): bool
{
    static $cache = [];
    if (!isset($cache[$tabla])) {
        $cache[$tabla] = columnaExiste($conn, $tabla, 'uuid_cliente');
    }
    return $cache[$tabla];
}

/** ¿Ya existe un registro con este UUID para este usuario? Si sí, el reenvío es un duplicado y se responde "ok". */
function registroYaGuardado(mysqli $conn, string $tabla, string $uuid, int $usuarioId): bool
{
    if ($uuid === '' || !in_array($tabla, ['operador_cargador', 'operador'], true) || !tablaTieneUuid($conn, $tabla)) {
        return false;
    }
    $stmt = $conn->prepare("SELECT id FROM {$tabla} WHERE uuid_cliente = ? AND usuario_id = ? LIMIT 1");
    $stmt->bind_param('si', $uuid, $usuarioId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() !== null;
}

/** ¿El error de MySQL es "clave duplicada" (1062)? Pasa si dos reenvíos del mismo registro llegan casi a la vez. */
function esErrorDuplicado(Throwable $e): bool
{
    return (int)$e->getCode() === 1062;
}

/**
 * Fecha del registro. Normalmente es la hora del servidor (como siempre).
 * Si el registro se hizo sin conexión, el equipo manda el momento real de la captura (capturado_ms) y se usa ese,
 * siempre que sea razonable: no futuro y no más antiguo que OFFLINE_MAX_DIAS.
 * Devuelve [fecha_para_guardar, detalle_para_auditoria ('' si no fue offline)].
 */
function fechaRegistroCliente(): array
{
    $ahora = time();
    $fechaServidor = date('Y-m-d H:i:s', $ahora);
    $ms = (string)($_POST['capturado_ms'] ?? '');
    if (!preg_match('/^\d{10,14}$/', $ms)) {
        return [$fechaServidor, ''];
    }
    $captura = intdiv((int)$ms, 1000);
    $atraso = $ahora - $captura;
    if ($atraso < OFFLINE_UMBRAL_SEGUNDOS || $atraso > OFFLINE_MAX_DIAS * 86400) {
        return [$fechaServidor, ''];
    }
    return [date('Y-m-d H:i:s', $captura), '; registro sin conexión, capturado ' . date('Y-m-d H:i:s', $captura) . ', recibido ' . $fechaServidor];
}

/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- FIN guardado_helper.php ----------------------------------------------
   ------------------------------------------------------------------------------------------------------------- */
