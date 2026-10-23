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
            </form>
        </div>
    </div>

    @include('partials.footer')

</body>
</html>
