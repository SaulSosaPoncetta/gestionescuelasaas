<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Carrera</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="{{ asset('fonts/remixicon/remixicon.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/sideBar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filtro.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tablas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/botones.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/materiaPerfil.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/profesoresAgregar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/carreraSec.css') }}">
</head>

<body>
<button class="mobile-menu-btn" id="menu-btn">
    <i class="ri-menu-line"></i>
</button>
@include('partials.sidebar')

<div class="main">
    <div class="form-container">

        <!-- HEADER -->
        <div class="perfil-header">
            <h1>Agregar Carrera</h1>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- FORM -->
        <form
            class="materia-form"
            method="POST"
            action="{{ route('carreras.store') }}">
            @csrf

            <div class="form-grid">

                <label>Nombre*</label>
                <input type="text" name="nombre" placeholder="Nombre de la carrera">

                <label>Título*</label>
                <input type="text" name="titulo" placeholder="Título que otorga">

            </div>

            <div class="section-divider"></div>

            <div class="login-section">
                <h2>Plan de estudio</h2>

                <div class="form-grid">

                    <label>Plan de estudio*</label>
                    <select name="id_plan" required>
                        <option value="" disabled selected>Seleccionar plan</option>
                        @foreach($planesEstudio as $plan)
                            <option value="{{ $plan->id_plan }}">{{ $plan->titulo }}</option>
                        @endforeach
                    </select>

                    <label>Resolución*</label>
                    <select name="id_resolucion" required>
                        <option value="" disabled selected>Seleccionar resolución</option>
                        @foreach($resoluciones as $resolucion)
                            <option value="{{ $resolucion->id_resolucion }}">{{ $resolucion->descripcion }}</option>
                        @endforeach
                    </select>

                </div>
            </div>

            <!-- BOTONES -->
            <div class="form-actions">
                <button type="button" class="btn-secondary"
                        onclick="window.location.href='{{ route('carreras.index') }}'">
                    Volver
                </button>

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
    const form        = document.querySelector('.materia-form');
    const btnGuardar   = document.getElementById('btn-guardar');
    const csrf         = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Alerta de éxito reutilizable
    function mostrarExito(mensaje) {
        let alerta = document.getElementById('alerta-ajax');
        if (!alerta) {
            alerta = document.createElement('div');
            alerta.id = 'alerta-ajax';
            alerta.className = 'alert alert-success';
            form.parentElement.insertBefore(alerta, form);
        }
        alerta.className = 'alert alert-success';
        alerta.textContent = mensaje;
        alerta.style.display = 'block';
        setTimeout(() => alerta.style.display = 'none', 4000);
    }

    function mostrarError(mensaje) {
        let alerta = document.getElementById('alerta-ajax');
        if (!alerta) {
            alerta = document.createElement('div');
            alerta.id = 'alerta-ajax';
            alerta.className = 'alert alert-danger';
            form.parentElement.insertBefore(alerta, form);
        }
        alerta.className = 'alert alert-danger';
        alerta.textContent = mensaje;
        alerta.style.display = 'block';
        setTimeout(() => alerta.style.display = 'none', 5000);
    }

    btnGuardar.addEventListener('click', function () {
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<i class="ri-loader-4-line"></i> Guardando...';

        // Recolectar datos del form
        const formData = new FormData(form);
        const datos = {};
        formData.forEach((valor, clave) => {
            if (clave !== '_token') datos[clave] = valor;
        });

        fetch('{{ route("carreras.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body: JSON.stringify(datos),
        })
        .then(r => r.json().then(data => ({ status: r.status, data })))
        .then(({ status, data }) => {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<i class="ri-save-line"></i> Guardar';

            if (data.success) {
                mostrarExito('✓ ' + data.message);
                form.reset(); // limpia todos los inputs
            } else {
                if (data.errors) {
                    const mensajes = Object.values(data.errors).flat().join('\n');
                    mostrarError('Errores:\n' + mensajes);
                } else {
                    mostrarError('Error al guardar. Revisá los datos.');
                }
            }
        })
        .catch(() => {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<i class="ri-save-line"></i> Guardar';
            mostrarError('Error de conexión.');
        });
    });
})();
</script>
</body>
</html>