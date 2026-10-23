<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carrera</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="{{ asset('fonts/remixicon/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/sideBar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filtro.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/botones.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/materiaPerfil.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Estudiante/carreraEst.css') }}">

    @vite(['resources/css/app.css'])
</head>

<body>
<button class="mobile-menu-btn" id="menu-btn">
    <i class="ri-menu-line"></i>
</button>
    @include('partials.sidebar')

    <div class="main">
        <div class="perfil-header">
        <h1>{{ $usuario->id_usuario }}-{{ $usuario->apellido }}, {{ $usuario->nombre }}</h1>
        </div>
        <div class="form-grid" style="max-width:600px; margin-top:24px;">

            <label for="filtro-carrera">Carrera</label>
            <div class="input-group">
                <select id="filtro-carrera">
                    @foreach ($carreras as $carrera)
                        <option value="{{ $carrera->id_carrera }}">{{ $carrera->nombre }}</option>
                    @endforeach
                </select>
            </div>

        </div>
        <br>
        @foreach ($carreras as $carrera)

            <div
                class="form-grid datos-carrera"
                data-carrera="{{ $carrera->id_carrera }}"
                style="max-width:600px; {{ !$loop->first ? 'display:none;' : '' }}">
                <label>Plan de estudio</label>
                @if($carrera->planVigente && $carrera->planVigente->plan)
                    <a
                        href="{{ route('planEstudio.show', $carrera->planVigente->plan->id_plan) }}"
                        style="color:#2563eb; font-weight:600;"
                    >
                        {{ $carrera->planVigente->plan->titulo }}
                    </a>
                @else
                    <span>Sin plan de estudio asignado</span>
                @endif
                <label>Cohorte</label>
                <span>
                    {{ $carrera->pivot->cohorte
                        ? \Carbon\Carbon::parse($carrera->pivot->cohorte)->format('Y')
                        : 'Sin datos'
                    }}
                </span>
                <label>Última matriculación</label>
                <span>
                    {{ $carrera->ultima_matriculacion
                        ? \Carbon\Carbon::parse($carrera->ultima_matriculacion)->format('d/m/Y')
                        : 'Sin datos'
                    }}
                </span>
                <label>Se recibió</label>
                @if($carrera->se_recibio)
                    <span class="estado-ok">
                        <i class="ri-checkbox-circle-fill"></i> Sí
                    </span>
                @else
                    <span class="estado-no">
                        <i class="ri-close-circle-line"></i> No
                    </span>
                @endif
                <label>Promedio</label>
                <span>
                    {{ $carrera->promedio }}
                </span>
            </div>

        @endforeach     

    </div>

    @include('partials.footer')

    <script>
    document.addEventListener('DOMContentLoaded', function () {

        const filtroCarrera = document.getElementById('filtro-carrera');
        const bloques = document.querySelectorAll('.datos-carrera');

        filtroCarrera.addEventListener('change', function () {
            const seleccionada = filtroCarrera.value;

            bloques.forEach(function (bloque) {
                bloque.style.display = bloque.dataset.carrera === seleccionada ? '' : 'none';
            });
        });

    });
    </script>

</body>
</html>
