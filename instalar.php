<?php
declare(strict_types=1);

/*
 * Instalador web — para hostings compartidos sin acceso SSH/CLI.
 *
 * Si tu hosting SÍ te permite ejecutar comandos por terminal, usa en su
 * lugar `php backend/crear_primer_admin.php` (ver README_PRODUCCION.md) y
 * define las variables de entorno reales del proceso PHP: es la opción
 * preferida porque no deja credenciales en un archivo dentro del proyecto.
 *
 * Este instalador es la alternativa cuando eso no es posible. Hace tres
 * cosas, en un solo paso:
 *   1) Prueba la conexión a MySQL con los datos que entregues y crea las
 *      tablas si no existen (mismo esquema que base_dato/base_dato_formulario).
 *   2) Crea el primer usuario administrador con la contraseña que definas.
 *   3) Guarda la configuración en backend/config.local.php (bloqueado a
 *      acceso web por backend/.htaccess) para que el resto del sistema
 *      funcione sin depender de variables de entorno del servidor.
 *
 * SEGURIDAD — leer antes de publicar el sitio:
 *   - Este archivo queda inutilizado solo mediante backend/storage/instalado.lock,
 *     que se crea automáticamente al terminar con éxito. Aun así, DEBES
 *     eliminar instalar.php del servidor (o renombrarlo/protegerlo) apenas
 *     termines. No sirve para nada una vez instalado y es superficie de
 *     ataque innecesaria si queda publicado.
 *   - Mientras el sitio no esté instalado, cualquier persona que conozca
 *     la URL puede intentar usar este formulario. La barrera real es que
 *     necesita las credenciales verdaderas de tu base de datos MySQL
 *     (las que te entrega tu hosting), que nadie más debería conocer.
 */

session_start();

const LOCK_FILE = __DIR__ . '/backend/storage/instalado.lock';
const CONFIG_FILE = __DIR__ . '/backend/config.local.php';
const RATE_DIR = __DIR__ . '/backend/storage/rate_limit';

function yaInstalado(): bool {
    return is_file(LOCK_FILE) || is_file(CONFIG_FILE);
}

function e(mixed $v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function validPasswordInstalador(string $password): bool {
    return strlen($password) >= 12
        && strlen($password) <= 255
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/\d/', $password)
        && preg_match('/[^A-Za-z0-9]/', $password);
}

/* Límite simple de intentos por IP, reutilizando el mismo mecanismo de
 * archivos que usa el resto del sistema, para frenar automatización. */
function limiteInstalador(string $ip): bool {
    if (!is_dir(RATE_DIR)) {
        @mkdir(RATE_DIR, 0700, true);
    }
    $safe = preg_replace('/[^a-f0-9]/', '', hash('sha256', 'instalador|' . $ip));
    $file = RATE_DIR . '/' . $safe . '.json';
    $fp = @fopen($file, 'c+');
    if (!$fp) {
        return true; // no bloquear la instalación por un problema de disco
    }
    flock($fp, LOCK_EX);
    $raw = stream_get_contents($fp);
    $data = json_decode($raw ?: '{}', true);
    $now = time();
    $intentos = is_array($data['intentos'] ?? null) ? $data['intentos'] : [];
    $intentos = array_values(array_filter($intentos, static fn($t) => is_int($t) && ($now - $t) < 900));
    $permitido = count($intentos) < 10;
    if ($permitido) {
        $intentos[] = $now;
    }
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode(['intentos' => $intentos]));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return $permitido;
}

if (empty($_SESSION['instalador_csrf'])) {
    $_SESSION['instalador_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['instalador_csrf'];

$error = '';
$éxito = null; // ['username' => ..., 'baseUrl' => ...]

if (yaInstalado()) {
    http_response_code(403);
    ?><!doctype html><html lang="es"><head><meta charset="utf-8"><title>Instalador</title></head>
    <body style="font-family:sans-serif;max-width:640px;margin:60px auto;line-height:1.5">
    <h1>El sistema ya fue instalado</h1>
    <p>Este instalador ya se ejecutó (o hay una configuración previa) y quedó desactivado por seguridad.</p>
    <p>Si necesitas reinstalar desde cero, elimina manualmente <code>backend/storage/instalado.lock</code> y
       <code>backend/config.local.php</code> desde el administrador de archivos de tu hosting, y luego
       vuelve a cargar esta página.</p>
    <p><strong>Por seguridad, elimina este archivo (<code>instalar.php</code>) del servidor ahora.</strong></p>
    </body></html><?php
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!limiteInstalador($ip)) {
        $error = 'Demasiados intentos. Espera unos minutos y vuelve a intentar.';
    } elseif (empty($_POST['csrf_token']) || !hash_equals($csrf, (string)$_POST['csrf_token'])) {
        $error = 'La sesión del formulario expiró. Recarga la página e inténtalo de nuevo.';
    } else {
        $dbHost = trim((string)($_POST['db_host'] ?? ''));
        $dbPort = trim((string)($_POST['db_port'] ?? '3306'));
        $dbUser = trim((string)($_POST['db_user'] ?? ''));
        $dbPass = (string)($_POST['db_pass'] ?? '');
        $dbName = trim((string)($_POST['db_name'] ?? ''));

        $appName = trim((string)($_POST['app_name'] ?? 'EMEX'));
        $appBaseUrl = trim((string)($_POST['app_base_url'] ?? ''));

        $adminUser = trim((string)($_POST['admin_user'] ?? ''));
        $adminEmail = trim((string)($_POST['admin_email'] ?? ''));
        $adminPass = (string)($_POST['admin_pass'] ?? '');
        $adminPassConfirm = (string)($_POST['admin_pass_confirm'] ?? '');

        $smtpHost = trim((string)($_POST['smtp_host'] ?? ''));
        $smtpPort = trim((string)($_POST['smtp_port'] ?? '587'));
        $smtpUser = trim((string)($_POST['smtp_user'] ?? ''));
        $smtpPass = (string)($_POST['smtp_pass'] ?? '');
        $smtpSecure = (string)($_POST['smtp_secure'] ?? 'tls');
        $smtpFromEmail = trim((string)($_POST['smtp_from_email'] ?? ''));

        if (
            $dbHost === '' || $dbUser === '' || $dbName === '' ||
            !preg_match('/^[A-Za-z0-9_]{1,64}$/', $dbName) ||
            !preg_match('/^\d{1,5}$/', $dbPort)
        ) {
            $error = 'Revisa los datos de conexión a la base de datos (host, usuario, puerto y nombre de base son obligatorios; el nombre solo admite letras, números y guion bajo).';
        } elseif (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $adminUser)) {
            $error = 'El usuario administrador debe tener entre 3 y 50 caracteres (letras, números, punto, guion o guion bajo).';
        } elseif ($adminEmail !== '' && !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'El correo del administrador no es válido.';
        } elseif (!validPasswordInstalador($adminPass)) {
            $error = 'La contraseña del administrador debe tener mínimo 12 caracteres, con mayúscula, minúscula, número y símbolo.';
        } elseif ($adminPass !== $adminPassConfirm) {
            $error = 'La confirmación de contraseña no coincide.';
        } elseif ($appBaseUrl !== '' && !filter_var($appBaseUrl, FILTER_VALIDATE_URL)) {
            $error = 'La URL base de la aplicación no es válida (ejemplo: https://tudominio.com/control_combustible).';
        } else {
            try {
                mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
                $conn = mysqli_init();
                $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 8);
                $conn->real_connect($dbHost, $dbUser, $dbPass, '', (int)$dbPort);
                $conn->set_charset('utf8mb4');

                /* Algunos hostings no dan privilegio CREATE DATABASE (ya viene
                 * creada desde el panel de control): se intenta, y si falla se
                 * continúa asumiendo que la base ya existe. */
                try {
                    $conn->query("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                } catch (Throwable $ignorado) {
                }
                $conn->select_db($dbName);
                $conn->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");

                $conn->query("CREATE TABLE IF NOT EXISTS usuarios (
                    id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50) NOT NULL UNIQUE, email VARCHAR(100) UNIQUE,
                    password VARCHAR(255) NOT NULL, rol ENUM('admin','operador') NOT NULL DEFAULT 'operador',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
                    debe_cambiar_password TINYINT(1) NOT NULL DEFAULT 0,
                    INDEX idx_estado_rol(estado,rol)
                ) ENGINE=InnoDB");

                $conn->query("CREATE TABLE IF NOT EXISTS operador_cargador (
                    id INT AUTO_INCREMENT PRIMARY KEY, nombres VARCHAR(100) NOT NULL, apellido_paterno VARCHAR(100) NOT NULL, apellido_materno VARCHAR(100) NOT NULL,
                    rut VARCHAR(12) NOT NULL, turno CHAR(1) NOT NULL, codigo VARCHAR(50) NOT NULL, ubicacion VARCHAR(100) NOT NULL, patente VARCHAR(10) NOT NULL,
                    horometro DECIMAL(10,2) NOT NULL, litros DECIMAL(10,2) NOT NULL, remanente DECIMAL(10,2) NOT NULL DEFAULT 0, observacion TEXT,
                    eliminado_at DATETIME NULL, eliminado_por INT NULL,
                    usuario_id INT NULL, fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_cargador_rut_fecha(rut,fecha_registro), INDEX idx_cargador_usuario(usuario_id), INDEX idx_cargador_eliminado(eliminado_at),
                    CONSTRAINT fk_cargador_usuario FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
                ) ENGINE=InnoDB");

                $conn->query("CREATE TABLE IF NOT EXISTS operador (
                    id INT AUTO_INCREMENT PRIMARY KEY, nombres VARCHAR(100) NOT NULL, apellido_paterno VARCHAR(100) NOT NULL, apellido_materno VARCHAR(100) NOT NULL,
                    rut VARCHAR(12) NOT NULL, turno CHAR(1) NOT NULL, ubicacion VARCHAR(100) NOT NULL, codigo_maquinaria VARCHAR(50) NOT NULL, patente VARCHAR(10) NOT NULL,
                    equipo VARCHAR(100) NOT NULL, horometro DECIMAL(10,2) NULL, kilometro DECIMAL(10,2) NULL, litros DECIMAL(10,2) NOT NULL, consumo DECIMAL(10,2) NOT NULL DEFAULT 0,
                    observacion TEXT, eliminado_at DATETIME NULL, eliminado_por INT NULL, usuario_id INT NULL, fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_operador_rut_fecha(rut,fecha_registro), INDEX idx_operador_usuario(usuario_id), INDEX idx_operador_eliminado(eliminado_at),
                    CONSTRAINT fk_operador_usuario FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
                ) ENGINE=InnoDB");

                $conn->query("CREATE TABLE IF NOT EXISTS recuperacion_password (
                    id INT AUTO_INCREMENT PRIMARY KEY, usuario_id INT NOT NULL, token_hash CHAR(64) NOT NULL UNIQUE, usado TINYINT(1) NOT NULL DEFAULT 0,
                    fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP, expira_en DATETIME NOT NULL, INDEX idx_reset_usuario(usuario_id),
                    CONSTRAINT fk_reset_usuario FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
                ) ENGINE=InnoDB");

                $conn->query("CREATE TABLE IF NOT EXISTS auditoria (
                    id BIGINT AUTO_INCREMENT PRIMARY KEY, usuario_id INT NULL, accion VARCHAR(80) NOT NULL, tabla_afectada VARCHAR(80) NULL, registro_id INT NULL,
                    detalle TEXT NULL, ip VARCHAR(45) NOT NULL, user_agent VARCHAR(500) NULL, fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_auditoria_fecha(fecha_registro), INDEX idx_auditoria_usuario(usuario_id), INDEX idx_auditoria_accion(accion),
                    CONSTRAINT fk_auditoria_usuario FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
                ) ENGINE=InnoDB");

                $existente = $conn->query("SELECT COUNT(*) c FROM usuarios WHERE rol='admin'")->fetch_assoc();
                if ((int)$existente['c'] > 0) {
                    $error = 'Ya existe al menos un administrador en esta base de datos. Este instalador solo se usa para la instalación inicial.';
                } else {
                    $hash = password_hash($adminPass, PASSWORD_DEFAULT);
                    $rol = 'admin';
                    $estado = 'activo';
                    $emailParam = $adminEmail !== '' ? $adminEmail : null;
                    $stmt = $conn->prepare('INSERT INTO usuarios (username, password, email, rol, estado, debe_cambiar_password) VALUES (?,?,?,?,?,0)');
                    $stmt->bind_param('sssss', $adminUser, $hash, $emailParam, $rol, $estado);
                    $stmt->execute();
                    $adminId = (int)$stmt->insert_id;

                    try {
                        $accion = 'CREAR_PRIMER_ADMIN_INSTALADOR_WEB';
                        $tabla = 'usuarios';
                        $detalle = 'username=' . $adminUser;
                        $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
                        $auditStmt = $conn->prepare('INSERT INTO auditoria (usuario_id, accion, tabla_afectada, registro_id, detalle, ip, user_agent) VALUES (?,?,?,?,?,?,?)');
                        $auditStmt->bind_param('ississs', $adminId, $accion, $tabla, $adminId, $detalle, $ip, $ua);
                        $auditStmt->execute();
                    } catch (Throwable $ignorado) {
                    }

                    /* Genera backend/config.local.php con la configuración. Se
                     * escribe con var_export() para que los valores queden
                     * como literales de cadena seguros, sin riesgo de
                     * inyección de código aunque contengan comillas. */
                    $lineas = [];
                    $lineas[] = '<?php';
                    $lineas[] = 'declare(strict_types=1);';
                    $lineas[] = '/* Generado por instalar.php — no editar a mano si vas a reinstalar. */';
                    $vars = [
                        'APP_ENV' => 'production',
                        'APP_NAME' => $appName !== '' ? $appName : 'EMEX',
                        'APP_BASE_URL' => $appBaseUrl,
                        'DB_HOST' => $dbHost . ($dbPort !== '3306' ? ':' . $dbPort : ''),
                        'DB_USER' => $dbUser,
                        'DB_PASS' => $dbPass,
                        'DB_NAME' => $dbName,
                        'SMTP_HOST' => $smtpHost,
                        'SMTP_PORT' => $smtpPort,
                        'SMTP_USER' => $smtpUser,
                        'SMTP_PASS' => $smtpPass,
                        'SMTP_SECURE' => $smtpSecure,
                        'SMTP_FROM_EMAIL' => $smtpFromEmail,
                        'SMTP_FROM_NAME' => $appName !== '' ? $appName : 'EMEX',
                    ];
                    foreach ($vars as $k => $v) {
                        if ($v === '') {
                            continue;
                        }
                        $lineas[] = 'if (getenv(' . var_export($k, true) . ') === false) { putenv(' . var_export($k, true) . ' . \'=\' . ' . var_export($v, true) . '); }';
                    }
                    $contenido = implode("\n", $lineas) . "\n";

                    if (@file_put_contents(CONFIG_FILE, $contenido) === false) {
                        $error = 'El administrador se creó, pero no fue posible escribir backend/config.local.php (revisa permisos de escritura en la carpeta backend/). Configura las variables de entorno manualmente según CONFIGURACION_SEGURA_PRODUCCION.txt.';
                    } else {
                        @chmod(CONFIG_FILE, 0640);
                        @file_put_contents(LOCK_FILE, 'Instalado: ' . date('Y-m-d H:i:s') . "\n");
                        unset($_SESSION['instalador_csrf']);
                        $éxito = ['username' => $adminUser];
                    }
                }
            } catch (Throwable $e) {
                $error = 'No fue posible conectar o preparar la base de datos: ' . $e->getMessage();
            }
        }
    }
}
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Instalación — Control de Combustible</title>
<style>
 body{font-family:system-ui,sans-serif;background:#f3f4f6;margin:0;padding:24px}
 .card{max-width:640px;margin:0 auto;background:#fff;border-radius:10px;padding:28px;box-shadow:0 1px 4px rgba(0,0,0,.08)}
 h1{font-size:1.4rem;margin-top:0}
 h2{font-size:1rem;margin:24px 0 8px;color:#374151}
 label{display:block;font-size:.85rem;font-weight:600;margin:12px 0 4px;color:#374151}
 input,select{width:100%;box-sizing:border-box;padding:9px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:.95rem}
 small{color:#6b7280}
 .fila{display:grid;grid-template-columns:1fr 1fr;gap:12px}
 button{margin-top:22px;width:100%;padding:12px;background:#2563eb;color:#fff;border:0;border-radius:6px;font-size:1rem;cursor:pointer}
 .error{background:#fef2f2;color:#991b1b;padding:12px;border-radius:6px;margin-bottom:16px;font-size:.9rem}
 .ok{background:#f0fdf4;color:#166534;padding:16px;border-radius:6px;font-size:.9rem}
 .aviso{background:#fffbeb;color:#92400e;padding:12px;border-radius:6px;font-size:.85rem;margin-top:16px}
 code{background:#f3f4f6;padding:2px 6px;border-radius:4px}
</style>
</head>
<body>
<div class="card">

<?php if ($éxito): ?>

    <h1>✅ Instalación completada</h1>
    <div class="ok">
        <p>Se creó el usuario administrador <strong><?= e($éxito['username']) ?></strong> y la configuración quedó
           guardada en <code>backend/config.local.php</code>.</p>
        <p>Ya puedes <a href="index.php">iniciar sesión</a> con el usuario y la contraseña que definiste en este formulario.</p>
    </div>
    <div class="aviso">
        <strong>Importante — hazlo ahora:</strong> elimina <code>instalar.php</code> del servidor (o al menos
        renómbralo o restringe su acceso con <code>.htaccess</code>). Este instalador ya quedó bloqueado por el
        archivo <code>backend/storage/instalado.lock</code>, pero mientras el archivo siga publicado sigue siendo
        una superficie innecesaria.
    </div>

<?php else: ?>

    <h1>Instalación — Control de Combustible</h1>
    <p style="color:#4b5563;font-size:.9rem">
        Usa este formulario solo si tu hosting no te permite ejecutar comandos por SSH/terminal.
        Necesitas las credenciales de una base de datos MySQL ya creada (te las entrega tu panel de hosting).
    </p>

    <?php if ($error !== ''): ?><div class="error"><?= e($error) ?></div><?php endif; ?>

    <form method="POST" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

        <h2>Base de datos MySQL</h2>
        <div class="fila">
            <div><label>Host</label><input name="db_host" value="<?= e($_POST['db_host'] ?? 'localhost') ?>" required></div>
            <div><label>Puerto</label><input name="db_port" value="<?= e($_POST['db_port'] ?? '3306') ?>" required></div>
        </div>
        <label>Usuario de la base de datos</label>
        <input name="db_user" value="<?= e($_POST['db_user'] ?? '') ?>" required>
        <label>Contraseña de la base de datos</label>
        <input type="password" name="db_pass" autocomplete="new-password">
        <label>Nombre de la base de datos</label>
        <input name="db_name" value="<?= e($_POST['db_name'] ?? '') ?>" required>
        <small>Solo letras, números y guion bajo. Si no existe y tu usuario tiene privilegios, se crea automáticamente.</small>

        <h2>Datos del sitio</h2>
        <label>Nombre comercial (se muestra en el sistema)</label>
        <input name="app_name" value="<?= e($_POST['app_name'] ?? 'EMEX') ?>">
        <label>URL base del sitio</label>
        <input name="app_base_url" placeholder="https://tudominio.com/control_combustible" value="<?= e($_POST['app_base_url'] ?? '') ?>">
        <small>Déjalo vacío si el sistema queda instalado en la raíz del dominio.</small>

        <h2>Primer administrador</h2>
        <label>Usuario</label>
        <input name="admin_user" value="<?= e($_POST['admin_user'] ?? '') ?>" required pattern="[A-Za-z0-9._-]{3,50}">
        <label>Correo (opcional, para recuperar contraseña)</label>
        <input type="email" name="admin_email" value="<?= e($_POST['admin_email'] ?? '') ?>">
        <label>Contraseña</label>
        <input type="password" name="admin_pass" autocomplete="new-password" required minlength="12">
        <small>Mínimo 12 caracteres, con mayúscula, minúscula, número y símbolo.</small>
        <label>Confirmar contraseña</label>
        <input type="password" name="admin_pass_confirm" autocomplete="new-password" required minlength="12">

        <h2>Correo SMTP (opcional, recomendado)</h2>
        <p style="font-size:.82rem;color:#6b7280;margin:0 0 8px">Si lo dejas vacío, el sistema intentará usar la
            función <code>mail()</code> del servidor, que en muchos hostings no entrega los correos de forma confiable.</p>
        <div class="fila">
            <div><label>Servidor SMTP</label><input name="smtp_host" value="<?= e($_POST['smtp_host'] ?? '') ?>"></div>
            <div><label>Puerto</label><input name="smtp_port" value="<?= e($_POST['smtp_port'] ?? '587') ?>"></div>
        </div>
        <label>Usuario SMTP</label>
        <input name="smtp_user" value="<?= e($_POST['smtp_user'] ?? '') ?>">
        <label>Contraseña SMTP</label>
        <input type="password" name="smtp_pass" autocomplete="new-password">
        <label>Cifrado</label>
        <select name="smtp_secure">
            <option value="tls" <?= (($_POST['smtp_secure'] ?? 'tls') === 'tls') ? 'selected' : '' ?>>TLS (puerto 587, recomendado)</option>
            <option value="ssl" <?= (($_POST['smtp_secure'] ?? '') === 'ssl') ? 'selected' : '' ?>>SSL (puerto 465)</option>
        </select>
        <label>Correo remitente</label>
        <input type="email" name="smtp_from_email" placeholder="no-reply@tudominio.com" value="<?= e($_POST['smtp_from_email'] ?? '') ?>">

        <button type="submit">Instalar</button>
    </form>

<?php endif; ?>

</div>
</body>
</html>
