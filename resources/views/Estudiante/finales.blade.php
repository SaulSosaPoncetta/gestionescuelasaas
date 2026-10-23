<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Finales</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('fonts/remixicon/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/sideBar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tablas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/botones.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/materiaPerfil.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/alumnosSec.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Estudiante/finalesEst.css') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <button class="mobile-menu-btn" id="menu-btn">
        <i class="ri-menu-line"></i>
    </button>

    @include('partials.sidebar')

    <div class="main">
        <!-- HEADER -->
        <div class="top-bar">
            <h1>Finales</h1>
            <button class="btn-primary" type="button" onclick="window.location.href='{{ route('finalesInscripcion') }}'">
                Inscripción
            </button>
        </div>

        @if ($carreras->isEmpty())

            <div class="empty-state">
                <i class="ri-book-line"></i>
                <p>No estás inscripto en ninguna carrera.</p>
            </div>

        @else

        <div class="card-container">
            <div class="form-grid finalesEst-grid">
                <label>Carrera</label>
                <select id="select-carrera-finales">
                    @foreach ($carreras as $carrera)
                        <option value="{{ $carrera->id_carrera }}">{{ $carrera->nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- PESTAÑAS -->
        <div class="finales-tabs">
            <button class="btn-tab activo" type="button" data-tabla="inscripto">Inscripto</button>
            <button class="btn-tab" type="button" data-tabla="aprobado">Aprobado</button>
            <button class="btn-tab" type="button" data-tabla="no-aprobado">No aprobado</button>
            <button class="btn-tab" type="button" data-tabla="bajas">Bajas</button>
        </div>

        <div class="table-container" id="finales-print-area">

            @php
                // data-tabla usa guion (no-aprobado); la clave del array del
                // controller usa guion bajo (no_aprobado). Mapeo acá nomás.
                $tabs = [
                    'inscripto'   => 'inscripto',
                    'aprobado'    => 'aprobado',
                    'no_aprobado' => 'no-aprobado',
                    'bajas'       => 'bajas',
                ];
            @endphp

            @foreach ($carreras as $indexCarrera => $carrera)
                @foreach ($tabs as $claveController => $dataTabla)

                    @php
                        $filas = $finalesPorCarrera[$carrera->id_carrera][$claveController] ?? collect();
                        $esPrimeraVisible = $indexCarrera === 0 && $dataTabla === 'inscripto';
                    @endphp

                    <table
                        class="cursada-table tabla-finales"
                        data-carrera="{{ $carrera->id_carrera }}"
                        data-tabla="{{ $dataTabla }}"
                        @if (!$esPrimeraVisible) style="display:none;" @endif
                    >
                        <thead>
                            <tr>
                                <th>Fecha y Hora</th>
                                <th>Espacio Curricular</th>
                                <th>Año</th>
                                <th>Modalidad</th>
                                <th>Nota</th>
                                @if ($dataTabla === 'inscripto')
                                    <th>Acción</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($filas as $fila)
                                <tr>
                                    <td>{{ $fila->fecha_hora }}</td>
                                    <td>{{ $fila->espacio }}</td>
                                    <td>{{ $fila->anio }}°</td>
                                    <td>{{ $fila->modalidad }}</td>
                                    <td>{{ $fila->nota }}</td>
                                    @if ($dataTabla === 'inscripto')
                                        <td>
                                            <button
                                                type="button"
                                                class="btn-table ri-close-circle-line btn-baja-final"
                                                data-id-mesa="{{ $fila->id_mesa }}"
                                                title="Darme de baja"
                                            ></button>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $dataTabla === 'inscripto' ? 6 : 5 }}" class="sin-resultados">Sin registros</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                @endforeach
            @endforeach

        </div>

        <button type="button" class="btn-table ri-printer-line" id="btn-imprimir" title="Imprimir tabla"></button>

        @endif

    </div>

    @include('partials.footer')

    <script>
        window.rutaBajaFinal = '{{ route('finalesEst.baja') }}';
    </script>
    <script src="{{ asset('js/Estudiante/finalesEst.js') }}"></script>

</body>
</html>