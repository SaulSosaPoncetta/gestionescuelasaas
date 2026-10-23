<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Materia</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="{{ asset('fonts/remixicon/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/sideBar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filtro.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tablas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/botones.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/materiaUpload.css') }}">
</head>

<body>
    <button class="mobile-menu-btn" id="menu-btn">
    <i class="ri-menu-line"></i>
    </button>
    @include('partials.sidebar')

    <div class="main">

        <div class="form-container">
            <h1>Agregar Materia</h1>

            <form class="materia-form">

                <div class="form-grid">

                    <label>Nombre</label>
                    <input type="text">

                    <label>Plan de estudios</label>
                    <select>
                        <option>Seleccionar</option>
                    </select>

                    <label>Año curricular</label>
                    <select>
                        <option>Seleccionar</option>
                    </select>

                    <label>N° de orden dentro de año</label>
                    <select>
                        <option>Seleccionar</option>
                    </select>

                    <label>Carga horaria anual</label>
                    <input type="number">

                </div>

                <div class="checkbox-group">

                    <label class="checkbox-row">
                        <span>TFO/EDI/UDI</span>
                        <input type="checkbox">
                    </label>

                    <label class="checkbox-row">
                        <span>Acreditable con final</span>
                        <input type="checkbox">
                    </label>

                    <label class="checkbox-row">
                        <span>Promocionable</span>
                        <input type="checkbox">
                    </label>

                    <label class="checkbox-row">
                        <span>Permite libres</span>
                        <input type="checkbox">
                    </label>

                    <label class="checkbox-row">
                        <span>Optativa</span>
                        <input type="checkbox">
                    </label>

                </div>

                <div class="form-actions">
                    <button class="btn-primary">Guardar</button>
                    <button type="button" class="btn-secondary"onclick="window.history.back()">Volver</button>
                </div>

            </form>
        </div>

    </div>

    @include('partials.footer')

</body>
</html>
