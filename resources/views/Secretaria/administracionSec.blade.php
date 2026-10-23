<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Buscar Administrador</title>
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
            <h1>Buscar Administrador</h1>
            <button class="btn-primary" onclick="window.location.href='{{ route('administradores.create') }}'">Agregar Administrador</button>
        </div>

        <!-- FILTROS — solo cliente, sin form GET -->
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

        <div class="table-container">

            <table id="tablaAdministradores" class="cursada-table">
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
                    @foreach($administradores as $administrador)
                        <tr>
                            <td>
                                {{ $administrador->usuario->nombre }} {{ $administrador->usuario->apellido }}
                            </td>
                            <td>
                                {{ $administrador->usuario->dnis->first()->dni ?? 'Sin DNI' }}
                            </td>
                            <td>
                                {{ $administrador->usuario->estado ?? '—' }}
                            </td>
                            <td>
                                {{ $administrador->usuario->telefonos->first()->telefono ?? 'Sin teléfono' }}
                            </td>

                            <td>
                                <button
                                    class="btn-primary"
                                    onclick="window.location.href='{{ route('administradores.show', $administrador->id_usuario) }}'">
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

    <script src="{{ asset('js/Secretaria/perfilEditable.js') }}"></script>

</body>
</html>