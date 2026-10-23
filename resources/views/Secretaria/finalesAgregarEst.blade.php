<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar alumno a final</title>
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

    <div class="main" id="app-agregar-est" data-mesa="{{ $mesa->id_mesa }}" data-url="{{ route('finales.estudiantes.store', $mesa->id_mesa) }}">

        <button class="btn-modificar" type="button" onclick="window.location='{{ route('finalPerfil', $mesa->id_mesa) }}'">
            <i class="ri-arrow-left-line"></i>Volver
        </button>

        <br>

        <!-- HEADER -->
        <div class="top-bar">
            <h1>Agregar alumno a final</h1>
        </div>

        <p class="help" style="margin-bottom:12px;">
            {{ $mesa->materia }} - {{ $mesa->fecha }}
        </p>

        <div id="alert-ajax" class="alert" style="display:none;"></div>

        <!-- FILTROS -->
        <div class="card-container">
            <div class="form-grid buscar-alumno-final">
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
                    @forelse ($estudiantesDisponibles as $estudiante)
                        <tr data-id="{{ $estudiante->id_usuario }}">
                            <td>{{ $estudiante->id_usuario }}</td>
                            <td>{{ $estudiante->nombre }}</td>
                            <td>{{ $estudiante->documento }}</td>
                            <td>
                                <button type="button" class="btn-table ri-add-circle-line btn-agregar"></button>
                            </td>
                        </tr>
                    @empty
                        <tr id="fila-sin-disponibles">
                            <td colspan="4">No hay alumnos disponibles para agregar.</td>
                        </tr>
                    @endforelse
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
                    <tr id="fila-sin-agregados">
                        <td colspan="4">Todavía no agregaste ningún alumno.</td>
                    </tr>
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
            const app = document.getElementById('app-agregar-est');
            if (!app) return;

            const url = app.dataset.url;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const alertAjax = document.getElementById('alert-ajax');
            const filtroNombre = document.getElementById('filtro-nombre');
            const tablaDisponibles = document.getElementById('tabla-disponibles');
            const tablaAgregados = document.getElementById('tabla-agregados');
            const btnGuardar = document.getElementById('btn-guardar');

            function mostrarAlerta(mensaje, tipo) {
                alertAjax.textContent = mensaje;
                alertAjax.className = 'alert alert-' + tipo;
                alertAjax.style.display = 'block';
                alertAjax.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

                if (tipo === 'success') {
                    setTimeout(() => { alertAjax.style.display = 'none'; }, 5000);
                }
            }

            function actualizarPlaceholders() {
                const filaSinDisponibles = document.getElementById('fila-sin-disponibles');
                const hayDisponibles = tablaDisponibles.querySelectorAll('tr[data-id]').length > 0;
                if (!hayDisponibles && !filaSinDisponibles) {
                    const tr = document.createElement('tr');
                    tr.id = 'fila-sin-disponibles';
                    tr.innerHTML = '<td colspan="4">No hay alumnos disponibles para agregar.</td>';
                    tablaDisponibles.appendChild(tr);
                } else if (hayDisponibles && filaSinDisponibles) {
                    filaSinDisponibles.remove();
                }

                const filaSinAgregados = document.getElementById('fila-sin-agregados');
                const hayAgregados = tablaAgregados.querySelectorAll('tr[data-id]').length > 0;
                if (!hayAgregados && !filaSinAgregados) {
                    const tr = document.createElement('tr');
                    tr.id = 'fila-sin-agregados';
                    tr.innerHTML = '<td colspan="4">Todavía no agregaste ningún alumno.</td>';
                    tablaAgregados.appendChild(tr);
                } else if (hayAgregados && filaSinAgregados) {
                    filaSinAgregados.remove();
                }
            }

            // ─── Buscar (filtro cliente sobre la tabla de disponibles) ────
            filtroNombre.addEventListener('input', () => {
                const texto = filtroNombre.value.trim().toLowerCase();
                tablaDisponibles.querySelectorAll('tr[data-id]').forEach((fila) => {
                    const nombre = fila.children[1].textContent.toLowerCase();
                    fila.style.display = nombre.includes(texto) ? '' : 'none';
                });
            });

            // ─── Mover de "Disponibles" a "A agregar" ──────────────────────
            tablaDisponibles.addEventListener('click', (e) => {
                const btn = e.target.closest('.btn-agregar');
                if (!btn) return;

                const fila = btn.closest('tr');
                const btnQuitar = document.createElement('button');
                btnQuitar.type = 'button';
                btnQuitar.className = 'btn-table ri-delete-bin-6-line btn-quitar';
                btnQuitar.style.backgroundColor = 'rgb(228, 59, 59)';

                fila.querySelector('td:last-child').innerHTML = '';
                fila.querySelector('td:last-child').appendChild(btnQuitar);
                fila.style.display = '';

                tablaAgregados.appendChild(fila);
                actualizarPlaceholders();
            });

            // ─── Mover de "A agregar" de vuelta a "Disponibles" ────────────
            tablaAgregados.addEventListener('click', (e) => {
                const btn = e.target.closest('.btn-quitar');
                if (!btn) return;

                const fila = btn.closest('tr');
                const btnAgregar = document.createElement('button');
                btnAgregar.type = 'button';
                btnAgregar.className = 'btn-table ri-add-circle-line btn-agregar';

                fila.querySelector('td:last-child').innerHTML = '';
                fila.querySelector('td:last-child').appendChild(btnAgregar);

                tablaDisponibles.appendChild(fila);
                actualizarPlaceholders();
            });

            // ─── Guardar ────────────────────────────────────────────────
            btnGuardar.addEventListener('click', () => {
                const ids = Array.from(tablaAgregados.querySelectorAll('tr[data-id]'))
                    .map((fila) => fila.dataset.id);

                if (ids.length === 0) {
                    mostrarAlerta('No agregaste ningún alumno todavía.', 'error');
                    return;
                }

                btnGuardar.disabled = true;
                btnGuardar.innerHTML = '<i class="ri-loader-4-line"></i> Guardando...';

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ estudiantes: ids }),
                })
                .then((r) => r.json().then((data) => ({ status: r.status, data })))
                .then(({ status, data }) => {
                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = '<i class="ri-save-line"></i> Guardar';

                    if (status >= 200 && status < 300) {
                        mostrarAlerta('✓ Alumnos agregados correctamente.', 'success');
                        setTimeout(() => {
                            window.location.href = '{{ route('finalPerfil', $mesa->id_mesa) }}';
                        }, 800);
                    } else {
                        mostrarAlerta(data.message || 'Ocurrió un error al guardar.', 'error');
                    }
                })
                .catch(() => {
                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = '<i class="ri-save-line"></i> Guardar';
                    mostrarAlerta('Error de conexión. Intentá de nuevo.', 'error');
                });
            });
        });
    </script>

</body>
</html>