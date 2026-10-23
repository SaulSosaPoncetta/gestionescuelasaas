<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Profesor a Materia</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="{{ asset('fonts/remixicon/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/sideBar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filtro.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tablas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/botones.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/docMatUpload.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/materiaPerfil.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/Filtro.js'])
</head>

<body>
    <button class="mobile-menu-btn" id="menu-btn">
        <i class="ri-menu-line"></i>
    </button>

    @include('partials.sidebar')

    @php
        $divisionesDisponibles = range(1, 10);
        $situacionesRevista = ['Titular', 'Suplente', 'Interino', 'Provisional'];
    @endphp

    <div class="main">

        <button class="btn-modificar" type="button" onclick="window.location='{{ route('materias.show', $materia->id_materia) }}'">
            <i class="ri-arrow-left-line"></i>Volver
        </button>

        <div class="form-container">
            <h1>Agregar Profesor a Materia</h1>

            <div id="alert-ajax" class="alert" style="display:none;"></div>

            <form class="materia-form" id="form-asignar-docente">
                @csrf

                <div class="form-grid">

                    <label for="materia_nombre">Materia</label>
                    <div class="input-group">
                        <input type="text" id="materia_nombre" value="{{ $materia->nombre }}" disabled>
                    </div>

                    <label for="id_docente">Profesor *</label>
                    <div class="input-group">
                        <select name="id_docente" id="id_docente" required>
                            <option value="" disabled selected hidden>Seleccionar</option>
                            @foreach ($docentes as $docente)
                                <option value="{{ $docente->id_usuario }}">
                                    {{ $docente->usuario->apellido }}, {{ $docente->usuario->nombre }}
                                </option>
                            @endforeach
                        </select>
                        <small class="error-message" id="error-id_docente"></small>
                    </div>

                    <label for="division">División</label>
                    <div class="input-group">
                        <select name="division" id="division">
                            <option value="" selected>Seleccionar</option>
                            @foreach ($divisionesDisponibles as $division)
                                <option value="{{ $division }}">{{ $division }}</option>
                            @endforeach
                        </select>
                        <small class="error-message" id="error-division"></small>
                    </div>

                    <label for="situacion_revista">Situación de revista</label>
                    <div class="input-group">
                        <select name="situacion_revista" id="situacion_revista">
                            <option value="" selected>Seleccionar</option>
                            @foreach ($situacionesRevista as $situacion)
                                <option value="{{ $situacion }}">{{ $situacion }}</option>
                            @endforeach
                        </select>
                        <small class="error-message" id="error-situacion_revista"></small>
                    </div>

                    <label for="fecha_inicio">Fecha inicio</label>
                    <div class="input-group">
                        <input type="date" name="fecha_inicio" id="fecha_inicio">
                        <small class="error-message" id="error-fecha_inicio"></small>
                    </div>

                    <label for="fecha_fin">Fecha fin</label>
                    <div class="input-group">
                        <input type="date" name="fecha_fin" id="fecha_fin">
                        <small class="error-message" id="error-fecha_fin"></small>
                    </div>

                    <label for="horas_semanales">Horas semanales</label>
                    <div class="input-group">
                        <input type="number" name="horas_semanales" id="horas_semanales" min="1" placeholder="Ej: 4">
                        <small class="error-message" id="error-horas_semanales"></small>
                    </div>

                </div>

                <div class="form-actions">
                    <button type="button" class="btn-primary" id="btn-agregar">Agregar</button>
                </div>

            </form>
        </div>

    </div>

    @include('partials.footer')

    <script>
    (function () {

        const form = document.getElementById('form-asignar-docente');
        const btnAgregar = document.getElementById('btn-agregar');
        const alertAjax = document.getElementById('alert-ajax');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function mostrarAlerta(mensaje, tipo) {
            alertAjax.textContent = mensaje;
            alertAjax.className = 'alert alert-' + tipo;
            alertAjax.style.display = 'block';
            alertAjax.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

            if (tipo === 'success') {
                setTimeout(function () { alertAjax.style.display = 'none'; }, 5000);
            }
        }

        function limpiarErrores() {
            document.querySelectorAll('.error-message').forEach(e => e.textContent = '');
            document.querySelectorAll('.input-error').forEach(e => e.classList.remove('input-error'));
        }

        btnAgregar.addEventListener('click', function () {

            limpiarErrores();

            btnAgregar.disabled = true;
            btnAgregar.innerHTML = '<i class="ri-loader-4-line"></i> Agregando...';

            const formData = new FormData(form);
            const datos = {};
            formData.forEach(function (valor, clave) {
                if (clave !== '_token') datos[clave] = valor;
            });

            fetch('{{ route('materias.asignarProfesor.store', $materia->id_materia) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(datos),
            })
            .then(async (response) => ({ status: response.status, data: await response.json() }))
            .then(function ({ data }) {

                btnAgregar.disabled = false;
                btnAgregar.innerHTML = 'Agregar';

                if (data.success) {

                    mostrarAlerta('✓ ' + data.message, 'success');

                    setTimeout(function () {
                        window.location.href = '{{ route('materias.show', $materia->id_materia) }}';
                    }, 1200);

                } else if (data.errors) {

                    Object.entries(data.errors).forEach(function ([campo, errores]) {
                        const input = document.getElementById(campo);
                        if (input) input.classList.add('input-error');

                        const error = document.getElementById('error-' + campo);
                        if (error) error.textContent = errores[0];
                    });

                    mostrarAlerta('Hay campos con errores. Revisá el formulario.', 'error');

                } else {
                    mostrarAlerta(data.message || 'Ocurrió un error.', 'error');
                }
            })
            .catch(function () {
                btnAgregar.disabled = false;
                btnAgregar.innerHTML = 'Agregar';
                mostrarAlerta('Error de conexión. Intentá de nuevo.', 'error');
            });
        });

    })();
    </script>

</body>
</html>
