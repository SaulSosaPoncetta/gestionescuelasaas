document.addEventListener('DOMContentLoaded', function () {

    const espaciosAcreditadosEl = document.getElementById('espacios-acreditados');
    const progressFill = document.getElementById('progress-ring-fill');
    const progressText = document.getElementById('progress-ring-text');

    if (!espaciosAcreditadosEl || !progressFill || !progressText) return;

    const RADIO = 52;
    const CIRCUNFERENCIA = 2 * Math.PI * RADIO;
    progressFill.style.strokeDasharray = CIRCUNFERENCIA;

    // Índice de la columna "Acreditación / Final" dentro de cada fila.
    // Si el back cambia el orden de las columnas de la tabla, actualizar
    // este número (0 = primera columna).
    const COLUMNA_ACREDITACION = 2;

    function calcularStats() {
        const filas = document.querySelectorAll('.cursada-table tbody tr');

        let total = 0;
        let acreditados = 0;

        filas.forEach(function (fila) {
            const celdas = fila.querySelectorAll('td');
            const celdaAcreditacion = celdas[COLUMNA_ACREDITACION];
            if (!celdaAcreditacion) return;

            total++;

            // Se considera acreditada si el texto de la celda arranca con
            // "Acreditado" (ver comentario TODO-BACK en el blade).
            if (celdaAcreditacion.textContent.trim().startsWith('Acreditado')) {
                acreditados++;
            }
        });

        return { total: total, acreditados: acreditados };
    }

    function actualizarUI() {
        const { total, acreditados } = calcularStats();
        const porcentaje = total > 0 ? Math.round((acreditados / total) * 100) : 0;

        espaciosAcreditadosEl.textContent = acreditados;
        progressText.textContent = porcentaje + '%';

        const offset = CIRCUNFERENCIA - (porcentaje / 100) * CIRCUNFERENCIA;
        progressFill.style.strokeDashoffset = offset;
    }

    actualizarUI();

    // No se inicializa DataTable en esta pantalla a propósito: no va a
    // haber tanto contenido como para necesitar buscador/paginación/
    // cantidad de entradas. Las tablas quedan como HTML plano y
    // scrolleables (ver .table-container en analiticoAlumno.css).

    // TODO-BACK: cuando el select de carrera pase a recargar la página o
    // traer datos por AJAX al cambiar de carrera, después de que las
    // tablas nuevas estén en el DOM hay que volver a llamar a
    // actualizarUI() para que el número y el anillo se recalculen.

});