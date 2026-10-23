<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Egresados</title>
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

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/Filtro.js'])
</head>

<body>
    <button class="mobile-menu-btn" id="menu-btn">
        <i class="ri-menu-line"></i>
    </button>

    @include('partials.sidebar')

    <div class="main">
        <!-- href no anda para el button, hay que acordarse de que se usa onclick para button y href para a -->
        <button class="btn-modificar" type="button" onclick="window.location='{{ route('planDeEstudio') }}'">
            <i class="ri-arrow-left-line"></i>Volver
        </button>

        <div class="form-container">

            <!-- HEADER -->
            <div class="perfil-header">
                <div style="display:flex; align-items:center; gap:12px;">
                    <!-- BACK: Agregar nombre del plan en cuestión despues -->
                    <h1>Egresados</h1>
                </div>
            </div>

            <form class="alumno-form" id="form-alumno">
                @csrf
                @method('PUT')

                <div class="form-grid">

                    <label>Carreras</label>
                    <div class="input-group">
                        <select>
                            <option value="">Seleccionar</option>
                            <option value="Sis y Org I">Analisis de sistemas</option>
                            <option value="Base de datos">Recursos humanos</option>
                            <option value="Base de datos">Psicopedagogia</option>
                            <option value="Base de datos">Minería</option>
                        </select>
                    </div>

                    <label>Año</label>
                    <div class="input-group">
                        <select>
                            <option value="">Seleccionar</option>
                            <option value="2026">2026</option>
                            <option value="2025">2025</option>
                            <option value="2024">2024</option>
                        </select>
                    </div>

                    <label>Cohorte</label>
                    <div class="input-group">
                        <select>
                            <option value="">Seleccionar</option>
                            <option value="2026C">2026</option>
                            <option value="2025C">2025</option>
                            <option value="2024C">2024</option>
                        </select>
                    </div>

                </div>
            </form>
        </div>

        <div class="table-container">
            <table id="tablaAlumnos" class="cursada-table">
                <thead>
                    <tr>
                        <th>Codigo</th>
                        <th>Apellido y Nombre</th>
                        <th>Documento</th>
                        <th>Fecha recibido</th>
                        <th>Promedio</th>
                        <th>Perfil</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>0802</td>
                        <td>Miguel Rodriguez</td>
                        <td>47659329</td>
                        <td>31/12/2016</td>
                        <td>7.5</td>
                        <td>
                            <button
                                class="btn-primary"
                                onclick="window.location.href=''">
                                Ver
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    @include('partials.footer')

</body>
</html>