<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Analitico</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('fonts/remixicon/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/sideBar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filtro.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tablas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/botones.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/alumnosSec.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/materiaPerfil.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/analiticoAlumno.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/planEstudio.css') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/Filtro.js'])
</head>

<body>
    <button class="mobile-menu-btn" id="menu-btn">
        <i class="ri-menu-line"></i>
    </button>

    @include('partials.sidebar')

    <div class="main">

        <button class="btn-modificar" type="button" onclick="window.location='{{ route('alumnos.index') }}'">
            <i class="ri-arrow-left-line"></i>Volver
        </button>
        <br>

        <!-- HEADER -->
        <div class="top-bar">
            {{-- TODO-BACK: reemplazar el texto fijo por {{ $nombreAlumno }} que venga del controller --}}
            <h1>Analítico - Juan Pérez</h1>
        </div>

        <!-- FILTROS + STATS -->
        <div class="card-container">
            <div class="analitico-top">

                <div class="form-grid">
                    {{--
                        TODO-BACK: armar con @foreach($carreras as $carrera) las <option>
                        reales del alumno. Si solo tiene una carrera, ver si conviene
                        ocultar el <select> directamente desde el blade (@if(count($carreras) > 1)).

                        Cuando el alumno tenga más de una carrera y cambie el select, va a
                        hacer falta recargar la página o traer por AJAX los datos de la
                        otra carrera (materias, acreditados, %) — eso es decisión de back,
                        avisen a frontend qué mecanismo eligen para adaptar el JS.
                    --}}
                    <select id="select-carrera">
                        <option value="1">Profesorado en Educación Primaria</option>
                        <option value="2">Tecnicatura en Informática</option>
                    </select>
                </div>

                <div class="analitico-stats">

                    <div class="stat-item">
                        <span class="stat-label">Espacios acreditados:</span>
                        {{-- El número lo completa el JS contando las filas "Acreditado" de la tabla --}}
                        <span class="stat-numero" id="espacios-acreditados">0</span>
                    </div>

                    <div class="stat-item stat-progreso">
                        <span class="stat-label">Porcentaje de la carrera:</span>
                        <div class="progress-ring" id="progress-ring">
                            <svg viewBox="0 0 120 120">
                                <circle class="progress-ring-bg" cx="60" cy="60" r="52"></circle>
                                <circle class="progress-ring-fill" id="progress-ring-fill" cx="60" cy="60" r="52"></circle>
                            </svg>
                            <span class="progress-ring-text" id="progress-ring-text">0%</span>
                        </div>
                        {{--
                            TODO-BACK: cuando este porcentaje llegue a 100%, el alumno
                            pasa a la tabla de egresados (pantalla /egresados). Eso es
                            lógica de servidor (por ejemplo al cargar la última nota que
                            complete la carrera), frontend no lo puede resolver acá.
                        --}}
                    </div>

                </div>

            </div>
        </div>

        {{--
            TODO-BACK: estas tablas son un EJEMPLO de cómo tienen que quedar
            armadas las filas (una tabla por año, misma clase "cursada-table",
            mismo orden de columnas). Reemplazar por un @foreach recorriendo
            $materiasPorAnio como en cursadaSec.

            IMPORTANTE para que el cálculo de porcentaje siga funcionando:
            la celda de "Acreditación / Final" tiene que empezar con la
            palabra "Acreditado" cuando la materia está acreditada, y
            cualquier otro texto (ej. "Pendiente") cuando no lo está. El JS
            de analiticoAlumno.js busca ese texto para contar, no hace falta
            tocar nada del JS si respetan ese formato.
        --}}

        <h2 class="anio-titulo">Año 1</h2>
        <div class="table-container">
            <table class="cursada-table">
                <thead>
                    <tr>
                        <th>Espacio Curricular</th>
                        <th>Cursada</th>
                        <th>Acreditación / Final</th>
                        <th>Perfil</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Inglés I</td>
                        <td>Aprobada en 2024 - Nota: 9,00</td>
                        <td>Acreditado el 20/11/2024 - Nota: 8,00</td>
                        <td><button class="btn-primary" onclick="window.location='{{ route('materias.show', 10) }}'">Ver</button></td>
                    </tr>
                    <tr>
                        <td>Pedagogía</td>
                        <td>Aprobada en 2024 - Nota: 7,50</td>
                        <td>Acreditado el 02/12/2024 - Nota: 7,00</td>
                        <td><button class="btn-primary" onclick="window.location='{{ route('materias.show', 11) }}'">Ver</button></td>
                    </tr>
                    <tr>
                        <td>Matemática I</td>
                        <td>Regular en 2024 - Nota: 6,00</td>
                        <td>Pendiente</td>
                        <td><button class="btn-primary" onclick="window.location='{{ route('materias.show', 12) }}'">Ver</button></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h2 class="anio-titulo">Año 2</h2>
        <div class="table-container">
            <table class="cursada-table">
                <thead>
                    <tr>
                        <th>Espacio Curricular</th>
                        <th>Cursada</th>
                        <th>Acreditación / Final</th>
                        <th>Perfil</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Didáctica General</td>
                        <td>Aprobada en 2025 - Nota: 8,00</td>
                        <td>Acreditado el 10/06/2025 - Nota: 8,00</td>
                        <td><button class="btn-primary" onclick="window.location='{{ route('materias.show', 20) }}'">Ver</button></td>
                    </tr>
                    <tr>
                        <td>Psicología Educacional</td>
                        <td>Sin cursar</td>
                        <td>Pendiente</td>
                        <td><button class="btn-primary" onclick="window.location='{{ route('materias.show', 21) }}'">Ver</button></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <button class="btn-primary" type="button" onclick="window.print()">
            <i class="ri-printer-fill"></i> Imprimir
        </button>

    </div>

    @include('partials.footer')

    <script src="{{ asset('js/Secretaria/analiticoAlumno.js') }}"></script>

</body>
</html>