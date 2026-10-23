<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar contraseña</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('css/log.css') }}">
</head>
<body>

    <div class="top">
        <img src="{{ asset('img/logo.png') }}" alt="Logo">
    </div>

    <main>
        <div class="container">

            <h2 class="form-title">Recuperar contraseña</h2>
            <p class="form-subtitle">Ingresá tu email y te enviaremos un link para restablecer tu contraseña.</p>

            {{-- Mensaje de éxito --}}
            @if (session('status'))
                <div class="success-msg">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Mensaje de error --}}
            @if ($errors->any())
                <div class="error-msg">
                    {{ $errors->first('email') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <input
                    type="email"
                    name="email"
                    placeholder="Ingresá tu email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                >

                <button type="submit">Enviar link de recuperación</button>

                <a href="{{ route('login') }}" class="back-link">Volver al login</a>
            </form>
        </div>
    </main>

    <footer>
        <p>@2027 - SGI</p>
    </footer>

</body>
</html>