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
                <h1 style="margin:0 0 16px; font-size:20px; color:#111827;">Pagamento confirmado</h1>
                <p style="margin:0 0 12px; font-size:15px; color:#374151; line-height:1.5;">
                    Recebemos o pagamento do seu agendamento de <strong>{{ $appointment->service->name }}</strong>.
                </p>
                <p style="margin:0 0 12px; font-size:15px; color:#374151; line-height:1.5;">
                    Data: <strong>{{ $when }}</strong>
                </p>
                <p style="margin:0; font-size:14px; color:#6b7280; line-height:1.5;">
                    Assim que o estabelecimento confirmar o agendamento, você recebe outro e-mail.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
