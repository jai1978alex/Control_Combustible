// Sitio Web Creado por Jerez.
// JJA
// Pide confirmación antes de eliminar un movimiento del estanque (la política CSP no permite scripts dentro del HTML).
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (ev) {
            if (!window.confirm(form.getAttribute('data-confirm'))) { ev.preventDefault(); }
        });
    });
});
