document.addEventListener('DOMContentLoaded', function () {
    const imprimir = document.getElementById('btnImprimir');
    const cerrar = document.getElementById('btnCerrar');
    if (imprimir) imprimir.addEventListener('click', function () { window.print(); });
    if (cerrar) cerrar.addEventListener('click', function () { window.close(); });
});
