<!--
    TÍTULO: INFORMACIÓN DEL SITIO WEB

    Sitio Web Creado por Jerez.
    Dirección: Miraflorez #1280
    Quintero - Chile
    jaime.jerez1978@gmail.com
    https://www.
    Creado, Programado y Diseñado por Jerez.
    JJA
-->


<!-- -------------------------------------------------------------------------------------------------------------
   ------------------------------------- INICIO Jerez PHPMailer.php ------------------------------------
   ------------------------------------------------------------------------------------------------------------- -->



<?php
/** TÍTULO: PHPMAILER - CLASE PARA CREAR Y ENVIAR CORREOS ELECTRÓNICOS CON PHP
 *
 * PHPMailer - Clase para crear y enviar correos electrónicos con PHP.
 * Versión de PHP utilizada originalmente: 5.5.
 * 
 * @see https://github.com/PHPMailer/PHPMailer/ El PHPMailer GitHub project
 * 
 * @author    Marcus Bointon (Synchro/coolbru) <phpmailer@synchromedia.co.uk>
 * @author    Jim Jagielski (jimjag) <jimjag@gmail.com>
 * @author    Andy Prevost (codeworxtech) <codeworxtech@users.sourceforge.net>
 * @author    Brent R. Matzelle (original founder)
 * @copyright 2012 - 2020 Marcus Bointon
 * @copyright 2010 - 2012 Jim Jagielski
 * @copyright 2004 - 2009 Andy Prevost
 * @license   https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU Lesser General Public License
 * @note      Este programa se distribuye con la intención de que sea útil, SIN
 * NINGUNA GARANTÍA; ni siquiera la garantía implícita de COMERCIALIZACIÓN o
 * IDONEIDAD PARA UN PROPÓSITO PARTICULAR.
 */

namespace PHPMailer\PHPMailer;

/**
 * TÍTULO: PHPMAILER - CLASE PARA CREAR Y ENVIAR CORREOS ELECTRÓNICOS CON PHP
 *
 * PHPMailer - Clase para crear y enviar correos electrónicos con PHP.
 * 
 * @author Marcus Bointon (Synchro/coolbru) <phpmailer@synchromedia.co.uk>
 * @author Jim Jagielski (jimjag) <jimjag@gmail.com>
 * @author Andy Prevost (codeworxtech) <codeworxtech@users.sourceforge.net>
 * @author Brent R. Matzelle (original founder)
 */
class PHPMailer
{
    const CHARSET_ASCII = 'us-ascii';
    const CHARSET_ISO88591 = 'iso-8859-1';
    const CHARSET_UTF8 = 'utf-8';

    const CONTENT_TYPE_PLAINTEXT = 'text/plain';
    const CONTENT_TYPE_TEXT_CALENDAR = 'text/calendar';
    const CONTENT_TYPE_TEXT_HTML = 'text/html';
    const CONTENT_TYPE_MULTIPART_ALTERNATIVE = 'multipart/alternative';
    const CONTENT_TYPE_MULTIPART_MIXED = 'multipart/mixed';
    const CONTENT_TYPE_MULTIPART_RELATED = 'multipart/related';

    const ENCODING_7BIT = '7bit';
    const ENCODING_8BIT = '8bit';
    const ENCODING_BASE64 = 'base64';
    const ENCODING_BINARY = 'binary';
    const ENCODING_QUOTED_PRINTABLE = 'quoted-printable';

    const ENCRYPTION_STARTTLS = 'tls';
    const ENCRYPTION_SMTPS = 'ssl';

    const ICAL_METHOD_REQUEST = 'REQUEST';
    const ICAL_METHOD_PUBLISH = 'PUBLISH';
    const ICAL_METHOD_REPLY = 'REPLY';
    const ICAL_METHOD_ADD = 'ADD';
    const ICAL_METHOD_CANCEL = 'CANCEL';
    const ICAL_METHOD_REFRESH = 'REFRESH';
    const ICAL_METHOD_COUNTER = 'COUNTER';
    const ICAL_METHOD_DECLINECOUNTER = 'DECLINECOUNTER';

    /**
 * TÍTULO: PRIORIDAD DEL CORREO ELECTRÓNICO
 *
     * Prioridad del correo electrónico.
     * Options: null (default), 1 = High, 3 = Normal, 5 = low.
     * Cuando es null, el encabezado no se establece.
     * 
     * @var int|null
     */
    public $Priority;

    /**
 * TÍTULO: CONJUNTO DE CARACTERES DEL MENSAJE
 *
     * Conjunto de caracteres del mensaje.
     * 
     * @var string
     */
    public $CharSet = self::CHARSET_ISO88591;

    /**
 * TÍTULO: TIPO DE CONTENIDO MIME DEL MENSAJE
 *
     * Tipo de contenido MIME del mensaje.
     * 
     * @var string
     */
    public $ContentType = self::CONTENT_TYPE_PLAINTEXT;

    /**
 * TÍTULO: CODIFICACIÓN DEL MENSAJE
 *
     * Codificación del mensaje.
     * Opciones: "8bit", "7bit", "binary", "base64" y "quoted-printable".
     * 
     * @var string
     */
    public $Encoding = self::ENCODING_8BIT;

    /**
 * TÍTULO: GUARDA EL MENSAJE DE ERROR MÁS RECIENTE DEL CORREO
 *
     * Guarda el mensaje de error más reciente del correo.
     * 
     * @var string
     */
    public $ErrorInfo = '';

    /**
 * TÍTULO: DIRECCIÓN DE CORREO QUE APARECE COMO REMITENTE
 *
     * Dirección de correo que aparece como remitente.
     * 
     * @var string
     */
    public $From = '';

    /**
 * TÍTULO: NOMBRE QUE APARECE COMO REMITENTE
 *
     * Nombre que aparece como remitente.
     * 
     * @var string
     */
    public $FromName = '';

    /**
 * TÍTULO: REMITENTE DEL SOBRE DEL MENSAJE
 *
     * Remitente del sobre del mensaje.
     * Normalmente el receptor lo convierte en un encabezado Return-Path,
     * y es la dirección a la que se enviarán los mensajes devueltos.
     * Si no está vacío, se enviará mediante `-f` to sendmail or as the 'MAIL FROM' value over SMTP.
     * 
     * @var string
     */
    public $Sender = '';

    /**
 * TÍTULO: ASUNTO DEL MENSAJE
 *
     * Asunto del mensaje.
     * 
     * @var string
     */
    public $Subject = '';

    /**
 * TÍTULO: CONTENIDO DEL MENSAJE EN HTML O TEXTO SIMPLE
 *
     * Contenido del mensaje en HTML o texto simple.
     * Si se usa HTML, se debe llamar a isHTML(true).
     * 
     * @var string
     */
    public $Body = '';

    /**
 * TÍTULO: CONTENIDO DEL MENSAJE EN TEXTO SIMPLE
 *
     * Contenido del mensaje en texto simple.
     * Este contenido puede ser leído por clientes de correo que no tienen soporte para correo HTML
     * capability such as mutt & Eudora.
     * Los clientes que pueden leer HTML mostrarán el contenido normal de Body.
     * 
     * @var string
     */
    public $AltBody = '';

    /**
 * TÍTULO: CONTENIDO DE LA PARTE ICAL DEL MENSAJE
 *
     * Contenido de la parte iCal del mensaje.
     * Solo es compatible con los tipos de mensaje alt o alt_inline simples.
     * Para crear estructuras de eventos iCal, se pueden usar clases como EasyPeasyICS o iCalcreator.
     * 
     * @see https://kigkonsult.se/iCalcreator/
     * 
     * @var string
     */
    public $Ical = '';

    /**
 * TÍTULO: LISTA DE VALORES DE "METHOD" EN EL ENCABEZADO CONTENT-TYPE "TEXT/CALENDAR"
 *
     * Lista de valores de "method" en el encabezado Content-Type "text/calendar".
     * 
     * @var string[]
     */
    protected static $IcalMethods = [
        self::ICAL_METHOD_REQUEST,
        self::ICAL_METHOD_PUBLISH,
        self::ICAL_METHOD_REPLY,
        self::ICAL_METHOD_ADD,
        self::ICAL_METHOD_CANCEL,
        self::ICAL_METHOD_REFRESH,
        self::ICAL_METHOD_COUNTER,
        self::ICAL_METHOD_DECLINECOUNTER,
    ];

    /**
 * TÍTULO: CONTENIDO MIME COMPLETO DEL MENSAJE
 *
     * Contenido MIME completo del mensaje.
     * 
     * @var string
     */
    protected $MIMEBody = '';

    /**
 * TÍTULO: ENCABEZADOS MIME COMPLETOS DEL MENSAJE
 *
     * Encabezados MIME completos del mensaje.
     * 
     * @var string
     */
    protected $MIMEHeader = '';

    /**
 * TÍTULO: ENCABEZADOS ADICIONALES QUE CREATEHEADER() NO INCLUYE
 *
     * Encabezados adicionales que createHeader() no incluye.
     * 
     * @var string
     */
    protected $mailHeader = '';

    /**
 * TÍTULO: AJUSTA LAS LÍNEAS DEL CONTENIDO DEL MENSAJE A ESTA CANTIDAD DE CARACTERES
 *
     * Ajusta las líneas del contenido del mensaje a esta cantidad de caracteres.
     * Use 0 para no ajustar las líneas. Un valor útil es 78 para cumplir RFC2822, sección 2.1.1.
     * 
     * @see static::STD_LINE_LENGTH
     * 
     * @var int
     */
    public $WordWrap = 0;

    /**
 * TÍTULO: MÉTODO QUE SE UTILIZARÁ PARA ENVIAR EL CORREO
 *
     * Método que se utilizará para enviar el correo.
     * Opciones: "mail", "sendmail" o "smtp".
     * 
     * @var string
     */
    public $Mailer = 'mail';

    /**
 * TÍTULO: RUTA DEL PROGRAMA SENDMAIL
 *
     * Ruta del programa sendmail.
     * 
     * @var string
     */
    public $Sendmail = '/usr/sbin/sendmail';

    /**
 * TÍTULO: INDICA SI MAIL() USA UN MTA TOTALMENTE COMPATIBLE CON SENDMAIL
 *
     * Indica si mail() usa un MTA totalmente compatible con sendmail.
     * Debe admitir las opciones "-oi -f" de sendmail.
     * 
     * @var bool
     */
    public $UseSendmailOptions = true;

    /**
 * TÍTULO: DIRECCIÓN DE CORREO A LA QUE SE ENVIARÁ LA CONFIRMACIÓN DE LECTURA
 *
     * Dirección de correo a la que se enviará la confirmación de lectura.
     * 
     * @var string
     */
    public $ConfirmReadingTo = '';

    /**
 * TÍTULO: NOMBRE DEL SERVIDOR QUE SE USARÁ EN EL ENCABEZADO MESSAGE-ID Y COMO HELO PREDETERMINADO
 *
     * Nombre del servidor que se usará en el encabezado Message-ID y como HELO predeterminado.
     * Si está vacío, PHPMailer intenta encontrar uno en este orden:
     * $_SERVER['SERVER_NAME'], gethostname(), php_uname('n'), or the value
     * 'localhost.localdomain'.
     * 
     * @see PHPMailer::$Helo
     * 
     * @var string
     */
    public $Hostname = '';

    /**
 * TÍTULO: IDENTIFICADOR QUE SE UTILIZARÁ EN EL ENCABEZADO MESSAGE-ID
 *
     * Identificador que se utilizará en el encabezado Message-ID.
     * Si está vacío, a unique id will be generated.
     * Puede establecer uno propio, pero debe tener el formato "<id@domain>",
     * según lo definido en RFC5322 section 3.6.4 o será ignorado.
     * 
     * @see https://www.rfc-editor.org/rfc/rfc5322#section-3.6.4
     * 
     * @var string
     */
    public $MessageID = '';

    /**
 * TÍTULO: FECHA DEL MENSAJE QUE SE UTILIZARÁ EN EL ENCABEZADO DATE
 *
     * Fecha del mensaje que se utilizará en el encabezado Date.
     * Si está vacío, se agregará la fecha actual.
     * 
     * @var string
     */
    public $MessageDate = '';

    /**
 * TÍTULO: SERVIDORES SMTP
 *
     * Servidores SMTP.
     * Puede ser un solo nombre de servidor o varios nombres separados por punto y coma.
     * You can also specify a different port
     * for each host by using this format: [hostname:port]
     * (e.g. "smtp1.example.com:25;smtp2.example.com").
     * You can also specify encryption type, for example:
     * (e.g. "tls://smtp1.example.com:587;ssl://smtp2.example.com:465").
     * Los servidores se probarán en el orden indicado.
     * 
     * @var string
     */
    public $Host = 'localhost';

    /**
 * TÍTULO: PUERTO PREDETERMINADO DEL SERVIDOR SMTP
 *
     * Puerto predeterminado del servidor SMTP.
     * 
     * @var int
     */
    public $Port = 25;

    /**
 * TÍTULO: NOMBRE HELO/EHLO UTILIZADO PARA LA CONEXIÓN SMTP
 *
     * Nombre HELO/EHLO utilizado para la conexión SMTP.
     * El valor predeterminado es $Hostname. Si $Hostname está vacío, PHPMailer intenta encontrar
     * uno usando el mismo método indicado anteriormente para $Hostname.
     * 
     * @see PHPMailer::$Hostname
     * 
     * @var string
     */
    public $Helo = '';

    /**
 * TÍTULO: TIPO DE CIFRADO QUE SE UTILIZARÁ EN LA CONEXIÓN SMTP
 *
     * Tipo de cifrado que se utilizará en la conexión SMTP.
     * Options: '', static::ENCRYPTION_STARTTLS, or static::ENCRYPTION_SMTPS.
     * 
     * @var string
     */
    public $SMTPSecure = '';

    /**
 * TÍTULO: INDICA SI SE ACTIVA AUTOMÁTICAMENTE EL CIFRADO TLS CUANDO EL SERVIDOR LO ADMITE
 *
     * Indica si se activa automáticamente el cifrado TLS cuando el servidor lo admite.
     * aunque `SMTPSecure` no esté configurado como 'tls'.
     * Tenga en cuenta que en PHP >= 5.6 los certificados del servidor deben ser válidos.
     * 
     * @var bool
     */
    public $SMTPAutoTLS = true;

    /**
 * TÍTULO: INDICA SI SE UTILIZARÁ AUTENTICACIÓN SMTP
 *
     * Indica si se utilizará autenticación SMTP.
     * Utiliza las propiedades Username y Password.
     * 
     * @see PHPMailer::$Username
     * @see PHPMailer::$Password
     * 
     * @var bool
     */
    public $SMTPAuth = false;

    /**
 * TÍTULO: LISTA DE OPCIONES ENVIADA A STREAM_CONTEXT_CREATE AL CONECTARSE MEDIANTE SMTP
 *
     * Lista de opciones enviada a stream_context_create al conectarse mediante SMTP.
     * 
     * @var array
     */
    public $SMTPOptions = [];

    /**
 * TÍTULO: USUARIO DEL SERVIDOR SMTP
 *
     * Usuario del servidor SMTP.
     * 
     * @var string
     */
    public $Username = '';

    /**
 * TÍTULO: CONTRASEÑA DEL SERVIDOR SMTP
 *
     * Contraseña del servidor SMTP.
     * 
     * @var string
     */
    public $Password = '';

    /**
 * TÍTULO: TIPO DE AUTENTICACIÓN SMTP. LAS OPCIONES SON CRAM-MD5, LOGIN, PLAIN Y XOAUTH2
 *
     * Tipo de autenticación SMTP. Las opciones son CRAM-MD5, LOGIN, PLAIN y XOAUTH2.
     * Si no se especifica, se seleccionará la primera opción de esa lista que admita el servidor.
     * 
     * @var string
     */
    public $AuthType = '';

    /**
 * TÍTULO: SMTP SMTPXCLIENT COMMAND ATTRIBUTES
 *
     * SMTP SMTPXClient command attributes
     * 
     * @var array
     */
    protected $SMTPXClient = [];

    /**
 * TÍTULO: IMPLEMENTACIÓN DE LA INTERFAZ OAUTHTOKENPROVIDER DE PHPMAILER
 *
     * Implementación de la interfaz OAuthTokenProvider de PHPMailer.
     * 
     * @var OAuthTokenProvider
     */
    protected $oauth;

    /**
 * TÍTULO: TIEMPO MÁXIMO DE ESPERA DEL SERVIDOR SMTP, EN SEGUNDOS
 *
     * Tiempo máximo de espera del servidor SMTP, en segundos.
     * El valor predeterminado de 5 minutos (300 segundos) proviene de RFC2821, sección 4.5.3.2.
     * 
     * @var int
     */
    public $Timeout = 300;

    /**
 * TÍTULO: LISTA DE NOTIFICACIONES DSN SEPARADAS POR COMAS
 *
     * Lista de notificaciones DSN separadas por comas.
     * 'NEVER' indica que nunca se debe devolver un DSN al remitente.
     * Si usa NEVER, las demás notificaciones serán ignoradas.
     * 'SUCCESS' will notify you when your mail has arrived at its destination.
     * 'FAILURE' will arrive if an error occurred during delivery.
     * 'DELAY' avisará si existe un retraso inusual en la entrega, pero el resultado
     * de la entrega (éxito o error) todavía no está definido.
     * 
     * @see https://www.rfc-editor.org/rfc/rfc3461.html#section-4.1 for more information about NOTIFY
     */
    public $dsn = '';

    /**
 * TÍTULO: MODO DE SALIDA DE DEPURACIÓN DE LA CLASE SMTP
 *
     * Modo de salida de depuración de la clase SMTP.
     * Nivel de salida de depuración.
     * Opciones:
     * @see SMTP::DEBUG_OFF: Sin salida
     * @see SMTP::DEBUG_CLIENT: Client mensajes
     * @see SMTP::DEBUG_SERVER: Mensajes del cliente y del servidor
     * @see SMTP::DEBUG_CONNECTION: As SERVER plus conexión status
     * @see SMTP::DEBUG_LOWLEVEL: Salida de datos de bajo nivel, normalmente no necesaria
     * 
     * @see SMTP::$do_debug
     * 
     * @var int
     */
    public $SMTPDebug = 0;

    /**
 * TÍTULO: INDICA CÓMO MANEJAR LA INFORMACIÓN DE DEPURACIÓN
 *
     * Indica cómo manejar la información de depuración.
     * Opciones:
     * * `echo` Muestra texto simple tal como está, adecuado para CLI
     * * `html` Escapa el contenido y convierte los saltos de línea a `<br>`, adecuado para el navegador
     * * `error_log` Envía la salida al registro de errores configurado en php.ini
     * Por defecto PHPMailer usa `echo` en SAPI `cli` o `cli-server`, y `html` en otros casos.
     * También puede proporcionar una función que reciba dos parámetros: un texto y el nivel de depuración:
     * 
     * ```php
     * $mail->Debugoutput = function($str, $level) {echo "debug level $level; mensaje: $str";};
     * ```
     * 
     * También puede proporcionar una instancia de un registrador compatible con PSR-3, aunque solo se usa el nivel
     * `debug`:
     * 
     * ```php
     * $mail->Debugoutput = new myPsr3Logger;
     * ```
     * 
     * @see SMTP::$Debugoutput
     * 
     * @var string|callable|\Psr\Log\LoggerInterface
     */
    public $Debugoutput = 'echo';

    /**
 * TÍTULO: INDICA SI LA CONEXIÓN SMTP PERMANECE ABIERTA DESPUÉS DE CADA MENSAJE
 *
     * Indica si la conexión SMTP permanece abierta después de cada mensaje.
     * Si se establece en true, la conexión permanecerá abierta después de un envío,
     * y para cerrar la conexión será necesario llamar explícitamente a smtpClose().
     * Es recomendable usarlo al enviar varios mensajes porque reduce el trabajo adicional.
     * Consulte el ejemplo de lista de correo para ver cómo utilizarlo.
     * 
     * @var bool
     */
    public $SMTPKeepAlive = false;

    /**
 * TÍTULO: INDICA SI VARIAS DIRECCIONES DE DESTINO SE SEPARAN EN VARIOS MENSAJES
 *
     * Indica si varias direcciones de destino se separan en varios mensajes.
     * o enviarlas todas en un solo mensaje.
     * Solo es compatible con los métodos `mail` y `sendmail`, no con SMTP.
     * 
     * @var bool
     * 
     * @deprecated 6.0.0 PHPMailer isn't a mailing list manager!
     */
    public $SingleTo = false;

    /**
 * TÍTULO: GUARDA LAS DIRECCIONES CUANDO SINGLETO ESTÁ ACTIVADO
 *
     * Guarda las direcciones cuando SingleTo está activado.
     * 
     * @var array
     */
    protected $SingleToArray = [];

    /**
 * TÍTULO: INDICA SI SE DEBEN GENERAR DIRECCIONES VERP AL ENVIAR
 *
     * Indica si se deben generar direcciones VERP al enviar.
     * Solo se aplica cuando se envía mediante SMTP.
     * 
     * @see https://en.wikipedia.org/wiki/Variable_envelope_return_path
     * @see https://www.postfix.org/VERP_README.html Postfix VERP info
     * 
     * @var bool
     */
    public $do_verp = false;

    /**
 * TÍTULO: INDICA SI SE PERMITE ENVIAR MENSAJES SIN CONTENIDO
 *
     * Indica si se permite enviar mensajes sin contenido.
     * 
     * @var bool
     */
    public $AllowEmpty = false;

    /**
 * TÍTULO: SELECTOR DKIM
 *
     * Selector DKIM.
     * 
     * @var string
     */
    public $DKIM_selector = '';

    /**
 * TÍTULO: IDENTIDAD DKIM
 *
     * Identidad DKIM.
     * Normalmente es la dirección de correo usada como origen.
     * 
     * @var string
     */
    public $DKIM_identity = '';

    /**
 * TÍTULO: FRASE DE SEGURIDAD DKIM
 *
     * Frase de seguridad DKIM.
     * Se utiliza cuando la clave está cifrada.
     * 
     * @var string
     */
    public $DKIM_passphrase = '';

    /**
 * TÍTULO: NOMBRE DEL DOMINIO USADO PARA FIRMAR CON DKIM
 *
     * Nombre del dominio usado para firmar con DKIM.
     * 
     * @example 'example.com'
     * 
     * @var string
     */
    public $DKIM_domain = '';

    /**
 * TÍTULO: COPIA LOS VALORES DE LOS ENCABEZADOS DKIM PARA DIAGNÓSTICO
 *
     * Copia los valores de los encabezados DKIM para diagnóstico.
     * 
     * @var bool
     */
    public $DKIM_copyHeaderFields = true;

    /**
 * TÍTULO: ENCABEZADOS ADICIONALES QUE SE FIRMARÁN CON DKIM
 *
     * Encabezados adicionales que se firmarán con DKIM.
     * 
     * @example ['List-Unsubscribe', 'List-Help']
     * 
     * @var array
     */
    public $DKIM_extraHeaders = [];

    /**
 * TÍTULO: RUTA DEL ARCHIVO DE CLAVE PRIVADA DKIM
 *
     * Ruta del archivo de clave privada DKIM.
     * 
     * @var string
     */
    public $DKIM_private = '';

    /**
 * TÍTULO: TEXTO DE LA CLAVE PRIVADA DKIM
 *
     * Texto de la clave privada DKIM.
     * 
     * Si se establece, tiene prioridad sobre `$DKIM_private`.
     * 
     * @var string
     */
    public $DKIM_private_string = '';

    /**
 * TÍTULO: NOMBRE DE LA FUNCIÓN DE ACCIÓN DE RESPUESTA
 *
     * Nombre de la función de acción de respuesta.
     * 
     * El function that handles the result of the send email action.
     * send() la llama por cada correo enviado.
     * 
     * Value can be any php callable: https://www.php.net/is_callable
     * 
     * Parameters:
     * bool $result           resultado de la acción de envío
     * array   $to            dirección de correoes of the destinatarios
     * array   $cc            cc dirección de correoes
     * array   $bcc           bcc dirección de correoes
     * string  $asunto       el asunto
     * string  $contenido          el contenido del correo
     * string  $from          dirección de correo del remitente
     * string  $extra         información adicional que puede ser útil
     * "smtp_transaction_id' => last smtp transaction id
     * 
     * @var string
     */
    public $action_function = '';

    /**
 * TÍTULO: INDICA QUÉ COLOCAR EN EL ENCABEZADO X-MAILER
 *
     * Indica qué colocar en el encabezado X-Mailer.
     * Options: Un empty string for PHPMailer default, whitespace/null for none, or a string to use.
     * 
     * @var string|null
     */
    public $XMailer = '';

    /**
 * TÍTULO: VALIDADOR QUE SE USARÁ POR DEFECTO PARA COMPROBAR DIRECCIONES DE CORREO
 *
     * Validador que se usará por defecto para comprobar direcciones de correo.
     * Puede ser una función propia para validar direcciones; también existen varios validadores incluidos.
     * El default validator uses PHP's FILTER_VALIDATE_EMAIL filter_var option.
     * 
     * @see PHPMailer::validateAddress()
     * 
     * @var string|callable
     */
    public static $validator = 'php';

    /**
 * TÍTULO: UNA INSTANCIA DE LA CLASE ENCARGADA DEL ENVÍO SMTP
 *
     * Una instancia de la clase encargada del envío SMTP.
     * 
     * @var SMTP
     */
    protected $smtp;

    /**
 * TÍTULO: LISTA DE NOMBRES Y DIRECCIONES DE DESTINO
 *
     * Lista de nombres y direcciones de destino.
     * 
     * @var array
     */
    protected $to = [];

    /**
 * TÍTULO: LISTA DE NOMBRES Y DIRECCIONES CON COPIA
 *
     * Lista de nombres y direcciones con copia.
     * 
     * @var array
     */
    protected $cc = [];

    /**
 * TÍTULO: LISTA DE NOMBRES Y DIRECCIONES CON COPIA OCULTA
 *
     * Lista de nombres y direcciones con copia oculta.
     * 
     * @var array
     */
    protected $bcc = [];

    /**
 * TÍTULO: LISTA DE NOMBRES Y DIRECCIONES PARA RESPONDER
 *
     * Lista de nombres y direcciones para responder.
     * 
     * @var array
     */
    protected $ReplyTo = [];

    /**
 * TÍTULO: UN ARRAY OF ALL KINDS OF DIRECCIONES
 *
     * Un array of all kinds of direcciones.
     * Incluye $to, $cc y $bcc.
     * 
     * @see PHPMailer::$to
     * @see PHPMailer::$cc
     * @see PHPMailer::$bcc
     * 
     * @var array
     */
    protected $all_recipients = [];

    /**
 * TÍTULO: UN ARRAY OF NAMES AND DIRECCIONES QUEUED FOR VALIDATION
 *
     * Un array of names and direcciones queued for validation.
     * En send(), las entradas válidas y no duplicadas se pasan a $all_recipients
     * y a una de las listas $to, $cc o $bcc.
     * Esta lista se usa solo para direcciones con IDN.
     * 
     * @see PHPMailer::$to
     * @see PHPMailer::$cc
     * @see PHPMailer::$bcc
     * @see PHPMailer::$all_destinatarios
     * 
     * @var array
     */
    protected $RecipientsQueue = [];

    /**
 * TÍTULO: UN ARRAY OF REPLY-TO NAMES AND DIRECCIONES QUEUED FOR VALIDATION
 *
     * Un array of reply-to names and direcciones queued for validation.
     * En send(), las entradas válidas y no duplicadas se pasan a $ReplyTo.
     * Esta lista se usa solo para direcciones con IDN.
     * 
     * @see PHPMailer::$ReplyTo
     * 
     * @var array
     */
    protected $ReplyToQueue = [];

    /**
 * TÍTULO: LISTA DE ARCHIVOS ADJUNTOS
 *
     * Lista de archivos adjuntos.
     * 
     * @var array
     */
    protected $attachment = [];

    /**
 * TÍTULO: LISTA DE ENCABEZADOS PERSONALIZADOS
 *
     * Lista de encabezados personalizados.
     * 
     * @var array
     */
    protected $CustomHeader = [];

    /**
 * TÍTULO: ÚLTIMO MESSAGE-ID UTILIZADO, INCLUIDOS LOS CORCHETES ANGULARES
 *
     * Último Message-ID utilizado, incluidos los corchetes angulares.
     * 
     * @var string
     */
    protected $lastMessageID = '';

    /**
 * TÍTULO: TIPO MIME DEL MENSAJE
 *
     * Tipo MIME del mensaje.
     * 
     * @var string
     */
    protected $message_type = '';

    /**
 * TÍTULO: LISTA DE LÍMITES UTILIZADOS PARA LAS PARTES MIME
 *
     * Lista de límites utilizados para las partes MIME.
     * 
     * @var array
     */
    protected $boundary = [];

    /**
 * TÍTULO: LISTA DE TEXTOS DISPONIBLES PARA EL IDIOMA ACTUAL
 *
     * Lista de textos disponibles para el idioma actual.
     * 
     * @var array
     */
    protected $language = [];

    /**
 * TÍTULO: CANTIDAD DE ERRORES ENCONTRADOS
 *
     * Cantidad de errores encontrados.
     * 
     * @var int
     */
    protected $error_count = 0;

    /**
 * TÍTULO: RUTA DEL ARCHIVO DEL CERTIFICADO S/MIME
 *
     * Ruta del archivo del certificado S/MIME.
     * 
     * @var string
     */
    protected $sign_cert_file = '';

    /**
 * TÍTULO: RUTA DEL ARCHIVO DE CLAVE S/MIME
 *
     * Ruta del archivo de clave S/MIME.
     * 
     * @var string
     */
    protected $sign_key_file = '';

    /**
 * TÍTULO: RUTA OPCIONAL DEL ARCHIVO DE CERTIFICADOS ADICIONALES S/MIME (CADENA CA)
 *
     * Ruta opcional del archivo de certificados adicionales S/MIME (cadena CA).
     * 
     * @var string
     */
    protected $sign_extracerts_file = '';

    /**
 * TÍTULO: CONTRASEÑA DE LA CLAVE S/MIME
 *
     * Contraseña de la clave S/MIME.
     * Usado only if the key is encrypted.
     * 
     * @var string
     */
    protected $sign_key_pass = '';

    /**
 * TÍTULO: INDICA SI SE DEBEN LANZAR EXCEPCIONES CUANDO OCURRE UN ERROR
 *
     * Indica si se deben lanzar excepciones cuando ocurre un error.
     * 
     * @var bool
     */
    protected $exceptions = false;

    /**
 * TÍTULO: IDENTIFICADOR ÚNICO USADO PARA EL MESSAGE-ID Y LOS LÍMITES MIME
 *
     * Identificador único usado para el Message-ID y los límites MIME.
     * 
     * @var string
     */
    protected $uniqueid = '';

    /**
 * TÍTULO: NÚMERO DE VERSIÓN DE PHPMAILER
 *
     * Número de versión de PHPMailer.
     * 
     * @var string
     */
    const VERSION = '6.9.3';

    /**
 * TÍTULO: NIVEL DE ERROR: SOLO INFORMA EL MENSAJE Y CONTINÚA
 *
     * Nivel de error: solo informa el mensaje y continúa.
     * 
     * @var int
     */
    const STOP_MESSAGE = 0;

    /**
 * TÍTULO: NIVEL DE ERROR: INFORMA EL MENSAJE Y PROBABLEMENTE PUEDE CONTINUAR
 *
     * Nivel de error: informa el mensaje y probablemente puede continuar.
     * 
     * @var int
     */
    const STOP_CONTINUE = 1;

    /**
 * TÍTULO: NIVEL DE ERROR: INFORMA EL MENSAJE Y DETIENE EL PROCESO POR UN ERROR CRÍTICO
 *
     * Nivel de error: informa el mensaje y detiene el proceso por un error crítico.
     * 
     * @var int
     */
    const STOP_CRITICAL = 2;

    /**
 * TÍTULO: SALTO DE LÍNEA CRLF ESTÁNDAR DE SMTP
 *
     * Salto de línea CRLF estándar de SMTP.
     * Si desea cambiar el formato del salto de línea, cambie static::$LE, no este valor.
     */
    const CRLF = "\r\n";

    /**
 * TÍTULO: "FOLDING WHITE SPACE" A WHITE SPACE STRING USED FOR LINE FOLDING
 *
     * "Folding White Space" a white space string used for line folding.
     */
    const FWS = ' ';

    /**
 * TÍTULO: SMTP RFC STANDARD LINE ENDING; CARRIAGE RETURN, LINE FEED
 *
     * Final de línea estándar de SMTP y RFC: retorno de carro y salto de línea.
     * 
     * @var string
     */
    protected static $LE = self::CRLF;

    /**
 * TÍTULO: MÁXIMA LONGITUD DE LÍNEA ADMITIDA POR MAIL()
 *
     * Máxima longitud de línea admitida por mail().
     * 
     * Background: mail() will sometimes corrupt mensajes
     * con encabezados de más de 65 caracteres; consulte #818.
     * 
     * @var int
     */
    const MAIL_MAX_LINE_LENGTH = 63;

    /**
 * TÍTULO: MÁXIMA LONGITUD DE LÍNEA PERMITIDA POR RFC 2822, SECCIÓN 2.1.1
 *
     * Máxima longitud de línea permitida por RFC 2822, sección 2.1.1.
     * 
     * @var int
     */
    const MAX_LINE_LENGTH = 998;

    /**
 * TÍTULO: LONGITUD MÁXIMA DE LÍNEA MÁS CORTA PERMITIDA POR RFC 2822, SECCIÓN 2.1.1
 *
     * Longitud máxima de línea más corta permitida por RFC 2822, sección 2.1.1.
     * Esta longitud NO incluye el salto de línea
     * 76 significa que las líneas tendrán 77 u 78 caracteres dependiendo de si
     * el formato de salto de línea es LF o CRLF; ambos son válidos.
     * 
     * @var int
     */
    const STD_LINE_LENGTH = 76;

    /**
 * TÍTULO: CONSTRUCTOR DE LA CLASE
 *
     * Constructor de la clase.
     * 
     * @param bool $exceptions Should we throw external exceptions?
     */
    public function __construct($exceptions = null)
    {
        if (null !== $exceptions) {
            $this->exceptions = (bool) $exceptions;
        }
        // Pick an appropriate salida de depuración format automatically
        $this->Debugoutput = (strpos(PHP_SAPI, 'cli') !== false ? 'echo' : 'html');
    }

    /**
 * TÍTULO: DESTRUCTOR DE LA CLASE
 *
     * Destructor de la clase.
     */
    public function __destruct()
    {
        // Close any open SMTP conexión nicely
        $this->smtpClose();
    }

    /**
 * TÍTULO: LLAMA A MAIL() DE UNA FORMA COMPATIBLE CON SAFE_MODE
 *
     * Llama a mail() de una forma compatible con safe_mode.
     * Además, si sendmail_path no apunta a sendmail (o a algo que
     * claims to be sendmail), don't pass params (not a perfect fix,
     * but it will do).
     * 
     * @param string      $to      Destinatario
     * @param string      $asunto Subject
     * @param string      $contenido    Message Body
     * @param string      $header  Encabezado(s) adicional(es)
     * @param string|null $params  Parámetros
     * 
     * @return bool
     */
    private function mailPassthru($to, $subject, $body, $header, $params)
    {
        // Comprobar overloading of mail function to avoid double-encoding
        if ((int)ini_get('mbstring.func_overload') & 1) {
            $subject = $this->secureHeader($subject);
        } else {
            $subject = $this->encodeHeader($this->secureHeader($subject));
        }
        // Llamar a mail() con parámetros null provoca un error
        $this->edebug('Sending with mail()');
        $this->edebug('Sendmail path: ' . ini_get('sendmail_path'));
        $this->edebug("Envelope sender: {$this->Sender}");
        $this->edebug("To: {$to}");
        $this->edebug("Subject: {$subject}");
        $this->edebug("Headers: {$header}");
        if (!$this->UseSendmailOptions || null === $params) {
            $result = @mail($to, $subject, $body, $header);
        } else {
            $this->edebug("Additional params: {$params}");
            $result = @mail($to, $subject, $body, $header, $params);
        }
        $this->edebug('Result: ' . ($result ? 'true' : 'false'));
        return $result;
    }

    /**
 * TÍTULO: MUESTRA INFORMACIÓN DE DEPURACIÓN MEDIANTE UN MÉTODO DEFINIDO POR EL USUARIO
 *
     * Muestra información de depuración mediante un método definido por el usuario.
     * Solo genera salida si la depuración está activada.
     * 
     * @see PHPMailer::$Debugoutput
     * @see PHPMailer::$SMTPDebug
     * 
     * @param string $str
     */
    protected function edebug($str)
    {
        if ($this->SMTPDebug <= 0) {
            return;
        }
        // Is this a PSR-3 logger?
        if ($this->Debugoutput instanceof \Psr\Log\LoggerInterface) {
            $this->Debugoutput->debug(rtrim($str, "\r\n"));

            return;
        }
        // Avoid clash with built-in function names
        if (is_callable($this->Debugoutput) && !in_array($this->Debugoutput, ['error_log', 'html', 'echo'])) {
            call_user_func($this->Debugoutput, $str, $this->SMTPDebug);

            return;
        }
        switch ($this->Debugoutput) {
            case 'error_log':
                // No mostrar; solo registrar
                /** @noinspection ForgottenDebugOutputInspection */
                error_log($str);
                break;
            case 'html':
                // Limpia un poco la salida para que sea más ordenada y segura para HTML
                echo htmlentities(
                    preg_replace('/[\r\n]+/', '', $str),
                    ENT_QUOTES,
                    'UTF-8'
                ), "<br>\n";
                break;
            case 'echo':
            default:
                // Normalize salto de líneas
                $str = preg_replace('/\r\n|\r/m', "\n", $str);
                echo gmdate('Y-m-d H:i:s'),
                "\t",
                    // Trim trailing space
                trim(
                    // Aplicar sangría para facilitar la lectura, excepto en el salto final
                    str_replace(
                        "\n",
                        "\n                   \t                  ",
                        trim($str)
                    )
                ),
                "\n";
        }
    }

    /**
 * TÍTULO: DEFINE EL TIPO DE MENSAJE COMO HTML O TEXTO SIMPLE
 *
     * Define el tipo de mensaje como HTML o texto simple.
     * 
     * @param bool $isHtml True for HTML mode
     */
    public function isHTML($isHtml = true)
    {
        if ($isHtml) {
            $this->ContentType = static::CONTENT_TYPE_TEXT_HTML;
        } else {
            $this->ContentType = static::CONTENT_TYPE_PLAINTEXT;
        }
    }

    /**
 * TÍTULO: ENVIAR MENSAJES USANDO SMTP
 *
     * Enviar mensajes usando SMTP.
     */
    public function isSMTP()
    {
        $this->Mailer = 'smtp';
    }

    /**
 * TÍTULO: ENVIAR MENSAJES USANDO LA FUNCIÓN MAIL() DE PHP
 *
     * Enviar mensajes usando la función mail() de PHP.
     */
    public function isMail()
    {
        $this->Mailer = 'mail';
    }

    /**
 * TÍTULO: ENVIAR MENSAJES USANDO $SENDMAIL
 *
     * Enviar mensajes usando $Sendmail.
     */
    public function isSendmail()
    {
        $ini_sendmail_path = ini_get('sendmail_path');

        if (false === stripos($ini_sendmail_path, 'sendmail')) {
            $this->Sendmail = '/usr/sbin/sendmail';
        } else {
            $this->Sendmail = $ini_sendmail_path;
        }
        $this->Mailer = 'sendmail';
    }

    /**
 * TÍTULO: ENVIAR MENSAJES USANDO QMAIL
 *
     * Enviar mensajes usando qmail.
     */
    public function isQmail()
    {
        $ini_sendmail_path = ini_get('sendmail_path');

        if (false === stripos($ini_sendmail_path, 'qmail')) {
            $this->Sendmail = '/var/qmail/bin/qmail-inject';
        } else {
            $this->Sendmail = $ini_sendmail_path;
        }
        $this->Mailer = 'qmail';
    }

    /**
 * TÍTULO: AGREGAR UNA DIRECCIÓN DE DESTINATARIO
 *
     * Agregar una dirección de destinatario.
     * 
     * @param string $dirección El dirección de correo to send to
     * @param string $name
     * 
     * @throws Exception
     * 
     * @return bool true on success, false if dirección already used or invalid in some way
     */
    public function addAddress($address, $name = '')
    {
        return $this->addOrEnqueueAnAddress('to', $address, $name);
    }

    /**
 * TÍTULO: AGREGAR UNA DIRECCIÓN CON COPIA
 *
     * Agregar una dirección con copia.
     * 
     * @param string $dirección El dirección de correo to send to
     * @param string $name
     * 
     * @throws Exception
     * 
     * @return bool true on success, false if dirección already used or invalid in some way
     */
    public function addCC($address, $name = '')
    {
        return $this->addOrEnqueueAnAddress('cc', $address, $name);
    }

    /**
 * TÍTULO: AGREGAR UNA DIRECCIÓN CON COPIA OCULTA
 *
     * Agregar una dirección con copia oculta.
     * 
     * @param string $dirección El dirección de correo to send to
     * @param string $name
     * 
     * @throws Exception
     * 
     * @return bool true on success, false if dirección already used or invalid in some way
     */
    public function addBCC($address, $name = '')
    {
        return $this->addOrEnqueueAnAddress('bcc', $address, $name);
    }

    /**
 * TÍTULO: AGREGAR UNA DIRECCIÓN PARA RESPONDER
 *
     * Agregar una dirección para responder.
     * 
     * @param string $dirección El dirección de correo to reply to
     * @param string $name
     * 
     * @throws Exception
     * 
     * @return bool true on success, false if dirección already used or invalid in some way
     */
    public function addReplyTo($address, $name = '')
    {
        return $this->addOrEnqueueAnAddress('Reply-To', $address, $name);
    }

    /**
 * TÍTULO: AGREGAR AN DIRECCIÓN TO ONE OF THE DESTINATARIO ARRAYS OR TO THE REPLYTO ARRAY. BECAUSE PHPMAILER
 *
     * Agregar an dirección to one of the destinatario arrays or to the ReplyTo array. Because PHPMailer
     * can't validate direcciones with an IDN without knowing the PHPMailer::$CharEstablecer (that can still
     * be modified after calling this function), addition of such direcciones is delayed until send().
     * Addresses that have been added already return false, but do not throw exceptions.
     * 
     * @param string $kind    One of 'to', 'cc', 'bcc', or 'Reply-To'
     * @param string $dirección El dirección de correo
     * @param string $name    Un optional usuario associated with the dirección
     * 
     * @throws Exception
     * 
     * @return bool true on success, false if dirección already used or invalid in some way
     */
    protected function addOrEnqueueAnAddress($kind, $address, $name)
    {
        $pos = false;
        if ($address !== null) {
            $address = trim($address);
            $pos = strrpos($address, '@');
        }
        if (false === $pos) {
            // Falta el símbolo @.
            $error_message = sprintf(
                '%s (%s): %s',
                $this->lang('invalid_address'),
                $kind,
                $address
            );
            $this->setError($error_message);
            $this->edebug($error_message);
            if ($this->exceptions) {
                throw new Exception($error_message);
            }

            return false;
        }
        if ($name !== null && is_string($name)) {
            $name = trim(preg_replace('/[\r\n]+/', '', $name)); // Quitar saltos de línea y espacios sobrantes
        } else {
            $name = '';
        }
        $params = [$kind, $address, $name];
        // Enqueue direcciones with IDN until we know the PHPMailer::$CharSet.
        // Domain is assumed to be whatever is after the last @ symbol in the dirección
        if (static::idnSupported() && $this->has8bitChars(substr($address, ++$pos))) {
            if ('Reply-To' !== $kind) {
                if (!array_key_exists($address, $this->RecipientsQueue)) {
                    $this->RecipientsQueue[$address] = $params;

                    return true;
                }
            } elseif (!array_key_exists($address, $this->ReplyToQueue)) {
                $this->ReplyToQueue[$address] = $params;

                return true;
            }

            return false;
        }

        // Immediately add standard direcciones without IDN.
        return call_user_func_array([$this, 'addAnAddress'], $params);
    }

    /**
 * TÍTULO: DEFINE LOS LÍMITES UTILIZADOS PARA SEPARAR LAS PARTES MIME
 *
     * Define los límites utilizados para separar las partes MIME.
     * If you override this, ensure you set all 3 boundaries to unique values.
     * El default boundaries include a "=_" sequence which cannot occur in quoted-printable bodies,
     * as suggested by https://www.rfc-editor.org/rfc/rfc2045#section-6.7
     * 
     * @return void
     */
    public function setBoundaries()
    {
        $this->uniqueid = $this->generateId();
        $this->boundary[1] = 'b1=_' . $this->uniqueid;
        $this->boundary[2] = 'b2=_' . $this->uniqueid;
        $this->boundary[3] = 'b3=_' . $this->uniqueid;
    }

    /**
 * TÍTULO: AGREGAR AN DIRECCIÓN TO ONE OF THE DESTINATARIO ARRAYS OR TO THE REPLYTO ARRAY
 *
     * Agregar an dirección to one of the destinatario arrays or to the ReplyTo array.
     * Addresses that have been added already return false, but do not throw exceptions.
     * 
     * @param string $kind    One of 'to', 'cc', 'bcc', or 'ReplyTo'
     * @param string $dirección El dirección de correo to send, resp. to reply to
     * @param string $name
     * 
     * @throws Exception
     * 
     * @return bool true on success, false if dirección already used or invalid in some way
     */
    protected function addAnAddress($kind, $address, $name = '')
    {
        if (!in_array($kind, ['to', 'cc', 'bcc', 'Reply-To'])) {
            $error_message = sprintf(
                '%s: %s',
                $this->lang('Invalid recipient kind'),
                $kind
            );
            $this->setError($error_message);
            $this->edebug($error_message);
            if ($this->exceptions) {
                throw new Exception($error_message);
            }

            return false;
        }
        if (!static::validateAddress($address)) {
            $error_message = sprintf(
                '%s (%s): %s',
                $this->lang('invalid_address'),
                $kind,
                $address
            );
            $this->setError($error_message);
            $this->edebug($error_message);
            if ($this->exceptions) {
                throw new Exception($error_message);
            }

            return false;
        }
        if ('Reply-To' !== $kind) {
            if (!array_key_exists(strtolower($address), $this->all_recipients)) {
                $this->{$kind}[] = [$address, $name];
                $this->all_recipients[strtolower($address)] = true;

                return true;
            }
        } elseif (!array_key_exists(strtolower($address), $this->ReplyTo)) {
            $this->ReplyTo[strtolower($address)] = [$address, $name];

            return true;
        }

        return false;
    }

    /**
 * TÍTULO: PARSE AND VALIDATE A STRING CONTAINING ONE OR MORE RFC822-STYLE COMMA-SEPARATED DIRECCIÓN DE CORREOES
 *
     * Parse and validate a string containing one or more RFC822-style comma-separated dirección de correoes
     * of the form "display name <dirección>" into an array of name/dirección pairs.
     * Uses the imap_rfc822_parse_adrlist function if the IMAP extension is available.
     * Note that quotes in the name part are removed.
     * 
     * @see https://www.andrew.cmu.edu/user/agreen1/testing/mrbs/web/Mail/RFC822.php A more careful implementation
     * 
     * @param string $addrstr El dirección list string
     * @param bool   $useimap Indica si se debe use the IMAP extension to parse the list
     * @param string $charset El charset to use when decoding the dirección list string.
     * 
     * @return array
     */
    public static function parseAddresses($addrstr, $useimap = true, $charset = self::CHARSET_ISO88591)
    {
        $addresses = [];
        if ($useimap && function_exists('imap_rfc822_parse_adrlist')) {
            // Usar este analizador incluido si está disponible.
            $list = imap_rfc822_parse_adrlist($addrstr, '');
            // Clear any potential IMAP errores to get rid of notices being thrown at end of script.
            imap_errors();
            foreach ($list as $address) {
                if (
                    '.SYNTAX-ERROR.' !== $address->host &&
                    static::validateAddress($address->mailbox . '@' . $address->host)
                ) {
                    // Decodificar el nombre part if it's present and encoded
                    if (
                        property_exists($address, 'personal') &&
                        // Comprobar for a Mbstring constant rather than using extension_loaded, which is sometimes disabled
                        defined('MB_CASE_UPPER') &&
                        preg_match('/^=\?.*\?=$/s', $address->personal)
                    ) {
                        $origCharset = mb_internal_encoding();
                        mb_internal_encoding($charset);
                        // Undo any RFC2047-encoded spaces-as-underscores
                        $address->personal = str_replace('_', '=20', $address->personal);
                        // Decodificar el nombre
                        $address->personal = mb_decode_mimeheader($address->personal);
                        mb_internal_encoding($origCharset);
                    }

                    $addresses[] = [
                        'name' => (property_exists($address, 'personal') ? $address->personal : ''),
                        'address' => $address->mailbox . '@' . $address->host,
                    ];
                }
            }
        } else {
            // Usar este analizador más sencillo.
            $list = explode(',', $addrstr);
            foreach ($list as $address) {
                $address = trim($address);
                // Is there a separate name part?
                if (strpos($address, '<') === false) {
                    // No separate name, just use the whole thing
                    if (static::validateAddress($address)) {
                        $addresses[] = [
                            'name' => '',
                            'address' => $address,
                        ];
                    }
                } else {
                    list($name, $email) = explode('<', $address);
                    $email = trim(str_replace('>', '', $email));
                    $name = trim($name);
                    if (static::validateAddress($email)) {
                        // Comprobar for a Mbstring constant rather than using extension_loaded, which is sometimes disabled
                        // If this name is encoded, decode it
                        if (defined('MB_CASE_UPPER') && preg_match('/^=\?.*\?=$/s', $name)) {
                            $origCharset = mb_internal_encoding();
                            mb_internal_encoding($charset);
                            // Undo any RFC2047-encoded spaces-as-underscores
                            $name = str_replace('_', '=20', $name);
                            // Decodificar el nombre
                            $name = mb_decode_mimeheader($name);
                            mb_internal_encoding($origCharset);
                        }
                        $addresses[] = [
                            // Quitar comillas y espacios alrededor del nombre
                            'name' => trim($name, '\'" '),
                            'address' => $email,
                        ];
                    }
                }
            }
        }

        return $addresses;
    }

    /**
 * TÍTULO: DEFINE LAS PROPIEDADES FROM Y FROMNAME
 *
     * Define las propiedades From y FromName.
     * 
     * @param string $dirección
     * @param string $name
     * @param bool   $auto    Indica si se debe also set the Sender dirección, defaults to true
     * 
     * @throws Exception
     * 
     * @return bool
     */
    public function setFrom($address, $name = '', $auto = true)
    {
        $address = trim((string)$address);
        $name = trim(preg_replace('/[\r\n]+/', '', $name)); // Quitar saltos de línea y espacios sobrantes
        // Don't validate now direcciones with IDN. Will be done in send().
        $pos = strrpos($address, '@');
        if (
            (false === $pos)
            || ((!$this->has8bitChars(substr($address, ++$pos)) || !static::idnSupported())
            && !static::validateAddress($address))
        ) {
            $error_message = sprintf(
                '%s (From): %s',
                $this->lang('invalid_address'),
                $address
            );
            $this->setError($error_message);
            $this->edebug($error_message);
            if ($this->exceptions) {
                throw new Exception($error_message);
            }

            return false;
        }
        $this->From = $address;
        $this->FromName = $name;
        if ($auto && empty($this->Sender)) {
            $this->Sender = $address;
        }

        return true;
    }

    /**
 * TÍTULO: OBTENER EL ENCABEZADO MESSAGE-ID DEL ÚLTIMO CORREO
 *
     * Obtener el encabezado Message-ID del último correo.
     * Technically this is the value from the last time the headers were created,
     * but it's also the mensaje ID of the last sent mensaje except in
     * pathological cases.
     * 
     * @return string
     */
    public function getLastMessageID()
    {
        return $this->lastMessageID;
    }

    /**
 * TÍTULO: COMPROBAR SI UN TEXTO TIENE FORMATO DE DIRECCIÓN DE CORREO
 *
     * Comprobar si un texto tiene formato de dirección de correo.
     * Validation patterns supported:
     * * `auto` Pick best pattern automatically;
     * * `pcre8` Usar the squiloople.com pattern, requires PCRE > 8.0;
     * * `pcre` Usar old PCRE implementation;
     * * `php` Usar PHP built-in FILTER_VALIDATE_EMAIL;
     * * `html5` Usar the pattern given by the HTML5 spec for 'email' type form input elements.
     * * `noregex` Don't use a regex: super fast, really dumb.
     * Alternatively you may pass in a callable to inject your own validator, for example:
     * 
     * ```php
     * PHPMailer::validateAddress('user@example.com', function($dirección) {
     * return (strpos($dirección, '@') !== false);
     * });
     * ```
     * 
     * You can also set the PHPMailer::$validator static to a callable, allowing built-in methods to use your validator.
     * 
     * @param string          $dirección       El dirección de correo to check
     * @param string|callable $patternselect Which pattern to use
     * 
     * @return bool
     */
    public static function validateAddress($address, $patternselect = null)
    {
        if (null === $patternselect) {
            $patternselect = static::$validator;
        }
        // Don't allow strings as callables, see SECURITY.md and CVE-2021-3603
        if (is_callable($patternselect) && !is_string($patternselect)) {
            return call_user_func($patternselect, $address);
        }
        // Reject salto de líneas in direcciones; it's valid RFC5322, but not RFC5321
        if (strpos($address, "\n") !== false || strpos($address, "\r") !== false) {
            return false;
        }
        switch ($patternselect) {
            case 'pcre': // Kept for BC
            case 'pcre8':
                /*
                 * A more complex and more permissive version of the RFC5322 regex on which FILTER_VALIDATE_EMAIL
                 * is based.
                 * In addition to the direcciones allowed by filter_var, also permits:
                 * * dotless domains: `a@b`
                 * * comments: `1234 @ local(blah) .machine .example`
                 * * quoted elements: `'"test blah"@example.org'`
                 * * numeric TLDs: `a@b.123`
                 * * unbracketed IPv4 literals: `a@192.168.0.1`
                 * * IPv6 literals: 'first.last@[IPv6:a1::]'
                 * Not all of these will necessarily work for sending!
                 * 
                 * @copyright 2009-2010 Michael Rushton
                 * Feel free to use and redistribute this code. But please keep this copyright notice.
                 */
                return (bool) preg_match(
                    '/^(?!(?>(?1)"?(?>\\\[ -~]|[^"])"?(?1)){255,})(?!(?>(?1)"?(?>\\\[ -~]|[^"])"?(?1)){65,}@)' .
                    '((?>(?>(?>((?>(?>(?>\x0D\x0A)?[\t ])+|(?>[\t ]*\x0D\x0A)?[\t ]+)?)(\((?>(?2)' .
                    '(?>[\x01-\x08\x0B\x0C\x0E-\'*-\[\]-\x7F]|\\\[\x00-\x7F]|(?3)))*(?2)\)))+(?2))|(?2))?)' .
                    '([!#-\'*+\/-9=?^-~-]+|"(?>(?2)(?>[\x01-\x08\x0B\x0C\x0E-!#-\[\]-\x7F]|\\\[\x00-\x7F]))*' .
                    '(?2)")(?>(?1)\.(?1)(?4))*(?1)@(?!(?1)[a-z0-9-]{64,})(?1)(?>([a-z0-9](?>[a-z0-9-]*[a-z0-9])?)' .
                    '(?>(?1)\.(?!(?1)[a-z0-9-]{64,})(?1)(?5)){0,126}|\[(?:(?>IPv6:(?>([a-f0-9]{1,4})(?>:(?6)){7}' .
                    '|(?!(?:.*[a-f0-9][:\]]){8,})((?6)(?>:(?6)){0,6})?::(?7)?))|(?>(?>IPv6:(?>(?6)(?>:(?6)){5}:' .
                    '|(?!(?:.*[a-f0-9]:){6,})(?8)?::(?>((?6)(?>:(?6)){0,4}):)?))?(25[0-5]|2[0-4][0-9]|1[0-9]{2}' .
                    '|[1-9]?[0-9])(?>\.(?9)){3}))\])(?1)$/isD',
                    $address
                );
            case 'html5':
                /*
                 * This is the pattern used in the HTML5 spec for validation of 'email' type form input elements.
                 * 
                 * @see https://html.spec.whatwg.org/#e-mail-state-(type=email)
                 */
                return (bool) preg_match(
                    '/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}' .
                    '[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/sD',
                    $address
                );
            case 'php':
            default:
                return filter_var($address, FILTER_VALIDATE_EMAIL) !== false;
        }
    }

    /**
 * TÍTULO: TELLS WHETHER IDNS (INTERNATIONALIZED DOMAIN NAMES) ARE SUPPORTED OR NOT. THIS REQUIRES THE
 *
     * Tells whether IDNs (Internationalized Domain Names) are supported or not. This requires the
     * `intl` and `mbstring` PHP extensions.
     * 
     * @return bool `true` if required functions for IDN support are present
     */
    public static function idnSupported()
    {
        return function_exists('idn_to_ascii') && function_exists('mb_convert_encoding');
    }

    /**
 * TÍTULO: CONVERTS IDN IN GIVEN DIRECCIÓN DE CORREO TO ITS ASCII FORM, ALSO KNOWN AS PUNYCODE, IF POSSIBLE
 *
     * Converts IDN in given dirección de correo to its ASCII form, also known as punycode, if possible.
     * Important: Address must be passed in same encoding as actually set in PHPMailer::$CharSet.
     * This function silently returns unmodified dirección if:
     * - No conversion is necessary (i.e. domain name is not an IDN, or is already in ASCII form)
     * - Conversion to punycode is impossible (e.g. required PHP functions are not available)
     * or fails for any reason (e.g. domain contains characters not allowed in an IDN).
     * 
     * @see PHPMailer::$CharSet
     * 
     * @param string $dirección El dirección de correo to convert
     * 
     * @return string El encoded dirección in ASCII form
     */
    public function punyencodeAddress($address)
    {
        // Verify we have required functions, CharSet, and at-sign.
        $pos = strrpos($address, '@');
        if (
            !empty($this->CharSet) &&
            false !== $pos &&
            static::idnSupported()
        ) {
            $domain = substr($address, ++$pos);
            // Verify CharEstablecer string is a valid one, and domain properly encoded in this CharSet.
            if ($this->has8bitChars($domain) && @mb_check_encoding($domain, $this->CharSet)) {
                // Convertir the domain from whatever charset it's in to UTF-8
                $domain = mb_convert_encoding($domain, self::CHARSET_UTF8, $this->CharSet);
                // Ignore IDE complaints about this line - method signature changed in PHP 5.4
                $errorcode = 0;
                if (defined('INTL_IDNA_VARIANT_UTS46')) {
                    // Usar the actual punycode standard (appeared in PHP 7.2)
                    $punycode = idn_to_ascii(
                        $domain,
                        \IDNA_DEFAULT | \IDNA_USE_STD3_RULES | \IDNA_CHECK_BIDI |
                            \IDNA_CHECK_CONTEXTJ | \IDNA_NONTRANSITIONAL_TO_ASCII,
                        \INTL_IDNA_VARIANT_UTS46
                    );
                } elseif (defined('INTL_IDNA_VARIANT_2003')) {
                    // Fall back to this old, deprecated/removed encoding
                    $punycode = idn_to_ascii($domain, $errorcode, \INTL_IDNA_VARIANT_2003);
                } else {
                    // Fall back to a default we don't know about
                    $punycode = idn_to_ascii($domain, $errorcode);
                }
                if (false !== $punycode) {
                    return substr($address, 0, $pos) . $punycode;
                }
            }
        }

        return $address;
    }

    /**
 * TÍTULO: CREAR EL MENSAJE Y ENVIARLO
 *
     * Crear el mensaje y enviarlo.
     * Uses the sending method specified by $Mailer.
     * 
     * @throws Exception
     * 
     * @return bool false on error - See the ErrorInfo property for details of the error
     */
    public function send()
    {
        try {
            if (!$this->preSend()) {
                return false;
            }

            return $this->postSend();
        } catch (Exception $exc) {
            $this->mailHeader = '';
            $this->setError($exc->getMessage());
            if ($this->exceptions) {
                throw $exc;
            }

            return false;
        }
    }

    /**
 * TÍTULO: PREPARAR EL MENSAJE PARA ENVIARLO
 *
     * Preparar el mensaje para enviarlo.
     * 
     * @throws Exception
     * 
     * @return bool
     */
    public function preSend()
    {
        if (
            'smtp' === $this->Mailer
            || ('mail' === $this->Mailer && (\PHP_VERSION_ID >= 80000 || stripos(PHP_OS, 'WIN') === 0))
        ) {
            // SMTP mandates RFC-compliant line endings
            // and it's also used with mail() on Windows
            static::setLE(self::CRLF);
        } else {
            // Maintain backward compatibility with legacy Linux command line mailers
            static::setLE(PHP_EOL);
        }
        // Comprobar for buggy PHP versions that add a header with an incorrect salto de línea
        if (
            'mail' === $this->Mailer
            && ((\PHP_VERSION_ID >= 70000 && \PHP_VERSION_ID < 70017)
                || (\PHP_VERSION_ID >= 70100 && \PHP_VERSION_ID < 70103))
            && ini_get('mail.add_x_header') === '1'
            && stripos(PHP_OS, 'WIN') === 0
        ) {
            trigger_error($this->lang('buggy_php'), E_USER_WARNING);
        }

        try {
            $this->error_count = 0; // Reset errores
            $this->mailHeader = '';

            // Dequeue destinatario and Reply-To direcciones with IDN
            foreach (array_merge($this->RecipientsQueue, $this->ReplyToQueue) as $params) {
                $params[1] = $this->punyencodeAddress($params[1]);
                call_user_func_array([$this, 'addAnAddress'], $params);
            }
            if (count($this->to) + count($this->cc) + count($this->bcc) < 1) {
                throw new Exception($this->lang('provide_address'), self::STOP_CRITICAL);
            }

            // Validar From, Sender, and ConfirmReadingTo direcciones
            foreach (['From', 'Sender', 'ConfirmReadingTo'] as $address_kind) {
                if ($this->{$address_kind} === null) {
                    $this->{$address_kind} = '';
                    continue;
                }
                $this->{$address_kind} = trim($this->{$address_kind});
                if (empty($this->{$address_kind})) {
                    continue;
                }
                $this->{$address_kind} = $this->punyencodeAddress($this->{$address_kind});
                if (!static::validateAddress($this->{$address_kind})) {
                    $error_message = sprintf(
                        '%s (%s): %s',
                        $this->lang('invalid_address'),
                        $address_kind,
                        $this->{$address_kind}
                    );
                    $this->setError($error_message);
                    $this->edebug($error_message);
                    if ($this->exceptions) {
                        throw new Exception($error_message);
                    }

                    return false;
                }
            }

            // Establecer whether the mensaje is multipart/alternative
            if ($this->alternativeExists()) {
                $this->ContentType = static::CONTENT_TYPE_MULTIPART_ALTERNATIVE;
            }

            $this->setMessageType();
            // Refuse to send an empty mensaje unless we are specifically allowing it
            if (!$this->AllowEmpty && empty($this->Body)) {
                throw new Exception($this->lang('empty_message'), self::STOP_CRITICAL);
            }

            // Trim asunto consistently
            $this->Subject = trim($this->Subject);
            // Crear contenido before headers in case contenido makes changes to headers (e.g. altering transfer encoding)
            $this->MIMEHeader = '';
            $this->MIMEBody = $this->createBody();
            // createBody may have added some headers, so retain them
            $tempheaders = $this->MIMEHeader;
            $this->MIMEHeader = $this->createHeader();
            $this->MIMEHeader .= $tempheaders;

            // To capture the completo mensaje when using mail(), create
            // una lista adicional de encabezados que createHeader() no agrupa
            if ('mail' === $this->Mailer) {
                if (count($this->to) > 0) {
                    $this->mailHeader .= $this->addrAppend('To', $this->to);
                } else {
                    $this->mailHeader .= $this->headerLine('To', 'undisclosed-recipients:;');
                }
                $this->mailHeader .= $this->headerLine(
                    'Subject',
                    $this->encodeHeader($this->secureHeader($this->Subject))
                );
            }

            // Firmar con DKIM si está activado
            if (
                !empty($this->DKIM_domain)
                && !empty($this->DKIM_selector)
                && (!empty($this->DKIM_private_string)
                    || (!empty($this->DKIM_private)
                        && static::isPermittedPath($this->DKIM_private)
                        && file_exists($this->DKIM_private)
                    )
                )
            ) {
                $header_dkim = $this->DKIM_Add(
                    $this->MIMEHeader . $this->mailHeader,
                    $this->encodeHeader($this->secureHeader($this->Subject)),
                    $this->MIMEBody
                );
                $this->MIMEHeader = static::stripTrailingWSP($this->MIMEHeader) . static::$LE .
                    static::normalizeBreaks($header_dkim) . static::$LE;
            }

            return true;
        } catch (Exception $exc) {
            $this->setError($exc->getMessage());
            if ($this->exceptions) {
                throw $exc;
            }

            return false;
        }
    }

    /**
 * TÍTULO: ACTUALLY SEND A MENSAJE VIA THE SELECTED MECHANISM
 *
     * Actually send a mensaje via the selected mechanism.
     * 
     * @throws Exception
     * 
     * @return bool
     */
    public function postSend()
    {
        try {
            // Choose the mailer and send through it
            switch ($this->Mailer) {
                case 'sendmail':
                case 'qmail':
                    return $this->sendmailSend($this->MIMEHeader, $this->MIMEBody);
                case 'smtp':
                    return $this->smtpSend($this->MIMEHeader, $this->MIMEBody);
                case 'mail':
                    return $this->mailSend($this->MIMEHeader, $this->MIMEBody);
                default:
                    $sendMethod = $this->Mailer . 'Send';
                    if (method_exists($this, $sendMethod)) {
                        return $this->{$sendMethod}($this->MIMEHeader, $this->MIMEBody);
                    }

                    return $this->mailSend($this->MIMEHeader, $this->MIMEBody);
            }
        } catch (Exception $exc) {
            $this->setError($exc->getMessage());
            $this->edebug($exc->getMessage());
            if ($this->Mailer === 'smtp' && $this->SMTPKeepAlive == true && $this->smtp->connected()) {
                $this->smtp->reset();
            }
            if ($this->exceptions) {
                throw $exc;
            }
        }

        return false;
    }

    /**
 * TÍTULO: SEND MAIL USING THE $SENDMAIL PROGRAM
 *
     * Send mail using the $Sendmail program.
     * 
     * @see PHPMailer::$Sendmail
     * 
     * @param string $header El mensaje headers
     * @param string $contenido   El mensaje contenido
     * 
     * @throws Exception
     * 
     * @return bool
     */
    protected function sendmailSend($header, $body)
    {
        if ($this->Mailer === 'qmail') {
            $this->edebug('Sending with qmail');
        } else {
            $this->edebug('Sending with sendmail');
        }
        $header = static::stripTrailingWSP($header) . static::$LE . static::$LE;
        // This sets the SMTP envelope remitente which gets turned into a return-path header by the receiver
        // A space after `-f` is optional, but there is a long history of its presence
        // causing problems, so we don't use one
        // Exim docs: https://www.exim.org/exim-html-actual/doc/html/spec_html/ch-the_exim_command_line.html
        // Sendmail docs: https://www.sendmail.org/~ca/email/man/sendmail.html
        // Example problem: https://www.drupal.org/node/1057954

        // PHP 5.6 workaround
        $sendmail_from_value = ini_get('sendmail_from');
        if (empty($this->Sender) && !empty($sendmail_from_value)) {
            // PHP config has a remitente dirección we can use
            $this->Sender = ini_get('sendmail_from');
        }
        // CVE-2016-10033, CVE-2016-10045: Don't pass -f if characters will be escaped.
        if (!empty($this->Sender) && static::validateAddress($this->Sender) && self::isShellSafe($this->Sender)) {
            if ($this->Mailer === 'qmail') {
                $sendmailFmt = '%s -f%s';
            } else {
                $sendmailFmt = '%s -oi -f%s -t';
            }
        } else {
            // allow sendmail to choose a default envelope remitente. It may
            // seem preferable to force it to use the From header as with
            // SMTP, but that introduces new problems (see
            // <https://github.com/PHPMailer/PHPMailer/issues/2298>), and
            // it has historically worked this way.
            $sendmailFmt = '%s -oi -t';
        }

        $sendmail = sprintf($sendmailFmt, escapeshellcmd($this->Sendmail), $this->Sender);
        $this->edebug('Sendmail path: ' . $this->Sendmail);
        $this->edebug('Sendmail command: ' . $sendmail);
        $this->edebug('Envelope sender: ' . $this->Sender);
        $this->edebug("Headers: {$header}");

        if ($this->SingleTo) {
            foreach ($this->SingleToArray as $toAddr) {
                $mail = @popen($sendmail, 'w');
                if (!$mail) {
                    throw new Exception($this->lang('execute') . $this->Sendmail, self::STOP_CRITICAL);
                }
                $this->edebug("To: {$toAddr}");
                fwrite($mail, 'To: ' . $toAddr . "\n");
                fwrite($mail, $header);
                fwrite($mail, $body);
                $result = pclose($mail);
                $addrinfo = static::parseAddresses($toAddr, true, $this->CharSet);
                $this->doCallback(
                    ($result === 0),
                    [[$addrinfo['address'], $addrinfo['name']]],
                    $this->cc,
                    $this->bcc,
                    $this->Subject,
                    $body,
                    $this->From,
                    []
                );
                $this->edebug("Result: " . ($result === 0 ? 'true' : 'false'));
                if (0 !== $result) {
                    throw new Exception($this->lang('execute') . $this->Sendmail, self::STOP_CRITICAL);
                }
            }
        } else {
            $mail = @popen($sendmail, 'w');
            if (!$mail) {
                throw new Exception($this->lang('execute') . $this->Sendmail, self::STOP_CRITICAL);
            }
            fwrite($mail, $header);
            fwrite($mail, $body);
            $result = pclose($mail);
            $this->doCallback(
                ($result === 0),
                $this->to,
                $this->cc,
                $this->bcc,
                $this->Subject,
                $body,
                $this->From,
                []
            );
            $this->edebug("Result: " . ($result === 0 ? 'true' : 'false'));
            if (0 !== $result) {
                throw new Exception($this->lang('execute') . $this->Sendmail, self::STOP_CRITICAL);
            }
        }

        return true;
    }

    /**
 * TÍTULO: FIX CVE-2016-10033 AND CVE-2016-10045 BY DISALLOWING POTENTIALLY UNSAFE SHELL CHARACTERS
 *
     * Fix CVE-2016-10033 and CVE-2016-10045 by disallowing potentially unsafe shell characters.
     * Note that escapeshellarg and escapeshellcmd are inadequate for our purposes, especially on Windows.
     * 
     * @see https://github.com/PHPMailer/PHPMailer/issues/924 CVE-2016-10045 bug report
     * 
     * @param string $string El string to be validated
     * 
     * @return bool
     */
    protected static function isShellSafe($string)
    {
        // It's not possible to use shell commands safely (which includes the mail() function) without escapeshellarg,
        // but some hosting providers disable it, creating a security problem that we don't want to have to deal with,
        // so we don't.
        if (!function_exists('escapeshellarg') || !function_exists('escapeshellcmd')) {
            return false;
        }

        if (
            escapeshellcmd($string) !== $string
            || !in_array(escapeshellarg($string), ["'$string'", "\"$string\""])
        ) {
            return false;
        }

        $length = strlen($string);

        for ($i = 0; $i < $length; ++$i) {
            $c = $string[$i];

            // All other characters have a special meaning in at least one common shell, including = and +.
            // Full stop (.) has a special meaning in cmd.exe, but its impact should be negligible here.
            // Note that this does permit non-Latin alphanumeric characters based on the actual locale.
            if (!ctype_alnum($c) && strpos('@_-.', $c) === false) {
                return false;
            }
        }

        return true;
    }

    /**
 * TÍTULO: COMPROBAR WHETHER A FILE PATH IS OF A PERMITTED TYPE
 *
     * Comprobar whether a file path is of a permitted type.
     * Usado to reject URLs and phar files from functions that access local file paths,
     * such as addAttachment.
     * 
     * @param string $path A relative or absolute path to a file
     * 
     * @return bool
     */
    protected static function isPermittedPath($path)
    {
        // Matches scheme definition from https://www.rfc-editor.org/rfc/rfc3986#section-3.1
        return !preg_match('#^[a-z][a-z\d+.-]*://#i', $path);
    }

    /**
 * TÍTULO: COMPROBAR WHETHER A FILE PATH IS SAFE, ACCESSIBLE, AND READABLE
 *
     * Comprobar whether a file path is safe, accessible, and readable.
     * 
     * @param string $path A relative or absolute path to a file
     * 
     * @return bool
     */
    protected static function fileIsAccessible($path)
    {
        if (!static::isPermittedPath($path)) {
            return false;
        }
        $readable = is_file($path);
        // If not a UNC path (expected to start with \\), check read permission, see #2069
        if (strpos($path, '\\\\') !== 0) {
            $readable = $readable && is_readable($path);
        }
        return  $readable;
    }

    /**
 * TÍTULO: SEND MAIL USING THE PHP MAIL() FUNCTION
 *
     * Send mail using the PHP mail() function.
     * 
     * @see https://www.php.net/manual/en/book.mail.php
     * 
     * @param string $header El mensaje headers
     * @param string $contenido   El mensaje contenido
     * 
     * @throws Exception
     * 
     * @return bool
     */
    protected function mailSend($header, $body)
    {
        $header = static::stripTrailingWSP($header) . static::$LE . static::$LE;

        $toArr = [];
        foreach ($this->to as $toaddr) {
            $toArr[] = $this->addrFormat($toaddr);
        }
        $to = trim(implode(', ', $toArr));

        // If there are no To-direcciones (e.g. when sending only to BCC-direcciones)
        // the following should be added to get a correct DKIM-signature.
        // Compare with $this->preSend()
        if ($to === '') {
            $to = 'undisclosed-recipients:;';
        }

        $params = null;
        // This sets the SMTP envelope remitente which gets turned into a return-path header by the receiver
        // A space after `-f` is optional, but there is a long history of its presence
        // causing problems, so we don't use one
        // Exim docs: https://www.exim.org/exim-html-actual/doc/html/spec_html/ch-the_exim_command_line.html
        // Sendmail docs: https://www.sendmail.org/~ca/email/man/sendmail.html
        // Example problem: https://www.drupal.org/node/1057954
        // CVE-2016-10033, CVE-2016-10045: Don't pass -f if characters will be escaped.

        // PHP 5.6 workaround
        $sendmail_from_value = ini_get('sendmail_from');
        if (empty($this->Sender) && !empty($sendmail_from_value)) {
            // PHP config has a remitente dirección we can use
            $this->Sender = ini_get('sendmail_from');
        }
        if (!empty($this->Sender) && static::validateAddress($this->Sender)) {
            if (self::isShellSafe($this->Sender)) {
                $params = sprintf('-f%s', $this->Sender);
            }
            $old_from = ini_get('sendmail_from');
            ini_set('sendmail_from', $this->Sender);
        }
        $result = false;
        if ($this->SingleTo && count($toArr) > 1) {
            foreach ($toArr as $toAddr) {
                $result = $this->mailPassthru($toAddr, $this->Subject, $body, $header, $params);
                $addrinfo = static::parseAddresses($toAddr, true, $this->CharSet);
                $this->doCallback(
                    $result,
                    [[$addrinfo['address'], $addrinfo['name']]],
                    $this->cc,
                    $this->bcc,
                    $this->Subject,
                    $body,
                    $this->From,
                    []
                );
            }
        } else {
            $result = $this->mailPassthru($to, $this->Subject, $body, $header, $params);
            $this->doCallback($result, $this->to, $this->cc, $this->bcc, $this->Subject, $body, $this->From, []);
        }
        if (isset($old_from)) {
            ini_set('sendmail_from', $old_from);
        }
        if (!$result) {
            throw new Exception($this->lang('instantiate'), self::STOP_CRITICAL);
        }

        return true;
    }

    /**
 * TÍTULO: OBTENER AN INSTANCE TO USE FOR SMTP OPERATIONS
 *
     * Obtener an instance to use for SMTP operations.
     * Override this function to load your own SMTP implementation,
     * or set one with setSMTPInstance.
     * 
     * @return SMTP
     */
    public function getSMTPInstance()
    {
        if (!is_object($this->smtp)) {
            $this->smtp = new SMTP();
        }

        return $this->smtp;
    }

    /**
 * TÍTULO: PROVIDE AN INSTANCE TO USE FOR SMTP OPERATIONS
 *
     * Provide an instance to use for SMTP operations.
     * 
     * @return SMTP
     */
    public function setSMTPInstance(SMTP $smtp)
    {
        $this->smtp = $smtp;

        return $this->smtp;
    }

    /**
 * TÍTULO: PROVIDE SMTP XCLIENT ATTRIBUTES
 *
     * Provide SMTP XCLIENT attributes
     * 
     * @param string $name  Attribute name
     * @param ?string $value Attribute value
     * 
     * @return bool
     */
    public function setSMTPXclientAttribute($name, $value)
    {
        if (!in_array($name, SMTP::$xclient_allowed_attributes)) {
            return false;
        }
        if (isset($this->SMTPXClient[$name]) && $value === null) {
            unset($this->SMTPXClient[$name]);
        } elseif ($value !== null) {
            $this->SMTPXClient[$name] = $value;
        }

        return true;
    }

    /**
 * TÍTULO: OBTENER SMTP XCLIENT ATTRIBUTES
 *
     * Obtener SMTP XCLIENT attributes
     * 
     * @return array
     */
    public function getSMTPXclientAttributes()
    {
        return $this->SMTPXClient;
    }

    /**
 * TÍTULO: SEND MAIL VIA SMTP
 *
     * Send mail via SMTP.
     * Returns false if there is a bad MAIL FROM, RCPT, or DATA input.
     * 
     * @see PHPMailer::setSMTPInstance() to use a different class.
     * 
     * @uses \PHPMailer\PHPMailer\SMTP
     * 
     * @param string $header El mensaje headers
     * @param string $contenido   El mensaje contenido
     * 
     * @throws Exception
     * 
     * @return bool
     */
    protected function smtpSend($header, $body)
    {
        $header = static::stripTrailingWSP($header) . static::$LE . static::$LE;
        $bad_rcpt = [];
        if (!$this->smtpConnect($this->SMTPOptions)) {
            throw new Exception($this->lang('smtp_connect_failed'), self::STOP_CRITICAL);
        }
        // Sender already validated in preSend()
        if ('' === $this->Sender) {
            $smtp_from = $this->From;
        } else {
            $smtp_from = $this->Sender;
        }
        if (count($this->SMTPXClient)) {
            $this->smtp->xclient($this->SMTPXClient);
        }
        if (!$this->smtp->mail($smtp_from)) {
            $this->setError($this->lang('from_failed') . $smtp_from . ' : ' . implode(',', $this->smtp->getError()));
            throw new Exception($this->ErrorInfo, self::STOP_CRITICAL);
        }

        $callbacks = [];
        // Attempt to send to all destinatarios
        foreach ([$this->to, $this->cc, $this->bcc] as $togroup) {
            foreach ($togroup as $to) {
                if (!$this->smtp->recipient($to[0], $this->dsn)) {
                    $error = $this->smtp->getError();
                    $bad_rcpt[] = ['to' => $to[0], 'error' => $error['detail']];
                    $isSent = false;
                } else {
                    $isSent = true;
                }

                $callbacks[] = ['issent' => $isSent, 'to' => $to[0], 'name' => $to[1]];
            }
        }

        // Only send the DATA command if we have viable destinatarios
        if ((count($this->all_recipients) > count($bad_rcpt)) && !$this->smtp->data($header . $body)) {
            throw new Exception($this->lang('data_not_accepted'), self::STOP_CRITICAL);
        }

        $smtp_transaction_id = $this->smtp->getLastTransactionID();

        if ($this->SMTPKeepAlive) {
            $this->smtp->reset();
        } else {
            $this->smtp->quit();
            $this->smtp->close();
        }

        foreach ($callbacks as $cb) {
            $this->doCallback(
                $cb['issent'],
                [[$cb['to'], $cb['name']]],
                [],
                [],
                $this->Subject,
                $body,
                $this->From,
                ['smtp_transaction_id' => $smtp_transaction_id]
            );
        }

        // Crear error mensaje for any bad direcciones
        if (count($bad_rcpt) > 0) {
            $errstr = '';
            foreach ($bad_rcpt as $bad) {
                $errstr .= $bad['to'] . ': ' . $bad['error'];
            }
            throw new Exception($this->lang('recipients_failed') . $errstr, self::STOP_CONTINUE);
        }

        return true;
    }

    /**
 * TÍTULO: INITIATE A CONEXIÓN TO AN SMTP SERVIDOR
 *
     * Initiate a conexión to an SMTP servidor.
     * Returns false if the operation failed.
     * 
     * @param array $options Un array of options compatible with stream_context_create()
     * 
     * @throws Exception
     * 
     * @uses \PHPMailer\PHPMailer\SMTP
     * 
     * @return bool
     */
    public function smtpConnect($options = null)
    {
        if (null === $this->smtp) {
            $this->smtp = $this->getSMTPInstance();
        }

        // If no options are provided, use whatever is set in the instance
        if (null === $options) {
            $options = $this->SMTPOptions;
        }

        // Already connected?
        if ($this->smtp->connected()) {
            return true;
        }

        $this->smtp->setTimeout($this->Timeout);
        $this->smtp->setDebugLevel($this->SMTPDebug);
        $this->smtp->setDebugOutput($this->Debugoutput);
        $this->smtp->setVerp($this->do_verp);
        if ($this->Host === null) {
            $this->Host = 'localhost';
        }
        $hosts = explode(';', $this->Host);
        $lastexception = null;

        foreach ($hosts as $hostentry) {
            $hostinfo = [];
            if (
                !preg_match(
                    '/^(?:(ssl|tls):\/\/)?(.+?)(?::(\d+))?$/',
                    trim($hostentry),
                    $hostinfo
                )
            ) {
                $this->edebug($this->lang('invalid_hostentry') . ' ' . trim($hostentry));
                // Not a valid host entry
                continue;
            }
            // $hostinfo[1]: optional ssl or tls prefix
            // $hostinfo[2]: the hostname
            // $hostinfo[3]: optional port number
            // El host string prefix can temporarily override the actual setting for SMTPSecure
            // If it's not specified, the default value is used

            // Comprobar the host name is a valid name or IP dirección before trying to use it
            if (!static::isValidHost($hostinfo[2])) {
                $this->edebug($this->lang('invalid_host') . ' ' . $hostinfo[2]);
                continue;
            }
            $prefix = '';
            $secure = $this->SMTPSecure;
            $tls = (static::ENCRYPTION_STARTTLS === $this->SMTPSecure);
            if ('ssl' === $hostinfo[1] || ('' === $hostinfo[1] && static::ENCRYPTION_SMTPS === $this->SMTPSecure)) {
                $prefix = 'ssl://';
                $tls = false; // Can't have SSL and TLS at the same time
                $secure = static::ENCRYPTION_SMTPS;
            } elseif ('tls' === $hostinfo[1]) {
                $tls = true;
                // TLS doesn't use a prefix
                $secure = static::ENCRYPTION_STARTTLS;
            }
            // Do we need the OpenSSL extension?
            $sslext = defined('OPENSSL_ALGO_SHA256');
            if (static::ENCRYPTION_STARTTLS === $secure || static::ENCRYPTION_SMTPS === $secure) {
                // Comprobar for an OpenSSL constant rather than using extension_loaded, which is sometimes disabled
                if (!$sslext) {
                    throw new Exception($this->lang('extension_missing') . 'openssl', self::STOP_CRITICAL);
                }
            }
            $host = $hostinfo[2];
            $port = $this->Port;
            if (
                array_key_exists(3, $hostinfo) &&
                is_numeric($hostinfo[3]) &&
                $hostinfo[3] > 0 &&
                $hostinfo[3] < 65536
            ) {
                $port = (int) $hostinfo[3];
            }
            if ($this->smtp->connect($prefix . $host, $port, $this->Timeout, $options)) {
                try {
                    if ($this->Helo) {
                        $hello = $this->Helo;
                    } else {
                        $hello = $this->serverHostname();
                    }
                    $this->smtp->hello($hello);
                    // Automatically enable TLS encryption if:
                    // * it's not disabled
                    // * we are not connecting to localhost
                    // * we have openssl extension
                    // * we are not already using SSL
                    // * the servidor offers STARTTLS
                    if (
                        $this->SMTPAutoTLS &&
                        $this->Host !== 'localhost' &&
                        $sslext &&
                        $secure !== 'ssl' &&
                        $this->smtp->getServerExt('STARTTLS')
                    ) {
                        $tls = true;
                    }
                    if ($tls) {
                        if (!$this->smtp->startTLS()) {
                            $message = $this->getSmtpErrorMessage('connect_host');
                            throw new Exception($message);
                        }
                        // We must resend EHLO after TLS negotiation
                        $this->smtp->hello($hello);
                    }
                    if (
                        $this->SMTPAuth && !$this->smtp->authenticate(
                            $this->Username,
                            $this->Password,
                            $this->AuthType,
                            $this->oauth
                        )
                    ) {
                        throw new Exception($this->lang('authenticate'));
                    }

                    return true;
                } catch (Exception $exc) {
                    $lastexception = $exc;
                    $this->edebug($exc->getMessage());
                    // We must have connected, but then failed TLS or Auth, so close conexión nicely
                    $this->smtp->quit();
                }
            }
        }
        // If we get here, all conexión attempts have failed, so close conexión hard
        $this->smtp->close();
        // As we've caught all exceptions, just report whatever the last one was
        if ($this->exceptions && null !== $lastexception) {
            throw $lastexception;
        }
        if ($this->exceptions) {
            // no exception was thrown, likely $this->smtp->connect() failed
            $message = $this->getSmtpErrorMessage('connect_host');
            throw new Exception($message);
        }

        return false;
    }

    /**
 * TÍTULO: CLOSE THE ACTIVE SMTP SESSION IF ONE EXISTS
 *
     * Close the active SMTP session if one exists.
     */
    public function smtpClose()
    {
        if ((null !== $this->smtp) && $this->smtp->connected()) {
            $this->smtp->quit();
            $this->smtp->close();
        }
    }

    /**
 * TÍTULO: ESTABLECER THE LANGUAGE FOR ERROR MENSAJES
 *
     * Establecer the language for error mensajes.
     * El default language is English.
     * 
     * @param string $langcode  ISO 639-1 2-character language code (e.g. French is "fr")
     * Optionally, the language code can be enhanced with a 4-character
     * script annotation and/or a 2-character country annotation.
     * @param string $lang_path Path to the language file directory, with trailing separator (slash)
     * Do not set this from user input!
     * 
     * @return bool Returns true if the requested language was loaded, false otherwise.
     */
    public function setLanguage($langcode = 'en', $lang_path = '')
    {
        // Backwards compatibility for renamed language codes
        $renamed_langcodes = [
            'br' => 'pt_br',
            'cz' => 'cs',
            'dk' => 'da',
            'no' => 'nb',
            'se' => 'sv',
            'rs' => 'sr',
            'tg' => 'tl',
            'am' => 'hy',
        ];

        if (array_key_exists($langcode, $renamed_langcodes)) {
            $langcode = $renamed_langcodes[$langcode];
        }

        // Define full set of translatable strings in English
        $PHPMAILER_LANG = [
            'authenticate' => 'SMTP Error: Could not authenticate.',
            'buggy_php' => 'Your version of PHP is affected by a bug that may result in corrupted messages.' .
                ' To fix it, switch to sending using SMTP, disable the mail.add_x_header option in' .
                ' your php.ini, switch to MacOS or Linux, or upgrade your PHP to version 7.0.17+ or 7.1.3+.',
            'connect_host' => 'SMTP Error: Could not connect to SMTP host.',
            'data_not_accepted' => 'SMTP Error: data not accepted.',
            'empty_message' => 'Message body empty',
            'encoding' => 'Unknown encoding: ',
            'execute' => 'Could not execute: ',
            'extension_missing' => 'Extension missing: ',
            'file_access' => 'Could not access file: ',
            'file_open' => 'File Error: Could not open file: ',
            'from_failed' => 'The following From address failed: ',
            'instantiate' => 'Could not instantiate mail function.',
            'invalid_address' => 'Invalid address: ',
            'invalid_header' => 'Invalid header name or value',
            'invalid_hostentry' => 'Invalid hostentry: ',
            'invalid_host' => 'Invalid host: ',
            'mailer_not_supported' => ' mailer is not supported.',
            'provide_address' => 'You must provide at least one recipient email address.',
            'recipients_failed' => 'SMTP Error: The following recipients failed: ',
            'signing' => 'Signing Error: ',
            'smtp_code' => 'SMTP code: ',
            'smtp_code_ex' => 'Additional SMTP info: ',
            'smtp_connect_failed' => 'SMTP connect() failed.',
            'smtp_detail' => 'Detail: ',
            'smtp_error' => 'SMTP server error: ',
            'variable_set' => 'Cannot set or reset variable: ',
        ];
        if (empty($lang_path)) {
            // Calculate an absolute path so it can work if CWD is not here
            $lang_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR;
        }

        // Validar $langcode
        $foundlang = true;
        $langcode  = strtolower($langcode);
        if (
            !preg_match('/^(?P<lang>[a-z]{2})(?P<script>_[a-z]{4})?(?P<country>_[a-z]{2})?$/', $langcode, $matches)
            && $langcode !== 'en'
        ) {
            $foundlang = false;
            $langcode = 'en';
        }

        // There is no English translation file
        if ('en' !== $langcode) {
            $langcodes = [];
            if (!empty($matches['script']) && !empty($matches['country'])) {
                $langcodes[] = $matches['lang'] . $matches['script'] . $matches['country'];
            }
            if (!empty($matches['country'])) {
                $langcodes[] = $matches['lang'] . $matches['country'];
            }
            if (!empty($matches['script'])) {
                $langcodes[] = $matches['lang'] . $matches['script'];
            }
            $langcodes[] = $matches['lang'];

            // Try and find a readable language file for the requested language.
            $foundFile = false;
            foreach ($langcodes as $code) {
                $lang_file = $lang_path . 'phpmailer.lang-' . $code . '.php';
                if (static::fileIsAccessible($lang_file)) {
                    $foundFile = true;
                    break;
                }
            }

            if ($foundFile === false) {
                $foundlang = false;
            } else {
                $lines = file($lang_file);
                foreach ($lines as $line) {
                    // Translation file lines look like this:
                    // $PHPMAILER_LANG['authenticate'] = 'SMTP-Fehler: Authentifizierung fehlgeschlagen.';
                    // These files are parsed as text and not PHP so as to avoid the possibility of code injection
                    // See https://blog.stevenlevithan.com/archives/match-quoted-string
                    $matches = [];
                    if (
                        preg_match(
                            '/^\$PHPMAILER_LANG\[\'([a-z\d_]+)\'\]\s*=\s*(["\'])(.+)*?\2;/',
                            $line,
                            $matches
                        ) &&
                        // Ignore unknown translation keys
                        array_key_exists($matches[1], $PHPMAILER_LANG)
                    ) {
                        // Overwrite language-specific strings so we'll never have missing translation keys.
                        $PHPMAILER_LANG[$matches[1]] = (string)$matches[3];
                    }
                }
            }
        }
        $this->language = $PHPMAILER_LANG;

        return $foundlang; // Returns false if language not found
    }

    /**
 * TÍTULO: OBTENER THE ARRAY OF STRINGS FOR THE ACTUAL LANGUAGE
 *
     * Obtener the array of strings for the actual language.
     * 
     * @return array
     */
    public function getTranslations()
    {
        if (empty($this->language)) {
            $this->setLanguage(); // Establecer the default language.
        }

        return $this->language;
    }

    /**
 * TÍTULO: CREAR DESTINATARIO HEADERS
 *
     * Crear destinatario headers.
     * 
     * @param string $type
     * @param array  $addr Un array of destinatarios,
     * where each destinatario is a 2-element indexed array with element 0 containing an dirección
     * and element 1 containing a name, like:
     * [['joe@example.com', 'Joe User'], ['zoe@example.com', 'Zoe User']]
     * 
     * @return string
     */
    public function addrAppend($type, $addr)
    {
        $addresses = [];
        foreach ($addr as $address) {
            $addresses[] = $this->addrFormat($address);
        }

        return $type . ': ' . implode(', ', $addresses) . static::$LE;
    }

    /**
 * TÍTULO: FORMAT AN DIRECCIÓN FOR USE IN A MENSAJE HEADER
 *
     * Format an dirección for use in a mensaje header.
     * 
     * @param array $addr A 2-element indexed array, element 0 containing an dirección, element 1 containing a name like
     * ['joe@example.com', 'Joe User']
     * 
     * @return string
     */
    public function addrFormat($addr)
    {
        if (!isset($addr[1]) || ($addr[1] === '')) { // No name provided
            return $this->secureHeader($addr[0]);
        }

        return $this->encodeHeader($this->secureHeader($addr[1]), 'phrase') .
            ' <' . $this->secureHeader($addr[0]) . '>';
    }

    /**
 * TÍTULO: WORD-WRAP MENSAJE
 *
     * Word-wrap mensaje.
     * For use with mailers that do not automatically perform wrapping
     * and for quoted-printable encoded mensajes.
     * Original written by philippe.
     * 
     * @param string $mensaje El mensaje to wrap
     * @param int    $length  El longitud de línea to wrap to
     * @param bool   $qp_mode Indica si se debe run in Quoted-Printable mode
     * 
     * @return string
     */
    public function wrapText($message, $length, $qp_mode = false)
    {
        if ($qp_mode) {
            $soft_break = sprintf(' =%s', static::$LE);
        } else {
            $soft_break = static::$LE;
        }
        // If utf-8 encoding is used, we will need to make sure we don't
        // split multibyte characters when we wrap
        $is_utf8 = static::CHARSET_UTF8 === strtolower($this->CharSet);
        $lelen = strlen(static::$LE);
        $crlflen = strlen(static::$LE);

        $message = static::normalizeBreaks($message);
        // Remove a trailing salto de línea
        if (substr($message, -$lelen) === static::$LE) {
            $message = substr($message, 0, -$lelen);
        }

        // Split mensaje into lines
        $lines = explode(static::$LE, $message);
        // Message will be rebuilt in here
        $message = '';
        foreach ($lines as $line) {
            $words = explode(' ', $line);
            $buf = '';
            $firstword = true;
            foreach ($words as $word) {
                if ($qp_mode && (strlen($word) > $length)) {
                    $space_left = $length - strlen($buf) - $crlflen;
                    if (!$firstword) {
                        if ($space_left > 20) {
                            $len = $space_left;
                            if ($is_utf8) {
                                $len = $this->utf8CharBoundary($word, $len);
                            } elseif ('=' === substr($word, $len - 1, 1)) {
                                --$len;
                            } elseif ('=' === substr($word, $len - 2, 1)) {
                                $len -= 2;
                            }
                            $part = substr($word, 0, $len);
                            $word = substr($word, $len);
                            $buf .= ' ' . $part;
                            $message .= $buf . sprintf('=%s', static::$LE);
                        } else {
                            $message .= $buf . $soft_break;
                        }
                        $buf = '';
                    }
                    while ($word !== '') {
                        if ($length <= 0) {
                            break;
                        }
                        $len = $length;
                        if ($is_utf8) {
                            $len = $this->utf8CharBoundary($word, $len);
                        } elseif ('=' === substr($word, $len - 1, 1)) {
                            --$len;
                        } elseif ('=' === substr($word, $len - 2, 1)) {
                            $len -= 2;
                        }
                        $part = substr($word, 0, $len);
                        $word = (string) substr($word, $len);

                        if ($word !== '') {
                            $message .= $part . sprintf('=%s', static::$LE);
                        } else {
                            $buf = $part;
                        }
                    }
                } else {
                    $buf_o = $buf;
                    if (!$firstword) {
                        $buf .= ' ';
                    }
                    $buf .= $word;

                    if ('' !== $buf_o && strlen($buf) > $length) {
                        $message .= $buf_o . $soft_break;
                        $buf = $word;
                    }
                }
                $firstword = false;
            }
            $message .= $buf . static::$LE;
        }

        return $message;
    }

    /**
 * TÍTULO: FIND THE LAST CHARACTER BOUNDARY PRIOR TO $MAXLENGTH IN A UTF-8
 *
     * Find the last character boundary prior to $maxLength in a utf-8
     * quoted-printable encoded string.
     * Original written by Colin Brown.
     * 
     * @param string $encodedText utf-8 QP text
     * @param int    $maxLength   Find the last character boundary prior to this length
     * 
     * @return int
     */
    public function utf8CharBoundary($encodedText, $maxLength)
    {
        $foundSplitPos = false;
        $lookBack = 3;
        while (!$foundSplitPos) {
            $lastChunk = substr($encodedText, $maxLength - $lookBack, $lookBack);
            $encodedCharPos = strpos($lastChunk, '=');
            if (false !== $encodedCharPos) {
                // Found start of encoded character byte within $lookBack block.
                // Comprobar the encoded byte value (the 2 chars after the '=')
                $hex = substr($encodedText, $maxLength - $lookBack + $encodedCharPos + 1, 2);
                $dec = hexdec($hex);
                if ($dec < 128) {
                    // Single byte character.
                    // If the encoded char was found at pos 0, it will fit
                    // otherwise reduce maxLength to start of the encoded char
                    if ($encodedCharPos > 0) {
                        $maxLength -= $lookBack - $encodedCharPos;
                    }
                    $foundSplitPos = true;
                } elseif ($dec >= 192) {
                    // First byte of a multi byte character
                    // Reduce maxLength to split at start of character
                    $maxLength -= $lookBack - $encodedCharPos;
                    $foundSplitPos = true;
                } elseif ($dec < 192) {
                    // Middle byte of a multi byte character, look further back
                    $lookBack += 3;
                }
            } else {
                // No encoded character found
                $foundSplitPos = true;
            }
        }

        return $maxLength;
    }

    /**
 * TÍTULO: APPLY WORD WRAPPING TO THE MENSAJE CONTENIDO
 *
     * Apply word wrapping to the mensaje contenido.
     * Wraps the mensaje contenido to the number of chars set in the WordWrap property.
     * You should only do this to plain-text bodies as wrapping HTML tags may break them.
     * This is called automatically by createBody(), so you don't need to call it yourself.
     */
    public function setWordWrap()
    {
        if ($this->WordWrap < 1) {
            return;
        }

        switch ($this->message_type) {
            case 'alt':
            case 'alt_inline':
            case 'alt_attach':
            case 'alt_inline_attach':
                $this->AltBody = $this->wrapText($this->AltBody, $this->WordWrap);
                break;
            default:
                $this->Body = $this->wrapText($this->Body, $this->WordWrap);
                break;
        }
    }

    /**
 * TÍTULO: ASSEMBLE MENSAJE HEADERS
 *
     * Assemble mensaje headers.
     * 
     * @return string El assembled headers
     */
    public function createHeader()
    {
        $result = '';

        $result .= $this->headerLine('Date', '' === $this->MessageDate ? self::rfcDate() : $this->MessageDate);

        // El To header is created automatically by mail(), so needs to be omitted here
        if ('mail' !== $this->Mailer) {
            if ($this->SingleTo) {
                foreach ($this->to as $toaddr) {
                    $this->SingleToArray[] = $this->addrFormat($toaddr);
                }
            } elseif (count($this->to) > 0) {
                $result .= $this->addrAppend('To', $this->to);
            } elseif (count($this->cc) === 0) {
                $result .= $this->headerLine('To', 'undisclosed-recipients:;');
            }
        }
        $result .= $this->addrAppend('From', [[trim($this->From), $this->FromName]]);

        // sendmail and mail() extract Cc from the header before sending
        if (count($this->cc) > 0) {
            $result .= $this->addrAppend('Cc', $this->cc);
        }

        // sendmail and mail() extract Bcc from the header before sending
        if (
            (
                'sendmail' === $this->Mailer || 'qmail' === $this->Mailer || 'mail' === $this->Mailer
            )
            && count($this->bcc) > 0
        ) {
            $result .= $this->addrAppend('Bcc', $this->bcc);
        }

        if (count($this->ReplyTo) > 0) {
            $result .= $this->addrAppend('Reply-To', $this->ReplyTo);
        }

        // mail() sets el asunto itself
        if ('mail' !== $this->Mailer) {
            $result .= $this->headerLine('Subject', $this->encodeHeader($this->secureHeader($this->Subject)));
        }

        // Only allow a personalizado mensaje ID if it conforms to RFC 5322 section 3.6.4
        // https://www.rfc-editor.org/rfc/rfc5322#section-3.6.4
        if (
            '' !== $this->MessageID &&
            preg_match(
                '/^<((([a-z\d!#$%&\'*+\/=?^_`{|}~-]+(\.[a-z\d!#$%&\'*+\/=?^_`{|}~-]+)*)' .
                '|("(([\x01-\x08\x0B\x0C\x0E-\x1F\x7F]|[\x21\x23-\x5B\x5D-\x7E])' .
                '|(\\[\x01-\x09\x0B\x0C\x0E-\x7F]))*"))@(([a-z\d!#$%&\'*+\/=?^_`{|}~-]+' .
                '(\.[a-z\d!#$%&\'*+\/=?^_`{|}~-]+)*)|(\[(([\x01-\x08\x0B\x0C\x0E-\x1F\x7F]' .
                '|[\x21-\x5A\x5E-\x7E])|(\\[\x01-\x09\x0B\x0C\x0E-\x7F]))*\])))>$/Di',
                $this->MessageID
            )
        ) {
            $this->lastMessageID = $this->MessageID;
        } else {
            $this->lastMessageID = sprintf('<%s@%s>', $this->uniqueid, $this->serverHostname());
        }
        $result .= $this->headerLine('Message-ID', $this->lastMessageID);
        if (null !== $this->Priority) {
            $result .= $this->headerLine('X-Priority', $this->Priority);
        }
        if ('' === $this->XMailer) {
            // Empty string for default X-Mailer header
            $result .= $this->headerLine(
                'X-Mailer',
                'PHPMailer ' . self::VERSION . ' (https://github.com/PHPMailer/PHPMailer)'
            );
        } elseif (is_string($this->XMailer) && trim($this->XMailer) !== '') {
            // Some string
            $result .= $this->headerLine('X-Mailer', trim($this->XMailer));
        } // Other values result in no X-Mailer header

        if ('' !== $this->ConfirmReadingTo) {
            $result .= $this->headerLine('Disposition-Notification-To', '<' . $this->ConfirmReadingTo . '>');
        }

        // Agregar personalizado headers
        foreach ($this->CustomHeader as $header) {
            $result .= $this->headerLine(
                trim($header[0]),
                $this->encodeHeader(trim($header[1]))
            );
        }
        if (!$this->sign_key_file) {
            $result .= $this->headerLine('MIME-Version', '1.0');
            $result .= $this->getMailMIME();
        }

        return $result;
    }

    /**
 * TÍTULO: OBTENER THE MENSAJE MIME TYPE HEADERS
 *
     * Obtener the mensaje MIME type headers.
     * 
     * @return string
     */
    public function getMailMIME()
    {
        $result = '';
        $ismultipart = true;
        switch ($this->message_type) {
            case 'inline':
                $result .= $this->headerLine('Content-Type', static::CONTENT_TYPE_MULTIPART_RELATED . ';');
                $result .= $this->textLine(' boundary="' . $this->boundary[1] . '"');
                break;
            case 'attach':
            case 'inline_attach':
            case 'alt_attach':
            case 'alt_inline_attach':
                $result .= $this->headerLine('Content-Type', static::CONTENT_TYPE_MULTIPART_MIXED . ';');
                $result .= $this->textLine(' boundary="' . $this->boundary[1] . '"');
                break;
            case 'alt':
            case 'alt_inline':
                $result .= $this->headerLine('Content-Type', static::CONTENT_TYPE_MULTIPART_ALTERNATIVE . ';');
                $result .= $this->textLine(' boundary="' . $this->boundary[1] . '"');
                break;
            default:
                // Catches case 'plain': and case '':
                $result .= $this->textLine('Content-Type: ' . $this->ContentType . '; charset=' . $this->CharSet);
                $ismultipart = false;
                break;
        }
        // RFC1341 part 5 says 7bit is assumed if not specified
        if (static::ENCODING_7BIT !== $this->Encoding) {
            // RFC 2045 section 6.4 says multipart MIME parts may only use 7bit, 8bit or binary CTE
            if ($ismultipart) {
                if (static::ENCODING_8BIT === $this->Encoding) {
                    $result .= $this->headerLine('Content-Transfer-Encoding', static::ENCODING_8BIT);
                }
                // El only remaining alternatives are quoted-printable and base64, which are both 7bit compatible
            } else {
                $result .= $this->headerLine('Content-Transfer-Encoding', $this->Encoding);
            }
        }

        return $result;
    }

    /**
 * TÍTULO: RETURNS THE WHOLE MIME MENSAJE
 *
     * Returns the whole MIME mensaje.
     * Includes completo headers and contenido.
     * Only valid post preSend().
     * 
     * @see PHPMailer::preSend()
     * 
     * @return string
     */
    public function getSentMIMEMessage()
    {
        return static::stripTrailingWSP($this->MIMEHeader . $this->mailHeader) .
            static::$LE . static::$LE . $this->MIMEBody;
    }

    /**
 * TÍTULO: CREAR A UNIQUE ID TO USE FOR BOUNDARIES
 *
     * Crear a unique ID to use for boundaries.
     * 
     * @return string
     */
    protected function generateId()
    {
        $len = 32; // 32 bytes = 256 bits
        $bytes = '';
        if (function_exists('random_bytes')) {
            try {
                $bytes = random_bytes($len);
            } catch (\Exception $e) {
                // Do nothing
            }
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            /** @noinspection CryptographicallySecureRandomnessInspection */
            $bytes = openssl_random_pseudo_bytes($len);
        }
        if ($bytes === '') {
            // We failed to produce a proper random string, so make do.
            // Usar a hash to force the length to the same as the other methods
            $bytes = hash('sha256', uniqid((string) mt_rand(), true), true);
        }

        // We don't care about messing up base64 format here, just want a random string
        return str_replace(['=', '+', '/'], '', base64_encode(hash('sha256', $bytes, true)));
    }

    /**
 * TÍTULO: ASSEMBLE THE MENSAJE CONTENIDO
 *
     * Assemble the mensaje contenido.
     * Returns an empty string on failure.
     * 
     * @throws Exception
     * 
     * @return string El assembled mensaje contenido
     */
    public function createBody()
    {
        $body = '';
        // Crear unique IDs and preset boundaries
        $this->setBoundaries();

        if ($this->sign_key_file) {
            $body .= $this->getMailMIME() . static::$LE;
        }

        $this->setWordWrap();

        $bodyEncoding = $this->Encoding;
        $bodyCharSet = $this->CharSet;
        // Can we do a 7-bit downgrade?
        if (static::ENCODING_8BIT === $bodyEncoding && !$this->has8bitChars($this->Body)) {
            $bodyEncoding = static::ENCODING_7BIT;
            // All ISO 8859, Windows codepage and UTF-8 charsets are ascii compatible up to 7-bit
            $bodyCharSet = static::CHARSET_ASCII;
        }
        // If lines are too long, and we're not already using an encoding that will shorten them,
        // change to quoted-printable transfer encoding for the contenido part only
        if (static::ENCODING_BASE64 !== $this->Encoding && static::hasLineLongerThanMax($this->Body)) {
            $bodyEncoding = static::ENCODING_QUOTED_PRINTABLE;
        }

        $altBodyEncoding = $this->Encoding;
        $altBodyCharSet = $this->CharSet;
        // Can we do a 7-bit downgrade?
        if (static::ENCODING_8BIT === $altBodyEncoding && !$this->has8bitChars($this->AltBody)) {
            $altBodyEncoding = static::ENCODING_7BIT;
            // All ISO 8859, Windows codepage and UTF-8 charsets are ascii compatible up to 7-bit
            $altBodyCharSet = static::CHARSET_ASCII;
        }
        // If lines are too long, and we're not already using an encoding that will shorten them,
        // change to quoted-printable transfer encoding for the alt contenido part only
        if (static::ENCODING_BASE64 !== $altBodyEncoding && static::hasLineLongerThanMax($this->AltBody)) {
            $altBodyEncoding = static::ENCODING_QUOTED_PRINTABLE;
        }
        // Usar this as a preamble in all multipart mensaje types
        $mimepre = '';
        switch ($this->message_type) {
            case 'inline':
                $body .= $mimepre;
                $body .= $this->getBoundary($this->boundary[1], $bodyCharSet, '', $bodyEncoding);
                $body .= $this->encodeString($this->Body, $bodyEncoding);
                $body .= static::$LE;
                $body .= $this->attachAll('inline', $this->boundary[1]);
                break;
            case 'attach':
                $body .= $mimepre;
                $body .= $this->getBoundary($this->boundary[1], $bodyCharSet, '', $bodyEncoding);
                $body .= $this->encodeString($this->Body, $bodyEncoding);
                $body .= static::$LE;
                $body .= $this->attachAll('attachment', $this->boundary[1]);
                break;
            case 'inline_attach':
                $body .= $mimepre;
                $body .= $this->textLine('--' . $this->boundary[1]);
                $body .= $this->headerLine('Content-Type', static::CONTENT_TYPE_MULTIPART_RELATED . ';');
                $body .= $this->textLine(' boundary="' . $this->boundary[2] . '";');
                $body .= $this->textLine(' type="' . static::CONTENT_TYPE_TEXT_HTML . '"');
                $body .= static::$LE;
                $body .= $this->getBoundary($this->boundary[2], $bodyCharSet, '', $bodyEncoding);
                $body .= $this->encodeString($this->Body, $bodyEncoding);
                $body .= static::$LE;
                $body .= $this->attachAll('inline', $this->boundary[2]);
                $body .= static::$LE;
                $body .= $this->attachAll('attachment', $this->boundary[1]);
                break;
            case 'alt':
                $body .= $mimepre;
                $body .= $this->getBoundary(
                    $this->boundary[1],
                    $altBodyCharSet,
                    static::CONTENT_TYPE_PLAINTEXT,
                    $altBodyEncoding
                );
                $body .= $this->encodeString($this->AltBody, $altBodyEncoding);
                $body .= static::$LE;
                $body .= $this->getBoundary(
                    $this->boundary[1],
                    $bodyCharSet,
                    static::CONTENT_TYPE_TEXT_HTML,
                    $bodyEncoding
                );
                $body .= $this->encodeString($this->Body, $bodyEncoding);
                $body .= static::$LE;
                if (!empty($this->Ical)) {
                    $method = static::ICAL_METHOD_REQUEST;
                    foreach (static::$IcalMethods as $imethod) {
                        if (stripos($this->Ical, 'METHOD:' . $imethod) !== false) {
                            $method = $imethod;
                            break;
                        }
                    }
                    $body .= $this->getBoundary(
                        $this->boundary[1],
                        '',
                        static::CONTENT_TYPE_TEXT_CALENDAR . '; method=' . $method,
                        ''
                    );
                    $body .= $this->encodeString($this->Ical, $this->Encoding);
                    $body .= static::$LE;
                }
                $body .= $this->endBoundary($this->boundary[1]);
                break;
            case 'alt_inline':
                $body .= $mimepre;
                $body .= $this->getBoundary(
                    $this->boundary[1],
                    $altBodyCharSet,
                    static::CONTENT_TYPE_PLAINTEXT,
                    $altBodyEncoding
                );
                $body .= $this->encodeString($this->AltBody, $altBodyEncoding);
                $body .= static::$LE;
                $body .= $this->textLine('--' . $this->boundary[1]);
                $body .= $this->headerLine('Content-Type', static::CONTENT_TYPE_MULTIPART_RELATED . ';');
                $body .= $this->textLine(' boundary="' . $this->boundary[2] . '";');
                $body .= $this->textLine(' type="' . static::CONTENT_TYPE_TEXT_HTML . '"');
                $body .= static::$LE;
                $body .= $this->getBoundary(
                    $this->boundary[2],
                    $bodyCharSet,
                    static::CONTENT_TYPE_TEXT_HTML,
                    $bodyEncoding
                );
                $body .= $this->encodeString($this->Body, $bodyEncoding);
                $body .= static::$LE;
                $body .= $this->attachAll('inline', $this->boundary[2]);
                $body .= static::$LE;
                $body .= $this->endBoundary($this->boundary[1]);
                break;
            case 'alt_attach':
                $body .= $mimepre;
                $body .= $this->textLine('--' . $this->boundary[1]);
                $body .= $this->headerLine('Content-Type', static::CONTENT_TYPE_MULTIPART_ALTERNATIVE . ';');
                $body .= $this->textLine(' boundary="' . $this->boundary[2] . '"');
                $body .= static::$LE;
                $body .= $this->getBoundary(
                    $this->boundary[2],
                    $altBodyCharSet,
                    static::CONTENT_TYPE_PLAINTEXT,
                    $altBodyEncoding
                );
                $body .= $this->encodeString($this->AltBody, $altBodyEncoding);
                $body .= static::$LE;
                $body .= $this->getBoundary(
                    $this->boundary[2],
                    $bodyCharSet,
                    static::CONTENT_TYPE_TEXT_HTML,
                    $bodyEncoding
                );
                $body .= $this->encodeString($this->Body, $bodyEncoding);
                $body .= static::$LE;
                if (!empty($this->Ical)) {
                    $method = static::ICAL_METHOD_REQUEST;
                    foreach (static::$IcalMethods as $imethod) {
                        if (stripos($this->Ical, 'METHOD:' . $imethod) !== false) {
                            $method = $imethod;
                            break;
                        }
                    }
                    $body .= $this->getBoundary(
                        $this->boundary[2],
                        '',
                        static::CONTENT_TYPE_TEXT_CALENDAR . '; method=' . $method,
                        ''
                    );
                    $body .= $this->encodeString($this->Ical, $this->Encoding);
                }
                $body .= $this->endBoundary($this->boundary[2]);
                $body .= static::$LE;
                $body .= $this->attachAll('attachment', $this->boundary[1]);
                break;
            case 'alt_inline_attach':
                $body .= $mimepre;
                $body .= $this->textLine('--' . $this->boundary[1]);
                $body .= $this->headerLine('Content-Type', static::CONTENT_TYPE_MULTIPART_ALTERNATIVE . ';');
                $body .= $this->textLine(' boundary="' . $this->boundary[2] . '"');
                $body .= static::$LE;
                $body .= $this->getBoundary(
                    $this->boundary[2],
                    $altBodyCharSet,
                    static::CONTENT_TYPE_PLAINTEXT,
                    $altBodyEncoding
                );
                $body .= $this->encodeString($this->AltBody, $altBodyEncoding);
                $body .= static::$LE;
                $body .= $this->textLine('--' . $this->boundary[2]);
                $body .= $this->headerLine('Content-Type', static::CONTENT_TYPE_MULTIPART_RELATED . ';');
                $body .= $this->textLine(' boundary="' . $this->boundary[3] . '";');
                $body .= $this->textLine(' type="' . static::CONTENT_TYPE_TEXT_HTML . '"');
                $body .= static::$LE;
                $body .= $this->getBoundary(
                    $this->boundary[3],
                    $bodyCharSet,
                    static::CONTENT_TYPE_TEXT_HTML,
                    $bodyEncoding
                );
                $body .= $this->encodeString($this->Body, $bodyEncoding);
                $body .= static::$LE;
                $body .= $this->attachAll('inline', $this->boundary[3]);
                $body .= static::$LE;
                $body .= $this->endBoundary($this->boundary[2]);
                $body .= static::$LE;
                $body .= $this->attachAll('attachment', $this->boundary[1]);
                break;
            default:
                // Catch case 'plain' and case '', applies to simple `text/plain` and `text/html` contenido content types
                // Reset the `Encoding` property in case we changed it for longitud de línea reasons
                $this->Encoding = $bodyEncoding;
                $body .= $this->encodeString($this->Body, $this->Encoding);
                break;
        }

        if ($this->isError()) {
            $body = '';
            if ($this->exceptions) {
                throw new Exception($this->lang('empty_message'), self::STOP_CRITICAL);
            }
        } elseif ($this->sign_key_file) {
            try {
                if (!defined('PKCS7_TEXT')) {
                    throw new Exception($this->lang('extension_missing') . 'openssl');
                }

                $file = tempnam(sys_get_temp_dir(), 'srcsign');
                $signed = tempnam(sys_get_temp_dir(), 'mailsign');
                file_put_contents($file, $body);

                // Workaround for PHP bug https://bugs.php.net/bug.php?id=69197
                if (empty($this->sign_extracerts_file)) {
                    $sign = @openssl_pkcs7_sign(
                        $file,
                        $signed,
                        'file://' . realpath($this->sign_cert_file),
                        ['file://' . realpath($this->sign_key_file), $this->sign_key_pass],
                        []
                    );
                } else {
                    $sign = @openssl_pkcs7_sign(
                        $file,
                        $signed,
                        'file://' . realpath($this->sign_cert_file),
                        ['file://' . realpath($this->sign_key_file), $this->sign_key_pass],
                        [],
                        PKCS7_DETACHED,
                        $this->sign_extracerts_file
                    );
                }

                @unlink($file);
                if ($sign) {
                    $body = file_get_contents($signed);
                    @unlink($signed);
                    // El mensaje returned by openssl contains both headers and contenido, so need to split them up
                    $parts = explode("\n\n", $body, 2);
                    $this->MIMEHeader .= $parts[0] . static::$LE . static::$LE;
                    $body = $parts[1];
                } else {
                    @unlink($signed);
                    throw new Exception($this->lang('signing') . openssl_error_string());
                }
            } catch (Exception $exc) {
                $body = '';
                if ($this->exceptions) {
                    throw $exc;
                }
            }
        }

        return $body;
    }

    /**
 * TÍTULO: OBTENER THE BOUNDARIES THAT THIS MENSAJE WILL USE
 *
     * Obtener the boundaries that this mensaje will use
     * @return array
     */
    public function getBoundaries()
    {
        if (empty($this->boundary)) {
            $this->setBoundaries();
        }
        return $this->boundary;
    }

    /**
 * TÍTULO: OBTENER THE START OF A MENSAJE BOUNDARY
 *
     * Obtener the start of a mensaje boundary.
     * 
     * @param string $boundary
     * @param string $charSet
     * @param string $contentType
     * @param string $encoding
     * 
     * @return string
     */
    protected function getBoundary($boundary, $charSet, $contentType, $encoding)
    {
        $result = '';
        if ('' === $charSet) {
            $charSet = $this->CharSet;
        }
        if ('' === $contentType) {
            $contentType = $this->ContentType;
        }
        if ('' === $encoding) {
            $encoding = $this->Encoding;
        }
        $result .= $this->textLine('--' . $boundary);
        $result .= sprintf('Content-Type: %s; charset=%s', $contentType, $charSet);
        $result .= static::$LE;
        // RFC1341 part 5 says 7bit is assumed if not specified
        if (static::ENCODING_7BIT !== $encoding) {
            $result .= $this->headerLine('Content-Transfer-Encoding', $encoding);
        }
        $result .= static::$LE;

        return $result;
    }

    /**
 * TÍTULO: OBTENER THE END OF A MENSAJE BOUNDARY
 *
     * Obtener the end of a mensaje boundary.
     * 
     * @param string $boundary
     * 
     * @return string
     */
    protected function endBoundary($boundary)
    {
        return static::$LE . '--' . $boundary . '--' . static::$LE;
    }

    /**
 * TÍTULO: ESTABLECER THE MENSAJE TYPE
 *
     * Establecer the mensaje type.
     * PHPMailer only supports some preset mensaje types, not arbitrary MIME structures.
     */
    protected function setMessageType()
    {
        $type = [];
        if ($this->alternativeExists()) {
            $type[] = 'alt';
        }
        if ($this->inlineImageExists()) {
            $type[] = 'inline';
        }
        if ($this->attachmentExists()) {
            $type[] = 'attach';
        }
        $this->message_type = implode('_', $type);
        if ('' === $this->message_type) {
            // El 'plain' mensaje_type refers to the mensaje having a single contenido element, not that it is plain-text
            $this->message_type = 'plain';
        }
    }

    /**
 * TÍTULO: FORMAT A HEADER LINE
 *
     * Format a header line.
     * 
     * @param string     $name
     * @param string|int $value
     * 
     * @return string
     */
    public function headerLine($name, $value)
    {
        return $name . ': ' . $value . static::$LE;
    }

    /**
 * TÍTULO: OBTENER A FORMATTED MAIL LINE
 *
     * Obtener a formatted mail line.
     * 
     * @param string $value
     * 
     * @return string
     */
    public function textLine($value)
    {
        return $value . static::$LE;
    }

    /**
 * TÍTULO: AGREGAR AN ATTACHMENT FROM A PATH ON THE FILESYSTEM
 *
     * Agregar an attachment from a path on the filesystem.
     * Never use a user-supplied path to a file!
     * Returns false if the file could not be found or read.
     * Explicitly *does not* support passing URLs; PHPMailer is not an HTTP client.
     * If you need to do that, fetch the resource yourself and pass it in via a local file or string.
     * 
     * @param string $path        Path to the attachment
     * @param string $name        Overrides the attachment name
     * @param string $encoding    File encoding (see $Encoding)
     * @param string $type        MIME type, e.g. `image/jpeg`; determined automatically from $path if not specified
     * @param string $disposition Disposition to use
     * 
     * @throws Exception
     * 
     * @return bool
     */
    public function addAttachment(
        $path,
        $name = '',
        $encoding = self::ENCODING_BASE64,
        $type = '',
        $disposition = 'attachment'
    ) {
        try {
            if (!static::fileIsAccessible($path)) {
                throw new Exception($this->lang('file_access') . $path, self::STOP_CONTINUE);
            }

            // If a MIME type is not specified, try to work it out from the file name
            if ('' === $type) {
                $type = static::filenameToType($path);
            }

            $filename = (string) static::mb_pathinfo($path, PATHINFO_BASENAME);
            if ('' === $name) {
                $name = $filename;
            }
            if (!$this->validateEncoding($encoding)) {
                throw new Exception($this->lang('encoding') . $encoding);
            }

            $this->attachment[] = [
                0 => $path,
                1 => $filename,
                2 => $name,
                3 => $encoding,
                4 => $type,
                5 => false, // isStringAttachment
                6 => $disposition,
                7 => $name,
            ];
        } catch (Exception $exc) {
            $this->setError($exc->getMessage());
            $this->edebug($exc->getMessage());
            if ($this->exceptions) {
                throw $exc;
            }

            return false;
        }

        return true;
    }

    /**
 * TÍTULO: OBTENER THE ARRAY OF ATTACHMENTS
 *
     * Obtener the array of attachments.
     * 
     * @return array
     */
    public function getAttachments()
    {
        return $this->attachment;
    }

    /**
 * TÍTULO: ATTACH ALL FILE, STRING, AND BINARY ATTACHMENTS TO THE MENSAJE
 *
     * Attach all file, string, and binary attachments to the mensaje.
     * Returns an empty string on failure.
     * 
     * @param string $disposition_type
     * @param string $boundary
     * 
     * @throws Exception
     * 
     * @return string
     */
    protected function attachAll($disposition_type, $boundary)
    {
        // Obtener text of contenido
        $mime = [];
        $cidUniq = [];
        $incl = [];

        // Agregar all attachments
        foreach ($this->attachment as $attachment) {
            // Comprobar if it is a valid disposition_filter
            if ($attachment[6] === $disposition_type) {
                // Comprobar for string attachment
                $string = '';
                $path = '';
                $bString = $attachment[5];
                if ($bString) {
                    $string = $attachment[0];
                } else {
                    $path = $attachment[0];
                }

                $inclhash = hash('sha256', serialize($attachment));
                if (in_array($inclhash, $incl, true)) {
                    continue;
                }
                $incl[] = $inclhash;
                $name = $attachment[2];
                $encoding = $attachment[3];
                $type = $attachment[4];
                $disposition = $attachment[6];
                $cid = $attachment[7];
                if ('inline' === $disposition && array_key_exists($cid, $cidUniq)) {
                    continue;
                }
                $cidUniq[$cid] = true;

                $mime[] = sprintf('--%s%s', $boundary, static::$LE);
                // Only include a filename property if we have one
                if (!empty($name)) {
                    $mime[] = sprintf(
                        'Content-Type: %s; name=%s%s',
                        $type,
                        static::quotedString($this->encodeHeader($this->secureHeader($name))),
                        static::$LE
                    );
                } else {
                    $mime[] = sprintf(
                        'Content-Type: %s%s',
                        $type,
                        static::$LE
                    );
                }
                // RFC1341 part 5 says 7bit is assumed if not specified
                if (static::ENCODING_7BIT !== $encoding) {
                    $mime[] = sprintf('Content-Transfer-Encoding: %s%s', $encoding, static::$LE);
                }

                // Only set Content-IDs on inline attachments
                if ((string) $cid !== '' && $disposition === 'inline') {
                    $mime[] = 'Content-ID: <' . $this->encodeHeader($this->secureHeader($cid)) . '>' . static::$LE;
                }

                // Allow for bypassing the Content-Disposition header
                if (!empty($disposition)) {
                    $encoded_name = $this->encodeHeader($this->secureHeader($name));
                    if (!empty($encoded_name)) {
                        $mime[] = sprintf(
                            'Content-Disposition: %s; filename=%s%s',
                            $disposition,
                            static::quotedString($encoded_name),
                            static::$LE . static::$LE
                        );
                    } else {
                        $mime[] = sprintf(
                            'Content-Disposition: %s%s',
                            $disposition,
                            static::$LE . static::$LE
                        );
                    }
                } else {
                    $mime[] = static::$LE;
                }

                // Encode as string attachment
                if ($bString) {
                    $mime[] = $this->encodeString($string, $encoding);
                } else {
                    $mime[] = $this->encodeFile($path, $encoding);
                }
                if ($this->isError()) {
                    return '';
                }
                $mime[] = static::$LE;
            }
        }

        $mime[] = sprintf('--%s--%s', $boundary, static::$LE);

        return implode('', $mime);
    }

    /**
 * TÍTULO: ENCODE A FILE ATTACHMENT IN REQUESTED FORMAT
 *
     * Encode a file attachment in requested format.
     * Returns an empty string on failure.
     * 
     * @param string $path     El full path to the file
     * @param string $encoding El encoding to use; one of 'base64', '7bit', '8bit', 'binary', 'quoted-printable'
     * 
     * @return string
     */
    protected function encodeFile($path, $encoding = self::ENCODING_BASE64)
    {
        try {
            if (!static::fileIsAccessible($path)) {
                throw new Exception($this->lang('file_open') . $path, self::STOP_CONTINUE);
            }
            $file_buffer = file_get_contents($path);
            if (false === $file_buffer) {
                throw new Exception($this->lang('file_open') . $path, self::STOP_CONTINUE);
            }
            $file_buffer = $this->encodeString($file_buffer, $encoding);

            return $file_buffer;
        } catch (Exception $exc) {
            $this->setError($exc->getMessage());
            $this->edebug($exc->getMessage());
            if ($this->exceptions) {
                throw $exc;
            }

            return '';
        }
    }

    /**
 * TÍTULO: ENCODE A STRING IN REQUESTED FORMAT
 *
     * Encode a string in requested format.
     * Returns an empty string on failure.
     * 
     * @param string $str      El text to encode
     * @param string $encoding El encoding to use; one of 'base64', '7bit', '8bit', 'binary', 'quoted-printable'
     * 
     * @throws Exception
     * 
     * @return string
     */
    public function encodeString($str, $encoding = self::ENCODING_BASE64)
    {
        $encoded = '';
        switch (strtolower($encoding)) {
            case static::ENCODING_BASE64:
                $encoded = chunk_split(
                    base64_encode($str),
                    static::STD_LINE_LENGTH,
                    static::$LE
                );
                break;
            case static::ENCODING_7BIT:
            case static::ENCODING_8BIT:
                $encoded = static::normalizeBreaks($str);
                // Make sure it ends with a salto de línea
                if (substr($encoded, -(strlen(static::$LE))) !== static::$LE) {
                    $encoded .= static::$LE;
                }
                break;
            case static::ENCODING_BINARY:
                $encoded = $str;
                break;
            case static::ENCODING_QUOTED_PRINTABLE:
                $encoded = $this->encodeQP($str);
                break;
            default:
                $this->setError($this->lang('encoding') . $encoding);
                if ($this->exceptions) {
                    throw new Exception($this->lang('encoding') . $encoding);
                }
                break;
        }

        return $encoded;
    }

    /**
 * TÍTULO: ENCODE A HEADER VALUE (NOT INCLUDING ITS LABEL) OPTIMALLY
 *
     * Encode a header value (not including its label) optimally.
     * Picks shortest of Q, B, or none. Result includes folding if needed.
     * See RFC822 definitions for phrase, comment and text positions.
     * 
     * @param string $str      El header value to encode
     * @param string $position What context the string will be used in
     * 
     * @return string
     */
    public function encodeHeader($str, $position = 'text')
    {
        $matchcount = 0;
        switch (strtolower($position)) {
            case 'phrase':
                if (!preg_match('/[\200-\377]/', $str)) {
                    // Can't use addslashes as we don't know the value of magic_quotes_sybase
                    $encoded = addcslashes($str, "\0..\37\177\\\"");
                    if (($str === $encoded) && !preg_match('/[^A-Za-z0-9!#$%&\'*+\/=?^_`{|}~ -]/', $str)) {
                        return $encoded;
                    }

                    return "\"$encoded\"";
                }
                $matchcount = preg_match_all('/[^\040\041\043-\133\135-\176]/', $str, $matches);
                break;
            /* @noinspection PhpMissingBreakStatementInspection */
            case 'comment':
                $matchcount = preg_match_all('/[()"]/', $str, $matches);
            // fallthrough
            case 'text':
            default:
                $matchcount += preg_match_all('/[\000-\010\013\014\016-\037\177-\377]/', $str, $matches);
                break;
        }

        if ($this->has8bitChars($str)) {
            $charset = $this->CharSet;
        } else {
            $charset = static::CHARSET_ASCII;
        }

        // Q/B encoding adds 8 chars and the charset ("` =?<charset>?[QB]?<content>?=`").
        $overhead = 8 + strlen($charset);

        if ('mail' === $this->Mailer) {
            $maxlen = static::MAIL_MAX_LINE_LENGTH - $overhead;
        } else {
            $maxlen = static::MAX_LINE_LENGTH - $overhead;
        }

        // Select the encoding that produces the shortest output and/or prevents corruption.
        if ($matchcount > strlen($str) / 3) {
            // More than 1/3 of the content needs encoding, use B-encode.
            $encoding = 'B';
        } elseif ($matchcount > 0) {
            // Less than 1/3 of the content needs encoding, use Q-encode.
            $encoding = 'Q';
        } elseif (strlen($str) > $maxlen) {
            // No encoding needed, but value exceeds max longitud de línea, use Q-encode to prevent corruption.
            $encoding = 'Q';
        } else {
            // No reformatting needed
            $encoding = false;
        }

        switch ($encoding) {
            case 'B':
                if ($this->hasMultiBytes($str)) {
                    // Usar a personalizado function which correctly encodes and wraps long
                    // multibyte strings without breaking lines within a character
                    $encoded = $this->base64EncodeWrapMB($str, "\n");
                } else {
                    $encoded = base64_encode($str);
                    $maxlen -= $maxlen % 4;
                    $encoded = trim(chunk_split($encoded, $maxlen, "\n"));
                }
                $encoded = preg_replace('/^(.*)$/m', ' =?' . $charset . "?$encoding?\\1?=", $encoded);
                break;
            case 'Q':
                $encoded = $this->encodeQ($str, $position);
                $encoded = $this->wrapText($encoded, $maxlen, true);
                $encoded = str_replace('=' . static::$LE, "\n", trim($encoded));
                $encoded = preg_replace('/^(.*)$/m', ' =?' . $charset . "?$encoding?\\1?=", $encoded);
                break;
            default:
                return $str;
        }

        return trim(static::normalizeBreaks($encoded));
    }

    /**
 * TÍTULO: COMPROBAR IF A STRING CONTAINS MULTI-BYTE CHARACTERS
 *
     * Comprobar if a string contains multi-byte characters.
     * 
     * @param string $str multi-byte text to wrap encode
     * 
     * @return bool
     */
    public function hasMultiBytes($str)
    {
        if (function_exists('mb_strlen')) {
            return strlen($str) > mb_strlen($str, $this->CharSet);
        }

        // Assume no multibytes (we can't handle without mbstring functions anyway)
        return false;
    }

    /**
 * TÍTULO: DOES A STRING CONTAIN ANY 8-BIT CHARS (IN ANY CHARSET)?
 *
     * Does a string contain any 8-bit chars (in any charset)?
     * 
     * @param string $text
     * 
     * @return bool
     */
    public function has8bitChars($text)
    {
        return (bool) preg_match('/[\x80-\xFF]/', $text);
    }

    /**
 * TÍTULO: ENCODE AND WRAP LONG MULTIBYTE STRINGS FOR MAIL HEADERS
 *
     * Encode and wrap long multibyte strings for mail headers
     * without breaking lines within a character.
     * Adapted from a function by paravoid.
     * 
     * @see https://www.php.net/manual/en/function.mb-encode-mimeheader.php#60283
     * 
     * @param string $str       multi-byte text to wrap encode
     * @param string $linebreak string to use as linefeed/end-of-line
     * 
     * @return string
     */
    public function base64EncodeWrapMB($str, $linebreak = null)
    {
        $start = '=?' . $this->CharSet . '?B?';
        $end = '?=';
        $encoded = '';
        if (null === $linebreak) {
            $linebreak = static::$LE;
        }

        $mb_length = mb_strlen($str, $this->CharSet);
        // Each line must have length <= 75, including $start and $end
        $length = 75 - strlen($start) - strlen($end);
        // Average multi-byte ratio
        $ratio = $mb_length / strlen($str);
        // Base64 has a 4:3 ratio
        $avgLength = floor($length * $ratio * .75);

        $offset = 0;
        for ($i = 0; $i < $mb_length; $i += $offset) {
            $lookBack = 0;
            do {
                $offset = $avgLength - $lookBack;
                $chunk = mb_substr($str, $i, $offset, $this->CharSet);
                $chunk = base64_encode($chunk);
                ++$lookBack;
            } while (strlen($chunk) > $length);
            $encoded .= $chunk . $linebreak;
        }

        // Chomp the last linefeed
        return substr($encoded, 0, -strlen($linebreak));
    }

    /**
 * TÍTULO: ENCODE A STRING IN QUOTED-PRINTABLE FORMAT
 *
     * Encode a string in quoted-printable format.
     * According to RFC2045 section 6.7.
     * 
     * @param string $string El text to encode
     * 
     * @return string
     */
    public function encodeQP($string)
    {
        return static::normalizeBreaks(quoted_printable_encode($string));
    }

    /**
 * TÍTULO: ENCODE A STRING USING Q ENCODING
 *
     * Encode a string using Q encoding.
     * 
     * @see https://www.rfc-editor.org/rfc/rfc2047#section-4.2
     * 
     * @param string $str      the text to encode
     * @param string $position Where the text is going to be used, see the RFC for what that means
     * 
     * @return string
     */
    public function encodeQ($str, $position = 'text')
    {
        // There should not be any EOL in the string
        $pattern = '';
        $encoded = str_replace(["\r", "\n"], '', $str);
        switch (strtolower($position)) {
            case 'phrase':
                // RFC 2047 section 5.3
                $pattern = '^A-Za-z0-9!*+\/ -';
                break;
            /*
             * RFC 2047 section 5.2.
             * Build $pattern without including delimiters and []
             */
            /* @noinspection PhpMissingBreakStatementInspection */
            case 'comment':
                $pattern = '\(\)"';
            /* Intentional fall through */
            case 'text':
            default:
                // RFC 2047 section 5.1
                // Replace every high ascii, control, =, ? and _ characters
                $pattern = '\000-\011\013\014\016-\037\075\077\137\177-\377' . $pattern;
                break;
        }
        $matches = [];
        if (preg_match_all("/[{$pattern}]/", $encoded, $matches)) {
            // If the string contains an '=', make sure it's the first thing we replace
            // so as to avoid double-encoding
            $eqkey = array_search('=', $matches[0], true);
            if (false !== $eqkey) {
                unset($matches[0][$eqkey]);
                array_unshift($matches[0], '=');
            }
            foreach (array_unique($matches[0]) as $char) {
                $encoded = str_replace($char, '=' . sprintf('%02X', ord($char)), $encoded);
            }
        }
        // Replace spaces with _ (more readable than =20)
        // RFC 2047 section 4.2(2)
        return str_replace(' ', '_', $encoded);
    }

    /**
 * TÍTULO: AGREGAR A STRING OR BINARY ATTACHMENT (NON-FILESYSTEM)
 *
     * Agregar a string or binary attachment (non-filesystem).
     * This method can be used to attach ascii or binary data,
     * such as a BLOB record from a database.
     * 
     * @param string $string      String attachment data
     * @param string $filename    Name of the attachment
     * @param string $encoding    File encoding (see $Encoding)
     * @param string $type        File extension (MIME) type
     * @param string $disposition Disposition to use
     * 
     * @throws Exception
     * 
     * @return bool True on successfully adding an attachment
     */
    public function addStringAttachment(
        $string,
        $filename,
        $encoding = self::ENCODING_BASE64,
        $type = '',
        $disposition = 'attachment'
    ) {
        try {
            // If a MIME type is not specified, try to work it out from the file name
            if ('' === $type) {
                $type = static::filenameToType($filename);
            }

            if (!$this->validateEncoding($encoding)) {
                throw new Exception($this->lang('encoding') . $encoding);
            }

            // Append to $attachment array
            $this->attachment[] = [
                0 => $string,
                1 => $filename,
                2 => static::mb_pathinfo($filename, PATHINFO_BASENAME),
                3 => $encoding,
                4 => $type,
                5 => true, // isStringAttachment
                6 => $disposition,
                7 => 0,
            ];
        } catch (Exception $exc) {
            $this->setError($exc->getMessage());
            $this->edebug($exc->getMessage());
            if ($this->exceptions) {
                throw $exc;
            }

            return false;
        }

        return true;
    }

    /**
 * TÍTULO: AGREGAR AN EMBEDDED (INLINE) ATTACHMENT FROM A FILE
 *
     * Agregar an embedded (inline) attachment from a file.
     * This can include images, sounds, and just about any other document type.
     * These differ from 'regular' attachments in that they are intended to be
     * displayed inline with the mensaje, not just attached for download.
     * This is used in HTML mensajes that embed the images
     * the HTML refers to using the `$cid` value in `img` tags, for example `<img src="cid:mylogo">`.
     * Never use a user-supplied path to a file!
     * 
     * @param string $path        Path to the attachment
     * @param string $cid         Content ID of the attachment; Usar this to reference
     * the content when using an embedded image in HTML
     * @param string $name        Overrides the attachment filename
     * @param string $encoding    File encoding (see $Encoding) defaults to `base64`
     * @param string $type        File MIME type (by default mapped from the `$path` filename's extension)
     * @param string $disposition Disposition to use: `inline` (default) or `attachment`
     * (unlikely you want this – {@see `addAttachment()`} instead)
     * 
     * @return bool True on successfully adding an attachment
     * @throws Exception
     * 
     */
    public function addEmbeddedImage(
        $path,
        $cid,
        $name = '',
        $encoding = self::ENCODING_BASE64,
        $type = '',
        $disposition = 'inline'
    ) {
        try {
            if (!static::fileIsAccessible($path)) {
                throw new Exception($this->lang('file_access') . $path, self::STOP_CONTINUE);
            }

            // If a MIME type is not specified, try to work it out from the file name
            if ('' === $type) {
                $type = static::filenameToType($path);
            }

            if (!$this->validateEncoding($encoding)) {
                throw new Exception($this->lang('encoding') . $encoding);
            }

            $filename = (string) static::mb_pathinfo($path, PATHINFO_BASENAME);
            if ('' === $name) {
                $name = $filename;
            }

            // Append to $attachment array
            $this->attachment[] = [
                0 => $path,
                1 => $filename,
                2 => $name,
                3 => $encoding,
                4 => $type,
                5 => false, // isStringAttachment
                6 => $disposition,
                7 => $cid,
            ];
        } catch (Exception $exc) {
            $this->setError($exc->getMessage());
            $this->edebug($exc->getMessage());
            if ($this->exceptions) {
                throw $exc;
            }

            return false;
        }

        return true;
    }

    /**
 * TÍTULO: AGREGAR AN EMBEDDED STRINGIFIED ATTACHMENT
 *
     * Agregar an embedded stringified attachment.
     * This can include images, sounds, and just about any other document type.
     * If your filename doesn't contain an extension, be sure to set the $type to an appropriate MIME type.
     * 
     * @param string $string      El attachment binary data
     * @param string $cid         Content ID of the attachment; Usar this to reference
     * the content when using an embedded image in HTML
     * @param string $name        A filename for the attachment. If this contains an extension,
     * PHPMailer will attempt to set a MIME type for the attachment.
     * For example 'file.jpg' would get an 'image/jpeg' MIME type.
     * @param string $encoding    File encoding (see $Encoding), defaults to 'base64'
     * @param string $type        MIME type - will be used in preference to any automatically derived type
     * @param string $disposition Disposition to use
     * 
     * @throws Exception
     * 
     * @return bool True on successfully adding an attachment
     */
    public function addStringEmbeddedImage(
        $string,
        $cid,
        $name = '',
        $encoding = self::ENCODING_BASE64,
        $type = '',
        $disposition = 'inline'
    ) {
        try {
            // If a MIME type is not specified, try to work it out from the name
            if ('' === $type && !empty($name)) {
                $type = static::filenameToType($name);
            }

            if (!$this->validateEncoding($encoding)) {
                throw new Exception($this->lang('encoding') . $encoding);
            }

            // Append to $attachment array
            $this->attachment[] = [
                0 => $string,
                1 => $name,
                2 => $name,
                3 => $encoding,
                4 => $type,
                5 => true, // isStringAttachment
                6 => $disposition,
                7 => $cid,
            ];
        } catch (Exception $exc) {
            $this->setError($exc->getMessage());
            $this->edebug($exc->getMessage());
            if ($this->exceptions) {
                throw $exc;
            }

            return false;
        }

        return true;
    }

    /**
 * TÍTULO: VALIDAR ENCODINGS
 *
     * Validar encodings.
     * 
     * @param string $encoding
     * 
     * @return bool
     */
    protected function validateEncoding($encoding)
    {
        return in_array(
            $encoding,
            [
                self::ENCODING_7BIT,
                self::ENCODING_QUOTED_PRINTABLE,
                self::ENCODING_BASE64,
                self::ENCODING_8BIT,
                self::ENCODING_BINARY,
            ],
            true
        );
    }

    /**
 * TÍTULO: COMPROBAR IF AN EMBEDDED ATTACHMENT IS PRESENT WITH THIS CID
 *
     * Comprobar if an embedded attachment is present with this cid.
     * 
     * @param string $cid
     * 
     * @return bool
     */
    protected function cidExists($cid)
    {
        foreach ($this->attachment as $attachment) {
            if ('inline' === $attachment[6] && $cid === $attachment[7]) {
                return true;
            }
        }

        return false;
    }

    /**
 * TÍTULO: COMPROBAR IF AN INLINE ATTACHMENT IS PRESENT
 *
     * Comprobar if an inline attachment is present.
     * 
     * @return bool
     */
    public function inlineImageExists()
    {
        foreach ($this->attachment as $attachment) {
            if ('inline' === $attachment[6]) {
                return true;
            }
        }

        return false;
    }

    /**
 * TÍTULO: COMPROBAR IF AN ATTACHMENT (NON-INLINE) IS PRESENT
 *
     * Comprobar if an attachment (non-inline) is present.
     * 
     * @return bool
     */
    public function attachmentExists()
    {
        foreach ($this->attachment as $attachment) {
            if ('attachment' === $attachment[6]) {
                return true;
            }
        }

        return false;
    }

    /**
 * TÍTULO: COMPROBAR IF THIS MENSAJE HAS AN ALTERNATIVE CONTENIDO SET
 *
     * Comprobar if this mensaje has an alternative contenido set.
     * 
     * @return bool
     */
    public function alternativeExists()
    {
        return !empty($this->AltBody);
    }

    /**
 * TÍTULO: CLEAR QUEUED DIRECCIONES OF GIVEN KIND
 *
     * Clear queued direcciones of given kind.
     * 
     * @param string $kind 'to', 'cc', or 'bcc'
     */
    public function clearQueuedAddresses($kind)
    {
        $this->RecipientsQueue = array_filter(
            $this->RecipientsQueue,
            static function ($params) use ($kind) {
                return $params[0] !== $kind;
            }
        );
    }

    /**
 * TÍTULO: CLEAR ALL TO DESTINATARIOS
 *
     * Clear all To destinatarios.
     */
    public function clearAddresses()
    {
        foreach ($this->to as $to) {
            unset($this->all_recipients[strtolower($to[0])]);
        }
        $this->to = [];
        $this->clearQueuedAddresses('to');
    }

    /**
 * TÍTULO: CLEAR ALL CC DESTINATARIOS
 *
     * Clear all CC destinatarios.
     */
    public function clearCCs()
    {
        foreach ($this->cc as $cc) {
            unset($this->all_recipients[strtolower($cc[0])]);
        }
        $this->cc = [];
        $this->clearQueuedAddresses('cc');
    }

    /**
 * TÍTULO: CLEAR ALL BCC DESTINATARIOS
 *
     * Clear all BCC destinatarios.
     */
    public function clearBCCs()
    {
        foreach ($this->bcc as $bcc) {
            unset($this->all_recipients[strtolower($bcc[0])]);
        }
        $this->bcc = [];
        $this->clearQueuedAddresses('bcc');
    }

    /**
 * TÍTULO: CLEAR ALL REPLYTO DESTINATARIOS
 *
     * Clear all ReplyTo destinatarios.
     */
    public function clearReplyTos()
    {
        $this->ReplyTo = [];
        $this->ReplyToQueue = [];
    }

    /**
 * TÍTULO: CLEAR ALL DESTINATARIO TYPES
 *
     * Clear all destinatario types.
     */
    public function clearAllRecipients()
    {
        $this->to = [];
        $this->cc = [];
        $this->bcc = [];
        $this->all_recipients = [];
        $this->RecipientsQueue = [];
    }

    /**
 * TÍTULO: CLEAR ALL FILESYSTEM, STRING, AND BINARY ATTACHMENTS
 *
     * Clear all filesystem, string, and binary attachments.
     */
    public function clearAttachments()
    {
        $this->attachment = [];
    }

    /**
 * TÍTULO: CLEAR ALL PERSONALIZADO HEADERS
 *
     * Clear all personalizado headers.
     */
    public function clearCustomHeaders()
    {
        $this->CustomHeader = [];
    }

    /**
 * TÍTULO: CLEAR A SPECIFIC PERSONALIZADO HEADER BY NAME OR NAME AND VALUE
 *
     * Clear a specific personalizado header by name or name and value.
     * $name value can be overloaded to contain
     * both header name and value (name:value).
     * 
     * @param string      $name  Custom header name
     * @param string|null $value Header value
     * 
     * @return bool True if a header was replaced successfully
     */
    public function clearCustomHeader($name, $value = null)
    {
        if (null === $value && strpos($name, ':') !== false) {
            // Value passed in as name:value
            list($name, $value) = explode(':', $name, 2);
        }
        $name = trim($name);
        $value = (null === $value) ? null : trim($value);

        foreach ($this->CustomHeader as $k => $pair) {
            if ($pair[0] == $name) {
                // We remove the header if the value is not provided or it matches.
                if (null === $value ||  $pair[1] == $value) {
                    unset($this->CustomHeader[$k]);
                }
            }
        }

        return true;
    }

    /**
 * TÍTULO: REPLACE A PERSONALIZADO HEADER
 *
     * Replace a personalizado header.
     * $name value can be overloaded to contain
     * both header name and value (name:value).
     * 
     * @param string      $name  Custom header name
     * @param string|null $value Header value
     * 
     * @return bool True if a header was replaced successfully
     * @throws Exception
     */
    public function replaceCustomHeader($name, $value = null)
    {
        if (null === $value && strpos($name, ':') !== false) {
            // Value passed in as name:value
            list($name, $value) = explode(':', $name, 2);
        }
        $name = trim($name);
        $value = (null === $value) ? '' : trim($value);

        $replaced = false;
        foreach ($this->CustomHeader as $k => $pair) {
            if ($pair[0] == $name) {
                if ($replaced) {
                    unset($this->CustomHeader[$k]);
                    continue;
                }
                if (strpbrk($name . $value, "\r\n") !== false) {
                    if ($this->exceptions) {
                        throw new Exception($this->lang('invalid_header'));
                    }

                    return false;
                }
                $this->CustomHeader[$k] = [$name, $value];
                $replaced = true;
            }
        }

        return true;
    }

    /**
 * TÍTULO: AGREGAR AN ERROR MENSAJE TO THE ERROR CONTAINER
 *
     * Agregar an error mensaje to the error container.
     * 
     * @param string $msg
     */
    protected function setError($msg)
    {
        ++$this->error_count;
        if ('smtp' === $this->Mailer && null !== $this->smtp) {
            $lasterror = $this->smtp->getError();
            if (!empty($lasterror['error'])) {
                $msg .= $this->lang('smtp_error') . $lasterror['error'];
                if (!empty($lasterror['detail'])) {
                    $msg .= ' ' . $this->lang('smtp_detail') . $lasterror['detail'];
                }
                if (!empty($lasterror['smtp_code'])) {
                    $msg .= ' ' . $this->lang('smtp_code') . $lasterror['smtp_code'];
                }
                if (!empty($lasterror['smtp_code_ex'])) {
                    $msg .= ' ' . $this->lang('smtp_code_ex') . $lasterror['smtp_code_ex'];
                }
            }
        }
        $this->ErrorInfo = $msg;
    }

    /**
 * TÍTULO: OBTENER AN RFC 822 FORMATTED DATE
 *
     * Obtener an RFC 822 formatted date.
     * 
     * @return string
     */
    public static function rfcDate()
    {
        // Establecer the time zone to whatever the default is to avoid 500 errores
        // Will default to UTC if it's not set properly in php.ini
        date_default_timezone_set(@date_default_timezone_get());

        return date('D, j M Y H:i:s O');
    }

    /**
 * TÍTULO: OBTENER THE SERVIDOR HOSTNAME
 *
     * Obtener the servidor hostname.
     * Returns 'localhost.localdomain' if unknown.
     * 
     * @return string
     */
    protected function serverHostname()
    {
        $result = '';
        if (!empty($this->Hostname)) {
            $result = $this->Hostname;
        } elseif (isset($_SERVER) && array_key_exists('SERVER_NAME', $_SERVER)) {
            $result = $_SERVER['SERVER_NAME'];
        } elseif (function_exists('gethostname') && gethostname() !== false) {
            $result = gethostname();
        } elseif (php_uname('n') !== '') {
            $result = php_uname('n');
        }
        if (!static::isValidHost($result)) {
            return 'localhost.localdomain';
        }

        return $result;
    }

    /**
 * TÍTULO: VALIDAR WHETHER A STRING CONTAINS A VALID VALUE TO USE AS A HOSTNAME OR IP DIRECCIÓN
 *
     * Validar whether a string contains a valid value to use as a hostname or IP dirección.
     * IPv6 direcciones must include [], e.g. `[::1]`, not just `::1`.
     * 
     * @param string $host El host name or IP dirección to check
     * 
     * @return bool
     */
    public static function isValidHost($host)
    {
        // Simple syntax limits
        if (
            empty($host)
            || !is_string($host)
            || strlen($host) > 256
            || !preg_match('/^([a-z\d.-]*|\[[a-f\d:]+\])$/i', $host)
        ) {
            return false;
        }
        // Looks like a bracketed IPv6 dirección
        if (strlen($host) > 2 && substr($host, 0, 1) === '[' && substr($host, -1, 1) === ']') {
            return filter_var(substr($host, 1, -1), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
        }
        // If removing all the dots results in a numeric string, it must be an IPv4 dirección.
        // Need to check this first because otherwise things like `999.0.0.0` are considered valid host names
        if (is_numeric(str_replace('.', '', $host))) {
            // Is it a valid IPv4 dirección?
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
        }
        // Is it a syntactically valid hostname (when embedded in a URL)?
        return filter_var('https://' . $host, FILTER_VALIDATE_URL) !== false;
    }

    /**
 * TÍTULO: OBTENER AN ERROR MENSAJE IN THE ACTUAL LANGUAGE
 *
     * Obtener an error mensaje in the actual language.
     * 
     * @param string $key
     * 
     * @return string
     */
    protected function lang($key)
    {
        if (count($this->language) < 1) {
            $this->setLanguage(); // Establecer the default language
        }

        if (array_key_exists($key, $this->language)) {
            if ('smtp_connect_failed' === $key) {
                // Include a link to troubleshooting docs on SMTP conexión failure.
                // This is by far the biggest cause of support questions
                // but it's usually not PHPMailer's fault.
                return $this->language[$key] . ' https://github.com/PHPMailer/PHPMailer/wiki/Troubleshooting';
            }

            return $this->language[$key];
        }

        // Obtener the key as a fallback
        return $key;
    }

    /**
 * TÍTULO: BUILD AN ERROR MENSAJE STARTING WITH A GENERIC ONE AND ADDING DETAILS IF POSSIBLE
 *
     * Build an error mensaje starting with a generic one and adding details if possible.
     * 
     * @param string $base_key
     * @return string
     */
    private function getSmtpErrorMessage($base_key)
    {
        $message = $this->lang($base_key);
        $error = $this->smtp->getError();
        if (!empty($error['error'])) {
            $message .= ' ' . $error['error'];
            if (!empty($error['detail'])) {
                $message .= ' ' . $error['detail'];
            }
        }

        return $message;
    }

    /**
 * TÍTULO: COMPROBAR IF AN ERROR OCCURRED
 *
     * Comprobar if an error occurred.
     * 
     * @return bool True if an error did occur
     */
    public function isError()
    {
        return $this->error_count > 0;
    }

    /**
 * TÍTULO: AGREGAR A PERSONALIZADO HEADER
 *
     * Agregar a personalizado header.
     * $name value can be overloaded to contain
     * both header name and value (name:value).
     * 
     * @param string      $name  Custom header name
     * @param string|null $value Header value
     * 
     * @return bool True if a header was set successfully
     * @throws Exception
     */
    public function addCustomHeader($name, $value = null)
    {
        if (null === $value && strpos($name, ':') !== false) {
            // Value passed in as name:value
            list($name, $value) = explode(':', $name, 2);
        }
        $name = trim($name);
        $value = (null === $value) ? '' : trim($value);
        // Ensure name is not empty, and that neither name nor value contain salto de líneas
        if (empty($name) || strpbrk($name . $value, "\r\n") !== false) {
            if ($this->exceptions) {
                throw new Exception($this->lang('invalid_header'));
            }

            return false;
        }
        $this->CustomHeader[] = [$name, $value];

        return true;
    }

    /**
 * TÍTULO: RETURNS ALL PERSONALIZADO HEADERS
 *
     * Returns all personalizado headers.
     * 
     * @return array
     */
    public function getCustomHeaders()
    {
        return $this->CustomHeader;
    }

    /**
 * TÍTULO: CREAR A MENSAJE CONTENIDO FROM AN HTML STRING
 *
     * Crear a mensaje contenido from an HTML string.
     * Automatically inlines images and creates a plain-text version by converting the HTML,
     * overwriting any existing values in Body and AltBody.
     * Do not source $mensaje content from user input!
     * $basedir is prepended when handling relative URLs, e.g. <img src="/images/a.png"> and must not be empty
     * will look for an image file in $basedir/images/a.png and convert it to inline.
     * If you don't provide a $basedir, relative paths will be left untouched (and thus probably break in email)
     * Converts data-uri images into embedded attachments.
     * If you don't want to apply these transformations to your HTML, just set Body and AltBody directly.
     * 
     * @param string        $mensaje  HTML mensaje string
     * @param string        $basedir  Absolute path to a base directory to prepend to relative paths to images
     * @param bool|callable $advanced Indica si se debe use the internal HTML to text converter
     * or your own personalizado converter
     * @return string El transformed mensaje contenido
     * 
     * @throws Exception
     * 
     * @see PHPMailer::html2text()
     */
    public function msgHTML($message, $basedir = '', $advanced = false)
    {
        preg_match_all('/(?<!-)(src|background)=["\'](.*)["\']/Ui', $message, $images);
        if (array_key_exists(2, $images)) {
            if (strlen($basedir) > 1 && '/' !== substr($basedir, -1)) {
                // Ensure $basedir has a trailing /
                $basedir .= '/';
            }
            foreach ($images[2] as $imgindex => $url) {
                // Convertir data URIs into embedded images
                // e.g. "data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=="
                $match = [];
                if (preg_match('#^data:(image/(?:jpe?g|gif|png));?(base64)?,(.+)#', $url, $match)) {
                    if (count($match) === 4 && static::ENCODING_BASE64 === $match[2]) {
                        $data = base64_decode($match[3]);
                    } elseif ('' === $match[2]) {
                        $data = rawurldecode($match[3]);
                    } else {
                        // Not recognised so leave it alone
                        continue;
                    }
                    // Hash the decoded data, not the URL, so that the same data-URI image used in multiple places
                    // will only be embedded once, even if it used a different encoding
                    $cid = substr(hash('sha256', $data), 0, 32) . '@phpmailer.0'; // RFC2392 S 2

                    if (!$this->cidExists($cid)) {
                        $this->addStringEmbeddedImage(
                            $data,
                            $cid,
                            'embed' . $imgindex,
                            static::ENCODING_BASE64,
                            $match[1]
                        );
                    }
                    $message = str_replace(
                        $images[0][$imgindex],
                        $images[1][$imgindex] . '="cid:' . $cid . '"',
                        $message
                    );
                    continue;
                }
                if (
                    // Only process relative URLs if a basedir is provided (i.e. no absolute local paths)
                    !empty($basedir)
                    // Ignore URLs containing parent dir traversal (..)
                    && (strpos($url, '..') === false)
                    // Do not change urls that are already inline images
                    && 0 !== strpos($url, 'cid:')
                    // Do not change absolute URLs, including anonymous protocol
                    && !preg_match('#^[a-z][a-z0-9+.-]*:?//#i', $url)
                ) {
                    $filename = static::mb_pathinfo($url, PATHINFO_BASENAME);
                    $directory = dirname($url);
                    if ('.' === $directory) {
                        $directory = '';
                    }
                    // RFC2392 S 2
                    $cid = substr(hash('sha256', $url), 0, 32) . '@phpmailer.0';
                    if (strlen($basedir) > 1 && '/' !== substr($basedir, -1)) {
                        $basedir .= '/';
                    }
                    if (strlen($directory) > 1 && '/' !== substr($directory, -1)) {
                        $directory .= '/';
                    }
                    if (
                        $this->addEmbeddedImage(
                            $basedir . $directory . $filename,
                            $cid,
                            $filename,
                            static::ENCODING_BASE64,
                            static::_mime_types((string) static::mb_pathinfo($filename, PATHINFO_EXTENSION))
                        )
                    ) {
                        $message = preg_replace(
                            '/' . $images[1][$imgindex] . '=["\']' . preg_quote($url, '/') . '["\']/Ui',
                            $images[1][$imgindex] . '="cid:' . $cid . '"',
                            $message
                        );
                    }
                }
            }
        }
        $this->isHTML();
        // Convertir all mensaje contenido salto de líneas to LE, makes quoted-printable encoding work much better
        $this->Body = static::normalizeBreaks($message);
        $this->AltBody = static::normalizeBreaks($this->html2text($message, $advanced));
        if (!$this->alternativeExists()) {
            $this->AltBody = 'This is an HTML-only message. To view it, activate HTML in your email application.'
                . static::$LE;
        }

        return $this->Body;
    }

    /**
 * TÍTULO: CONVERTIR AN HTML STRING INTO TEXTO SIMPLE
 *
     * Convertir an HTML string into texto simple.
     * This is used by msgHTML().
     * Note - older versions of this function used a bundled advanced converter
     * which was removed for license reasons in #232.
     * Example usage:
     * 
     * ```php
     * //Usar default conversion
     * $plain = $mail->html2text($html);
     * //Usar your own personalizado converter
     * $plain = $mail->html2text($html, function($html) {
     * $converter = new MyHtml2text($html);
     * return $converter->get_text();
     * });
     * ```
     * 
     * @param string        $html     El HTML text to convert
     * @param bool|callable $advanced Any boolean value to use the internal converter,
     * or provide your own callable for personalizado conversion.
     * *Never* pass user-supplied data into this parameter
     * 
     * @return string
     */
    public function html2text($html, $advanced = false)
    {
        if (is_callable($advanced)) {
            return call_user_func($advanced, $html);
        }

        return html_entity_decode(
            trim(strip_tags(preg_replace('/<(head|title|style|script)[^>]*>.*?<\/\\1>/si', '', $html))),
            ENT_QUOTES,
            $this->CharSet
        );
    }

    /**
 * TÍTULO: OBTENER THE MIME TYPE FOR A FILE EXTENSION
 *
     * Obtener the MIME type for a file extension.
     * 
     * @param string $ext File extension
     * 
     * @return string MIME type of file
     */
    public static function _mime_types($ext = '')
    {
        $mimes = [
            'xl' => 'application/excel',
            'js' => 'application/javascript',
            'hqx' => 'application/mac-binhex40',
            'cpt' => 'application/mac-compactpro',
            'bin' => 'application/macbinary',
            'doc' => 'application/msword',
            'word' => 'application/msword',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
            'potx' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
            'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'sldx' => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
            'xlam' => 'application/vnd.ms-excel.addin.macroEnabled.12',
            'xlsb' => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
            'class' => 'application/octet-stream',
            'dll' => 'application/octet-stream',
            'dms' => 'application/octet-stream',
            'exe' => 'application/octet-stream',
            'lha' => 'application/octet-stream',
            'lzh' => 'application/octet-stream',
            'psd' => 'application/octet-stream',
            'sea' => 'application/octet-stream',
            'so' => 'application/octet-stream',
            'oda' => 'application/oda',
            'pdf' => 'application/pdf',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',
            'smi' => 'application/smil',
            'smil' => 'application/smil',
            'mif' => 'application/vnd.mif',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',
            'wbxml' => 'application/vnd.wap.wbxml',
            'wmlc' => 'application/vnd.wap.wmlc',
            'dcr' => 'application/x-director',
            'dir' => 'application/x-director',
            'dxr' => 'application/x-director',
            'dvi' => 'application/x-dvi',
            'gtar' => 'application/x-gtar',
            'php3' => 'application/x-httpd-php',
            'php4' => 'application/x-httpd-php',
            'php' => 'application/x-httpd-php',
            'phtml' => 'application/x-httpd-php',
            'phps' => 'application/x-httpd-php-source',
            'swf' => 'application/x-shockwave-flash',
            'sit' => 'application/x-stuffit',
            'tar' => 'application/x-tar',
            'tgz' => 'application/x-tar',
            'xht' => 'application/xhtml+xml',
            'xhtml' => 'application/xhtml+xml',
            'zip' => 'application/zip',
            'mid' => 'audio/midi',
            'midi' => 'audio/midi',
            'mp2' => 'audio/mpeg',
            'mp3' => 'audio/mpeg',
            'm4a' => 'audio/mp4',
            'mpga' => 'audio/mpeg',
            'aif' => 'audio/x-aiff',
            'aifc' => 'audio/x-aiff',
            'aiff' => 'audio/x-aiff',
            'ram' => 'audio/x-pn-realaudio',
            'rm' => 'audio/x-pn-realaudio',
            'rpm' => 'audio/x-pn-realaudio-plugin',
            'ra' => 'audio/x-realaudio',
            'wav' => 'audio/x-wav',
            'mka' => 'audio/x-matroska',
            'bmp' => 'image/bmp',
            'gif' => 'image/gif',
            'jpeg' => 'image/jpeg',
            'jpe' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'webp' => 'image/webp',
            'avif' => 'image/avif',
            'heif' => 'image/heif',
            'heifs' => 'image/heif-sequence',
            'heic' => 'image/heic',
            'heics' => 'image/heic-sequence',
            'eml' => 'message/rfc822',
            'css' => 'text/css',
            'html' => 'text/html',
            'htm' => 'text/html',
            'shtml' => 'text/html',
            'log' => 'text/plain',
            'text' => 'text/plain',
            'txt' => 'text/plain',
            'rtx' => 'text/richtext',
            'rtf' => 'text/rtf',
            'vcf' => 'text/vcard',
            'vcard' => 'text/vcard',
            'ics' => 'text/calendar',
            'xml' => 'text/xml',
            'xsl' => 'text/xml',
            'csv' => 'text/csv',
            'wmv' => 'video/x-ms-wmv',
            'mpeg' => 'video/mpeg',
            'mpe' => 'video/mpeg',
            'mpg' => 'video/mpeg',
            'mp4' => 'video/mp4',
            'm4v' => 'video/mp4',
            'mov' => 'video/quicktime',
            'qt' => 'video/quicktime',
            'rv' => 'video/vnd.rn-realvideo',
            'avi' => 'video/x-msvideo',
            'movie' => 'video/x-sgi-movie',
            'webm' => 'video/webm',
            'mkv' => 'video/x-matroska',
        ];
        $ext = strtolower($ext);
        if (array_key_exists($ext, $mimes)) {
            return $mimes[$ext];
        }

        return 'application/octet-stream';
    }

    /**
 * TÍTULO: MAP A FILE NAME TO A MIME TYPE
 *
     * Map a file name to a MIME type.
     * Defaults to 'application/octet-stream', i.e.. arbitrary binary data.
     * 
     * @param string $filename A file name or full path, does not need to exist as a file
     * 
     * @return string
     */
    public static function filenameToType($filename)
    {
        // In case the path is a URL, strip any query string before getting extension
        $qpos = strpos($filename, '?');
        if (false !== $qpos) {
            $filename = substr($filename, 0, $qpos);
        }
        $ext = static::mb_pathinfo($filename, PATHINFO_EXTENSION);

        return static::_mime_types($ext);
    }

    /**
 * TÍTULO: MULTI-BYTE-SAFE PATHINFO REPLACEMENT
 *
     * Multi-byte-safe pathinfo replacement.
     * Drop-in replacement for pathinfo(), but multibyte- and cross-platform-safe.
     * 
     * @see https://www.php.net/manual/en/function.pathinfo.php#107461
     * 
     * @param string     $path    A filename or path, does not need to exist as a file
     * @param int|string $options Either a PATHINFO_* constant,
     * or a string name to return only the specified piece
     * 
     * @return string|array
     */
    public static function mb_pathinfo($path, $options = null)
    {
        $ret = ['dirname' => '', 'basename' => '', 'extension' => '', 'filename' => ''];
        $pathinfo = [];
        if (preg_match('#^(.*?)[\\\\/]*(([^/\\\\]*?)(\.([^.\\\\/]+?)|))[\\\\/.]*$#m', $path, $pathinfo)) {
            if (array_key_exists(1, $pathinfo)) {
                $ret['dirname'] = $pathinfo[1];
            }
            if (array_key_exists(2, $pathinfo)) {
                $ret['basename'] = $pathinfo[2];
            }
            if (array_key_exists(5, $pathinfo)) {
                $ret['extension'] = $pathinfo[5];
            }
            if (array_key_exists(3, $pathinfo)) {
                $ret['filename'] = $pathinfo[3];
            }
        }
        switch ($options) {
            case PATHINFO_DIRNAME:
            case 'dirname':
                return $ret['dirname'];
            case PATHINFO_BASENAME:
            case 'basename':
                return $ret['basename'];
            case PATHINFO_EXTENSION:
            case 'extension':
                return $ret['extension'];
            case PATHINFO_FILENAME:
            case 'filename':
                return $ret['filename'];
            default:
                return $ret;
        }
    }

    /**
 * TÍTULO: ESTABLECER OR RESET INSTANCE PROPERTIES
 *
     * Establecer or reset instance properties.
     * You should avoid this function - it's more verbose, less efficient, more error-prone and
     * harder to debug than setting properties directly.
     * Usage Example:
     * `$mail->set('SMTPSecure', static::ENCRYPTION_STARTTLS);`
     * is the same as:
     * `$mail->SMTPSecure = static::ENCRYPTION_STARTTLS;`.
     * 
     * @param string $name  El property name to set
     * @param mixed  $value El value to set the property to
     * 
     * @return bool
     */
    public function set($name, $value = '')
    {
        if (property_exists($this, $name)) {
            $this->{$name} = $value;

            return true;
        }
        $this->setError($this->lang('variable_set') . $name);

        return false;
    }

    /**
 * TÍTULO: STRIP NEWLINES TO PREVENT HEADER INJECTION
 *
     * Strip newlines to prevent header injection.
     * 
     * @param string $str
     * 
     * @return string
     */
    public function secureHeader($str)
    {
        return trim(str_replace(["\r", "\n"], '', $str));
    }

    /**
 * TÍTULO: NORMALIZE SALTO DE LÍNEAS IN A STRING
 *
     * Normalize salto de líneas in a string.
     * Converts UNIX LF, Mac CR and Windows CRLF salto de líneas into a single salto de línea format.
     * Defaults to CRLF (for mensaje bodies) and preserves consecutive breaks.
     * 
     * @param string $text
     * @param string $breaktype What kind of salto de línea to use; defaults to static::$LE
     * 
     * @return string
     */
    public static function normalizeBreaks($text, $breaktype = null)
    {
        if (null === $breaktype) {
            $breaktype = static::$LE;
        }
        // Normalise to \n
        $text = str_replace([self::CRLF, "\r"], "\n", $text);
        // Now convert LE as needed
        if ("\n" !== $breaktype) {
            $text = str_replace("\n", $breaktype, $text);
        }

        return $text;
    }

    /**
 * TÍTULO: REMOVE TRAILING WHITESPACE FROM A STRING
 *
     * Remove trailing whitespace from a string.
     * 
     * @param string $text
     * 
     * @return string El text to remove whitespace from
     */
    public static function stripTrailingWSP($text)
    {
        return rtrim($text, " \r\n\t");
    }

    /**
 * TÍTULO: STRIP TRAILING SALTO DE LÍNEAS FROM A STRING
 *
     * Strip trailing salto de líneas from a string.
     * 
     * @param string $text
     * 
     * @return string El text to remove breaks from
     */
    public static function stripTrailingBreaks($text)
    {
        return rtrim($text, "\r\n");
    }

    /**
 * TÍTULO: OBTENER THE ACTUAL SALTO DE LÍNEA FORMAT STRING
 *
     * Obtener the actual salto de línea format string.
     * 
     * @return string
     */
    public static function getLE()
    {
        return static::$LE;
    }

    /**
 * TÍTULO: ESTABLECER THE SALTO DE LÍNEA FORMAT STRING, E.G. "\R\N"
 *
     * Establecer the salto de línea format string, e.g. "\r\n".
     * 
     * @param string $le
     */
    protected static function setLE($le)
    {
        static::$LE = $le;
    }

    /**
 * TÍTULO: ESTABLECER THE PUBLIC AND PRIVATE KEY FILES AND CONTRASEÑA FOR S/MIME SIGNING
 *
     * Establecer the public and private key files and contraseña for S/MIME signing.
     * 
     * @param string $cert_filename
     * @param string $key_filename
     * @param string $key_pass            Password for private key
     * @param string $extracerts_filename Optional path to chain certificate
     */
    public function sign($cert_filename, $key_filename, $key_pass, $extracerts_filename = '')
    {
        $this->sign_cert_file = $cert_filename;
        $this->sign_key_file = $key_filename;
        $this->sign_key_pass = $key_pass;
        $this->sign_extracerts_file = $extracerts_filename;
    }

    /**
 * TÍTULO: QUOTED-PRINTABLE-ENCODE A DKIM HEADER
 *
     * Quoted-Printable-encode a DKIM header.
     * 
     * @param string $txt
     * 
     * @return string
     */
    public function DKIM_QP($txt)
    {
        $line = '';
        $len = strlen($txt);
        for ($i = 0; $i < $len; ++$i) {
            $ord = ord($txt[$i]);
            if (((0x21 <= $ord) && ($ord <= 0x3A)) || $ord === 0x3C || ((0x3E <= $ord) && ($ord <= 0x7E))) {
                $line .= $txt[$i];
            } else {
                $line .= '=' . sprintf('%02X', $ord);
            }
        }

        return $line;
    }

    /**
 * TÍTULO: GENERATE A DKIM SIGNATURE
 *
     * Generate a DKIM signature.
     * 
     * @param string $signHeader
     * 
     * @throws Exception
     * 
     * @return string El DKIM signature value
     */
    public function DKIM_Sign($signHeader)
    {
        if (!defined('PKCS7_TEXT')) {
            if ($this->exceptions) {
                throw new Exception($this->lang('extension_missing') . 'openssl');
            }

            return '';
        }
        $privKeyStr = !empty($this->DKIM_private_string) ?
            $this->DKIM_private_string :
            file_get_contents($this->DKIM_private);
        if ('' !== $this->DKIM_passphrase) {
            $privKey = openssl_pkey_get_private($privKeyStr, $this->DKIM_passphrase);
        } else {
            $privKey = openssl_pkey_get_private($privKeyStr);
        }
        if (openssl_sign($signHeader, $signature, $privKey, 'sha256WithRSAEncryption')) {
            if (\PHP_MAJOR_VERSION < 8) {
                openssl_pkey_free($privKey);
            }

            return base64_encode($signature);
        }
        if (\PHP_MAJOR_VERSION < 8) {
            openssl_pkey_free($privKey);
        }

        return '';
    }

    /**
 * TÍTULO: GENERATE A DKIM CANONICALIZATION HEADER
 *
     * Generate a DKIM canonicalization header.
     * Uses the 'relaxed' algorithm from RFC6376 section 3.4.2.
     * Canonicalized headers should *always* use CRLF, regardless of mailer setting.
     * 
     * @see https://www.rfc-editor.org/rfc/rfc6376#section-3.4.2
     * 
     * @param string $signHeader Header
     * 
     * @return string
     */
    public function DKIM_HeaderC($signHeader)
    {
        // Normalize breaks to CRLF (regardless of the mailer)
        $signHeader = static::normalizeBreaks($signHeader, self::CRLF);
        // Unfold header lines
        // Note PCRE \s is too broad a definition of whitespace; RFC5322 defines it as `[ \t]`
        // @see https://www.rfc-editor.org/rfc/rfc5322#section-2.2
        // That means this may break if you do something daft like put vertical tabs in your headers.
        $signHeader = preg_replace('/\r\n[ \t]+/', ' ', $signHeader);
        // Break headers out into an array
        $lines = explode(self::CRLF, $signHeader);
        foreach ($lines as $key => $line) {
            // If the header is missing a :, skip it as it's invalid
            // This is likely to happen because the explode() above will also split
            // on the trailing LE, leaving an empty line
            if (strpos($line, ':') === false) {
                continue;
            }
            list($heading, $value) = explode(':', $line, 2);
            // Lower-case header name
            $heading = strtolower($heading);
            // Collapse white space within the value, also convert WSP to space
            $value = preg_replace('/[ \t]+/', ' ', $value);
            // RFC6376 is slightly unclear here - it says to delete space at the *end* of each value
            // But then says to delete space before and after the colon.
            // Net result is the same as trimming both ends of the value.
            // By elimination, the same applies to the field name
            $lines[$key] = trim($heading, " \t") . ':' . trim($value, " \t");
        }

        return implode(self::CRLF, $lines);
    }

    /**
 * TÍTULO: GENERATE A DKIM CANONICALIZATION CONTENIDO
 *
     * Generate a DKIM canonicalization contenido.
     * Uses the 'simple' algorithm from RFC6376 section 3.4.3.
     * Canonicalized bodies should *always* use CRLF, regardless of mailer setting.
     * 
     * @see https://www.rfc-editor.org/rfc/rfc6376#section-3.4.3
     * 
     * @param string $contenido Message Body
     * 
     * @return string
     */
    public function DKIM_BodyC($body)
    {
        if (empty($body)) {
            return self::CRLF;
        }
        // Normalize line endings to CRLF
        $body = static::normalizeBreaks($body, self::CRLF);

        // Reduce multiple trailing salto de líneas to a single one
        return static::stripTrailingBreaks($body) . self::CRLF;
    }

    /**
 * TÍTULO: CREAR THE DKIM HEADER AND CONTENIDO IN A NEW MENSAJE HEADER
 *
     * Crear the DKIM header and contenido in a new mensaje header.
     * 
     * @param string $headers_line Header lines
     * @param string $asunto      Subject
     * @param string $contenido         Body
     * 
     * @throws Exception
     * 
     * @return string
     */
    public function DKIM_Add($headers_line, $subject, $body)
    {
        $DKIMsignatureType = 'rsa-sha256'; // Signature & hash algorithms
        $DKIMcanonicalization = 'relaxed/simple'; // Canonicalization methods of header & contenido
        $DKIMquery = 'dns/txt'; // Query method
        $DKIMtime = time();
        // Always sign these headers without being asked
        // Recommended list from https://www.rfc-editor.org/rfc/rfc6376#section-5.4.1
        $autoSignHeaders = [
            'from',
            'to',
            'cc',
            'date',
            'subject',
            'reply-to',
            'message-id',
            'content-type',
            'mime-version',
            'x-mailer',
        ];
        if (stripos($headers_line, 'Subject') === false) {
            $headers_line .= 'Subject: ' . $subject . static::$LE;
        }
        $headerLines = explode(static::$LE, $headers_line);
        $currentHeaderLabel = '';
        $currentHeaderValue = '';
        $parsedHeaders = [];
        $headerLineIndex = 0;
        $headerLineCount = count($headerLines);
        foreach ($headerLines as $headerLine) {
            $matches = [];
            if (preg_match('/^([^ \t]*?)(?::[ \t]*)(.*)$/', $headerLine, $matches)) {
                if ($currentHeaderLabel !== '') {
                    // We were previously in another header; This is the start of a new header, so save the previous one
                    $parsedHeaders[] = ['label' => $currentHeaderLabel, 'value' => $currentHeaderValue];
                }
                $currentHeaderLabel = $matches[1];
                $currentHeaderValue = $matches[2];
            } elseif (preg_match('/^[ \t]+(.*)$/', $headerLine, $matches)) {
                // This is a folded continuation of the actual header, so unfold it
                $currentHeaderValue .= ' ' . $matches[1];
            }
            ++$headerLineIndex;
            if ($headerLineIndex >= $headerLineCount) {
                // This was the last line, so finish off this header
                $parsedHeaders[] = ['label' => $currentHeaderLabel, 'value' => $currentHeaderValue];
            }
        }
        $copiedHeaders = [];
        $headersToSignKeys = [];
        $headersToSign = [];
        foreach ($parsedHeaders as $header) {
            // Is this header one that must be included in the DKIM signature?
            if (in_array(strtolower($header['label']), $autoSignHeaders, true)) {
                $headersToSignKeys[] = $header['label'];
                $headersToSign[] = $header['label'] . ': ' . $header['value'];
                if ($this->DKIM_copyHeaderFields) {
                    $copiedHeaders[] = $header['label'] . ':' . // Note no space after this, as per RFC
                        str_replace('|', '=7C', $this->DKIM_QP($header['value']));
                }
                continue;
            }
            // Is this an extra personalizado header we've been asked to sign?
            if (in_array($header['label'], $this->DKIM_extraHeaders, true)) {
                // Find its value in personalizado headers
                foreach ($this->CustomHeader as $customHeader) {
                    if ($customHeader[0] === $header['label']) {
                        $headersToSignKeys[] = $header['label'];
                        $headersToSign[] = $header['label'] . ': ' . $header['value'];
                        if ($this->DKIM_copyHeaderFields) {
                            $copiedHeaders[] = $header['label'] . ':' . // Note no space after this, as per RFC
                                str_replace('|', '=7C', $this->DKIM_QP($header['value']));
                        }
                        // Skip straight to the next header
                        continue 2;
                    }
                }
            }
        }
        $copiedHeaderFields = '';
        if ($this->DKIM_copyHeaderFields && count($copiedHeaders) > 0) {
            // Assemble a DKIM 'z' tag
            $copiedHeaderFields = ' z=';
            $first = true;
            foreach ($copiedHeaders as $copiedHeader) {
                if (!$first) {
                    $copiedHeaderFields .= static::$LE . ' |';
                }
                // Fold long values
                if (strlen($copiedHeader) > self::STD_LINE_LENGTH - 3) {
                    $copiedHeaderFields .= substr(
                        chunk_split($copiedHeader, self::STD_LINE_LENGTH - 3, static::$LE . self::FWS),
                        0,
                        -strlen(static::$LE . self::FWS)
                    );
                } else {
                    $copiedHeaderFields .= $copiedHeader;
                }
                $first = false;
            }
            $copiedHeaderFields .= ';' . static::$LE;
        }
        $headerKeys = ' h=' . implode(':', $headersToSignKeys) . ';' . static::$LE;
        $headerValues = implode(static::$LE, $headersToSign);
        $body = $this->DKIM_BodyC($body);
        // Base64 of packed binary SHA-256 hash of contenido
        $DKIMb64 = base64_encode(pack('H*', hash('sha256', $body)));
        $ident = '';
        if ('' !== $this->DKIM_identity) {
            $ident = ' i=' . $this->DKIM_identity . ';' . static::$LE;
        }
        // El DKIM-Signature header is included in the signature *except for* the value of the `b` tag
        // which is appended after calculating the signature
        // https://www.rfc-editor.org/rfc/rfc6376#section-3.5
        $dkimSignatureHeader = 'DKIM-Signature: v=1;' .
            ' d=' . $this->DKIM_domain . ';' .
            ' s=' . $this->DKIM_selector . ';' . static::$LE .
            ' a=' . $DKIMsignatureType . ';' .
            ' q=' . $DKIMquery . ';' .
            ' t=' . $DKIMtime . ';' .
            ' c=' . $DKIMcanonicalization . ';' . static::$LE .
            $headerKeys .
            $ident .
            $copiedHeaderFields .
            ' bh=' . $DKIMb64 . ';' . static::$LE .
            ' b=';
        // Canonicalize the set of headers
        $canonicalizedHeaders = $this->DKIM_HeaderC(
            $headerValues . static::$LE . $dkimSignatureHeader
        );
        $signature = $this->DKIM_Sign($canonicalizedHeaders);
        $signature = trim(chunk_split($signature, self::STD_LINE_LENGTH - 3, static::$LE . self::FWS));

        return static::normalizeBreaks($dkimSignatureHeader . $signature);
    }

    /**
 * TÍTULO: DETECT IF A STRING CONTAINS A LINE LONGER THAN THE MAXIMUM LONGITUD DE LÍNEA
 *
     * Detect if a string contains a line longer than the maximum longitud de línea
     * allowed by RFC 2822 section 2.1.1.
     * 
     * @param string $str
     * 
     * @return bool
     */
    public static function hasLineLongerThanMax($str)
    {
        return (bool) preg_match('/^(.{' . (self::MAX_LINE_LENGTH + strlen(static::$LE)) . ',})/m', $str);
    }

    /**
 * TÍTULO: IF A STRING CONTAINS ANY "SPECIAL" CHARACTERS, DOUBLE-QUOTE THE NAME,
 *
     * If a string contains any "special" characters, double-quote the name,
     * and escape any double quotes with a backslash.
     * 
     * @param string $str
     * 
     * @return string
     * 
     * @see RFC822 3.4.1
     */
    public static function quotedString($str)
    {
        if (preg_match('/[ ()<>@,;:"\/\[\]?=]/', $str)) {
            // If the string contains any of these chars, it must be double-quoted
            // and any double quotes must be escaped with a backslash
            return '"' . str_replace('"', '\\"', $str) . '"';
        }

        // Obtener the string untouched, it doesn't need quoting
        return $str;
    }

    /**
 * TÍTULO: PERMITE FOR PUBLIC READ ACCESS TO 'TO' PROPERTY
 *
     * Permite for public read access to 'to' property.
     * Before the send() call, queued direcciones (i.e. with IDN) are not yet included.
     * 
     * @return array
     */
    public function getToAddresses()
    {
        return $this->to;
    }

    /**
 * TÍTULO: PERMITE LEER PÚBLICAMENTE LA PROPIEDAD 'CC'
 *
     * Permite leer públicamente la propiedad 'cc'.
     * Before the send() call, queued direcciones (i.e. with IDN) are not yet included.
     * 
     * @return array
     */
    public function getCcAddresses()
    {
        return $this->cc;
    }

    /**
 * TÍTULO: PERMITE LEER PÚBLICAMENTE LA PROPIEDAD 'BCC'
 *
     * Permite leer públicamente la propiedad 'bcc'.
     * Before the send() call, queued direcciones (i.e. with IDN) are not yet included.
     * 
     * @return array
     */
    public function getBccAddresses()
    {
        return $this->bcc;
    }

    /**
 * TÍTULO: PERMITE LEER PÚBLICAMENTE LA PROPIEDAD 'REPLYTO'
 *
     * Permite leer públicamente la propiedad 'ReplyTo'.
     * Before the send() call, queued direcciones (i.e. with IDN) are not yet included.
     * 
     * @return array
     */
    public function getReplyToAddresses()
    {
        return $this->ReplyTo;
    }

    /**
 * TÍTULO: PERMITE LEER PÚBLICAMENTE LA PROPIEDAD 'ALL_RECIPIENTS'
 *
     * Permite leer públicamente la propiedad 'all_recipients'.
     * Before the send() call, queued direcciones (i.e. with IDN) are not yet included.
     * 
     * @return array
     */
    public function getAllRecipientAddresses()
    {
        return $this->all_recipients;
    }

    /**
 * TÍTULO: EJECUTAR UNA FUNCIÓN DE RESPUESTA
 *
     * Ejecutar una función de respuesta.
     * 
     * @param bool   $isSent
     * @param array  $to
     * @param array  $cc
     * @param array  $bcc
     * @param string $asunto
     * @param string $contenido
     * @param string $from
     * @param array  $extra
     */
    protected function doCallback($isSent, $to, $cc, $bcc, $subject, $body, $from, $extra)
    {
        if (!empty($this->action_function) && is_callable($this->action_function)) {
            call_user_func($this->action_function, $isSent, $to, $cc, $bcc, $subject, $body, $from, $extra);
        }
    }

    /**
 * TÍTULO: OBTENER LA INSTANCIA DE OAUTHTOKENPROVIDER
 *
     * Obtener la instancia de OAuthTokenProvider.
     * 
     * @return OAuthTokenProvider
     */
    public function getOAuth()
    {
        return $this->oauth;
    }

    /**
 * TÍTULO: DEFINIR UNA INSTANCIA DE OAUTHTOKENPROVIDER
 *
     * Definir una instancia de OAuthTokenProvider.
     */
    public function setOAuth(OAuthTokenProvider $oauth)
    {
        $this->oauth = $oauth;
    }
}

?>

<!-- -------------------------------------------------------------------------------------------------------------
   -------------------------------------- FIN Jerez PHPMailer.php --------------------------------------
   ------------------------------------------------------------------------------------------------------------- -->


<!--
    TÍTULO: INFORMACIÓN DEL SITIO WEB
    Sitio Web Creado por Jerez.
    Dirección: Miraflorez #1280
    Quintero - Chile
    jaime.jerez1978@gmail.com
    https:// www.
    Creado, Programado y Diseñado por Jerez.
    JJA
-->