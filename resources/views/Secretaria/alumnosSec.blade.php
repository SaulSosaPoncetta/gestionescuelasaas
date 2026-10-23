<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Buscar Alumno</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('fonts/remixicon/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/sideBar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filtro.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tablas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/botones.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/alumnosSec.css') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/Filtro.js'])
</head>

<body>
    <button class="mobile-menu-btn" id="menu-btn">
        <i class="ri-menu-line"></i>
    </button>

    @include('partials.sidebar')

    <div class="main">

        <!-- HEADER -->
        <div class="top-bar">
            <h1>Buscar Alumno</h1>
            <button class="btn-primary" onclick="window.location.href='{{ route('alumnoAgregar') }}'">
                Agregar Alumno
            </button>
        </div>

        <!-- FILTROS — solo cliente, sin form GET (igual que administradores) -->
        <div class="card-container">
            <div class="form-grid">

                <input
                    type="text"
                    id="filtro-nombre"
                    placeholder="Buscar por nombre, apellido o DNI..."
                >

                <select id="filtro-estado">
                    <option value="">Todos</option>
                    <option value="Activo">Activo</option>
                    <option value="Inactivo">Inactivo</option>
                </select>

            </div>
        </div>

        <!-- TABLA — DataTable del lado del cliente -->
        <div class="table-container">
            <table id="tablaAlumnos" class="cursada-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>DNI</th>
                        <th>Estado</th>
                        <th>Teléfono</th>
                        <th>Perfil</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($estudiantes as $est)
                        <tr>
                            <td>{{ $est->usuario->apellido }}, {{ $est->usuario->nombre }}</td>
                            <td>{{ $est->usuario->dnis->first()?->dni ?? 'Sin DNI' }}</td>
                            <td>{{ $est->usuario->estado ?? '—' }}</td>
                            <td>{{ $est->usuario->telefonos->first()?->telefono ?? 'Sin teléfono' }}</td>
                            <td>
                                <button
                                    class="btn-primary"
                                    onclick="window.location.href='{{ route('alumnos.show', $est->id_usuario) }}'">
                                    Ver
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>

    @include('partials.footer')

    <script>
        (function () {
            window.addEventListener('pageshow', function (event) {
                if (event.persisted) {
                    window.location.reload();
                }
                const perfEntries = performance.getEntriesByType('navigation');
                if (perfEntries.length > 0 && perfEntries[0].type === 'back_forward') {
                    window.location.reload();
                }
            });
        })();
    </script>

</body>
</html>
