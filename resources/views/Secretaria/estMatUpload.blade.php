<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar alumno a materia</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('fonts/remixicon/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/sideBar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filtro.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tablas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/botones.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/carreraSec.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/materiaPerfil.css') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/Filtro.js'])
</head>

<body>
    <button class="mobile-menu-btn" id="menu-btn">
        <i class="ri-menu-line"></i>
    </button>

    @include('partials.sidebar')

    <div class="main">

        <button class="btn-modificar" type="button" onclick="window.location='{{ route('materias.show', $materia->id_materia) }}'">
            <i class="ri-arrow-left-line"></i>Volver
        </button>

        <br>

        <!-- HEADER -->
        <div class="top-bar">
            <h1>Agregar alumno a {{ $materia->nombre }}</h1>
        </div>

        <div id="alert-ajax" class="alert" style="display:none;"></div>

        <!-- FILTROS -->
        <div class="card-container">
            <div class="form-grid">
                <input
                    type="text"
                    id="filtro-nombre"
                    placeholder="Buscar alumno"
                >
            </div>
        </div>

        <!-- TABLA DISPONIBLES -->
        <div class="table-container">
            <table class="cursada-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Apellido y nombre</th>
                        <th>Documento</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody id="tabla-disponibles">
                    @foreach ($disponibles as $estudiante)
                    <tr data-id="{{ $estudiante->id_usuario }}">
                        <td>{{ $estudiante->id_usuario }}</td>
                        <td>{{ $estudiante->usuario->apellido }}, {{ $estudiante->usuario->nombre }}</td>
                        <td>{{ optional($estudiante->usuario->dnis->first())->dni ?? '—' }}</td>
                        <td>
                            <button type="button" class="btn-table ri-add-circle-line btn-agregar"></button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="top-bar">
            <h1>Alumnos a agregar</h1>
        </div>

        <!-- TABLA AGREGADOS -->
        <div class="table-container">
            <table class="cursada-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Apellido y nombre</th>
                        <th>Documento</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody id="tabla-agregados">
                    @foreach ($agregados as $inscripcion)
                    <tr data-id="{{ $inscripcion->id_usuario }}">
                        <td>{{ $inscripcion->id_usuario }}</td>
                        <td>{{ $inscripcion->estudiante->usuario->apellido }}, {{ $inscripcion->estudiante->usuario->nombre }}</td>
                        <td>{{ optional($inscripcion->estudiante->usuario->dnis->first())->dni ?? '—' }}</td>
                        <td>
                            <button type="button" class="btn-table ri-delete-bin-6-line btn-quitar" style="background-color: rgb(228, 59, 59);"></button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="form-actions">
            <button type="button" class="btn-primary" id="btn-guardar">
                <i class="ri-save-line"></i> Guardar
            </button>
        </div>
    </div>

    @include('partials.footer')

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tbodyDisponibles = document.getElementById('tabla-disponibles');
            const tbodyAgregados = document.getElementById('tabla-agregados');
            const btnGuardar = document.getElementById('btn-guardar');
            const filtroNombre = document.getElementById('filtro-nombre');
            const alertAjax = document.getElementById('alert-ajax');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            let timeoutBusqueda = null;

            function mostrarAlerta(mensaje, tipo) {
                alertAjax.textContent = mensaje;
                alertAjax.className = 'alert alert-' + tipo;
                alertAjax.style.display = 'block';
                alertAjax.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

                if (tipo === 'success') {
                    setTimeout(function () { alertAjax.style.display = 'none'; }, 5000);
                }
            }

            function actualizarBotonGuardar() {
                btnGuardar.style.display = tbodyAgregados.querySelectorAll('tr').length > 0 ? 'inline-block' : 'none';
            }

            function idsYaAgregados() {
                return Array.from(tbodyAgregados.querySelectorAll('tr')).map(fila => fila.dataset.id);
            }

            // 1. Mover de arriba a abajo
            tbodyDisponibles.addEventListener('click', (e) => {
                const btn = e.target.closest('.btn-agregar');
                if (!btn) return;

                const fila = btn.closest('tr');

                btn.classList.remove('ri-add-circle-line', 'btn-agregar');
                btn.classList.add('ri-delete-bin-6-line', 'btn-quitar');
                btn.style.backgroundColor = 'rgb(228, 59, 59)';
                btn.textContent = 'Quitar';

                tbodyAgregados.appendChild(fila);
                actualizarBotonGuardar();
            });

            // 2. Mover de abajo a arriba
            tbodyAgregados.addEventListener('click', (e) => {
                const btn = e.target.closest('.btn-quitar');
                if (!btn) return;

                const fila = btn.closest('tr');

                btn.classList.remove('ri-delete-bin-6-line', 'btn-quitar');
                btn.classList.add('ri-add-circle-line', 'btn-agregar');
                btn.style.backgroundColor = '';
                btn.textContent = 'Agregar';

                tbodyDisponibles.appendChild(fila);
                actualizarBotonGuardar();
            });

            // 3. Buscar alumno (debounce) sin perder los que ya están agregados
            filtroNombre.addEventListener('input', () => {
                clearTimeout(timeoutBusqueda);

                timeoutBusqueda = setTimeout(() => {
                    const url = new URL('{{ route('materias.alumnosDisponibles', $materia->id_materia) }}');
                    url.searchParams.set('nombre', filtroNombre.value);

                    fetch(url, { headers: { 'Accept': 'application/json' } })
                        .then(r => r.json())
                        .then(({ data }) => {
                            const agregados = idsYaAgregados();

                            tbodyDisponibles.innerHTML = '';

                            data
                                .filter(alumno => !agregados.includes(String(alumno.id_usuario)))
                                .forEach(alumno => {
                                    const fila = document.createElement('tr');
                                    fila.dataset.id = alumno.id_usuario;
                                    fila.innerHTML = `
                                        <td>${alumno.id_usuario}</td>
                                        <td>${alumno.nombre}</td>
                                        <td>${alumno.dni}</td>
                                        <td><button type="button" class="btn-table ri-add-circle-line btn-agregar">Agregar</button></td>
                                    `;
                                    tbodyDisponibles.appendChild(fila);
                                });
                        })
                        .catch(() => mostrarAlerta('Error de conexión al buscar alumnos.', 'error'));
                }, 350);
            });

            // 4. Guardar (sync de inscripciones)
            btnGuardar.addEventListener('click', () => {
                btnGuardar.disabled = true;
                btnGuardar.innerHTML = '<i class="ri-loader-4-line"></i> Guardando...';

                fetch('{{ route('materias.agregarAlumno.store', $materia->id_materia) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ alumnos: idsYaAgregados() }),
                })
                .then(r => r.json())
                .then((data) => {
                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = '<i class="ri-save-line"></i> Guardar';

                    if (data.success) {
                        mostrarAlerta('✓ ' + data.message, 'success');
                    } else {
                        mostrarAlerta(data.message || 'Ocurrió un error.', 'error');
                    }
                })
                .catch(() => {
                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = '<i class="ri-save-line"></i> Guardar';
                    mostrarAlerta('Error de conexión. Intentá de nuevo.', 'error');
                });
            });

            actualizarBotonGuardar();
        });
    </script>

</body>
</html>
