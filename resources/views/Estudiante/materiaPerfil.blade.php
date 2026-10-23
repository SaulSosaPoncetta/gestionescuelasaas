<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil Materia</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="{{ asset('fonts/remixicon/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/sideBar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filtro.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tablas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/botones.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/materiaPerfil.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/cursadaPerfil.css') }}">

    @vite(['resources/css/app.css'])
</head>

<body>
    <button class="mobile-menu-btn" id="menu-btn">
        <i class="ri-menu-line"></i>
    </button>

    @include('partials.sidebar')

    <div class="main">

        <button class="btn-modificar" type="button" onclick="window.location='{{ route('planEspacios', $materia->carrera->id_carrera) }}'">
            <i class="ri-arrow-left-line"></i> Volver
        </button>

        <div class="perfil-header">
            <h1>{{ $materia->codigo }} - {{ $materia->nombre }}</h1>
        </div>

        <div class="form-container">

            <div class="form-grid">

                <label for="nombre">Nombre</label>
                <div class="input-group">
                    <input type="text" id="nombre" value="{{ $materia->nombre }}" disabled>
                </div>

                <label for="id_plan_estudio">Plan de estudios</label>
                <div class="input-group">
                    <select id="id_plan_estudio" disabled>
                        <option selected>{{ $materia->planEstudio->titulo ?? '—' }}</option>
                    </select>
                </div>

                <label for="id_anio">Año curricular</label>
                <div class="input-group">
                    <select id="id_anio" disabled>
                        <option selected>{{ $materia->anio_curricular_texto }}</option>
                    </select>
                </div>

                <label for="orden_anio">N° de orden dentro de año</label>
                <div class="input-group">
                    <select id="orden_anio" disabled>
                        <option selected>{{ $materia->orden_anio }}</option>
                    </select>
                </div>

                <label for="carga_horaria_anual">Carga horaria anual</label>
                <div class="input-group">
                    <input type="number" id="carga_horaria_anual" value="{{ $materia->carga_horaria_anual }}" disabled>
                </div>

            </div>

            <div class="checkbox-group">

                <div class="checkbox-row">
                    <label>TFO/EDI/UDI</label>
                    <input type="checkbox" {{ $materia->tfo_edi_udi ? 'checked' : '' }} disabled>
                </div>

                <div class="checkbox-row">
                    <label>Acreditable con final</label>
                    <input type="checkbox" {{ $materia->acreditable_final ? 'checked' : '' }} disabled>
                </div>

                <div class="checkbox-row">
                    <label>Promocionable</label>
                    <input type="checkbox" {{ $materia->promocionable ? 'checked' : '' }} disabled>
                </div>

                <div class="checkbox-row">
                    <label>Optativa</label>
                    <input type="checkbox" {{ $materia->optativa ? 'checked' : '' }} disabled>
                </div>

            </div>

        </div>

        <div>

            <div class="section-divider"></div>

            <h2 class="section-title">Profesores asignados</h2>

            @if(count($profesoresAsignados))

                <div class="table-container">
                    <table id="tablaProfesoresAsignados" class="cursada-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($profesoresAsignados as $prof)
                            <tr>
                                <td>{{ $prof->docente->usuario->apellido }}, {{ $prof->docente->usuario->nombre }}</td>
                                <td>{{ optional($prof->fecha_inicio)->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ optional($prof->fecha_fin)->format('d/m/Y') ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @else

                <div class="empty-state">
                    <i class="ri-user-line"></i>
                    <p>No hay profesores asignados a esta materia.</p>
                </div>

            @endif

        </div>
    </div>

    @include('partials.footer')

    <div class="overlay" id="overlay"></div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {

        // ─── Sidebar ──────────────────────────────────────────────────
        const menuBtn = document.getElementById('menu-btn');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('overlay');

        if (menuBtn) {
            menuBtn.addEventListener('click', function () {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            });
        }

        overlay?.addEventListener('click', function () {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });

        // ─── DataTable de profesores asignados ────────────────────────
        if (document.getElementById('tablaProfesoresAsignados') && window.jQuery) {
            $('#tablaProfesoresAsignados').DataTable({
                dom: 't',
                paging: false,
                order: [[0, 'asc']],
                language: {
                    zeroRecords: 'No se encontraron resultados',
                }
            });
        }

    });
    </script>

</body>
</html>
