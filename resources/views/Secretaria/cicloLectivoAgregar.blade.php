<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Ciclo Lectivo</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="{{ asset('fonts/remixicon/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/sideBar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/botones.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/materiaPerfil.css') }}">

</head>

<body>

    <button class="mobile-menu-btn" id="menu-btn">
        <i class="ri-menu-line"></i>
    </button>

    @include('partials.sidebar')

    <div class="main">

        <div class="form-container">

            <div class="perfil-header">
                <h1>Agregar Ciclo Lectivo</h1>
            </div>

            <div id="alert-ajax" class="alert" style="display:none;"></div>

                <form id="form-ciclo"
                      method="POST"
                      action="{{ route('cicloLectivo.store') }}">

                    @csrf

                    <div class="form-grid">

                        <label>Año *</label>
                            <div class="input-group">
                                <div class="input-with-help">
                                    <input
                                        type="number"
                                        id="anio"
                                        name="anio"
                                        min="2000"
                                        max="2100"
                                        placeholder="Ej: 2027"
                                        required>
                                     <div class="help-placeholder"></div>
                                </div>
                                <small id="error-anio" class="error-message"></small>
                            </div>

                        <label>Fecha de inicio *</label>
                            <div class="input-group">
                                <div class="input-with-help">
                                    <input
                                        type="date"
                                        id="fecha_inicio"
                                        name="fecha_inicio"
                                        required>
                                    <div class="help-icon">
                                        <i class="ri-question-line"></i>
                                        <div class="help-tooltip">Inicio del ciclo lectivo.</div>
                                    </div>
                                </div>
                                <small id="error-fecha_inicio" class="error-message"></small>
                            </div>

                        <label>Fecha de fin *</label>
                            <div class="input-group">
                                <div class="input-with-help">
                                    <input
                                        type="date"
                                        id="fecha_fin"
                                        name="fecha_fin"
                                        required>
                                    <div class="help-icon">
                                        <i class="ri-question-line"></i>
                                        <div class="help-tooltip">Debe ser posterior a la fecha de inicio.</div>
                                    </div>
                                </div>
                                <small id="error-fecha_fin" class="error-message"></small>
                            </div>

                        <label>Inicio matriculación *</label>
                            <div class="input-group">
                                <div class="input-with-help">
                                    <input
                                        type="date"
                                        id="inicio_matriculacion"
                                        name="inicio_matriculacion"
                                        required>
                                    <div class="help-icon">
                                        <i class="ri-question-line"></i>
                                        <div class="help-tooltip">Desde esta fecha los alumnos podrán inscribirse.</div>
                                    </div>
                                </div>
                                <small id="error-inicio_matriculacion" class="error-message"></small>
                            </div>



                        <label>Fin matriculación *</label>
                            <div class="input-group">
                                <div class="input-with-help">
                                    <input
                                        type="date"
                                        id="fin_matriculacion"
                                        name="fin_matriculacion"
                                        required>
                                    <div class="help-icon">
                                        <i class="ri-question-line"></i>
                                        <div class="help-tooltip">Hasta esta fecha podrán inscribirse.</div>
                                    </div>
                                </div>
                                <small id="error-fin_matriculacion" class="error-message"></small>
                            </div>
                    </div>

                    <div class="form-actions">

                        <button type="button" class="btn-secondary" onclick="window.location.href='{{ route('cicloLectivoSec') }}'">Volver</button>

                        <button type="submit" id="btn-guardar" class="btn-primary">
                        <i class="ri-save-line"></i>Guardar</button>

                    </div>
                </form>
        </div>
    </div>

@include('partials.footer')

<script>
(function () {

    const form = document.getElementById('form-ciclo');
    const btnGuardar = document.getElementById('btn-guardar');
    const alertAjax = document.getElementById('alert-ajax');

    function limpiarErrores() {

        document.querySelectorAll('.error-message')
            .forEach(function (elemento) {
                elemento.textContent = '';
            });

        document.querySelectorAll('.input-error')
            .forEach(function (elemento) {
                elemento.classList.remove('input-error');
            });
    }

    function mostrarAlerta(mensaje, tipo) {

        alertAjax.textContent = mensaje;
        alertAjax.className = 'alert alert-' + tipo;
        alertAjax.style.display = 'block';

        alertAjax.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
        });
    }

    form.addEventListener('submit', function (event) {

        event.preventDefault();

        limpiarErrores();

        btnGuardar.disabled = true;

        btnGuardar.innerHTML =
            '<i class="ri-loader-4-line ri-spin"></i> Guardando...';

        const datos = {};

        const campos = form.querySelectorAll(
            'input[name]:not([name="_token"])'
        );

        campos.forEach(function (campo) {
            datos[campo.name] = campo.value;
        });

        const csrfToken =
            document.querySelector('meta[name="csrf-token"]')
                .getAttribute('content');

        fetch("{{ route('cicloLectivo.store') }}", {

            method: 'POST',

            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },

            body: JSON.stringify(datos)

        })
        .then(function (response) {

            return response.json().then(function (data) {

                return {
                    status: response.status,
                    data: data
                };

            });

        })
        .then(function ({ status, data }) {

            btnGuardar.disabled = false;

            btnGuardar.innerHTML =
                '<i class="ri-save-line"></i> Guardar';

            if (status >= 200 && status < 300 && data.success) {

                mostrarAlerta(
                    '✓ ' + data.message,
                    'success'
                );

                form.reset();

                setTimeout(function () {
                    window.location.href =
                        "{{ route('cicloLectivoSec') }}";
                }, 1500);

            } else {

                if (data.errors) {

                    Object.entries(data.errors).forEach(
                        function ([campo, errores]) {

                            const input =
                                document.getElementById(campo);

                            if (input) {
                                input.classList.add('input-error');
                            }

                            const error =
                                document.getElementById(
                                    'error-' + campo
                                );

                            if (error) {
                                error.textContent = errores[0];
                            }

                        }
                    );

                    mostrarAlerta(
                        'Hay campos con errores.',
                        'error'
                    );

                } else {

                    mostrarAlerta(
                        data.message ?? 'Ocurrió un error.',
                        'error'
                    );
                }
            }

        })
        .catch(function () {

            btnGuardar.disabled = false;

            btnGuardar.innerHTML =
                '<i class="ri-save-line"></i> Guardar';

            mostrarAlerta(
                'Error de conexión.',
                'error'
            );

        });

    });

    form.querySelectorAll('input[name]').forEach(function (campo) {

        campo.addEventListener('input', function () {

            campo.classList.remove('input-error');

            const error =
                document.getElementById(
                    'error-' + campo.id
                );

            if (error) {
                error.textContent = '';
            }

        });

    });

})();
</script>

</body>
</html>
