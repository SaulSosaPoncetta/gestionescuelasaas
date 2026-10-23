<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil Materia</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="{{ asset('fonts/remixicon/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/sideBar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filtro.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tablas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/botones.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/materiaPerfil.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/cursadaPerfil.css') }}">

    @vite(['resources/css/app.css'])
</head>

<body>
    <button class="mobile-menu-btn" id="menu-btn">
        <i class="ri-menu-line"></i>
    </button>

    @include('partials.sidebar')

    <div class="main">
        <div class="top-actions">

            <button
                class="btn-modificar"
                type="button"
                onclick="window.location='{{ route('planEspacios', $materia->carrera->id_carrera) }}'">
                <i class="ri-arrow-left-line"></i> Volver
            </button>

            <div class="acciones-fila">
                <button class="btn-modificar" id="btn-modificar" type="button">
                    <i class="ri-edit-line"></i> Modificar
                </button>

                <button
                    class="btn-primary"
                    type="button"
                    onclick="window.location.href='{{ route('materias.cargarNota.form', $materia->id_materia) }}'">
                    Cargar Notas
                </button>

                <button
                    class="btn-primary"
                    type="button"
                    onclick="window.location.href='{{ route('materias.asignarProfesor.form', $materia->id_materia) }}'">
                    Asignar Profesor
                </button>

                <button
                    class="btn-primary"
                    type="button"
                    onclick="window.location.href='{{ route('materias.agregarAlumno.form', $materia->id_materia) }}'">
                    Agregar Alumno
                </button>
            </div>

        </div>

        <h1 class="page-title">
            {{ $materia->codigo }} - {{ $materia->nombre }}
        </h1>

        <div class="form-container">

            <div id="alert-ajax" class="alert" style="display:none;"></div>

            <form class="materia-form" id="form-materia">
                @csrf
                @method('PUT')

                <div class="form-grid">

                    <label for="nombre">Nombre</label>
                    <div class="input-group">
                        <input type="text" name="nombre" id="nombre" value="{{ $materia->nombre }}" disabled>
                        <small class="error-message" id="error-nombre"></small>
                    </div>

                    <label for="id_plan_estudio">Plan de estudios</label>
                    <div class="input-group">
                        <select id="id_plan_estudio" disabled>
                            <option selected>{{ $materia->planEstudio->titulo ?? '—' }}</option>
                        </select>
                        <small class="error-message" id="error-id_plan_estudio"></small>
                    </div>

                    <label for="id_anio">Año curricular</label>
                    <div class="input-group">
                        <select id="id_anio" disabled>
                            <option selected>{{ $materia->anio_curricular_texto }}</option>
                        </select>
                        <small class="error-message" id="error-id_anio"></small>
                    </div>

                    <label for="orden_anio">N° de orden dentro de año</label>
                    <div class="input-group">
                        <select id="orden_anio" disabled>
                            <option selected>{{ $materia->orden_anio }}</option>
                        </select>
                        <small class="error-message" id="error-orden_anio"></small>
                    </div>

                    <label for="carga_horaria_anual">Carga horaria anual</label>
                    <div class="input-group">
                        <input
                            type="number"
                            name="carga_horaria_anual"
                            id="carga_horaria_anual"
                            value="{{ $materia->carga_horaria_anual }}"
                            disabled
                        >
                        <small class="error-message" id="error-carga_horaria_anual"></small>
                    </div>

                </div>

                <div class="checkbox-group">

                    <div class="checkbox-row">
                        <label>TFO/EDI/UDI</label>
                        <input type="checkbox" name="tfo_edi_udi" id="tfo_edi_udi" value="1" {{ $materia->tfo_edi_udi ? 'checked' : '' }} disabled>
                    </div>

                    <div class="checkbox-row">
                        <label>Acreditable con final</label>
                        <input type="checkbox" name="acreditable_final" id="acreditable_final" value="1" {{ $materia->acreditable_final ? 'checked' : '' }} disabled>
                    </div>

                    <div class="checkbox-row">
                        <label>Promocionable</label>
                        <input type="checkbox" name="promocionable" id="promocionable" value="1" {{ $materia->promocionable ? 'checked' : '' }} disabled>
                    </div>

                    <div class="checkbox-row">
                        <label>Optativa</label>
                        <input type="checkbox" name="optativa" id="optativa" value="1" {{ $materia->optativa ? 'checked' : '' }} disabled>
                    </div>

                </div>

                <div class="form-actions">
                    <button type="button" class="btn-primary" id="btn-guardar" style="display:none;">
                        <i class="ri-save-line"></i> Guardar
                    </button>
                    <button type="button" class="btn-secondary" id="btn-cancelar" style="display:none;">
                        Cancelar
                    </button>
                </div>

            </form>

        </div>

        <div>

            <div class="section-divider"></div>

            <h2 class="section-title">Profesores asignados</h2>

            @if(count($profesoresAsignados))

                <div class="table-container">
                    <table id="tablaProfesoresAsignados" class="cursada-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>DNI</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                                <th style="width:80px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($profesoresAsignados as $prof)
                            <tr>
                                <td>{{ $prof->docente->usuario->apellido }}, {{ $prof->docente->usuario->nombre }}</td>
                                <td>{{ optional($prof->docente->usuario->dnis->first())->dni ?? '—' }}</td>
                                <td>{{ optional($prof->fecha_inicio)->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ optional($prof->fecha_fin)->format('d/m/Y') ?? '—' }}</td>
                                <td class="text-center">
                                    <div class="table-actions">
                                        <a href="{{ route('docentes.show', $prof->id_usuario_docente) }}" class="btn-table" title="Ver profesor">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <a
                                            href="#"
                                            class="btn-table btn-quitar-profesor"
                                            title="Quitar profesor"
                                            data-profesor="{{ $prof->docente->usuario->apellido }}, {{ $prof->docente->usuario->nombre }}"
                                            data-docente="{{ $prof->id_usuario_docente }}"
                                            style="background:#dc2626;">
                                            <i class="ri-delete-bin-line"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @else

                <div class="empty-state">
                    <i class="ri-user-line"></i>
                    <p>No hay profesores asignados a esta materia.</p>
                </div>

            @endif

        </div>
    </div>

    @include('partials.footer')

    <div class="overlay" id="overlay"></div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {

        // ─── Sidebar ──────────────────────────────────────────────────
        const menuBtn = document.getElementById('menu-btn');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('overlay');

        if (menuBtn) {
            menuBtn.addEventListener('click', function () {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            });
        }

        overlay?.addEventListener('click', function () {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });

        // ─── Campos, alerta y errores ─────────────────────────────────
        const btnModificar = document.getElementById('btn-modificar');
        const btnGuardar   = document.getElementById('btn-guardar');
        const btnCancelar  = document.getElementById('btn-cancelar');
        const form         = document.getElementById('form-materia');
        const alertAjax    = document.getElementById('alert-ajax');

        const camposEditables = Array.from(
            form.querySelectorAll('input[name], select[name]')
        ).filter(el => el.name !== '_token' && el.name !== '_method');

        let valoresOriginales = {};

        function guardarValoresOriginales() {
            camposEditables.forEach(function (campo) {
                valoresOriginales[campo.name] = campo.type === 'checkbox' ? campo.checked : campo.value;
            });
        }

        function restaurarValoresOriginales() {
            camposEditables.forEach(function (campo) {
                if (campo.type === 'checkbox') {
                    campo.checked = valoresOriginales[campo.name] ?? false;
                } else {
                    campo.value = valoresOriginales[campo.name] ?? '';
                }
            });
        }

        function habilitarEdicion() {
            camposEditables.forEach(function (campo) {
                campo.disabled = false;
            });
            btnModificar.innerHTML = '<i class="ri-edit-line"></i> Editando...';
            btnModificar.classList.add('activo');
            btnGuardar.style.display  = '';
            btnCancelar.style.display = '';
        }

        function deshabilitarEdicion() {
            camposEditables.forEach(function (campo) {
                campo.disabled = true;
            });
            btnModificar.innerHTML = '<i class="ri-edit-line"></i> Modificar';
            btnModificar.classList.remove('activo');
            btnGuardar.style.display  = 'none';
            btnCancelar.style.display = 'none';
        }

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

        guardarValoresOriginales();

        btnModificar.addEventListener('click', function () {
            habilitarEdicion();
        });

        btnCancelar.addEventListener('click', function () {
            restaurarValoresOriginales();
            deshabilitarEdicion();
            limpiarErrores();
            alertAjax.style.display = 'none';
        });

        camposEditables.forEach(function (campo) {
            const evento = campo.tagName === 'SELECT' ? 'change' : 'input';

            campo.addEventListener(evento, function () {
                campo.classList.remove('input-error');
                const error = document.getElementById('error-' + campo.id);
                if (error) {
                    error.textContent = '';
                }
            });
        });

        // ─── Guardar (AJAX) ─────────────────────────────────────────
        btnGuardar.addEventListener('click', function () {

            limpiarErrores();

            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<i class="ri-loader-4-line"></i> Guardando...';

            const datos = {};
            camposEditables.forEach(function (campo) {
                datos[campo.name] = campo.type === 'checkbox' ? (campo.checked ? 1 : 0) : campo.value;
            });

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // NOTA: URL de prueba, reemplazar cuando esté la ruta real:
            fetch('{{ route('materias.update', $materia->id_materia) }}', {
                method: 'PUT',
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

                    guardarValoresOriginales();
                    deshabilitarEdicion();
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

                        mostrarAlerta('Hay campos con errores.', 'error');

                    } else {

                        mostrarAlerta(data.message ?? 'Ocurrió un error.', 'error');

                    }

                }

            })
            .catch(function () {
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = '<i class="ri-save-line"></i> Guardar';
                mostrarAlerta('Error de conexión.', 'error');
            });

        });

        // ─── Quitar profesor (con confirmación) ───────────────────────
        document.querySelectorAll('.btn-quitar-profesor').forEach(function (boton) {
            boton.addEventListener('click', function (e) {
                e.preventDefault();

                const nombreProfesor = boton.dataset.profesor || 'este profesor';
                const idDocente = boton.dataset.docente;

                const confirmado = confirm(
                    '¿Seguro que querés quitar a ' + nombreProfesor + ' de esta materia?'
                );

                if (!confirmado) {
                    return;
                }

                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                fetch('{{ route('materias.profesor.destroy', [$materia->id_materia, '__DOCENTE__']) }}'.replace('__DOCENTE__', idDocente), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (data.success) {
                        boton.closest('tr').remove();
                        mostrarAlerta('✓ ' + data.message, 'success');
                    } else {
                        mostrarAlerta(data.message || 'Ocurrió un error.', 'error');
                    }
                })
                .catch(function () {
                    mostrarAlerta('Error de conexión.', 'error');
                });
            });
        });

        // ─── DataTable de profesores asignados ────────────────────────
        if (document.getElementById('tablaProfesoresAsignados') && window.jQuery) {
            $('#tablaProfesoresAsignados').DataTable({
                dom: 't',
                paging: false,
                order: [[0, 'asc']],
                language: {
                    zeroRecords: 'No se encontraron resultados',
                }
            });
        }

    });
    </script>

</body>
</html>
