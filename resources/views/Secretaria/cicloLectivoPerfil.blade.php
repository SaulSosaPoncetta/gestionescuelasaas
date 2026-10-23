<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil Ciclo Lectivo</title>
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

<div class="main">

    <button class="btn-modificar" type="button" onclick="window.location='{{ route('cicloLectivoSec') }}'"><i class="ri-arrow-left-line"></i>Volver</button>

    <div class="form-container">

        <div class="perfil-header">

            <h1>Ciclo Lectivo</h1>

            <button class="btn-modificar" id="btn-modificar" type="button"><i class="ri-edit-line"></i>Modificar</button>

        </div>

        <div id="alert-ajax" class="alert" style="display:none;"></div>

        <form id="form-ciclo">

            @csrf
            @method('PUT')

            <div class="form-grid">

                <label for="anio">Año</label>

                <div class="input-group">
                    <div class="input-with-help">
                        <input
                            type="number"
                            id="anio"
                            name="anio"
                            value="{{ $ciclo->anio }}"
                            disabled>

                        <div class="help-placeholder"></div>

                    </div>
                    <small id="error-anio" class="error-message"></small>
                </div>

                <label for="fecha_inicio">Fecha inicio</label>

                <div class="input-group">
                    <div class="input-with-help">
                        <input
                            type="date"
                            id="fecha_inicio"
                            name="fecha_inicio"
                            value="{{$ciclo->fecha_inicio?->format('Y-m-d')}}"
                            disabled>

                        <div class="help-icon">
                            <i class="ri-information-line"></i>
                            <div class="help-tooltip">
                                Fecha en la que comienza el ciclo lectivo.
                            </div>
                        </div>

                    </div>
                    <small id="error-fecha_inicio" class="error-message"></small>
                </div>

                <label for="fecha_fin">Fecha fin</label>

                <div class="input-group">
                    <div class="input-with-help">
                        <input
                            type="date"
                            id="fecha_fin"
                            name="fecha_fin"
                            value="{{ $ciclo->fecha_fin?->format('Y-m-d') }}"
                            disabled>

                        <div class="help-icon">
                            <i class="ri-information-line"></i>
                            <div class="help-tooltip">
                                Debe ser posterior a la fecha de inicio.
                            </div>
                        </div>

                    </div>
                    <small id="error-fecha_fin" class="error-message"></small>
                </div>

                <label for="inicio_matriculacion">Inicio matriculación</label>

                <div class="input-group">
                    <div class="input-with-help">
                        <input
                            type="date"
                            id="inicio_matriculacion"
                            name="inicio_matriculacion"
                            value="{{ $ciclo->inicio_matriculacion?->format('Y-m-d') }}"
                            disabled>

                        <div class="help-icon">
                            <i class="ri-information-line"></i>
                            <div class="help-tooltip">
                                Desde esta fecha los alumnos pueden comenzar a inscribirse.
                            </div>
                        </div>

                    </div>
                    <small id="error-inicio_matriculacion" class="error-message"></small>
                </div>

                <label for="fin_matriculacion">Fin matriculación</label>

                <div class="input-group">
                    <div class="input-with-help">
                        <input
                            type="date"
                            id="fin_matriculacion"
                            name="fin_matriculacion"
                            value="{{ $ciclo->fin_matriculacion?->format('Y-m-d') }}"
                            disabled>

                        <div class="help-icon">
                            <i class="ri-information-line"></i>
                            <div class="help-tooltip">
                                Último día disponible para la inscripción de alumnos.
                            </div>
                        </div>

                    </div>
                    <small id="error-fin_matriculacion" class="error-message"></small>
                </div>
                <label>Estado de matrícula</label>

                <div class="input-group">
                    @if($ciclo->matriculacionAbierta())
                        <span style="color:green; font-weight:bold;">
                             Abierta
                        </span>
                    @else
                        <span style="color:red; font-weight:bold;">
                             Cerrada
                        </span>
                    @endif
                </div>
            </div>

            <div class="form-actions">

                <button type="button" class="btn-primary" id="btn-guardar" style="display:none;"><i class="ri-save-line"></i>Guardar</button>

                <button type="button" class="btn-secondary" id="btn-cancelar" style="display:none;">Cancelar</button>

            </div>
        </form>
    </div>
</div>

@include('partials.footer')

<script>
(function () {

    const btnModificar = document.getElementById('btn-modificar');
    const btnGuardar   = document.getElementById('btn-guardar');
    const btnCancelar  = document.getElementById('btn-cancelar');
    const form         = document.getElementById('form-ciclo');
    const alertAjax    = document.getElementById('alert-ajax');

    const camposEditables = Array.from(
        form.querySelectorAll('input[name], select[name]')
    ).filter(el => el.name !== '_token' && el.name !== '_method');

    let valoresOriginales = {};

    function guardarValoresOriginales() {
        camposEditables.forEach(function (campo) {
            valoresOriginales[campo.name] = campo.value;
        });
    }

    function restaurarValoresOriginales() {
        camposEditables.forEach(function (campo) {
            campo.value = valoresOriginales[campo.name] ?? '';
        });
    }

    function habilitarEdicion() {
        camposEditables.forEach(function (campo) {
            campo.disabled = false;
        });

        btnModificar.innerHTML =
            '<i class="ri-edit-line"></i> Editando...';

        btnModificar.classList.add('btn-editing');

        btnGuardar.style.display = '';
        btnCancelar.style.display = '';
    }

    function deshabilitarEdicion() {
        camposEditables.forEach(function (campo) {
            campo.disabled = true;
        });

        btnModificar.innerHTML =
            '<i class="ri-edit-line"></i> Modificar';

        btnModificar.classList.remove('btn-editing');

        btnGuardar.style.display = 'none';
        btnCancelar.style.display = 'none';
    }

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

        document.querySelectorAll(".error-message")
            .forEach(function (e) {
                e.textContent = "";
            });

        document.querySelectorAll(".input-error")
            .forEach(function (i) {
                i.classList.remove("input-error");
            });

    }

    guardarValoresOriginales();

    btnModificar.addEventListener('click', function () {

        habilitarEdicion();

    });

    btnCancelar.addEventListener('click', function () {

        restaurarValoresOriginales();

        deshabilitarEdicion();

        limpiarErrores();

        alertAjax.style.display = "none";

    });

    btnGuardar.addEventListener('click', function () {

        limpiarErrores();

        btnGuardar.disabled = true;

        btnGuardar.innerHTML =
            '<i class="ri-loader-4-line"></i> Guardando...';

        const datos = {};

        camposEditables.forEach(function (campo) {

            datos[campo.name] = campo.value;

        });

        const csrfToken =
            document.querySelector('meta[name="csrf-token"]')
                    .getAttribute('content');

        fetch("{{ route('cicloLectivo.update', $ciclo->id_ciclo) }}", {

            method: 'PUT',

            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },

            body: JSON.stringify(datos)

        })
        .then(function(response){

            return response.json().then(function(data){

                return {
                    status: response.status,
                    data: data
                };

            });

        })
        .then(function({status,data}){

            btnGuardar.disabled = false;

            btnGuardar.innerHTML =
                '<i class="ri-save-line"></i> Guardar';

            if(data.success){

                guardarValoresOriginales();
                deshabilitarEdicion();
                mostrarAlerta("✓ " + data.message, "success");

            } else {

                if(data.errors){

                    Object.entries(data.errors).forEach(function([campo,errores]){

                        const input = document.getElementById(campo);
                        if(input){ input.classList.add("input-error"); }

                        const error = document.getElementById("error-"+campo);
                        if(error){ error.textContent = errores[0]; }

                    });

                    mostrarAlerta("Hay campos con errores.", "error");

                } else {

                    mostrarAlerta(data.message ?? "Ocurrió un error.", "error");

                }

            }

        })
        .catch(function(){

            btnGuardar.disabled = false;

            btnGuardar.innerHTML =
                '<i class="ri-save-line"></i> Guardar';

            mostrarAlerta("Error de conexión.", "error");

        });

    });

    camposEditables.forEach(function(campo){

        const evento =
            campo.tagName==="SELECT"
            ? "change"
            : "input";

        campo.addEventListener(evento,function(){

            campo.classList.remove("input-error");

            const error =
                document.getElementById(
                    "error-"+campo.id
                );

            if(error){

                error.textContent="";

            }

        });

    });

})();
</script>

</body>
</html>
