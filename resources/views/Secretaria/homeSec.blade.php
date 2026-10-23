<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pantalla Principal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('fonts/remixicon/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/sideBar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/homeSec.css') }}">
</head>

<body>
    <button class="mobile-menu-btn" id="menu-btn">
    <i class="ri-menu-line"></i>
    </button>
    @include('partials.sidebar')

    <div class="main">

        <div class="grid">
            <a class="card"><i class="ri-book-line"></i><p>CARRERA</p></a>
            <a class="card"><i class="ri-calendar-line"></i><p>CICLO LECTIVO</p></a>
            <a href="" class="card"><i class="ri-book-open-line"></i><p>CURSADA</p></a>
            <a class="card"><i class="ri-award-line"></i><p>FINALES</p></a>
            <a href="{{ route('alumnos.index') }}" class="card"><i class="ri-book-open-line"></i><p>ALUMNOS</p></a>
            <a href="{{ route('profesoresSec') }}" class="card"><i class="ri-briefcase-line"></i><p>PROFESORES</p></a>
            <a href="{{ route('administracionSec') }}" class="card"><i class="ri-user-line"></i><p>ADMINISTRADORES</p></a>
            <a href="" class="card"><i class="ri-graduation-cap-line"></i><p>EGRESADOS</p></a>
            <a class="card"><i class="ri-file-line"></i><p>CONSTANCIAS</p></a>
            <a class="card"><i class="ri-history-line"></i><p>AUDITORIA</p></a>
            <a class="card"><i class="ri-home-line"></i><p>MIS DATOS</p></a>
        </div>

    </div>

    @include('partials.footer')

    <div class="overlay" id="overlay"></div>

        <script>
            const menuBtn = document.getElementById('menu-btn');
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('overlay');

            menuBtn.addEventListener('click', () => {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            });

            overlay.addEventListener('click', () => {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });
        </script>

</body>
</html>
