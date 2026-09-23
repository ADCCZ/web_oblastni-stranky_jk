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
        private string $fromName = 'Oblast Jižní Kříž - Klub Pathfinder',
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

    private function getEmailTemplate(string $title, string $code, string $userName, string $codeColor, string $codeTextColor, string $bodyText, string $expiryText, string $warningText): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="cs" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{$title}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        @media only screen and (max-width: 620px) {
            .outer-table { width: 100% !important; }
            .inner-pad { padding: 24px 20px !important; }
            .header-pad { padding: 28px 20px !important; }
            .code-box { font-size: 24px !important; letter-spacing: 6px !important; padding: 16px 24px !important; }
            .heading { font-size: 22px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    <!-- Outer wrapper -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f1f5f9;">
        <tr>
            <td align="center" style="padding: 32px 16px;">
                <!-- Card -->
                <table role="presentation" class="outer-table" width="560" cellpadding="0" cellspacing="0" border="0" style="max-width: 560px; width: 100%; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08);">
                    <!-- Header -->
                    <tr>
                        <td class="header-pad" align="center" style="background: linear-gradient(135deg, #0075b5, #005a8c); padding: 36px 32px;">
                            <!--[if mso]><v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="width:560px;"><v:fill type="gradient" color="#0075b5" color2="#005a8c" angle="135"/><v:textbox inset="0,36px,0,36px"><center><![endif]-->
                            <h1 class="heading" style="margin: 0; color: #ffffff; font-size: 26px; font-weight: 700; line-height: 1.3;">{$title}</h1>
                            <!--[if mso]></center></v:textbox></v:rect><![endif]-->
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td class="inner-pad" style="background-color: #ffffff; padding: 32px;">
                            <p style="margin: 0 0 16px; color: #334155; font-size: 15px; line-height: 1.6;">Ahoj <strong style="color: #1e293b;">{$userName}</strong>,</p>
                            <p style="margin: 0 0 24px; color: #334155; font-size: 15px; line-height: 1.6;">{$bodyText}</p>
                            <!-- Code -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" style="padding: 8px 0 24px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td class="code-box" align="center" style="background-color: {$codeColor}; color: {$codeTextColor}; font-size: 32px; font-weight: 700; letter-spacing: 8px; padding: 18px 36px; border-radius: 10px; font-family: 'Courier New', monospace;">
                                                    {$code}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin: 0 0 16px; color: #1e293b; font-size: 14px; font-weight: 600; line-height: 1.6;">{$expiryText}</p>
                            <p style="margin: 0; color: #64748b; font-size: 13px; line-height: 1.6;">{$warningText}</p>
                        </td>
                    </tr>
                    <!-- Divider -->
                    <tr>
                        <td style="background-color: #ffffff; padding: 0 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr><td style="border-top: 1px solid #e2e8f0; font-size: 0; line-height: 0;">&nbsp;</td></tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td class="inner-pad" align="center" style="background-color: #ffffff; padding: 20px 32px 28px;">
                            <p style="margin: 0; color: #94a3b8; font-size: 12px; line-height: 1.5;">
                                Oblast Jižní Kříž - Klub Pathfinder<br>
                                Tento email byl odeslán automaticky, neodpovídej na něj.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    private function getPasswordResetTemplate(string $code, string $userName): string
    {
        return $this->getEmailTemplate(
            'Obnova hesla',
            $code,
            $userName,
            '#0075b5',
            '#ffffff',
            'Obdrželi jsme žádost o obnovu hesla k tvému účtu. Použij následující kód pro ověření:',
            'Kód je platný 15 minut.',
            'Pokud jsi o obnovu hesla nežádal/a, tento email můžeš ignorovat. Tvé heslo zůstane nezměněno.',
        );
    }

    private function getTwoFactorTemplate(string $code, string $userName): string
    {
        return $this->getEmailTemplate(
            'Přihlašovací kód',
            $code,
            $userName,
            '#ffd600',
            '#1f2937',
            'Tvůj přihlašovací kód pro dvoufázové ověření:',
            'Kód je platný 10 minut.',
            'Pokud ses nepokoušel/a přihlásit, někdo se možná pokouší získat přístup k tvému účtu. Doporučujeme změnit heslo.',
        );
    }
}
