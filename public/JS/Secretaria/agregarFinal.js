document.addEventListener('DOMContentLoaded', function () {

    // TODO-BACK: reemplazar por los docentes reales. El resto del código
    // no necesita cambios, solo espera objetos con esta forma:
    // { id, codigo, nombre, dni }
    const docentesMock = [
        { id: 1, codigo: '0001', nombre: 'Barragán, Carlos',  dni: '30111222' },
        { id: 2, codigo: '0002', nombre: 'Wagner, Rafael',    dni: '29888777' },
        { id: 3, codigo: '0003', nombre: 'Letier, Ana',       dni: '28555444' },
        { id: 4, codigo: '0004', nombre: 'Gómez, Marina',     dni: '31222333' },
        { id: 5, codigo: '0005', nombre: 'Suárez, Pablo',     dni: '27999111' },
    ];

    const dropdown       = document.getElementById('docente-dropdown');
    const inputBuscar    = document.getElementById('buscar-docente');
    const dropdownMenu    = document.getElementById('docente-dropdown-menu');
    const tablaBody       = document.getElementById('tabla-docentes-body');

    if (!dropdown || !inputBuscar || !dropdownMenu || !tablaBody) return;

    const idsAgregados = new Set();

    function normalizar(texto) {
        return texto.toLowerCase();
    }

    function renderDropdown(filtro) {
        const texto = normalizar(filtro.trim());

        const resultados = docentesMock.filter(function (docente) {
            return normalizar(docente.nombre).includes(texto) ||
                   docente.dni.includes(texto) ||
                   docente.codigo.includes(texto);
        });

        dropdownMenu.innerHTML = '';

        if (resultados.length === 0) {
            const item = document.createElement('div');
            item.className = 'dropdown-item disabled';
            item.textContent = 'Sin resultados';
            dropdownMenu.appendChild(item);
            dropdownMenu.classList.add('show');
            return;
        }

        resultados.forEach(function (docente) {
            const item = document.createElement('div');
            const yaAgregado = idsAgregados.has(docente.id);

            item.className = 'dropdown-item' + (yaAgregado ? ' disabled' : '');
            item.textContent = docente.nombre + ' — DNI ' + docente.dni +
                (yaAgregado ? ' (ya agregado)' : '');

            if (!yaAgregado) {
                item.addEventListener('click', function () {
                    agregarDocente(docente);
                    inputBuscar.value = '';
                    dropdownMenu.classList.remove('show');
                });
            }

            dropdownMenu.appendChild(item);
        });

        dropdownMenu.classList.add('show');
    }

    function agregarDocente(docente) {
        if (idsAgregados.has(docente.id)) return;
        idsAgregados.add(docente.id);

        const fila = document.createElement('tr');
        fila.dataset.idDocente = docente.id;

        fila.innerHTML =
            '<td>' + docente.codigo + '</td>' +
            '<td>' + docente.nombre + '</td>' +
            '<td>' + docente.dni + '</td>' +
            '<td>' +
                '<select class="tabla-tipo-select" name="tipo_docente_' + docente.id + '">' +
                    '<option value="Titular">Titular</option>' +
                    '<option value="Vocal">Vocal</option>' +
                '</select>' +
            '</td>' +
            '<td>' +
                '<button type="button" class="btn-table ri-delete-bin-6-line btn-quitar-docente" title="Quitar"></button>' +
            '</td>';

        fila.querySelector('.btn-quitar-docente').addEventListener('click', function () {
            idsAgregados.delete(docente.id);
            fila.remove();
        });

        tablaBody.appendChild(fila);
    }

    inputBuscar.addEventListener('input', function () {
        if (this.value.trim() === '') {
            dropdownMenu.classList.remove('show');
            return;
        }
        renderDropdown(this.value);
    });

    inputBuscar.addEventListener('focus', function () {
        if (this.value.trim() !== '') {
            renderDropdown(this.value);
        }
    });

    // Cerrar el dropdown al clickear afuera
    document.addEventListener('click', function (e) {
        if (!dropdown.contains(e.target)) {
            dropdownMenu.classList.remove('show');
        }
    });

});