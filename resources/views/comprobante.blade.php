<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('fonts/remixicon/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/sideBar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/botones.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/materiaPerfil.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/comprobante.css') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <button class="mobile-menu-btn" id="menu-btn">
        <i class="ri-menu-line"></i>
    </button>

    @include('partials.sidebar')

    <div class="main">

        <button class="btn-modificar" type="button" onclick="window.history.back()">
            <i class="ri-arrow-left-line"></i>Volver
        </button>

        {{--
            esta pantalla es una PLANTILLA genérica, pensada
            para reusarse en cualquier movimiento (inscripción a final,
            carga de nota, alta de usuario, etc.). Reemplazar los valores
            fijos de acá abajo por los reales:

            - {{ $comprobante->numero }}      → número único del comprobante
            - {{ $comprobante->usuario }}     → nombre_usuario de quien hizo la operación
            - {{ $comprobante->fecha }}       → fecha en que se generó
            - {{ $comprobante->hora }}        → hora en que se generó
            - {{ $comprobante->operacion }}   → descripción de la operación realizada

            El "código de verificación" de más abajo hoy repite el número
            de comprobante como placeholder. Cuando el back lo tenga
            resuelto (hash, o URL tipo sgi.edu/verificar/{codigo}), conectarlo,
            no es algo que el HTML/CSS pueda resolver solo.
        --}}

        <div class="comprobante-wrapper">

            <div class="comprobante-box" id="comprobante-imprimible">

                <p class="comprobante-titulo">
                    Comprobante N° <span class="comprobante-numero">63888297573</span>
                </p>

                <div class="comprobante-datos">
                    <p><strong>Usuario</strong>: lucio.requena</p>
                    <p><strong>Fecha</strong>: 16/7/2025</p>
                    <p><strong>Hora</strong>: 21:19</p>
                    <p><strong>Operación</strong>: Inscripción a fecha de final Sistemas y Organizaciones (06/08/2025 18:10 hs)</p>
                </div>

                <div class="comprobante-verificacion">
                    Código de verificación: <span>63888297573</span>
                </div>

            </div>
            
            <button class="btn-primary" type="button" id="btn-imprimir" title="Imprimir comprobante" onclick="window.print()">
            <i class="ri-printer-fill"></i> Imprimir
            </button>

        </div>

    </div>

    @include('partials.footer')

    <script src="{{ asset('js/Secretaria/comprobante.js') }}"></script>

</body>
</html>