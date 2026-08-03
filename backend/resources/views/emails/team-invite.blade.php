<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
</head>
<body style="margin:0; padding:32px 16px; background:#f4f4f5; font-family: -apple-system, Segoe UI, Roboto, sans-serif;">
    <table role="presentation" width="100%" style="max-width:480px; margin:0 auto; background:#ffffff; border-radius:12px; overflow:hidden;">
        <tr>
            <td style="background:#16a34a; padding:20px 28px;">
                <span style="color:#ffffff; font-size:18px; font-weight:600;">Zelo</span>
            </td>
        </tr>
        <tr>
            <td style="padding:28px;">
                <h1 style="margin:0 0 16px; font-size:20px; color:#111827;">Você foi convidado pra equipe de {{ $businessName }}</h1>
                <p style="margin:0 0 20px; font-size:15px; color:#374151; line-height:1.5;">
                    Clique no botão abaixo pra criar sua senha e começar a administrar
                    {{ $businessName }} no Zelo. Esse link expira em 7 dias.
                </p>
                <a href="{{ $url }}" style="display:inline-block; background:#16a34a; color:#ffffff; text-decoration:none; padding:12px 24px; border-radius:8px; font-size:15px; font-weight:600;">
                    Aceitar convite
                </a>
                <p style="margin:20px 0 0; font-size:13px; color:#6b7280; line-height:1.5;">
                    Se você não esperava esse convite, pode ignorar esse e-mail com segurança.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
