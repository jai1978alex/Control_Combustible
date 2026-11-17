<?php
declare(strict_types=1);
require_once __DIR__.'/conexion.php';
require_once __DIR__.'/security.php';
require_once __DIR__.'/mailer.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message'=>'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$email = trim((string)($data['email'] ?? ''));
$generic = 'Si el correo está registrado, recibirás instrucciones para restablecer tu contraseña.';

/* Evita abuso del formulario de recuperación. */
if (!rateLimitFile('recovery-ip', clientIp(), RECOVERY_WINDOW, RECOVERY_MAX_ATTEMPTS, false) ||
    ($email !== '' && !rateLimitFile('recovery-email', strtolower($email), RECOVERY_WINDOW, RECOVERY_MAX_ATTEMPTS, false))) {
    echo json_encode(['message'=>$generic]);
    exit;
}
rateLimitFile('recovery-ip', clientIp(), RECOVERY_WINDOW, RECOVERY_MAX_ATTEMPTS, true);
if ($email !== '') {
    rateLimitFile('recovery-email', strtolower($email), RECOVERY_WINDOW, RECOVERY_MAX_ATTEMPTS, true);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['message'=>$generic]);
    exit;
}

$stmt = $conn->prepare("SELECT id,username FROM usuarios WHERE email=? AND estado='activo' LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();

if ($u) {
    $conn->query('DELETE FROM recuperacion_password WHERE expira_en < NOW() OR usado=1');

    /* Un token nuevo invalida los anteriores del mismo usuario. */
    $uid = (int)$u['id'];
    $old = $conn->prepare('UPDATE recuperacion_password SET usado=1 WHERE usuario_id=? AND usado=0');
    $old->bind_param('i', $uid);
    $old->execute();

    $raw = bin2hex(random_bytes(32));
    $hash = hash('sha256', $raw);
    $exp = (new DateTimeImmutable('+30 minutes'))->format('Y-m-d H:i:s');

    $ins = $conn->prepare('INSERT INTO recuperacion_password(usuario_id,token_hash,expira_en) VALUES(?,?,?)');
    $ins->bind_param('iss', $uid, $hash, $exp);
    $ins->execute();

    $baseUrl = getenv('APP_BASE_URL');
    if ($baseUrl === false || $baseUrl === '') {
        $baseUrl = 'http://localhost/control_combustible';
    }
    $baseUrl = rtrim($baseUrl, '/');
    $url = $baseUrl . '/html/programa/restablecer.html?token=' . rawurlencode($raw);

    $subject = 'Restablecer contraseña - Control de Combustible';
    $body = "Hola {$u['username']},\n\nSolicitaste restablecer tu contraseña. El enlace expira en 30 minutos:\n$url\n\nSi no realizaste esta solicitud, ignora este mensaje.";

    /*
     * El resultado del envío no se expone al usuario (evita filtrar si el
     * correo existe), pero queda registrado en el log del servidor y en la
     * auditoría para que un administrador pueda detectar fallos de entrega.
     */
    $enviado = sendAppMail($email, $subject, $body);

    audit($conn, $uid, 'SOLICITUD_RECUPERACION', 'usuarios', $uid, $enviado ? null : 'Fallo el envio del correo de recuperacion');
}

echo json_encode(['message'=>$generic]);
