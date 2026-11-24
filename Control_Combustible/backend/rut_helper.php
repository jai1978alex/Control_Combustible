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



// TITULO IDENTIDAD: COMPARAR EL RUT CON EL DE LA CUENTA

// Deja un RUT en forma canonica (solo numeros y digito verificador en mayuscula,
// sin puntos ni guion) para compararlo sin importar como fue escrito.
function rutCanonico($rut) {
    return strtoupper(preg_replace('/[^0-9kK]/', '', (string)$rut));
}

// Indica si dos RUT corresponden a la misma persona (ignora puntos, guion y mayusculas)
function rutsCoinciden($a, $b) {
    $a = rutCanonico($a);
    $b = rutCanonico($b);
    return $a !== '' && $b !== '' && hash_equals($a, $b);
}

// Revisa si el RUT ingresado puede usarse con la cuenta que tiene la sesion abierta.
// Reglas:
//  - Los administradores quedan exentos (pueden registrar por terceros y la accion queda en auditoria).
//  - Los operadores solo pueden usar el RUT que tienen asignado en usuarios.rut.
//  - Un operador sin RUT asignado no puede validar un QR ni registrar operaciones.
// Devuelve ['ok' => true] o ['ok' => false, 'codigo' => 'sin_rut'|'identidad', 'mensaje' => '...'].
function verificarRutDeCuenta(mysqli $conn, int $usuarioId, string $rutIngresado): array {
    $st = $conn->prepare('SELECT rol, rut FROM usuarios WHERE id = ? LIMIT 1');
    $st->bind_param('i', $usuarioId);
    $st->execute();
    $cuenta = $st->get_result()->fetch_assoc();

    if (!$cuenta) {
        return ['ok' => false, 'codigo' => 'identidad', 'mensaje' => 'No se pudo verificar tu identidad.'];
    }
    if ($cuenta['rol'] === 'admin') {
        return ['ok' => true];
    }
    if (rutCanonico((string)($cuenta['rut'] ?? '')) === '') {
        return ['ok' => false, 'codigo' => 'sin_rut', 'mensaje' => 'Tu cuenta no tiene un RUT asignado. Pide al administrador que lo asigne.'];
    }
    if (!rutsCoinciden((string)$cuenta['rut'], $rutIngresado)) {
        return ['ok' => false, 'codigo' => 'identidad', 'mensaje' => 'El RUT no corresponde a tu cuenta.'];
    }
    return ['ok' => true];
}

// Devuelve el RUT asignado a la cuenta (formato guardado) o null si no tiene
function rutAsignadoACuenta(mysqli $conn, int $usuarioId): ?string {
    $st = $conn->prepare('SELECT rut FROM usuarios WHERE id = ? LIMIT 1');
    $st->bind_param('i', $usuarioId);
    $st->execute();
    $fila = $st->get_result()->fetch_assoc();
    $rut = trim((string)($fila['rut'] ?? ''));
    return $rut !== '' ? $rut : null;
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