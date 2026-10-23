<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Alumnos - Plan de estudio</title>
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
    <link rel="stylesheet" href="{{ asset('css/Secretaria/profesoresAgregar.css') }}">
</head>

<body>
    <button class="mobile-menu-btn" id="menu-btn">
        <i class="ri-menu-line"></i>
    </button>

    @include('partials.sidebar')

    <div class="main">
        <button class="btn-modificar" type="button" onclick="window.location='{{ route('planDeEstudio.index') }}'">
            <i class="ri-arrow-left-line"></i>Volver
        </button>

        <div class="form-container">
            <div class="perfil-header">
                <div style="display:flex; align-items:center; gap:12px;">
                    <h1>Alumnos en {{ $planResolucion->plan->titulo ?? $carrera->nombre }}</h1>
                </div>
            </div>

            <form class="alumno-form" id="form-alumno" method="GET" action="{{ route('planAlumnos', $carrera->id_carrera) }}">
                <div class="form-grid">
                    <label>Materia</label>
                    <div class="input-group">
                        <select name="materia" onchange="this.form.submit()">
                            <option value="">Todas</option>
                            @foreach($materias as $materia)
                                <option value="{{ $materia->id_materia }}" @selected(request('materia') == $materia->id_materia)>
                                    {{ $materia->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <label>Año</label>
                    <div class="input-group">
                        <select name="anio" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            @foreach($materias->pluck('anio')->unique()->sort() as $anio)
                                <option value="{{ $anio }}" @selected(request('anio') == $anio)>{{ $anio }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table id="tablaAlumnos" class="cursada-table">
                <thead>
                    <tr>
                        <th>Codigo</th>
                        <th>Apellido y Nombre</th>
                        <th>Documento</th>
                        <th>Promedio</th>
                        <th>Perfil</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($alumnos as $alumno)
                        <tr>
                            <td>{{ str_pad($alumno->id_usuario, 4, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $alumno->usuario->apellido }}, {{ $alumno->usuario->nombre }}</td>
                            <td>{{ $alumno->usuario->dnis->first()->dni ?? '—' }}</td>
                            <td>—</td> {{-- promedio: pendiente definir cálculo --}}
                            <td>
                                <button class="btn-primary" onclick="window.location.href='{{ route('alumnos.show', $alumno->id_usuario) }}'">
                                    Ver
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No hay alumnos cargados para esta carrera.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('partials.footer')
</body>
</html>