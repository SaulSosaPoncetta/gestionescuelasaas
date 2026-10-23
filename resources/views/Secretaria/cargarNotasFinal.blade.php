<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cargar notas final</title>
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

    @vite(['resources/css/app.css'])
</head>

<body>
<button class="mobile-menu-btn" id="menu-btn">
    <i class="ri-menu-line"></i>
</button>
    @include('partials.sidebar')

    <div class="main">

        <h1>Cargar notas final</h1>
        <br>
        <p class="help" style="margin-bottom:24px; font-size:18px;">
            {{ $mesa->fecha }} {{ $mesa->hora }} - {{ $mesa->materia }} - {{ $mesa->anio }}
        </p>

        <div id="alert-ajax" class="alert" style="display:none;"></div>

        <form id="form-notas-final" data-url="/cargarNotaFinal/{{ $mesa->id_mesa }}">
            @csrf

            <div class="table-container">
                <table class="cursada-table" id="tablaNotasFinal">
                    <thead>
                        <tr>
                            <th>Alumno</th>
                            <th style="width:140px;">Nota</th>
                            <th style="width:100px;">Intentos</th>
                        </tr>
                    </thead>
                    <tbody>

                        @foreach ($alumnosFinal as $alumno)
                        <tr>
                            <td>{{ $alumno->nombre }}</td>
                            <td class="nota-cell">
                                <input
                                    type="text"
                                    inputmode="decimal"
                                    class="nota-input"
                                    name="nota_{{ $alumno->id_usuario }}"
                                    id="nota_{{ $alumno->id_usuario }}"
                                    placeholder="Nota"
                                >
                                <small class="error-message" id="error-nota_{{ $alumno->id_usuario }}"></small>
                            </td>
                            <td>{{ $alumno->cant }}</td>
                        </tr>
                        @endforeach

                    </tbody>
                </table>
            </div>

            <div style="display:flex; justify-content:flex-start; gap:10px; margin-top:12px;">
                <button type="button" class="btn-primary" id="btn-guardar">
                    <i class="ri-save-line"></i> Guardar
                </button>
                <button type="button" class="btn-secondary" onclick="window.location.href='{{ route('finalPerfil', $mesa->id_mesa) }}'">
                    Volver
                </button>
            </div>

        </form>

    </div>

    @include('partials.footer')

    <script src="{{ asset('js/Secretaria/cargarNota.js') }}"></script>

</body>
</html>