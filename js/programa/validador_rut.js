// RUT Validation Module

const ValidadorRut = {
    // Format RUT with hyphen
    format(value) {
        // Remove all non-digits and 'k'
        const cleaned = value.replace(/[^\dkK]/g, '').toUpperCase();
        
        // Format: XXXXXXXX-X
        if (cleaned.length > 1) {
            const body = cleaned.slice(0, -1);
            const dv = cleaned.slice(-1);
            return `${body}-${dv}`;
        }
        
        return cleaned;
    },

    // Validate RUT using Chilean algorithm (Modulo 11)
    validate(rut) {
        // Remove formatting
        const cleanRut = rut.replace(/\./g, '').replace(/-/g, '');
        
        if (cleanRut.length < 2) return false;

        const body = cleanRut.slice(0, -1);
        const dv = cleanRut.slice(-1).toUpperCase();

        // Validate that body contains only digits
        if (!/^\d+$/.test(body)) return false;

        // Calculate expected DV using Modulo 11
        let sum = 0;
        let multiplier = 2;

        for (let i = body.length - 1; i >= 0; i--) {
            sum += parseInt(body[i]) * multiplier;
            multiplier = multiplier === 7 ? 2 : multiplier + 1;
        }

        const expectedDV = 11 - (sum % 11);
        let expectedDVStr = '';
        
        if (expectedDV === 11) {
            expectedDVStr = '0';
        } else if (expectedDV === 10) {
            expectedDVStr = 'K';
        } else {
            expectedDVStr = expectedDV.toString();
        }

        return dv === expectedDVStr;
    }
};

// Make ValidadorRut available globally
window.ValidadorRut = ValidadorRut;
