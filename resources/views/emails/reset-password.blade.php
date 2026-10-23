<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar contraseña</title>
</head>
<body style="margin:0; padding:0; background:#f4f4f4; font-family: Arial, sans-serif;">

    <div style="text-align:center; padding:20px; background:#ffffff;">
        <img src="{{ asset('img/logo.png') }}" alt="Logo" style="max-width:150px;">
    </div>

    <div style="max-width:500px; margin:20px auto; background:#ffffff; padding:30px; border-radius:8px;">
        <h2 style="color:#333;">Recuperar contraseña</h2>

        <p>Hola{{ $nombre ? ' ' . $nombre : '' }},</p>

        <p>Recibimos una solicitud para restablecer tu contraseña. Hacé clic en el botón para continuar:</p>

        <p style="text-align:center; margin:30px 0;">
            <a href="{{ $url }}"
               style="background:#2d6cdf; color:#fff; padding:12px 24px; text-decoration:none; border-radius:5px;">
                Restablecer contraseña
            </a>
        </p>

        <p>Si vos no solicitaste este cambio, podés ignorar este email.</p>

        <p>Este link expirará en {{ config('auth.passwords.'.config('auth.defaults.passwords').'.expire') }} minutos.</p>
    </div>

    <footer style="text-align:center; padding:20px; color:#999; font-size:12px;">
        <p>&copy; 2027 - SGI</p>
    </footer>

</body>
</html>