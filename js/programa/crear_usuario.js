document.addEventListener("DOMContentLoaded", function () {
    const nueva = document.getElementById("crearUsuarioPassword");
    const confirmar = document.getElementById("crearUsuarioPasswordConfirm");
    const error = document.getElementById("crearUsuarioPasswordError");
    const form = document.querySelector("form");
    if (!nueva || !confirmar || !error || !form) return;

    function validar() {
        if (confirmar.value && confirmar.value !== nueva.value) {
            error.textContent = "Las contraseñas no coinciden";
            return false;
        }
        error.textContent = "";
        return true;
    }

    nueva.addEventListener("input", validar);
    confirmar.addEventListener("input", validar);
    form.addEventListener("submit", function (event) {
        if (!validar()) event.preventDefault();
    });
});
