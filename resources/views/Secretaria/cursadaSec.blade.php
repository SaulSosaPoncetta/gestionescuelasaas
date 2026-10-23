<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Perfil Carrera</title>

    <link rel="stylesheet" href="{{ asset('fonts/remixicon/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/sideBar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filtro.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tablas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/botones.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/materiaPerfil.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/cursadaPerfil.css') }}">

    @vite(['resources/css/app.css', 'resources/js/Filtro.js'])

</head>

<body>

    @include('partials.sidebar')

    <main class="main">
        <button class="btn-modificar" type="button" onclick="window.location='{{ route('carreras.index') }}'">
            <i class="ri-arrow-left-line"></i>Volver
        </button>

        <div id="alert-ajax" class="alert" style="display:none;"></div>

        <form id="form-carrera">

            @csrf
            @method('PUT')

            <div class="form-container">

                <div class="perfil-header">

                    <div>
                        <h1>{{ $carrera->nombre }}</h1>
                    </div>

                    <div style="display:flex; gap:12px;">

                        <button
                            id="btn-modificar"
                            type="button"
                            class="btn-modificar">

                            <i class="ri-edit-line"></i>
                            Modificar

                        </button>

                        <a
                            href="{{ route('materias.create', $carrera->id_carrera) }}"
                            class="btn-primary">

                            <i class="ri-add-line"></i>
                            Nueva Materia

                        </a>

                    </div>

                </div>

                <div class="form-grid">

                    <label for="nombre">Nombre *</label>

                    <div class="input-group">

                        <input
                            id="nombre"
                            name="nombre"
                            value="{{ $carrera->nombre }}"
                            disabled>

                        <small id="error-nombre" class="error-message"></small>

                    </div>

                    <label for="titulo">Título *</label>

                    <div class="input-group">

                        <input
                            id="titulo"
                            name="titulo"
                            value="{{ $carrera->titulo }}"
                            disabled>

                        <small id="error-titulo" class="error-message"></small>

                    </div>

                    <label for="plan_estudio">Plan de estudio *</label>

                    <div class="input-group">

                        @php $planVigente = $carrera->planVigente; @endphp

                        <input
                            id="plan_estudio"
                            value="{{ $planVigente ? ($planVigente->resolucion->descripcion ?? '—') . ' — ' . ($planVigente->plan->titulo ?? '—') : '—' }}"
                            disabled>

                        <small id="error-plan_estudio" class="error-message"></small>

                    </div>

                </div>
                <div class="form-actions">

                    <button
                        type="button"
                        class="btn-primary"
                        id="btn-guardar"
                        style="display:none;">
                        <i class="ri-save-line"></i> Guardar
                    </button>

                    <button
                        type="button"
                        class="btn-secondary"
                        id="btn-cancelar"
                        style="display:none;">
                        Cancelar
                    </button>

                </div>

            </div>

            <div class="section-divider"></div>

            <h2 class="section-title">Materias</h2>

            @foreach($materiasPorAnio as $anio => $materias)

                    <div class="year-card">
                        <div class="year-header">
                            <h3>{{ $anio }}
                                <span class="year-count">
                                    ({{ count($materias) }} {{ count($materias) == 1 ? 'materia' : 'materias' }})
                                </span>
                            </h3>
                        </div>

                        @if(count($materias))

                            <div class="table-container">
                                <table
                                    id="tabla-anio-{{ $loop->index }}"
                                    class="cursada-table tabla-anio">
                                    <thead>
                                        <tr>
                                            <th>Espacio Curricular</th>
                                            <th>Profesor</th>
                                            <th style="width:80px;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        @foreach($materias as $materia)
                                        <tr>
                                            <td>
                                                {{ $materia->nombre }}
                                            </td>
                                            <td>
                                                {{ $materia->profesor->usuario->apellido ?? 'Sin asignar' }}
                                            </td>
                                            <td class="text-center">
                                                <a
                                                    href="{{ route('materias.show', $materia->id_materia) }}"
                                                    class="btn-table"
                                                    title="Ver materia">

                                                    <i class="ri-eye-line"></i>
                                                </a>
                                            </td>
                                        </tr>

                                        @endforeach

                                    </tbody>
                                </table>
                            </div>

                        @else

                            <div class="empty-state">
                                <i class="ri-book-open-line"></i>
                                <p>No hay materias registradas para este año.</p>
                            </div>

                        @endif

                    </div>

                @endforeach


        </form>
    </main>

<script>

    document.addEventListener("DOMContentLoaded", function () {

        const btnModificar = document.getElementById("btn-modificar");
        const btnGuardar   = document.getElementById("btn-guardar");
        const btnCancelar  = document.getElementById("btn-cancelar");

        const form = document.getElementById("form-carrera");

        const alertAjax = document.getElementById("alert-ajax");

        const camposEditables = Array.from(
            form.querySelectorAll("input[name], select[name], textarea[name]")
        ).filter(el => el.name !== "_token" && el.name !== "_method");

        let valoresOriginales = {};

        // ===========================
        // DATATABLES (una por año, mismo estilo que el resto del sistema)
        // ===========================

        document.querySelectorAll(".tabla-anio").forEach(function (tabla) {

            $(tabla).DataTable({
                dom: "t",
                paging: false,
                order: [[0, "asc"]],
            });

        });

        // ===========================
        // GUARDAR VALORES ORIGINALES
        // ===========================

        function guardarValoresOriginales() {
            camposEditables.forEach(function (campo) {
                valoresOriginales[campo.name] = campo.value;
            });
        }

        function restaurarValoresOriginales() {
            camposEditables.forEach(function (campo) {
                campo.value = valoresOriginales[campo.name] ?? "";
            });
        }

        guardarValoresOriginales();

        // ===========================
        // HABILITAR / DESHABILITAR EDICIÓN
        // ===========================

        function habilitarEdicion() {
            camposEditables.forEach(function (campo) {
                campo.disabled = false;
            });
            btnModificar.innerHTML = '<i class="ri-edit-line"></i> Editando...';
            btnModificar.classList.add("btn-editing");
            btnGuardar.style.display  = "";
            btnCancelar.style.display = "";
        }

        function deshabilitarEdicion() {
            camposEditables.forEach(function (campo) {
                campo.disabled = true;
            });
            btnModificar.innerHTML = '<i class="ri-edit-line"></i> Modificar';
            btnModificar.classList.remove("btn-editing");
            btnGuardar.style.display  = "none";
            btnCancelar.style.display = "none";
        }

        btnModificar.addEventListener("click", function () {
            habilitarEdicion();
        });

        // ===========================
        // CANCELAR
        // ===========================

        btnCancelar.addEventListener("click", function () {
            restaurarValoresOriginales();
            deshabilitarEdicion();
            limpiarErrores();
            alertAjax.style.display = "none";
        });

        // ===========================
        // ALERTA
        // ===========================

        function mostrarAlerta(mensaje, tipo) {

            alertAjax.textContent = mensaje;
            alertAjax.className = "alert alert-" + tipo;
            alertAjax.style.display = "block";

            alertAjax.scrollIntoView({
                behavior: "smooth",
                block: "nearest"
            });

            if (tipo === "success") {
                setTimeout(function () {
                    alertAjax.style.display = "none";
                }, 5000);
            }
        }

        // ===========================
        // LIMPIAR ERRORES
        // ===========================

        function limpiarErrores() {

            document.querySelectorAll(".error-message").forEach(function (error) {
                error.textContent = "";
            });

            document.querySelectorAll(".input-error").forEach(function (input) {
                input.classList.remove("input-error");
            });

        }

        camposEditables.forEach(function (campo) {

            const evento = campo.tagName === "SELECT" ? "change" : "input";

            campo.addEventListener(evento, function () {

                campo.classList.remove("input-error");

                const error = document.getElementById("error-" + campo.id);

                if (error) {
                    error.textContent = "";
                }

            });

        });

        // ===========================
        // GUARDAR
        // ===========================

        btnGuardar.addEventListener("click", function () {

            limpiarErrores();

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<i class="ri-loader-4-line"></i> Guardando...';

            const formData = new FormData(form);
            const datos = {};
            formData.forEach((valor, clave) => {
                if (clave !== '_token' && clave !== '_method') datos[clave] = valor;
            });

            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute('content');

            fetch('{{ route('carreras.update', $carrera->id_carrera) }}', {

                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                    "Accept": "application/json"
                },
                body: JSON.stringify(datos)

            })
            .then(function (response) {
                return response.json().then(function (data) {
                    return { status: response.status, data: data };
                });
            })
            .then(function ({ status, data }) {

                btnGuardar.disabled = false;
                btnGuardar.innerHTML = '<i class="ri-save-line"></i> Guardar';

                if (data.success) {

                    guardarValoresOriginales();
                    deshabilitarEdicion();
                    mostrarAlerta("✓ " + data.message, "success");

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

                        mostrarAlerta("Hay campos con errores.", "error");

                    } else {

                        mostrarAlerta(data.message ?? "Ocurrió un error.", "error");

                    }

                }

            })
            .catch(function () {

                btnGuardar.disabled = false;
                btnGuardar.innerHTML = '<i class="ri-save-line"></i> Guardar';

                mostrarAlerta("Error de conexión.", "error");

            });

        });

    });

</script>

@include('partials.footer')

</body>

</html>
