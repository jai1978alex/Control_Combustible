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
   ------------------------------------- INICIO Jerez horometro_helper.php ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */





declare(strict_types=1);

/**
 * Comprueba que el horómetro nuevo no sea inferior a la última lectura
 * registrada para la misma maquinaria, considerando ambos formularios.
 * Devuelve la última lectura encontrada o null si no existe historial.
 */
function obtenerUltimoHorometro(mysqli $conn, string $codigo, string $tablaActual = '', int $idExcluir = 0): ?float
{
    $codigo = trim($codigo);
    if ($codigo === '') {
        return null;
    }

    // Los nombres de tabla son fijos; nunca proceden de datos enviados por el navegador.
    $excluirCargador = ($tablaActual === 'operador_cargador' && $idExcluir > 0);
    $excluirOperador = ($tablaActual === 'operador' && $idExcluir > 0);

    $sql = 'SELECT MAX(lectura) AS ultimo FROM (
        SELECT horometro AS lectura FROM operador_cargador WHERE codigo = ?' .
        ($excluirCargador ? ' AND id <> ?' : '') . '
        UNION ALL
        SELECT horometro AS lectura FROM operador WHERE codigo_maquinaria = ?' .
        ($excluirOperador ? ' AND id <> ?' : '') . '
    ) AS lecturas';

    $stmt = $conn->prepare($sql);
    if ($excluirCargador && $excluirOperador) {
        $stmt->bind_param('sisi', $codigo, $idExcluir, $codigo, $idExcluir);
    } elseif ($excluirCargador) {
        $stmt->bind_param('sis', $codigo, $idExcluir, $codigo);
    } elseif ($excluirOperador) {
        $stmt->bind_param('ssi', $codigo, $codigo, $idExcluir);
    } else {
        $stmt->bind_param('ss', $codigo, $codigo);
    }
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    return ($fila && $fila['ultimo'] !== null) ? (float)$fila['ultimo'] : null;
}

/** Comprueba que una lectura no retroceda respecto del historial de la máquina. */
function horometroNoRetrocede(mysqli $conn, string $codigo, float $nuevo, string $tablaActual = '', int $idExcluir = 0): bool
{
    $ultimo = obtenerUltimoHorometro($conn, $codigo, $tablaActual, $idExcluir);
    return $ultimo === null || $nuevo >= $ultimo;
}



/* -------------------------------------------------------------------------------------------------------------
   ------------------------------------- FIN Jerez horometro_helper.php --------------------------------------
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