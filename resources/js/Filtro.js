import $ from 'jquery';
import DataTable from 'datatables.net-dt';

// Enlazamos obligatoriamente DataTables con el jQuery global de Vite
window.jQuery = $;
window.$ = $;

$(document).ready(function () {

    function initDataTable(selector, opciones = {}) {
        if (!$(selector).length) return null;

        const defaults = {
            order: [[0, 'asc']],
            dom: 'tp',
            pageLength: 10,
            language: {
                info:         "Mostrando _START_–_END_ de _TOTAL_ registros",
                infoEmpty:    "Sin resultados",
                infoFiltered: "(filtrado de _MAX_ registros)",
                zeroRecords:  "No se encontraron resultados",
                paginate: { first: "«", last: "»", next: "›", previous: "‹" }
            }
        };

        // Sacamos colEstado de las opciones antes de pasarle el resto a DataTable
        const { colEstado, ...opcionesDataTable } = opciones;

        const tabla = $(selector).DataTable($.extend(true, {}, defaults, opcionesDataTable));

        // Filtro de texto genérico, si existe el input en esta vista
        $(document).on('input', '#filtro-nombre, input[name="nombre_apellido"]', function () {
            tabla.search(this.value).draw();
        });

        // Filtro de estado genérico, solo si se indicó la columna
        if (colEstado !== undefined) {
            $(document).on('change', '#filtro-estado', function () {
                const valor = this.value;
                valor === ''
                    ? tabla.column(colEstado).search('').draw()
                    : tabla.column(colEstado).search('^' + valor + '$', true, false).draw();
            });
        }

        return tabla;
    }

    // =========================================================================
    // TABLA ADMINISTRADORES / DOCENTES (selector dinámico)
    // =========================================================================

    const selectorAdminDocente = $('#tablaAdministradores').length
        ? '#tablaAdministradores'
        : '#tablaDocentes';

    initDataTable(selectorAdminDocente, {
        columnDefs: [{ orderable: false, targets: [1, 2, 3, 4] }],
        colEstado: 2
    });

    // =========================================================================
    // TABLA ALUMNOS
    // col 0 = Nombre | col 1 = DNI | col 2 = Teléfono | col 3 = Estado | col 4 = Perfil
    // =========================================================================

    initDataTable('#tablaAlumnos', {
        columnDefs: [{ orderable: false, targets: [1, 2, 3, 4] }],
        pageLength: 15,
        colEstado: 2 
    });

    // =========================================================================
    // TABLA MATERIAS
    // =========================================================================

    initDataTable('#tablaMaterias', {
        pageLength: 5
    });

    // =========================================================================
    // TABLA CARRERAS
    // col 0 = Nombre | col 1 = Título | col 2 = Plan vigente | col 3 = Perfil
    // =========================================================================

    initDataTable('#tablaCarreras', {
        columnDefs: [{ orderable: false, targets: [1, 2, 3] }],
        pageLength: 15
    });

    

    initDataTable('#tablaPlanEstudio', {
        columnDefs: [{ orderable: false, targets: [3] }],
        pageLength: 15
    });

    initDataTable('#tablaEspacios', {
        columnDefs: [{ orderable: false, targets: [5] }],
        pageLength: 10
    });

    
});

    // =========================================================================
    // TABLA CICLOS LECTIVOS
    // =========================================================================

if ($('#tablaCiclos').length) {
    const tablaCiclos = $('#tablaCiclos').DataTable({
        order: [[0, 'asc']],
        dom: 'tp',
        pageLength: 10,
        language: {
            info: "Mostrando _START_–_END_ de _TOTAL_ registros",
            infoEmpty: "Sin ciclos lectivos",
            zeroRecords: "No se encontraron ciclos lectivos",
            paginate: {
                first: "«",
                last: "»",
                next: "›",
                previous: "‹"
            }
        }
    });

    $('#buscador-ciclo').on('input', function () {
        tablaCiclos.search(this.value).draw();
    });
}