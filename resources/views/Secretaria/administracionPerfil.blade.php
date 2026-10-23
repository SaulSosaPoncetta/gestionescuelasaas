<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil de Administrador</title>
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
    @include('partials.sidebar')

    <div class="main">

        <!-- href no anda para el button, hay que acordarse de que se usa onclick para button y href para a -->
        <button class="btn-modificar" type="button" onclick="window.location='{{ route('administracionSec') }}'">
            <i class="ri-arrow-left-line"></i>Volver
        </button>

        <div class="form-container">

            <div class="perfil-header">
                <div>
                    <h1>Administrador</h1>
                </div>
                <button class="btn-modificar" id="btn-modificar" type="button">
                    <i class="ri-edit-line"></i> Modificar
                </button>
            </div>

            @if (session('success'))
                <div class="alert alert-success" id="alert-success">
                    <i class="ri-checkbox-circle-line"></i> {{ session('success') }}
                </div>
            @endif

            <div id="alert-ajax" class="alert" style="display:none;"></div>

            @php $primerDni = $dni->first(); @endphp

            <form class="alumno-form" id="form-administrador"
            data-perfil-editable
            data-update-url="{{ route('administradores.update', $administrador->id_usuario) }}">
            
                @csrf
                @method('PUT')

                <div class="form-grid">

                    <label for="apellido">Apellido</label>
                    <div class="input-group">
                        <input
                            type="text"
                            name="apellido"
                            id="apellido"
                            value="{{ $administrador->usuario->apellido }}"
                            disabled
                        >
                        <small class="error-message" id="error-apellido"></small>
                    </div>

                    <label for="nombre">Nombre</label>
                    <div class="input-group">
                        <input
                            type="text"
                            name="nombre"
                            id="nombre"
                            value="{{ $administrador->usuario->nombre }}"
                            disabled
                        >
                        <small class="error-message" id="error-nombre"></small>
                    </div>

                    <label for="fecha_nacimiento">Fecha de nacimiento</label>
                    <div class="input-group">
                        <input
                            type="date"
                            name="fecha_nacimiento"
                            id="fecha_nacimiento"
                            value="{{ $administrador->usuario->fecha_nacimiento ? \Carbon\Carbon::parse($administrador->usuario->fecha_nacimiento)->format('Y-m-d') : '' }}"
                            disabled
                        >
                        <small class="error-message" id="error-fecha_nacimiento"></small>
                    </div>

                    <label for="id_tipo_dni">Tipo de documento</label>
                    <div class="input-group">
                        <select name="id_tipo_dni" id="id_tipo_dni" disabled>
                            <option value="">Seleccionar</option>
                            @foreach($tiposDni as $tipo)
                                <option
                                    value="{{ $tipo->id_tipo_dni }}"
                                    {{ ($primerDni?->id_tipo_dni == $tipo->id_tipo_dni) ? 'selected' : '' }}>
                                    {{ $tipo->descripcion }}
                                </option>
                            @endforeach
                        </select>
                        <small class="error-message" id="error-id_tipo_dni"></small>
                    </div>

                    <label for="dni">Documento</label>
                    <div class="input-group">
                        <input type="text" name="dni" id="dni" value="{{ $primerDni?->dni ?? '' }}" disabled>
                        <small class="error-message" id="error-dni"></small>
                    </div>

                    <label for="cuil">CUIL/CUIT</label>
                    <div class="input-group">
                        <input
                            type="text"
                            id="cuil"
                            name="cuil"
                            value="{{ $primerDni?->cuil ?? '' }}"
                            disabled
                        >
                        <small class="error-message" id="error-cuil"></small>
                    </div>

                    <label for="genero">Género</label>
                    <div class="input-group">
                        <select name="genero" id="genero" disabled>
                            <option value="">Seleccionar</option>
                            <option value="M"    {{ $administrador->usuario->genero === 'M'    ? 'selected' : '' }}>Masculino</option>
                            <option value="F"    {{ $administrador->usuario->genero === 'F'    ? 'selected' : '' }}>Femenino</option>
                            <option value="Otro" {{ $administrador->usuario->genero === 'Otro' ? 'selected' : '' }}>Otro</option>
                        </select>
                        <small class="error-message" id="error-genero"></small>
                    </div>

                    <label for="id_pais">Nacionalidad</label>
                    <div class="input-group">
                        <select name="id_pais" id="id_pais" disabled>
                            <option value="">Seleccionar</option>
                            @foreach ($paises as $pais)
                                <option
                                    value="{{ $pais->id_pais }}"
                                    {{ $administrador->usuario->id_pais == $pais->id_pais ? 'selected' : '' }}
                                >
                                    {{ $pais->nombre_pais }}
                                </option>
                            @endforeach
                        </select>
                        <small class="error-message" id="error-id_pais"></small>
                    </div>

                    <label for="id_localidad">Localidad</label>
                    <div class="input-group">
                        <select name="id_localidad" id="id_localidad" disabled>
                            <option value="">Seleccionar</option>
                            @foreach ($localidades as $loc)
                                <option
                                    value="{{ $loc->id_localidad }}"
                                    {{ $administrador->usuario->id_localidad == $loc->id_localidad ? 'selected' : '' }}
                                >
                                    {{ $loc->nombre_localidad }}
                                    @if ($loc->pais)
                                        ({{ $loc->pais->nombre_pais }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <small class="error-message" id="error-id_localidad"></small>
                    </div>

                    <label for="domicilio">Domicilio</label>
                    <div class="input-group">
                        <input
                            type="text"
                            name="domicilio"
                            id="domicilio"
                            value="{{ $administrador->usuario->domicilio ?? '' }}"
                            disabled
                        >
                        <small class="error-message" id="error-domicilio"></small>
                    </div>

                    <label for="telefono">Teléfono celular</label>
                    <div class="input-group">
                        <input
                            type="text"
                            name="telefono"
                            id="telefono"
                            value="{{ $telefonos->get(0)?->telefono ?? '' }}"
                            disabled
                        >
                        <small class="error-message" id="error-telefono"></small>
                    </div>

                    <label for="email">E-mail</label>
                    <div class="input-group">
                        <input
                            type="email"
                            name="email"
                            id="email"
                            value="{{ $administrador->usuario->email }}"
                            disabled
                        >
                        <small class="error-message" id="error-email"></small>
                    </div>

                    <label for="estado">Estado</label>
                    <div class="input-group">
                        <select name="estado" id="estado" disabled>
                            <option value="Activo"   {{ $administrador->usuario->estado === 'Activo'   ? 'selected' : '' }}>Activo</option>
                            <option value="Inactivo" {{ $administrador->usuario->estado === 'Inactivo' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                        <small class="error-message" id="error-estado"></small>
                    </div>

                </div>

                <div class="section-divider"></div>

                <div class="login-section">
                    <h2>Inicio de Sesión</h2>
                    <div class="form-grid">

                        <label for="nombre_usuario">Nombre de usuario</label>
                        <div class="input-group">
                            <input
                                type="text"
                                name="nombre_usuario"
                                id="nombre_usuario"
                                value="{{ $administrador->usuario->nombre_usuario }}"
                                disabled
                            >
                            <small class="error-message" id="error-nombre_usuario"></small>
                        </div>

                        <label for="password">Nueva contraseña</label>
                        <div class="input-group">
                            <input
                                type="password"
                                name="password"
                                id="password"
                                placeholder="Dejar en blanco para no cambiar"
                                disabled
                            >
                            <small class="error-message" id="error-password"></small>
                        </div>

                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-primary" id="btn-guardar" style="display:none;">
                        <i class="ri-save-line"></i> Guardar
                    </button>
                    <button type="button" class="btn-secondary" id="btn-cancelar" style="display:none;">
                        Cancelar
                    </button>
                </div>

            </form>
        </div>

    </div>

    @include('partials.footer')

    <script src="{{ asset('js/Secretaria/perfilEditable.js') }}"></script>
</body>
</html>
