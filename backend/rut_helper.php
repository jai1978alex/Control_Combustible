<?php

/* ========================================================= */
/* FUNCIONES COMPARTIDAS DE VALIDACION DE RUT                */
/* Usadas por guardar_cargador.php y guardar_operador.php    */
/* ========================================================= */

/* VALIDAR FORMATO (XX.XXX.XXX-X) */
function validarFormatoRut($rut) {
    return (bool) preg_match('/^[0-9]{1,2}\.[0-9]{3}\.[0-9]{3}-[0-9kK]{1}$/', $rut);
}

/* VALIDAR DIGITO VERIFICADOR (Modulo 11) */
function validarRut($rut) {

    $rut = preg_replace('/[^kK0-9]/', '', $rut);

    $cuerpo = substr($rut, 0, -1);
    $dv = strtoupper(substr($rut, -1));

    $suma = 0;
    $multiplo = 2;

    for ($i = strlen($cuerpo) - 1; $i >= 0; $i--) {

        $suma += $cuerpo[$i] * $multiplo;

        $multiplo++;

        if ($multiplo > 7) {
            $multiplo = 2;
        }

    }

    $resto = $suma % 11;
    $dvEsperado = 11 - $resto;

    if ($dvEsperado == 11) {
        $dvEsperado = '0';
    } elseif ($dvEsperado == 10) {
        $dvEsperado = 'K';
    } else {
        $dvEsperado = (string)$dvEsperado;
    }

    return $dvEsperado === $dv;

}
