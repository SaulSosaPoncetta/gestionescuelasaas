<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cargar notas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('fonts/remixicon/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/sideBar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filtro.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tablas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/botones.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/cargarNota.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/materiaPerfil.css') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/Filtro.js'])

</head>

<body>
<button class="mobile-menu-btn" id="menu-btn">
    <i class="ri-menu-line"></i>
</button>
    @include('partials.sidebar')

    @php
        $periodos = ['1C' => '1° C', '2C' => '2° C', 'REC' => 'Rec./Prefinal'];

        $renderCeldaNota = function ($nota) {
            $valor = $nota->valor ?? '';
            $situacion = $nota->situacion ?? '';
            $etiquetas = [
                'promocionado'      => 'Promocionado',
                'aprobado'          => 'Aprobado',
                'desaprobado'       => 'Desaprobado',
                'promocion_directa' => 'Promoción directa',
                'examen_flotante'   => 'Pendiente examen flotante',
            ];
            $etiqueta = $etiquetas[$situacion] ?? '';

            return compact('valor', 'situacion', 'etiqueta');
        };
    @endphp

    <div
        class="main"
        id="app-cargar-notas"
        data-materia="{{ $materia->id_materia }}"
        data-nota-promocionable="{{ $materia->nota_promocionable ?? 7 }}"
        data-nota-aprobada="{{ $materia->nota_aprobada ?? 4 }}"
        data-promocion-directa="{{ $materia->promocionable ? '1' : '0' }}"
    >
        <button class="btn-modificar" type="button" onclick="window.location='{{ route('materias.show', $materia->id_materia) }}'">
            <i class="ri-arrow-left-line"></i>Volver
        </button>

        <h1>Cargar notas — {{ $materia->nombre }}</h1>

        <div id="alert-ajax" class="alert" style="display:none;"></div>

        <div class="top-bar">
            <label>
                Cerrar cursada Automaticamente
                <input type="checkbox" id="cierre_automatico" checked>
            </label>
            <span class="help">
                Si esta activado, la cursada se cerrara automaticamente en base a la situacion de los dos cuatrimestres. Puede cambiar los datos de cierre manualmete.
            </span>
        </div>

        <div class="table-container tabla-responsive">
            <table class="cursada-table" id="tablaCargarNotas">
                <thead>
                    <tr>
                        <th>Alumno</th>
                        <th>1° C</th>
                        <th>2° C</th>
                        <th>Rec./Prefinal</th>
                        <th>Cierre</th>
                        <th style="width:100px;">Reprobar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cursando as $fila)
                    <tr data-estado="cursando" data-id="{{ $fila->id_usuario }}">
                        <td>{{ $fila->nombre }}</td>

                        @foreach (['primerC' => '1C', 'segundoC' => '2C', 'recPrefinal' => 'REC'] as $prop => $periodo)
                            @php $c = $renderCeldaNota($fila->{$prop}); @endphp
                            <td class="nota-cell" data-periodo="{{ $periodo }}">
                                <div class="nota-wrapper dropdown" data-situacion="{{ $c['situacion'] }}">
                                    <input type="text" class="nota-input" placeholder="Nota" value="{{ $c['valor'] }}">
                                    <button type="button" class="arrow-btn btn-dropdown">›</button>
                                    <ul class="dropdown-menu-notas">
                                        <li><a class="dropdown-item opcion-nota" href="#" data-valor="promocionado">Promocionado</a></li>
                                        <li><a class="dropdown-item opcion-nota" href="#" data-valor="aprobado">Aprobado</a></li>
                                        <li><a class="dropdown-item opcion-nota" href="#" data-valor="desaprobado">Desaprobado</a></li>
                                        @if ($periodo !== 'REC' && $materia->promocionable)
                                            <li><a class="dropdown-item opcion-nota" href="#" data-valor="promocion_directa">Promoción directa</a></li>
                                        @endif
                                    </ul>
                                    @if ($c['etiqueta'])
                                        <span class="badge-situacion badge-{{ $c['situacion'] }}">{{ $c['etiqueta'] }}</span>
                                    @endif
                                </div>
                            </td>
                        @endforeach

                        {{-- Cierre (solo lectura, lo completa el cierre automático o el cierre manual) --}}
                        @php $cierre = $renderCeldaNota($fila->cierre); @endphp
                        <td class="nota-cell" data-periodo="CIERRE">
                            <div class="nota-wrapper dropdown" data-situacion="{{ $cierre['situacion'] }}">
                                <input type="text" class="nota-input" placeholder="Nota" value="{{ $cierre['valor'] }}">
                                <button type="button" class="arrow-btn btn-dropdown">›</button>
                                <ul class="dropdown-menu-notas">
                                    <li><a class="dropdown-item opcion-nota" href="#" data-valor="promocionado">Promocionado</a></li>
                                    <li><a class="dropdown-item opcion-nota" href="#" data-valor="aprobado">Aprobado</a></li>
                                    <li><a class="dropdown-item opcion-nota" href="#" data-valor="desaprobado">Desaprobado</a></li>
                                </ul>
                                @if ($cierre['etiqueta'])
                                    <span class="badge-situacion badge-{{ $cierre['situacion'] }}">{{ $cierre['etiqueta'] }}</span>
                                @endif
                            </div>
                        </td>

                        {{-- Reprobar directo --}}
                        <td class="text-center">
                            <a
                                href="#"
                                class="btn-table btn-reprobar-directo"
                                title="Reprobar alumno directamente"
                                style="background:#dc2626; text-decoration: none;">
                                <i class="ri-close-circle-line"></i>
                            </a>
                        </td>

                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="display:flex; justify-content:flex-start; gap:10px; margin-top:12px;">
            <button class="btn-primary" id="btn-guardar">
                <i class="ri-save-line"></i> Guardar
            </button>
        </div>

        <br>

        <h1>Alumnos desaprobados directamente</h1>

        <div class="table-container tabla-responsive">
            <table class="cursada-table" id="tablaReprobados">
                <thead>
                    <tr>
                        <th>Alumno</th>
                        <th>1° C</th>
                        <th>2° C</th>
                        <th>Rec./Prefinal</th>
                        <th style="width:100px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reprobados as $fila)
                    <tr data-estado="reprobado" data-id="{{ $fila->id_usuario }}">
                        <td>{{ $fila->nombre }}</td>
                        <td>{{ $fila->primerC->valor ?? '—' }}</td>
                        <td>{{ $fila->segundoC->valor ?? '—' }}</td>
                        <td>{{ $fila->recPrefinal->valor ?? '—' }}</td>
                        <td class="text-center">
                            <a href="#" class="btn-table btn-restaurar" title="Restaurar a cursando">
                                <i class="ri-arrow-go-back-line"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>

    @include('partials.footer')

    <script src="{{ asset('js/Secretaria/cargarNota.js') }}"></script>

</body>
</html>
