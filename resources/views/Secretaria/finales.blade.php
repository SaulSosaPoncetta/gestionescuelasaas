<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Finales</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('fonts/remixicon/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/sideBar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filtro.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tablas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/botones.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/alumnosSec.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/materiaPerfil.css') }}">

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
            <h1>Fechas de finales</h1>
            <div>
                <button class="btn-primary" onclick="window.location.href='{{ route('finalesAgregarFecha') }}'">Agregar fecha</button>
                <button class="btn-primary" onclick="window.location.href='{{ route('finalesHistorial') }}'">Informes</button>
            </div>
        </div>

        <!-- FILTROS — recargan la página con querystring; el backend
             (MesaExamenService::getAll) ya soporta id_carrera y anio -->
        <form method="GET" action="{{ route('finalesSec') }}">
            <div class="card-container">
                <div class="form-grid">
                    <label>Carrera</label>
                    <select name="id_carrera" onchange="this.form.submit()">
                        <option value="" {{ empty($filters['id_carrera']) ? 'selected' : '' }}>Todas</option>
                        @foreach ($carreras as $carrera)
                            <option
                                value="{{ $carrera->id_carrera }}"
                                {{ (string) ($filters['id_carrera'] ?? '') === (string) $carrera->id_carrera ? 'selected' : '' }}
                            >
                                {{ $carrera->nombre }}
                            </option>
                        @endforeach
                    </select>

                    <label>Año</label>
                    <select name="anio" onchange="this.form.submit()">
                        <option value="" {{ empty($filters['anio']) ? 'selected' : '' }}>Todos</option>
                        @foreach ($anios as $anio)
                            <option
                                value="{{ $anio }}"
                                {{ (string) ($filters['anio'] ?? '') === (string) $anio ? 'selected' : '' }}
                            >
                                {{ $anio }}°
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>

        <div class="table-container">

            <table class="cursada-table">
                <thead>
                    <tr>
                        <th>Fecha y hora</th>
                        <th>Espacio</th>
                        <th>Carrera</th>
                        <th>Año</th>
                        <th>Profesores</th>
                        <th>Perfil</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($mesas as $mesa)
                        <tr>
                            <td>{{ $mesa->fecha_hora }}</td>
                            <td>{{ $mesa->espacio }}</td>
                            <td>{{ $mesa->carrera }}</td>
                            <td>{{ $mesa->anio }}</td>
                            <td>{{ $mesa->profesores }}</td>
                            <td>
                                <button
                                    class="btn-primary"
                                    onclick="window.location.href='{{ route('finalPerfil', $mesa->id_mesa) }}'">
                                    Ver
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">No hay mesas de final cargadas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

        </div>

    </div>

    @include('partials.footer')

</body>
</html>