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
   ------------------------------------- INICIO Jerez rut_helper.php ----------------------------------------
   ------------------------------------------------------------------------------------------------------------- */


// TÍTULO: FUNCIÓN PARA REVISAR LA FORMA DEL RUT

// Recibe un RUT y responde "sí" si está escrito con la forma correcta (por ejemplo 12.345.678-5), o "no" si no
function validarFormatoRut($rut) {
    // Compara lo escrito con el modelo: 1 o 2 números, punto, 3 números, punto, 3 números, guion y un número o la letra K
    return (bool) preg_match('/^[0-9]{1,2}\.[0-9]{3}\.[0-9]{3}-[0-9kK]{1}$/', $rut);
}


// TÍTULO: FUNCIÓN PARA REVISAR QUE EL RUT SEA REAL

// Recibe un RUT y responde "sí" si su dígito verificador (el último) calza con el resto de los números, o "no" si no
function validarRut($rut) {

    // Quita puntos, guion y cualquier símbolo, dejando solo los números y la letra K
    $rut = preg_replace('/[^kK0-9]/', '', $rut);

    // Separa la parte principal del RUT (todos los números menos el último)
    $cuerpo = substr($rut, 0, -1);
    // Separa el último carácter, que es el dígito verificador, y lo pasa a mayúsculas
    $dv = strtoupper(substr($rut, -1));

    // Prepara un contador en cero para ir sumando
    $suma = 0;
    // Empieza a multiplicar desde el número 2
    $multiplo = 2;

    // Recorre los números de la parte principal, desde el último hacia el primero
    for ($i = strlen($cuerpo) - 1; $i >= 0; $i--) {

        // Multiplica cada número por el valor que toca y lo suma al total
        $suma += $cuerpo[$i] * $multiplo;

        // Pasa al siguiente valor de multiplicación
        $multiplo++;

        // Si el valor pasó de 7...
        if ($multiplo > 7) {
            // ...vuelve a empezar desde 2
            $multiplo = 2;
        }
        
    }

    // Calcula el resto de dividir la suma total por 11
    $resto = $suma % 11;
    // Calcula cuál debería ser el dígito verificador restando ese resto a 11
    $dvEsperado = 11 - $resto;

    // Si el resultado es 11, el dígito esperado es "0"; si es 10, es la letra "K"; en cualquier otro caso, es el mismo número convertido a texto
    if ($dvEsperado == 11) {
        // El dígito esperado es "0"
        $dvEsperado = '0';
    } elseif ($dvEsperado == 10) {
        // El dígito esperado es la letra "K"
        $dvEsperado = 'K';
    } else {
        // El dígito esperado es el mismo número, convertido a texto
        $dvEsperado = (string)$dvEsperado;
    }

    // Entrega "sí" si el dígito que trae el RUT es igual al esperado, o "no" si es distinto
    return $dvEsperado === $dv;
}



/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- FIN Jerez rut_helper.php ------------------------------------------
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