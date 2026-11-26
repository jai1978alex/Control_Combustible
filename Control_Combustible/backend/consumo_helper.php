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
   ------------------------------------- INICIO Jerez consumo_helper.php ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

declare(strict_types=1);

// Calcula el consumo (rendimiento) de una carga de Operador.
//
// Regla: el estanque se llena en cada carga, así que los litros de ESTA carga
// son lo que la máquina consumió desde la carga ANTERIOR del mismo código.
//   - Si el registro trae horómetro (> 0): consumo = litros / (horómetro actual - horómetro anterior)  → L/h
//   - Si no trae horómetro pero sí kilómetro (> 0): consumo = litros / (km actual - km anterior) * 100 → L/100 km
//   - Sin lectura anterior, o lectura que no avanzó: 0.00 (no hay base de comparación).
// La carga anterior se busca en operador y en operador_cargador (mismo código de maquinaria), solo registros vigentes.
function calcularConsumoOperador(mysqli $conn, string $codigo, float $horometro, float $kilometro, float $litros, string $fecha, int $excluirId = 0): float {
    // Tope de la columna DECIMAL(10,2)
    $max = 99999999.99;
    try {
        if ($horometro > 0) {
            // Última lectura de horómetro anterior a esta fecha, en cualquiera de las dos tablas
            $st = $conn->prepare(
                'SELECT h FROM (
                    SELECT horometro AS h, fecha_registro AS f, id FROM operador
                      WHERE codigo_maquinaria=? AND eliminado_at IS NULL AND horometro>0 AND fecha_registro<? AND id<>?
                    UNION ALL
                    SELECT horometro AS h, fecha_registro AS f, id FROM operador_cargador
                      WHERE codigo=? AND eliminado_at IS NULL AND horometro>0 AND fecha_registro<?
                 ) t ORDER BY f DESC, id DESC LIMIT 1'
            );
            $st->bind_param('ssiss', $codigo, $fecha, $excluirId, $codigo, $fecha);
            $st->execute();
            $fila = $st->get_result()->fetch_assoc();
            if ($fila) {
                $delta = $horometro - (float)$fila['h'];
                if ($delta > 0) { return round(min($litros / $delta, $max), 2); }
            }
            return 0.0;
        }
        if ($kilometro > 0) {
            $st = $conn->prepare(
                'SELECT kilometro FROM operador
                  WHERE codigo_maquinaria=? AND eliminado_at IS NULL AND kilometro>0 AND fecha_registro<? AND id<>?
                  ORDER BY fecha_registro DESC, id DESC LIMIT 1'
            );
            $st->bind_param('ssi', $codigo, $fecha, $excluirId);
            $st->execute();
            $fila = $st->get_result()->fetch_assoc();
            if ($fila) {
                $delta = $kilometro - (float)$fila['kilometro'];
                if ($delta > 0) { return round(min($litros / $delta * 100, $max), 2); }
            }
        }
    } catch (Throwable $e) {
        // Si algo falla, el registro igual se guarda con consumo 0
        error_log('calcularConsumoOperador: ' . $e->getMessage());
    }
    return 0.0;
}

// Texto con la unidad del consumo de un registro de Operador (según la misma regla de arriba)
function unidadConsumo(array $registro): string {
    return ((float)($registro['horometro'] ?? 0)) > 0 ? 'L/h' : 'L/100 km';
}


/* -------------------------------------------------------------------------------------------------------------
   ------------------------------------- FIN Jerez consumo_helper.php --------------------------------------
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
