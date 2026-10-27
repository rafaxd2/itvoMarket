<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/PHPMailer/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\Exception as ExcepcionPHPMailer;
use PHPMailer\PHPMailer\PHPMailer;

class CorreoServicio
{
    public static function enviarRecuperacion(string $correo, string $nombre, string $enlace): bool
    {
        $configuracion = require __DIR__ . '/../config/config.php';
        $modo = strtolower((string) $configuracion['MAIL_MODO']);

        if ($modo === 'log') {
            error_log('Enlace de recuperación para ' . $correo . ': ' . $enlace, 4);
            return true;
        }

        if ($modo !== 'smtp') {
            error_log('MAIL_MODO debe ser smtp o log.');
            return false;
        }

        try {
            $correoServicio = new PHPMailer(true);
            $correoServicio->isSMTP();
            $correoServicio->Host = (string) $configuracion['MAIL_HOST'];
            $correoServicio->Port = (int) $configuracion['MAIL_PORT'];
            $correoServicio->SMTPAuth = true;
            $correoServicio->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $correoServicio->Username = (string) $configuracion['MAIL_USERNAME'];
            $correoServicio->Password = (string) $configuracion['MAIL_PASSWORD'];
            $correoServicio->CharSet = PHPMailer::CHARSET_UTF8;
            $correoServicio->setFrom(
                (string) $configuracion['MAIL_USERNAME'],
                (string) $configuracion['MAIL_FROM_NAME']
            );
            $correoServicio->addAddress($correo, $nombre);
            $correoServicio->Subject = 'Recupera tu acceso a ITVOmarket';
            $enlaceSeguro = htmlspecialchars($enlace, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $correoServicio->isHTML(true);
            $correoServicio->Body = '<!doctype html><html lang="es"><body style="margin:0;padding:24px;background:#FDFBF7;font-family:Arial,sans-serif;color:#1F2A24;">'
                . '<main style="max-width:480px;margin:0 auto;padding:24px;background:#FFFFFF;border-radius:24px;">'
                . '<p>Hola,</p><p>Recibimos una solicitud para restablecer tu contraseña.</p>'
                . '<p style="margin:28px 0;"><a href="' . $enlaceSeguro . '" style="display:inline-block;padding:14px 22px;background:#2E7D5B;border-radius:20px;color:#FFFFFF;text-decoration:none;">Restablecer contraseña</a></p>'
                . '<p>Si el botón no funciona, abre este enlace:</p><p><a href="' . $enlaceSeguro . '" style="color:#2E7D5B;overflow-wrap:anywhere;">' . $enlaceSeguro . '</a></p>'
                . '<p>Este enlace vence en 15 minutos y solo se puede usar una vez.</p>'
                . '<p>Si no lo solicitaste, ignora este mensaje; tu contraseña no cambia.</p>'
                . '</main></body></html>';
            $correoServicio->AltBody = "Hola,\n\n"
                . "Recibimos una solicitud para restablecer tu contraseña.\n\n"
                . "Restablecer contraseña: {$enlace}\n\n"
                . "Este enlace vence en 15 minutos y solo se puede usar una vez.\n\n"
                . "Si no lo solicitaste, ignora este mensaje; tu contraseña no cambia.";

            return $correoServicio->send();
        } catch (ExcepcionPHPMailer $excepcion) {
            error_log('No fue posible enviar el correo de recuperación: ' . $excepcion->getMessage());
            return false;
        } catch (Throwable $excepcion) {
            error_log('Error al preparar el correo de recuperación: ' . $excepcion->getMessage());
            return false;
        }
    }
}
