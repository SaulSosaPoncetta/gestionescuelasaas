<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Plan de estudio - espacios</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('fonts/remixicon/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/sideBar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filtro.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tablas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/botones.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/planEstudio.css') }}">

    {{-- Reusable en cualquier vista con botón Imprimir --}}
    <link rel="stylesheet" href="{{ asset('css/print.css') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/Filtro.js'])
</head>

<body>
    <button class="mobile-menu-btn no-print" id="menu-btn">
        <i class="ri-menu-line"></i>
    </button>

    @include('partials.sidebar')
    {{-- No hace falta marcar el sidebar: print.css oculta TODO por default,
         y solo hace visible lo que esté dentro de .printable-area --}}

    <div class="main">

        @php
            $rol = auth()->user()->id_rol;
        @endphp

        <div class="no-print">
            @if($rol == 1)
                <button class="btn-modificar" type="button" onclick="window.location='{{ route('planEstudio.index') }}'">
                    <i class="ri-arrow-left-line"></i> Volver
                </button>
            @elseif($rol == 3)
                @if($planResolucion)
                    <button class="btn-modificar" type="button" onclick="window.location.href='{{ route('planEstudio.show', $planResolucion->id) }}'">
                        <i class="ri-arrow-left-line"></i> Volver
                    </button>
                @else
                    <button class="btn-modificar" type="button" onclick="window.history.back()">
                        <i class="ri-arrow-left-line"></i> Volver
                    </button>
                @endif
            @endif
        </div>

        {{-- Todo lo que va DENTRO de este div es lo único que se imprime --}}
        <div class="printable-area">

            <h1 class="page-title">Plan de estudios - Espacios curriculares — {{ $carrera->nombre }}</h1>

            <!-- FILTROS — solo cliente, sin form GET -->
            <div class="card-container no-print">
                <div class="form-grid">
                    <input
                        type="text"
                        id="filtro-nombre"
                        placeholder="Buscar materia"
                    >
                </div>
            </div>

            <div class="table-container">
                <table id="tablaEspacios" class="cursada-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Año</th>
                            <th>Nombre</th>
                            <th>Correlativas</th>
                            <th>Acreditación</th>
                            <th class="no-print">Perfil</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($materias as $materia)
                            <tr data-id-materia="{{ $materia->id_materia }}">
                                <td>{{ $materia->codigo }}</td>
                                <td>{{ $materia->anio }}°</td>
                                <td class="celda-titulo">{{ $materia->nombre }}</td>
                                <td>{{ $materia->correlativas_texto ?: '—' }}</td>
                                <td>{{ $materia->acreditacion }}</td>
                                <td class="no-print">
                                    @if($rol == 1)
                                        <button class="btn-primary" onclick="window.location.href='{{ route('materias.show', $materia->id_materia) }}'">Ver</button>
                                    @elseif($rol == 3)
                                        <button class="btn-primary" onclick="window.location.href='{{ route('materias.show.estudiante', $materia->id_materia) }}'">Ver</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
        {{-- fin .printable-area --}}

        <button class="btn-primary no-print" type="button" onclick="window.print()">
            <i class="ri-printer-fill"></i> Imprimir
        </button>
    </div>

    @include('partials.footer')
</body>
</html>