<?php
declare(strict_types=1);

/*
 * Envío de correo centralizado.
 *
 * Antes: recuperar.php llamaba a @mail(...) sin SMTP configurado. En muchos
 * hostings esto falla en silencio (el @ oculta el error) y el usuario nunca
 * recibe el enlace de recuperación.
 *
 * Ahora: se usa PHPMailer con SMTP real cuando hay credenciales configuradas
 * (variables de entorno SMTP_*). Si no hay SMTP configurado, se usa mail()
 * nativo como último recurso, pero SIN silenciar errores: cualquier fallo
 * queda registrado con error_log() para que sea visible en producción.
 *
 * Variables de entorno esperadas para SMTP:
 *   SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS
 *   SMTP_SECURE      -> "tls" (STARTTLS, por defecto) o "ssl"
 *   SMTP_FROM_EMAIL  -> remitente (por defecto no-reply@<host>)
 *   SMTP_FROM_NAME   -> nombre visible del remitente
 */

require_once __DIR__ . '/libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/libs/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Envía un correo de texto plano. Devuelve true si el mensaje fue aceptado
 * para su entrega (no garantiza que llegue a destino, eso depende del
 * proveedor SMTP), false si hubo un error. Los errores siempre quedan
 * registrados con error_log(), nunca se ocultan con @.
 */
function sendAppMail(string $to, string $subject, string $body): bool {
    $smtpHost = (string)(getenv('SMTP_HOST') ?: '');

    $fromEmail = (string)(getenv('SMTP_FROM_EMAIL') ?: 'no-reply@' . (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $fromName = (string)(getenv('SMTP_FROM_NAME') ?: 'Control de Combustible');

    if ($smtpHost !== '') {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->Port = (int)(getenv('SMTP_PORT') ?: 587);
            $mail->SMTPAuth = true;
            $mail->Username = (string)(getenv('SMTP_USER') ?: '');
            $mail->Password = (string)(getenv('SMTP_PASS') ?: '');
            $secure = strtolower((string)(getenv('SMTP_SECURE') ?: 'tls'));
            $mail->SMTPSecure = $secure === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body = $body;

            $mail->send();
            return true;
        } catch (PHPMailerException $e) {
            error_log('Error enviando correo vía SMTP: ' . $mail->ErrorInfo);
            return false;
        } catch (Throwable $e) {
            error_log('Error enviando correo vía SMTP: ' . $e->getMessage());
            return false;
        }
    }

    /*
     * Sin SMTP configurado: se intenta mail() nativo como último recurso,
     * pero se deja constancia clara en el log para que no pase inadvertido
     * en producción (a diferencia del antiguo @mail()).
     */
    error_log('SMTP_HOST no configurado: usando mail() nativo de PHP para enviar a ' . $to . '. Configure SMTP_HOST/SMTP_USER/SMTP_PASS para envíos confiables en producción.');

    $headers = "From: {$fromName} <{$fromEmail}>\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    $enviado = mail($to, $subject, $body, $headers);

    if (!$enviado) {
        error_log('mail() nativo no pudo enviar el correo a ' . $to . '. Revise la configuración del servidor de correo.');
    }

    return $enviado;
}
