<div class="sidebar">

    <div>
        <div class="top-icons">
            <i class="ri-logout-box-r-line"
               id="logout-icon"
               title="Cerrar sesión"
               style="cursor:pointer"></i>
        </div>

        <form method="POST" action="{{ route('logout') }}" id="logout-form" class="d-none">
            @csrf
        </form>

        <div class="profile">
            <img src="{{ asset('img/logo.png') }}" alt="Logo">
        </div>

        <div class="menu">

            @switch(auth()->user()->rol->descripcion)

            {{-- ========================================================= --}}
            {{-- SECRETARÍA --}}
            {{-- ========================================================= --}}

            @case('Administrador')
            @case('Secretario')

                <a href="{{ route('carreras.index') }}">
                    <i class="ri-book-line"></i> Carrera
                </a>

                <a href="{{ route('cicloLectivoSec') }}">
                    <i class="ri-calendar-line"></i> Ciclo Lectivo
                </a>

                <a href="{{ route('finalesSec') }}">
                    <i class="ri-award-line"></i> Finales
                </a>

                <div class="menu-item-dropdown">

                    <a href="#" class="dropdown-toggle" id="usuarios-toggle">
                        <i class="ri-user-line"></i> Usuarios
                        <i class="ri-arrow-right-s-line dropdown-arrow"></i>
                    </a>

                    <div class="dropdown-submenu" id="usuarios-submenu">

                        <a href="{{ route('alumnos.index') }}">
                            Alumnos
                        </a>

                        <a href="{{ route('profesoresSec') }}">
                            Profesores
                        </a>

                        <a href="{{ route('administracionSec') }}">
                            Administradores
                        </a>

                    </div>

                </div>

                <a href="#">
                    <i class="ri-graduation-cap-line"></i> Egresados
                </a>

                <a href="{{ route('auditoria') }}">
                    <i class="ri-history-line"></i> Auditoría
                </a>

                <a href="{{ route('misdatos') }}">
                    <i class="ri-home-line"></i> Mis datos
                </a>

            @break


            {{-- ========================================================= --}}
            {{-- ESTUDIANTE --}}
            {{-- ========================================================= --}}
           @case('Estudiante')

                <a href="{{ route('carreraEst') }}">
                    <i class="ri-book-line"></i> Carrera
                </a>

                <a href="#">
                    <i class="ri-calendar-line"></i> Cursada
                </a>

                <a href="{{ route('finales') }}">
                    <i class="ri-award-line"></i> Finales
                </a>

                <a href="#">
                    <i class="ri-clipboard-line"></i>Analitico
                </a>

                <a href="{{ route('misdatos.estudiante') }}">
                    <i class="ri-home-line"></i> Mis datos
                </a>

            @break


            {{-- ========================================================= --}}
            {{-- DOCENTE --}}
            {{-- ========================================================= --}}
            @case('Docente')

                <a href="#">
                    <i class="ri-book-line"></i> Materias
                </a>

                <a href="#">
                    <i class="ri-award-line"></i> Finales
                </a>

                <a href="{{ route('misdatos') }}">
                    <i class="ri-home-line"></i> Mis datos
                </a>
            @break

        @endswitch

        </div>

    </div>

</div>

<div class="overlay" id="overlay"></div>

<script src="{{ asset('js/sidebar.js') }}"></script>
<script>
document.getElementById('logout-icon').addEventListener('click', function () {
    document.getElementById('logout-form').submit();
});
</script>
