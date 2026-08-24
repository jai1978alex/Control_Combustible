// recuperar.js

document.addEventListener("DOMContentLoaded", () => {

    const form = document.getElementById("recuperarForm");
    const emailInput = document.getElementById("email");

    if (!form) return;

    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        const email = emailInput.value.trim();

        // Validación básica
        if (!email) {
            mostrarMensaje("Ingrese un correo electrónico", "error");
            return;
        }

        if (!validarEmail(email)) {
            mostrarMensaje("Correo no válido", "error");
            return;
        }

        try {
            const response = await fetch("../../backend/recuperar.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ email })
            });

            const data = await response.json();

            mostrarMensaje(data.message, "success");

            form.reset();

        } catch (error) {
            console.error(error);
            mostrarMensaje("Error al conectar con el servidor", "error");
        }
    });

    function validarEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function mostrarMensaje(mensaje, tipo) {
        let div = document.getElementById("mensaje");

        if (!div) {
            div = document.createElement("div");
            div.id = "mensaje";
            form.appendChild(div);
        }

        div.textContent = mensaje;
        div.style.marginTop = "10px";
        div.style.padding = "10px";
        div.style.borderRadius = "8px";
        div.style.textAlign = "center";

        if (tipo === "error") {
            div.style.backgroundColor = "#f8d7da";
            div.style.color = "#721c24";
        } else {
            div.style.backgroundColor = "#d4edda";
            div.style.color = "#155724";
        }
    }

});