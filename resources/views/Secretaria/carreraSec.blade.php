<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carreras</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="{{ asset('fonts/remixicon/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/sideBar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filtro.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tablas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/botones.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/carreraSec.css') }}">

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
            <h1>Carreras</h1>
            <button class="btn-primary" onclick="window.location.href='{{ route('carreras.create') }}'">Agregar Carrera</button>
        </div>

        <!-- BOTONES DE ACCIÓN SECUNDARIOS -->
        <div class="action-buttons">
            <button class="btn-primary" onclick="window.location.href='{{ route('planDeEstudio.index') }}'">Plan de estudio</button>
        </div>

        <!-- FILTROS — solo cliente, sin form GET -->
        <div class="card-container">
            <div class="form-grid">

                <input
                    type="text"
                    id="filtro-nombre"
                    placeholder="Buscar por nombre o título..."
                >

            </div>
        </div>

        <div class="table-container">

            @if($carreras->count() > 0)

                <table id="tablaCarreras" class="cursada-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Título</th>
                            <th>Plan de estudios</th>
                            <th>Carrera</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($carreras as $carrera)
                            <tr>
                                <td>{{ $carrera->nombre }}</td>
                                <td>{{ $carrera->titulo }}</td>
                                <td>
                                    @if($carrera->planResolucion)
                                        {{ $carrera->planResolucion->resolucion->descripcion ?? '—' }} — {{ $carrera->planResolucion->plan->titulo ?? '—' }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <button
                                        class="btn-primary"
                                        onclick="window.location.href='{{ route('carreras.show', $carrera->id_carrera) }}'">
                                        Ver
                                    </button>
                                    
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

            @else

                <div class="table-placeholder">
                    <i class="ri-booklet-line"></i>
                    <p>No hay carreras cargadas. Usá <strong>Agregar</strong> para crear la primera.</p>
                </div>

            @endif

        </div>

    </div>

    @include('partials.footer')

    <div class="overlay" id="overlay"></div>

    <!-- recargar la pagina al volver para atras, por si hay modificaciones -->
    <script>
        (function () {
            window.addEventListener('pageshow', function (event) {
                if (event.persisted) {
                    window.location.reload();
                }

                const perfEntries = performance.getEntriesByType("navigation");

                if (perfEntries.length > 0 && perfEntries[0].type === "back_forward") {
                    window.location.reload();
                }
            });
        })();
    </script>

</body>
</html>