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
   ------------------------------------- INICIO Jerez mailer.php ----------------------------------------
   ------------------------------------------------------------------------------------------------------------- */


// TÍTULO: INICIO DEL PROGRAMA

// Le pide a PHP que sea estricto y no acepte datos mal escritos o mezclados
declare(strict_types=1);


// TÍTULO: TRAER LAS HERRAMIENTAS PARA ENVIAR CORREOS

// Trae la pieza de PHPMailer que maneja los errores de envío
require_once __DIR__ . '/libs/PHPMailer/src/Exception.php';
// Trae la pieza principal de PHPMailer, la que arma y envía el correo
require_once __DIR__ . '/libs/PHPMailer/src/PHPMailer.php';
// Trae la pieza de PHPMailer que se conecta al servidor de correo
require_once __DIR__ . '/libs/PHPMailer/src/SMTP.php';

// Deja listo el nombre corto "PHPMailer" para usarlo más abajo
use PHPMailer\PHPMailer\PHPMailer;
// Deja listo el nombre corto "PHPMailerException" para atrapar los errores de envío
use PHPMailer\PHPMailer\Exception as PHPMailerException;


// TÍTULO: FUNCIÓN PARA ENVIAR UN CORREO

/**
 * Envía un correo de texto plano. Devuelve true si el mensaje fue aceptado
 * para su entrega (no garantiza que llegue a destino, eso depende del
 * proveedor SMTP), false si hubo un error. Los errores siempre quedan
 * registrados con error_log(), nunca se ocultan con @.
 */

// Recibe a quién va el correo, el asunto y el mensaje; responde "sí" si se pudo enviar y "no" si falló
function sendAppMail(string $to, string $subject, string $body): bool {

    // Lee el nombre del servidor de correo configurado (vacío si no hay ninguno)
    $smtpHost = (string)(getenv('SMTP_HOST') ?: '');

    // Define el correo desde el cual se envía; si no hay uno configurado, inventa uno con el nombre de la página
    $fromEmail = (string)(getenv('SMTP_FROM_EMAIL') ?: 'no-reply@' . (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
    // Define el nombre que verá quien recibe el correo; si no hay uno configurado, usa "Control de Combustible"
    $fromName = (string)(getenv('SMTP_FROM_NAME') ?: 'Control de Combustible');

    // Si hay un servidor de correo configurado...
    if ($smtpHost !== '') {
        // ...prepara el enviador de correos (con el modo que avisa si algo sale mal)
        $mail = new PHPMailer(true);
        // Intenta enviar el correo
        try {
            // Le indica que el correo se enviará por un servidor de correo (SMTP)
            $mail->isSMTP();
            // Le indica la dirección del servidor de correo
            $mail->Host = $smtpHost;
            // Le indica la "puerta" del servidor por donde entrar (587 si no se configuró otra)
            $mail->Port = (int)(getenv('SMTP_PORT') ?: 587);
            // Le indica que el servidor pide usuario y clave
            $mail->SMTPAuth = true;
            // Le entrega el usuario del servidor de correo
            $mail->Username = (string)(getenv('SMTP_USER') ?: '');
            // Le entrega la clave del servidor de correo
            $mail->Password = (string)(getenv('SMTP_PASS') ?: '');
            // Lee qué tipo de protección usa la conexión (por defecto "tls") y lo pasa a minúsculas
            $secure = strtolower((string)(getenv('SMTP_SECURE') ?: 'tls'));
            // Elige la protección de la conexión según lo leído: "ssl" o la opción normal
            $mail->SMTPSecure = $secure === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            // Indica que el correo acepta tildes y letras especiales
            $mail->CharSet = 'UTF-8';

            // Anota quién envía el correo (correo y nombre)
            $mail->setFrom($fromEmail, $fromName);
            // Anota a quién va dirigido el correo
            $mail->addAddress($to);
            // Indica que el correo es texto simple, sin diseño
            $mail->isHTML(false);
            // Escribe el asunto del correo
            $mail->Subject = $subject;
            // Escribe el mensaje del correo
            $mail->Body = $body;

            // Envía el correo
            $mail->send();
            // Responde "sí": el correo fue aceptado para su entrega
            return true;
        // Si el enviador de correos avisa de un error
        } catch (PHPMailerException $e) {
            // deja anotado el problema en el registro interno del servidor
            error_log('Error enviando correo vía SMTP: ' . $mail->ErrorInfo);
            // Y responde "no": no se pudo enviar
            return false;
        // Si ocurre cualquier otro tipo de error
        } catch (Throwable $e) {
            // deja anotado el problema en el registro interno del servidor
            error_log('Error enviando correo vía SMTP: ' . $e->getMessage());
            // Y responde "no": no se pudo enviar
            return false;
        }
    
    }

    /*
     * Sin SMTP configurado: se intenta mail() nativo como último recurso,
     * pero se deja constancia clara en el log para que no pase inadvertido
     * en producción (a diferencia del antiguo @mail()).
     */
    // Deja anotado en el registro interno que no había servidor de correo y se usará el método básico del servidor
    error_log('SMTP_HOST no configurado: usando mail() nativo de PHP para enviar a ' . $to . '. Configure SMTP_HOST/SMTP_USER/SMTP_PASS para envíos confiables en producción.');

    // Arma los datos de cabecera del correo: quién lo envía y que es texto simple con tildes
    $headers = "From: {$fromName} <{$fromEmail}>\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    // Envía el correo con el método básico del servidor y guarda si resultó bien o mal
    $enviado = mail($to, $subject, $body, $headers);

    // Si no se pudo enviar...
    if (!$enviado) {
        // ...deja anotado el problema en el registro interno del servidor
        error_log('mail() nativo no pudo enviar el correo a ' . $to . '. Revise la configuración del servidor de correo.');
    }

    // Entrega la respuesta: "sí" si se envió, "no" si falló
    return $enviado;
}



/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- FIN Jerez mailer.php ------------------------------------------
   ------------------------------------------------------------------------------------------------------------- */


/*
Sitio Web Creado por Jerez.
Direccion: Miraflorez #1280
Quintero - Chile
jaime.jerez1978@gmail.com
https://www.
Creado, Programado y Diseñado por Jerez.
JJA
*/   