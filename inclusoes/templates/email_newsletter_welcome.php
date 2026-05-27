<?php
/**
 * inclusoes/templates/email_newsletter_welcome.php
 * Template HTML premium para e-mail de boas-vindas da newsletter.
 */

function getNewsletterWelcomeTemplate($userName) {
    return '
    <!DOCTYPE html>
    <html lang="pt">
    <head>
        <meta charset="UTF-8">
        <link rel="icon" type="image/png" sizes="32x32" href="../recursos/images/marca/favicon-k-32x32.png">
        <style>
            body { font-family: "Inter", sans-serif; background-color: #f8fafc; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
            .header { background: #0f172a; padding: 40px; text-align: center; }
            .content { padding: 40px; color: #334155; line-height: 1.6; }
            .footer { background: #f1f5f9; padding: 20px; text-align: center; font-size: 12px; color: #64748b; }
            .btn { display: inline-block; padding: 14px 28px; background-color: #f7941d; color: #ffffff !important; text-decoration: none; border-radius: 8px; font-weight: 700; margin-top: 20px; }
            h1 { color: #ffffff; font-family: "Outfit", sans-serif; margin: 0; font-size: 24px; }
            .logo-text { color: #f7941d; font-weight: 900; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>KALIYE</h1>
            </div>
            <div class="content">
                <h2>Olá, ' . htmlspecialchars($userName) . '!</h2>
                <p>É um prazer dar-te as boas-vindas à nossa comunidade oficial de crescimento profissional.</p>
                <p>A partir de agora, serás o primeiro a receber as nossas atualizações exclusivas, insights sobre o ecossistema de Angola e oportunidades estratégicas.</p>
                <p>Estamos ansiosos por acompanhar o teu percurso de sucesso.</p>
                <a href="https://aksanti.ao" class="btn">Explorar Plataforma</a>
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' KALIYE. Todos os direitos reservados.</p>
                <p>Enviado com ❤️ de Luanda para o mundo.</p>
            </div>
        </div>
    </body>
    </html>
    ';
}
