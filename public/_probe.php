<?php
require_once __DIR__ . '/../config/config.php';

use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;

$ok = [];
try { new Dompdf();    $ok[] = 'Dompdf OK'; }    catch(Throwable $e){ $ok[]='Dompdf ERROR: '.$e->getMessage(); }
try { new PHPMailer(); $ok[] = 'PHPMailer OK'; } catch(Throwable $e){ $ok[]='PHPMailer ERROR: '.$e->getMessage(); }

echo implode(' | ', $ok);
