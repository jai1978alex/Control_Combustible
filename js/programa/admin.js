document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form[action$="eliminar_usuario.php"], form[action$="eliminar_registro.php"]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            const esRegistro = form.action.endsWith('eliminar_registro.php');
            const mensaje = esRegistro
                ? '¿Quieres confirmar la eliminación de este registro de combustible?\n\nEsta acción no se puede deshacer.'
                : '¿Quieres confirmar la eliminación de este usuario?\n\nEsta acción no se puede deshacer.';

            if (!window.confirm(mensaje)) {
                event.preventDefault();
            }
        });
    });

    // Los mensajes de confirmación/error se ocultan automáticamente después de unos segundos.
    // La contraseña temporal se excluye porque debe permanecer visible una sola vez.
    document.querySelectorAll('.mensaje-admin:not(.temp-password-flash)').forEach(function (mensaje) {
        window.setTimeout(function () {
            mensaje.classList.add('mensaje-admin-oculto');

            window.setTimeout(function () {
                if (mensaje && mensaje.parentNode) {
                    mensaje.parentNode.removeChild(mensaje);
                }
            }, 400);
        }, 3000);
    });
});
