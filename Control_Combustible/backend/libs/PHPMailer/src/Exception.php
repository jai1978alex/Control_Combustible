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
   ------------------------------------- INICIO Jerez Exception.php ------------------------------------
   ------------------------------------------------------------------------------------------------------------- -->


<?php

/**
 * TÍTULO: CLASE DE ERRORES DE PHPMailer
 *
 * Este archivo se encarga de manejar los errores
 * que pueden ocurrir dentro de PHPMailer.
 *
 * Versión de PHP utilizada originalmente: 5.5.
 *
 * @see       https://github.com/PHPMailer/PHPMailer/ The PHPMailer GitHub project
 *
 * @author    Marcus Bointon (Synchro/coolbru) <phpmailer@synchromedia.co.uk>
 * @author    Jim Jagielski (jimjag) <jimjag@gmail.com>
 * @author    Andy Prevost (codeworxtech) <codeworxtech@users.sourceforge.net>
 * @author    Brent R. Matzelle (original founder)
 * @copyright 2012 - 2020 Marcus Bointon
 * @copyright 2010 - 2012 Jim Jagielski
 * @copyright 2004 - 2009 Andy Prevost
 * @license   https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU Lesser General Public License
 * @note      This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */


/*
 * TÍTULO: ESPACIO DE NOMBRES DE PHPMailer
 *
 * Indica que esta clase pertenece al grupo de clases
 * utilizadas por PHPMailer.
 */

namespace PHPMailer\PHPMailer;


/*
 * TÍTULO: CLASE PARA MANEJAR ERRORES
 *
 * Esta clase permite controlar y mostrar los errores
 * que se producen durante el uso de PHPMailer.
 *
 * La clase utiliza la clase de errores propia de PHP.
 */

class Exception extends \Exception
{


    /*
     * TÍTULO: FUNCIÓN PARA OBTENER EL MENSAJE DE ERROR
     *
     * Esta función toma el mensaje del error
     * y lo prepara para mostrarlo de forma segura.
     *
     * @return string
     */

    public function errorMessage()
    {


        /*
         * TÍTULO: MOSTRAR EL MENSAJE DE ERROR
         *
         * Se obtiene el mensaje del error y se convierte
         * cualquier carácter especial para evitar problemas
         * al mostrarlo en una página web.
         */

        return '<strong>' . htmlspecialchars($this->getMessage(), ENT_COMPAT | ENT_HTML401) . "</strong><br />\n";
    }
}


?>


<!-- -------------------------------------------------------------------------------------------------------------
   -------------------------------------- FIN Jerez Exception.php --------------------------------------
   ------------------------------------------------------------------------------------------------------------- -->


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

