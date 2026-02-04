<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Mail\Message;
use Nette\Mail\Mailer;

final class MailService
{
    public function __construct(
        private Mailer $mailer,
        private string $fromEmail = 'noreply@jiznkriz.cz',
        private string $fromName = 'Jižní Kříž Pathfinder',
    ) {
    }

    /**
     * Odešle email s kódem pro reset hesla
     */
    public function sendPasswordResetCode(string $email, string $code, string $userName): void
    {
        $message = new Message;
        $message->setFrom($this->fromEmail, $this->fromName)
            ->addTo($email)
            ->setSubject('Obnova hesla - Jižní Kříž')
            ->setHtmlBody($this->getPasswordResetTemplate($code, $userName));

        $this->mailer->send($message);
    }

    /**
     * Odešle email s 2FA kódem
     */
    public function sendTwoFactorCode(string $email, string $code, string $userName): void
    {
        $message = new Message;
        $message->setFrom($this->fromEmail, $this->fromName)
            ->addTo($email)
            ->setSubject('Přihlašovací kód - Jižní Kříž')
            ->setHtmlBody($this->getTwoFactorTemplate($code, $userName));

        $this->mailer->send($message);
    }

    private function getPasswordResetTemplate(string $code, string $userName): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #0075b5, #005a8c); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f8fafc; padding: 30px; border-radius: 0 0 10px 10px; }
        .code { background: #0075b5; color: white; font-size: 32px; font-weight: bold; letter-spacing: 8px; padding: 20px 40px; border-radius: 10px; display: inline-block; margin: 20px 0; }
        .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Obnova hesla</h1>
        </div>
        <div class="content">
            <p>Ahoj <strong>{$userName}</strong>,</p>
            <p>obdrželi jsme žádost o obnovu hesla k tvému účtu. Použij následující kód pro ověření:</p>
            <div style="text-align: center;">
                <div class="code">{$code}</div>
            </div>
            <p><strong>Kód je platný 15 minut.</strong></p>
            <p>Pokud jsi o obnovu hesla nežádal/a, tento email můžeš ignorovat. Tvé heslo zůstane nezměněno.</p>
        </div>
        <div class="footer">
            <p>Jižní Kříž Pathfinder<br>Tento email byl odeslán automaticky, neodpovídej na něj.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function getTwoFactorTemplate(string $code, string $userName): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #0075b5, #005a8c); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f8fafc; padding: 30px; border-radius: 0 0 10px 10px; }
        .code { background: #ffd600; color: #1f2937; font-size: 32px; font-weight: bold; letter-spacing: 8px; padding: 20px 40px; border-radius: 10px; display: inline-block; margin: 20px 0; }
        .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Přihlašovací kód</h1>
        </div>
        <div class="content">
            <p>Ahoj <strong>{$userName}</strong>,</p>
            <p>tvůj přihlašovací kód pro dvoufázové ověření:</p>
            <div style="text-align: center;">
                <div class="code">{$code}</div>
            </div>
            <p><strong>Kód je platný 10 minut.</strong></p>
            <p>Pokud ses nepokoušel/a přihlásit, někdo se možná pokouší získat přístup k tvému účtu. Doporučujeme změnit heslo.</p>
        </div>
        <div class="footer">
            <p>Jižní Kříž Pathfinder<br>Tento email byl odeslán automaticky, neodpovídej na něj.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
