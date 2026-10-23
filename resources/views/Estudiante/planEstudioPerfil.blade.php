<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Plan de Estudio</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="{{ asset('fonts/remixicon/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/sideBar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filtro.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tablas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/botones.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/planEstudio.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/alumnosSec.css') }}">

    @vite(['resources/css/app.css'])
</head>

<body>
<button class="mobile-menu-btn" id="menu-btn">
    <i class="ri-menu-line"></i>
</button>
@include('partials.sidebar')

<div class="main">

    <div class="top-actions">
        <button class="btn-modificar" type="button" onclick="window.location.href='{{ route('carreraEst') }}'">
            <i class="ri-arrow-left-line"></i> Volver
        </button>
        <button class="btn-primary" onclick="window.location.href='{{ route('planEspacios', $planResolucion->id_carrera) }}'"> Espacios</button>
    </div>

    <div class="form-container">

        <div class="perfil-header">
            <h1>{{ $planResolucion->plan->titulo }} Res. {{ $planResolucion->resolucion->descripcion }}</h1>

        </div>

        <div class="form-grid">

            <label for="titulo">Título del plan</label>
            <div class="input-group">
                <input type="text" id="titulo" value="{{ $planResolucion->plan->titulo }}" disabled>
            </div>

            <label for="plan_descripcion">Descripción del plan</label>
            <div class="input-group">
                <input type="text" id="plan_descripcion" value="{{ $planResolucion->plan->descripcion }}" disabled>
            </div>

            <label for="carrera">Carrera</label>
            <div class="input-group">
                <input type="text" id="carrera" value="{{ $planResolucion->carrera->nombre ?? '—' }}" disabled>
            </div>

            <label for="total_horas">Años de duración</label>
            <div class="input-group">
                <input type="text" id="total_horas" value="{{ $planResolucion->total_horas }}" disabled>
            </div>

        </div>

        <div class="section-divider"></div>

        <div class="login-section">
            <h2>Resolución</h2>
            <div class="form-grid">

                <label for="resolucion_descripcion">Resolución</label>
                <div class="input-group">
                    <input type="text" id="resolucion_descripcion" value="{{ $planResolucion->resolucion->descripcion }}" disabled>
                </div>

                <label for="fecha">Fecha</label>
                <div class="input-group">
                    <input type="text" id="fecha" value="{{ $planResolucion->fecha?->format('d/m/Y') }}" disabled>
                </div>

            </div>
        </div>

        <div class="section-divider"></div>

        <div class="login-section">
            <h2>Archivo</h2>
            <div class="form-grid">

                <label for="archivo_descripcion">Descripción del archivo</label>
                <div class="input-group">
                    <input type="text" id="archivo_descripcion" value="{{ $planResolucion->tipoArchivo->descripcion ?? '—' }}" disabled>
                </div>

                <label>Archivo</label>
                @if($planResolucion->tipoArchivo?->archivo)
                    <a href="{{ Storage::url($planResolucion->tipoArchivo->archivo) }}" target="_blank" class="btn-primary" style="width:fit-content;">
                        <i class="ri-download-line"></i> Descargar PDF
                    </a>
                @else
                    <span>No hay archivo cargado.</span>
                @endif

            </div>
        </div>

    </div>
</div>

@include('partials.footer')

</body>
</html>
