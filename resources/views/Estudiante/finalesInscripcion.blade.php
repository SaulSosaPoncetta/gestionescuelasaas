<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inscripción a finales</title>
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
    <link rel="stylesheet" href="{{ asset('css/Estudiante/finalesInscripcion.css') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <button class="mobile-menu-btn" id="menu-btn">
        <i class="ri-menu-line"></i>
    </button>

    @include('partials.sidebar')

    <div class="main">

        <button class="btn-modificar" type="button" onclick="window.location='{{ route('finales') }}'">
            <i class="ri-arrow-left-line"></i>Volver
        </button>

        <br>

        <!-- HEADER -->
        <div class="top-bar">
            <h1>Inscripcion a finales</h1>
        </div>

        <div id="alert-ajax" class="alert" style="display:none;"></div>

        @if ($aniosCarrera->isEmpty())

            <div class="empty-state">
                <i class="ri-file-list-3-line"></i>
                <p>Todavía no hay materias cargadas para tu carrera.</p>
            </div>

        @else

            @foreach ($aniosCarrera as $anio => $materias)

            <div class="table-container anio-container">
                <table class="cursada-table tabla-inscripcion-anio">
                    <thead>
                        <tr>
                            <th class="col-espacio">
                                <span>Espacio Curricular</span>
                                <span class="anio-badge">{{ $anio }}° AÑO</span>
                            </th>
                            <th>Condición</th>
                            <th>Fechas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($materias as $materia)
                        <tr @if ($materia->bloqueada) class="fila-bloqueada" @endif>
                            <td>{{ $materia->nombre }}</td>
                            <td>{{ $materia->condicion }}</td>
                            <td>
                                @if (!$materia->bloqueada)
                                    <div class="fechas-row">
                                        @foreach ($materia->fechas as $fecha)
                                            <button type="button" class="btn-fecha" data-id-mesa="{{ $fecha->id_mesa }}">{{ $fecha->fecha }}</button>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @endforeach

            <div class="form-actions">
                <button type="button" class="btn-primary" id="btn-guardar-inscripcion">
                    <i class="ri-save-line"></i> Guardar
                </button>
            </div>

        @endif

    </div>

    @include('partials.footer')

    <script>
        window.rutaFinalesSec = '{{ route('finales') }}';
        window.rutaInscripcionStore = '{{ route('finalesInscripcion.store') }}';
    </script>
    <script src="{{ asset('js/Estudiante/finalesInscripcion.js') }}"></script>

    {{--
        NOTA: finalesInscripcion.js (que no tengo) debería encargarse de:
          1) togglear la clase "seleccionada" en los .btn-fecha al hacer click
             (idealmente uno seleccionado por materia como mucho).
          2) al hacer click en #btn-guardar-inscripcion, juntar los
             data-id-mesa de todos los .btn-fecha.seleccionada y hacer
             POST a window.rutaInscripcionStore.

        Si ese archivo todavía no hace el punto 2 (el submit real), este
        bloque lo cubre acá mismo. Si lo sacás de acá porque ya lo movés
        al .js, mantené la misma lógica de body/response.
    --}}
    <script>
    document.addEventListener('DOMContentLoaded', function () {

        const btnGuardar = document.getElementById('btn-guardar-inscripcion');
        const alertAjax = document.getElementById('alert-ajax');

        if (!btnGuardar) {
            return;
        }

        function mostrarAlerta(mensaje, tipo) {
            if (!alertAjax) {
                return;
            }
            alertAjax.textContent = mensaje;
            alertAjax.className = 'alert alert-' + tipo;
            alertAjax.style.display = 'block';
            alertAjax.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        btnGuardar.addEventListener('click', function () {

            const idsMesa = Array.from(document.querySelectorAll('.btn-fecha.seleccionada'))
                .map(function (btn) {
                    return parseInt(btn.dataset.idMesa, 10);
                });

            if (idsMesa.length === 0) {
                mostrarAlerta('Elegí al menos una fecha para inscribirte.', 'error');
                return;
            }

            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<i class="ri-loader-4-line"></i> Guardando...';

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(window.rutaInscripcionStore, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ ids_mesa: idsMesa }),
            })
            .then(function (response) {
                return response.json().then(function (data) {
                    return { status: response.status, data: data };
                });
            })
            .then(function ({ data }) {

                btnGuardar.disabled = false;
                btnGuardar.innerHTML = '<i class="ri-save-line"></i> Guardar';

                if (data.success) {
                    mostrarAlerta('✓ ' + data.message, 'success');
                    setTimeout(function () {
                        window.location = window.rutaFinalesSec;
                    }, 1200);
                } else {
                    const detalle = data.rechazadas
                        ? Object.values(data.rechazadas).join(' ')
                        : (data.message || 'Ocurrió un error.');
                    mostrarAlerta(detalle, 'error');
                }
            })
            .catch(function () {
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = '<i class="ri-save-line"></i> Guardar';
                mostrarAlerta('Error de conexión. Intentá de nuevo.', 'error');
            });

        });

    });
    </script>

</body>
</html>