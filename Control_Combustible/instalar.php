<?php
/*
Sitio Web Creado por Jerez.
Direccion: Miraflorez #1280
Quintero - Chile
jaime.jerez1978@gmail.com
https://www.
Creado, Programado y Diseñado por Jerez.
JJA
*/

/* -------------------------------------------------------------------------------------------------------------
   ------------------------------------- INICIO Jerez instalar.php ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */


// TÍTULO: INICIO DEL PROGRAMA

// Le pide a PHP que sea estricto y no acepte datos mal escritos o mezclados
declare(strict_types=1);

// Abre la "memoria temporal" del visitante para recordar cosas mientras usa esta página
session_start();


// TÍTULO: DATOS FIJOS DEL INSTALADOR

// Archivo "candado": si existe, el sistema ya fue instalado
const LOCK_FILE = __DIR__ . '/backend/storage/instalado.lock';
// Archivo donde se guardan las claves y datos de configuración
const CONFIG_FILE = __DIR__ . '/backend/config.local.php';
// Carpeta donde se anotan los intentos para frenar a quien insista demasiado
const RATE_DIR = __DIR__ . '/backend/storage/rate_limit';
// Archivo aparte donde está escrita la estructura de la base de datos (las tablas)
const SQL_ESTRUCTURA = __DIR__ . '/base_dato/04_INSTALADOR_ESTRUCTURA_TABLAS.sql';


// TÍTULO: HERRAMIENTAS (pequeños ayudantes que se usan más abajo)

// Ayudante 1: revisar si el sistema ya fue instalado

// Responde "sí" o "no" a la pregunta: ¿ya se instaló el sistema?
function yaInstalado(): bool {
    // Dice "sí" si existe el candado o el archivo de configuración
    return is_file(LOCK_FILE) || is_file(CONFIG_FILE);
}


// Ayudante 2: limpiar un texto antes de mostrarlo en pantalla

// Recibe cualquier dato y lo entrega como texto seguro
function e(mixed $v): string {
    // Cambia los símbolos peligrosos por inofensivos para que nadie pueda "colar" código en la página
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}


// Ayudante 3: revisar que la contraseña sea lo bastante fuerte

// Responde "sí" o "no": ¿la contraseña cumple las reglas?
function validPasswordInstalador(string $password): bool {
    // Debe tener 12 letras o más
    return strlen($password) >= 12
        // Y no puede pasar de 255 letras
        && strlen($password) <= 255
        // Debe tener al menos una letra mayúscula
        && preg_match('/[A-Z]/', $password)
        // Debe tener al menos una letra minúscula
        && preg_match('/[a-z]/', $password)
        // Debe tener al menos un número
        && preg_match('/\d/', $password)
        // Debe tener al menos un símbolo (por ejemplo ! # $ %)
        && preg_match('/[^A-Za-z0-9]/', $password);
}


// Ayudante 4: limitar los intentos para que nadie pruebe sin parar

// Responde "sí" si la persona aún puede intentar, "no" si ya intentó demasiado
function limiteInstalador(string $ip): bool {
    // Si la carpeta de intentos todavía no existe...
    if (!is_dir(RATE_DIR)) {
        // ...la crea (el @ evita mostrar avisos feos si algo falla)
        @mkdir(RATE_DIR, 0700, true);
    // Fin de la creación de la carpeta
    }
    // Convierte la dirección de la persona en un código secreto para usarlo como nombre de archivo
    $safe = preg_replace('/[^a-f0-9]/', '', hash('sha256', 'instalador|' . $ip));
    // Arma el nombre completo del archivo donde se anotan sus intentos
    $file = RATE_DIR . '/' . $safe . '.json';
    // Abre ese archivo (o lo crea si no existe) para leerlo y escribirlo
    $fp = @fopen($file, 'c+');
    // Si no se pudo abrir el archivo...
    if (!$fp) {
        // ...no se bloquea a nadie y se deja continuar
        return true;
    // Fin de la revisión del archivo
    }
    // Reserva el archivo para que nadie más lo use justo ahora
    flock($fp, LOCK_EX);
    // Lee todo lo que estaba anotado en el archivo
    $raw = stream_get_contents($fp);
    // Convierte lo leído en una lista que PHP entiende (o una lista vacía si no había nada)
    $data = json_decode($raw ?: '{}', true);
    // Anota la hora actual
    $now = time();
    // Toma la lista de intentos anteriores (o una lista vacía si no hay)
    $intentos = is_array($data['intentos'] ?? null) ? $data['intentos'] : [];
    // Se queda solo con los intentos de los últimos 15 minutos (900 segundos)
    $intentos = array_values(array_filter($intentos, static fn($t) => is_int($t) && ($now - $t) < 900));
    // Permite continuar solo si hizo menos de 10 intentos en ese tiempo
    $permitido = count($intentos) < 10;
    // Si todavía puede intentar...
    if ($permitido) {
        // ...anota este nuevo intento en la lista
        $intentos[] = $now;
    // Fin de la anotación
    }
    // Borra lo que había escrito en el archivo
    ftruncate($fp, 0);
    // Vuelve al principio del archivo
    rewind($fp);
    // Escribe la lista actualizada de intentos
    fwrite($fp, json_encode(['intentos' => $intentos]));
    // Se asegura de que todo quede realmente guardado
    fflush($fp);
    // Libera el archivo para que otros puedan usarlo
    flock($fp, LOCK_UN);
    // Cierra el archivo
    fclose($fp);
    // Entrega la respuesta: "sí" puede seguir, "no" está bloqueado por ahora
    return $permitido;
}


// Ayudante 5: leer del archivo SQL la lista de órdenes para crear las tablas

// Entrega una lista con cada orden SQL escrita en el archivo
function leerSentenciasSql(string $ruta): array {
    // Abre el archivo y lee todo su contenido (el @ evita avisos feos si no existe)
    $texto = @file_get_contents($ruta);
    // Si no se pudo leer el archivo...
    if ($texto === false) {
        // ...detiene la instalación y explica qué falta
        throw new RuntimeException('No se encontró el archivo base_dato/04_INSTALADOR_ESTRUCTURA_TABLAS.sql (súbelo al servidor junto con el instalador).');
    // Fin de la revisión del archivo
    }
    // Quita la marca invisible que algunos editores ponen al inicio del archivo
    $texto = preg_replace('/^\xEF\xBB\xBF/', '', $texto);
    // Lista vacía donde se guardarán solo las líneas que no son comentarios
    $sinComentarios = [];
    // Revisa el archivo línea por línea
    foreach (preg_split('/\R/', $texto) as $linea) {
        // Si la línea empieza con -- es un comentario...
        if (str_starts_with(ltrim($linea), '--')) {
            // ...la salta, porque no es una orden
            continue;
        // Fin de la revisión de comentarios
        }
        // Guarda la línea, porque sí es parte de una orden
        $sinComentarios[] = $linea;
    // Fin de la revisión línea por línea
    }
    // Lista vacía donde se guardarán las órdenes completas
    $sentencias = [];
    // Une las líneas y las corta en cada punto y coma, que es donde termina una orden
    foreach (explode(';', implode("\n", $sinComentarios)) as $trozo) {
        // Quita los espacios y saltos de línea sobrantes
        $trozo = trim($trozo);
        // Si el trozo no está vacío...
        if ($trozo !== '') {
            // ...lo agrega a la lista de órdenes
            $sentencias[] = $trozo;
        }
    }
    // Entrega la lista de órdenes listas para ejecutar
    return $sentencias;
}


// TÍTULO: PREPARACIÓN DE LA PÁGINA

// Llave de seguridad del formulario

// Si todavía no se creó la llave de seguridad de este visitante...
if (empty($_SESSION['instalador_csrf'])) {
    // ...crea una llave larga y al azar, imposible de adivinar
    $_SESSION['instalador_csrf'] = bin2hex(random_bytes(32));
}
// Guarda la llave en una variable para usarla más abajo en el formulario
$csrf = $_SESSION['instalador_csrf'];


// Mensajes que se mostrarán al visitante

// Aquí se guardará el mensaje de error (por ahora vacío = no hay errores)
$error = '';
// Aquí se guardarán los datos de la instalación exitosa (por ahora vacío = aún no se instala)
$éxito = null;


// TÍTULO: BLOQUEO SI EL SISTEMA YA ESTÁ INSTALADO

// Si el sistema ya fue instalado antes...
if (yaInstalado()) {
    // ...le avisa al navegador que el acceso está prohibido
    http_response_code(403);
    ?>
<!doctype html>
<!-- Le dice al navegador que esta es una página web moderna -->
<html lang="es">
<!-- Inicio de la página, escrita en español -->

    <head>
        <!-- Parte invisible de la página: datos de configuración -->
        <meta charset="utf-8">
        <!-- Permite mostrar bien las tildes y la letra ñ -->
        <title>Instalador</title>
        <!-- Nombre que aparece en la pestaña del navegador -->
        <link rel="stylesheet" href="css/programa/instalar.css">
        <!-- Conecta esta página con el archivo de colores y tamaños -->
    </head>
    <!-- Fin de la parte invisible -->

    <!-- TÍTULO: CUERPO DE LA PÁGINA "YA INSTALADO" -->

    <body class="instalar-ya-instalado">
        <!-- Parte visible de la página (con su formato en instalar.css) -->

        <!-- título principal del aviso -->
        <h1>El sistema ya fue instalado</h1>

        <!-- explica por qué ya no se puede usar este instalador -->
        <p>Este instalador ya se ejecutó (o hay una configuración previa) y quedó desactivado por seguridad.</p>

        <!-- explica qué hacer si de verdad se necesita instalar todo de nuevo -->
        <p>Si necesitas reinstalar desde cero, elimina manualmente <code>backend/storage/instalado.lock</code> y
           <code>backend/config.local.php</code> desde el administrador de archivos de tu hosting, y luego
           vuelve a cargar esta página.</p>

        <!-- recuerda borrar este archivo del servidor para mayor seguridad -->
        <p><strong>Por seguridad, elimina este archivo (<code>instalar.php</code>) del servidor ahora.</strong></p>

    </body>
    
</html>

<?php
    // Detiene el programa aquí: no se muestra nada más
    exit;

}


// TÍTULO: PROCESO DE INSTALACIÓN (se activa cuando la persona presiona el botón "Instalar")

// Anota la dirección de internet de quien está usando el instalador (o una dirección vacía si no se sabe)
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// Solo si la persona envió el formulario (presionó el botón)...
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Paso 1: controles de seguridad

    // Si la persona ya hizo demasiados intentos...
    if (!limiteInstalador($ip)) {
        // ...le pide esperar unos minutos
        $error = 'Demasiados intentos. Espera unos minutos y vuelve a intentar.';
    // Si no viene la llave de seguridad, o no coincide con la original...
    } elseif (empty($_POST['csrf_token']) || !hash_equals($csrf, (string)$_POST['csrf_token'])) {
        // ...le pide recargar la página
        $error = 'La sesión del formulario expiró. Recarga la página e inténtalo de nuevo.';
    // Si pasó los controles, se sigue con la instalación
    } else {

        // Paso 2: leer los datos que la persona escribió en el formulario

        // Datos de la base de datos
        // Dirección del servidor de la base de datos (sin espacios sobrantes)
        $dbHost = trim((string)($_POST['db_host'] ?? ''));
        // Número de puerta de entrada de la base de datos (si no lo escribe, usa 3306)
        $dbPort = trim((string)($_POST['db_port'] ?? '3306'));
        // Usuario de la base de datos
        $dbUser = trim((string)($_POST['db_user'] ?? ''));
        // Contraseña de la base de datos (no se le quitan espacios porque podrían ser parte de la clave)
        $dbPass = (string)($_POST['db_pass'] ?? '');
        // Nombre de la base de datos
        $dbName = trim((string)($_POST['db_name'] ?? ''));

        // Datos del sitio
        // Nombre comercial del sistema (si no lo escribe, usa EMEX)
        $appName = trim((string)($_POST['app_name'] ?? 'EMEX'));
        // Dirección web donde quedará instalado el sistema
        $appBaseUrl = trim((string)($_POST['app_base_url'] ?? ''));

        // Datos del primer administrador
        // Nombre de usuario del administrador
        $adminUser = trim((string)($_POST['admin_user'] ?? ''));
        // Correo del administrador
        $adminEmail = trim((string)($_POST['admin_email'] ?? ''));
        // Contraseña del administrador
        $adminPass = (string)($_POST['admin_pass'] ?? '');
        // Contraseña del administrador escrita por segunda vez, para confirmar
        $adminPassConfirm = (string)($_POST['admin_pass_confirm'] ?? '');

        // Datos del correo (SMTP)
        // Servidor que enviará los correos
        $smtpHost = trim((string)($_POST['smtp_host'] ?? ''));
        // Puerta de entrada de ese servidor (si no la escribe, usa 587)
        $smtpPort = trim((string)($_POST['smtp_port'] ?? '587'));
        // Usuario de la cuenta de correo
        $smtpUser = trim((string)($_POST['smtp_user'] ?? ''));
        // Contraseña de la cuenta de correo
        $smtpPass = (string)($_POST['smtp_pass'] ?? '');
        // Tipo de protección del envío (si no elige, usa TLS)
        $smtpSecure = (string)($_POST['smtp_secure'] ?? 'tls');
        // Correo desde el cual se enviarán los mensajes
        $smtpFromEmail = trim((string)($_POST['smtp_from_email'] ?? ''));


        // Paso 3: revisar que los datos estén correctos

        // Primera revisión: los datos de la base de datos
        if (
            // Si falta el servidor, el usuario o el nombre de la base
            $dbHost === '' || $dbUser === '' || $dbName === '' ||
            // o el nombre tiene letras o símbolos no permitidos
            !preg_match('/^[A-Za-z0-9_]{1,64}$/', $dbName) ||
            // o el puerto no es un número de hasta 5 cifras
            !preg_match('/^\d{1,5}$/', $dbPort)
        // entonces hay un problema
        ) {
            // Explica qué revisar
            $error = 'Revisa los datos de conexión a la base de datos (host, usuario, puerto y nombre de base son obligatorios; el nombre solo admite letras, números y guion bajo).';
        // Segunda revisión: si el usuario administrador no cumple el formato permitido
        } elseif (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $adminUser)) {
            // Explica cómo debe ser
            $error = 'El usuario administrador debe tener entre 3 y 50 caracteres (letras, números, punto, guion o guion bajo).';
        // Tercera revisión: si escribió un correo pero no tiene forma de correo
        } elseif ($adminEmail !== '' && !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            // Avisa que el correo no sirve
            $error = 'El correo del administrador no es válido.';
        // Cuarta revisión: si la contraseña no es lo bastante fuerte
        } elseif (!validPasswordInstalador($adminPass)) {
            // Explica las reglas
            $error = 'La contraseña del administrador debe tener mínimo 12 caracteres, con mayúscula, minúscula, número y símbolo.';
        // Quinta revisión: si las dos contraseñas escritas no son iguales
        } elseif ($adminPass !== $adminPassConfirm) {
            // Avisa que no coinciden
            $error = 'La confirmación de contraseña no coincide.';
        // Sexta revisión: si escribió una dirección web pero no tiene forma de dirección
        } elseif ($appBaseUrl !== '' && !filter_var($appBaseUrl, FILTER_VALIDATE_URL)) {
            // Avisa y da un ejemplo
            $error = 'La URL base de la aplicación no es válida (ejemplo: https://tudominio.com/control_combustible).';
        // Si todos los datos están bien, se empieza a instalar de verdad
        } else {

            // Se intenta hacer todo lo siguiente; si algo falla, se captura el problema más abajo
            try {

                // Paso 4: conectarse a la base de datos

                // Pide que cualquier falla de la base de datos se avise de inmediato
                mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
                // Prepara la conexión con la base de datos
                $conn = mysqli_init();
                // Si no responde en 8 segundos, se da por fallida
                $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 8);
                // Se conecta con el servidor usando el host, usuario, contraseña y puerto indicados
                $conn->real_connect($dbHost, $dbUser, $dbPass, '', (int)$dbPort);
                // Indica que se guardarán bien las tildes, la ñ y hasta los emojis
                $conn->set_charset('utf8mb4');

                // Se intenta crear la base de datos
                try {
                    // Crea la base de datos solo si todavía no existe
                    $conn->query("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                // Si no se pudo crear (por ejemplo, falta de permisos)...
                } catch (Throwable $ignorado) {
                // ...no pasa nada: se ignora y se sigue, porque la base podría estar ya creada por el hosting
                }
                // Entra a trabajar dentro de esa base de datos
                $conn->select_db($dbName);
                // Activa reglas estrictas para que la base no acepte datos dudosos
                $conn->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");


                // Paso 5: crear las tablas (las "hojas de Excel" donde se guarda la información)

                // Crear todas las tablas leyendo el archivo SQL que está en la carpeta base_dato
                // Toma una por una las órdenes escritas en el archivo SQL (usuarios, operador_cargador, operador, recuperacion_password y auditoria)
                foreach (leerSentenciasSql(SQL_ESTRUCTURA) as $sentencia) {
                    // Ejecuta la orden: la tabla queda creada (o se salta si ya existía)
                    $conn->query($sentencia);
                // Fin de la creación de tablas
                }

                // --- Revisión extra: asegurarse de que la tabla usuarios tenga la columna RUT ---
                // Pregunta a la base si la tabla usuarios ya tiene la columna "rut"
                $columnaRut = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'rut'");
                // Si la pregunta se pudo hacer y la columna no existe...
                if ($columnaRut && $columnaRut->num_rows === 0) {
                    // ...la agrega (esto arregla instalaciones antiguas)
                    $conn->query("ALTER TABLE usuarios ADD COLUMN rut VARCHAR(12) DEFAULT NULL");
                // Fin de la revisión de la columna
                }
                // Pregunta a la base si ya existe la regla de "RUT no repetido"
                $indiceRut = $conn->query("SHOW INDEX FROM usuarios WHERE Key_name = 'uq_usuarios_rut'");
                // Si la pregunta se pudo hacer y la regla no existe...
                if ($indiceRut && $indiceRut->num_rows === 0) {
                    // ...la agrega
                    $conn->query("ALTER TABLE usuarios ADD UNIQUE KEY uq_usuarios_rut (rut)");
                // Fin de la revisión de la regla
                }


                // Paso 6: crear al primer administrador

                // Cuenta cuántos administradores ya existen en la base de datos
                $existente = $conn->query("SELECT COUNT(*) c FROM usuarios WHERE rol='admin'")->fetch_assoc();
                // Si ya existe al menos uno...
                if ((int)$existente['c'] > 0) {
                    // ...avisa que no se puede instalar de nuevo
                    $error = 'Ya existe al menos un administrador en esta base de datos. Este instalador solo se usa para la instalación inicial.';
                // Si todavía no hay ningún administrador, se crea el primero
                } else {
                    // Convierte la contraseña en un código secreto: así nadie puede leer la contraseña real
                    $hash = password_hash($adminPass, PASSWORD_DEFAULT);
                    // Define que el nuevo usuario será administrador
                    $rol = 'admin';
                    // Define que el nuevo usuario estará activo
                    $estado = 'activo';
                    // Usa el correo escrito, o lo deja vacío si no escribió ninguno
                    $emailParam = $adminEmail !== '' ? $adminEmail : null;
                    // Prepara la orden para guardar al nuevo usuario (los signos ? se rellenan después, de forma segura)
                    $stmt = $conn->prepare('INSERT INTO usuarios (username, password, email, rol, estado, debe_cambiar_password) VALUES (?,?,?,?,?,0)');
                    // Rellena los espacios con: usuario, contraseña protegida, correo, tipo y estado
                    $stmt->bind_param('sssss', $adminUser, $hash, $emailParam, $rol, $estado);
                    // Ejecuta la orden: el administrador queda guardado
                    $stmt->execute();
                    // Anota el número de identificación que recibió el nuevo administrador
                    $adminId = (int)$stmt->insert_id;


                    // Paso 7: dejar constancia en el diario (auditoría)

                    // Se intenta anotar en el diario
                    try {
                        // Nombre de la acción que se anota
                        $accion = 'CREAR_PRIMER_ADMIN_INSTALADOR_WEB';
                        // Tabla donde ocurrió el cambio
                        $tabla = 'usuarios';
                        // Explicación: con qué nombre de usuario se creó
                        $detalle = 'username=' . $adminUser;
                        // Datos del navegador usado (recortados a 500 letras como máximo)
                        $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
                        // Prepara la orden para guardar la anotación en el diario
                        $auditStmt = $conn->prepare('INSERT INTO auditoria (usuario_id, accion, tabla_afectada, registro_id, detalle, ip, user_agent) VALUES (?,?,?,?,?,?,?)');
                        // Rellena los espacios con todos los datos de la anotación
                        $auditStmt->bind_param('ississs', $adminId, $accion, $tabla, $adminId, $detalle, $ip, $ua);
                        // Ejecuta la orden: la anotación queda guardada
                        $auditStmt->execute();
                    // Si no se pudo anotar en el diario...
                    } catch (Throwable $ignorado) {
                    // ...no pasa nada: se ignora, porque lo importante es que el administrador ya quedó creado
                    }


                    // Paso 8: crear el archivo de configuración

                    // Prepara una lista vacía donde se irán escribiendo las líneas del archivo
                    $lineas = [];
                    // Primera línea: indica que el archivo está escrito en PHP
                    $lineas[] = '<?php';
                    // Segunda línea: pide que PHP sea estricto
                    $lineas[] = 'declare(strict_types=1);';
                    // Tercera línea: un aviso para quien abra el archivo
                    $lineas[] = '/* Generado por instalar.php — no editar a mano si vas a reinstalar. */';
                    // Lista de todos los datos de configuración que se guardarán
                    $vars = [
                        // Tipo de ambiente: producción (el sistema ya en uso real)
                        'APP_ENV' => 'production',
                        // Nombre del sistema (EMEX si no escribió ninguno)
                        'APP_NAME' => $appName !== '' ? $appName : 'EMEX',
                        // Dirección web del sistema
                        'APP_BASE_URL' => $appBaseUrl,
                        // Servidor de la base de datos (con su puerto solo si no es el 3306 habitual)
                        'DB_HOST' => $dbHost . ($dbPort !== '3306' ? ':' . $dbPort : ''),
                        // Usuario de la base de datos
                        'DB_USER' => $dbUser,
                        // Contraseña de la base de datos
                        'DB_PASS' => $dbPass,
                        // Nombre de la base de datos
                        'DB_NAME' => $dbName,
                        // Servidor que envía los correos
                        'SMTP_HOST' => $smtpHost,
                        // Puerta de entrada del servidor de correos
                        'SMTP_PORT' => $smtpPort,
                        // Usuario de la cuenta de correo
                        'SMTP_USER' => $smtpUser,
                        // Contraseña de la cuenta de correo
                        'SMTP_PASS' => $smtpPass,
                        // Tipo de protección del envío de correos
                        'SMTP_SECURE' => $smtpSecure,
                        // Correo desde el cual se envían los mensajes
                        'SMTP_FROM_EMAIL' => $smtpFromEmail,
                        // Nombre que verá quien reciba los correos (EMEX si no escribió ninguno)
                        'SMTP_FROM_NAME' => $appName !== '' ? $appName : 'EMEX',
                    // Fin de la lista de datos
                    ];
                    // Revisa uno por uno los datos de la lista (k = nombre del dato, v = su valor)
                    foreach ($vars as $k => $v) {
                        // Si el dato está vacío...
                        if ($v === '') {
                            // ...lo salta y pasa al siguiente
                            continue;
                        // Fin de la revisión de vacío
                        }
                        // Escribe una línea que dice: "si este dato no fue definido en otro lugar, defínelo con este valor"
                        $lineas[] = 'if (getenv(' . var_export($k, true) . ') === false) { putenv(' . var_export($k, true) . ' . \'=\' . ' . var_export($v, true) . '); }';
                    // Fin de la revisión uno por uno
                    }
                    // Une todas las líneas en un solo texto, una debajo de otra
                    $contenido = implode("\n", $lineas) . "\n";

                    // Intenta guardar el archivo de configuración; si no puede...
                    if (@file_put_contents(CONFIG_FILE, $contenido) === false) {
                        // ...explica el problema y qué hacer
                        $error = 'El administrador se creó, pero no fue posible escribir backend/config.local.php (revisa permisos de escritura en la carpeta backend/). Configura las variables de entorno manualmente según CONFIGURACION_SEGURA_PRODUCCION.txt.';
                    // Si el archivo se guardó bien...
                    } else {
                        // Restringe quién puede leer ese archivo (porque tiene claves)
                        @chmod(CONFIG_FILE, 0640);
                        // Crea el "candado" con la fecha y hora de la instalación, para bloquear futuras instalaciones
                        @file_put_contents(LOCK_FILE, 'Instalado: ' . date('Y-m-d H:i:s') . "\n");
                        // Borra la llave de seguridad del formulario, que ya no se necesita
                        unset($_SESSION['instalador_csrf']);
                        // Anota que todo salió bien, junto con el nombre del administrador creado
                        $éxito = ['username' => $adminUser];
                    // Fin del guardado del archivo de configuración
                    }
                // Fin de la creación del administrador
                }

            // Si algo falló en cualquier punto de la instalación...
            } catch (Throwable $e) {
                // ...muestra el motivo del problema
                $error = 'No fue posible conectar o preparar la base de datos: ' . $e->getMessage();
            // Fin del manejo de fallas
            }
        }
    }

}

?>
<!doctype html>
<!-- Le dice al navegador que esta es una página web moderna -->
<html lang="es">
<!-- Inicio de la página, escrita en español -->

    <!-- TÍTULO: PARTE INVISIBLE DE LA PÁGINA (configuración) -->

    <head>
        <!-- Datos de configuración que el visitante no ve -->
        <meta charset="utf-8">
        <!-- Permite mostrar bien las tildes y la letra ñ -->
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <!-- Hace que la página se vea bien en celulares y tablets -->
        <title>Instalación — Control de Combustible</title>
        <!-- Nombre que aparece en la pestaña del navegador -->
        <link rel="stylesheet" href="css/programa/instalar.css">
        <!-- Conecta esta página con el archivo de colores y tamaños -->
    </head>
    <!-- Fin de la parte invisible -->

    <!-- TÍTULO: CUERPO PRINCIPAL DE LA PÁGINA -->

    <body class="instalar-page">
        <!-- Parte visible de la página (con su formato en instalar.css) -->

        <!-- caja blanca que contiene todo el contenido -->
        <div class="card">

        <?php if ($éxito): ?>
        <!-- Si la instalación salió bien, se muestra el mensaje final (en lugar del formulario) -->

        <!-- TÍTULO: MENSAJE DE INSTALACIÓN COMPLETADA -->

            <!-- título principal del mensaje final -->
            <h1>✅ Instalación completada</h1>

            <!-- recuadro verde con el resultado -->
            <div class="ok">
                <!-- cuenta qué se creó y dónde quedó guardada la configuración -->
                <p>Se creó el usuario administrador <strong><?= e($éxito['username']) ?></strong> y la configuración quedó
                guardada en <code>backend/config.local.php</code>.</p>
                <!-- invita a entrar al sistema con el enlace de acceso -->
                <p>Ya puedes <a href="index.php">iniciar sesión</a> con el usuario y la contraseña que definiste en este formulario.</p>
            </div>

            <!-- recuadro amarillo con una advertencia importante -->
            <div class="aviso">
                <!-- pide borrar este archivo del servidor para mayor seguridad -->
                <strong>Importante — hazlo ahora:</strong> elimina <code>instalar.php</code> del servidor (o al menos
                renómbralo o restringe su acceso con <code>.htaccess</code>). Este instalador ya quedó bloqueado por el
                archivo <code>backend/storage/instalado.lock</code>, pero mientras el archivo siga publicado sigue siendo
                una superficie innecesaria.
            </div>

        <?php else: ?>
        <!-- Si todavía no se ha instalado, se muestra el formulario -->

            <!-- TÍTULO: ENCABEZADO DEL FORMULARIO -->

            <!-- título principal de la página -->
            <h1>Instalación — Control de Combustible</h1>

            <!-- frase que explica cuándo y para qué se usa este formulario -->
            <p class="instalar-intro">
                Usa este formulario solo si tu hosting no te permite ejecutar comandos por SSH/terminal.
                Necesitas las credenciales de una base de datos MySQL ya creada (te las entrega tu panel de hosting).
            </p>

            <!-- si hay un mensaje de error, se muestra aquí en un recuadro rojo -->
            <?php if ($error !== ''): ?><div class="error"><?= e($error) ?></div><?php endif; ?>

            <!-- TÍTULO: FORMULARIO DE INSTALACIÓN -->

            <!-- formulario que se envía al presionar el botón "Instalar" (sin autocompletar datos del navegador) -->
            <form method="POST" autocomplete="off">
                <!-- dato oculto con la llave de seguridad del formulario -->
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">


                <!-- TÍTULO: DATOS DE LA BASE DE DATOS -->

                <!-- título del grupo de datos de la base de datos -->
                <h2>Base de datos MySQL</h2>
                <!-- fila con dos casillas lado a lado -->
                <div class="fila">
                    <!-- casilla para el servidor de la base de datos (si ya se escribió antes, se mantiene; si no, aparece "localhost") -->
                    <div><label>Host</label><input name="db_host" value="<?= e($_POST['db_host'] ?? 'localhost') ?>" required></div>
                    <!-- casilla para la puerta de entrada de la base de datos (si ya se escribió antes, se mantiene; si no, aparece 3306) -->
                    <div><label>Puerto</label><input name="db_port" value="<?= e($_POST['db_port'] ?? '3306') ?>" required></div>
                </div>
                <!-- nombre de la casilla del usuario -->
                <label>Usuario de la base de datos</label>
                <!-- casilla para el usuario de la base de datos (obligatoria) -->
                <input name="db_user" value="<?= e($_POST['db_user'] ?? '') ?>" required>
                <!-- nombre de la casilla de la contraseña -->
                <label>Contraseña de la base de datos</label>
                <!-- casilla para la contraseña de la base de datos (las letras aparecen ocultas) -->
                <input type="password" name="db_pass" autocomplete="new-password">
                <!-- nombre de la casilla del nombre de la base -->
                <label>Nombre de la base de datos</label>
                <!-- casilla para el nombre de la base de datos (obligatoria) -->
                <input name="db_name" value="<?= e($_POST['db_name'] ?? '') ?>" required>
                <!-- texto de ayuda con las reglas del nombre -->
                <small>Solo letras, números y guion bajo. Si no existe y tu usuario tiene privilegios, se crea automáticamente.</small>


                <!-- TÍTULO: DATOS DEL SITIO -->

                <!-- título del grupo de datos del sitio -->
                <h2>Datos del sitio</h2>
                <!-- nombre de la casilla del nombre comercial -->
                <label>Nombre comercial (se muestra en el sistema)</label>
                <!-- casilla para el nombre comercial (si ya se escribió antes, se mantiene; si no, aparece "EMEX") -->
                <input name="app_name" value="<?= e($_POST['app_name'] ?? 'EMEX') ?>">
                <!-- nombre de la casilla de la dirección web -->
                <label>URL base del sitio</label>
                <!-- casilla para la dirección web (con un ejemplo de cómo escribirla) -->
                <input name="app_base_url" placeholder="https://tudominio.com/control_combustible" value="<?= e($_POST['app_base_url'] ?? '') ?>">
                <!-- texto de ayuda: cuándo se puede dejar vacía -->
                <small>Déjalo vacío si el sistema queda instalado en la raíz del dominio.</small>


                <!-- TÍTULO: DATOS DEL PRIMER ADMINISTRADOR -->

                <!-- título del grupo de datos del administrador -->
                <h2>Primer administrador</h2>
                <!-- nombre de la casilla del usuario -->
                <label>Usuario</label>
                <!-- casilla para el usuario administrador (obligatoria; solo acepta el formato permitido) -->
                <input name="admin_user" value="<?= e($_POST['admin_user'] ?? '') ?>" required pattern="[A-Za-z0-9._-]{3,50}">
                <!-- nombre de la casilla del correo -->
                <label>Correo (opcional, para recuperar contraseña)</label>
                <!-- casilla para el correo del administrador (el navegador revisa que parezca un correo) -->
                <input type="email" name="admin_email" value="<?= e($_POST['admin_email'] ?? '') ?>">
                <!-- nombre de la casilla de la contraseña -->
                <label>Contraseña</label>
                <!-- casilla para la contraseña del administrador (obligatoria, mínimo 12 letras, aparece oculta) -->
                <input type="password" name="admin_pass" autocomplete="new-password" required minlength="12">
                <!-- texto de ayuda con las reglas de la contraseña -->
                <small>Mínimo 12 caracteres, con mayúscula, minúscula, número y símbolo.</small>
                <!-- nombre de la casilla de confirmación -->
                <label>Confirmar contraseña</label>
                <!-- casilla para repetir la contraseña (obligatoria, mínimo 12 letras, aparece oculta) -->
                <input type="password" name="admin_pass_confirm" autocomplete="new-password" required minlength="12">


                <!-- TÍTULO: DATOS DEL CORREO (SMTP) -->

                <!-- título del grupo de datos del correo -->
                <h2>Correo SMTP (opcional, recomendado)</h2>
                <!-- nota que explica qué pasa si se deja vacío -->
                <p class="instalar-nota-smtp">Si lo dejas vacío, el sistema intentará usar la
                    función <code>mail()</code> del servidor, que en muchos hostings no entrega los correos de forma confiable.</p>
                <!-- fila con dos casillas lado a lado -->
                <div class="fila">
                    <!-- casilla para el servidor de correos -->
                    <div><label>Servidor SMTP</label><input name="smtp_host" value="<?= e($_POST['smtp_host'] ?? '') ?>"></div>
                    <!-- casilla para la puerta de entrada del servidor de correos (si no se escribe, aparece 587) -->
                    <div><label>Puerto</label><input name="smtp_port" value="<?= e($_POST['smtp_port'] ?? '587') ?>"></div>
                </div>
                <!-- nombre de la casilla del usuario de correo -->
                <label>Usuario SMTP</label>
                <!-- casilla para el usuario de la cuenta de correo -->
                <input name="smtp_user" value="<?= e($_POST['smtp_user'] ?? '') ?>">
                <!-- nombre de la casilla de la contraseña del correo -->
                <label>Contraseña SMTP</label>
                <!-- casilla para la contraseña de la cuenta de correo (aparece oculta) -->
                <input type="password" name="smtp_pass" autocomplete="new-password">
                <!-- nombre de la lista de protección -->
                <label>Cifrado</label>
                <!-- lista para elegir el tipo de protección del envío de correos -->
                <select name="smtp_secure">
                    <!-- opción TLS (recomendada); queda elegida si es la que ya estaba seleccionada o si no se eligió nada -->
                    <option value="tls" <?= (($_POST['smtp_secure'] ?? 'tls') === 'tls') ? 'selected' : '' ?>>TLS (puerto 587, recomendado)</option>
                    <!-- opción SSL; queda elegida si fue la que se seleccionó antes -->
                    <option value="ssl" <?= (($_POST['smtp_secure'] ?? '') === 'ssl') ? 'selected' : '' ?>>SSL (puerto 465)</option>
                </select>
                <!-- nombre de la casilla del correo remitente -->
                <label>Correo remitente</label>
                <!-- casilla para el correo desde el cual se enviarán los mensajes (con un ejemplo de cómo escribirlo) -->
                <input type="email" name="smtp_from_email" placeholder="no-reply@tudominio.com" value="<?= e($_POST['smtp_from_email'] ?? '') ?>">


                <!-- TÍTULO: BOTÓN PARA INSTALAR -->

                <!-- botón que envía el formulario e inicia la instalación -->
                <button type="submit">Instalar</button>
            </form>

        <?php endif; ?>

        </div>
        
    </body>

</html>



<!-- -------------------------------------------------------------------------------------------------------------
   -------------------------------------- FIN Jerez instalar.php --------------------------------------
   ------------------------------------------------------------------------------------------------------------- -->

<!--
Sitio Web Creado por Jerez.
Direccion: Miraflorez #1280
Quintero - Chile
jaime.jerez1978@gmail.com
https://www.
Creado, Programado y Diseñado por Jerez.
JJA
-->
