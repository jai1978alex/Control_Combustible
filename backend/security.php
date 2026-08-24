<?php
declare(strict_types=1);

/*
 * Capa central de seguridad.
 * - Sesiones endurecidas
 * - CSRF
 * - autorización
 * - encabezados HTTP
 * - validaciones
 * - auditoría
 * - control de sesión mediante updated_at
 */

const SESSION_TIMEOUT = 1800; // 30 minutos
const LOGIN_WINDOW = 900;     // 15 minutos
const LOGIN_MAX_ATTEMPTS = 5;
const RECOVERY_WINDOW = 900;
const RECOVERY_MAX_ATTEMPTS = 3;

function securityHeaders(): void {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: no-referrer');
    header('X-XSS-Protection: 0');
    header('X-DNS-Prefetch-Control: off');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Permissions-Policy: geolocation=(self), camera=(self), microphone=()');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net https://unpkg.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; connect-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'self'; form-action 'self';");
    header('X-Permitted-Cross-Domain-Policies: none');
    header('Cross-Origin-Resource-Policy: same-origin');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
securityHeaders();

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', (string)SESSION_TIMEOUT);
    ini_set('expose_php', '0');
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');

    if (session_name() === 'PHPSESSID') {
        session_name('EMEXSESSID');
    }

    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

function destroyCurrentSession(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $p['path'] ?: '/',
            'domain' => $p['domain'] ?? '',
            'secure' => (bool)$p['secure'],
            'httponly' => (bool)$p['httponly'],
            'samesite' => $p['samesite'] ?? 'Lax'
        ]);
    }
    session_destroy();
}

function sessionBindingMatches(): bool {
    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    $ip = clientIp();
    $fingerprint = hash('sha256', $ua);
    return empty($_SESSION['binding']) || hash_equals((string)$_SESSION['binding'], $fingerprint);
}

function requireLogin(): void {
    if (empty($_SESSION['usuario_id']) || empty($_SESSION['username']) || empty($_SESSION['rol'])) {
        header('Location: ' . appBasePath() . '/index.php?error=acceso');
        exit;
    }

    if (!sessionBindingMatches()) {
        destroyCurrentSession();
        header('Location: ' . appBasePath() . '/index.php?error=sesion');
        exit;
    }

    if (isset($_SESSION['last_activity']) && (time() - (int)$_SESSION['last_activity']) > SESSION_TIMEOUT) {
        destroyCurrentSession();
        header('Location: ' . appBasePath() . '/index.php?error=sesion');
        exit;
    }

    /*
     * Comprobación de estado y updated_at en cada petición protegida.
     * Si la contraseña cambia o la cuenta se desactiva, las sesiones
     * existentes dejan de ser válidas.
     */
    // La conexión debe quedar disponible tanto dentro de requireLogin() como
    // en los archivos que llaman a esta función (por ejemplo, panel.php).
    global $conn;
    require_once __DIR__ . '/conexion.php';
    $uid = (int)$_SESSION['usuario_id'];
    $stmt = $conn->prepare('SELECT username, rol, estado, updated_at, password, debe_cambiar_password FROM usuarios WHERE id=? LIMIT 1');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();

    if (!$u || $u['estado'] !== 'activo') {
        destroyCurrentSession();
        header('Location: ' . appBasePath() . '/index.php?error=acceso');
        exit;
    }

    $sessionUpdated = (string)($_SESSION['user_updated_at'] ?? '');
    if ($sessionUpdated === '' || !hash_equals($sessionUpdated, (string)$u['updated_at'])) {
        destroyCurrentSession();
        header('Location: ' . appBasePath() . '/index.php?error=sesion');
        exit;
    }

    $_SESSION['last_activity'] = time();
    $_SESSION['username'] = $u['username'];
    $_SESSION['rol'] = $u['rol'];
    $_SESSION['debe_cambiar_password'] = (int)$u['debe_cambiar_password'];

    /*
     * Si el admin reseteó la contraseña de este usuario, se le obliga a
     * definir una nueva antes de continuar. Se exceptúan la propia página
     * de cambio de contraseña y el cierre de sesión, para no dejar al
     * usuario sin salida.
     */
    $script = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $permitidasConCambioPendiente = ['cambiar_password.php', 'logout.php'];
    if (!empty($_SESSION['debe_cambiar_password']) && !in_array($script, $permitidasConCambioPendiente, true)) {
        header('Location: ' . appBasePath() . '/html/programa/cambiar_password.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (($_SESSION['rol'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Acceso restringido.');
    }
}

function requirePost(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Método no permitido.');
    }
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function loginCsrfToken(): string {
    if (empty($_SESSION['login_csrf'])) {
        $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['login_csrf'];
}

function verifyCsrf(): void {
    $token = (string)($_POST['csrf_token'] ?? '');
    if ($token === '' || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Solicitud no válida.');
    }
}

function verifyLoginCsrf(string $token): bool {
    return $token !== '' && !empty($_SESSION['login_csrf']) &&
        hash_equals($_SESSION['login_csrf'], $token);
}

function e(mixed $value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function clientIp(): string {
    /*
     * No confiar en X-Forwarded-For sin un proxy de confianza configurado.
     */
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Nombre comercial mostrado en títulos, logo y encabezados impresos.
 *
 * Antes estaba escrito a mano como "EMEX" en 7 archivos distintos. Si este
 * proyecto se vende o reutiliza para otro cliente, basta con definir
 * APP_NAME en el entorno (o cambiar el valor por defecto aquí) en vez de
 * buscar y reemplazar el texto en cada plantilla.
 *
 * Nota: los dos formularios estáticos (recuperar.html y restablecer.html)
 * no pasan por PHP, así que si cambias el nombre también hay que editar el
 * texto "EMEX" directamente en esos dos archivos.
 */
function appName(): string {
    $name = trim((string)(getenv('APP_NAME') ?: ''));
    return $name !== '' ? $name : 'EMEX';
}

/**
 * Ruta base de la aplicación dentro del dominio (por ejemplo
 * "/control_combustible", o "" si se instala en la raíz del sitio).
 *
 * Antes esta ruta estaba escrita a mano ("/control_combustible/...") en
 * cada redirect del sistema, lo que rompía el login, el logout y el cambio
 * de contraseña obligatorio en cualquier instalación que no usara
 * exactamente esa carpeta (otro nombre, subdominio, o raíz del dominio).
 *
 * Se calcula automáticamente comparando la ubicación real del proyecto en
 * disco con el DOCUMENT_ROOT del servidor. Puede forzarse definiendo
 * APP_BASE_URL (se usa solo la parte de ruta de esa URL, no el dominio),
 * útil en despliegues detrás de un proxy o alias donde la comparación de
 * rutas de disco no sea fiable.
 */
function appBasePath(): string {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $envBase = trim((string)(getenv('APP_BASE_URL') ?: ''));
    if ($envBase !== '') {
        $path = (string)(parse_url($envBase, PHP_URL_PATH) ?? '');
        return $cached = rtrim($path, '/');
    }

    $appRoot = realpath(__DIR__ . '/..');
    $docRoot = realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? ''));

    if ($appRoot !== false && $docRoot !== false && str_starts_with($appRoot, $docRoot)) {
        $rel = substr($appRoot, strlen($docRoot));
        return $cached = rtrim(str_replace('\\', '/', $rel), '/');
    }

    /* Último recurso: mantiene el comportamiento previo si no se pudo detectar. */
    return $cached = '/control_combustible';
}

/**
 * Comprueba si una columna existe en una tabla de la base actual.
 *
 * Se usa para mantener compatibilidad con instalaciones anteriores a la
 * migración `migracion_produccion_100.sql` (que añade `eliminado_at` /
 * `eliminado_por`). Con mysqli en modo STRICT, consultar una columna
 * inexistente lanza una excepción y termina en un HTTP 500. Centralizada
 * aquí para que cualquier pantalla que la necesite (superadmin.php,
 * panel.php, etc.) se comporte igual sin duplicar la lógica.
 */
function columnaExiste(mysqli $conn, string $tabla, string $columna): bool {
    $st = $conn->prepare('SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
    $st->bind_param('ss', $tabla, $columna);
    $st->execute();
    return (int)$st->get_result()->fetch_assoc()['c'] > 0;
}

function audit(mysqli $conn, ?int $usuarioId, string $accion, ?string $tabla = null, ?int $registroId = null, ?string $detalle = null): void {
    try {
        $stmt = $conn->prepare('INSERT INTO auditoria (usuario_id, accion, tabla_afectada, registro_id, detalle, ip, user_agent) VALUES (?,?,?,?,?,?,?)');
        $ip = clientIp();
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
        $stmt->bind_param('ississs', $usuarioId, $accion, $tabla, $registroId, $detalle, $ip, $ua);
        $stmt->execute();
    } catch (Throwable $e) {
        error_log('Auditoria no disponible: '.$e->getMessage());
    }
}

function rateLimitFile(string $bucket, string $key, int $window, int $max, bool $consume = true): bool {
    $dir = __DIR__ . '/storage/rate_limit';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    $safe = preg_replace('/[^a-f0-9]/', '', hash('sha256', $bucket.'|'.$key));
    $file = $dir . DIRECTORY_SEPARATOR . $safe . '.json';
    /* Limpieza ocasional para evitar crecimiento indefinido del directorio. */
    try {
        if (random_int(1, 100) === 1) {
            foreach (glob($dir . DIRECTORY_SEPARATOR . '*.json') ?: [] as $oldFile) {
                if (@filemtime($oldFile) !== false && (time() - (int)filemtime($oldFile)) > 86400) @unlink($oldFile);
            }
        }
    } catch (Throwable $e) {}
    $fp = @fopen($file, 'c+');
    if (!$fp) {
        error_log('No se pudo abrir el almacenamiento de rate limiting.');
        return false;
    }

    flock($fp, LOCK_EX);
    $raw = stream_get_contents($fp);
    $data = json_decode($raw ?: '{}', true);
    $now = time();
    $attempts = is_array($data['attempts'] ?? null) ? $data['attempts'] : [];
    $attempts = array_values(array_filter($attempts, static fn($t) => is_int($t) && ($now - $t) < $window));
    $allowed = count($attempts) < $max;

    if ($allowed && $consume) {
        $attempts[] = $now;
    }

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode(['attempts' => $attempts], JSON_UNESCAPED_SLASHES));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return $allowed;
}

function validText(string $value, int $max = 100): bool {
    if ($value === '' || mb_strlen($value) > $max) return false;
    return !preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $value);
}

function validDecimal(string $value, float $min = 0, float $max = 100000000): bool {
    if ($value === '' || !preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) return false;
    $n = (float)$value;
    return is_finite($n) && $n >= $min && $n <= $max;
}

function validGps(string $value): bool {
    if (!preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/', $value, $m)) return false;
    $lat = (float)$m[1];
    $lon = (float)$m[2];
    return is_finite($lat) && is_finite($lon) &&
        $lat >= -90 && $lat <= 90 && $lon >= -180 && $lon <= 180;
}

function validPassword(string $password): bool {
    return strlen($password) >= 12
        && strlen($password) <= 255
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/\d/', $password)
        && preg_match('/[^A-Za-z0-9]/', $password);
}

function normalizeRut(string $rut): string {
    return strtoupper(trim($rut));
}

/**
 * Genera una contraseña temporal aleatoria que cumple validPassword().
 * Se usa cuando el admin resetea la clave de otro usuario (ver
 * backend/resetear_password_usuario.php).
 */
function generarPasswordTemporal(): string {
    $mayus = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $minus = 'abcdefghijkmnpqrstuvwxyz';
    $nums = '23456789';
    $simb = '!@#$%&*-_+=?';
    $todos = $mayus.$minus.$nums.$simb;

    $pick = static function (string $set): string {
        return $set[random_int(0, strlen($set) - 1)];
    };

    $chars = [$pick($mayus), $pick($minus), $pick($nums), $pick($simb)];
    for ($i = 0; $i < 12; $i++) {
        $chars[] = $pick($todos);
    }
    shuffle($chars);
    return implode('', $chars);
}
