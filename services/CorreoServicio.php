<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/PHPMailer/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\Exception as ExcepcionPHPMailer;
use PHPMailer\PHPMailer\PHPMailer;

class CorreoServicio
{
    public static function enviarRecuperacion(string $correo, string $nombre, string $codigo): bool
    {
        $configuracion = require __DIR__ . '/../config/config.php';
        $modo = strtolower((string) $configuracion['MAIL_MODO']);

        if ($modo === 'log') {
            error_log('Código de recuperación para ' . $correo . ': ' . $codigo, 4);
            return true;
        }

        if ($modo !== 'smtp') {
            error_log('MAIL_MODO debe ser smtp o log.');
            return false;
        }

        $mail = null;

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = (string) $configuracion['MAIL_HOST'];
            $mail->Port = (int) $configuracion['MAIL_PORT'];
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Username = (string) $configuracion['MAIL_USERNAME'];
            $mail->Password = (string) $configuracion['MAIL_PASSWORD'];
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->setFrom(
                (string) $configuracion['MAIL_USERNAME'],
                (string) $configuracion['MAIL_FROM_NAME']
            );
            $mail->addAddress($correo, $nombre);
            $mail->Subject = 'Tu código de recuperación de ITVOmarket';
            $codigoSeguro = htmlspecialchars($codigo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $mail->isHTML(true);
            $mail->Body = '<!doctype html><html lang="es"><body style="margin:0;padding:24px;background:#FDFBF7;font-family:Arial,sans-serif;color:#1F2A24;">'
                . '<main style="max-width:480px;margin:0 auto;padding:24px;background:#FFFFFF;border-radius:24px;">'
                . '<p>Hola,</p><p>Recibimos una solicitud para restablecer tu contraseña.</p>'
                . '<p style="margin:28px 0;font-size:42px;font-weight:700;letter-spacing:10px;color:#2E7D5B;">' . $codigoSeguro . '</p>'
                . '<p>Ingresa este código en ITVOmarket para continuar.</p>'
                . '<p>Este código vence en 15 minutos y solo se puede usar una vez.</p>'
                . '<p>Si no lo solicitaste, ignora este mensaje; tu contraseña no cambia.</p>'
                . '</main></body></html>';
            $mail->AltBody = "Hola,\n\n"
                . "Recibimos una solicitud para restablecer tu contraseña.\n\n"
                . "Tu código de recuperación es: {$codigo}\n\n"
                . "Ingresa este código en ITVOmarket para continuar.\n"
                . "Este código vence en 15 minutos y solo se puede usar una vez.\n\n"
                . "Si no lo solicitaste, ignora este mensaje; tu contraseña no cambia.";

            return $mail->send();
        } catch (ExcepcionPHPMailer $excepcion) {
            $detalle = $mail instanceof PHPMailer && $mail->ErrorInfo !== ''
                ? $mail->ErrorInfo
                : $excepcion->getMessage();
            error_log('No fue posible enviar el correo de recuperación: ' . $detalle);
            return false;
        } catch (Throwable $excepcion) {
            $detalle = $mail instanceof PHPMailer && $mail->ErrorInfo !== ''
                ? $mail->ErrorInfo
                : $excepcion->getMessage();
            error_log('Error al preparar el correo de recuperación: ' . $detalle);
            return false;
        }
    }
}
