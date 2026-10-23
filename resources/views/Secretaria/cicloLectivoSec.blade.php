<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Buscar profesor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="{{ asset('fonts/remixicon/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/sideBar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filtro.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tablas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/botones.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/profesoresSec.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/alumnosSec.css') }}">
    @vite(['resources/css/app.css', 'resources/js/Filtro.js'])
</head>

<body>
<button class="mobile-menu-btn" id="menu-btn">
    <i class="ri-menu-line"></i>
</button>
@include('partials.sidebar')

<div class="main">

    <!-- HEADER -->
    <div class="top-bar">
        <h1>Ciclo Lectivo</h1>
        <button class="btn-primary" onclick="window.location.href='{{ route('cicloLectivoAgregar') }}'">Agregar Ciclo Lectivo</button>
    </div>

    <!-- FILTROS -->
    <form {{--method="GET" action="{{ route('profesoresSec') }}"--}}>
        <div class="card-container">
            <div class="form-grid">
                <input type="text" id="buscador-ciclo" placeholder="Buscar por año" name="buscar_año">
            </div>
        </div>
    </form>

    <div class="table-container">

        <table id="tablaCiclos" class="cursada-table">
            <thead>
                <tr>
                    <th>Año</th>
                    <th>Periodo</th>
                    <th>Ver</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ciclos as $ciclo)
                    <tr>
                        <td>
                            {{ $ciclo->anio }}
                        </td>

                        <td>
                            {{ $ciclo->fecha_inicio->format('d/m/Y') }}
                            -
                            {{ $ciclo->fecha_fin->format('d/m/Y') }}
                        </td>

                        <td>
                            <a href="{{ route('cicloLectivoPerfil', $ciclo->id_ciclo) }}">
                                <button class="btn-primary" type="button">
                                    Ver
                                </button>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>      
        </table>

    </div>

    @include('partials.footer')

    <!-- recargar la pagina al volver para atras si estás en el perfil, por si hay modificaciones -->
    {{-- <script>
        (function () {
            window.addEventListener('pageshow', function(event){
                if (event.persisted){
                    window.location.reload();
                }

                const perfEntries = performance.getEntriesByType("navigation");

                if (perfEntries.length > 0 && perfEntries[0].type === "back_forward"){
                    window.location.reload();
                }
            });
        })();
    </script> --}}

</body>
</html>
