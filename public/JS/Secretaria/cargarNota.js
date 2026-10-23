document.addEventListener('DOMContentLoaded', () => {
    inicializarNotasCursada();
    inicializarNotasFinal();
});

function inicializarNotasCursada() {
    const app = document.getElementById('app-cargar-notas');
    if (!app) {
        return;
    }

    const idMateria = app.dataset.materia;
    const notaPromocionable = parseFloat(app.dataset.notaPromocionable) || 7;
    const notaAprobada = parseFloat(app.dataset.notaAprobada) || 4;
    const esPromocionDirecta = app.dataset.promocionDirecta === '1';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const alertAjax = document.getElementById('alert-ajax');
    const tablaCargarNotas = document.getElementById('tablaCargarNotas').querySelector('tbody');
    const tablaReprobados = document.getElementById('tablaReprobados').querySelector('tbody');
    const btnGuardar = document.getElementById('btn-guardar');
    const checkCierreAutomatico = document.getElementById('cierre_automatico');

    const ETIQUETAS = {
        promocionado: 'Promocionado',
        aprobado: 'Aprobado',
        desaprobado: 'Desaprobado',
        promocion_directa: 'Promoción directa',
        examen_flotante: 'Pendiente examen flotante',
        reprobado_directo: 'Reprobado directo',
    };

    function aplicarSituacion(wrapper, situacion) {
        wrapper.dataset.situacion = situacion;

        let badge = wrapper.querySelector('.badge-situacion');
        if (!badge) {
            badge = document.createElement('span');
            wrapper.appendChild(badge);
        }
        badge.className = 'badge-situacion badge-' + situacion;
        badge.textContent = ETIQUETAS[situacion];
    }

    // ─── Inferir situación automáticamente a partir del número cargado ────
    // Solo aplica a 1°C y 2°C (no al Rec./Prefinal ni al Cierre, que se
    // deciden aparte). Si el usuario ya eligió algo manualmente del
    // desplegable, escribir de nuevo en "Nota" lo vuelve a recalcular.
    tablaCargarNotas.addEventListener('input', (e) => {
        const input = e.target.closest('.nota-input');
        if (!input) return;

        const celda = input.closest('.nota-cell');
        if (!celda || !['1C', '2C'].includes(celda.dataset.periodo)) return;

        const wrapper = input.closest('.nota-wrapper');
        const valor = parseFloat(input.value.replace(',', '.'));

        if (isNaN(valor)) {
            delete wrapper.dataset.situacion;
            const badge = wrapper.querySelector('.badge-situacion');
            if (badge) badge.remove();
            return;
        }

        let situacion;
        if (valor >= notaPromocionable) {
            situacion = esPromocionDirecta ? 'promocion_directa' : 'promocionado';
        } else if (valor >= notaAprobada) {
            situacion = 'aprobado';
        } else {
            situacion = 'desaprobado';
        }

        aplicarSituacion(wrapper, situacion);
    });

    function mostrarAlerta(mensaje, tipo) {
        alertAjax.textContent = mensaje;
        alertAjax.className = 'alert alert-' + tipo;
        alertAjax.style.display = 'block';
        alertAjax.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        if (tipo === 'success') {
            setTimeout(() => { alertAjax.style.display = 'none'; }, 5000);
        }
    }

    // ─── Dropdown de situación (Promocionado / Aprobado / Desaprobado) ────
    // El CSS (cargarNota.css) espera la clase "show" en el <ul> y que el JS
    // calcule su posición, porque usa position:fixed para salir del
    // contenedor con scroll horizontal (.tabla-responsive).
    function cerrarDropdowns(excepto = null) {
        document.querySelectorAll('.dropdown-menu-notas.show').forEach((menu) => {
            if (menu !== excepto) menu.classList.remove('show');
        });
    }

    function posicionarDropdown(boton, menu) {
        const rect = boton.getBoundingClientRect();
        menu.style.top = (rect.bottom + 4) + 'px';
        menu.style.left = rect.left + 'px';
    }

    document.addEventListener('click', (e) => {
        const btnDropdown = e.target.closest('.btn-dropdown');

        if (btnDropdown) {
            const menu = btnDropdown.closest('.nota-wrapper').querySelector('.dropdown-menu-notas');
            const estabaAbierto = menu.classList.contains('show');

            cerrarDropdowns();

            if (!estabaAbierto) {
                posicionarDropdown(btnDropdown, menu);
                menu.classList.add('show');
            }

            return;
        }

        const opcion = e.target.closest('.opcion-nota');
        if (opcion) {
            e.preventDefault();

            const wrapper = opcion.closest('.nota-wrapper');
            cerrarDropdowns();

            aplicarSituacion(wrapper, opcion.dataset.valor);
            return;
        }

        // Click afuera: cierra cualquier dropdown abierto
        cerrarDropdowns();
    });

    // Reposicionar/cerrar si hacen scroll o resize mientras está abierto
    window.addEventListener('scroll', () => cerrarDropdowns(), true);
    window.addEventListener('resize', () => cerrarDropdowns());

    // ─── Reprobar directo ───────────────────────────────────────────────
    tablaCargarNotas.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-reprobar-directo');
        if (!btn) return;
        e.preventDefault();

        const fila = btn.closest('tr');
        const idAlumno = fila.dataset.id;

        if (!confirm('¿Seguro que querés reprobar directamente a este alumno?')) return;

        fetch(`/materias/${idMateria}/alumnos/${idAlumno}/reprobar`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
        })
        .then(r => r.json())
        .then((data) => {
            if (!data.success) {
                mostrarAlerta(data.message || 'Ocurrió un error.', 'error');
                return;
            }

            // Arma la fila de la tabla de reprobados con los valores cargados hasta el momento
            const celdas = fila.querySelectorAll('.nota-cell .nota-input');
            const nuevaFila = document.createElement('tr');
            nuevaFila.dataset.estado = 'reprobado';
            nuevaFila.dataset.id = idAlumno;
            nuevaFila.innerHTML = `
                <td>${fila.children[0].textContent}</td>
                <td>${celdas[0].value || '—'}</td>
                <td>${celdas[1].value || '—'}</td>
                <td>${celdas[2].value || '—'}</td>
                <td class="text-center">
                    <a href="#" class="btn-table btn-restaurar" title="Restaurar a cursando">
                        <i class="ri-arrow-go-back-line"></i>
                    </a>
                </td>
            `;

            tablaReprobados.appendChild(nuevaFila);
            fila.remove();

            mostrarAlerta('✓ ' + data.message, 'success');
        })
        .catch(() => mostrarAlerta('Error de conexión.', 'error'));
    });

    // ─── Restaurar a cursando ───────────────────────────────────────────
    tablaReprobados.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-restaurar');
        if (!btn) return;
        e.preventDefault();

        const fila = btn.closest('tr');
        const idAlumno = fila.dataset.id;

        if (!confirm('¿Restaurar a este alumno a cursando?')) return;

        fetch(`/materias/${idMateria}/alumnos/${idAlumno}/restaurar`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
        })
        .then(r => r.json())
        .then((data) => {
            if (!data.success) {
                mostrarAlerta(data.message || 'Ocurrió un error.', 'error');
                return;
            }

            mostrarAlerta('✓ ' + data.message + ' Refrescá la página para volver a cargarle notas.', 'success');
            fila.remove();
        })
        .catch(() => mostrarAlerta('Error de conexión.', 'error'));
    });

    // ─── Guardar notas ──────────────────────────────────────────────────
    btnGuardar.addEventListener('click', () => {

        const alumnos = Array.from(tablaCargarNotas.querySelectorAll('tr')).map((fila) => {
            const celdas = fila.querySelectorAll('.nota-cell');
            const datosFila = { id_usuario: fila.dataset.id };

            celdas.forEach((celda) => {
                const periodo = celda.dataset.periodo;

                const wrapper = celda.querySelector('.nota-wrapper');
                const valor = wrapper.querySelector('.nota-input').value;
                const situacion = wrapper.dataset.situacion || null;

                datosFila[periodo] = {
                    valor: valor === '' ? null : valor,
                    situacion: situacion === '' ? null : situacion,
                };
            });

            return datosFila;
        });

        if (alumnos.length === 0) {
            mostrarAlerta('No hay alumnos cursando para guardar.', 'error');
            return;
        }

        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<i class="ri-loader-4-line"></i> Guardando...';

        fetch(`/materias/${idMateria}/cargarNota`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                cierre_automatico: checkCierreAutomatico.checked ? 1 : 0,
                alumnos,
            }),
        })
        .then(r => r.json())
        .then((data) => {

            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<i class="ri-save-line"></i> Guardar';

            // Limpiar errores anteriores
            document.querySelectorAll('.nota-input').forEach(input => {
                input.classList.remove('input-error');
            });

            if (data.success) {

                mostrarAlerta('✓ ' + data.message, 'success');
                setTimeout(() => window.location.reload(), 800);

            } else {

                if (data.errors) {

                    Object.keys(data.errors).forEach(function (campo) {

                        // campo viene como:
                        // alumnos.0.1C.valor

                        const partes = campo.split('.');

                        if (partes.length < 4) return;

                        const indiceFila = Number(partes[1]);
                        const periodo = partes[2];

                        const fila = tablaCargarNotas.querySelectorAll('tr')[indiceFila];

                        if (!fila) return;

                        const celda = fila.querySelector(
                            '.nota-cell[data-periodo="' + periodo + '"]'
                        );

                        if (!celda) return;

                        const input = celda.querySelector('.nota-input');

                        if (input) {
                            input.classList.add('input-error');
                        }

                    });

                    mostrarAlerta('Hay notas con errores. Revisá el formulario.', 'error');

                } else {

                    mostrarAlerta(data.message || 'Ocurrió un error.', 'error');

                }

            }

        })
        .catch(() => {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<i class="ri-save-line"></i> Guardar';
            mostrarAlerta('Error de conexión. Intentá de nuevo.', 'error');
        });
    });

}

function inicializarNotasFinal() {

    const form = document.getElementById('form-notas-final');

    if (!form) {
        return;
    }

    const btnGuardar = document.getElementById('btn-guardar');
    const alertAjax  = document.getElementById('alert-ajax');
    const notaInputs = Array.from(document.querySelectorAll('.nota-input'));

    function mostrarAlerta(mensaje, tipo) {
        alertAjax.textContent = mensaje;
        alertAjax.className = 'alert alert-' + tipo;
        alertAjax.style.display = 'block';

        alertAjax.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
        });

        if (tipo === 'success') {
            setTimeout(function () {
                alertAjax.style.display = 'none';
            }, 5000);
        }
    }

    function limpiarErrores() {
        document.querySelectorAll('.error-message').forEach(function (error) {
            error.textContent = '';
        });
        document.querySelectorAll('.input-error').forEach(function (input) {
            input.classList.remove('input-error');
        });
    }

    // Limpiar error al escribir
    notaInputs.forEach(function (input) {
        input.addEventListener('input', function () {
            input.classList.remove('input-error');
        });
    });

    btnGuardar.addEventListener('click', function () {

        limpiarErrores();

        // Validación en cliente: notas entre 1 y 10, con decimales
        // permitidos (acepta coma o punto como separador). Dejar en
        // blanco es válido (un alumno puede no haber rendido todavía).
        let hayErrores = false;

        notaInputs.forEach(function (input) {
            const valor = input.value.trim();

            if (valor === '') {
                return;
            }

            const numero = Number(valor.replace(',', '.'));

            if (isNaN(numero) || numero < 1 || numero > 10) {
                input.classList.add('input-error');
                hayErrores = true;
            }
        });

        if (hayErrores) {
            mostrarAlerta('Hay notas con errores. Revisá el formulario.', 'error');
            return;
        }

        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<i class="ri-loader-4-line"></i> Guardando...';

        // Se normaliza coma → punto antes de mandar al backend, porque
        // Laravel ('numeric') no reconoce la coma como separador decimal.
        const datos = {};
        notaInputs.forEach(function (input) {
            const valor = input.value.trim();
            datos[input.name] = valor === '' ? '' : valor.replace(',', '.');
        });

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // NOTA: URL de prueba, reemplazar cuando esté la ruta real:

        fetch(form.dataset.url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify(datos),
        })
        .then(function (response) {
            return response.json().then(function (data) {
                return { status: response.status, data: data };
            });
        })
        .then(function ({ status, data }) {

            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<i class="ri-save-line"></i> Guardar';

            if (data.success) {

                mostrarAlerta('✓ ' + data.message, 'success');

            } else {

                if (data.errors) {

                    Object.entries(data.errors).forEach(function ([campo, errores]) {

                        const input = document.getElementById(campo);
                        if (input) {
                            input.classList.add('input-error');
                        }

                        const error = document.getElementById('error-' + campo);
                        if (error) {
                            error.textContent = errores[0];
                        }

                    });

                    mostrarAlerta('Hay campos con errores. Revisá el formulario.', 'error');

                } else {

                    mostrarAlerta(data.message || 'Ocurrió un error.', 'error');

                }
            }

        })
        .catch(function () {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<i class="ri-save-line"></i> Guardar';
            mostrarAlerta('Error de conexión. Intentá de nuevo.', 'error');
        });

    });

}