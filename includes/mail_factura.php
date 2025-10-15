<?php
// includes/mail_factura.php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/factura_pdf.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Envía la factura del pedido al cliente y opcionalmente BCC al admin.
 */
function enviar_factura_por_correo(PDO $bd, int $pedidoId, string $toEmail, string $toName = '', string $adminEmail = ''): array {
  $pdf = factura_pdf_bytes($bd, $pedidoId);

  $mail = new PHPMailer(true);
  try {
    // ——— Configura SMTP si lo necesitas ———
    // $mail->isSMTP();
    // $mail->Host = 'smtp.gmail.com';
    // $mail->SMTPAuth = true;
    // $mail->Username = 'tu_usuario';
    // $mail->Password = 'tu_password_o_app_password';
    // $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    // $mail->Port = 587;

    $mail->CharSet = 'UTF-8';
    $mail->isHTML(true);

    $mail->setFrom('no-reply@vivero.local', 'Vivero El Prado');
    $mail->addAddress($toEmail, $toName ?: $toEmail);
    if ($adminEmail !== '') $mail->addBCC($adminEmail, 'Administrador');

    $mail->Subject = "Factura pedido #$pedidoId – Vivero El Prado";
    $body = "Hola " . htmlspecialchars($toName ?: 'cliente') . ",<br>
             Adjuntamos la factura de tu pedido <strong>#{$pedidoId}</strong>.<br><br>
             ¡Gracias por tu compra!";
    $mail->Body    = $body;
    $mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));

    // Adjunta el PDF generado en memoria
    $mail->addStringAttachment($pdf, "factura_pedido_{$pedidoId}.pdf", 'base64', 'application/pdf');

    $mail->send();
    return ['ok' => true];
  } catch (Exception $e) {
    return ['ok' => false, 'error' => $mail->ErrorInfo ?: $e->getMessage()];
  }
}
