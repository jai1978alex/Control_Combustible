<?php
declare(strict_types=1);
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/security.php';
requirePost();

$username = trim((string)($_POST['username'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$csrf = (string)($_POST['csrf_token'] ?? '');

if (!verifyLoginCsrf($csrf)) {
    header('Location: ' . appBasePath() . '/index.php?error=1');
    exit;
}

/* El token de acceso inicial es de un solo uso. */
unset($_SESSION['login_csrf']);

if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username) || strlen($password) < 1 || strlen($password) > 255) {
    header('Location: ' . appBasePath() . '/index.php?error=1'); exit;
}

$ip = clientIp();
/* Rate limit real: IP y usuario, independiente de la sesión. */
if (!rateLimitFile('login-ip', $ip, LOGIN_WINDOW, LOGIN_MAX_ATTEMPTS, false) ||
    !rateLimitFile('login-user', strtolower($username), LOGIN_WINDOW, LOGIN_MAX_ATTEMPTS, false)) {
    header('Location: ' . appBasePath() . '/index.php?error=bloqueo'); exit;
}

$stmt = $conn->prepare('SELECT id, username, password, rol, estado, updated_at, debe_cambiar_password FROM usuarios WHERE username = ? LIMIT 1');
$stmt->bind_param('s', $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || $user['estado'] !== 'activo' || !password_verify($password, $user['password'])) {
    rateLimitFile('login-ip', $ip, LOGIN_WINDOW, LOGIN_MAX_ATTEMPTS, true);
    rateLimitFile('login-user', strtolower($username), LOGIN_WINDOW, LOGIN_MAX_ATTEMPTS, true);
    audit($conn, $user ? (int)$user['id'] : null, 'LOGIN_FALLIDO', 'usuarios', $user ? (int)$user['id'] : null, 'Usuario: '.$username);
    header('Location: ' . appBasePath() . '/index.php?error=1'); exit;
}

if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    $u = $conn->prepare('UPDATE usuarios SET password=? WHERE id=?');
    $uid=(int)$user['id']; $u->bind_param('si',$newHash,$uid); $u->execute();
    $user['updated_at'] = date('Y-m-d H:i:s');
}

session_regenerate_id(true);
$_SESSION['user'] = true;
$_SESSION['usuario_id'] = (int)$user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['rol'] = $user['rol'];
$_SESSION['user_updated_at'] = (string)$user['updated_at'];
$_SESSION['last_activity'] = time();
$_SESSION['binding'] = hash('sha256', (string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$_SESSION['login_csrf'] = bin2hex(random_bytes(32));
$_SESSION['debe_cambiar_password'] = (int)$user['debe_cambiar_password'];

audit($conn, (int)$user['id'], 'LOGIN_EXITOSO', 'usuarios', (int)$user['id']);

/* Si el admin reseteó esta contraseña, se exige definir una nueva antes de continuar. */
if (!empty($_SESSION['debe_cambiar_password'])) {
    header('Location: ' . appBasePath() . '/html/programa/cambiar_password.php');
    exit;
}

header('Location: ' . appBasePath() . ($user['rol'] === 'admin'
    ? '/html/login/seguridad_login/superadmin.php'
    : '/html/programa/panel.php'));
exit;
