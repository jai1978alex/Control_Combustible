const botonesAbrir = document.querySelectorAll(".btnAbrirModal");

const modal = document.getElementById("modalConfirmacion");

const contenido = document.getElementById("contenidoConfirmacion");

const btnEditar = document.getElementById("btnEditar");

const btnConfirmar = document.getElementById("btnConfirmar");

let formularioActivo = null;



/* ========================================= */
/* ABRIR MODAL */
/* ========================================= */

botonesAbrir.forEach(btn => {

    btn.addEventListener("click", () => {

        formularioActivo = btn.closest("form");

        const inputs = formularioActivo.querySelectorAll(
            "input, select, textarea"
        );

        /*
         * Se construye el contenido con nodos DOM (createElement + textContent)
         * en vez de innerHTML con plantillas de texto. Los valores de estos
         * campos pueden venir de un código QR escaneado o de texto libre
         * (observación), por lo que no son datos de confianza: si se insertan
         * como HTML permiten XSS. textContent nunca interpreta HTML/JS.
         */
        contenido.replaceChildren();

        inputs.forEach(input => {

            if (
                input.type !== "button" &&
                input.type !== "submit" &&
                input.type !== "hidden"
            ) {

                const item = document.createElement("div");
                item.className = "confirmacion-item";

                const etiqueta = document.createElement("strong");
                etiqueta.textContent = input.name;

                const valor = document.createElement("span");
                valor.textContent = input.value;

                item.appendChild(etiqueta);
                item.appendChild(valor);
                contenido.appendChild(item);
            }
        });

        modal.style.display = "flex";
    });
});



/* ========================================= */
/* EDITAR */
/* ========================================= */

if (!modal || !contenido || !btnEditar || !btnConfirmar) { console.warn("Modal de confirmación no disponible"); }

if (btnEditar) btnEditar.addEventListener("click", () => {

    modal.style.display = "none";

});



/* ========================================= */
/* CONFIRMAR */
/* ========================================= */

if (btnConfirmar) btnConfirmar.addEventListener("click", () => {

    formularioActivo.submit();

});



/* ========================================= */
/* CERRAR AFUERA */
/* ========================================= */

window.addEventListener("click", e => {

    if (e.target === modal) {

        modal.style.display = "none";
    }
});

let tiempoInactivo = 15 * 60 * 1000;
