<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Constancias</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="{{ asset('fonts/remixicon/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/sideBar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tablas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/constanciasSec.css') }}">
</head>

<body>
<button class="mobile-menu-btn" id="menu-btn">
    <i class="ri-menu-line"></i>
</button>
@include('partials.sidebar')

{{-- Botón solicitar fijo en esquina superior derecha --}}
<button class="btn-solicitar" data-href="#">Solicitar</button>

<div class="main">

    <div class="top-bar">
        <h1>Constancias</h1>
    </div>

    {{-- TABS --}}
    <div class="tabs">
        <button class="tab-btn active" data-tab="no-procesadas">No Procesadas</button>
        <button class="tab-btn"        data-tab="procesadas">Procesadas</button>
    </div>

    {{-- CONTENIDO --}}
    <div class="table-area">

        {{-- ── TAB: NO PROCESADAS ───────────────── --}}
        <div class="tab-panel active" id="tab-no-procesadas">

            @if(isset($noProcesadas) && $noProcesadas->count() > 0)

                <table class="constancias-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Interesado</th>
                            <th>Tipo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($noProcesadas as $c)
                        <tr>
                            <td>{{ $c->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $c->alumno->padron }} - {{ $c->alumno->apellido }}, {{ $c->alumno->nombre }}</td>
                            <td>{{ $c->tipo }}</td>
                            <td>
                                <button class="btn-lupa" title="Ver detalle" data-id="{{ $c->id }}">
                                    <i class="ri-search-line"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

            @else

                <div class="table-placeholder">
                    <i class="ri-file-list-3-line"></i>
                    <p>No hay constancias pendientes de procesar.</p>
                </div>

            @endif

        </div>

        {{-- ── TAB: PROCESADAS ─────────────────── --}}
        <div class="tab-panel" id="tab-procesadas">

            @if(isset($procesadas) && $procesadas->count() > 0)

                <table class="constancias-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Interesado</th>
                            <th>Tipo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($procesadas as $c)
                        <tr>
                            <td>{{ $c->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $c->alumno->padron }} - {{ $c->alumno->apellido }}, {{ $c->alumno->nombre }}</td>
                            <td>{{ $c->tipo }}</td>
                            <td>
                                <button class="btn-lupa" title="Ver detalle" data-id="{{ $c->id }}">
                                    <i class="ri-search-line"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

            @else

                <div class="table-placeholder">
                    <i class="ri-archive-line"></i>
                    <p>No hay constancias procesadas en el historial.</p>
                </div>

            @endif

        </div>

    </div>

    {{-- BOTÓN IMPRIMIR --}}
    <button class="btn-print" id="btn-print" title="Imprimir constancia">
        <i class="ri-printer-line"></i>
    </button>

</div>

@include('partials.footer')

<div class="overlay" id="overlay"></div>

<script src="{{ asset('js/Secretaria/constanciasSec.js') }}" defer></script>

</body>
</html>
