<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Plan de Estudio</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('fonts/remixicon/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/sideBar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filtro.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tablas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/botones.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/planEstudio.css') }}">
</head>

<body>
<button class="mobile-menu-btn" id="menu-btn">
    <i class="ri-menu-line"></i>
</button>
@include('partials.sidebar')

<div class="main">

    <!-- Volver fuera de .form-container: mismo nivel que en el listado -->
    <div class="top-actions">
        <button class="btn-modificar" type="button" onclick="window.location='{{ route('planEstudio.index') }}'">
            <i class="ri-arrow-left-line"></i>Volver
        </button>
    </div>

    <div class="form-container">

        <div class="perfil-header">
            <h1>Agregar Plan de Estudio</h1>
        </div>

        <div id="alerta-ajax" class="alert" style="display:none;"></div>

        <form class="materia-form" id="form-plan-estudio" enctype="multipart/form-data">
            @csrf

            <div class="form-grid">

                <label for="titulo">Título del plan *</label>
                <div class="input-group">
                    <input type="text" name="titulo" id="titulo" placeholder="Ej: Plan 2024">
                    <small class="error-message" id="error-titulo"></small>
                </div>

                <label for="plan_descripcion">Descripción del plan</label>
                <div class="input-group">
                    <input type="text" name="plan_descripcion" id="plan_descripcion" placeholder="Descripción (opcional)">
                    <small class="error-message" id="error-plan_descripcion"></small>
                </div>

                <label for="id_carrera">Carrera</label>
                <div class="input-group">
                    <select name="id_carrera" id="id_carrera">
                        <option value="{{ $carreraSinAsignar->id_carrera }}" selected>Sin asignar (Opcional)</option>
                        @foreach($carreras as $carrera)
                            <option value="{{ $carrera->id_carrera }}">{{ $carrera->nombre }}</option>
                        @endforeach
                    </select>
                    <small class="error-message" id="error-id_carrera"></small>
                </div>

                <label for="total_horas">Años de duración *</label>
                <div class="input-group">
                    <input type="number" name="total_horas" id="total_horas" placeholder="Ej: 4" min="1">
                    <small class="error-message" id="error-total_horas"></small>
                </div>

            </div>

            <div class="section-divider"></div>

            <div class="login-section">
                <h2>Resolución</h2>
                <div class="form-grid">

                    <label for="resolucion_descripcion">Resolución *</label>
                    <div class="input-group">
                        <input type="text" name="resolucion_descripcion" id="resolucion_descripcion" placeholder="Ej: Resolución 123/2024">
                        <small class="error-message" id="error-resolucion_descripcion"></small>
                    </div>

                    <label for="fecha">Fecha *</label>
                    <div class="input-group">
                        <input type="date" name="fecha" id="fecha">
                        <small class="error-message" id="error-fecha"></small>
                    </div>

                </div>
            </div>

            <div class="section-divider"></div>

            <div class="login-section">
                <h2>Archivo</h2>
                <div class="form-grid">

                    <label for="archivo_descripcion">Descripción del archivo *</label>
                    <div class="input-group">
                        <input type="text" name="archivo_descripcion" id="archivo_descripcion" placeholder="Ej: Resolución 123/2024 - PDF">
                        <small class="error-message" id="error-archivo_descripcion"></small>
                    </div>

                    <label for="archivo">Archivo (PDF) *</label>
                    <div class="input-group">
                        <input type="file" name="archivo" id="archivo" accept="application/pdf">
                        <small class="error-message" id="error-archivo"></small>
                    </div>

                </div>
            </div>

            <div class="form-actions">
                
                <button type="button" class="btn-primary" id="btn-guardar">
                    <i class="ri-save-line"></i> Guardar
                </button>
            </div>

        </form>

    </div>
</div>

@include('partials.footer')
<script>
(function () {
    const form       = document.getElementById('form-plan-estudio');
    const btnGuardar = document.getElementById('btn-guardar');
    const csrf       = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const alerta     = document.getElementById('alerta-ajax');

    function mostrarAlerta(mensaje, tipo) {
        alerta.className = 'alert alert-' + tipo;
        alerta.textContent = mensaje;
        alerta.style.display = 'block';
        alerta.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function limpiarErrores() {
        form.querySelectorAll('.error-message').forEach(el => el.textContent = '');
        form.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
    }

    btnGuardar.addEventListener('click', function () {
        limpiarErrores();
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<i class="ri-loader-4-line"></i> Guardando...';

        const formData = new FormData(form);

        fetch('{{ route('planEstudio.store') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body: formData,
        })
        .then(r => r.json().then(data => ({ status: r.status, data })))
        .then(({ data }) => {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<i class="ri-save-line"></i> Guardar';

            if (data.success) {
                mostrarAlerta('✓ ' + data.message, 'success');
                setTimeout(() => { window.location.href = '{{ route('planEstudio.index') }}'; }, 800);
            } else if (data.errors) {
                Object.entries(data.errors).forEach(([campo, mensajes]) => {
                    const input = document.getElementById(campo);
                    if (input) input.classList.add('input-error');
                    const error = document.getElementById('error-' + campo);
                    if (error) error.textContent = mensajes[0];
                });
                mostrarAlerta('Hay campos con errores. Revisá el formulario.', 'error');
            } else {
                mostrarAlerta(data.message || 'Ocurrió un error.', 'error');
            }
        })
        .catch(() => {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<i class="ri-save-line"></i> Guardar';
            mostrarAlerta('Error de conexión. Intentá de nuevo.', 'error');
        });
    });
})();
</script>
</body>
</html>