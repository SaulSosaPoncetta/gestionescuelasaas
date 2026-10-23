<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer contraseña</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('css/log.css') }}">
</head>
<body>

    <div class="top">
        <img src="{{ asset('img/logo.png') }}" alt="Logo">
    </div>

    <main>
        <div class="container">

            <h2 class="form-title">Restablecer contraseña</h2>

            @if ($errors->any())
                <div class="error-msg">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                <input
                    type="email"
                    name="email"
                    placeholder="Email"
                    value="{{ old('email', $email ?? '') }}"
                    required
                    autofocus
                >

                <input
                    type="password"
                    name="password"
                    placeholder="Nueva contraseña"
                    required
                >

                <input
                    type="password"
                    name="password_confirmation"
                    placeholder="Confirmar nueva contraseña"
                    required
                >

                <button type="submit">Restablecer contraseña</button>
            </form>
        </div>
    </main>

    <footer>
        <p>@2027 - SGI</p>
    </footer>

</body>
</html>