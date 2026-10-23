<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de finales</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="{{ asset('fonts/remixicon/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/sideBar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filtro.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tablas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/botones.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/materiaPerfil.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/cursadaPerfil.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/cargarNota.css') }}">

    {{-- Reusable en cualquier vista con botón Imprimir --}}
    <link rel="stylesheet" href="{{ asset('css/print.css') }}">

    @vite(['resources/css/app.css'])
</head>

<body>
<button class="mobile-menu-btn no-print" id="menu-btn">
    <i class="ri-menu-line"></i>
</button>
    @include('partials.sidebar')

    <div class="main">
        <button class="btn-modificar no-print" type="button" onclick="window.location='{{ route('finalesSec') }}'">
            <i class="ri-arrow-left-line"></i>Volver
        </button>
        <br>

        {{-- Todo lo que va DENTRO de este div es lo único que se imprime --}}
        <div class="printable-area">

            <h1>Historial de finales</h1>

            <div class="form-grid no-print" style="max-width:600px; margin:24px 0;">

                <label for="filtro-carrera">Carrera:</label>
                <div class="input-group">
                    <select id="filtro-carrera">
                        <option value="" disabled selected hidden>Seleccionar carrera</option>
                        @foreach ($carreras as $carrera)
                            <option value="{{ $carrera->id_carrera }}">{{ $carrera->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <label for="filtro-llamado">Llamado:</label>
                <div class="input-group">
                    <select id="filtro-llamado">
                        <option value="" disabled selected hidden>Seleccionar llamado</option>
                        @foreach ($llamados as $llamado)
                            <option value="{{ $llamado->id_llamado }}">{{ $llamado->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <label for="filtro-anio">Año:</label>
                <div class="input-group">
                    <select id="filtro-anio">
                        <option value="">Todos</option>
                        @foreach ($anios as $anio)
                            <option value="{{ $anio }}">{{ $anio }}</option>
                        @endforeach
                    </select>
                </div>

            </div>

            <div id="mensaje-seleccionar" class="empty-state no-print">
                <i class="ri-filter-3-line"></i>
                <p>Seleccioná una carrera y un llamado para ver el historial de finales.</p>
            </div>

            <div id="tabla-historial-wrapper" style="display:none;">
                <div class="table-container">
                    <table class="cursada-table" id="tablaHistorialFinales">
                    <thead>
                        <tr>
                            <th>Fecha y Hora</th>
                            <th>Espacio</th>
                            <th style="width:80px;">Año</th>
                            <th>Profesores</th>
                            <th style="width:100px;" class="no-print">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>

                        @foreach ($mesas as $mesa)
                        <tr
                            data-carrera="{{ $mesa->id_carrera }}"
                            data-llamado="{{ $mesa->id_llamado }}"
                            data-anio="{{ $mesa->anio }}"
                        >
                            <td>{{ $mesa->fecha_hora }}</td>
                            <td>{{ $mesa->espacio }}</td>
                            <td>{{ $mesa->anio }}°</td>
                            <td>{{ $mesa->profesores }}</td>
                            <td class="text-center no-print">
                                <a
                                    href="{{ route('finalPerfil', $mesa->id_mesa) }}"
                                    class="btn-primary">
                                    ver
                                </a>
                            </td>
                        </tr>
                        @endforeach

                    </tbody>
                    </table>
                </div>

            </div>

        </div>
        {{-- fin .printable-area --}}

        <button type="button" class="btn-table no-print" id="btn-imprimir" title="Imprimir historial" style="width:44px; height:44px; margin-top:20px;">
            <i class="ri-printer-line"></i>
        </button>

    </div>

    @include('partials.footer')

    <script>
    document.addEventListener('DOMContentLoaded', function () {

        const filtroCarrera = document.getElementById('filtro-carrera');
        const filtroLlamado = document.getElementById('filtro-llamado');
        const filtroAnio    = document.getElementById('filtro-anio');
        const filas         = document.querySelectorAll('#tablaHistorialFinales tbody tr');
        const btnImprimir   = document.getElementById('btn-imprimir');
        const mensajeVacio  = document.getElementById('mensaje-seleccionar');
        const tablaWrapper  = document.getElementById('tabla-historial-wrapper');

        function aplicarFiltros() {
            const carrera = filtroCarrera.value;
            const llamado = filtroLlamado.value;
            const anio    = filtroAnio.value;

            // Carrera y Llamado son obligatorios: sin los dos, no se muestra nada
            if (carrera === '' || llamado === '') {
                tablaWrapper.style.display = 'none';
                mensajeVacio.style.display = '';
                return;
            }

            tablaWrapper.style.display = '';
            mensajeVacio.style.display = 'none';

            filas.forEach(function (fila) {
                const coincide =
                    fila.dataset.carrera === carrera &&
                    fila.dataset.llamado === llamado &&
                    (anio === '' || fila.dataset.anio === anio);

                fila.style.display = coincide ? '' : 'none';
            });
        }

        [filtroCarrera, filtroLlamado, filtroAnio].forEach(function (select) {
            select.addEventListener('change', aplicarFiltros);
        });

        // pageshow se dispara en la carga normal Y cuando el navegador
        // restaura la página desde el historial (botón "atrás", bfcache).
        // En ese segundo caso los <select> ya vienen con su valor anterior
        // restaurado por el navegador, pero sin disparar 'change' — por
        // eso hay que re-evaluar los filtros acá y no solo en el load
        // inicial, o la tabla queda oculta aunque los selects tengan algo
        // elegido.
        window.addEventListener('pageshow', aplicarFiltros);

        btnImprimir.addEventListener('click', function () {
            window.print();
        });

    });
    </script>

</body>
</html>