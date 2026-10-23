<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Materia</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="{{ asset('fonts/remixicon/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/sideBar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filtro.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tablas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/botones.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/materiaPerfil.css') }}">

</head>

<body>
    <button class="mobile-menu-btn" id="menu-btn">
        <i class="ri-menu-line"></i>
    </button>

    @include('partials.sidebar')

    @php
        $aniosCurriculares = collect(range(1, 5))->map(fn ($anio) => (object) [
            'id_anio' => $anio,
            'nombre'  => \App\Models\Materia::nombreAnio($anio),
        ]);

        $ordenesDisponibles = range(1, 20);
    @endphp

    <div class="main">
        <div class="form-container">

            <!-- HEADER -->
            <div class="perfil-header">
                <h1>Agregar Materia — {{ $carrera->nombre }}</h1>
            </div>

            <div id="alert-ajax" class="alert" style="display:none;"></div>

            <form class="materia-form" id="form-materia">
                @csrf
                <input type="hidden" name="id_carrera" id="id_carrera" value="{{ $carrera->id_carrera }}">

                <div class="form-grid">

                    <label for="nombre">Nombre *</label>
                    <div class="input-group">
                        <input type="text" name="nombre" id="nombre" placeholder="Nombre" required>
                        <small class="error-message" id="error-nombre"></small>
                    </div>

                    <label for="id_plan_estudio">Plan de estudios *</label>
                    <div class="input-group">
                        <select name="id_plan_estudio" id="id_plan_estudio" required>
                            <option value="" disabled selected hidden>Seleccionar plan de estudios</option>
                            @foreach ($planesEstudio as $plan)
                                <option value="{{ $plan->id_plan }}">{{ $plan->titulo }}</option>
                            @endforeach
                        </select>
                        <small class="error-message" id="error-id_plan_estudio"></small>
                    </div>

                    <label for="id_anio">Año curricular *</label>
                    <div class="input-group">
                        <select name="id_anio" id="id_anio" required>
                            <option value="" disabled selected hidden>Seleccionar año curricular</option>
                            @foreach ($aniosCurriculares as $anio)
                                <option value="{{ $anio->id_anio }}">{{ $anio->nombre }}</option>
                            @endforeach
                        </select>
                        <small class="error-message" id="error-id_anio"></small>
                    </div>

                    <label for="orden_anio">N° de orden dentro de año *</label>
                    <div class="input-group">
                        <select name="orden_anio" id="orden_anio" required>
                            <option value="" disabled selected hidden>Seleccionar orden</option>
                            @foreach ($ordenesDisponibles as $orden)
                                <option value="{{ $orden }}">{{ $orden }}</option>
                            @endforeach
                        </select>
                        <small class="error-message" id="error-orden_anio"></small>
                    </div>

                    <label for="carga_horaria_anual">Carga horaria anual *</label>
                    <div class="input-group">
                        <input
                            type="number"
                            name="carga_horaria_anual"
                            id="carga_horaria_anual"
                            placeholder="Carga horaria anual"
                            min="1"
                            required
                        >
                        <small class="error-message" id="error-carga_horaria_anual"></small>
                    </div>

                </div>

                <div class="checkbox-group">

                    <div class="checkbox-row">
                        <label>TFO/EDI/UDI</label>
                        <input type="checkbox" name="tfo_edi_udi" id="tfo_edi_udi" value="1" checked>
                    </div>

                    <div class="checkbox-row">
                        <label>Acreditable con final</label>
                        <input type="checkbox" name="acreditable_final" id="acreditable_final" value="1" checked>
                    </div>

                    <div class="checkbox-row">
                        <label>Promocionable</label>
                        <input type="checkbox" name="promocionable" id="promocionable" value="1" checked>
                    </div>

                    <div class="checkbox-row">
                        <label>Optativa</label>
                        <input type="checkbox" name="optativa" id="optativa" value="1" checked>
                    </div>

                </div>

                <!-- BOTONES -->
                <div class="form-actions">
                    <button type="button" class="btn-primary" id="btn-guardar">
                        <i class="ri-save-line"></i> Guardar
                    </button>
                    <button type="button" class="btn-secondary" onclick="window.history.back()">
                        Volver
                    </button>
                </div>

            </form>

        </div>
    </div>

    @include('partials.footer')

    <script>
    (function () {

        const btnGuardar = document.getElementById('btn-guardar');
        const form = document.getElementById('form-materia');
        const alertAjax = document.getElementById('alert-ajax');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function mostrarAlerta(mensaje, tipo) {
            alertAjax.textContent = mensaje;
            alertAjax.className = 'alert alert-' + tipo;
            alertAjax.style.display = 'block';

            alertAjax.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });

            if (tipo === 'success') {
                setTimeout(function () {
                    alertAjax.style.display = 'none';
                }, 5000);
            }
        }

        function limpiarErrores() {
            document.querySelectorAll(".error-message").forEach(function (error) {
                error.textContent = "";
            });
            document.querySelectorAll(".input-error").forEach(function (input) {
                input.classList.remove("input-error");
            });
        }

        btnGuardar.addEventListener('click', function () {

            limpiarErrores();

            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<i class="ri-loader-4-line"></i> Guardando...';

            const formData = new FormData(form);
            const datos = {};

            formData.forEach(function (valor, clave) {
                if (clave !== '_token') {
                    datos[clave] = valor;
                }
            });

            // Los checkboxes no aparecen en el FormData si están destildados,
            // así que los forzamos explícitamente a 0/1
            ['tfo_edi_udi', 'acreditable_final', 'promocionable', 'optativa'].forEach(function (campo) {
                const input = document.getElementById(campo);
                datos[campo] = input.checked ? 1 : 0;
            });

            fetch('{{ route('materias.store', $carrera->id_carrera) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(datos),
            })
            .then(async (response) => {
                const data = await response.json();
                return { status: response.status, data: data };
            })
            .then(function ({ status, data }) {

                btnGuardar.disabled = false;
                btnGuardar.innerHTML = '<i class="ri-save-line"></i> Guardar';

                if (data.success) {

                    mostrarAlerta('✓ ' + data.message, 'success');

                    setTimeout(function () {
                        window.location.href = '{{ route('carreras.show', $carrera->id_carrera) }}';
                    }, 1200);

                } else {

                    if (data.errors) {

                        Object.entries(data.errors).forEach(function ([campo, errores]) {

                            const input = document.getElementById(campo);

                            if (input) {
                                input.classList.add("input-error");
                            }

                            const error = document.getElementById("error-" + campo);

                            if (error) {
                                error.textContent = errores[0];
                            }

                        });

                        mostrarAlerta("Hay campos con errores. Revisá el formulario.", "error");

                    } else {

                        mostrarAlerta(data.message || "Ocurrió un error.", "error");

                    }
                }

            })
            .catch(function () {

                btnGuardar.disabled = false;
                btnGuardar.innerHTML = '<i class="ri-save-line"></i> Guardar';

                mostrarAlerta('Error de conexión. Intentá de nuevo.', 'error');

            });

        });

        // Limpiar error al escribir/seleccionar
        document.querySelectorAll("input, select").forEach(function (campo) {

            const evento = campo.tagName === "SELECT" ? "change" : "input";

            campo.addEventListener(evento, function () {

                campo.classList.remove("input-error");

                const error = document.getElementById("error-" + campo.id);

                if (error) {
                    error.textContent = "";
                }

            });

        });

    })();
    </script>

</body>
</html>
