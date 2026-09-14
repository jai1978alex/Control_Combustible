<!--
    TÍTULO: CLASE SMTP PARA ENVÍO DE CORREO

    Sitio Web Creado por Jerez.
    Dirección: Miraflorez #1280
    Quintero - Chile
    jaime.jerez1978@gmail.com
    https://www.
    Creado, Programado y Diseñado por Jerez.
    JJA
-->


<!-- -------------------------------------------------------------------------------------------------------------
   ------------------------------------- INICIO Jerez SMTP.php ------------------------------------
   ------------------------------------------------------------------------------------------------------------- -->

<?php
namespace PHPMailer\PHPMailer;
?>

<?php

/*
 * Clase de transporte de correo SMTP de PHPMailer basada en RFC821.
 * Versión de PHP indicada por la biblioteca.
 * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
 * @ver       https://github.com/PHPMailer/PHPMailer/ Proyecto de PHPMailer en GitHub
 * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
 * @autor    Marcus Bointon (Synchro/coolbru) <phpmailer@synchromedia.co.uk>
 * @autor    Jim Jagielski (jimjag) <jimjag@gmail.com>
 * @autor    Andy Prevost (codeworxtech) <codeworxtech@users.sourceforge.net>
 * @autor    Brent R. Matzelle (original founder)
 * @derechos de autor 2012 - 2020 Marcus Bointon
 * @derechos de autor 2010 - 2012 Jim Jagielski
 * @derechos de autor 2004 - 2009 Andy Prevost
 * @licencia   https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html Licencia Pública General Reducida de GNU
 * @nota      Este programa se distribuye con la intención de que sea útil - SIN
 * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
 * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
 */


/*
 * Clase de transporte de correo SMTP de PHPMailer basada en RFC821.
 * Implementa los comandos SMTP de RFC 821 y ofrece funciones de apoyo para enviar correo mediante un servidor SMTP.
 * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
 * @autor Chris Ryan
 * @autor Marcus Bointon <phpmailer@synchromedia.co.uk>
 */
class SMTP
{
    /*
     * Número de versión de SMTP de PHPMailer.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @tipo string
     */
    const VERSION = '6.9.3';

    /*
     * Constante para el salto de línea usado por SMTP.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @tipo string
     */
    const LE = "\r\n";

    /*
     * Puerto SMTP que se usa cuando no se indica otro.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @tipo int
     */
    const DEFAULT_PORT = 25;

    /*
     * Puerto SMTPS que se usa cuando no se indica otro.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @tipo int
     */
    const DEFAULT_SECURE_PORT = 465;

    /*
     * Longitud máxima de línea permitida por la sección 4.5.3.1.6 de RFC 5321,
     * *sin contar* el salto CRLF final.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @ver https://www.rfc-editor.org/rfc/rfc5321#section-4.5.3.1.6
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @tipo int
     */
    const MAX_LINE_LENGTH = 998;

    /*
     * Longitud máxima permitida para respuestas según la sección 4.5.3.1.5 de RFC 5321,
     * *incluyendo* el salto de línea CRLF final.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @ver https://www.rfc-editor.org/rfc/rfc5321#section-4.5.3.1.5
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @tipo int
     */
    const MAX_REPLY_LENGTH = 512;

    /*
     * Nivel de depuración sin mostrar información.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @tipo int
     */
    const DEBUG_OFF = 0;

    /*
     * Nivel de depuración para mostrar mensajes del cliente al servidor.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @tipo int
     */
    const DEBUG_CLIENT = 1;

    /*
     * Nivel de depuración para mostrar mensajes del cliente al servidor y del servidor al cliente.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @tipo int
     */
    const DEBUG_SERVER = 2;

    /*
     * Nivel de depuración para mostrar el estado de la conexión y los mensajes en ambos sentidos.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @tipo int
     */
    const DEBUG_CONNECTION = 3;

    /*
     * Nivel de depuración para mostrar todos los mensajes.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @tipo int
     */
    const DEBUG_LOWLEVEL = 4;

    /*
     * Nivel de salida de depuración.
     * Opciones:
     * * self::DEBUG_OFF (`0`) Sin salida de depuración; valor predeterminado
     * * self::DEBUG_CLIENT (`1`) Comandos del cliente
     * * self::DEBUG_SERVER (`2`) Comandos del cliente y respuestas del servidor
     * * self::DEBUG_CONNECTION (`3`) Igual que DEBUG_SERVER y además el estado de la conexión
     * * self::DEBUG_LOWLEVEL (`4`) Salida detallada de bajo nivel con todos los mensajes.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @tipo int
     */
    public $do_debug = self::DEBUG_OFF;

    /*
     * Forma de mostrar la información de depuración.
     * Opciones:
     * * `echo` Muestra texto normal tal cual, adecuado para consola.
     * * `html` Muestra texto protegido y cambia los saltos de línea por `<br>`, adecuado para el navegador.
     * * `error_log` Guarda la información en el registro de errores configurado en php.ini.
     * También se puede entregar una función que reciba dos datos: el mensaje y el nivel de depuración:
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * ```php
     * $smtp->Debugoutput = function($str, $level) {echo "debug level $level; message: $str";};
     * ```
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * También se puede entregar un registro compatible con PSR-3; solo se usa el nivel `debug`
     * para la salida.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * ```php
     * $mail->Debugoutput = new myPsr3Logger;
     * ```
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @tipo string|callable|\Psr\Log\LoggerInterface
     */
    public $Debugoutput = 'echo';

    /*
     * Indica si se usará VERP.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @ver https://en.wikipedia.org/wiki/Variable_envelope_return_path
     * @ver https://www.postfix.org/VERP_README.html Información sobre VERP
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @tipo bool
     */
    public $do_verp = false;

    /*
     * Tiempo máximo de espera de la conexión, en segundos.
     * El valor predeterminado es de 5 minutos (300 segundos), según RFC2821 sección 4.5.3.2.
     * Debe ser suficientemente alto para funcionar correctamente con servidores que usan una espera inicial contra spam.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @ver https://www.rfc-editor.org/rfc/rfc2821#section-4.5.3.2
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @tipo int
     */
    public $Timeout = 300;

    /*
     * Tiempo máximo de espera para completar los comandos, en segundos.
     * El valor predeterminado es de 5 minutos (300 segundos), según RFC2821 sección 4.5.3.2.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @tipo int
     */
    public $Timelimit = 300;

    /*
     * Patrones para obtener el identificador de una operación SMTP desde la respuesta al comando DATA.
     * El primer grupo capturado de cada expresión se usará como identificador.
     * MS ESMTP devuelve el identificador del mensaje, que puede no ser adecuado para el seguimiento interno.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @tipo string[]
     */
    protected $smtp_transaction_id_patterns = [
        'exim' => '/[\d]{3} OK id=(.*)/',
        'sendmail' => '/[\d]{3} 2\.0\.0 (.*) Message/',
        'postfix' => '/[\d]{3} 2\.0\.0 Ok: queued as (.*)/',
        'Microsoft_ESMTP' => '/[0-9]{3} 2\.[\d]\.0 (.*)@(?:.*) Queued mail for delivery/',
        'Amazon_SES' => '/[\d]{3} Ok (.*)/',
        'SendGrid' => '/[\d]{3} Ok: queued as (.*)/',
        'CampaignMonitor' => '/[\d]{3} 2\.0\.0 OK:([a-zA-Z\d]{48})/',
        'Haraka' => '/[\d]{3} Message Queued \((.*)\)/',
        'ZoneMTA' => '/[\d]{3} Message queued as (.*)/',
        'Mailjet' => '/[\d]{3} OK queued as (.*)/',
    ];

    /*
     * Atributos XCLIENT permitidos por SMTP.
     * El servidor SMTP debe permitirlos. No se comprueba la respuesta de EHLO.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @ver https://www.postfix.org/XCLIENT_README.html
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @tipo array
     */
    public static $xclient_allowed_attributes = [
        'NAME', 'ADDR', 'PORT', 'PROTO', 'HELO', 'LOGIN', 'DESTADDR', 'DESTPORT'
    ];

    /*
     * Último identificador de operación entregado como respuesta a un comando DATA,
     * si se encontró uno.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @tipo string|bool|null
     */
    protected $last_smtp_transaction_id;

    /*
     * Conexión usada para comunicarse con el servidor.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @tipo ?resource
     */
    protected $smtp_conn;

    /*
     * Información de error, si existe, del último comando SMTP.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @tipo array
     */
    protected $error = [
        'error' => '',
        'detail' => '',
        'smtp_code' => '',
        'smtp_code_ex' => '',
    ];

    /*
     * Respuesta enviada por el servidor al comando HELO.
     * Si es null, todavía no se ha recibido una respuesta HELO.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @tipo string|null
     */
    protected $helo_rply;

    /*
     * Conjunto de extensiones SMTP enviadas como respuesta al comando EHLO.
     * Los índices del arreglo contienen los nombres de las extensiones.
     * El valor de 'HELO' o 'EHLO', según el comando enviado,
     * representa el nombre del servidor. Con HELO es el único elemento del arreglo.
     * Los demás valores pueden ser TRUE o un arreglo con opciones de la extensión.
     * Si es null, todavía no se ha recibido una respuesta HELO/EHLO.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @tipo array|null
     */
    protected $server_caps;

    /*
     * Respuesta más reciente recibida desde el servidor.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @tipo string
     */
    protected $last_reply = '';

    /*
     * Muestra información de depuración usando el método seleccionado.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @parámetro string $str   Texto de depuración que se mostrará
     * @parámetro int    $level Nivel de depuración de este mensaje; ver las constantes DEBUG_*
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @ver SMTP::$Debugoutput
     * @ver SMTP::$do_debug
     */
    protected function edebug($str, $level = 0)
    {
        if ($level > $this->do_debug) {
            return;
        }
        // Comentario: esta parte prepara o procesa la conexión SMTP.
        if ($this->Debugoutput instanceof \Psr\Log\LoggerInterface) {
            // Comentario: esta parte prepara o procesa la conexión SMTP.
            $this->Debugoutput->debug(rtrim($str, "\r\n"));

            return;
        }
        // Comentario: esta parte prepara o procesa la conexión SMTP.
        if (is_callable($this->Debugoutput) && !in_array($this->Debugoutput, ['error_log', 'html', 'echo'])) {
            call_user_func($this->Debugoutput, $str, $level);

            return;
        }
        switch ($this->Debugoutput) {
            case 'error_log':
                // Comentario: esta parte prepara o procesa la conexión SMTP.
                /** Esta línea evita mostrar una advertencia innecesaria. */
                error_log($str);
                break;
            case 'html':
                // Comentario: esta parte prepara o procesa la conexión SMTP.
                echo gmdate('Y-m-d H:i:s'), ' ', htmlentities(
                    preg_replace('/[\r\n]+/', '', $str),
                    ENT_QUOTES,
                    'UTF-8'
                ), "<br>\n";
                break;
            case 'echo':
            default:
                // Comentario: esta parte prepara o procesa la conexión SMTP.
                $str = preg_replace('/\r\n|\r/m', "\n", $str);
                echo gmdate('Y-m-d H:i:s'),
                "\t",
                    // Comentario: esta parte prepara o procesa la conexión SMTP.
                trim(
                    // Comentario: esta parte prepara o procesa la conexión SMTP.
                    str_replace(
                        "\n",
                        "\n                   \t                  ",
                        trim($str)
                    )
                ),
                "\n";
        }
    }

    /*
     * Conecta con un servidor SMTP.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @parámetro string $host    IP o nombre del servidor SMTP
     * @parámetro int    $port    Número de puerto al que se conectará
     * @parámetro int    $timeout Tiempo máximo de espera para abrir la conexión
     * @parámetro array  $options Arreglo de opciones para stream_context_create()
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve bool
     */
    public function connect($host, $port = null, $timeout = 30, $options = [])
    {
        // Limpia los errores anteriores para evitar confusiones
        $this->setError('');
        // Comprueba que todavía no exista una conexión activa
        if ($this->connected()) {
            // Ya existe una conexión; se genera un error
            $this->setError('Already connected to a server');

            return false;
        }
        if (empty($port)) {
            $port = self::DEFAULT_PORT;
        }
        // Conecta con el servidor SMTP
        $this->edebug(
            "Connection: opening to $host:$port, timeout=$timeout, options=" .
            (count($options) > 0 ? var_export($options, true) : 'array()'),
            self::DEBUG_CONNECTION
        );

        $this->smtp_conn = $this->getSMTPConnection($host, $port, $timeout, $options);

        if ($this->smtp_conn === false) {
            // La información del error ya fue establecida dentro de `getSMTPConnection()`
            return false;
        }

        $this->edebug('Connection: opened', self::DEBUG_CONNECTION);

        // Obtiene el mensaje inicial del servidor
        $this->last_reply = $this->get_lines();
        $this->edebug('SERVER -> CLIENT: ' . $this->last_reply, self::DEBUG_SERVER);
        $responseCode = (int)substr($this->last_reply, 0, 3);
        if ($responseCode === 220) {
            return true;
        }
        // Cualquier respuesta diferente de 220 indica que ocurrió un problema
        // RFC 5321 indica que el servidor esperará un comando QUIT después de un error 554
        // Referencia técnica de la biblioteca PHPMailer.
        if ($responseCode === 554) {
            $this->quit();
        }
        // Esto gestiona respuestas 421 que pueden no esperar un QUIT, por ejemplo, si el servidor se está apagando
        $this->edebug('Connection: closing due to error', self::DEBUG_CONNECTION);
        $this->close();
        return false;
    }

    /*
     * Crea una conexión con el servidor SMTP.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @parámetro string $host    IP o nombre del servidor SMTP
     * @parámetro int    $port    Número de puerto al que se conectará
     * @parámetro int    $timeout Tiempo máximo de espera para abrir la conexión
     * @parámetro array  $options Arreglo de opciones para stream_context_create()
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve false|resource
     */
    protected function getSMTPConnection($host, $port = null, $timeout = 30, $options = [])
    {
        static $streamok;
        // Está disponible de forma predeterminada desde 5.0.0, pero algunos proveedores lo desactivan
        // Comprueba esto una vez y guarda el resultado para reutilizarlo
        if (null === $streamok) {
            $streamok = function_exists('stream_socket_client');
        }

        $errno = 0;
        $errstr = '';
        if ($streamok) {
            $socket_context = stream_context_create($options);
            set_error_handler(function () {
                call_user_func_array([$this, 'errorHandler'], func_get_args());
            });
            $connection = stream_socket_client(
                $host . ':' . $port,
                $errno,
                $errstr,
                $timeout,
                STREAM_CLIENT_CONNECT,
                $socket_context
            );
        } else {
            // Usa fsockopen como alternativa cuando sea necesario; funciona en más casos, pero tiene menos funciones
            $this->edebug(
                'Connection: stream_socket_client not available, falling back to fsockopen',
                self::DEBUG_CONNECTION
            );
            set_error_handler(function () {
                call_user_func_array([$this, 'errorHandler'], func_get_args());
            });
            $connection = fsockopen(
                $host,
                $port,
                $errno,
                $errstr,
                $timeout
            );
        }
        restore_error_handler();

        // Comprueba que la conexión se haya realizado correctamente
        if (!is_resource($connection)) {
            $this->setError(
                'Failed to connect to server',
                '',
                (string) $errno,
                $errstr
            );
            $this->edebug(
                'SMTP ERROR: ' . $this->error['error']
                . ": $errstr ($errno)",
                self::DEBUG_CLIENT
            );

            return false;
        }

        // El servidor SMTP puede tardar más en responder, por eso se da más tiempo a la primera lectura
        // Windows no admite esta función de tiempo de espera
        if (strpos(PHP_OS, 'WIN') !== 0) {
            $max = (int)ini_get('max_execution_time');
            // No hace cambios si el tiempo es ilimitado o set_time_limit está desactivado
            if (0 !== $max && $timeout > $max && strpos(ini_get('disable_functions'), 'set_time_limit') === false) {
                @set_time_limit($timeout);
            }
            stream_set_timeout($connection, $timeout, 0);
        }

        return $connection;
    }

    /*
     * Inicia una sesión TLS (cifrada).
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve bool
     */
    public function startTLS()
    {
        if (!$this->sendCommand('STARTTLS', 'STARTTLS', 220)) {
            return false;
        }

        // Permite las mejores versiones TLS disponibles
        $crypto_method = STREAM_CRYPTO_METHOD_TLS_CLIENT;

        // PHP 5.6.7 dejó fuera TLS 1.1 y 1.2 de STREAM_CRYPTO_METHOD_TLS_CLIENT
        // por eso se vuelven a agregar manualmente cuando es posible
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
        }

        // Inicia la conexión cifrada
            set_error_handler(function () {
                call_user_func_array([$this, 'errorHandler'], func_get_args());
            });
        $crypto_ok = stream_socket_enable_crypto(
            $this->smtp_conn,
            true,
            $crypto_method
        );
        restore_error_handler();

        return (bool) $crypto_ok;
    }

    /*
     * Realiza la autenticación SMTP.
     * Debe ejecutarse después de hello().
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @ver    hello()
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @parámetro string $username Nombre de usuario
     * @parámetro string $password Contraseña
     * @parámetro string $authtype Tipo de autenticación (CRAM-MD5, PLAIN, LOGIN, XOAUTH2)
     * @parámetro OAuthTokenProvider $OAuth Instancia opcional de OAuthTokenProvider para autenticación XOAUTH2
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve bool Verdadero si la autenticación fue correcta
     */
    public function authenticate(
        $username,
        $password,
        $authtype = null,
        $OAuth = null
    ) {
        if (!$this->server_caps) {
            $this->setError('Authentication is not allowed before HELO/EHLO');

            return false;
        }

        if (array_key_exists('EHLO', $this->server_caps)) {
            // Hay extensiones SMTP disponibles; busca un método de autenticación compatible
            if (!array_key_exists('AUTH', $this->server_caps)) {
                $this->setError('Authentication is not allowed at this stage');
                // 'en esta etapa' significa que la autenticación puede permitirse después de cambiar de etapa
                // por ejemplo, después de STARTTLS

                return false;
            }

            $this->edebug('Auth method requested: ' . ($authtype ?: 'UNSPECIFIED'), self::DEBUG_LOWLEVEL);
            $this->edebug(
                'Auth methods available on the server: ' . implode(',', $this->server_caps['AUTH']),
                self::DEBUG_LOWLEVEL
            );

            // Si se solicitó un tipo de autenticación específico, comprueba que el servidor lo admita antes de probar otros
            if (null !== $authtype && !in_array($authtype, $this->server_caps['AUTH'], true)) {
                $this->edebug('Requested auth method not available: ' . $authtype, self::DEBUG_LOWLEVEL);
                $authtype = null;
            }

            if (empty($authtype)) {
                // Si no se indicó un método de autenticación, intenta usarlos en este orden
                // Prueba CRAM-MD5 primero porque es más seguro que los demás
                foreach (['CRAM-MD5', 'LOGIN', 'PLAIN', 'XOAUTH2'] as $method) {
                    if (in_array($method, $this->server_caps['AUTH'], true)) {
                        $authtype = $method;
                        break;
                    }
                }
                if (empty($authtype)) {
                    $this->setError('No supported authentication methods found');

                    return false;
                }
                $this->edebug('Auth method selected: ' . $authtype, self::DEBUG_LOWLEVEL);
            }

            if (!in_array($authtype, $this->server_caps['AUTH'], true)) {
                $this->setError("The requested authentication method \"$authtype\" is not supported by the server");

                return false;
            }
        } elseif (empty($authtype)) {
            $authtype = 'LOGIN';
        }
        switch ($authtype) {
            case 'PLAIN':
                // Inicia la autenticación
                if (!$this->sendCommand('AUTH', 'AUTH PLAIN', 334)) {
                    return false;
                }
                // Envía el usuario y la contraseña codificados
                if (
                    // Comentario: esta parte prepara o procesa la conexión SMTP.
                    // Comentario: esta parte prepara o procesa la conexión SMTP.
                    !$this->sendCommand(
                        'User & Password',
                        base64_encode("\0" . $username . "\0" . $password),
                        235
                    )
                ) {
                    return false;
                }
                break;
            case 'LOGIN':
                // Inicia la autenticación
                if (!$this->sendCommand('AUTH', 'AUTH LOGIN', 334)) {
                    return false;
                }
                if (!$this->sendCommand('Username', base64_encode($username), 334)) {
                    return false;
                }
                if (!$this->sendCommand('Password', base64_encode($password), 235)) {
                    return false;
                }
                break;
            case 'CRAM-MD5':
                // Inicia la autenticación
                if (!$this->sendCommand('AUTH CRAM-MD5', 'AUTH CRAM-MD5', 334)) {
                    return false;
                }
                // Obtiene el desafío del servidor
                $challenge = base64_decode(substr($this->last_reply, 4));

                // Prepara la respuesta
                $response = $username . ' ' . $this->hmac($challenge, $password);

                // Envía las credenciales codificadas
                return $this->sendCommand('Username', base64_encode($response), 235);
            case 'XOAUTH2':
                // La instancia OAuth debe estar preparada antes de solicitar la autenticación.
                if (null === $OAuth) {
                    return false;
                }
                $oauth = $OAuth->getOauth64();

                // Inicia la autenticación
                if (!$this->sendCommand('AUTH', 'AUTH XOAUTH2 ' . $oauth, 235)) {
                    return false;
                }
                break;
            default:
                $this->setError("Authentication method \"$authtype\" is not supported");

                return false;
        }

        return true;
    }

    /*
     * Calcula un resumen HMAC con MD5.
     * Funciona como hash_hmac('md5', $data, $key)
     * cuando esa función no está disponible.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @parámetro string $data Datos que se procesarán
     * @parámetro string $key  Clave usada para procesar los datos
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve string
     */
    protected function hmac($data, $key)
    {
        if (function_exists('hash_hmac')) {
            return hash_hmac('md5', $data, $key);
        }

        // Comentario: esta parte prepara o procesa la conexión SMTP.
        // Referencia técnica de la biblioteca PHPMailer.

        // Comentario: esta parte prepara o procesa la conexión SMTP.
        // Comentario: esta parte prepara o procesa la conexión SMTP.
        // Comentario: esta parte prepara o procesa la conexión SMTP.
        // Comentario: esta parte prepara o procesa la conexión SMTP.

        $bytelen = 64; //byte length for md5
        if (strlen($key) > $bytelen) {
            $key = pack('H*', md5($key));
        }
        $key = str_pad($key, $bytelen, chr(0x00));
        $ipad = str_pad('', $bytelen, chr(0x36));
        $opad = str_pad('', $bytelen, chr(0x5c));
        $k_ipad = $key ^ $ipad;
        $k_opad = $key ^ $opad;

        return md5($k_opad . pack('H*', md5($k_ipad . $data)));
    }

    /*
     * Comprueba el estado de la conexión.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve bool Verdadero si existe conexión
     */
    public function connected()
    {
        if (is_resource($this->smtp_conn)) {
            $sock_status = stream_get_meta_data($this->smtp_conn);
            if ($sock_status['eof']) {
                // La conexión existe, pero no está activa
                $this->edebug(
                    'SMTP NOTICE: EOF caught while checking if connected',
                    self::DEBUG_CLIENT
                );
                $this->close();

                return false;
            }

            return true; //everything looks good
        }

        return false;
    }

    /*
     * Cierra la conexión y limpia el estado de la clase.
     * No uses esta función sin intentar primero enviar QUIT.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @ver quit()
     */
    public function close()
    {
        $this->server_caps = null;
        $this->helo_rply = null;
        if (is_resource($this->smtp_conn)) {
            // Cierra la conexión y limpia los datos
            fclose($this->smtp_conn);
            $this->smtp_conn = null; //Makes for cleaner serialization
            $this->edebug('Connection: closed', self::DEBUG_CONNECTION);
        }
    }

    /*
     * Envía un comando SMTP DATA.
     * Envía el comando DATA y manda msg_data al servidor,
     * finalizando la operación de correo. $msg_data contiene el mensaje
     * que se enviará con sus encabezados. Cada encabezado debe estar
     * en una sola línea seguida de <CRLF>; los encabezados del mensaje
     * y el contenido del mensaje se separan con otro <CRLF>.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @parámetro string $msg_data Datos del mensaje que se enviarán
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve bool
     */
    public function data($msg_data)
    {
        // Usará el tiempo límite normal
        if (!$this->sendCommand('DATA', 'DATA', 354)) {
            return false;
        }

        /* * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
         * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
         * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
         * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
         * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
         * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
         */

        // Normaliza los saltos de línea antes de separar el contenido
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $msg_data));

        /* * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
         * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
         * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
         */

        $field = substr($lines[0], 0, strpos($lines[0], ':'));
        $in_headers = false;
        if (!empty($field) && strpos($field, ' ') === false) {
            $in_headers = true;
        }

        foreach ($lines as $line) {
            $lines_out = [];
            if ($in_headers && $line === '') {
                $in_headers = false;
            }
            // Divide esta línea en varias líneas pequeñas si es demasiado larga
            // Comentario: esta parte prepara o procesa la conexión SMTP.
            while (isset($line[self::MAX_LINE_LENGTH])) {
                // Busca hacia atrás un espacio dentro de los últimos MAX_LINE_LENGTH caracteres para dividir la línea
                // para evitar cortar una palabra por la mitad
                $pos = strrpos(substr($line, 0, self::MAX_LINE_LENGTH), ' ');
                // Está hecho para aceptar tanto false como 0
                if (!$pos) {
                    // No se encontró un punto adecuado; hace un corte directo
                    $pos = self::MAX_LINE_LENGTH - 1;
                    $lines_out[] = substr($line, 0, $pos);
                    $line = substr($line, $pos);
                } else {
                    // Divide la línea en el punto encontrado
                    $lines_out[] = substr($line, 0, $pos);
                    // Avanza según la parte que ya fue procesada
                    $line = substr($line, $pos + 1);
                }
                // Si se procesan encabezados, agrega un carácter LWSP al inicio de la nueva línea según RFC822 sección 3.1.1
                if ($in_headers) {
                    $line = "\t" . $line;
                }
            }
            $lines_out[] = $line;

            // Envía las líneas al servidor
            foreach ($lines_out as $line_out) {
                // Comentario: esta parte prepara o procesa la conexión SMTP.
                // Referencia técnica de la biblioteca PHPMailer.
                if (!empty($line_out) && $line_out[0] === '.') {
                    $line_out = '.' . $line_out;
                }
                $this->client_send($line_out . static::LE, 'DATA');
            }
        }

        // Los datos del mensaje fueron enviados; completa el comando
        // Aumenta el tiempo límite para terminar el comando DATA
        $savetimelimit = $this->Timelimit;
        $this->Timelimit *= 2;
        $result = $this->sendCommand('DATA END', '.', 250);
        $this->recordLastTransactionID();
        // Restaura el tiempo límite original
        $this->Timelimit = $savetimelimit;

        return $result;
    }

    /*
     * Envía un comando SMTP HELO o EHLO.
     * Se usa para identificar el servidor que envía el mensaje.
     * Asegura que cliente y servidor conozcan el estado de la conexión.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @parámetro string $host Nombre del servidor o IP a la que se conectará
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve bool
     */
    public function hello($host = '')
    {
        // Primero intenta el saludo extendido (RFC 2821)
        if ($this->sendHello('EHLO', $host)) {
            return true;
        }

        // Algunos servidores pueden cerrar el servicio SMTP en este punto (RFC 5321)
        if (substr($this->helo_rply, 0, 3) == '421') {
            return false;
        }

        return $this->sendHello('HELO', $host);
    }

    /*
     * Envía un comando SMTP HELO o EHLO.
     * Implementación interna usada por hello().
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @parámetro string $hello Texto usado por HELO
     * @parámetro string $host  Nombre del servidor que se informa al servidor remoto
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve bool
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @ver hello()
     */
    protected function sendHello($hello, $host)
    {
        $noerror = $this->sendCommand($hello, $hello . ' ' . $host, 250);
        $this->helo_rply = $this->last_reply;
        if ($noerror) {
            $this->parseHelloFields($hello);
        } else {
            $this->server_caps = null;
        }

        return $noerror;
    }

    /*
     * Analiza la respuesta de HELO/EHLO para conocer las extensiones del servidor.
     * Con HELO, el único dato que se puede conocer es el nombre del servidor.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @parámetro string $type `HELO` o `EHLO`
     */
    protected function parseHelloFields($type)
    {
        $this->server_caps = [];
        $lines = explode("\n", $this->helo_rply);

        foreach ($lines as $n => $s) {
            // Los primeros 4 caracteres contienen el código de respuesta seguido de - o espacio
            $s = trim(substr($s, 4));
            if (empty($s)) {
                continue;
            }
            $fields = explode(' ', $s);
            if (!empty($fields)) {
                if (!$n) {
                    $name = $type;
                    $fields = $fields[0];
                } else {
                    $name = array_shift($fields);
                    switch ($name) {
                        case 'SIZE':
                            $fields = ($fields ? $fields[0] : 0);
                            break;
                        case 'AUTH':
                            if (!is_array($fields)) {
                                $fields = [];
                            }
                            break;
                        default:
                            $fields = true;
                    }
                }
                $this->server_caps[$name] = $fields;
            }
        }
    }

    /*
     * Envía un comando SMTP MAIL.
     * Inicia una operación de correo desde la dirección indicada en
     * $from. Devuelve true si funciona correctamente o false si ocurre un problema. Si devuelve true
     * la operación de correo comienza y luego se pueden indicar uno o más destinatarios
     * antes de enviar los datos del mensaje.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @parámetro string $from Dirección de origen de este mensaje
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve bool
     */
    public function mail($from)
    {
        $useVerp = ($this->do_verp ? ' XVERP' : '');

        return $this->sendCommand(
            'MAIL FROM',
            'MAIL FROM:<' . $from . '>' . $useVerp,
            250
        );
    }

    /*
     * Envía un comando SMTP QUIT.
     * Cierra la conexión si no hay error o si $close_on_error es true.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @parámetro bool $close_on_error Indica si la conexión debe cerrarse cuando ocurre un error.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve bool
     */
    public function quit($close_on_error = true)
    {
        $noerror = $this->sendCommand('QUIT', 'QUIT', 221);
        $err = $this->error; //Save any error
        if ($noerror || $close_on_error) {
            $this->close();
            $this->error = $err; //Restore any error from the quit command
        }

        return $noerror;
    }

    /*
     * Envía un comando SMTP RCPT.
     * Establece el destinatario usando $toaddr.
     * Devuelve true si el destinatario fue aceptado y false si fue rechazado.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @parámetro string $address Dirección a la que se enviará el mensaje
     * @parámetro string $dsn     Lista de avisos DSN separados por comas: NEVER, SUCCESS, FAILURE
     *                        o DELAY. Si se indica NEVER, se ignoran los demás avisos.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve bool
     */
    public function recipient($address, $dsn = '')
    {
        if (empty($dsn)) {
            $rcpt = 'RCPT TO:<' . $address . '>';
        } else {
            $dsn = strtoupper($dsn);
            $notify = [];

            if (strpos($dsn, 'NEVER') !== false) {
                $notify[] = 'NEVER';
            } else {
                foreach (['SUCCESS', 'FAILURE', 'DELAY'] as $value) {
                    if (strpos($dsn, $value) !== false) {
                        $notify[] = $value;
                    }
                }
            }

            $rcpt = 'RCPT TO:<' . $address . '> NOTIFY=' . implode(',', $notify);
        }

        return $this->sendCommand(
            'RCPT TO',
            $rcpt,
            [250, 251]
        );
    }

    /*
     * Envía XCLIENT al servidor SMTP y comprueba el código de respuesta.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve bool Verdadero si la operación fue correcta
     */
    public function xclient(array $vars)
    {
        $xclient_options = "";
        foreach ($vars as $key => $value) {
            if (in_array($key, SMTP::$xclient_allowed_attributes)) {
                $xclient_options .= " {$key}={$value}";
            }
        }
        if (!$xclient_options) {
            return true;
        }
        return $this->sendCommand('XCLIENT', 'XCLIENT' . $xclient_options, 250);
    }

    /*
     * Envía un comando SMTP RSET.
     * Cancela cualquier operación que esté en curso.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve bool Verdadero si la operación fue correcta
     */
    public function reset()
    {
        return $this->sendCommand('RSET', 'RSET', 250);
    }

    /*
     * Envía un comando al servidor SMTP y comprueba su código de respuesta.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @parámetro string    $command       Nombre del comando; no se envía al servidor
     * @parámetro string    $commandstring Comando real que se enviará
     * @parámetro int|array $expect        Uno o más códigos numéricos esperados para indicar éxito
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve bool Verdadero si la operación fue correcta
     */
    protected function sendCommand($command, $commandstring, $expect)
    {
        if (!$this->connected()) {
            $this->setError("Called $command without being connected");

            return false;
        }
        // Rechaza saltos de línea dentro de los comandos
        if ((strpos($commandstring, "\n") !== false) || (strpos($commandstring, "\r") !== false)) {
            $this->setError("Command '$command' contained line breaks");

            return false;
        }
        $this->client_send($commandstring . static::LE, $command);

        $this->last_reply = $this->get_lines();
        // Obtiene el código SMTP y una posible explicación del error
        $matches = [];
        if (preg_match('/^([\d]{3})[ -](?:([\d]\\.[\d]\\.[\d]{1,2}) )?/', $this->last_reply, $matches)) {
            $code = (int) $matches[1];
            $code_ex = (count($matches) > 2 ? $matches[2] : null);
            // Quita el código de error de cada línea de respuesta
            $detail = preg_replace(
                "/{$code}[ -]" .
                ($code_ex ? str_replace('.', '\\.', $code_ex) . ' ' : '') . '/m',
                '',
                $this->last_reply
            );
        } else {
            // Usa un análisis simple si la expresión regular falla
            $code = (int) substr($this->last_reply, 0, 3);
            $code_ex = null;
            $detail = substr($this->last_reply, 4);
        }

        $this->edebug('SERVER -> CLIENT: ' . $this->last_reply, self::DEBUG_SERVER);

        if (!in_array($code, (array) $expect, true)) {
            $this->setError(
                "$command command failed",
                $detail,
                $code,
                $code_ex
            );
            $this->edebug(
                'SMTP ERROR: ' . $this->error['error'] . ': ' . $this->last_reply,
                self::DEBUG_CLIENT
            );

            return false;
        }

        // No limpia los errores cuando se mantiene la conexión activa
        if ($command !== 'RSET') {
            $this->setError('');
        }

        return true;
    }

    /*
     * Envía un comando SMTP SAML.
     * Inicia una operación de correo desde la dirección indicada en $from.
     * Devuelve true si funciona correctamente y false si ocurre un problema. Si devuelve true
     * la operación de correo comienza y luego se pueden indicar uno o más destinatarios
     * antes de enviar los datos del mensaje. This command
     * envía el mensaje al terminal del usuario si está conectado
     * y también le envía un correo.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @parámetro string $from Dirección desde la que se envía el mensaje
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve bool
     */
    public function sendAndMail($from)
    {
        return $this->sendCommand('SAML', "SAML FROM:$from", 250);
    }

    /*
     * Envía un comando SMTP VRFY.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @parámetro string $name Nombre que se desea comprobar
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve bool
     */
    public function verify($name)
    {
        return $this->sendCommand('VRFY', "VRFY $name", [250, 251]);
    }

    /*
     * Envía un comando SMTP NOOP.
     * Se usa para mantener activa la conexión; no realiza otra acción.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve bool
     */
    public function noop()
    {
        return $this->sendCommand('NOOP', 'NOOP', 250);
    }

    /*
     * Envía un comando SMTP TURN.
     * Es un comando SMTP opcional que esta clase no admite.
     * Este método existe para completar la definición de RFC821 en esta clase
     * y _podría_ implementarse en el futuro.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve bool
     */
    public function turn()
    {
        $this->setError('The SMTP TURN command is not implemented');
        $this->edebug('SMTP NOTICE: ' . $this->error['error'], self::DEBUG_CLIENT);

        return false;
    }

    /*
     * Envía datos sin procesar al servidor.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @parámetro string $data    Datos que se enviarán
     * @parámetro string $command Opcionalmente, el comando al que pertenece; solo se usa para controlar la depuración
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve int|bool Cantidad de bytes enviados al servidor o false si ocurre un error
     */
    public function client_send($data, $command = '')
    {
        // Comentario: esta parte prepara o procesa la conexión SMTP.
        // Comentario: esta parte prepara o procesa la conexión SMTP.
        if (
            self::DEBUG_LOWLEVEL > $this->do_debug &&
            in_array($command, ['User & Password', 'Username', 'Password'], true)
        ) {
            $this->edebug('CLIENT -> SERVER: [credentials hidden]', self::DEBUG_CLIENT);
        } else {
            $this->edebug('CLIENT -> SERVER: ' . $data, self::DEBUG_CLIENT);
        }
        set_error_handler(function () {
            call_user_func_array([$this, 'errorHandler'], func_get_args());
        });
        $result = fwrite($this->smtp_conn, $data);
        restore_error_handler();

        return $result;
    }

    /*
     * Obtiene el último error.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve array
     */
    public function getError()
    {
        return $this->error;
    }

    /*
     * Obtiene las extensiones SMTP disponibles en el servidor.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve array|null
     */
    public function getServerExtList()
    {
        return $this->server_caps;
    }

    /*
     * Obtiene información del servidor SMTP a partir de su respuesta HELO/EHLO.
     * El método funciona de tres formas según el valor recibido y el estado actual:
     *   1. No se ha enviado HELO/EHLO: devuelve null y guarda el error en $this->error.
     *   2. Se ha enviado HELO:
     *     $name == 'HELO': devuelve el nombre del servidor
     *     $name == 'EHLO': devuelve false
     *     $name == cualquier otro texto: devuelve null y guarda el error en $this->error
     *   3. Se ha enviado EHLO:
     *     $name == 'HELO'|'EHLO': devuelve el nombre del servidor
     *     $name == cualquier otro texto: si existe la extensión $name, devuelve true
     *       o sus opciones (por ejemplo, métodos AUTH admitidos). De lo contrario, devuelve false.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @parámetro string $name Nombre de la extensión SMTP o 'HELO'|'EHLO'
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve string|bool|null
     */
    public function getServerExt($name)
    {
        if (!$this->server_caps) {
            $this->setError('No HELO/EHLO was sent');

            return null;
        }

        if (!array_key_exists($name, $this->server_caps)) {
            if ('HELO' === $name) {
                return $this->server_caps['EHLO'];
            }
            if ('EHLO' === $name || array_key_exists('EHLO', $this->server_caps)) {
                return false;
            }
            $this->setError('HELO handshake was used; No information about server extensions available');

            return null;
        }

        return $this->server_caps[$name];
    }

    /*
     * Obtiene la última respuesta del servidor.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve string
     */
    public function getLastReply()
    {
        return $this->last_reply;
    }

    /*
     * Lee la respuesta del servidor SMTP.
     * Termina al llegar al final de los datos o al producirse un tiempo de espera.
     * Con SMTP se puede saber si quedan más líneas por leer cuando el
     * cuarto carácter es '-'. Si es un espacio, no
     * es necesario leer nada más.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve string
     */
    protected function get_lines()
    {
        // Si la conexión no es válida, termina inmediatamente
        if (!is_resource($this->smtp_conn)) {
            return '';
        }
        $data = '';
        $endtime = 0;
        stream_set_timeout($this->smtp_conn, $this->Timeout);
        if ($this->Timelimit > 0) {
            $endtime = time() + $this->Timelimit;
        }
        $selR = [$this->smtp_conn];
        $selW = null;
        while (is_resource($this->smtp_conn) && !feof($this->smtp_conn)) {
            // Debe pasar las variables aquí porque los parámetros se usan por referencia
            // Comentario: esta parte prepara o procesa la conexión SMTP.
            set_error_handler(function () {
                call_user_func_array([$this, 'errorHandler'], func_get_args());
            });
            $n = stream_select($selR, $selW, $selW, $this->Timelimit);
            restore_error_handler();

            if ($n === false) {
                $message = $this->getError()['detail'];

                $this->edebug(
                    'SMTP -> get_lines(): select failed (' . $message . ')',
                    self::DEBUG_LOWLEVEL
                );

                // stream_select devuelve false cuando la llamada `select` es interrumpida
                // por una señal; intenta nuevamente
                if (stripos($message, 'interrupted system call') !== false) {
                    $this->edebug(
                        'SMTP -> get_lines(): retrying stream_select',
                        self::DEBUG_LOWLEVEL
                    );
                    $this->setError('');
                    continue;
                }

                break;
            }

            if (!$n) {
                $this->edebug(
                    'SMTP -> get_lines(): select timed-out in (' . $this->Timelimit . ' sec)',
                    self::DEBUG_LOWLEVEL
                );
                break;
            }

            // Se evita mostrar ruido innecesario; los errores se gestionan después
            $str = @fgets($this->smtp_conn, self::MAX_REPLY_LENGTH);
            $this->edebug('SMTP INBOUND: "' . trim($str) . '"', self::DEBUG_LOWLEVEL);
            $data .= $str;
            // Si la respuesta tiene solo 3 caracteres (no es válida, pero RFC5321 S4.2 indica que debe gestionarse),
            // o el cuarto carácter es un espacio o un salto de línea, termina la lectura y sale del ciclo.
            // Acceder a una posición del texto es una pequeña mejora de rendimiento frente a strlen
            if (!isset($str[3]) || $str[3] === ' ' || $str[3] === "\r" || $str[3] === "\n") {
                break;
            }
            // ¿Se agotó el tiempo? Registra el evento y termina
            $info = stream_get_meta_data($this->smtp_conn);
            if ($info['timed_out']) {
                $this->edebug(
                    'SMTP -> get_lines(): stream timed-out (' . $this->Timeout . ' sec)',
                    self::DEBUG_LOWLEVEL
                );
                break;
            }
            // Ahora comprueba si las lecturas tardaron demasiado
            if ($endtime && time() > $endtime) {
                $this->edebug(
                    'SMTP -> get_lines(): timelimit reached (' .
                    $this->Timelimit . ' sec)',
                    self::DEBUG_LOWLEVEL
                );
                break;
            }
        }

        return $data;
    }

    /*
     * Activa o desactiva la generación de direcciones VERP.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @parámetro bool $enabled
     */
    public function setVerp($enabled = false)
    {
        $this->do_verp = $enabled;
    }

    /*
     * Obtiene el estado de generación de direcciones VERP.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve bool
     */
    public function getVerp()
    {
        return $this->do_verp;
    }

    /*
     * Establece los mensajes y códigos de error.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @parámetro string $message      Mensaje del error
     * @parámetro string $detail       Información adicional sobre el error
     * @parámetro string $smtp_code    Código SMTP asociado al error
     * @parámetro string $smtp_code_ex Código SMTP ampliado
     */
    protected function setError($message, $detail = '', $smtp_code = '', $smtp_code_ex = '')
    {
        $this->error = [
            'error' => $message,
            'detail' => $detail,
            'smtp_code' => $smtp_code,
            'smtp_code_ex' => $smtp_code_ex,
        ];
    }

    /*
     * Establece la forma de mostrar la depuración.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @parámetro string|callable $method The name of the mechanism to use for debugging output, or a callable to handle it
     */
    public function setDebugOutput($method = 'echo')
    {
        $this->Debugoutput = $method;
    }

    /*
     * Obtiene la forma de mostrar la depuración.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve string
     */
    public function getDebugOutput()
    {
        return $this->Debugoutput;
    }

    /*
     * Establece el nivel de depuración.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @parámetro int $level
     */
    public function setDebugLevel($level = 0)
    {
        $this->do_debug = $level;
    }

    /*
     * Obtiene el nivel de depuración.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve int
     */
    public function getDebugLevel()
    {
        return $this->do_debug;
    }

    /*
     * Establece el tiempo máximo de espera SMTP.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @parámetro int $timeout Duración del tiempo de espera en segundos
     */
    public function setTimeout($timeout = 0)
    {
        $this->Timeout = $timeout;
    }

    /*
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve int
     */
    public function getTimeout()
    {
        return $this->Timeout;
    }

    /*
     * Registra un número y un mensaje de error.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @parámetro int    $errno   Número de error devuelto por PHP
     * @parámetro string $errmsg  Mensaje de error devuelto por PHP
     * @parámetro string $errfile Archivo donde ocurrió el error
     * @parámetro int    $errline Número de línea donde ocurrió el error
     */
    protected function errorHandler($errno, $errmsg, $errfile = '', $errline = 0)
    {
        $notice = 'Connection failed.';
        $this->setError(
            $notice,
            $errmsg,
            (string) $errno
        );
        $this->edebug(
            "$notice Error #$errno: $errmsg [$errfile line $errline]",
            self::DEBUG_CONNECTION
        );
    }

    /*
     * Obtiene y devuelve el identificador de la última operación SMTP usando
     * una lista de patrones definidos en SMTP::$smtp_transaction_id_patterns.
     * Depende de que el servidor entregue el identificador como respuesta a DATA.
     * Si todavía no se recibió una respuesta, devuelve null.
     * Si ningún patrón coincide, devuelve false.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve bool|string|null
     */
    protected function recordLastTransactionID()
    {
        $reply = $this->getLastReply();

        if (empty($reply)) {
            $this->last_smtp_transaction_id = null;
        } else {
            $this->last_smtp_transaction_id = false;
            foreach ($this->smtp_transaction_id_patterns as $smtp_transaction_id_pattern) {
                $matches = [];
                if (preg_match($smtp_transaction_id_pattern, $reply, $matches)) {
                    $this->last_smtp_transaction_id = trim($matches[1]);
                    break;
                }
            }
        }

        return $this->last_smtp_transaction_id;
    }

    /*
     * Obtiene el identificador de cola u operación de la última operación SMTP
     * Si todavía no se recibió una respuesta, devuelve null.
     * Si ningún patrón coincide, devuelve false.
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @devuelve bool|string|null
     * Explicación: esta parte del código cumple una tarea interna de la conexión SMTP.
     * @ver recordLastTransactionID()
     */
    public function getLastTransactionID()
    {
        return $this->last_smtp_transaction_id;
    }
}

?>

<!-- -------------------------------------------------------------------------------------------------------------
   -------------------------------------- FIN Jerez SMTP.php --------------------------------------
   ------------------------------------------------------------------------------------------------------------- -->


<!--
    TÍTULO: CLASE SMTP PARA ENVÍO DE CORREO
    Sitio Web Creado por Jerez.
    Dirección: Miraflorez #1280
    Quintero - Chile
    jaime.jerez1978@gmail.com
    https://www.
    Creado, Programado y Diseñado por Jerez.
    JJA
-->