<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Plan de estudio</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('fonts/remixicon/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/sideBar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filtro.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tablas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/botones.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/planEstudio.css') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/Filtro.js'])
</head>

<body>
    <button class="mobile-menu-btn" id="menu-btn">
        <i class="ri-menu-line"></i>
    </button>

    @include('partials.sidebar')

    <div class="main">

        <!-- FILA SUPERIOR: Volver + Agregar Plan a la misma altura -->
        <div class="top-actions">
            <button class="btn-modificar" type="button" onclick="window.location='{{ route('carreras.index') }}'">
                <i class="ri-arrow-left-line"></i>Volver
            </button>
            <button class="btn-primary" type="button" onclick="window.location.href='{{ route('planEstudio.create') }}'">
                Agregar Plan
            </button>
        </div>

        <h1 class="page-title">Carreras - Plan de estudio</h1>

        <!-- FILTROS — solo cliente, sin form GET -->
        <div class="card-container">
            <div class="form-grid">
                <input
                    type="text"
                    id="filtro-nombre"
                    placeholder="Buscar por plan, resolución o carrera"
                >
            </div>
        </div>

        <div class="table-container">
            <table id="tablaPlanEstudio" class="cursada-table">
                <thead>
                    <tr>
                        <th>Plan de estudio</th>
                        <th>Carrera</th>
                        <th>Años</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($planResoluciones as $pr)
                        <tr>
                            <td class="celda-titulo">
                                {{ $pr->plan->titulo }} - {{ $pr->resolucion->descripcion }}
                            </td>
                            <td>{{ $pr->carrera->nombre ?? '—' }}</td>
                            <td>{{ $pr->total_horas ?? '—' }} Años</td>
                            <td>
                                <div class="acciones-fila">
                                    <button class="btn-primary" onclick="window.location.href='{{ route('planEspacios', $pr->id_carrera) }}'">Espacios</button>
                                    <button class="btn-primary" onclick="window.location.href='{{ route('planAlumnos', $pr->id_carrera) }}'">Alumnos</button>                                    <button
                                        class="btn-modificar"
                                        type="button"
                                        onclick="window.location.href='{{ route('planEstudio.edit', $pr->id) }}'">
                                        <i class="ri-edit-line"></i> Modificar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No hay planes de estudio cargados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('partials.footer')
</body>
</html>