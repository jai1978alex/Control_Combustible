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
    // Acepta RUT con puntos (12.345.678-5) o sin puntos (12345678-5),
    // siempre con guion y dígito verificador.
    $rut = strtoupper(trim((string)$rut));
    return (bool) preg_match('/^(?:[0-9]{1,2}\.[0-9]{3}\.[0-9]{3}|[0-9]{7,8})-[0-9K]$/', $rut);
}


// TÍTULO: FUNCIÓN PARA REVISAR QUE EL RUT SEA REAL

// Recibe un RUT y responde "sí" si su dígito verificador (el último) calza con el resto de los números, o "no" si no
function validarRut($rut) {
    // Validación robusta: elimina puntos, guion y espacios; conserva únicamente
    // el cuerpo numérico y el dígito verificador (0-9 o K).
    $rut = strtoupper(trim((string)$rut));
    $rut = preg_replace('/[^0-9Kk]/', '', $rut);

    // Un RUT debe tener cuerpo de 7 u 8 dígitos y un DV.
    if (!preg_match('/^[0-9]{7,8}[0-9K]$/', $rut)) {
        return false;
    }

    $dv = substr($rut, -1);
    $cuerpo = substr($rut, 0, -1);

    // Calcular DV recorriendo el cuerpo de derecha a izquierda con factores 2..7.
    $suma = 0;
    $factor = 2;
    for ($i = strlen($cuerpo) - 1; $i >= 0; $i--) {
        $suma += ((int)$cuerpo[$i]) * $factor;
        $factor = ($factor === 7) ? 2 : $factor + 1;
    }

    $resultado = 11 - ($suma % 11);
    if ($resultado === 11) {
        $dvCalculado = '0';
    } elseif ($resultado === 10) {
        $dvCalculado = 'K';
    } else {
        $dvCalculado = (string)$resultado;
    }

    return hash_equals($dvCalculado, $dv);
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



// TITULO NOMBRE REGISTRADO: CADA RUT TIENE UN NOMBRE

// Deja un nombre en forma comparable: sin tildes, en mayusculas y con un solo espacio entre palabras.
function nombreCanonico($texto) {
    $t = trim((string)$texto);
    if ($t === '') return '';
    $sinTildes = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $t);
    if ($sinTildes === false || $sinTildes === '') $sinTildes = $t;
    $sinTildes = preg_replace('/[^A-Za-z0-9 ]/', '', $sinTildes);
    return strtoupper(trim((string)preg_replace('/\s+/', ' ', (string)$sinTildes)));
}

// Indica si dos nombres corresponden a lo mismo (ignora tildes, mayusculas y espacios de sobra)
function nombresCoinciden($a, $b) {
    $a = nombreCanonico($a);
    $b = nombreCanonico($b);
    return $a !== '' && $a === $b;
}

// Devuelve el nombre registrado para un RUT en la tabla usuarios, o null si ese RUT no tiene cuenta.
// ['tiene_cuenta'=>bool, 'tiene_nombre'=>bool, 'nombres'=>..., 'apellido_paterno'=>..., 'apellido_materno'=>...]
function nombreRegistradoDeRut(mysqli $conn, string $rut): array {
    $vacio = ['tiene_cuenta' => false, 'tiene_nombre' => false, 'nombres' => '', 'apellido_paterno' => '', 'apellido_materno' => ''];
    $canon = rutCanonico($rut);
    if ($canon === '') return $vacio;
    // Si la base aun no tiene las columnas del nombre (falta la migracion), no hay nombre que comparar
    $hayColumnas = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'nombres'");
    if (!$hayColumnas || $hayColumnas->num_rows === 0) return $vacio;
    $st = $conn->prepare("SELECT nombres, apellido_paterno, apellido_materno FROM usuarios WHERE UPPER(REPLACE(REPLACE(rut,'.',''),'-','')) = ? LIMIT 1");
    $st->bind_param('s', $canon);
    $st->execute();
    $f = $st->get_result()->fetch_assoc();
    if (!$f) return $vacio;
    $n = trim((string)($f['nombres'] ?? ''));
    $p = trim((string)($f['apellido_paterno'] ?? ''));
    $m = trim((string)($f['apellido_materno'] ?? ''));
    return ['tiene_cuenta' => true, 'tiene_nombre' => ($n !== '' && $p !== ''), 'nombres' => $n, 'apellido_paterno' => $p, 'apellido_materno' => $m];
}

// Revisa que el nombre ingresado sea el registrado para ese RUT.
// Si el RUT no pertenece a ninguna cuenta, no hay nada con que comparar y se acepta.
// Si pertenece a una cuenta con nombre registrado, los nombres y apellidos deben coincidir.
// Devuelve ['ok'=>true] o ['ok'=>false,'codigo'=>'nombre'|'sin_nombre','mensaje'=>'...'].
function verificarNombreDeRut(mysqli $conn, string $rut, string $nombres, string $ap, string $am, bool $exigirNombreRegistrado = false): array {
    $reg = nombreRegistradoDeRut($conn, $rut);
    if (!$reg['tiene_cuenta']) {
        return ['ok' => true];
    }
    if (!$reg['tiene_nombre']) {
        if ($exigirNombreRegistrado) {
            return ['ok' => false, 'codigo' => 'sin_nombre', 'mensaje' => 'Tu cuenta no tiene nombre registrado. Pide al administrador que lo asigne.'];
        }
        return ['ok' => true];
    }
    if (!nombresCoinciden($reg['nombres'], $nombres)
        || !nombresCoinciden($reg['apellido_paterno'], $ap)
        || !nombresCoinciden($reg['apellido_materno'], $am)) {
        return ['ok' => false, 'codigo' => 'nombre', 'mensaje' => 'El nombre no coincide con el registrado para este RUT.'];
    }
    return ['ok' => true];
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