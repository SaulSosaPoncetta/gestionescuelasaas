<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Datos</title>
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
    <link rel="stylesheet" href="{{ asset('css/Secretaria/profesoresAgregar.css') }}">
</head>

<body>
    <button class="mobile-menu-btn" id="menu-btn">
        <i class="ri-menu-line"></i>
    </button>

    @include('partials.sidebar')

    <div class="main">
        <div class="form-container">

            <div class="perfil-header">
                <div><h1>Mis Datos</h1></div>
                <button class="btn-modificar" id="btn-modificar" type="button">
                    <i class="ri-edit-line"></i> Modificar
                </button>
            </div>

            <div id="alert-ajax" class="alert" style="display:none;"></div>

            @php $primerDni = $usuario->dnis->first(); @endphp

            <form class="alumno-form" id="form-perfil">
                @csrf
                @method('PUT')

                <div class="form-grid">

                    <label>Apellido</label>
                    <input type="text" name="apellido" value="{{ $usuario->apellido }}" disabled>

                    <label>Nombre</label>
                    <input type="text" name="nombre" value="{{ $usuario->nombre }}" disabled>

                    <label>Fecha de nacimiento</label>
                    <input type="date" name="fecha_nacimiento"
                        value="{{ $usuario->fecha_nacimiento ? \Carbon\Carbon::parse($usuario->fecha_nacimiento)->format('Y-m-d') : '' }}"
                        disabled>
                        
                    
                    <label>Tipo de documento</label>
                    <select name="id_tipo_dni" id="id_tipo_dni" disabled>
                        @foreach($tiposDni as $tipo)
                            <option
                                value="{{ $tipo->id_tipo_dni }}"
                                {{ ($primerDni?->id_tipo_dni == $tipo->id_tipo_dni) ? 'selected' : '' }}>
                                {{ $tipo->descripcion }}
                            </option>
                        @endforeach
                    </select>
                    <label>Documento</label>
                    <input type="text" name="dni" value="{{ $primerDni?->dni ?? '' }}" disabled>

                    <label>CUIL/CUIT</label>
                    <input type="text" name="cuil" value="{{ $primerDni?->cuil ?? '' }}" disabled>

                    <label>Nacionalidad</label>
                    <select name="id_pais" disabled>
                        <option value="">Seleccionar</option>
                        @foreach ($paises as $pais)
                            <option value="{{ $pais->id_pais }}"
                                {{ $usuario->id_pais == $pais->id_pais ? 'selected' : '' }}>
                                {{ $pais->nombre_pais }}
                            </option>
                        @endforeach
                    </select>

                    <label>Localidad</label>
                    <select name="id_localidad" disabled>
                        <option value="">Seleccionar</option>
                        @foreach ($localidades as $loc)
                            <option value="{{ $loc->id_localidad }}"
                                {{ $usuario->id_localidad == $loc->id_localidad ? 'selected' : '' }}>
                                {{ $loc->nombre_localidad }}
                                @if ($loc->pais) ({{ $loc->pais->nombre_pais }}) @endif
                            </option>
                        @endforeach
                    </select>

                    <label>Domicilio</label>
                    <input type="text" name="domicilio" value="{{ $usuario->domicilio ?? '' }}" disabled>

                    <label>Teléfono celular</label>
                    <input type="text" name="telefono_celular"
                        value="{{ $usuario->telefonos->get(0)->telefono ?? '' }}" disabled>

                    <label>E-mail</label>
                    <input type="email" name="email" value="{{ $usuario->email }}" disabled>

                    <label>Género</label>
                    <select name="genero" disabled>
                        <option value="M"    {{ $usuario->genero === 'M'    ? 'selected' : '' }}>Masculino</option>
                        <option value="F"    {{ $usuario->genero === 'F'    ? 'selected' : '' }}>Femenino</option>
                        <option value="Otro" {{ $usuario->genero === 'Otro' ? 'selected' : '' }}>Otro</option>
                    </select>

                </div>

                <div class="section-divider"></div>

                <div class="login-section">
                    <h2>Inicio de Sesión</h2>
                    <div class="form-grid">

                        <label>Nombre de usuario</label>
                        <input type="text" name="nombre_usuario"
                            value="{{ $usuario->nombre_usuario }}" disabled>

                        <label>Nueva contraseña</label>
                        <input type="password" name="password"
                            placeholder="Dejar en blanco para no cambiar" disabled>

                    </div>
                </div>

                {{-- Hidden necesarios para el backend --}}
                <input type="hidden" name="estado" value="{{ $usuario->estado }}">

                <div class="form-actions">
                    <button type="button" class="btn-primary" id="btn-guardar" disabled>
                        <i class="ri-save-line"></i> Guardar
                    </button>
                    <button type="button" class="btn-secondary" id="btn-cancelar" disabled>
                        Cancelar
                    </button>
                </div>

            </form>
        </div>
    </div>

    @include('partials.footer')

    <script>
(function () {
    const btnModificar    = document.getElementById('btn-modificar');
    const btnGuardar      = document.getElementById('btn-guardar');
    const btnCancelar     = document.getElementById('btn-cancelar');
    const form            = document.getElementById('form-perfil');
    const alertAjax       = document.getElementById('alert-ajax');
    const camposEditables = form.querySelectorAll('input:not([type="hidden"]), select');

    let valoresOriginales = {};

    function guardarValoresOriginales() {
        camposEditables.forEach(c => valoresOriginales[c.name] = c.value);
    }

    function restaurarValoresOriginales() {
        camposEditables.forEach(c => c.value = valoresOriginales[c.name] ?? '');
    }

    function habilitarEdicion() {
        camposEditables.forEach(c => c.disabled = false);
        btnModificar.innerHTML = '<i class="ri-edit-line"></i> Editando...';
        btnModificar.classList.add('btn-editing');
        btnGuardar.disabled  = false;
        btnCancelar.disabled = false;
    }

    function deshabilitarEdicion() {
        camposEditables.forEach(c => c.disabled = true);
        btnModificar.innerHTML = '<i class="ri-edit-line"></i> Modificar';
        btnModificar.classList.remove('btn-editing');
        btnGuardar.disabled  = true;
        btnCancelar.disabled = true;
    }

    function mostrarAlerta(mensaje, tipo) {
        alertAjax.textContent   = mensaje;
        alertAjax.className     = 'alert alert-' + tipo;
        alertAjax.style.display = 'block';
        setTimeout(() => alertAjax.style.display = 'none', 4000);
    }

    guardarValoresOriginales();

    btnModificar.addEventListener('click', habilitarEdicion);

    btnCancelar.addEventListener('click', function () {
        restaurarValoresOriginales();
        deshabilitarEdicion();
        alertAjax.style.display = 'none';
    });

    btnGuardar.addEventListener('click', function () {
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<i class="ri-loader-4-line"></i> Guardando...';

        const formData = new FormData(form);
        const datos    = {};
        formData.forEach((valor, clave) => {
            if (clave !== '_token' && clave !== '_method') datos[clave] = valor;
        });

        const telefonos = [];
        if (datos['telefono_celular']) telefonos.push(datos['telefono_celular']);
        datos['telefonos'] = telefonos;
        delete datos['telefono_celular'];

        datos['dni']      = document.querySelector('input[name="dni"]:not([type="hidden"])').value;
        datos['tipo_dni'] = document.querySelector('select[name="id_tipo_dni"]').value;

        fetch('{{ route("perfil.update") }}', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify(datos),
        })
        .then(r => {
            if (!r.ok) {
                return r.text().then(text => {
                    mostrarAlerta('Error HTTP ' + r.status + ': ' + text.substring(0, 300), 'error');
                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = '<i class="ri-save-line"></i> Guardar';
                    throw new Error('HTTP ' + r.status);
                });
            }
            return r.json().then(data => ({ status: r.status, data }));
        })
        .then(({ data }) => {
            if (data.success) {
                guardarValoresOriginales();
                deshabilitarEdicion();
                btnGuardar.innerHTML = '<i class="ri-save-line"></i> Guardar';
                mostrarAlerta('✓ ' + data.message, 'success');
            } else {
                let msg = 'Errores de validación: ';
                if (data.errors) {
                    Object.entries(data.errors).forEach(([campo, errs]) => {
                        msg += campo + ': ' + errs.join(', ') + ' | ';
                    });
                }
                mostrarAlerta(msg, 'error');
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = '<i class="ri-save-line"></i> Guardar';
            }
        })
        .catch(err => {
            mostrarAlerta('Error: ' + err.message, 'error');
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<i class="ri-save-line"></i> Guardar';
        });
    }); 

})(); 
</script>

</body>
</html>