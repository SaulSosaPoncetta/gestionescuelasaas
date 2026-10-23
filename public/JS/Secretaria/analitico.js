// analiticoAlumno.js

// ── Sidebar mobile ────────────────────────────────
const menuBtn = document.getElementById('menu-btn');
const sidebar = document.querySelector('.sidebar');
const overlay = document.getElementById('overlay');

if (menuBtn) {
    menuBtn.addEventListener('click', () => {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    });
}

overlay.addEventListener('click', () => {
    sidebar.classList.remove('active');
    overlay.classList.remove('active');
});

// ── Analítico ─────────────────────────────────────
const selCarrera      = document.getElementById('sel-carrera');

const emptyCareer     = document.getElementById('empty-career');
const analyticContent = document.getElementById('analytic-content');

const progressCircle  = document.getElementById('progress-circle');
const progressText    = document.getElementById('progress-text');

const noDataPlaceholder = document.getElementById('no-data-placeholder');
const tablesContent     = document.getElementById('tables-content');

// Circunferencia del círculo
const radius = 48;
const circumference = 2 * Math.PI * radius;

// Ejemplo porcentaje
let porcentaje = 38;

// Actualiza círculo
function actualizarProgreso(valor) {

    const offset =
        circumference - (valor / 100) * circumference;

    progressCircle.style.strokeDasharray = circumference;
    progressCircle.style.strokeDashoffset = offset;

    progressText.textContent = `${valor}%`;
}

actualizarProgreso(porcentaje);

// Cambio de carrera
selCarrera.addEventListener('change', () => {

    const carreraSeleccionada = selCarrera.value;

    // Sin carrera seleccionada
    if (carreraSeleccionada === '') {

        emptyCareer.style.display = 'block';
        analyticContent.style.display = 'none';

        return;
    }

    // Mostrar contenido
    emptyCareer.style.display = 'none';
    analyticContent.style.display = 'block';

    // ─────────────────────────────────────
    // EJEMPLO:
    // carrera 2 no tiene datos
    // ─────────────────────────────────────

    if (carreraSeleccionada === '2') {

        noDataPlaceholder.style.display = 'block';
        tablesContent.style.display = 'none';

    } else {

        noDataPlaceholder.style.display = 'none';
        tablesContent.style.display = 'block';
    }

});

// ── Botón imprimir (placeholder) ─────────────────
document.querySelector('.print-btn')
.addEventListener('click', () => {

    console.log('Imprimir analítico');

});