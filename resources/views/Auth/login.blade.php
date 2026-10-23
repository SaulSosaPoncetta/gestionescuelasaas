<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('css/log.css') }}">
</head>
<body>

    <div class="top">
        <img src="{{ asset('img/logo.png') }}" alt="Logo">
    </div>

    <main>
        <div class="container">

            {{-- Muestra el error si las credenciales son incorrectas --}}
            @if ($errors->any())
                <div class="error-msg">
                    {{ $errors->first('login') }}
                </div>
            @endif

            {{-- action apunta a la ruta nombrada definida en LoginRoute.php --}}
            <form method="POST" action="{{ route('login.post') }}">
                @csrf

                <input
                    type="text"
                    name="login"
                    placeholder="Usuario, DNI o mail"
                    value="{{ old('login') }}"
                >

                <input type="password" name="password" placeholder="Contraseña">

                <a href="{{ route('password.request') }}">Olvidé mi contraseña</a>

                <div class="captcha">
                    <div class="g-recaptcha" data-sitekey="6LdpLJwsAAAAABCGmdWy4K6OVEmcQPHo8IpSPypS"></div>
                </div>

                <button type="submit">Ingresar</button>
            </form>
        </div>
    </main>

    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    <footer>
        <p>@2027 - SGI</p>
    </footer>

</body>
</html>