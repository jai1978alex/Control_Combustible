// Sitio Web Creado por Jerez.
// Direccion: Miraflorez #1280
// Quintero - Chile
// jaime.jerez1978@gmail.com
// https://www.
// Creado, Programado y Diseñado por Jerez.
// JJA 

// 

/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- INICIO validador_rut.js ------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

// TITULO MODULO VALIDADOR DE RUT
// Define el objeto ValidadorRut con dos funciones: format (da
// formato al RUT) y validate (comprueba el dígito verificador).

// RUT Validation Module

// Declara el objeto que agrupa las funciones del validador
const ValidadorRut = {

    // TITULO FORMATEAR RUT
    // Limpia el texto y deja el RUT con la forma CUERPO-DV.

    // Format RUT with hyphen
    // Define la función format, que recibe el texto escrito
    format(value) {
        // Remove all non-digits and 'k'
        // Elimina todo lo que no sea dígito o la letra K y pasa a mayúscula
        const cleaned = value.replace(/[^\dkK]/g, '').toUpperCase();

        // Format: XXXXXXXX-X
        // Si quedan al menos 2 caracteres, entra para agregar el guion
        if (cleaned.length > 1) {
            // Separa el cuerpo (todos los caracteres menos el último)
            const body = cleaned.slice(0, -1);
            // Separa el dígito verificador (el último carácter)
            const dv = cleaned.slice(-1);
            // Devuelve el cuerpo, un guion y el dígito verificador
            return `${body}-${dv}`;
        }

        // Si hay 0 o 1 caracteres, devuelve el texto limpio sin guion
        return cleaned;
    },

    // TITULO VALIDAR RUT
    // Calcula el dígito verificador con el algoritmo Módulo 11
    // y lo compara con el ingresado. Devuelve true o false.

    // Validate RUT using Chilean algorithm (Modulo 11)
    // Define la función validate, que recibe el RUT completo
    validate(rut) {
        // Remove formatting
        // Quita los puntos y el guion del RUT
        const cleanRut = rut.replace(/\./g, '').replace(/-/g, '');

        // Si quedan menos de 2 caracteres, el RUT no es válido
        if (cleanRut.length < 2) return false;

        // Separa el cuerpo numérico (todo menos el último carácter)
        const body = cleanRut.slice(0, -1);
        // Toma el dígito verificador ingresado y lo pasa a mayúscula
        const dv = cleanRut.slice(-1).toUpperCase();

        // Validate that body contains only digits
        // Si el cuerpo contiene algo que no sea un dígito, no es válido
        if (!/^\d+$/.test(body)) return false;

        // Calculate expected DV using Modulo 11
        // Acumulador de la suma ponderada
        let sum = 0;
        // Multiplicador inicial (2)
        let multiplier = 2;

        // Recorre los dígitos del cuerpo de derecha a izquierda
        for (let i = body.length - 1; i >= 0; i--) {
            // Suma el dígito multiplicado por el multiplicador actual
            sum += parseInt(body[i]) * multiplier;
            // El multiplicador sube de 2 a 7 y luego vuelve a 2
            multiplier = multiplier === 7 ? 2 : multiplier + 1;
        }

        // Calcula el valor esperado: 11 menos el resto de dividir la suma por 11
        const expectedDV = 11 - (sum % 11);
        // Variable donde se guardará el dígito verificador esperado como texto
        let expectedDVStr = '';

        // Si el resultado es 11, el dígito verificador es 0
        if (expectedDV === 11) {
            // Asigna "0"
            expectedDVStr = '0';
        // Si el resultado es 10, el dígito verificador es K
        } else if (expectedDV === 10) {
            // Asigna "K"
            expectedDVStr = 'K';
        // En cualquier otro caso, usa el número calculado
        } else {
            // Convierte el número a texto
            expectedDVStr = expectedDV.toString();
        }

        // Devuelve true si el dígito ingresado coincide con el esperado
        return dv === expectedDVStr;
    }
};

// TITULO DISPONIBILIDAD GLOBAL
// Publica el validador en window para usarlo desde otros archivos.

// Make ValidadorRut available globally
// Asigna el objeto a window para que otros scripts puedan usarlo
window.ValidadorRut = ValidadorRut;



/* --------------------------------------------------------------------------------------------------------------
   ---------------------------------------- FIN validador_rut.js --------------------------------------
   -------------------------------------------------------------------------------------------------------------- */


// Sitio Web Creado por Jerez.
// Direccion: Miraflorez #1280
// Quintero - Chile
// jaime.jerez1978@gmail.com
// https://www.
// Creado, Programado y Diseñado por Jerez.
// JJA
