<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ingresar Alumno</title>
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
    <link rel="stylesheet" href="{{ asset('css/Secretaria/ingModAlumno.css') }}">
</head>

<body>
    <button class="mobile-menu-btn" id="menu-btn">
        <i class="ri-menu-line"></i>
    </button>

    @include('partials.sidebar')

    <div class="main">

        <!-- href no anda para el button, hay que acordarse de que se usa onclick para button y href para a -->
        <button class="btn-modificar" type="button" onclick="window.location='{{ route('alumnos.index') }}'">
            <i class="ri-arrow-left-line"></i>Volver
        </button>

        <div class="form-container">

            <!-- HEADER -->
            <div class="perfil-header">
                <h1>Ingresar Alumno</h1>
            </div>

            <div id="alert-ajax" class="alert" style="display:none;"></div>

            <form class="alumno-form" id="form-alumno"
                data-ajax-form
                data-store-url="{{ route('alumnos.store') }}"
                data-redirect-url="{{ route('alumnos.index') }}">
                @csrf

                <div class="form-grid">
                    <label for="apellido">Apellido *</label>
                    <div class="input-group">
                        <input type="text" name="apellido" id="apellido" placeholder="Apellido" required>
                        <small class="error-message" id="error-apellido"></small>
                    </div>

                    <label for="nombre">Nombre *</label>
                    <div class="input-group">
                    <input type="text" name="nombre" id="nombre" placeholder="Nombre" required>
                    <small class="error-message" id="error-nombre"></small>
                    </div>

                    <label for="id_tipo_dni">Tipo de documento *</label>
                    <div class="input-group">
                    <select name="id_tipo_dni" id="id_tipo_dni" required>
                        <option value="" disabled selected hidden>Seleccionar tipo de documento</option>
                        @foreach ($tiposDni as $tipo)
                            <option value="{{ $tipo->id_tipo_dni }}">{{ $tipo->descripcion }}</option>
                        @endforeach
                    </select>
                    <small class="error-message" id="error-id_tipo_dni"></small>
                    </div>

                    <label for="dni">Documento *</label>
                    <div class="input-group">
                    <input type="text" name="dni" id="dni" placeholder="Número de documento" required>
                    <small class="error-message" id="error-dni"></small>
                    </div>

                    <label for="cuil">CUIL/CUIT</label>
                    <div class="input-group">
                    <input type="text" name="cuil" id="cuil" placeholder="CUIL/CUIT">
                    <small class="error-message" id="error-cuil"></small>
                    </div>

                    <label for="fecha_nacimiento">Fecha de nacimiento</label>
                    <div class="input-group">
                    <input type="date" name="fecha_nacimiento" id="fecha_nacimiento">
                    <small class="error-message" id="error-fecha_nacimiento"></small>
                    </div>

                    <label for="genero">Género</label>
                    <div class="input-group">
                    <select name="genero" id="genero">
                        <option disabled selected hidden>Seleccionar</option>
                        <option value="M">Masculino</option>
                        <option value="F">Femenino</option>
                        <option value="Otro">Otro</option>
                    </select>
                    <small class="error-message" id="error-genero"></small>
                    </div>

                    <label for="id_pais">Nacionalidad</label>
                    <div class="input-group">
                    <select name="id_pais" id="id_pais">
                        <option disabled selected hidden>Seleccionar nacionalidad</option>
                        @foreach ($paises as $pais)
                            <option value="{{ $pais->id_pais }}">{{ $pais->nombre_pais }}</option>
                        @endforeach
                    </select>
                    <small class="error-message" id="error-id_pais"></small>
                    </div>

                    <label for="id_localidad">Localidad</label>
                    <div class="input-group">
                    <select name="id_localidad" id="id_localidad">
                        <option disabled selected hidden>Seleccionar localidad</option>
                        @foreach ($localidades as $localidad)
                            <option value="{{ $localidad->id_localidad }}">
                                {{ $localidad->nombre_localidad }}
                                @if($localidad->pais)
                                    ({{ $localidad->pais->nombre_pais }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    <small class="error-message" id="error-id_localidad"></small>
                    </div>

                    <label for="domicilio">Domicilio</label>
                    <div class="input-group">
                    <input type="text" name="domicilio" id="domicilio" placeholder="Dirección">
                    <small class="error-message" id="error-domicilio"></small>
                    </div>

                    <label for="telefono_celular">Teléfono celular</label>
                    <div class="input-group">
                    <input type="text" name="telefono_celular" id="telefono_celular" placeholder="Teléfono celular">
                    <small class="error-message" id="error-telefono_celular"></small>
                    </div>

                    <label for="email">E-mail *</label>
                    <div class="input-group">
                    <input type="email" name="email" id="email" placeholder="Email" required>
                    <small class="error-message" id="error-email"></small>
                    </div>

                </div>

                <div class="section-divider"></div>

                <div class="login-section">
                    <h2>Inicio de Sesión</h2>
                    <div class="form-grid">

                        <label for="nombre_usuario">Nombre de usuario *</label>
                        <div class="input-group">
                        <input type="text" name="nombre_usuario" id="nombre_usuario" placeholder="Usuario" required>
                        <small class="error-message" id="error-nombre_usuario"></small>
                        </div>

                        <label for="password">Contraseña *</label>
                        <div class="input-group">
                        <input type="password" name="password" id="password" placeholder="Contraseña (mínimo 8 caracteres)" required>
                        <small class="error-message" id="error-password"></small>
                        </div>
                    </div>
                </div>

                <!-- BOTONES -->
                <div class="form-actions">
                    <button type="button" class="btn-primary" id="btn-guardar">
                        <i class="ri-save-line"></i> Guardar
                    </button>
                </div>

            </form>

        </div>
    </div>

    @include('partials.footer')

<script src="{{ asset('js/Secretaria/formularios.js') }}"></script>

</body>
</html>
