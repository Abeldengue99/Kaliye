<?php
// config/mail.php

// CONFIGURAÇÃO DE E-MAIL (SMTP) - BREVO (SENDINBLUE)
// Atualizado com credenciais oficiais Aksanti

define('SMTP_HOST', 'smtp-relay.brevo.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'a18daa001@smtp-brevo.com');
define('SMTP_PASS', 'HF63LJ8hpwWKZjnE');
define('SMTP_FROM_NAME', 'KALIYE');
define('SMTP_FROM_EMAIL', 'alexandrinadeoliveiraale@gmail.com'); // EMAIL VERIFICADO NA BREVO (CONTÁBIL)
// CONFIGURAÇÃO SMS (BREVO API)
define('BREVO_API_KEY', 'HF63LJ8hpwWKZjnE'); // Geralmente começa com xkeysib-

// LOGICA DE ATIVAÇÃO
define('MAIL_ENABLED', true);   // Ativado agora com credenciais válidas
define('MAIL_DEBUG_MODE', false); // Definido como false para produção
