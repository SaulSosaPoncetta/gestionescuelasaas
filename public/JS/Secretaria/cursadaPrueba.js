// ── Sidebar mobile ────────────────────────────────
const menuBtn = document.getElementById('menu-btn');
const sidebar = document.querySelector('.sidebar');
const overlay = document.getElementById('overlay');

menuBtn.addEventListener('click', () => {
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
});
overlay.addEventListener('click', () => {
    sidebar.classList.remove('active');
    overlay.classList.remove('active');
});

// ── Lógica de filtros ─────────────────────────────
const selNombre    = document.getElementById('sel-nombre');
const selPlan      = document.getElementById('sel-plan');
const btnFiltrar   = document.getElementById('btn-filtrar');
const btnLimpiar   = document.getElementById('btn-limpiar');
const actionBtns   = document.querySelectorAll('.action-buttons .btn-primary');
const placeholder  = document.getElementById('table-placeholder');
const tableContent = document.getElementById('table-content');

// Habilita/deshabilita botones según si hay carrera seleccionada
function actualizarEstado() {
    const hayCarrera = selNombre.value !== '';
    btnFiltrar.disabled = !hayCarrera;
    actionBtns.forEach(btn => btn.disabled = !hayCarrera);
}

selNombre.addEventListener('change', actualizarEstado);

// Filtrar: muestra la tabla (acá irá el fetch al backend)
btnFiltrar.addEventListener('click', () => {
    // TODO: reemplazar esto por un fetch/AJAX al backend
    // con selNombre.value y selPlan.value como parámetros
    placeholder.style.display  = 'none';
    tableContent.style.display = 'block';
});

// Limpiar: resetea selects, deshabilita botones, vuelve al placeholder
btnLimpiar.addEventListener('click', () => {
    selNombre.value = '';
    selPlan.value   = '';
    actualizarEstado();
    placeholder.style.display  = 'block';
    tableContent.style.display = 'none';
});

// Lupas: de momento solo un console.log hasta que esté la pantalla destino
document.querySelectorAll('.btn-lupa').forEach(btn => {
    btn.addEventListener('click', () => {
        // TODO: redirigir a la pantalla de detalle de materia
        console.log('Ver detalle de materia');
    });
});

// Botones de acción: navegan a su ruta cuando están habilitados
actionBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        if (!btn.disabled && btn.dataset.href && btn.dataset.href !== '#') {
            window.location.href = btn.dataset.href;
        }
    });
});